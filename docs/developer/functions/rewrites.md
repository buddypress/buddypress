# BuddyPress Rewrites Functions

These functions help you build BuddyPress URLs whether your WordPress site is using plain or pretty permalinks.

**NB**: In version 12.0.0, in order to make sure to support plain permalinks and customizable slugs, many legacy URL functions were deprecated as they were often used to concatenate URL chunks. The full list of these deprecated functions is available inside the plugin's [bp-core/deprecated/12.0.php](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php) file. 

## Core functions

### `bp_rewrites_get_url()`

This is the base function used to build BuddyPress URLs. **All** other BuddyPress URL functions rely on this base function. This function exposes a WordPress filter hook just before returning the built URL. If you need to override all BuddyPress URLs, you can use the `bp_rewrites_get_url` filter to do so, making sure to get the 2 available filter parameters. The first parameter is the built URL and the second parameter contains all of the arguments that were used to build the URL.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_rewrites_get_url()` accepts only one argument, which is an associative array containing keys referring to the "hierarchy" ofBuddyPress objects. Below is a list of the most commonly available keys.

| Array key | Type | Required | Default | Description |
|---|---|---|---|---|
|`component_id`|`string`|No|`''`|The BuddyPress component ID the URL is built for. Possible value is an active BuddyPress components (e.g.: `members`).|
|`directory_type`|`string`|No|`''`|BuddyPress members or groups can be organized in types a bit like a taxonomy can organize WordPress posts. Possible values are slugs of the registered group or member types.|
|`single_item`|`string`|No|`''`|The BP Members, BP Groups, and BP Activity components support individual items such as a member slug, a group slug or an activity ID.|
|`single_item_component`|`string`|No|`''`|The BP Members component individual pages (the member pages) are organized according to other BuddyPress components to list the displayed member's Activities, Groups or Friends for instance. Possible values are slugs of the active components. Other BP Components such as the Groups or Activity do not use the same URL schema.|
|`single_item_action`|`string`|No|`''`|The BP Members component uses this parameter to reach sub-pages of a Member's component page. The BP Groups component uses it to reach front-end component pages or the root page of the group’s management area. The BP Activity component uses it to perform an action on the `single_item`.|
|`single_item_action_variables`|`[string]`|No|`[]`|Used to pass as many additional variables as needed.|

#### Example of use

```php
// Outputs the Members directory URL.
echo bp_rewrites_get_url(
	array(
		'component_id' => 'members',
	)
);
```

### `bp_rewrites_get_slug()`

This is the function used to get a BuddyPress customized slug. Since 12.0.0, Site Administrators can use the "URLs" BuddyPress settings screen of their WordPress Dashboard to customize the majority of BuddyPress URLs. To retrieve a customized BP URL, you need to use this function to get each customized portion of it. If a customized slug is not found, it will return the value of the default slug.

You need to use `bp_rewrites_get_slug()` to get the customized slugs for the following parameters of the argument you pass to the `bp_rewrites_get_url()` function:

- `single_item_component`,
- `single_item_action`,
- each non-numerical element of the `single_item_action_variables` array.

**NB**: when the BP Query Parser is overriden in favor of using the 'legacy' parser (e.g.: when the [BP Classic](https://wordpress.org/plugins/bp-classic/) Add-on is in use) the default slug will be immediately returned and 'rewrite' slugs will be ignored.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_rewrites_get_slug()` needs 3 arguments to retrieve the customized slug.

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
|`$component_id`|`string`|Yes|`''`|The BuddyPress component ID the URL is built for. Possible value is the ID of an active BuddyPress component (e.g.: `members`)|
|`$rewrite_id`|`string`|Yes|`''`|The BuddyPress component's Rewrite ID element the URL is built for. This Rewrite ID element is a string concatenation using default BuddyPress slugs behind the singular value of a component (e.g.: members => member) separated by underscores. For instance, to get the customized slug for the public profile page of a member, you will use `member_profile_public`. It's important to note that rewrite IDs do not contain `-`, if a default BP slug contains one or more, you need to replace it/them with `_`|
|`$default_slug`|`string`|Yes|`''`|The BuddyPress component's default slug for the the URL portion. For instance the default slug of the public profile page of a member is `public`|

#### Example of use

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

