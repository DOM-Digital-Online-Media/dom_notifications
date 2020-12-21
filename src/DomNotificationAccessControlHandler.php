<?php

namespace Drupal\dom_notifications;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Dom Notification entity.
 *
 * @see \Drupal\dom_notifications\Entity\DomNotification.
 */
class DomNotificationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    if ($account->hasPermission('administer dom notifications')) {
      return AccessResult::allowed();
    }

    switch ($operation) {
      case 'view':
        return $entity->isPublished()
          ? AccessResult::allowedIfHasPermission($account, 'view published dom notification entities')
          : AccessResult::allowedIfHasPermission($account, 'view unpublished dom notification entities');

    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer dom notifications');
  }


}
