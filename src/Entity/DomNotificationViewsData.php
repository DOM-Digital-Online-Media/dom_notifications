<?php

namespace Drupal\dom_notifications\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Dom Notification entities.
 */
class DomNotificationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
