<?php

namespace Drupal\dom_notifications_firebase\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dom_notifications\DomNotificationsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\firebase\Service\FirebaseMessageService;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Send notification to the user via firebase service.
 *
 * @QueueWorker(
 *   id = "dom_notifications_firebase_queue_worker",
 *   title = @Translation("DOM: Notifications firebase"),
 *   cron = {"time" = 45}
 * )
 */
class DomNotificationsFirebaseQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Notifications service.
   *
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $notificationsService;

  /**
   * Firebase service.
   *
   * @var \Drupal\firebase\Service\FirebaseMessageService
   */
  protected $firebase;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $date;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\dom_notifications\DomNotificationsServiceInterface $notifications_service
   *   Notifications service.
   * @param \Drupal\firebase\Service\FirebaseMessageService $firebase
   *   Service for pushing message to mobile devices using Firebase.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date
   *   Provides an interface defining a date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomNotificationsServiceInterface $notifications_service, FirebaseMessageService $firebase, DateFormatterInterface $date, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->notificationsService = $notifications_service;
    $this->firebase = $firebase;
    $this->date = $date;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dom_notifications.service'),
      $container->get('firebase.message'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $settings = $this->notificationsService->getNotificationsSettings();
    if (!$settings['token']) {
      return;
    }

    // Get channels which should initiate full firebase notification.
    $push_channels = array_filter($settings['channels'] ?? []);

    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $data['entity'];
    $action = $entity->retrieveRedirectUri()->__toString();
    foreach ($this->entityTypeManager->getStorage('user')->loadMultiple($data['recipients']) as $user) {
      /** @var \Drupal\user\UserInterface $user */
      $token = $user->hasField($settings['token'])
        ? $user->get($settings['token'])->getString()
        : NULL;
      if (!$token) {
        continue;
      }

      // Fetch all unseen user messages to get count.
      $unseen = count($this->notificationsService->fetchNotifications($user, ['is_seen' => FALSE]));

      try {
        $messageService = $this->firebase;
        $messageService->setRecipients($token);

        if (in_array($entity->getChannel()->id(), $push_channels)) {
          $messageService->setNotification([
            'title' => t('New notification'),
            'body' => strip_tags($entity->retrieveMessage()),
            'badge' => $unseen,
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
        }
        else {
          $messageService->setOptions(['content_available' => TRUE]);
          $messageService->setData([
            'badge' => $unseen,
          ]);
        }

        $messageService->setOptions(['priority' => 'normal']);
        $messageService->send();
      }
      catch (\Exception $e) {
        watchdog_exception('dom_notifications_firebase', $e);
      }
    }
  }

}