Thanks to this function you can get the user's slug (which is stored in `$wpdb->users.user_nicename`). This function exposes a WordPress filter hook just before returning the user's slug. If you need to override a specific user's slug, you can use the `bp_members_get_user_slug` filter making sure to get the 2 available filter arguments. The first argument is the retrieved user's slug and the second contains the corresponding user ID. `bp_members_get_user_url()` uses this function to build a user's url.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_members_get_user_slug()` requires one argument, the user ID.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$user_id`|`integer`|Yes|`0`|The user ID the slug is retrieved for. Possible value is one of the existing users ID (e.g.: `134`)|

### `bp_members_get_path_chunks()`

This function eases the way to get a list of customized URL slugs for a single Members page. Instead of building an associative array of customized slugs using `bp_rewrites_get_slug()` on each element of this array, you can use this function to prepare this associative array out of a regular array of BuddyPress default slugs ordered according to their position in the URL (e.g.: to build arguments for this portion of a member URL `profile/edit/group/1`, you would use `array( 'profile', 'edit', 'group', 1 )` ).

_This function was introduced in version 12.0.0_

#### Arguments

`bp_members_get_path_chunks()` requires one argument, an array of path chunks.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$chunks`|`[string]`|Yes|`[]`|The array of path chunks to get the associative array expected by `bp_members_get_user_url()`|

#### Example of use

```php
// The list of path chunks ordered as they would appear in the URL.
$chunks = array( 'profile', 'edit', 'group', 1 );

$member_path_chunks = bp_members_get_path_chunks( $chunks );

// Here's an example of the value returned by bp_members_get_path_chunks( $chunks ).
$path_chunks = array(
	'single_item_component'        => 'profil',   // 'profil' is French for 'profile'.
	'single_item_action'           => 'modifier', // 'modifier' is French for 'edit'.
	'single_item_action_variables' => array(
		'groupe', // 'groupe' is French for 'group'.
		1
	)
);
```

### `bp_members_get_user_url()`

Use this function to build a single Members' URL. It uses `bp_members_get_user_slug()` and `bp_rewrites_get_url()` to do so and exposes a WordPress filter hook just before returning the built URL. If you need to override single Members BuddyPress URLs, you can use the `bp_members_get_user_url` filter to do so, making sure to get the 4 available filter arguments. The first argument is the built URL, the second & third are the user ID and slug and the last one contains the associative array of path chunks used to build the URL.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_members_get_user_url()` accepts two arguments: the user ID and an associative array of path chunks.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$user_id`|`integer`|Yes|`0`|The user ID the URL is retrieved for. Possible value is one of the existing users ID (e.g.: `134`)|
|`$path_chunks`|`array`|No|`''`|This associative array of arguments is described below|

`$path_chunks` list of arguments:

| Array key | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`single_item_component`|`string`|No|`''`|The BP Members component individual pages (the member pages) are organized according to other BuddyPress components to list the displayed member's Activities, Groups or Friends for instance. Possible values are slugs of the active components.|
|`single_item_action`|`string`|No|`''`|The BP Members component uses this parameter to reach sub-pages of a Member's component page.|
|`single_item_action_variables`|`[string]`|No|`[]`|Used to pass as many as needed additional variables.|

#### Example of use

```php
$user_id = 134;

// User url is looking like https://site.url/members/user_slug/profile/edit/group/1/.
$user_url = bp_members_get_user_url(
	$user_id,
	bp_members_get_path_chunks( array( 'profile', 'edit', 'group', 1 ) )
);
```

### `bp_get_members_directory_permalink()` &  `bp_members_directory_permalink()`

The first function,`bp_get_members_directory_permalink()`, uses `bp_rewrites_get_url()` to return the Members directory URL. It exposes a WordPress filter hook just before returning the built URL. If you need to override the Members directory URL, you can use the `bp_get_members_directory_permalink` filter to do so. The second function, `bp_members_directory_permalink()`, simply echoes the built URL after escaping it.

_These functions were introduced in version 1.5.0_

### `bp_get_member_type_directory_permalink()` & `bp_member_type_directory_permalink()`

The first function, `bp_get_member_type_directory_permalink()`, uses `bp_rewrites_get_url()` to return the Members directory URL for users having a specific member type (passed as an argument). It exposes a WordPress filter hook just before returning the built URL. If you need to override the URL showing all users having the requested member type, you can use the `bp_get_member_type_directory_permalink` filter to do so. You'll get the member type object from the second argument of this filter. The second function, `bp_member_type_directory_permalink()`, echoes the built URL after escaping it.

_These functions were introduced in version 2.5.0_

#### Arguments

`bp_get_member_type_directory_permalink()` accepts one argument: the requested member type ID. If no member type ID is passed, it will try to use the member type previously globalized as the current type during the loading process. If this global is not available, an empty string will be returned instead of the URL to list all users having the requested/global member type.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$member_type`|`string`|No|`''`|The member type ID|

