# Notifications stacking

Allows individual channels i.e. channels that are designed for single user to enable stacking feature.
You can confirure that on `admin/config/system/dom-notifications/stacking` page.

### How that works?
Lets imagine we have a channel about new comments on user post and we enabled stacking and set a stack size to 5.
* If user receives notification from the channel we simply produce a notification "You have 1 new comment" (message depends on channel configuration).
* When user receives 5th notification from the channel he receives "You have 5 new comments" (this message can be configured in stacking settings page with @count keyword availabe).
* When user receive notification again it won't be produced until there're 10 unread notifications on the channel, then we produce "You have 10 new comments" and delete the notification about 5.
* Then it continues to 15/20/25...N*5. Until user marks some notifications as read.
* If user marked that notification as read the counting starts from 1 again because he already seen 10/15/20 previous comments and he has only 1 NEW comment.

**Note:** If user has read notification about 1 new comment and his current stack on channel is 11, it will become 10 not 1 because he read notification about 1 comment.
If user has read a notification about 10 new comments, then it will become 11 - 10 = 1.

That's about it, however there's a special method in `DomNotificationsChannelInterface` called `DomNotificationsChannelInterface()`.
It is designed to allow some channels enable stacking not for the whole channel, but for particular content.

Imagine, the scenario above, but this time the channel sets stack related entity to be a node which has been commented instead of default NULL.
This time only comments on the same nodes will be stacked together.

So, if user received 3 new comments on Node A and 4 new comments on Node B, they will receive notification about 5 new comments only when new comment on Node B is created.
In other words, it is possible to have multiple stacks on one channel if those should be related to different entities. And if stack related entity is NULL, then stack is only one for the channel.
