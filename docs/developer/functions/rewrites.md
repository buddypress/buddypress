# BuddyPress Rewrites Functions

These functions help you build BuddyPress URLs whether the WordPress sites is using plain or pretty permalinks.

**NB**: In version 12.0.0, in order to make sure to support plain permalinks and customizable slugs, a lot of the legacy URL functions were deprecated as they were often used to concatenate URL chunks. The full list of these deprecated functions is available inside the plugin's [bp-core/deprecated/12.0.php](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php) file. 

## Core functions

### `bp_rewrites_get_url()`

This is the BuddyPress base function to build BP URLs. **All** other available BuddyPress functions building BP URLs are finally using this base function. This function is exposing a WordPress filter hook just before returning the built URL. If you need to override all BuddyPress URLs, you can use the `bp_rewrites_get_url` filter to do so, making sure to get the 2 available filter arguments. The first one is the built URL and the second one contains all arguments used to build the URL.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_rewrites_get_url()` accepts only one argument, which is an associative array containing keys referring to BuddyPress objects "hierarchy". Below is the list of most common available keys.

| Argument's keys | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$component_id`|`string`|No|`''`|The BuddyPress component ID the URL is built for. Possible value is one of the BuddyPress active components (eg: `members`)|
|`$directory_type`|`string`|No|`''`|BuddyPress members or groups can be organized in types a bit like a taxonomy can organize WordPress posts. Possible values are slugs of the registered group or member types.|
|`$single_item`|`string`|No|`''`|The BP Members, the BP Groups & the BP Activity components are supporting individual items such as a member slug, a group slug or an activity ID|
|`$single_item_component`|`string`|No|`''`|The BP Members component individual pages (the member pages) are organized according to other BuddyPress components to list the displayed member's Activities, Groups or Friends for instance. Possible values are slugs of the active components. Other BP Components such as the Groups or Activity ones are not using the same URL schema|
|`$single_item_action`|`string`|No|`''`|The BP Members component uses this parameter to reach sub-pages of a Member's component page. The BP Groups component uses it to reach front-end component pages or the root page of the group’s management area. The BP Activity component uses it to perform an action about the `single_item`.|
|`$single_item_action_variables`|`[string]`|No|`[]`|Used to pass as many as needed additional variables|

#### Example of use.

```php
// Outputs the Members directory URL.
echo bp_rewrites_get_url(
	array(
		'component_id' => 'members',
	)
);
```

### `bp_rewrites_get_slug()`

This is the function you use to get a BuddyPress customized slug. Since 12.0.0, Site Administrators can use the "URLs" BuddyPress settings screen of their WordPress Dashboard to customize the wide majority of BuddyPress URLs. To retrieve a customized BP URL, you need to use this function to get each customized portion of it. If a customized slug is not found, it will return the value of the default slug.

You need to use `bp_rewrites_get_slug()` to get the customized slugs for the following parameters of the argument you pass to the `bp_rewrites_get_url()` function:

- `$single_item_component`,
- `$single_item_action`,
- each non-numerical elements of the `$single_item_action_variables` array.

**NB**: when the BP Query Parser is overriden to use the 'legacy' one (eg: when you activated the [BP Classic](https://wordpress.org/plugins/bp-classic/) Add-on) instead of the 'rewrites' one, the default slug is immediately returned.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_rewrites_get_slug()` needs 3 arguments to retrieve the customized slug.

| Arguments | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$component_id`|`string`|Yes|`''`|The BuddyPress component ID the URL is built for. Possible value is one of the BuddyPress active components (eg: `members`)|
|`$rewrite_id`|`string`|Yes|`''`|The BuddyPress component's Rewrite ID element the URL is built for. This Rewrite ID element is a string concatenation using default BuddyPress slugs behind the singular value of a component (eg: members => member) separated by underscores. For instance to get the customized slug for the public profile page of a member, you will use `member_profile_public`. It's important to note that rewrite IDs do not contain `-`, if a default BP slug contains one or more, you need to replace it/them with `_`|
|`$default_slug`|`string`|Yes|`''`|The BuddyPress component's default slug for the the URL portion. For instance the default slug of the public profile page of a member is `public`|

#### Example of use.

```php
// Init a single member's URL.
$args = array(
	'component_id' => 'members', // The BP Members component ID.
	'single_item'  => 'imath',   // The user slug (stored in $wpdb->users.user_nicename).
);

// Get the customized part of the URL.
$args['single_item_component'] = bp_rewrites_get_slug(
	'members',         // The BP Members component ID.
	'member_activity', // The screen rewrite ID.
	'activity'         // The sub page default slug.
);

// Outputs the customized URL for the Activity page of the member
echo bp_rewrites_get_url( $args );
```

## Members functions

### `bp_members_get_user_slug()`

Thanks to this function you can get the user's slug (which is stored in `$wpdb->users.user_nicename`) thanks to their user ID. This function is exposing a WordPress filter hook just before returning the user's slug. If you need to override a specific user's slug, you can use the `bp_members_get_user_slug` filter making sure to get the 2 available filter arguments. The first one is the retrieved user's slug and the second one contains the corresponding user ID. `bp_members_get_user_url()` uses this function to build a user's url.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_members_get_user_slug()` requires one argument, the user ID.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$user_id`|`integer`|Yes|`0`|The user ID the slug is retrieved for. Possible value is one of the existing users ID (eg: `134`)|

### `bp_members_get_path_chunks()`

_This function was introduced in version 12.0.0_

### `bp_members_get_user_url()`

_This function was introduced in version 12.0.0_

### `bp_get_members_directory_permalink()` &  `bp_members_directory_permalink()`

_These functions were introduced in version 1.5.0_

### `bp_get_member_type_directory_permalink()` & `bp_member_type_directory_permalink()`

_These functions were introduced in version 2.5.0_

### `bp_get_signup_page()` & `bp_signup_page()`

_These functions were introduced in versions 1.1.0 & 1.0.0_

### `bp_get_activation_page()` & `bp_activation_page()`

_These functions were introduced in versions 1.2.0 & 1.0.0_

## Groups functions

### `groups_get_slug()`

_This function was introduced in version 1.0.0_

### `bp_groups_get_path_chunks()`

_This function was introduced in version 12.0.0_

### `bp_get_group_url()`

_This function was introduced in version 12.0.0_

### `bp_get_group_manage_url()` & `bp_group_manage_url()`

_These functions were introduced in version 12.0.0_

### `bp_get_groups_directory_url` & `bp_groups_directory_url()`

_These functions were introduced in version 12.0.0_

### `bp_get_group_type_directory_permalink()` & `bp_group_type_directory_permalink()`

_These functions were introduced in version 2.7.0_

### `bp_groups_get_create_url()`

_This function was introduced in version 12.0.0_

## Activity functions

### `bp_activity_get_permalink()`

_This function was introduced in version 1.2.0_

### `bp_get_activity_directory_permalink()` & `bp_activity_directory_permalink()`

_This function was introduced in version 1.5.0_

## Blogs functions

### `bp_get_blogs_directory_url()` && `bp_blogs_directory_url()`

_These functions were introduced in version 12.0.0_
