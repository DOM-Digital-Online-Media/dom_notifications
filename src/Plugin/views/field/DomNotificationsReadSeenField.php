<?php

namespace Drupal\dom_notifications\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Boolean;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide read and seen status for the notification and user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dom_notifications_read_seen_field")
 */
class DomNotificationsReadSeenField extends Boolean {

  /**
   * Current user account proxy.
   *
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
  protected function defineOptions() {
    return parent::defineOptions() + [
      'user' => ['default' => 'none'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Fetch relationship users.
    $relationship_options = [];
    $executable = $form_state->get('view')->getExecutable();
    $relationships = $executable->display_handler->getOption('relationships');

    foreach ($relationships as $relationship) {
      if (($relationship['table'] != $this->base_table) && ($relationship['field'] === 'uid')) {
        $relationship_options[$relationship['id']] = $relationship['admin_label'];
      }
    }

    $relationship_options = array_merge(['none' => $this->t('Current user')], $relationship_options);
    $rel = empty($this->options['user']) ? 'none' : $this->options['user'];
    if (empty($relationship_options[$rel])) {
      // Pick the first relationship.
      $rel = key($relationship_options);
    }

    $form['user'] = [
      '#type' => 'select',
      '#title' => $this->t('User'),
      '#options' => $relationship_options,
      '#default_value' => $rel,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function ensureMyTable() {
    if (!isset($this->tableAlias)) {
      $join = $this->getJoin();
      $join->type = 'LEFT';

      // Add user filter, so the value returned either user id or NULL which
      // helps determine whether notification is read/seen by the user.
      if (!isset($this->options['user']) || ($this->options['user'] === 'none')) {
        $join->extra = $this->table . '.' . $this->realField . ' = ' . $this->current_user->id();
      }
      else {
        $join->extra = $this->table . '.' . $this->realField . ' = ';
        $join->extra .= $this->view->relationship[$this->options['user']]->alias
          ?? $this->view->relationship[$this->options['user']]->table;
        $join->extra .= '.' . $this->view->relationship[$this->options['user']]->realField
          ?? $this->view->relationship[$this->options['user']]->field;
      }
      $this->tableAlias = $this->query->ensureTable($this->table, $this->relationship, $join);
    }
    return $this->tableAlias;
  }

}
