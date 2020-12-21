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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->notifications_service = $container->get('dom_notifications.service');
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
