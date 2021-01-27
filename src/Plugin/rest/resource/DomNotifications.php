<?php

namespace Drupal\dom_notifications\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\views\display\RestExport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to fetch user notifications by filters.
 *
 * @RestResource(
 *   id = "dom_notifications",
 *   label = @Translation("Dom Notifications fetch"),
 *   uri_paths = {
 *     "create" = "/api/dom-notifications",
 *   }
 * )
 */
class DomNotifications extends ResourceBase {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('dom_notifications');
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * Returns user notifications based on incoming filters.
   *
   * @param array $data
   *   Array of filters like id, uuid and channel_id.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   The HTTP response object.
   */
  public function post($data) {

    // Since view responds to GET request we move post data to query params.
    $request = $this->requestStack->getCurrentRequest();
    $request->query->replace(is_array($data) ? $data : []);

    return RestExport::buildResponse('dom_user_notifications', 'rest_get');
  }

}
