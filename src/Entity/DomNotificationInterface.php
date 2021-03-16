<?php

namespace Drupal\dom_notifications\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Psr\Http\Message\UriInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Dom Notification entities.
 *
 * @ingroup dom_notifications
 */
interface DomNotificationInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Sets notification message for the notification.
   *
   * @param string $message
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   */
  public function setMessage(string $message);

  /**
   * Returns message for the notification from db.
   *
   * @return string|null
   */
  public function getMessage();

  /**
   * Returns finalised message for notification without placeholders.
   */
  public function retrieveMessage();

  /**
   * Returns redirect Uri for the notification based on it's channel and settings.
   *
   * @return \Psr\Http\Message\UriInterface
   */
  public function retrieveRedirectUri();

  /**
   * Sets related entity for notification i.e. comment which has been created etc.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object, NULL to make empty.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   */
  public function setRelatedEntity(EntityInterface $entity = NULL);

  /**
   * Returns related entity for notification if it exists.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getRelatedEntity();

  /**
   * Get a list of all recipients for the notification.
   *
   * @return string[]
   *   User ids.
   */
  public function getRecipients();

  /**
   * Sets redirect Uri for notification if it's related to page, not particular
   * entity.
   *
   * @param \Psr\Http\Message\UriInterface|string $uri
   *   Uri to set, either string or object implementing UriInterface.
   *   Set NULL to unset.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   */
  public function setRedirectUri($uri = NULL);

  /**
   * Returns redirect Uri for notification if it exists.
   *
   * @return \Psr\Http\Message\UriInterface|null
   */
  public function getRedirectUri();

  /**
   * Returns stack size for notification i.e. if notification is related to
   * number of events this returns the number. If notification is not stacked
   * or stacking submodule disabled then return 1.
   *
   * @return integer
   */
  public function getStackSize();

  /**
   * Return whether notification is seen by certain user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User to take notifications for. Current user if not supplied.
   *
   * @return boolean
   */
  public function isSeen(UserInterface $user = NULL);

  /**
   * Marks notification as seen for certain user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User that has seen the notification. Current user if not supplied.
   *
   * @return boolean
   *   Returns TRUE if notification was marked as seen and FALSE if it was seen
   *   already.
   */
  public function markSeen(UserInterface $user = NULL);

  /**
   * Return whether notification is read by certain user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User to take notifications for. Current user if not supplied.
   *
   * @return boolean
   */
  public function isRead(UserInterface $user = NULL);

  /**
   * Marks notification as read for certain user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User that has read the notification. Current user if not supplied.
   *
   * @return boolean
   *   Returns TRUE if notification was marked as read and FALSE if it was read
   *   already.
   */
  public function markRead(UserInterface $user = NULL);

  /**
   * Set internal read status for notification entity for specific user
   * so it won't be pulled from db. Use this function when you already know
   * read status from DB.
   *
   * @param boolean $is_read
   *   Indication of read status.
   * @param \Drupal\user\UserInterface|null $user
   *   User for which notification is read.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   */
  public function setIsRead($is_read, UserInterface $user = NULL);

  /**
   * Gets the Dom Notification channel id.
   *
   * @return string
   *   Channel id of the Dom Notification.
   */
  public function getChannelID();

  /**
   * Sets the Dom Notification channel id.
   *
   * @param string $channel
   *   The Dom Notification channel id.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   *   The called Dom Notification entity.
   */
  public function setChannelID($channel);

  /**
   * Returns notification channel plugin object.
   *
   * @return \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  public function getChannel();

  /**
   * Gets the Dom Notification creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Dom Notification.
   */
  public function getCreatedTime();

  /**
   * Sets the Dom Notification creation timestamp.
   *
   * @param int $timestamp
   *   The Dom Notification creation timestamp.
   *
   * @return \Drupal\dom_notifications\Entity\DomNotificationInterface
   *   The called Dom Notification entity.
   */
  public function setCreatedTime($timestamp);

}
