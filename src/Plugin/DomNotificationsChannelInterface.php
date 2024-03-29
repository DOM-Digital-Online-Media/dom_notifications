<?php

namespace Drupal\dom_notifications\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dom_notifications\Entity\DomNotificationInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Uri;

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
  public function getChannelBaseID();

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
   * Returns whether channel has different computed and plugin id,
   * i.e. channel only for one user or for user with specific field value.
   *
   * @return boolean
   */
  public function isComputed();

  /**
   * Returns whether the channel is limited to notifications for one user.
   *
   * @return boolean
   */
  public function isIndividual();

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
   * Returns default link for the channel if it is set.
   *
   * @return string|null
   */
  public function getDefaultLink();

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
   * Returns specific channel IDs for channel instance looking at config values.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities to retrieve computed channel id, like user or content.
   *
   * @return string[]
   *   Returns empty array if $entities is not sufficient for the channel, i.e.
   *   does not have some field value required for the channel etc.
   */
  public function getComputedChannelIds(array $entities = []);

  /**
   * Returns whether channel uses related entity uri as notification uri.
   *
   * @return boolean
   */
  public function useEntityUri();

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
   *   TRUE if user wants to receive alerts from the channel.
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

  /**
   * Executed on notification save method so the channel can provide own logic.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification being saved.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface|null
   *   Return the same notification with changed values
   *   or NULL if it should not be created.
   */
  public function onNotificationSave(DomNotificationInterface $notification);

  /**
   * Alters notification uri for the notification, allows channels
   * to provide own uri depending on env.
   *
   * @param \GuzzleHttp\Psr7\Uri $uri|null
   *   Redirect uri or the notification.
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification to get redirect uri for.
   *
   * @return \GuzzleHttp\Psr7\Uri|string
   */
  public function alterRedirectUri(DomNotificationInterface $notification, Uri $uri = NULL);

  /**
   * Internal function that allows channels to define message placeholders.
   *
   * @return array
   *   Returns array of placeholders the channel provides, keys are placeholders
   *   which will be used on TranslatableMarkup and the value are an array
   *   describing placeholders with required fields 'name' and 'callback'.
   *   Callback will be called with notification as an argument to use output
   *   as placeholder value. Name should be user friendly.
   */
  public function getChannelPlaceholderInfo();

  /**
   * Return related entity for stacking functionality. By default it returns
   * null so stacking works for simple channels, however there might be a case
   * when some channels want stack to be related to channel and entity.
   *
   * For example, if we have a comment notification then developers may want
   * notifications to be stacked for content that's been commented on and not
   * stack just all comments into one pile.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification entity to return stack related entity for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Entity for which stack should be related or NULL.
   */
  public function getStackRelatedEntity(DomNotificationInterface $notification);

}
