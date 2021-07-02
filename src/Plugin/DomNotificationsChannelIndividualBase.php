<?php

namespace Drupal\dom_notifications\Plugin;

/**
 * Base channel class for individual channels, where each user have individual
 * channel notifications. Useful for likes/comments where only one recipient.
 */
class DomNotificationsChannelIndividualBase extends DomNotificationsChannelBase {

  /**
   * {@inheritdoc}
   */
  public function getComputedChannelId(array $entities = []) {
    if ($user = $this->fetchEntityFromConfiguration('user', $entities)) {
      return $this->getChannelBaseID() . ':' . $user->id();
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function isComputed() {
    return TRUE;
  }

}