### `bp_get_signup_page()` & `bp_signup_page()`

The first function, `bp_get_signup_page()`, uses `bp_rewrites_get_url()` to return the URL to the registration form. It exposes a WordPress filter hook just before returning the built URL. If you need to override this URL, you can use the `bp_get_signup_page` filter to do so. The second function, `bp_signup_page()`, echoes the built URL after escaping it.

_These functions were introduced in versions 1.1.0 & 1.0.0_

### `bp_get_activation_page()` & `bp_activation_page()`

The first function, `bp_get_activation_page()`, uses `bp_rewrites_get_url()` to return the URL to the account activation form. It exposes a WordPress filter hook just before returning the built URL. If you need to override this URL, you can use the `bp_get_activation_page` filter to do so. The second function, `bp_activation_page()`, echoes the built URL after escaping it.

_These functions were introduced in versions 1.2.0 & 1.0.0_

## Groups functions

### `groups_get_slug()`

You can get a specific group's slug using this function. You just need to pass it a group ID or a `BP_Groups_Group` object to do so. If the group ID doesn't match an existing group, you'll get an empty string.

_This function was introduced in version 1.0.0_

#### Arguments

`groups_get_slug()` requires one argument: the group ID or the group object.

| Argument | Type | Required | Description |
|---|---|---|---|
|`$group`|`integer` or `BP_Groups_Group`|Yes|The group ID or the group object.|

### `bp_groups_get_path_chunks()`

This function eases the way to get a list of customized URL slugs for a single Groups page. Instead of building an associative array of customized slugs using `groups_get_slug()` on each element of this array, you can use this function to prepare this associative array out of a regular array of BuddyPress default slugs ordered according to their position in the URL (e.g.: to build arguments for this portion of a group URL `members`, you would use `array( 'members' )`).

_This function was introduced in version 12.0.0_

#### Arguments

`bp_groups_get_path_chunks()` accepts two arguments: an array of path chunks and the context for the URL. This context informs about whether you need to get the URL of a regular Group page (`'read'`), the URL of one of the steps of the creation process (`'create'`) or the URL to the front-end's management (in another word: administration) pages of the group (`'manage'`).

**NB**: for the `'create'` & `'manage'` contexts, you should always use this function along with the `bp_groups_get_create_url()` and `bp_get_group_manage_url()` functions. That's because these two contexts only need the action variables part of the BP URL as they already occupy the action part of the URL for their specific create and admin keyword slugs.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$chunks`|`[string]`|Yes|`[]`|The array of path chunks to get the associative array expected by `bp_get_group_url()`, `bp_get_group_manage_url()` or `bp_groups_get_create_url()`|
|`$context`|`string`|No|`'read'`|The context for the URL. Possible values are `'read'`, `'create'`, or `'manage'`.|

```php
// Example for the "read" context.
$read_path_chunks = bp_groups_get_path_chunks( array( 'members' ), 'read' );

// Here's an example of the value returned by bp_groups_get_path_chunks( array( 'members' ), 'read' ).
$path_chunks = array(
	'single_item_action' => 'membres', // 'membres' is French for 'members'.
);

// NB: use $read_path_chunks along with bp_get_group_url().

// Example for the "manage" context.
$manage_path_chunks = bp_groups_get_path_chunks( array( 'manage-members' ), 'manage' );

// Here's an example of the value returned by bp_groups_get_path_chunks( array( 'manage-members' ), 'manage' ).
$path_chunks = array(
	'single_item_action_variables' => 'gerer-membres', // 'gerer-membres' is French for 'manage-members'.
);

// NB: use $manage_path_chunks along with bp_get_group_manage_url().

// Example for the "create" context.
$create_path_chunks = bp_groups_get_path_chunks( array( 'group-settings' ), 'create' );

// Here's an example of the value returned by bp_groups_get_path_chunks( array( 'group-settings' ), 'create' ).
$path_chunks = array(
	'create_single_item'           => 1,
	'create_single_item_variables' => 'reglages-du-groupe', // 'reglages-du-groupe' is French for 'group-settings'.
);

// NB: use $create_path_chunks along with bp_groups_get_create_url().
```

