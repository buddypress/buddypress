# User Groups REST API routes

The Groups component allow your users to organize themselves into specific public, private or hidden sections of your community site with separate activity streams and member listings.

> [!IMPORTANT]
> The User Groups component is an optional one. This means the `buddypress/v2/groups/*` endpoints will only be available if the component is active on the community site.

## Schema

The schema defines all the fields that exist for a Groups component single item.

| Property | Description |
| --- | --- |
| `id` | A unique numeric ID for the Groups single item. <br />JSON data type: _integer_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `creator_id` | The ID of the user who created the Groups single item. <br />JSON data type: _integer_. <br/>Default: the ID of the logged in member. <br />Context: `embed`, `view`, `edit`. |
| `name` | The name of the Groups single item. <br />JSON data type: _string_. <br/>Required: `true`. <br />Context: `embed`, `view`, `edit`. |
| `slug` | The URL-friendly slug for the Groups single item. <br />JSON data type: _string_. <br />Context: `embed`, `view`, `edit`. |
| `link` | The permalink to the Groups single item on the site. <br />JSON data type: _string_, format: _URI_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `description` | The description of the Groups single item. <br />JSON data type: _object_ (properties: `raw`, `rendered` ). <br/>Required: `true`. <br />Context: `embed`, `view`, `edit`. |
| `status` | The status of the Groups single item (e.g.: `private`). <br />JSON data type: _string_. <br />One of: `public`, `private`, `hidden`. <br />Context: `embed`, `view`, `edit`. |
| `enable_forum` | Whether the Group has a forum enabled or not[^1]. <br />JSON data type: _boolean_. <br />Context: `embed`, `view`, `edit`. |
| `parent_id` | ID of the parent Groups single item[^2]. <br />JSON data type: _integer_. <br />Context: `embed`, `view`, `edit`. |
| `date_created` | The date the Groups single item was created, in the site's timezone. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `date_created_gmt` | The date the Groups single item was created, as GMT. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `view`, `edit`. |
| `created_since` | Time elapsed since the Groups single item was created, in the site's timezone. <br />JSON data type: _string_. <br/>Default: `''`. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `types` | The BP Group type(s) assigned to the Groups single item. See this documentation page for more information about [Group Types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). <br />JSON data type: _array_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `admins` | The list of Groups single item administrators. <br />JSON data type: _array_. <br />Read only. <br />Context: `edit`. |
| `mods` | The list of Groups single item moderators. <br />JSON data type: _array_. <br />Read only. <br />Context: `edit`. |
| `total_member_count` | Count of all Group members. <br />JSON data type: _integer_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `last_activity` | The date the Groups single item was last active, in the site's timezone. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `last_activity_gmt` | The date the Groups single item was last active, as GMT. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `view`, `edit`. |
| `last_activity_diff` | Time elapsed since the Groups single item was last active. <br />JSON data type: _string_. <br/>Default: `''`. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `avatar_urls`[^3] | Avatar URLs for the Groups single item (Full & Thumb sizes). <br />JSON data type: _object_ (properties: `full`, and `thumb`). <br />Read only. <br />Context: `embed`, `view`, `edit`. |

## List User Groups

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br/>Default: `view`. <br/>One of: `view`, `embed`, `edit`. |
| `page` | Current page of the collection. <br />JSON data type: _integer_. <br />Default: `1`. |
| `per_page` | Maximum number of activity items to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. |
| `search` | Limit results to those matching a string. <br />JSON data type: _string_. |
| `exclude` | Ensure result set excludes Groups with specific IDs <br />JSON data type: _array_. <br />Default: `[]`. |
| `include` | Ensure result set includes Groups with specific IDs. <br />JSON data type: _array_. <br />Default: `[]`. |
| `type` | Shorthand for certain orderby/order combinations <br />JSON data type: _string_. <br />Default: `active`. <br />One of: `active`, `newest`, `alphabetical`, `random`, `popular`. |
| `order` | Order sort attribute ascending or descending. <br />JSON data type: _string_. <br />Default: `desc`. <br/>One of: `desc`, `asc`. |
| `orderby` | Order Groups by which attribute. <br />JSON data type: _string_. <br />Default: `date_created`. <br />One of: `date_created`, `last_activity`, `total_member_count`, `name`, `random`. |
| `user_id` | Limit result set to Groups single items that this user (ID) is a member of. <br />JSON data type: _integer_. <br />Default: `0`. |
| `status` | Group statuses to limit results to. <br />JSON data type: _array_. <br />Default: `[]`. <br />One or more of: `public`, `private`, `hidden`. |
| `parent_id` | Get Groups single items that are children of the specified Group(s) IDs[^2]. <br />JSON data type: _array_. <br />Default: `[]`. |
| `meta` | Get Groups based on their meta data information. <br />JSON data type: _array_. <br />Default: `[]`. |
| `group_type` | Limit results set to a certain Group type. See this documentation page for more information about [Group Types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). <br />JSON data type: _string_. <br />Default: `''`. <br />One of: the registered Group types on the site. |
| `enable_forum` | Whether the group has a forum enabled or not. <br />JSON data type: _boolean_. <br />Default: `false`. |
| `populate_extras` | Whether to fetch extra BP data about the returned groups. <br />JSON data type: _boolean_. <br />Default: `false`. |
| `show_hidden` | Whether results should include hidden Groups. <br />JSON data type: _boolean_. <br />Default: `false`. |

