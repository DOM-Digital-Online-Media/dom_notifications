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
  protected $notifications_service;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $field_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->notifications_service = $container->get('dom_notifications.service');
    $instance->field_manager = $container->get('entity_field.manager');
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
    $settings = $this->notifications_service->getNotificationsSettings();
    $form['keep_notification_months'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of months to keep notifications for'),
      '#default_value' => $settings['keep_notification_months'],
      '#required' => TRUE,
    ];

    $options = [];
    $field_map = $this->field_manager->getFieldDefinitions('user', 'user');
    foreach ($field_map as $name => $item) {
      $options[$name] = $item->getLabel();
    }

    $form['token'] = [
      '#type' => 'select',
      '#title' => $this->t('FCM token field'),
      '#options' => $options,
      '#default_value' => !empty($settings['token']) ? $settings['token'] : NULL,
      '#required' => TRUE,
    ];

    $form['user_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Count of users'),
      '#default_value' => !empty($settings['user_count']) ? $settings['user_count'] : 100,
      '#required' => TRUE,
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
    $settings = $this->notifications_service->getNotificationsSettings();
    foreach ($form_state->getValues() as $key => $value) {
      $settings[$key] = $value;
    }
    $this->notifications_service->setNotificationsSettings($settings);
  }

}
