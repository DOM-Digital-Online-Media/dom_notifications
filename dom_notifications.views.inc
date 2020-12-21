<?php

/**
 * @file
 * Contains dom_notifications\dom_notifications.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */


/**
* Implements hook_views_data().
*/
function dom_notifications_views_data() {
  $data = [];

  $data['dom_notification']['notification_link'] = [
    'title' => t('Notification link'),
    'field' => [
      'id' => 'dom_notifications_link_field',
    ],
  ];

  // Add user channels plugin table fields.
  $data['dom_notifications_user_channels']['table']['group'] = t('Dom notification');
  $data['dom_notifications_user_channels']['table']['join'] = [
    'dom_notification_field_data' => [
      'left_field' => 'channel_id',
      'field' => 'channel_id',
    ],
  ];
  $data['dom_notifications_user_channels']['channel_plugin_id'] = [
    'title' => t('Channel Plugin ID'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['dom_notifications_user_channels']['notify'] = [
    'title' => t('Notify user'),
    'help' => t('Whether user should be notified about the notification.'),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
  ];
  $data['dom_notifications_user_channels']['uid'] = [
    'title' => t('Recipient'),
    'help' => t('User that receives notification.'),
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Recipient user'),
    ],
  ];

  // Add read status table fields.
  $data['dom_notifications_read']['table']['group'] = t('Dom notification');
  $data['dom_notifications_read']['table']['join'] = [
    'dom_notification_field_data' => [
      'left_field' => 'id',
      'field' => 'nid',
    ],
  ];
  $data['dom_notifications_read']['is_read'] = [
    'title' => t('Is read'),
    'help' => t('Boolean field indicating that user has read the notification.'),
    'field' => [
      'field' => 'uid',
      'id' => 'dom_notifications_read_uid',
    ],
  ];

  return $data;
}
