<?php

namespace Drupal\dom_notifications;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface;
use Drupal\user\UserInterface;
use Drupal\views\Views;

/**
 * Class DomNotificationsService.
 */
class DomNotificationsService implements DomNotificationsServiceInterface {
  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * Notifications channel plugin manager.
   *
   * @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface
   */
  protected $channelManager;

  /**
   * Config factory for managing notification settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $datetime;

  /**
   * Constructs a new DomNotificationsService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface $channel_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, AccountProxyInterface $current_user, DomNotificationsChannelManagerInterface $channel_manager, ConfigFactoryInterface $config_factory, TranslationInterface $translation, TimeInterface $datetime) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->currentUser = $entity_type_manager->getStorage('user')->load($current_user->id());
    $this->channelManager = $channel_manager;
    $this->configFactory = $config_factory;
    $this->setStringTranslation($translation);
    $this->datetime = $datetime;
  }

  /**
   * {@inheritDoc}
   */
  public function addNotification($channel_id, array $fields = [], $message = '', UserInterface $recipient = NULL, UserInterface $sender = NULL) {
    $recipient_user = $recipient ?? $this->currentUser;
    $sender_user = $sender;

    // Fetch related entity and redirect uri.
    $related_entity = $fields['related_entity'] ?? NULL;
    $redirect_uri = $fields['redirect_uri'] ?? NULL;
    unset($fields['related_entity'], $fields['redirect_uri']);

    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $notification */
    $notification = $this->entityTypeManager->getStorage('dom_notification')->create([
      'message' => !empty($message) ? $message : NULL,
      'status' => 1,
    ] + $fields);

    // Prepare configs array with related recipient and entity if possible.
    $configs = ['recipient' => $recipient_user];
    if ($related_entity instanceof EntityInterface) {
      $notification->setRelatedEntity($related_entity);
      $configs['entity'] = $related_entity;

      if (!$sender_user) {
        $sender_user = $configs['entity']->getOwner();
      }
    }
    if ($redirect_uri) {
      $notification->setRedirectUri($redirect_uri);
    }

    $channel = $this->getChannelManager()->createInstance($channel_id, $configs);
    $computed_channel_id = $channel->getComputedChannelId();

    // If computed channel ID is empty than user is not sufficient for channel.
    if (empty($computed_channel_id)) {
      return NULL;
    }
    $notification->setChannelID($computed_channel_id);

    // Fallback to current user if no user passed and no related entity available.
    $notification->setOwner($sender_user ?? $this->currentUser);

    // Allow channel provide own onSave logic and abort notification creation
    // if needed.
    return $channel->onNotificationSave($notification);
  }

  /**
   * {@inheritDoc}
   */
  public function fetchNotifications(UserInterface $user = NULL, array $filters = []) {
    $account = $user ?? $this->currentUser;
    $notifications = [];

    if ($view = Views::getView('dom_user_notifications')) {
      $view->setDisplay('rest_get');
      $view->setArguments([$account->id()]);
      $view->setExposedInput($filters);
      $view->execute();

      foreach ($view->result as $resultRow) {
        $notifications[] = $resultRow->_entity;
      }
    }

    return $notifications;
  }

  /**
   * {@inheritDoc}
   */
  public function fetchUserChannels(UserInterface $user = NULL) {
    $account = $user ?? $this->currentUser;

    $channel_plugin_ids = $this->database
      ->select('dom_notifications_user_channels', 'dnuc')
      ->fields('dnuc', ['channel_plugin_id'])
      ->condition('dnuc.uid', $account->id())
      ->execute()
      ->fetchCol();

    $channels = [];
    foreach ($channel_plugin_ids as $plugin_id) {
      $channels[$plugin_id] = $this->getChannelManager()->createInstance($plugin_id);
    }
    return $channels;
  }

  /**
   * {@inheritDoc}
   */
  public function getChannelManager() {
    return $this->channelManager;
  }

  /**
   * {@inheritDoc}
   */
  public function setNotificationsSettings(array $settings) {
    $this->configFactory
      ->getEditable('dom_notifications.settings')
      ->setData($settings)
      ->save();
  }

  /**
   * {@inheritDoc}
   */
  public function getNotificationsSettings() {
    return $this->configFactory->get('dom_notifications.settings')->getRawData();
  }

  /**
   * {@inheritDoc}
   */
  public function executeCleaningUp() {
    $settings = $this->getNotificationsSettings();
    $months = $settings['keep_notification_months'];
    $request_time = $this->datetime->getRequestTime();
    $storage = $this->entityTypeManager->getStorage('dom_notification');

    // Calculate a timestamp $months months before today.
    $oldest_allowed = $request_time - $months * 30 * 24 * 60 * 60;
    $ids = $storage
      ->getQuery()
      ->condition('created', $oldest_allowed, '<')
      ->addTag('dom_notification_omit_access')
      ->execute();

    if ($notifications = $storage->loadMultiple($ids)) {
      $storage->delete($notifications);
      return TRUE;
    }
    return FALSE;
  }

}
