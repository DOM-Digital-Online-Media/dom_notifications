<?php

namespace Drupal\dom_notifications\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DomNotificationsNotAuthor
 *
 * @ViewsFilter("dom_notifications_not_author")
 */
class DomNotificationsNotAuthor extends FilterPluginBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->current_user = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    $this->ensureMyTable();
    if ($this->options['user'] === 'none') {
      $this->value = $this->current_user->id();
      $this->operator = '<>';
      parent::query();
    }
    else {
      $user_field = $this->query->view->relationship[$this->options['user']]->tableAlias . '.';
      $user_field .= $this->query->view->relationship[$this->options['user']]->realField;
      $this->query->addWhereExpression($this->options['group'], "{$this->tableAlias}.{$this->realField} <> {$user_field}");
    }
  }

  /**
   * {@inheritDoc}
   */
  public function defineOptions() {
    return parent::defineOptions() + [
      'user' => ['default' => 'none'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Fetch relationship users.
    $relationships = $form_state->get('view')->getExecutable()->display_handler->getOption('relationships');

    foreach ($relationships as $relationship) {
      if (($relationship['table'] != $this->base_table) && ($relationship['field'] === 'uid')) {
        $relationship_options[$relationship['id']] = $relationship['admin_label'];
      }
    }

    $relationship_options = array_merge(['none' => $this->t('Current user')], $relationship_options);
    $rel = empty($this->options['user']) ? 'none' : $this->options['user'];
    if (empty($relationship_options[$rel])) {
      $rel = key($relationship_options);
    }

    $form['user'] = [
      '#type' => 'select',
      '#title' => $this->t('User'),
      '#options' => $relationship_options,
      '#default_value' => $rel,
      '#description' => $this->t('User for which notifications are taken, used to filter out own notifications.'),
    ];
  }

  public function adminLabel($short = FALSE) {
    if (!empty($this->options['admin_label'])) {
      return $this->options['admin_label'];
    }
    return $this->t('@group: Author', ['@group' => $this->definition['group']]);
  }

  /**
   * {@inheritDoc}
   */
  public function adminSummary() {
    $user = $this->options['user'];
    if ($user === 'none') {
      $user = $this->t('Current user');
    }
    else {
      $relations = $this->view->getDisplay()->getOption('relationships');
      $user = $relations[$user]['admin_label'];

    }
    return '<> ' . $user;
  }

}
