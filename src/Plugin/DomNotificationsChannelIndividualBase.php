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
  public function getComputedChannelIds(array $entities = []) {
    if ($user = $this->fetchEntityFromConfiguration('user', $entities)) {
      return [$this->getChannelBaseID() . ':' . $user->id()];
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function isComputed() {
    return TRUE;
  }

}
