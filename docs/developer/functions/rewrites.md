# BuddyPress Rewrites Functions

These functions help you build BuddyPress URLs whether the WordPress sites is using plain or pretty permalinks.

**NB**: In version 12.0.0, in order to make sure to support plain permalinks and customizable slugs, a lot of the legacy URL functions were deprecated as they were often used to concatenate URL chunks. The full list of these deprecated functions is available inside the plugin's [bp-core/deprecated/12.0.php](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php) file. 

## Core functions

### `bp_rewrites_get_url()`

This is the BuddyPress base function to build BP URLs. **All** other available BuddyPress functions building BP URLs are finally using this base function. This function is exposing a WordPress filter hook just before returning the built URL. If you need to override all BuddyPress URLs, you can use the `bp_rewrites_get_url` filter to do so, making sure to get the 2 available filter arguments. The first one is the built URL and the second one contains all arguments used to build the URL.

#### Arguments

`bp_rewrites_get_url()` accepts only one argument, which is an associative array containing keys referring to BuddyPress objects "hierarchy". Below is the list of most common available keys.

| Keys | Type | Required | Defaults | Description |
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

#### Arguments

`bp_rewrites_get_slug()` needs 3 arguments to retrieve the customized slug.

| Keys | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$component_id`|`string`|Yes|`''`|The BuddyPress component ID the URL is built for. Possible value is one of the BuddyPress active components (eg: `members`)|
|`$rewrite_id`|`string`|Yes|`''`|The BuddyPress component's Rewrite ID element the URL is built for. This Rewrite ID element is a string concatenation using default BuddyPress slugs behind the singular value of a component (eg: members => member). For instance to get the customized slug for the public profile page of a member, you will use `member_profile_public`|
|`$default_slug`|`string`|Yes|`''`|The BuddyPress component's default slug for the the URL portion. For instance the default slug of the public profile page of a member is `public`|

#### Example of use.

```php
// Init a single member's URL.
$args = array(
	'component_id' => 'members',
	'single_item'  => 'imath' // The user slug (stored in $wpdb->users.user_nicename).
);

// Get the customized part of the URL.
$args['single_item_component'] = bp_rewrites_get_slug( 'members', 'member_activity', 'activity' );

// Outputs the customized URL for the Activity page of the member
echo bp_rewrites_get_url( $args );
```
