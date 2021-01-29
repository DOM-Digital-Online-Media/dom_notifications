# DOM notifications
Provides notifications D8 backend along with possibility to create various channels and extra submodules.

## Settings
Various module settings, notifications list and Field API pages for notifications you can find on `admin/config/system/dom-notifications` and tabs.

**Note:** notifications older than 3 months will be automatically removed on cron, that period can be adjusted in settings.

## Creating a channel
To define a channel you need to create `DomNotificationsChannel` plugin. It's preferable to use `DomNotificationsChannelBase` as a base class.
You can have a look at example in `dom_notifications_general` module and `\Drupal\dom_notifications\Annotation\DomNotificationsChannel` class to gather more info on how to describe a channel through annotation.
Also, there's `DomNotificationsChannelInterface` which describes every method for the channel.

Each channnel can provide own replacements to use in notification message, you can find more info on that in `getChannelPlaceholderInfo()` method annotation and an example of how `@author` placeholder is added in `DomNotificationsChannelBase`.

## Creating a notification
To create a notification please use `dom_notifications.service` service which provides various methods to work with notifications.
More info on each method of the service you can find in `DomNotificationsServiceInterface`.

**Note:** pay attention that `addNotification()` method may not return notification due to specific channels behaviour that may not allow some notifications to be sent. Also, this methos do not save notification right away so the correct way to use it would be:

```php
if ($notification = $service->addNotification(...)) {
  $notification->save();
}
```

If you want to set related entity to notification please pass it into `$fields` array in `related_entity` key, so the channel know what is related entity during entity creating process and can provide own login for channel_id notification suppose to have. 

## Available endpoints
There are several available endpoints to fetch user notification, change their subscription, alerts status etc.

* `POST api/dom-notifications` (data: {'id' => {id}, 'uuid' => {uuid}, 'channel_id' => {channel_id}}) - returns list of notifications for currently logged in user, based on passed filters (ommit filters if all notifications should be returned);
* `POST api/dom-notifications-read` (data: {'id' => {id}, 'uuid' => {uuid}, 'channel_id' => {channel_id}}) - marks notifications that match filters as read for logged in user;
* `POST api/dom-notifications/channel-subscribe` - returns list of channels user is sunscribed to;
* `PUT api/dom-notifications/channel-subscribe` (data: {'channel' => {channel_id}, 'status' => {true/false}}) - changes user subscription for the channel, if status true then user sunbscribes, false - unsubscribes;
* `POST api/dom-notifications/channels` - returns list of all available notification channels and 2 properties `subscribed` and `notify` based on user duing request;
* `POST api/dom-notifications/channel-mute` (data: {'channel' => {channel_id}, 'mute' => {true/false}}) - changes user notify status for the channel i.e. whether user wants to receive push notifications, if mute true then user mutes the channel notifications, false - unmutes;

## Views
There's a `dom_user_notifications` view included when module installs, it's required for `api/dom-notifications` and `api/dom-notifications-read` endpoints, it's also possible to change fields there to include more info on `api/dom-notifications` results.
