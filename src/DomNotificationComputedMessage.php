<?php

namespace Drupal\dom_notifications;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Returns notification message with values instead of placeholders.
 */
class DomNotificationComputedMessage extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $this->getEntity();

    $replace = [];
    foreach ($entity->getChannel()->getChannelPlaceholderInfo() as $placeholder => $info) {
      if (is_callable($info['callback'])) {
        $replace[$placeholder] = $info['callback']($entity);
      }
    }

    $this->list[0] = $this->createItem(0, new TranslatableMarkup($entity->getMessage(), $replace));
  }

}
