<?php

namespace Drupal\dom_notifications;

use Drupal\user\UserInterface;

/**
 * Interface DomNotificationsServiceInterface.
 */
interface DomNotificationsServiceInterface {

  /**
   * Creates notification for given channel. Notification may not be returned
   * depending on internal settings, so please check return value.
   *
   * @param string $channel_id
   *   Channel ID for notification i.e. plugin id of channel plugin.
   * @param string $message
   *   Notification message, can be omitted only if channel provides default.
   * @param array $fields
   *   Associative array of notification fields and values to set.
   * @param \Drupal\user\UserInterface|NULL $user
   *   User for which notification will be created. Defaults to current user.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface|null
   */
  public function addNotification($channel_id, $message = '', array $fields = [], UserInterface $user = NULL);

  /**
   * Retrieve array of notifications for the user or for current user.
   *
   * @param \Drupal\user\UserInterface|NULL $user
   *   User object for which take notifications. Current user if not supplied.
   * @param array $filters
   *   Array of additional filters like id, uuid, channel of notification to fetch.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface[]
   */
  public function fetchNotifications(UserInterface $user = NULL, array $filters = []);

  /**
   * Returns all channels user subscribed to.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User for which return all notification channel.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  public function fetchUserChannels(UserInterface $user = NULL);

  /**
   * Returns notifications channel manager service instance.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface
   */
  public function getChannelManager();

  /**
   * Saves notification settings to configs to use later.
   *
   * @param array $settings
   *   Array of notification settings in key value format.
   */
  public function setNotificationsSettings(array $settings);

  /**
   * Returns current notification settings or defaults if not set.
   *
   * @return array
   */
  public function getNotificationsSettings();

  /**
   * Removes all the notifications that are kept for more time than configured.
   *
   * @return boolean
   *   TRUE if there were notifications to clean up and FALSE otherwise.
   */
  public function executeCleaningUp();

}
