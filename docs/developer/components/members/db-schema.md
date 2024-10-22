# Members database tables

When first activated, the Members component creates 2 tables. One to store invitations to join the site or a Groups component single item (`{$table_prefix}bp_invitations`), the other to store information about people who opted out to site invitations (`{$table_prefix}bp_optouts`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_invitations`

This table stores invitations to join the site or a Groups component single item.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | The unique ID for each user/blog relationships. |
| `user_id` | `BIGINT` | ID of the invited user. | 
| `inviter_id` | `BIGINT` | ID of the user who created the invitation. |
| `invitee_email` | `VARCHAR` | Email address of the invited user. |
| `class` | `VARCHAR` | Name of the invitations class (the PHP object name) [^1]. |
| `item_id` | `BIGINT` | ID of the object associated with the invitation and class |
| `secondary_item_id` | `BIGINT` | Secondary ID associated with the invitation and class |
| `type` | `VARCHAR` | Type of record this is: 'invite' or 'request'. |
| `content` | `LONGTEXT` | Extra content the inviting user added to their invitation |
| `date_modified` | `DATETIME` | Date the invitation was last modified. |
| `invite_sent` | `TINYINT` | Whether the invitation has been sent or is still a draft. |
| `accepted` | `TINYINT` | Whether the invitation has been accepted or is still pending. |

## `{$table_prefix}bp_optouts`

Stores metadata associated with opt-out entries.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | ID of the opt-out record. |
| `email_address_hash` | `VARCHAR` | Hash of the email which was used to opt-out. |
| `user_id` | `BIGINT` | The ID of the user that generated the contact that resulted in the opt-out. |
| `email_type` | `VARCHAR` | The type of email contact that resulted in the opt-out. |
| `date_modified` | `DATETIME` | The date the opt-out was last modified. |

[^1]: BuddyPress currently uses 2 classes: `BP_Groups_Invitation_Manager` to deal with Groups component single item invitations and `BP_Members_Invitation_Manager` to deal with site invitations.
