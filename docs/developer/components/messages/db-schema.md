# Messages database tables

When first activated, the Messages component creates 4 tables. One is storing sent messages data (`{$table_prefix}bp_messages_messages`), the second one is about managing messages recipients (`{$table_prefix}bp_messages_recipients`) and the last one is used to store meta data about messages (`{$table_prefix}bp_messages_meta`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_messages_messages`

This table stores private messages data.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique message ID. |
| `thread_id` | `BIGINT` | ID of the conversation thread, the message belongs to. |
| `sender_id` | `BIGINT` | ID of the user who inited the conversation thread. |
| `subject` | `VARCHAR` | Subject of the message. |
| `message` | `LONGTEXT` | The message content. |
| `date_sent` | `DATETIME` | The date when the message was sent. |

## `{$table_prefix}bp_messages_recipients`

This table stores messages recipients data.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique field ID. |
| `user_id` | `BIGINT` | ID of the user involved into the private conversation. |
| `thread_id` | `BIGINT` | ID of the conversation thread, the user is involved in. |
| `unread_count` | `INT` | The number of unread messages for the thread and user. |
| `sender_only` | `TINYINT` | Whether the recipient is the only sender or not. |
| `is_deleted` | `TINYINT` | Whether the recipient has deleted the message or not. |

## `{$table_prefix}bp_messages_meta`

This table is used to store meta data about messages.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique meta ID. |
| `message_id` | `BIGINT` | ID of the message entry, the meta data relates to. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^1]. |

[^1]: Arrays or Objects are stored as serialized data.
