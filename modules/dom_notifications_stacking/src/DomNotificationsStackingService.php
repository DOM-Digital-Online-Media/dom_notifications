<?php

namespace Drupal\dom_notifications_stacking;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\dom_notifications\DomNotificationsService;
use Drupal\user\UserInterface;

/**
 * Altered DomNotificationsService to alter default system.
 */
class DomNotificationsStackingService extends DomNotificationsService {

  /**
   * {@inheritDoc}
   */
  public function addNotification($channel_id, array $fields = [], $message = '', UserInterface $recipient = NULL, UserInterface $sender = NULL) {
    $recipient_user = $recipient ?? $this->currentUser;
    $new_message = $message;
    $new_fields = $fields;
    $new_fields['stack_size'] = 1;

    /** @var \Drupal\dom_notifications\Plugin\DomNotificationsChannelInterface $channel */
    $channel = $this->getChannelManager()->createInstance($channel_id, $fields + ['recipient' => $recipient_user]);
    $computed_channel_id = $channel->getComputedChannelId();

    $stacking = $this->configFactory->getEditable('dom_notifications_stacking.settings')->get('channels');
    $stacking = array_combine(array_column($stacking, 'channel_plugin'), $stacking);
    $enabled = isset($stacking[$channel_id]['stack'])
      ? $stacking[$channel_id]['stack'] > 1
      : FALSE;
    $produce = $enabled && !empty($computed_channel_id)
      ? $this->produceStackNotification($computed_channel_id, $stacking[$channel_id]['stack'])
      : FALSE;

    if ($enabled) {
      if (!$produce) {
        return NULL;
      }
      else {
        // Update the message and Uri using stack configs.
        $new_fields['stack_size'] = $stacking[$channel_id]['stack'];
        $new_message = $stacking[$channel_id]['message'];
        $new_fields['redirect_uri'] = !empty($stacking[$channel_id]['uri'])
          ? $stacking[$channel_id]['uri']
          : $new_fields['redirect_uri'] ?? NULL;

      }

    }

    return parent::addNotification($channel_id, $new_fields, $new_message, $recipient, $sender);
  }

  /**
   * Internal function to check whether we should produce stack notification.
   * Note: this function if for internal use because it manages DB data.
   *
   * @param string $channel_id
   *   Computed channel id where all placeholders replaced.
   * @param integer $stack_size
   *   Stack size that should be reached.
   *
   * @return boolean
   *   TRUE if notification should be sent.
   */
  private function produceStackNotification($channel_id, $stack_size) {
    $plugin_id = $this->getChannelManager()->getPluginIDBySpecificChannel($channel_id);
    $count = $this->database->select('dom_notifications_stacking', 'dns')
      ->fields('dns', ['count'])
      ->condition('dns.channel_id', $channel_id)
      ->execute()
      ->fetchField();

    // Increase notification count or set initial.
    $count = $count ? $count + 1 : 1;
    if ($count === (int) $stack_size) {
      $this->database->update('dom_notifications_stacking')
        ->fields(['count' => 0])
        ->condition('channel_id', $channel_id)
        ->execute();
      return TRUE;
    }
    else {
      $this->database->upsert('dom_notifications_stacking')
        ->fields([
          'channel_plugin_id' => $plugin_id,
          'channel_id' => $channel_id,
          'count' => $count,
        ])
        ->key('channel_id')
        ->execute();
      return FALSE;
    }
  }

}