### Definition

`GET /buddypress/v2/groups`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups?context=view&populate_extras=true', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.table( data );
} );
```

### JSON Response

- An array of objects representing the matching groups single items on success.
- An object containg the error code, data and message on failure.

## Create a User Group

Logged in users can create Groups single items, unless the Site's administrator has disabled their capacity from [BuddyPress settings](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/settings/options.md#group-photo-upload).

### Arguments

| Name | Description |
| --- | --- |
| `creator_id` | The ID of the user who created the Group. <br />JSON data type: _integer_. <br />Default: the ID of the logged in member. |
| `name` | The name of the Group. <br />JSON data type: _string_. <br />Required. |
| `description` | The description of the Group. <br />JSON data type: _string_. <br />Required. |
| `slug` | The URL-friendly slug for the Group. <br />JSON data type: _string_. |
| `status` | The status of the Group. <br />JSON data type: _string_. <br />One of: `public`, `private`, `hidden`. <br />Default: `public`. |
| `types` | The BP Group type(s) assigned to the Groups single item. See this documentation page for more information about [Group Types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). To assign more than one type, use a comma separated list of types. <br />JSON data type: _string_. <br />One of: the registered group types. |
| `parent_id` | ID of the parent Group[^2]. <br />JSON data type: _integer_. |
| `enable_forum` | Whether the Group has a forum enabled or not. <br />JSON data type: _boolean_. |

### Definition

`POST /buddypress/v2/groups`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups', {
	method: 'POST',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			name: 'Bapuus',
			description: 'bapuu is the BuddyPress wapuu',
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the created Groups single item on success.
- An object containg the error code, data and message on failure.

## Retrieve a specific User Group

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Groups single item. **Required**. <br />JSON data type: _integer_. |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br /> Default: `view`. <br /> One of: `view, embed, edit`. |
| `populate_extras` | Whether to fetch extra BP data about the returned groups. <br />JSON data type: _boolean_. <br />Default: `false`. |

### Definition

`GET /buddypress/v2/groups/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups/4', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the Groups single item on success.
- An object containg the error code, data and message on failure.

## Update a specific User Group

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Groups single item. **Required**. <br />JSON data type: _integer_. |
| `creator_id` | The ID of the user who created the Group. <br />JSON data type: _integer_. <br />Default: the ID of the logged in member. |
| `name` | The name of the Group. <br />JSON data type: _string_. <br />Required. |
| `description` | The description of the Group. <br />JSON data type: _string_. <br />Required. |
| `slug` | The URL-friendly slug for the Group. <br />JSON data type: _string_. |
| `status` | The status of the Group. <br />JSON data type: _string_. <br />One of: `public`, `private`, `hidden`. <br />Default: `public`. |
| `parent_id` | ID of the parent Group[^2]. <br />JSON data type: _integer_. |
| `enable_forum` | Whether the Group has a forum enabled or not. <br />JSON data type: _boolean_. |
| `types` | The BP Group type(s) assigned to the Groups single item. See this documentation page for more information about [Group Types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). To assign more than one type, use a comma separated list of types. <br />JSON data type: _string_. <br />One of: the registered group types. |
| `append_types` | Append one or more BP Group type(s) to a group. To append more than one BP Group type, use a comma separated list of [BP Group types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). <br />JSON data type: _string_. <br />One of: the registered group types. |
| `remove_types` | Remove one or more BP Group type(s) from a group. To remove more than one BP Group type, use a comma separated list of [BP Group types](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/groups/group-types.md). <br />JSON data type: _string_. <br />One of: the registered group types. |

### Definition

`PUT /buddypress/v2/groups/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups/4', {
	method: 'PUT',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			creator_id: 2,
			status: 'private',
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the updated Groups single item on success.
- An object containg the error code, data and message on failure.

## Delete a specific User group

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Groups single item. **Required**. <br />JSON data type: _integer_. |

### Definition

`DELETE /buddypress/v2/groups/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups/4', {
	method: 'DELETE',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object informing about the `deleted` status and the `previous` Groups single item on success.
- An object containg the error code, data and message on failure.

## List the User Groups of the logged in member

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br /> Default: `view`. <br /> One of: `view, embed, edit`. |
| `max` | The maximum amount of groups the user is member of to return. Defaults to all groups. <br />JSON data type: _integer_. <br /> Default: `0`.  |

### Definition

`GET /buddypress/v2/groups/me`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/groups/me', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An array of objects representing the matching groups single items on success.
- An object containg the error code, data and message on failure.

[^1]: the `enable_forum` is used by the [bbPress](https://wordpress.org/plugins/bbpress/) plugin to inform the corresponding group has a forum associated to it.
[^2]: the `parent_id` field is not used by BuddyPress internally to provide a groups hierarchy feature leaving this part to BuddyPress Add-ons. See changeset [11095](https://buddypress.trac.wordpress.org/changeset/11095).
[^3]: This property is only available if the WordPress discussion settings allow avatars and the Site Administrator allowed group administrators to upload profile photos for groups.
