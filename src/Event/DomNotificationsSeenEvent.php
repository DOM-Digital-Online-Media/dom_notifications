<?php

namespace Drupal\dom_notifications\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\dom_notifications\Entity\DomNotificationInterface;
use Drupal\user\UserInterface;

/**
 * Event that is fired when user marks notification as seen.
 */
class DomNotificationsSeenEvent extends Event {

  const EVENT_NAME = 'dom_notifications_seen_event';

  /**
   * Notification entity.
   *
   * @var \Drupal\dom_notifications\Entity\DomNotificationInterface
   */
  public $notification;

  /**
   * User entity.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification entity that is marked as seen.
   * @param \Drupal\user\UserInterface $account
   *   User that has marked the notification as seen.
   */
  public function __construct(DomNotificationInterface $notification, UserInterface $account) {
    $this->notification = $notification;
    $this->account = $account;
  }

}
