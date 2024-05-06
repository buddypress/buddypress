# BuddyPress Email Tokens

As introduced in the [BuddyPress Emails documentation page](README.md), **Tokens** are variable strings surrounded with double or triple braces that will get replaced with dynamic content when the email gets sent. When a token is surrounded with double braces (eg: `{{site.name}}`), it means the corresponding dynamic data will be escaped during the Email merge. When it's surrounded with triple braces (eg: `{{{profile.url}}}`), it means dynamic data is not escaped during the Email merge.

## Global tokens available for all BuddyPress Email situations

| Tokens | Description |
| ------ | ----------- |
| `{{site.admin-email}}` | Email address of the site administrator |
| `{{{site.url}}}` | Your WordPress site’s landing page (value of `home_url()`) |
| `{{site.description}}` | Your WordPress site’s tagline (value of `get_bloginfo( 'description' )`) |
| `{{site.name}}` | Your WordPress site’s title (value of `get_bloginfo( 'name' )`) |
| `{{recipient.email}}` | Email address of recipient |
| `{{recipient.name}}` | Display name of recipient |
| `{{recipient.username}}` | Username (login) of recipient |
| `{{{unsubscribe}}}` | Link to the recipient’s email notifications settings screen in their user profile |
| `{{email.subject}}` | The subject line of the email |

## Tokens specific to a BuddyPress Email situation

### Core situations

#### Recipient has registered for an account.

| Tokens | Description |
| ------ | ----------- |
| `{{{activate.url}}}` | Link to the site’s membership activation page, including the user’s activation key |
| `{{key}}` | Activation key |
| `{{user.email}}` | The new user’s email address |
| `{{user.id}}` | The new user’s ID |

#### Recipient has registered for an account and site.

> [!NOTE]
> This situation is only available & used in WordPress Multisite configs.

| Tokens | Description |
| ------ | ----------- |
| `{{{activate-site.url}}}` | Link to the site’s membership and new blog activation page |
| `{{{user-site.url}}}` | The link to the new blog created by the user |
| `{{title}}` | The new blog’s title |
| `{{domain}}` | The new blog’s domain |
| `{{path}}` | The new blog’s path |
| `{{key_blog}}` | Activation key |

#### Recipient has successfully activated an account.

| Tokens | Description |
| ------ | ----------- |
| `{{displayname}}` | Display name of the user |
| `{{{profile.url}}}` | Link to the recipient’s user profile |
| `{{{lostpassword.url}}}` | Link to the WordPress login screen to reset recipient's password |

### Members situations

#### A site member has sent a site invitation to the recipient.

> [!NOTE]
> This situation is only used if the option to let members to send invitations to join a site is active.

| Tokens | Description |
| ------ | ----------- |
| `{{inviter.name}}` | The name of the user who sent the invitations to join the site (wrapped in a link to that user’s profile) |
| `{{{inviter.url}}}` | The link to the profile page of the user who sent the invitations to join the site |
| `{{inviter.id}}` | The ID of the user who sent the invitations to join the site |
| `{{{invite.accept_url}}}` | The URL to use to accept the invitation |
| `{{usermessage}}` | The invitation text |

#### Someone has requested membership on this site.

> [!NOTE]
> This situation is only used if the option to request site membership is active.

| Tokens | Description |
| ------ | ----------- |
| `{{admin.id}}`| The ID of the admin user to inform about this new site membership request |
| `{{{manage.url}}}` | The URL to use to manage site membership requests |
| `{{requesting-user.user_login}}` | The login name of the user who sent the membership request |

#### A site membership request has been rejected.

> [!NOTE]
> This situation is only used if the option to request site membership is active.
> This situation do not use specific tokens.

### Activity situations

> [!NOTE]
> The following situations are only used if Activity component is active.

#### A member has replied to an activity update that the recipient posted.

| Tokens | Description |
| ------ | ----------- |
| `{{usermessage}}` | The content of the Activity comment |
| `{{poster.name}}` | Display name of Activity comment author |
| `{{{thread.url}}}` | Permalink to the original activity item thread |
| `{{comment.id}}` | The Activity comment ID |
| `{{commenter.id}}` | The ID of the user who posted the Activity comment. |
| `{{original_activity.user_id}}` | The ID of the user who wrote the original activity update |

#### A member has replied to a comment on an activity update that the recipient posted.

| Tokens | Description |
| ------ | ----------- |
| `{{usermessage}}` | The content of the Activity comment reply |
| `{{poster.name}}` | Display name of Activity comment reply author |
| `{{{thread.url}}}` | Permalink to the original activity item thread |
| `{{comment.id}}` | The Activity comment reply ID |
| `{{parent-comment-user.id}}` | The ID of the user who wrote the immediate parent Activity comment |
| `{{commenter.id}}` | The ID of the user who posted the Activity comment reply |

