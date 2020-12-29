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

    $data[$this->entityType->getBaseTable()]['computed_message'] = [
      'title' => $this->t('Computed message'),
      'help' => $this->t('Notification message with all the placeholders replaced.'),
      'field' => [
        'id' => 'field',
        'default_formatter' => 'string',
        'field_name' => 'computed_message',
      ],
    ];
    $data[$this->entityType->getBaseTable()]['redirect_link'] = [
      'title' => $this->t('Link'),
      'help' => $this->t('Final link notification leads to.'),
      'field' => [
        'id' => 'field',
        'default_formatter' => 'string',
        'field_name' => 'redirect_link',
      ],
    ];

    return $data;
  }

}
