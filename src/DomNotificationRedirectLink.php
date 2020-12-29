<?php

namespace Drupal\dom_notifications;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use GuzzleHttp\Psr7\Uri;

/**
 *
 */
class DomNotificationRedirectLink extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $this->getEntity();
    if ($entity->getChannel()->useEntityUri() && ($link_entity = $entity->getRelatedEntity())) {
      $uri = new Uri($link_entity->toUrl()->toString());
    }
    else {
      $uri = $entity->getRedirectUri();
    }

    $uri = $entity->getChannel()->alterRedirectUri($entity, $uri);
    $this->list[0] = $this->createItem(0, $uri);
  }

}
