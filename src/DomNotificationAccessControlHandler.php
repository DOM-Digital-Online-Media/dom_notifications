<?php

namespace Drupal\dom_notifications;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Dom Notification entity.
 *
 * @see \Drupal\dom_notifications\Entity\DomNotification.
 */
class DomNotificationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $notificationsService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeInterface $entity_type, DomNotificationsServiceInterface $notifications_service, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->notificationsService = $notifications_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('dom_notifications.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    if ($account->hasPermission('administer dom notifications')) {
      return AccessResult::allowed();
    }

    // Fetch computed channel ID for the user, so we're sure it matches with
    // computed ID in notification entity i.e. if somebody liked node of [uid]
    // then computed ID will contain [uid] in the notification and will match
    // computed ID taken from user if node belonged to the user.
    $plugin_id = $entity->getChannel()->id();
    $channel = $this->notificationsService->getChannelManager()->createInstance($plugin_id);
    $entities = ['recipient' => $this->entityTypeManager->getStorage('user')->load($account->id())];
    if ($channel->getComputedChannelId($entities) !== $entity->getChannelID()) {
      return AccessResult::forbidden();
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
