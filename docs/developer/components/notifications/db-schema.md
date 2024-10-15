# Notifications database tables

When first activated, the Notifications component creates 2 tables. One to store user notifications (`{$table_prefix}bp_notifications`), the other to store metadata about notifications (`{$table_prefix}bp_notifications_meta`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_notifications`

This table stores notifications about user interactions.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique notification ID. |
| `user_id` | `BIGINT` | The ID of the user receiving the notification. |
| `item_id` | `BIGINT` | Primary item related to the notification. |
| `secondary_item_id` | `BIGINT` | Secondary item, if applicable. |
| `component_name` | `VARCHAR` | Name of the BuddyPress component (e.g.: groups, messages). |
| `component_action` | `VARCHAR` | Action triggering the notification (e.g., group invite, message received). |
| `date_notified` | `DATETIME` | When the notification was created. |
| `is_new` | `TINYINT` | Whether the notification is new. |
			

## `{$table_prefix}bp_notifications_meta`

Stores metadata associated with notifications.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Meta ID |
| `notifiction_id` | `BIGINT` | The ID of the associated notification. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^1]. |

[^1]: Arrays or Objects are stored as serialized data.