### `bp_get_group_url()`

Use this function to build a single Group's URL. It uses `groups_get_slug()` and `bp_rewrites_get_url()` to do so and exposes a WordPress filter hook just before returning the built URL. If you need to override single Groups BuddyPress URLs, you can use the `bp_get_group_url` filter to do so, making sure to get the 4 available filter arguments. The first argument is the built URL, the second and third are the group ID and slug and the last one contains the associative array of path chunks used to build the URL.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_get_group_url()` accepts two arguments: the group ID (or a group object) and an associative array of path chunks.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$group`|`integer` or `BP_Groups_Group`|Yes|`0`|The group ID (or the group object) the URL is retrieved for.|
|`$path_chunks`|`array`|No|`[]`|This associative array of arguments is described below|

`$path_chunks` list of arguments:

| Array key | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`single_item_action`|`string`|No|`''`|The BP Groups component uses this to reach front-end component pages.|
|`single_item_action_variables`|`[string]`|No|`[]`|Used to pass as many additional variables as needed.|

#### Example of use

```php
$group_id = 12;

// Returned URL is like https://site.url/groups/group_slug/members/.
$user_url = bp_get_group_url(
	$group_id,
	bp_groups_get_path_chunks( array( 'members' ) )
);
```

### `bp_get_group_manage_url()`

Use this function to get a single Group's front-end admin (management area) URL. It uses `bp_get_group()` and `bp_get_group_url()` to do so and exposes a WordPress filter hook just before returning the built URL. If you need to override single Groups front-end admin BuddyPress URLs, you can use the `bp_get_group_manage_url` filter to do so, making sure to get the 3 available filter arguments. The first argument is the built URL, the second is the group object and the last one contains the associative array of path chunks used to build the URL. 

_This function was introduced in version 12.0.0_

#### Arguments

`bp_get_group_manage_url()` accepts two arguments: the group ID, a group slug or a group object, and an associative array of path chunks.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$group`|`integer`, `string` or `BP_Groups_Group`|Yes|`false`|The group ID, a group slug, or the group object.|
|`$path_chunks`|`array`|No|`[]`|This associative array of arguments is described below|

`$path_chunks` list of arguments:

| Array key | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`single_item_action_variables`|`[string]`|No|`[]`|Used to pass as many as needed additional variables.|

#### Example of use

```php
$group_id = 12;

// Returned URL is like https://site.url/groups/group_slug/admin/manage-members/.
$user_url = bp_get_group_manage_url(
	$group_id,
	bp_groups_get_path_chunks( array( 'manage-members' ), 'manage' )
);
```

### `bp_group_manage_url()`

This function echoes the URL built by `bp_get_group_manage_url()` after escaping it.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_group_manage_url()` accepts two arguments: the group ID, a group slug or a group object, and an associative array of path chunks. This function uses `bp_groups_get_path_chunks()` to build the associative array expected by `bp_group_manage_url()`.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$group`|`integer`, `string` or `BP_Groups_Group`|Yes|`false`|The group ID, a group slug, or the group object the URL is retrieved for.|
|`$path_chunks`|`array`|No|`[]`|A list of default BuddyPress slugs.|

#### Example of use

```php
$group_id = 12;

// Returned URL is like https://site.url/groups/group_slug/admin/manage-members/.
bp_group_manage_url(
	$group_id,
	array( 'manage-members' )
);
```

### `bp_groups_get_create_url()`

This function returns a URL of the group 'create' context. It is used by `bp_groups_get_path_chunks()` & `bp_get_groups_directory_url()` to do so.

_This function was introduced in version 12.0.0_

#### Arguments

`bp_groups_get_create_url()` only accepts one argument: the list of action variables to add to the base URL of the 'create' context.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$action_variables`|`array`|No|`[]`|A list of default BuddyPress slugs.|

#### Example of use

```php
// Returned URL is like https://site.url/groups/create/step/group-details/.
$create_url = bp_groups_get_create_url( array( 'group-details' ) );
```

### `bp_get_groups_directory_url()` & `bp_groups_directory_url()`

