<?php

namespace Drupal\dom_notifications_stacking\EventSubscriber;

use Drupal\dom_notifications\Event\DomNotificationsReadEvent;
use Drupal\dom_notifications_stacking\DomNotificationsStackingServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for stacking submodule.
 */
class DomNotificationsStackingSubscriber implements EventSubscriberInterface {

  /**
   * Notifications service from stacking submodule.
   *
   * @var \Drupal\dom_notifications_stacking\DomNotificationsStackingServiceInterface
   */
  protected $stackingService;

  /**
   * Constructs a new DomNotificationsStackingSubscriber object.
   */
  public function __construct(DomNotificationsStackingServiceInterface $service) {
    $this->stackingService = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DomNotificationsReadEvent::EVENT_NAME] = ['onDomNotificationRead'];

    return $events;
  }

  /**
   * This method is called when user has read a notification.
   *
   * @param \Drupal\dom_notifications\Event\DomNotificationsReadEvent $event
   *   The dispatched event.
   */
  public function onDomNotificationRead(DomNotificationsReadEvent $event) {
    $count = $this->stackingService->getCurrentStackSize($event->notification);

    if (!empty($count) && $event->notification->isPublished()) {
      $stack_size = (int) $event->notification->get('stack_size')->getString();
      $this->stackingService->setCurrentStackSize($event->notification, max(0, $count - $stack_size));
    }
  }

}
