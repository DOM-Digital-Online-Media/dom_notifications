<?php

namespace Drupal\dom_notifications_general\Plugin\DomNotificationsChannel;

use Drupal\dom_notifications\Plugin\DomNotificationsChannelBase;

/**
 * Declare notification channel for all the users to sent site-wide system
 * messages or updates related to all users on the site.
 *
 * @DomNotificationsChannel(
 *   id = "general",
 *   label = @Translation("General site messages"),
 *   allow_mute = false,
 * )
 */
class DomNotificationsGeneralChannel extends DomNotificationsChannelBase {


}
