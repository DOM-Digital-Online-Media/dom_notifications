# Firebase integration

This module provides firebase integration for push notifications. Once module enabled there're additional settings on `admin/config/system/dom-notifications` page.
Note that there's a queue to send out push notifications on cron, so those might not arrive right away to not hit any timememory limits. 

Push notifications are created for published `dom_notification` entities and for user that have not muted the notification's channel.