#### Recipient was mentioned in an activity update.

| Tokens | Description |
| ------ | ----------- |
| `{{usermessage}}` | The content of the activity update |
| `{{{mentioned.url}}}` | Permalink to the activity item |
| `{{poster.name}}` | Display name of activity item author |
| `{{receiver-user.id}}` | The ID of the user who was mentioned in the update |

#### Recipient was mentioned in a group activity update.

> [!NOTE]
> This situation is only used if Groups component is active.

| Tokens | Description |
| ------ | ----------- |
| `{{usermessage}}` | The content of the activity update |
| `{{{mentioned.url}}}` | Permalink to the activity item |
| `{{poster.name}}` | Display name of activity item author |
| `{{receiver-user.id}}` | The ID of the user who was mentioned in the update |
| `{{group.name}}` | Name of the group housing the activity update |

### Friends situations

> [!NOTE]
> The following situations are only used if Friends component is active.

#### A member has sent a friend request to the recipient.

| Tokens | Description |
| ------ | ----------- |
| `{{{friend-requests.url}}}` | The URL to the user’s friendship request management screen |
| `{{{initiator.url}}}` | The URL of the initiator’s user profile |
| `{{initiator.name}}` | Display name of the friendship initiator |
| `{{friendship.id}}` | ID of the friendship object |
| `{{friend.id}}` | User ID of the friendship request recipient |
| `{{initiator.id}}` | User ID of the user who initiated the friendship request |

#### Recipient has had a friend request accepted by a member.

| Tokens | Description |
| ------ | ----------- |
| `{{{friendship.url}}}` |  The URL to the user’s friendship request management screen |
| `{{friend.name}}` | Display name of the friendship request recipient |
| `{{friendship.id}}` | ID of the friendship object |
| `{{friend.id}}` | User ID of the friendship request recipient |
| `{{initiator.id}}` | User ID of the user who initiated the request |
		
### Groups situations

> [!NOTE]
> The following situations are only used if Groups component is active.

#### A group’s details were updated.

| Tokens | Description |
| ------ | ----------- |
| `{{changed_text}}` |	Text describing the details of the change |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{group.name}}` | The name of the group |
| `{{group.id}}` | The ID of the group |

#### A member has sent a group invitation to the recipient.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{inviter.name}}` | Group Inviter’s display name wrapped in a link to that user’s profile |
| `{{{inviter.url}}}` |	The URL of the profile of the user who extended the invitation |
| `{{{invites.url}}}` | The URL of the recipient’s group invitations management screen |

#### A member has requested permission to join a group.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group-requests.url}}}` | The URL of the group’s membership requests management screen |
| `{{requesting-user.name}}` | Display name of the user who is requesting membership |
| `{{{profile.url}}}` | The URL of the user’s profile of the one who is requesting membership |
| `{{admin.id}}` | User ID of the group admin who is receiving this email |
| `{{group.id}}` | ID of the group |
| `{{membership.id}}` | ID of the Group membership object |
| `{{requesting-user.id}}` | ID of the user who is requesting membership |

#### Recipient had requested to join a group, which was accepted.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{group.id}}` | ID of the group |
| `{{requesting-user.id}}` | User ID of the user who is requesting membership |

#### Recipient had requested to join a group, which was rejected.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{group.id}}` | ID of the group |
| `{{requesting-user.id}}` | User ID of the user who is requesting membership |

#### Recipient had requested to join a group, which was accepted by admin.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{group.id}}` | ID of the group |
| `{{{leave-group.url}}}` | The URL of the page to leave a group |

#### Recipient had requested to join a group, which was rejected by admin.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{group.id}}` | ID of the group |

#### Recipient’s status within a group has changed.

| Tokens | Description |
| ------ | ----------- |
| `{{group.name}}` | The name of the group |
| `{{{group.url}}}` | The URL of the group’s landing page |
| `{{promoted_to}}` | The description of the new group role. Possible values: "an administrator" or "a moderator" |
| `{{group.id}}` | ID of the group |
| `{{user.id}}` | User ID of the promoted user |

### Messages situations

> [!NOTE]
> The following situations are only used if Messages component is active.

#### Recipient has received a private message.

| Tokens | Description |
| ------ | ----------- |
| `{{usersubject}}` | The subject of the message |
| `{{usermessage}}` | The content of the message |
| `{{{message.url}}}` | The URL of the message thread |
| `{{sender.name}}` | Display name of the message sender |
		
### Settings situations

> [!NOTE]
> The following situations are only used if Settings component is active.

#### Recipient has changed their email address.

| Tokens | Description |
| ------ | ----------- |
| `{{{verify.url}}}` | The URL of the page used to verify the new email address |
| `{{displayname}}` | Display name of the recipient |
| `{{old-user.email}}` | The user’s previous email address |
| `{{user.email}}` | The user’s new email address |
