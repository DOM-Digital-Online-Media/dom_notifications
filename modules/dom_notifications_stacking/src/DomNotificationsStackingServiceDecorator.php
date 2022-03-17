<?php

namespace Drupal\dom_notifications_stacking;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dom_notifications\DomNotificationsServiceInterface;
use Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface;
use Drupal\user\UserInterface;

/**
 * Decorates notifications service to apply stacking logic.
 */
class DomNotificationsStackingServiceDecorator implements DomNotificationsServiceInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;

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
   * Decorated notifications service.
   *
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $original;

  /**
   * Notifications stacking service.
   *
   * @var \Drupal\dom_notifications_stacking\DomNotificationsStackingServiceInterface
   */
  protected $stackingService;

  /**
   * Constructs a new DomNotificationsService object.
   *
   * @param \Drupal\dom_notifications\DomNotificationsServiceInterface $original
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface $channel_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   * @param \Drupal\Component\Datetime\TimeInterface $datetime
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(DomNotificationsServiceInterface $original, EntityTypeManagerInterface $entity_type_manager, Connection $database, AccountProxyInterface $current_user, DomNotificationsChannelManagerInterface $channel_manager, ConfigFactoryInterface $config_factory, TranslationInterface $translation, TimeInterface $datetime, DomNotificationsStackingServiceInterface $stacking_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->currentUser = $entity_type_manager->getStorage('user')->load($current_user->id());
    $this->channelManager = $channel_manager;
    $this->configFactory = $config_factory;
    $this->setStringTranslation($translation);
    $this->datetime = $datetime;

    $this->original = $original;
    $this->stackingService = $stacking_service;
  }

  /**
   * @inheritDoc
   */
  public function addNotification($channel_id, array $fields = [], $message = '', UserInterface $recipient = NULL, UserInterface $sender = NULL) {
    $entity = $this->original
      ->addNotification($channel_id, $fields, $message, $recipient, $sender);

    // Get stacking settings for notification's channel.
    $stacking_channels = $this->configFactory
      ->get('dom_notifications_stacking.settings')->get('channels');
    $stacking_channels = array_combine(array_column($stacking_channels, 'channel_plugin'), $stacking_channels);
    $enabled = isset($stacking_channels[$channel_id]['stack'])
      && $stacking_channels[$channel_id]['stack'] > 1;

    // If stacking is not enabled for a channel then don't do anything.
    if ($enabled && $entity) {

      // If stacking is enabled we get current stack and add current notification.
      $current_stack = $this->stackingService->getCurrentStackSize($entity) + 1;
      $this->stackingService->setCurrentStackSize($entity, $current_stack);

      // If we reached stack size one or several times i.e. 5/10/15 then
      // produce stacked notification with current stack size.
      if (($current_stack === $stacking_channels[$channel_id]['stack'])
    || ($current_stack % $stacking_channels[$channel_id]['stack'] === 0)) {
        $entity->set('stack_size', $current_stack);
        $entity->setMessage($stacking_channels[$channel_id]['message']);
        if (!empty($stacking_channels[$channel_id]['uri'])) {
          $entity->setRedirectUri($stacking_channels[$channel_id]['uri']);
        }

        // If current stack exceeded stack size, we need to remove the previous
        // stacked notification.
        if ($current_stack > $stacking_channels[$channel_id]['stack']) {
          $previous_stack = $current_stack - $stacking_channels[$channel_id]['stack'];
          $storage = $this->entityTypeManager->getStorage('dom_notification');
          $previous = $storage->loadByProperties([
            'channel_id' => $entity->getChannelID(),
            'stack_size' => $previous_stack,
          ]);
          /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $notification */
          foreach ($previous as $notification) {
            // Do save instead of straight up delete as it's faster.
            $notification->setPublished(FALSE)->save();
          }
        }
      }

      // Do not produce notification if we reached stack size once.
      elseif ($current_stack > $stacking_channels[$channel_id]['stack']) {
        return NULL;
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchNotifications(UserInterface $user = NULL, array $filters = []) {
    return $this->original->fetchNotifications($user, $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUserChannels(UserInterface $user = NULL) {
    return $this->original->fetchUserChannels($user);
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelManager() {
    return $this->original->getChannelManager();
  }

  /**
   * {@inheritdoc}
   */
  public function setNotificationsSettings(array $settings) {
    return $this->original->setNotificationsSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationsSettings() {
    return $this->original->getNotificationsSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function executeCleaningUp() {
    return $this->original->executeCleaningUp();
  }

}
