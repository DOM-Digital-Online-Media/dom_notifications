<?php

namespace Drupal\dom_notifications_firebase\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

/**
 * Process a queue.
 *
 * @QueueWorker(
 *   id = "dom_notifications_firebase_queue_worker",
 *   title = @Translation("DOM: Notifications firebase"),
 *   cron = {"time" = 60}
 * )
 */
class DomNotificationsFirebaseQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $user = User::load($data['recipient']);
    $settings = \Drupal::config('dom_notifications.settings')->get('token');
    if (!$user || !$settings) {
      return;
    }

    $token = $user->hasField($settings) ? $user->get($settings)->getString() : '';
    if (!$token) {
      return;
    }

    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $data['entity'];
    $action = $entity->retrieveRedirectUri()->__toString();

    try {
      $messageService = \Drupal::service('firebase.message');
      $messageService->setRecipients($token);

      $messageService->setNotification([
        'body' => $entity->getMessage(),
        'badge' => 1,
        'icon' => 'optional-icon',
        'sound' => 'optional-sound',
        'click_action' => '.MainActivity',
      ]);

      $time = $entity->getCreatedTime();
      $date = \Drupal::service('date.formatter');

      $messageService->setData([
        'url' => !empty($action) ? $action : '{}',
        'score' => '3x1',
        'date' => $date->format($time, '', 'Y-m-d'),
        'optional' => t('Data is used to send silent pushes. Otherwise, optional.'),
      ]);

      $messageService->setOptions(['priority' => 'normal']);
      $messageService->send();
    }
    catch (\Exception $e) {
      watchdog_exception('dom_notifications_firebase', $e);
    }
  }

}
