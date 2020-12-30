# DOM notifications
Provides notifications D8 backend along with possibility to create various channels and extra submodules

To define a channel you need to create DomNotificationsChannel plugin. It's prefeable to use DomNotificationsChannelBase as a base class.
You can have a look at example in dom_notifications_general module and \Drupal\dom_notifications\Annotation\DomNotificationsChannel class to gether more info on how to describe a channel through annotation.

To get the list of current notifications on site go to admin/config/system/dom-notifications/list, there you also would be able to find a couple of tabs with extra settings.

