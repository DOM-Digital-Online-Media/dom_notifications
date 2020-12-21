<?php

namespace Drupal\dom_notifications\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to subscribe/unsubscribe to notification channel.
 *
 * @RestResource(
 *   id = "dom_notifications_channel_subscribe",
 *   label = @Translation("Dom Notifications channel subscribe"),
 *   uri_paths = {
 *     "canonical" = "/api/dom-notifications/channel-subscribe",
 *     "create" = "/api/dom-notifications/channel-subscribe",
 *   }
 * )
 */
class DomNotificationsChannelSubscribe extends ResourceBase {

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
   * Returns all the notification channels user subscribed to.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    $result = [];

    $channels = $this->notificationsService->fetchUserChannels();
    foreach ($channels as $channel) {
      $result[] = [
        'name' => $channel->id(),
        'label' => $channel->getLabel(),
        'notify' => $channel->getAlertsStatus($this->currentUser->id())
      ];
    }

    $response = new ResourceResponse($result, 200);
    $response->addCacheableDependency($this->currentUser);

    return $response;
  }

  /**
   * Changes subscription status for the user.
   *
   * @param array $data
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($data) {
    if (!isset($data['channel'])) {
      return new ResourceResponse([
        'message' => $this->t('Required parameter \'channel\' is missing.'),
      ], 400);
    }
    if (!$this->notificationsService->getChannelManager()->hasDefinition($data['channel'])) {
      return new ResourceResponse([
        'message' => $this->t('Notification channel \'@name\' does not exist.', [
          '@name' => $data['channel'],
        ]),
      ], 404);
    }
    if (!isset($data['status']) || !is_bool($data['status'])) {
      return new ResourceResponse([
        'message' => $this->t('Required parameter \'status\' is missing or not boolean.'),
      ], 400);
    }

    /** @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface $channel */
    $channel = $this->notificationsService->getChannelManager()->createInstance($data['channel']);
    if ($data['status']) {
      $channel->subscribeUsers([$this->currentUser->id()]);
      return new ResourceResponse([
        'message' => $this->t('User has been successfully subscribed to the channel.'),
      ], 200);
    }
    else {
      $channel->unsubscribeUsers([$this->currentUser->id()]);
      return new ResourceResponse([
        'message' => $this->t('User has been successfully unsubscribed from the channel.'),
      ], 200);
    }
  }

}
