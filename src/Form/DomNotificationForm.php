<?php

namespace Drupal\dom_notifications\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Dom Notification edit forms.
 *
 * @ingroup dom_notifications
 */
class DomNotificationForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\dom_notifications\Entity\DomNotification $entity */
    $entity = $this->entity;
    $form = parent::buildForm($form, $form_state);

    $uri = $entity->getRedirectUri();
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $entity->getMessage(),
    ];
    $form['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect Uri'),
      '#default_value' => $uri ? $uri->__toString() : '',
    ];
    if (($channel = $entity->getChannel()) && ($info = $channel->getChannelPlaceholderInfo())) {
      $form['help'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Available placeholders') . ':',
      ];
      if ($entity->getStackSize() === 1) {
        unset($info['@count']);
      }
      foreach ($info as $placeholder => $data) {
        $form['help']['#items'][$placeholder] = [
          '#markup' => new FormattableMarkup('@placeholder: @label', [
            '@placeholder' => $placeholder,
            '@label' => $data['name'],
          ]),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\dom_notifications\Entity\DomNotificationInterface $entity */
    $entity = $this->entity;

    // Save redirect uri and allow channels to do pre save functionality.
    $entity->setRedirectUri($form_state->getValue('uri'));
    if ($entity = $entity->getChannel()->onNotificationSave($entity)) {
      $status = parent::save($form, $form_state);

      switch ($status) {
        case SAVED_NEW:
          $this->messenger()->addMessage($this->t('A new notification was created.'));
          break;

        default:
          $this->messenger()->addMessage($this->t('The notification was saved.'));

      }
      $form_state->setRedirect('entity.dom_notification.canonical', ['dom_notification' => $entity->id()]);
    }
    else {
      $this->messenger()->addWarning($this->t('The notification was not saved due to channel rules.'));
    }
  }

}
