<?php

namespace Drupal\dom_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DomNotificationsSettingsForm.
 */
class DomNotificationsSettingsForm extends FormBase {

  /**
   * Notifications service.
   *
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $notificationsService;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->notificationsService = $container->get('dom_notifications.service');
    $instance->fieldManager = $container->get('entity_field.manager');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dom_notifications_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->notificationsService->getNotificationsSettings();
    $form['keep_notification_months'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of months to keep notifications for'),
      '#default_value' => $settings['keep_notification_months'],
      '#required' => TRUE,
    ];

    $options = [];
    $field_map = $this->fieldManager->getFieldDefinitions('user', 'user');
    foreach ($field_map as $name => $item) {
      $options[$name] = $item->getLabel();
    }

    $form['firebase'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Firebase integration settings'),
      '#access' => $this->moduleHandler->moduleExists('dom_notifications_firebase'),
    ];

    $form['firebase']['token'] = [
      '#type' => 'select',
      '#title' => $this->t('FCM token field'),
      '#description' => $this->t('Choose a user field where his FCM token is saved. '),
      '#options' => $options,
      '#default_value' => $settings['token'],
    ];

    $form['firebase']['user_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Count of users'),
      '#description' => $this->t('Notifications are sent to firebase by chunks and this value controls the size of that chunk.'),
      '#default_value' => $settings['user_count'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->notificationsService->getNotificationsSettings();
    foreach ($form_state->getValues() as $key => $value) {
      $settings[$key] = $value;
    }
    $this->notificationsService->setNotificationsSettings($settings);
  }

}
