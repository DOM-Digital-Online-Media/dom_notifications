<?php

namespace Drupal\dom_notifications_general\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dom_notifications\DomNotificationsException;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DomNotificationsGeneralSendForm.
 */
class DomNotificationsGeneralSendForm extends FormBase {

  /**
   * Drupal\dom_notifications\DomNotificationsServiceInterface definition.
   *
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $domNotificationsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->domNotificationsService = $container->get('dom_notifications.service');
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dom_notifications_general_send_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Note: This message will be sent to all active users on site.'),
      '#required' => TRUE,
    ];
    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Link to follow after user pressed on a notification.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate link.
    $link = $form_state->getValue('link');
    if (!UrlHelper::isValid($link) && !UrlHelper::isValid($link, TRUE)) {
      $form_state->setErrorByName('link', $this->t('Please enter valid internal or external URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = Html::escape($form_state->getValue('message'));
    $link = new Uri($form_state->getValue('link'));

    try {
      if ($notification = $this->domNotificationsService->addNotification(DOM_NOTIFICATIONS_GENERAL_CHANNEL, [], $message)) {
        $notification->setRedirectUri($link);
        $notification->save();
      }
      $this->messenger()->addStatus($this->t('The notification message has been sent.'));
    }
    catch (DomNotificationsException $e) {
      $this->messenger()->addError($this->t('There was an issue with sending a notification, please contact site administrator if problem persists.'));
    }
  }

}
