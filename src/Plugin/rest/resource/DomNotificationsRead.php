<?php

namespace Drupal\dom_notifications\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to mark user notifications as read by filters.
 *
 * @RestResource(
 *   id = "dom_notifications_read",
 *   label = @Translation("Dom Notifications mark read"),
 *   uri_paths = {
 *     "canonical" = "/api/dom-notifications-read",
 *     "create" = "/api/dom-notifications-read",
 *   }
 * )
 */
class DomNotificationsRead extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Notifications service instance.
   *
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $notificationsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('dom_notifications');
    $instance->currentUser = $container->get('current_user');
    $instance->notificationsService = $container->get('dom_notifications.service');
    return $instance;
  }

  /**
   * Marks user notification matching incoming filters or all of them as read.
   *
   * @param array $data
   *   Array of filters like id, uuid and channel_id.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data) {

    // There might be no data passed, so data will be NULL.
    $filters = is_array($data) ? $data : [];
    foreach ($this->notificationsService->fetchNotifications(NULL, $filters) as $notification) {
      $notification->markRead();
    }

    return new ModifiedResourceResponse([
      'message' => $this->t('All matching notifications has been marked as read.'),
    ], 200);
  }

}
