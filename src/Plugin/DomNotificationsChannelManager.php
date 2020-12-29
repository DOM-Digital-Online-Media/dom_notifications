<?php

namespace Drupal\dom_notifications\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the Dom Notifications channel plugin manager.
 */
class DomNotificationsChannelManager extends DefaultPluginManager implements DomNotificationsChannelManagerInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * List of already instantiated channel plugins.
   *
   * @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  protected $instances = [];

  /**
   * Constructs a new DomNotificationsChannelManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, Connection $database, LoggerChannelInterface $logger) {
    parent::__construct('Plugin/DomNotificationsChannel', $namespaces, $module_handler, 'Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface', 'Drupal\dom_notifications\Annotation\DomNotificationsChannel');
    $this->alterInfo('dom_notifications_dom_notifications_channel_info');
    $this->setCacheBackend($cache_backend, 'dom_notifications_dom_notifications_channel_plugins');

    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $definition = $this->getDefinition($plugin_id);
    $class = DefaultFactory::getPluginClass($plugin_id, $definition, '\Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface');
    return $class::create(\Drupal::getContainer(), $configuration, $plugin_id, $definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getPluginIDBySpecificChannel($channel_id) {
    if (mb_strpos($channel_id, ':') === FALSE && $this->hasDefinition($channel_id)) {
      return $channel_id;
    }
    $base_ids = [];
    $specific_channels = $this->getSpecificChannels();
    foreach ($specific_channels as $channel) {
      $base_ids[$channel->getChannelBaseID()] = $channel->id();
    }

    // Retrieve channel without last part and see if it matches base channel ids.
    do {
      $base_id = implode(':', array_slice(explode(':', $channel_id), 0, -1));
    } while (!empty($base_id) && !array_key_exists($base_id, $base_ids));

    // If we shrank base id to an empty string that means we have not found matching channel.
    if (!array_key_exists($base_id, $base_ids)) {
      throw new PluginNotFoundException($channel_id, $this->t('Could not find notifications channel matching this specific channel ID.'));
    }

    return $base_ids[$base_id];
  }

  /**
   * {@inheritDoc}
   */
  public function getAllChannels() {
    $return = [];
    foreach ($this->getDefinitions() as $definition) {
      $return[] = $this->createInstance($definition['id']);
    }
    return $return;
  }

  /**
   * {@inheritDoc}
   */
  public function getBaseChannels() {
    $return = [];
    foreach ($this->getAllChannels() as $channel) {
      if ($channel->isBase()) {
        $return[] = $channel;
      }
    }
    return $return;
  }

  /**
   * {@inheritDoc}
   */
  public function getSpecificChannels() {
    $return = [];
    foreach ($this->getAllChannels() as $channel) {
      if (!$channel->isBase()) {
        $return[] = $channel;
      }
    }
    return $return;
  }

}
