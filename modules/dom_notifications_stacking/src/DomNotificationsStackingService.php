<?php

namespace Drupal\dom_notifications_stacking;

use Drupal\Core\Database\Connection;
use Drupal\dom_notifications\Entity\DomNotificationInterface;

/**
 * Altered DomNotificationsService to alter default system.
 */
class DomNotificationsStackingService implements DomNotificationsStackingServiceInterface {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * DomNotificationsStackingService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public function getCurrentStackSize(DomNotificationInterface $notification) {
    $query = $this->getStackingTableQueryForNotification($notification);
    return (int) $query->execute()->fetchField();
  }

  /**
   * {@inheritDoc}
   */
  public function setCurrentStackSize(DomNotificationInterface $notification, $stack) {
    $entity = $notification->getChannel()->getStackRelatedEntity($notification);
    $entity_type = $entity ? $entity->getEntityTypeId() : '';
    $entity_id = $entity ? $entity->id() : 0;

    // Check if we have a record in database already.
    $query = $this->getStackingTableQueryForNotification($notification);
    if ($query->countQuery()->execute()->fetchField() > 0) {
      $this->database->update('dom_notifications_stacking')
        ->fields(['count' => $stack])
        ->condition('channel_id', $notification->getChannelIDs()[0])
        ->condition('related_entity_type', $entity_type)
        ->condition('related_entity_id', $entity_id)
        ->execute();
    }
    else {
      $this->database->insert('dom_notifications_stacking')
        ->fields([
          'channel_plugin_id' => $notification->getChannel()->id(),
          'channel_id' => $notification->getChannelIDs()[0],
          'count' => $stack,
          'related_entity_type' => $entity_type,
          'related_entity_id' => $entity_id,
        ])
        ->execute();
    }
  }

  /**
   * Returns query to stacking table for notification.
   *
   * @param \Drupal\dom_notifications\Entity\DomNotificationInterface $notification
   *   Notification to build stacking query for.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  private function getStackingTableQueryForNotification(DomNotificationInterface $notification) {
    $entity = $notification->getChannel()->getStackRelatedEntity($notification);

    $query = $this->database->select('dom_notifications_stacking', 'dns');
    $query->fields('dns', ['count']);
    $query->condition('dns.channel_id', $notification->getChannelIDs()[0]);
    $query->condition('dns.related_entity_type', $entity ? $entity->getEntityTypeId() : '');
    $query->condition('dns.related_entity_id', $entity ? $entity->id() : 0);
    return $query;
  }

}
