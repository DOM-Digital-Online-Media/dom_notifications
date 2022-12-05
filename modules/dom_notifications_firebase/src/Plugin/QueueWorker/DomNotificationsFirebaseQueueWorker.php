<?php

namespace Drupal\dom_notifications_firebase\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dom_notifications\DomNotificationsServiceInterface;
use GuzzleHttp\Psr7\Uri;
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
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomNotificationsServiceInterface $notifications_service, FirebaseMessageService $firebase, DateFormatterInterface $date, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->notificationsService = $notifications_service;
    $this->firebase = $firebase;
    $this->date = $date;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
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
      $container->get('entity_type.manager'),
      $container->get('database')
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

      // Fetch count of unseen notifications with db select to minimise exec time.
      $query = $this->database->select('dom_notification_field_data', 'dnfd');
      $query->distinct();
      $query->leftJoin('dom_notification__channel_id', 'dnci', '[dnfd].[id] = [dnci].[entity_id] AND [dnci].[deleted] = 0');
      $query->innerJoin('dom_notifications_user_channels', 'dnuc', '[dnci].[channel_id_value] = [dnuc].[channel_id]');
      $query->leftJoin('dom_notifications_seen', 'dns', '[dnfd].[id] = [dns].[nid] AND [dns].[uid] = [dnuc].[uid]');
      $query->condition('dnuc.uid', $user->id());
      $query->condition('dnfd.channel_plugin_id', $entity->getChannel()->id());
      $query->condition('dnfd.status', 1);
      $query->where('[dnfd].[uid] <> [dnuc].[uid]');
      $query->isNull('dns.uid');
      $unseen = $query->countQuery()->execute()->fetchField();

      try {
        $messageService = $this->firebase;
        $messageService->setRecipients($token);

        if (in_array($entity->getChannel()->id(), $push_channels)) {
          $title = !empty($entity->redirect_options->custom_title)
            ? strip_tags($entity->redirect_options->custom_title)
            : t('New notification');
          $messageService->setNotification([
            'title' => $title,
            'body' => strip_tags($entity->retrieveMessage()),
            'badge' => $unseen,
            'icon' => 'optional-icon',
            'sound' => 'optional-sound',
            'click_action' => !empty($entity->redirect_options->custom_action)
              ? strip_tags($entity->redirect_options->custom_action)
              : '.MainActivity',
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
