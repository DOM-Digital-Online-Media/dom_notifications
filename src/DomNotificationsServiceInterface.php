<?php

namespace Drupal\dom_notifications;

use Drupal\user\UserInterface;

/**
 * Interface DomNotificationsServiceInterface.
 */
interface DomNotificationsServiceInterface {

  /**
   * Returns notification for given channel. Notification may not be returned
   * depending on internal settings, so please check return value. You also would
   * need to save entity yourself or adjust notification
   *
   * @param string $channel_id
   *   Channel ID for notification i.e. plugin id of channel plugin.
   * @param array $fields
   *   Associative array of notification fields and values to set, containing:
   *     - related_entity: object implementing EntityInterface which is related
   *       to an entity i.e. comment or vote etc.;
   *     - redirect_uri: string uri to use static redirect path for notification;
   *     - ... any Field API field;
   * @param string $message
   *   Notification message, can be omitted only if channel provides default.
   * @param \Drupal\user\UserInterface|NULL $recipient
   *   User for which notification will be created. Defaults to current user.
   * @param \Drupal\user\UserInterface|NULL $sender
   *   User which initiated notification creation. Falls back to author of
   *   related entity, then to current user.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface|null
   */
  public function addNotification($channel_id, array $fields = [], $message = '', UserInterface $recipient = NULL, UserInterface $sender = NULL);

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
