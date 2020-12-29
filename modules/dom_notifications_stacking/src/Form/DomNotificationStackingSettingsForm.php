<?php

namespace Drupal\dom_notifications_stacking\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure form for stacking notifications.
 */
class DomNotificationStackingSettingsForm extends ConfigFormBase {

  /**
   * Constant for config name that's getting changed in the form.
   */
  const CONFIG_NAME = 'dom_notifications_stacking.settings';

  /**
   * @var \Drupal\dom_notifications\DomNotificationsServiceInterface
   */
  protected $notificationsService;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->notificationsService = $container->get('dom_notifications.service');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dom_notification_stacking_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $channels = $this->notificationsService->getChannelManager()->getAllChannels();

    // Empty text.
    if (empty($channels)) {
      $form['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no notification channels to configure stacking for, please add some.')
      ];
      return $form;
    }

    $config_channels = $this->config(static::CONFIG_NAME)->get('channels');
    $config_channels = array_combine(array_column($config_channels, 'channel_plugin'), $config_channels);
    $form['channels'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    foreach ($channels as $channel) {
      $stack = $config_channels[$channel->id()]['stack'] ?? 1;
      $enabled = $stack > 1;
      $enabled_states = [
        'visible' => [
          ':input[name="channels[' . $channel->id() . '][enabled]"]' => [
            'checked' => TRUE,
          ],
        ],
      ];

      $form['channels'][$channel->id()] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configure "@name" stacking', [
          '@name' => $channel->getLabel(),
        ]),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#default_value' => $enabled,
        ],
        'stack' => [
          '#type' => 'number',
          '#title' => $this->t('Stack size'),
          '#description' => $this->t('The number of notifications to stack until notification is produced.'),
          '#default_value' => $stack === 1 ? 2 : $stack,
          '#min' => 2,
          '#states' => $enabled_states,
        ],
        'message' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message'),
          '#description' => $this->t('Message to use for stacked notification, use @count keyword to put size of a stack.'),
          '#default_value' => $config_channels[$channel->id()]['message'] ?? '',
          '#states' => $enabled_states,
        ],
        'uri' => [
          '#type' => 'textfield',
          '#title' => $this->t('Link'),
          '#default_value' => $config_channels[$channel->id()]['uri'] ?? '',
          '#states' => $enabled_states,
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($values = $form_state->getValue('channels')) {
      foreach ($values as $id => $settings) {
        if ($settings['enabled']) {
          if (empty($settings['message'])) {
            $form_state->setErrorByName(implode('][', [
              'channels',
              $id,
              'message',
            ]), $this->t('Please enter a message for the stacked notification on the channel.'));
          }
          if (!empty($settings['uri']) && !UrlHelper::isValid($settings['uri'])
        && !UrlHelper::isValid($settings['uri'], TRUE)) {
            $form_state->setErrorByName(implode('][', [
              'channels',
              $id,
              'uri',
            ]), $this->t('Please enter a valid link.'));
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(static::CONFIG_NAME);
    $disabled = [];

    $config_channels = [];
    if ($values = $form_state->getValue('channels')) {
      foreach ($values as $id => $settings) {
        $config_channels[$id]['channel_plugin'] = $id;
        if ($settings['enabled']) {
          $config_channels[$id]['stack'] = $settings['stack'];
          $config_channels[$id]['message'] = $settings['message'];
          $config_channels[$id]['uri'] = $settings['uri'];
        }
        else {
          $disabled[] = $id;

          // When size of stack is 1 stacking for channel disables.
          $config_channels[$id]['stack'] = 1;
        }
      }
    }
    $config->set('channels', array_values($config_channels))->save();

    // Clear up values for disabled stacks.
    $this->database->delete('dom_notifications_stacking')
      ->condition('channel_plugin_id', $disabled, 'IN')
      ->execute();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

}
