<?php

namespace Drupal\dom_notifications\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Http\Message\UriInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * Defines the Dom Notification entity.
 *
 * @ingroup dom_notifications
 *
 * @ContentEntityType(
 *   id = "dom_notification",
 *   label = @Translation("Dom Notification"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dom_notifications\DomNotificationListBuilder",
 *     "views_data" = "Drupal\dom_notifications\Entity\DomNotificationViewsData",
 *     "translation" = "Drupal\dom_notifications\DomNotificationTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\dom_notifications\Form\DomNotificationForm",
 *       "edit" = "Drupal\dom_notifications\Form\DomNotificationForm",
 *       "delete" = "Drupal\dom_notifications\Form\DomNotificationDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dom_notifications\DomNotificationHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\dom_notifications\DomNotificationAccessControlHandler",
 *   },
 *   base_table = "dom_notification",
 *   data_table = "dom_notification_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer dom notifications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/dom-notifications/manage/{dom_notification}",
 *     "edit-form" = "/admin/config/system/dom-notifications/manage/{dom_notification}/edit",
 *     "delete-form" = "/admin/config/system/dom-notifications/manage/{dom_notification}/delete",
 *     "collection" = "/admin/config/system/dom-notifications/list",
 *   },
 *   field_ui_base_route = "dom_notifications.settings"
 * )
 */
class DomNotification extends ContentEntityBase implements DomNotificationInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * Internal array to store is read status per user. Keys are user IDs.
   *
   * @var array
   */
  protected $isReadStatus = [];

  /**
   * Internal redirect entity to serve if it's been requested already.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $redirectEntity;

  /**
   * Channel plugin object.
   *
   * @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  protected $channel;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Message'))
      ->setDescription(new TranslatableMarkup('The notification message.'))
      ->setTranslatable(TRUE);

    $fields['redirect_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Redirect Entity type'))
      ->setDescription(new TranslatableMarkup('If notification is related to entity store it\'s type to redirect later.'))
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE);

    $fields['redirect_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Redirect Entity ID'))
      ->setDescription(new TranslatableMarkup('If notification is related to entity store it\'s ID to redirect later.'))
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE);

    $fields['redirect_uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('Redirect Uri'))
      ->setDescription(new TranslatableMarkup('If notification is not related to entity than store Uri for notification redirect.'))
      ->setReadOnly(TRUE)
    ->setTranslatable(FALSE);

    $fields['channel_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Channel'))
      ->setDescription(new TranslatableMarkup('The channel identifying set of users subscribed to this notification.'))
      ->setTranslatable(FALSE);

    $fields['status']->setDescription(new TranslatableMarkup('A boolean indicating whether the notification is published.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the notification was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the notification was changed.'));

    return $fields;
  }

  /**
   * {@inheritDoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear read status after entity is deleted.
    $ids = [];
    foreach ($entities as $entity) {
      $ids[] = $entity->id();
    }
    \Drupal::database()->delete('dom_notifications_read')
      ->condition('nid', $ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function setMessage(string $message) {
    $this->set('message', $message);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getMessage() {
    return $this->get('message')->getString();
  }

  /**
   * {@inheritDoc}
   */
  public function setRedirectEntity(EntityInterface $entity) {
    $this->set('redirect_entity_type', $entity->getEntityTypeId());
    $this->set('redirect_entity_id', $entity->id());
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getRedirectEntity() {
    if (!isset($this->redirectEntity)) {
      $entity_type = $this->get('redirect_entity_type')->getString();
      $entity_id = $this->get('redirect_entity_id')->getString();
      if (!empty($entity_type) && !empty($entity_id)) {
        $this->redirectEntity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      }
    }
    return $this->redirectEntity;
  }

  /**
   * {@inheritDoc}
   */
  public function getRedirectUri() {
    $value = $this->get('redirect_uri')->getValue();
    return !empty($value[0]['value']) ? new Uri($value[0]['value']) : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function setRedirectUri(UriInterface $uri) {
    $this->set('redirect_uri', $uri);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveRedirectUri() {
    if ($entity = $this->getRedirectEntity()) {
      $uri = new Uri($entity->toUrl()->getUri());
    }
    else {
      $uri = $this->getRedirectUri();
    }
    return $uri;
  }

  /**
   * {@inheritDoc}
   */
  public function getChannelID() {
    return $this->get('channel_id')->getString();
  }

  /**
   * {@inheritDoc}
   */
  public function setChannelID($channel) {
    $this->set('channel_id', $channel);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getChannel() {
    if (!isset($this->channel)) {
      /** @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelManagerInterface $channel_manager */
      $channel_manager = \Drupal::service('plugin.manager.dom_notifications_channel');
      $plugin_id = $channel_manager->getPluginIDBySpecificChannel($this->getChannelID());
      $this->channel = $channel_manager->createInstance($plugin_id);
    }

    return $this->channel;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function isRead(UserInterface $user = NULL) {
    $account = $this->resolveEmptyUser($user);
    if (!isset($this->isReadStatus[$account->id()])) {
      $count_read = \Drupal::database()
        ->select('dom_notifications_read')
        ->condition('uid', $account->id())
        ->condition('nid', $this->id())
        ->countQuery()->execute()->fetchField();
      $this->isReadStatus[$account->id()] = $count_read === '1';
    }

    return $this->isReadStatus[$account->id()];
  }

  /**
   * {@inheritDoc}
   */
  public function markRead(UserInterface $user = NULL) {
    $account = $this->resolveEmptyUser($user);
    if ($this->isRead($account)) {
      return FALSE;
    }

    // Mark notification as read in database and set internal status to true.
    \Drupal::database()
      ->insert('dom_notifications_read')
      ->fields([
        'uid' => $account->id(),
        'nid' => $this->id(),
      ])
      ->execute();
    $this->isReadStatus[$account->id()] = TRUE;
    Cache::invalidateTags($this->getCacheTags());

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function setIsRead($is_read, UserInterface $user = NULL) {
    $this->isReadStatus[$this->resolveEmptyUser($user)->id()] = $is_read;
  }

  /**
   * Internal function to return given user or load from current user instead.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   User entity. Omit for current user entity.
   *
   * @return UserInterface
   */
  private function resolveEmptyUser(UserInterface $user = NULL) {
    if ($user) {
      return $user;
    }
    /** @var UserInterface $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    return $account;
  }

}
