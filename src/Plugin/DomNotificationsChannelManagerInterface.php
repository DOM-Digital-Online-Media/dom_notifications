<?php

namespace Drupal\dom_notifications\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Describes basic method for notifications channel manager.
 */
interface DomNotificationsChannelManagerInterface extends PluginManagerInterface {

  /**
   * Returns channel plugin ID so the plugin can be determined from specific
   * channel ID instance.
   * For example, returns article:[uid] for article:2 if the channel is only for
   * user-specific articles.
   * It's important to use [channel]:[id] or [channel]:[sub-channel]:[id]
   * pattern for this method to work, so use : as a delimiter and put id in the end.
   *
   * @param string $channel_id
   *   Channel ID of a particular channel instance like article:2.
   *
   * @return string
   */
  public function getPluginIDBySpecificChannel($channel_id);

  /**
   * Returns channel instances for all declared channel plugins.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  public function getAllChannels();

  /**
   * Returns channel instances only for declared base channel plugins.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  public function getBaseChannels();

  /**
   * Returns channel instances only for declared specific channel plugins.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  public function getSpecificChannels();

}
