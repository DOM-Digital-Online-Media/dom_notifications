<?php

namespace Drupal\dom_notifications_stacking;

use Drupal\dom_notifications\Entity\DomNotificationInterface;

/**
 * Stack service interface.
 */
interface DomNotificationsStackingServiceInterface {

  /**
   * Returns current stack size for the notification.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification entity.
   *
   * @return integer
   */
  public function getCurrentStackSize(DomNotificationInterface $notification);

  /**
   * Updates current stack size for the notification.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification entity.
   * @param integer $stack
   *   New stack size to set.
   */
  public function setCurrentStackSize(DomNotificationInterface $notification, $stack);

}
