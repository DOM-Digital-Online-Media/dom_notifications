<?php

namespace Drupal\dom_notifications;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Dom Notification entities.
 *
 * @ingroup dom_notifications
 */
class DomNotificationListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['message'] = $this->t('Message');
    $header['link'] = $this->t('Link');
    $header['channel'] = $this->t('Channel');
    $header['author'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\dom_notifications\Entity\DomNotification $entity */
    $row['message'] = $entity->get('computed_message')->getString();
    $row['link'] = $entity->retrieveRedirectUri();
    $row['channel'] = $entity->getChannel()->getLabel();
    $row['author'] = $entity->getOwner()->getDisplayName();
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'long');
    return $row + parent::buildRow($entity);
  }

}
