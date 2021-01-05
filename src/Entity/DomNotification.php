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
use Drupal\user\EntityOwnerTrait;
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
 *     "owner" = "uid",
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
  use EntityOwnerTrait;

  /**
   * Internal array to store is read status per user. Keys are user IDs.
   *
   * @var array
   */
  private $isReadStatus = [];

  /**
   * Internal redirect entity to serve if it's been requested already.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $relatedEntity;

  /**
   * Channel plugin object.
   *
   * @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface
   */
  private $channel;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

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

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Message'))
      ->setDescription(new TranslatableMarkup('The notification message.'))
      ->setTranslatable(FALSE);

    $fields['computed_message'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Message with placeholders replaced'))
      ->setDescription(new TranslatableMarkup('The notification message with all available placeholders replaced.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\dom_notifications\DomNotificationComputedMessage');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the notification was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the notification was changed.'));

    $fields['redirect_link'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setDescription(new TranslatableMarkup('The link notification leads to.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\dom_notifications\DomNotificationRedirectLink');

    $fields['redirect_options'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Redirect options'))
      ->setDescription(new TranslatableMarkup('Various options for redirect like related entity, route etc.'))
      ->setDefaultValue(array('redirect_uri' => NULL));

    $fields['related_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity type of entity related to notification'));

    $fields['related_entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Entity ID of entity related to notification'));

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
  public function retrieveRedirectUri() {
    return new Uri($this->get('redirect_link')->getString());
  }

  /**
   * {@inheritDoc}
   */
  public function setRelatedEntity(EntityInterface $entity = NULL) {
    $this->set('related_entity_type', $entity ? $entity->getEntityTypeId() : NULL);
    $this->set('related_entity_id', $entity ? $entity->id() : NULL);
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getRelatedEntity() {
    if (!isset($this->relatedEntity)) {
      $entity_type = $this->get('related_entity_type')->getString();
      $entity_id = $this->get('related_entity_id')->getString();
      if (!empty($entity_type) && !empty($entity_id)) {
        $this->relatedEntity = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($entity_id);
      }
    }
    return $this->relatedEntity ?? NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getRecipients() {
    return \Drupal::database()
      ->select('dom_notifications_user_channels', 'dnuc')
      ->fields('dnuc', ['uid'])
      ->condition('channel_id', $this->getChannelID())
      ->condition('notify', 1)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritDoc}
   */
  public function getRedirectUri() {
    return !empty($this->redirect_options->redirect_uri)
      ? new Uri($this->redirect_options->redirect_uri)
      : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function setRedirectUri($uri = NULL) {
    $this->redirect_options->redirect_uri = $uri instanceof UriInterface
      ? $uri
      : $uri
        ? new Uri($uri)
        : NULL;
    return $this;
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
   * {@inheritDoc}
   */
  public function getStackSize() {
    $size = 1;
    if ($this->hasField('stack_size')) {
      if ($value = (int) $this->get('stack_size')->getString()) {
        $size = $value;
      }
    }
    return $size;
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
