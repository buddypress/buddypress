# Friends database tables

When first activated, the Friends component creates a table to store users friendships (`{$table_prefix}bp_friends`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_friends`

This table stores the friendship connections between users.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique ID for each friendship connection. |
| `initiator_user_id` | `BIGINT` | The user ID who sent the friend request. |
| `friend_user_id` | `BIGINT` | The user ID who received the friend request. |
| `is_confirmed` | `TINYINT` | Whether the friendship has been confirmed (1) or is pending (0). |
| `is_limited` | `TINYINT` | Whether the friendship has any limitations [^1]. |
| `date_created` | `DATETIME` | When the friendship was initiated. |

[^1]: Not used anymore. Kept for backwards compatibility reasons.
