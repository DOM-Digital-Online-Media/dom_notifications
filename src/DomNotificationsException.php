<?php

namespace Drupal\dom_notifications;


/**
 * Exception for dom_notifications module to determine errors from the module.
 *
 * @package Drupal\dom_notifications
 */
class DomNotificationsException extends \Exception {

  public function __construct($message = "", $code = 0, \Throwable $previous = NULL) {
    watchdog_exception('dom_notifications', $this);
    parent::__construct($message, $code, $previous);
  }

}