The first function, `bp_get_groups_directory_url()`, uses `bp_rewrites_get_url()` to return the Groups directory URL. It exposes a WordPress filter hook just before returning the built URL. If you need to override the Groups directory URL, you can use the `bp_get_groups_directory_url` filter to do so, making sure to get the 2 available filter arguments. The first argument is the built URL, and the second contains the associative array of path chunks used to build the URL. The second function, `bp_groups_directory_url()`, echoes the built URL after escaping it.

_These functions were introduced in version 12.0.0_

#### Arguments

`bp_get_groups_directory_url()` only accepts one argument: the list of action variables to add to the base URL of the 'create' context.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$path_chunks`|`array`|No|`[]`|This associative array of arguments is described below.|

`$path_chunks` list of arguments:

| Array keys | Type | Required | Description |
|---|---|---|---|
|`create_single_item`|`integer`|No|`1` to generate the URL of the groups 'create' context.|
|`create_single_item_variables`|`[string]`|No|Used to pass as many as needed additional variables for the groups 'create' context.|
|`directory_type`|`string`|No|A group type slug when generating a URL to list all groups of this type is needed.|

### `bp_get_group_type_directory_permalink()` & `bp_group_type_directory_permalink()`

The first function, `bp_get_group_type_directory_permalink()`, uses `bp_get_groups_directory_url()` to return the Groups directory URL that lists groups having a specific group type (the type passed in argument). It exposes a WordPress filter hook just before returning the built URL. If you need to override the URL showing all groups having the requested group type, you can use the `bp_get_group_type_directory_permalink` filter to do so. You'll get the group type object from the second argument of this filter. The second function, `bp_group_type_directory_permalink()`, echoes the built URL after escaping it.

_These functions were introduced in version 2.5.0_

#### Arguments

`bp_get_group_type_directory_permalink()` accepts one argument: the requested group type ID. If no group type ID is passed, it will try to use the group type previously globalized as the current type during the loading process. If this global is not available, an empty string will be returned instead of the URL to list all groups having the requested/global group type.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$group_type`|`string`|No|`''`|The group type ID|

## Activity functions

### `bp_activity_get_permalink()`

Use this function to build a single Activity URL. It uses `bp_rewrites_get_url()` to do so and exposes a WordPress filter hook just before returning the built URL. If you need to override single Activity BuddyPress URLs, you can use the `bp_activity_get_permalink` filter to do so, making sure to get the 2 available filter arguments. The first argument is the built URL, and the second contains the Activity object.

_This function was introduced in version 1.2.0_

#### Arguments

`bp_activity_get_permalink()` accepts two arguments: the activity ID and optionally the corresponding Activity object.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$activity_id`|`integer`|Yes|`Null`|The activity ID the URL is retrieved for.|
|`$activity_obj`|`BP_Activity_Activity`|No|`false`|The activity object the URL is retrieved for|

### `bp_get_activity_directory_permalink()` & `bp_activity_directory_permalink()`

The first function,`bp_get_activity_directory_permalink()`, uses `bp_rewrites_get_url()` to return the Activity directory URL. It exposes a WordPress filter hook just before returning the built URL. If you need to override the Activity directory URL, you can use the `bp_get_activity_directory_permalink` filter to do so. The second function, `bp_activity_directory_permalink()`, echoes the built URL after escaping it.

_This function was introduced in version 1.5.0_

## Blogs functions

### `bp_get_blogs_directory_url()`

This function uses `bp_rewrites_get_url()` to return the Blogs directory URL or the URL to create a new Blog. It exposes a WordPress filter hook just before returning the built URL. If you need to override the generated URL, you can use the `bp_get_blogs_directory_url` filter to do so, making sure to get the 2 available filter arguments. The first argument is the built URL, and the second contains the associative array of path chunks used to build the URL (this array can be useful to help you figure out if the URL to create a new Blog is being generated).

_This function was introduced in version 12.0.0_

#### Arguments

`bp_get_blogs_directory_url()` only accepts one argument: an associative array where the `$create_single_item` key is set to `1`.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$path_chunks`|`array`|No|`[]`|The associative array to generate the URL to create a new Blog.|

### `bp_blogs_directory_url()`

This function echoes the URL built by `bp_get_blogs_directory_url()` after escaping it.

_This function was introduced in version 12.0.0_
