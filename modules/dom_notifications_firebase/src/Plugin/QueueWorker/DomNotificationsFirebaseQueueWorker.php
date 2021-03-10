<?php

namespace Drupal\dom_notifications_firebase\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\firebase\Service\FirebaseMessageService;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;

/**
 * Send notification to the user via firebase service.
 *
 * @QueueWorker(
 *   id = "dom_notifications_firebase_queue_worker",
 *   title = @Translation("DOM: Notifications firebase"),
 *   cron = {"time" = 60}
 * )
 */
class DomNotificationsFirebaseQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Provides config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Provides firebase.message service.
   *
   * @var \Drupal\firebase\Service\FirebaseMessageService
   */
  protected $firebase;

  /**
   * Provides date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $date;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Defines the interface for a configuration object factory.
   * @param \Drupal\firebase\Service\FirebaseMessageService $firebase
   *   Service for pushing message to mobile devices using Firebase.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date
   *   Provides an interface defining a date formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, FirebaseMessageService $firebase, DateFormatterInterface $date) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
    $this->firebase = $firebase;
    $this->date = $date;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('firebase.message'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $settings = $this->config->get('dom_notifications.settings')->get('token');
    if (!$settings) {
      return;
    }

    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $data['entity'];
    $action = $entity->retrieveRedirectUri()->__toString();

    foreach (User::loadMultiple($data['recipients']) as $user) {
      $token = $user->hasField($settings) ? $user->get($settings)->getString() : '';
      if (!$token || !$entity->getChannel()->getAlertsStatus($user->id())) {
        continue;
      }

      // @todo: add dependency injection for database service.
      $query = \Drupal::database()->select('dom_notification_field_data', 'dn');
      $query->fields('dn', ['id']);
      $query->condition('dn.uid', $user->id());
      $query->condition('dn.status', 1);
      $all_count = $query->countQuery()->execute()->fetchField();

      $query = \Drupal::database()->select('dom_notifications_seen', 'dns');
      $query->fields('dns', ['nid']);
      $query->condition('dns.uid', $user->id());
      $seen_count = $query->countQuery()->execute()->fetchField();

      $count = 1;
      if ($all_count && $all_count - $seen_count >  1) {
        $count = $all_count - $seen_count;
      }

      try {
        $messageService = $this->firebase;
        $messageService->setRecipients($token);

        $messageService->setNotification([
          'title' => t('New notification'),
          'body' => $entity->getMessage(),
          'badge' => $count,
          'icon' => 'optional-icon',
          'sound' => 'optional-sound',
          'click_action' => '.MainActivity',
        ]);

        $messageService->setData([
          'url' => !empty($action) ? $action : '{}',
          'score' => '3x1',
          'date' => $this->date->format($entity->getCreatedTime(), '', 'Y-m-d'),
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

}
