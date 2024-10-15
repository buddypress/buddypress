# Groups database tables

When first activated, the Groups component creates 3 tables. One to store groups characteristics (`{$table_prefix}bp_groups`), another one to store metadata about items (`{$table_prefix}bp_groups_groupmeta`), and one to store groups memberships (`{$table_prefix}bp_groups_members`).

> [!IMPORTANT]  
> The Groups component also uses the BP Invitation API to manage single groups invitations. The database table used by this API is always installed and is documented into the [Members database tables chapter](../members/db-schema.md).

> [!NOTE]  
> The `{$table_prefix}` value is `wp_` by default but it can be customized within the WordPress [wp-config.php file](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix).

## `{$table_prefix}bp_groups`

This table stores all groups (such as public, private or hidden ones) created by users in BuddyPress.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique ID for the group. |
| `creator_id` | `BIGINT` | User ID of the group creator. |
| `name` | `VARCHAR` | Name of the group. |
| `slug` | `VARCHAR` | Unique slug for the group [^1]. |
| `description` | `LONGTEXT` | Group description. |
| `status` | `VARCHAR` | Group visibility status (public, private, hidden). |
| `parent_id` | `BIGINT` | Parent group ID (if applicable) [^2]. |
| `enable_forum` | `TINYINT` | Whether the group has forums enabled [^3]. |
| `date_created` | `DATETIME` | When the group was created. |

## `{$table_prefix}bp_groups_groupmeta`

Stores metadata associated with groups.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Meta ID |
| `group_id` | `BIGINT` | The ID of the associated group. |
| `meta_key` | `VARCHAR` | Metadata key. |
| `meta_value` | `LONGTEXT` | Metadata value[^4]. |

## `{$table_prefix}bp_groups_members`

Stores metadata associated with groups.

| Name | Type | Description |
| --- | --- | --- |
| `id` | `BIGINT` | Unique group membership ID |
| `group_id` | `BIGINT` | The ID of the associated group. |
| `user_id` | `BIGINT` | The ID of the user. |
| `inviter_id` | `BIGINT` | The ID of the user who invited the member (if applicable). |
| `is_admin` | `TINYINT` | Whether the user is an admin of the group. |
| `is_mod` | `TINYINT` | Whether the user is a moderator of the group |
| `user_title` | `VARCHAR` | The role or title of the user within the group. |
| `date_modified` | `DATETIME` | When the membership was last modified. |
| `comments` | `LONGTEXT` | Private group membership request message [^5]. |
| `is_confirmed` | `TINYINT` | Whether the membership has been confirmed. |
| `is_banned` | `TINYINT` | Whether the user is banned from the group. |
| `invite_sent` | `TINYINT` | Whether an invitation has been sent. |

[^1]: the group's `slug` is the URL portion used to reach a single group (e.g.: `site.url/groups/{$group-slug}`).
[^2]: the `parent_id` field is not used by BuddyPress internally to provide a groups hierarchy feature leaving this part to BuddyPress Add-ons. See changeset [11095](https://buddypress.trac.wordpress.org/changeset/11095).
[^3]: the `enable_forum` is used by the [bbPress](https://wordpress.org/plugins/bbpress/) plugin to inform the corresponding group has a forum associated to it.
[^4]: Arrays or Objects are stored as serialized data.
[^5]: Not used anymore. Kept for backwards compatibility reasons.
