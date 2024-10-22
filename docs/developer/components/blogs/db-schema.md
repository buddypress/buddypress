# Blogs database tables

When first activated and if the WordPress site uses a multisite configuration, the Blogs component creates 2 tables. One to store users relationship to blogs (`{$table_prefix}bp_user_blogs`), the other to store metadata about blogs (`{$table_prefix}bp_user_blogs_blogmeta`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_user_blogs`

This table stores users relationship to blogs.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | The unique ID for each user/blog relationships. |
| `user_id` | `BIGINT` | The ID of the user who has a relationship with the blog. |
| `blog_id` | `BIGINT` | The ID of the blog, the user has a relationship with. |

## `{$table_prefix}bp_user_blogs_blogmeta`

Stores metadata associated with blog entries.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Meta ID |
| `blog_id` | `BIGINT` | The ID of the associated blog. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^1]. |

[^1]: Arrays or Objects are stored as serialized data.
