<?php

namespace Drupal\dom_notifications\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Dom Notifications channel plugin type to allow users provide
 * various channels for their notifications. To provide a channel you only
 * need to specify a few properties like id and label for the channel.
 *
 * The channels can be base and specific. For example you want to notify all
 * users about new articles, so you'd want to create a base channel article and
 * subscribe users to it.
 * But if you want to notify certain users about their article, you'd want to
 * create a specific channel with id = articles:[uid], base_id = articles and
 * base = articles. Here base is a reference to parent channel plugin id and
 * base_id is an id of current channel without changeable parts i.e. [uid].
 *
 * @see \Drupal\dom_notifications\Plugin\DomNotificationsChannelManager
 * @see plugin_api
 *
 * @Annotation
 */
class DomNotificationsChannel extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * (optional) The plugin ID base.
   * If ID has changeable parts like [uid] etc. this ID should exclude those.
   * If not set then it is equal to id.
   *
   * @var string
   */
  public $base_id;

  /**
   * (optional) The plugin ID of notifications base channel type.
   * If omitted than channel is a base channel by default.
   *
   * @var string
   */
  public $base;

  /**
   * Predefined message for notifications, will be set to all notifications of
   * the channel if set.
   *
   * @var string
   */
  public $default_message = '';

  /**
   * Allow users to mute the channel notifications. User will still see them.
   *
   * @var boolean
   */
  public $allow_mute = TRUE;

}
