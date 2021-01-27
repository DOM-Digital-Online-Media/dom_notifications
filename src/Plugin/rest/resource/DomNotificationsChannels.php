<?php

namespace Drupal\dom_notifications\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to fetch all available notification channels.
 *
 * @RestResource(
 *   id = "dom_notifications_channels",
 *   label = @Translation("Dom Notifications channels"),
 *   uri_paths = {
 *     "create" = "/api/dom-notifications/channels",
 *   }
 * )
 */
class DomNotificationsChannels extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Notification service instance.
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
     * Responds to POST requests.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     */
    public function post() {
      $result = [];
      $channels = $this->notificationsService->getChannelManager()->getAllChannels();

      foreach ($channels as $channel) {
        $result[] = [
          'name' => $channel->id(),
          'label' => $channel->getLabel(),
          'allow_mute' => $channel->isMuteAllowed(),
          'base' => $channel->isBase(),
          'subscribed' => $channel->isSubscribed($this->currentUser->id()),
          'notify' => $channel->getAlertsStatus($this->currentUser->id()),
        ];
      }

      $responce = new ResourceResponse($result, 200);
      $responce->addCacheableDependency($this->currentUser);

      return $responce;
    }

}
