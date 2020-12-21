<?php

namespace Drupal\dom_notifications\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserInterface;

/**
 * Defines an interface for Dom Notifications channel plugins.
 */
interface DomNotificationsChannelInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns channel plugin id.
   *
   * @return string
   */
  public function id();

  /**
   * Returns base ID for the channel i.e. channel ID without changeable parts.
   *
   * @return string
   */
  public function getBaseID();

  /**
   * Returns TRUE if channel if a base channel .i.e not for specific user etc.
   *
   * @return boolean
   */
  public function isBase();

  /**
   * Returns whether it's allowed for user to mute the channel.
   *
   * @return boolean
   */
  public function isMuteAllowed();

  /**
   * Returns human-readable label for the channel.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns default message for the channel if it is set.
   *
   * @return string|null
   */
  public function getDefaultMessage();

  /**
   * Returns base channel for current specific channel if exists.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface|null
   */
  public function getBaseChannel();

  /**
   * Returns all specific channels for current base channel.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface[]
   */
  public function getSpecificChannels();

  /**
   * Returns specific channel ID for channel instance.
   *
   * @param \Drupal\user\UserInterface|null
   *   User instance to return specific channel ID for like articles:[uid].
   *
   * @return string
   */
  public function getComputedChannelID(UserInterface $user = NULL);

  /**
   * Returns boolean indicating whether user is subscribed to the channel.
   *
   * @param $uid
   *   User ID of user to check.
   *
   * @return boolean
   */
  public function isSubscribed($uid);

  /**
   * Returns array of User IDs for users that are subscribed to the channel.
   *
   * @return integer[]
   */
  public function getSubscribedUsers();

  /**
   * Subscribe given users to the channel.
   *
   * @param integer[] $users
   *   Array of User IDs.
   * @param boolean $notify
   *   (optional) Parameter to enable alerts for the channel. TRUE by default.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function subscribeUsers(array $users, $notify = TRUE);

  /**
   * Unsubscribe given users from the channel.
   *
   * @param integer[] $users
   *   Array of User IDs.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function unsubscribeUsers(array $users);

  /**
   * Unsubscribe all users for the channel.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function unsubscribeAll();

  /**
   * Deletes channel instance i.e. removes all user subscriptions and related
   * notifications. Also remove channel instance related data if such exists.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function delete();

  /**
   * Returns whether or not user receives alerts for the channel.
   *
   * @param integer $uid
   *   User ID.
   *
   * @return boolean
   */
  public function getAlertsStatus($uid);

  /**
   * Enable or disable notification alerts for channel.
   *
   * @param integer $uid
   *   User ID.
   * @param bool $status
   *   Status for notification alerts to set. True by default to enable alerts.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function setAlertsStatus($uid, $status = TRUE);

}
