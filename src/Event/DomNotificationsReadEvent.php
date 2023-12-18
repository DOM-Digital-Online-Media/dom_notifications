<?php

namespace Drupal\dom_notifications\Event;

use Drupal\dom_notifications\Entity\DomNotificationInterface;
use Drupal\user\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when user marks notification as read.
 */
class DomNotificationsReadEvent extends Event {

  const EVENT_NAME = 'dom_notifications_read_event';

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
   *   Notification entity that is marked as read.
   * @param \Drupal\user\UserInterface $account
   *   User that has marked the notification as read.
   */
  public function __construct(DomNotificationInterface $notification, UserInterface $account) {
    $this->notification = $notification;
    $this->account = $account;
  }

}
