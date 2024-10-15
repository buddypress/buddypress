# xProfile database tables

When first activated, the xProfile component creates 4 tables to store xProfile information about your members. The first one is storing groups of profile fields data (`{$table_prefix}bp_xprofile_groups`), the second one is about managing profile fields data (`{$table_prefix}bp_xprofile_fields`) that is relative to profile field groups. The third table is dealing with user information for the created profile fields (`{$table_prefix}bp_xprofile_data`) and the fourth one is used to store meta data about profile groups, fields and data (`{$table_prefix}bp_xprofile_meta`).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_xprofile_groups`

This table stores groups of profile fields data.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique ID for the profile fields group. |
| `name` | `VARCHAR` | Name of the profile fields group. |
| `description` | `MEDIUMTEXT` | Description of the profile fields group. |
| `group_order` | `BIGINT` | Informs about the display order of profile fields group inside a loop. |
| `can_delete` | `TINYINT` | Whether the profile fields group can be deleted or not[^1]. |

## `{$table_prefix}bp_xprofile_fields`

This table stores profile fields data.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique field ID. |
| `group_id` | `BIGINT` | ID of the profile fields group the field belongs to. |
| `parent_id` | `BIGINT` | Parent field ID (for hierarchical fields). |
| `type` | `VARCHAR` | The type of field (e.g.: text, checkbox, select). |
| `name` | `VARCHAR` | The name of the field. |
| `description` | `LONGTEXT` | Description of the field. |
| `is_required` | `TINYINT` | Whether the field is required. |
| `is_default_option` | `TINYINT` | Whether this is the default option (in case of multiple choice parent field). |
| `field_order` | `BIGINT` | Informs about the display order of the field inside a profile fields group. |
| `option_order` | `BIGINT` | Informs about the display order of the field inside a multiple choice parent field. |
| `order_by` | `VARCHAR` | The sorting method for the field. |
| `can_delete` | `TINYINT` | Whether the profile fields can be deleted or not[^2]. |

## `{$table_prefix}bp_xprofile_data`

This table is dealing with user information for the created profile fields.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique profile data ID. |
| `field_id` | `BIGINT` | ID of the profile field, user data is attached to. |
| `user_id` | `BIGINT` | ID of the user, data belongs to |
| `value` | `LONGTEXT` | Value of the profile field for the user. |
| `last_updated` | `DATETIME` | When the field data was updated for the last time. |

## `{$table_prefix}bp_xprofile_meta`

This table is used to store meta data about profile groups, fields and data.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique meta ID. |
| `object_id` | `BIGINT` | ID of the profile group, field or data entry, the meta data relates to. |
| `object_type` | `VARCHAR` | Type of object: 'group', 'field', or 'data'. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^3]. |

[^1]: for instance the "base" default BuddyPress profile fields group cannot be deleted.
[^2]: for instance the "name" default BuddyPress profile field cannot be deleted.
[^3]: Arrays or Objects are stored as serialized data.
