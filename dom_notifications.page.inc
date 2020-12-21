<?php

/**
 * @file
 * Contains dom_notifications.page.inc.
 *
 * Page callback for Dom Notification entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Dom Notification templates.
 *
 * Default template: dom_notification.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dom_notification(array &$variables) {
  // Fetch DomNotification Entity Object.
  $dom_notification = $variables['elements']['#dom_notification'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
