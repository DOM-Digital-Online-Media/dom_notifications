<?php

namespace Drupal\dom_notifications\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to mute/unmute notification channel.
 *
 * @RestResource(
 *   id = "dom_notifications_channel_mute",
 *   label = @Translation("Dom Notifications channel mute"),
 *   uri_paths = {
 *     "create" = "/api/dom-notifications/channel-mute",
 *   }
 * )
 */
class DomNotificationsChannelMute extends ResourceBase {

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
   * Changes status of alerts for specified channel.
   *
   * @param array $data
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($data) {
    if (!isset($data['channel'])) {
      return new ResourceResponse([
        'message' => $this->t('Required parameter \'channel\' is missing.'),
      ], 422);
    }
    if (!$this->notificationsService->getChannelManager()->hasDefinition($data['channel'])) {
      return new ResourceResponse([
        'message' => $this->t('Notification channel \'@name\' does not exist.', [
          '@name' => $data['channel'],
        ]),
      ], 404);
    }
    if (!isset($data['mute']) || !is_bool($data['mute'])) {
      return new ResourceResponse([
        'message' => $this->t('Required parameter \'mute\' is missing or not boolean.'),
      ], 422);
    }

    /** @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface $channel */
    $channel = $this->notificationsService->getChannelManager()->createInstance($data['channel']);
    if (!$channel->isMuteAllowed()) {
      return new ResourceResponse([
        'message' => $this->t('It is not possible to mute notifications from the channel.'),
      ], 422);
    }

    if (!$channel->isSubscribed($this->currentUser->id())) {
      return new ResourceResponse([
        'message' => $this->t('The user is not subscribed to the channel.'),
      ], 422);
    }

    $channel->setAlertsStatus($this->currentUser->id(), !$data['mute']);
    if ($data['mute']) {
      return new ResourceResponse([
        'message' => $this->t('The user has successfully muted alerts for the channel.')
      ], 200);
    }
    else {
      return new ResourceResponse([
        'message' => $this->t('The user has successfully unmuted alerts for the channel.')
      ], 200);
    }
  }

}
