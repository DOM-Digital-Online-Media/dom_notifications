<?php

namespace Drupal\dom_notifications\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dom_notifications\Entity\DomNotificationInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Dom Notifications channel plugins.
 */
class DomNotificationsChannelBase extends PluginBase implements DomNotificationsChannelInterface {
  use StringTranslationTrait;

  /**
   * Notification channel manager service.
   *
   * @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface
   */
  protected $channelManager;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cache object.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * Array of configuration supplied with the channel.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomNotificationsChannelManagerInterface $channel_manager, Connection $database, EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $invalidator, TranslationInterface $translation, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->channelManager = $channel_manager;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->invalidator = $invalidator;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.dom_notifications_channel'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function id() {
    return $this->getPluginId();
  }

  /**
   * {@inheritDoc}
   */
  public function getChannelBaseID() {
    $definition = $this->getPluginDefinition();
    return $definition['base_id'] ?? $this->id();
  }

  /**
   * {@inheritDoc}
   */
  public function isBase() {
    $definition = $this->getPluginDefinition();
    return !isset($definition['base']);
  }

  /**
   * {@inheritDoc}
   */
  public function isMuteAllowed() {
    $definition = $this->getPluginDefinition();
    return $this->isBase()
      ? $definition['allow_mute'] ?? TRUE
      : $this->getBaseChannel()->isMuteAllowed();
  }

  /**
   * {@inheritDoc}
   */
  public function isComputed() {
    return $this->id() !== $this->getChannelBaseID();
  }

  /**
   * {@inheritDoc}
   */
  public function isIndividual() {
    $definition = $this->getPluginDefinition();
    return $definition['individual'] ?? FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getLabel() {
    $definition = $this->getPluginDefinition();
    return $definition['label']->__toString();
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultMessage() {
    $definition = $this->getPluginDefinition();
    return $definition['default_message'] ?? NULL;
  }
  /**
   * {@inheritDoc}
   */
  public function getDefaultLink() {
    $definition = $this->getPluginDefinition();
    return $definition['default_link'] ?? NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getBaseChannel() {
    if ($this->isBase()) {
      return NULL;
    }
    $definition = $this->getPluginDefinition();
    return $this->channelManager->createInstance($definition['base']);
  }

  /**
   * {@inheritDoc}
   */
  public function getSpecificChannels() {
    if (!$this->isBase()) {
      return [];
    }
    $return = [];
    $channels = $this->channelManager->getSpecificChannels();
    foreach ($channels as $channel) {
      if ($channel->getBaseChannel()->id() === $this->id()) {
        $return[] = $channel;
      }
    }
    return $return;
  }

  /**
   * {@inheritDoc}
   */
  public function getComputedChannelIds(array $entities = []) {
    return [$this->id()];
  }

  /**
   * {@inheritDoc}
   */
  public function useEntityUri() {
    $definition = $this->getPluginDefinition();
    return !empty($definition['use_entity_uri'])
      ? (bool) $definition['use_entity_uri']
      : FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function isSubscribed($uid) {
    return $this->database
      ->select('dom_notifications_user_channels', 'dnuc')
      ->condition('dnuc.channel_plugin_id', $this->id())
      ->condition('dnuc.uid', $uid)
      ->countQuery()
      ->execute()
      ->fetchField() === '1';
  }

  /**
   * {@inheritDoc}
   */
  public function getSubscribedUsers() {
    return $this->database
      ->select('dom_notifications_user_channels', 'dnuc')
      ->fields('dnuc', ['uid'])
      ->condition('channel_plugin_id', $this->id())
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritDoc}
   */
  public function subscribeUsers(array $users, $notify = TRUE) {
    // Retrieve array of users that are not subscribed and clear cache for them.
    $users_to_add = array_diff($users, $this->getSubscribedUsers());

    // Determine whether we need user object to get computed channel ID.
    $is_computed = $this->isComputed();

    $query = $this->database->insert('dom_notifications_user_channels');
    $query->fields(['uid', 'channel_id', 'channel_plugin_id', 'notify']);
    foreach ($users_to_add as $uid) {
      // Do not load user if we don't have computed channel name
      // that requires user object.
      $user = NULL;
      if ($is_computed) {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager->getStorage('user')->load($uid);
      }

      if ($computed_channel_ids = $this->getComputedChannelIds(['user' => $user])) {
        foreach ($computed_channel_ids as $computed_channel_id) {
          $query->values([
            $uid,
            $computed_channel_id,
            $this->id(),
            (int) $notify
          ]);
        }
      }
    }

    $this->invalidator->invalidateTags(array_map(function ($id) {
      return 'user:' . $id;
    }, $users_to_add));
    $this->invalidateNotificationCaches();

    try {
      $query->execute();
    }
    catch (IntegrityConstraintViolationException $exception) {
      // Maybe user was subscribed after we checked, so just in case.
    }

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function unsubscribeUsers(array $users) {
    $query = $this->database
      ->delete('dom_notifications_user_channels')
      ->condition('channel_plugin_id', $this->id())
      ->condition('uid', $users, 'IN');

    $this->invalidator->invalidateTags(array_map(function ($id) {
      return 'user:' . $id;
    }, $users));
    $this->invalidateNotificationCaches();

    return $query->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function getAlertsStatus($uid) {
    return (bool) $this->database
      ->select('dom_notifications_user_channels', 'dnuc')
      ->fields('dnuc', ['notify'])
      ->condition('uid', $uid)
      ->condition('channel_plugin_id', $this->id())
      ->execute()
      ->fetchField();

  }

  /**
   * {@inheritDoc}
   */
  public function setAlertsStatus($uid, $status = TRUE) {
    $this->database
      ->update('dom_notifications_user_channels')
      ->fields(['notify' => (int) $status])
      ->condition('uid', $uid)
      ->condition('channel_plugin_id', $this->id())
      ->execute();

    foreach ($this->getSpecificChannels() as $channel) {
      $channel->setAlertsStatus($uid, $status);
    }

    // Invalidate caches.
    $this->invalidator->invalidateTags(['user:' . $uid]);
    $this->invalidateNotificationCaches();

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function unsubscribeAll() {
    $this->invalidator->invalidateTags(array_map(function ($id) {
      return 'user:' . $id;
    }, $this->getSubscribedUsers()));

    $this->database
      ->delete('dom_notifications_user_channels')
      ->condition('channel_plugin_id', $this->id())
      ->execute();

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function delete() {
    $query = $this->database->select('dom_notification_field_data', 'dnfd');
    $query->addJoin('INNER', 'dom_notifications_user_channels', 'dnuc', 'dnfd.channel_id = dnuc.channel_id');
    $query->fields('dnfd', ['id']);
    $query->condition('dnuc.channel_plugin_id', $this->id());
    $notification_ids = $query->execute()->fetchCol();

    // Clear up all the related notifications.
    if (!empty($notification_ids)) {
      $notification_storage = $this->entityTypeManager->getStorage('dom_notification');
      foreach (array_chunk($notification_ids, 1000) as $ids) {
        $notification_storage->delete($notification_storage->loadMultiple($ids));
      }
    }

    if ($this->moduleHandler->moduleExists('dom_notifications_stacking')) {
      $this->database
        ->delete('dom_notifications_stacking')
        ->condition('channel_plugin_id', $this->id())
        ->execute();
    }

    // Clear user subscriptions.
    $this->unsubscribeAll();

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function alterRedirectUri(DomNotificationInterface $notification, Uri $uri = NULL) {
    return $uri;
  }

  /**
   * {@inheritDoc}
   */
  public function onNotificationSave(DomNotificationInterface $notification) {
    $message = $notification->getMessage();
    if (empty($message) && ($channel_msg = $this->getDefaultMessage())) {
      $notification->setMessage($channel_msg);
    }

    $uri = $notification->retrieveRedirectUri();
    if (empty($uri->__toString()) && ($channel_uri = $this->getDefaultLink())) {
      $notification->setRedirectUri(new Uri($channel_uri));
    }

    return $notification;
  }

  /**
   * {@inheritDoc}
   */
  public function getChannelPlaceholderInfo() {
    $count_info = $this->moduleHandler->moduleExists('dom_notifications_stacking')
      ? $this->getStackedNotificationCountInfo()
      : [];
    return [
      '@author' => [
        'name' => $this->t('Author name'),
        'callback' => [get_called_class(), 'getChannelReplaceAuthor'],
      ],
    ] + $count_info;
  }

  /**
   * Returns additional placeholder info if stacking is enabled.
   *
   * @return array
   */
  protected function getStackedNotificationCountInfo() {
    return [
      '@count' => [
        'name' => $this->t('Messages count'),
        'callback' => [get_called_class(), 'getChannelReplaceCountStacking'],
      ],
    ];
  }

  /**
   * Returns notification count for stacked notifications.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   */
  public static function getChannelReplaceCountStacking(DomNotificationInterface $notification) {
    return $notification->getStackSize();
  }

  /**
   * Returns notification author name to use as a placehodler.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   */
  public static function getChannelReplaceAuthor(DomNotificationInterface $notification) {
    return $notification->getOwner()->getDisplayName();
  }

  /**
   * {@inheritDoc}
   */
  public function getStackRelatedEntity(DomNotificationInterface $notification) {
    return NULL;
  }

  /**
   * Helper function to invalidate entity cache for notification on the channel.
   */
  private function invalidateNotificationCaches() {
    $tags = ['dom_notifications:channel:' . $this->id()];
    foreach ($this->getSpecificChannels() as $channel) {
      $tags[] = 'dom_notifications:channel:' . $channel->id();
    }
    $this->invalidator->invalidateTags($tags);
  }

  /**
   * Returns entity object of an entity type from supplied configuration.
   *
   * @param string $entity_type_id
   *   Entity type of an entity to fetch.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Additional entities to fetch from.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function fetchEntityFromConfiguration($entity_type_id, array $entities = []) {
    if ($entity_info = $this->entityTypeManager->getDefinition($entity_type_id)) {
      foreach (array_merge($entities, $this->configuration) as $entity) {
        $class = $entity_info->getClass();
        if ($entity instanceof $class) {
          return $entity;
        }
      }
    }
    return NULL;
  }

}
