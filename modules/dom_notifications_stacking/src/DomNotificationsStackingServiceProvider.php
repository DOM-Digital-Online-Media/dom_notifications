<?php

namespace Drupal\dom_notifications_stacking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider for stacking submodule to alter default notifications service.
 */
class DomNotificationsStackingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritDoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('dom_notifications.service');
    $definition->setClass('Drupal\dom_notifications_stacking\DomNotificationsStackingService');
  }

}
