# Activity database tables

When first activated, the Activity component creates 2 tables. One to store activity stream items (`{$table_prefix}bp_activity`), the other to store metadata about items (`{$table_prefix}bp_activity_meta`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

> [!IMPORTANT]  
> The `{$table_prefix}bp_activity` is always created even if the Activity component is not active. BuddyPress is using this table to log members last site activity date/time.

## `{$table_prefix}bp_activity`

This table stores all activities (such as posts, comments, updates) made by users in BuddyPress. It also logs user interactions or community events.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | The unique ID for each activity entry. |
| `user_id` | `BIGINT` | The ID of the user who performed the activity. |
| `component` | `VARCHAR` | Specifies which BuddyPress component (e.g., groups, members) the activity is associated with. |
| `type` | `VARCHAR` | Type of activity (e.g., new post, comment, group creation). |
| `action` | `TEXT` | Human-readable description of the action performed[^1]. |
| `content` | `LONGTEXT` | Main content of the activity. |
| `primary_link` | `TEXT` | The link to the activity item[^2]. |
| `item_id` | `BIGINT` | ID associated with the primary item (group or other). |
| `secondary_item_id` | `BIGINT` | Additional context ID, if applicable. |
| `date_recorded` | `DATETIME` | When the activity was recorded. |
| `hide_sitewide` | `TINYINT` | Whether the activity is hidden from the sitewide activity stream. |
| `mptt_left` | `INT` | Node boundary start for activity or activity comment. |
| `mptt_right` | `INT` | Node boundary end for activity or activity comment. |
| `is_spam` | `TINYINT` | Whether the activity item is marked as spam.

## `{$table_prefix}bp_activity_meta`

Stores metadata associated with activity entries.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Meta ID |
| `activity_id` | `BIGINT` | The ID of the associated activity. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^3]. |

[^1]: BuddyPress is re-generating this `action` during runtime to make it translatable.
[^2]: BuddyPress may re-generate this `primary_link` at runtime for specific activity types, e.g.: it uses post's permalink for `new_blog_post` typed activities.
[^3]: Arrays or Objects are stored as serialized data.
