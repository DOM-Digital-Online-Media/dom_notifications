<?php

namespace Drupal\dom_notifications\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a human readable channel label.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dom_notifications_channel_label")
 */
class DomNotificationsChannelLabel extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $notification */
    $notification = $this->getEntity($values);
    return !empty($notification->redirect_options->custom_title)
      ? strip_tags($notification->redirect_options->custom_title)
      : $notification->getChannel()->getLabel();
  }

}
