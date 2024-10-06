# Members REST API routes

The BuddyPress Members REST controller extends the WordPress Users one to include specific BuddyPress data such as profile fields data[^1] and use the `BP_User_Query` instead of the `WP_User_Query` to fetch the members.

## Schema

The schema defines all the fields that exist for a member object.

| Property | Description |
| --- | --- |
| `id` | Unique identifier for the member. <br />JSON data type: _integer_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `name`  | Display name for the member. <br />JSON data type: _string_. <br />Context: `embed`, `view`, `edit`. |
| `mention_name` | The name used for that user in @-mentions. <br />JSON data type: _string_. <br />Context: `embed`, `view`, `edit`. |
| `link` | Profile URL of the member. <br />JSON data type: _string_, format: _URI_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `user_login` | An alphanumeric identifier for the member. <br />JSON data type: _string_. <br />Context: `embed`, `view`, `edit`. |
| `member_types` | Member types associated with the member. See this [documentation page](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/users/member-types.md) for more information. <br />JSON data type: _array_. <br />Read only. <br />Context: `embed`, `view`, `edit`. |
| `registered_date` | Registration date for the member. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `edit`. |
| `registered_date_gmt` | The date the member was registered, as GMT. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `edit`. |
| `registered_since` | Elapsed time since the member registered. <br />JSON data type: _string_. <br />Read only. <br />Context: `view`, `edit`. |
| `password` | Password for the member (never included). <br />JSON data type: _string_. <br />Context: none. |
| `roles` | Roles assigned to the member. <br />JSON data type: _array_. <br />Context: `edit`. |
| `capabilities` | All capabilities assigned to the member. <br />JSON data type: _object_. <br />Read only. <br />Context: `edit`. |
| `capabilities` | Any extra capabilities assigned to the user. <br />JSON data type: _object_. <br />Read only. <br />Context: `edit`. |
| `xprofile`[^2] | Member xProfile groups and its fields. <br />JSON data type: _array_. <br />Read only. <br />Context: `view`, `edit`. |
| `friendship_status`[^3] | Whether the logged in user has a friendship relationship with the fetched user. <br />JSON data type: _boolean_. <br />Read only. <br />Context: `view`, `edit`. |
| `friendship_status_slug`[^3] | Slug of the friendship relationship status the logged in user has with the fetched user. <br />JSON data type: _string_. <br />Read only. <br />One of: `is_friend`, `not_friends`, `pending`, `awaiting_response`. <br />Context: `view`, `edit`. |
| `total_friend_count`[^3] | Total number of friends for the member. <br />JSON data type: _integer_. <br />Read only. <br />Context: `view`, `edit`. |
| `last_activity` | Last date the member was active on the site. <br />JSON data type: _object_ (properties: `timediff`, `date` and `date_gmt`). <br />Read only. <br />Context: `view`, `edit`. |
| `latest_update`[^4] | The content of the latest activity posted by the member. <br />JSON data type: _object_ (properties: `id`, `raw` and `rendered`). <br />Read only. <br />Context: `view`, `edit`. |
| `avatar_urls`[^5] | Avatar URLs for the member (Full & Thumb sizes). <br />JSON data type: _object_ (properties: `full`, and `thumb`). <br />Read only. <br />Context: `embed`, `view`, `edit`. |

## List Members

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br/>Default: `view`. <br/>One of: `view`, `embed`, `edit`. |
| `page` | Current page of the collection. <br />JSON data type: _integer_. <br />Default: `1`. |
| `per_page` | Maximum number of members to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. |
| `search` | Limit results to those matching a string. <br />JSON data type: _string_. |
| `exclude` | Ensure result set excludes specific IDs. <br />JSON data type: _array_. <br />Default: `[]`. |
| `include` | Ensure result set includes specific IDs. <br />JSON data type: _array_. <br />Default: `[]`. |
| `type` | Shorthand for certain orderby/order combinations. <br />JSON data type: _string_. <br />Default: `newest`. <br/>One of: `active`, `newest`, `alphabetical`, `random`, `online`, `popular`. |
| `user_id` | Limit results to friends of a user. <br />JSON data type: _integer_. <br />Default: `0`. |
| `user_ids` | Pass IDs of users to limit result set. <br />JSON data type: _array_. <br />Default: `[]`. |
| `populate_extras` | Whether to fetch extra BP data about the returned members. <br />JSON data type: _boolean_. <br />Default: `false`. |
| `member_type` | Limit results set to certain type(s). See this [documentation page](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/users/member-types.md) for more information.<br />JSON data type: _array_. <br />Default: `[]`. |
| `xprofile` | Limit results set to a certain xProfile field. <br />JSON data type: _array_. <br />Default: `[]`. |

### Definition

`GET /buddypress/v2/members`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members?context=view&type=active', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.table( data );
} );
```

### JSON Response

- An array of objects representing the matching members on success.
- An object containg the error code, data and message on failure.

## Create a member

Only users having the `create_users` WordPress capability can create a new member.

### Arguments

| Name | Description |
| --- | --- |
| `user_login` | An alphanumeric identifier for the member. **Required**. <br />JSON data type: _string_. |
| `password` | Password for the member. **Required**. <br />JSON data type: _string_. |
| `email` | The email address for the member. **Required**. <br />JSON data type: _string_. |
| `name` | Display name for the member. <br />JSON data type: _string_. |
| `roles` | Roles assigned to the member. <br />JSON data type: _array_. |
| `member_type` | A comma separated list of Member Types to set for the member. See this [documentation page](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/users/member-types.md) for more information. <br />JSON data type: _string_. |

### Definition

`POST /buddypress/v2/members`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members', {
	method: 'POST',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			'user_login': 'bapuu',
			password: 'neverUseWe@kPassW0rd!',
			email: 'bapuu@buddypress.org',
			name: 'Bapuu',
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the created member on success.
- An object containg the error code, data and message on failure.

## Retrieve a specific member

### Arguments

| Name | Description |
| --- | --- |
| `id` | Unique identifier for the member. **Required**. <br />JSON data type: _integer_. |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br /> Default: `view`. <br /> One of: `view, embed, edit`. |
| `populate_extras` | Whether to fetch extra BP data about the returned member. <br />JSON data type: _boolean_.<br /> Default: `false`. |

### Definition

`GET /buddypress/v2/members/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/2?populate_extras=true', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the member on success.
- An object containg the error code, data and message on failure.

## Update a specific member

### Arguments

| Name | Description |
| --- | --- |
| `id` | Unique identifier for the user. **Required**. <br />JSON data type: _integer_. |
| `name` | Display name for the member. <br />JSON data type: _string_. |
| `roles` | Roles assigned to the member. <br />JSON data type: _array_. |
| `member_type` | A comma separated list of Member Types to set for the member. See this [documentation page](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/users/member-types.md) for more information. <br />JSON data type: _string_. |

### Definition

`PUT /buddypress/v2/members/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/2', {
	method: 'PUT',
	headers: requestHeaders,
	body: JSON.stringify( { name: 'Bapuu The BP Wapuu' } ),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the updated member on success.
- An object containg the error code, data and message on failure.

## Delete a specific member

### Arguments

| Name | Description |
| --- | --- |
| `id` | Unique identifier for the user. **Required**. <br />JSON data type: _integer_. |
| `force` | Required to be true, as members do not support trashing. <br />JSON data type: _boolean_. <br />Default: `false`. |
| `reassign` | Reassign the deleted member’s posts and links to this user ID. **Required**. <br />JSON data type: _integer_. |

### Definition

`DELETE /buddypress/v2/members/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/2', {
	method: 'DELETE',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			force: true,
			reassign: 1,
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object informing about the `deleted` status and the `previous` member on success.
- An object containg the error code, data and message on failure.

## Retrieve the logged in member

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br /> Default: `view`. <br /> One of: `view, embed, edit`. |
| `populate_extras` | Whether to fetch extra BP data about the returned member. <br />JSON data type: _boolean_.<br /> Default: `false`. |

### Definition

`GET /buddypress/v2/members/me`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/me', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the member on success.
- An object containg the error code, data and message on failure.

## Update the logged in member

To update roles, the logged in member must have the_ `promote_user` capability.

### Arguments

| Name | Description |
| --- | --- |
| `name` | Display name for the member. <br />JSON data type: _string_. |
| `roles` | Roles assigned to the member. <br />JSON data type: _array_. |
| `member_type` | A comma separated list of Member Types to set for the member. See this [documentation page](https://github.com/buddypress/buddypress/blob/master/docs/user/administration/users/member-types.md) for more information. <br />JSON data type: _string_. |

### Definition

`PUT /buddypress/v2/members/me`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/me', {
	method: 'PUT',
	headers: requestHeaders,
	body: JSON.stringify( { name: 'Admin Istrator' } ),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the updated member on success.
- An object containg the error code, data and message on failure.

## Delete the logged in member

### Arguments

| Name | Description |
| --- | --- |
| `force` | Required to be true, as members do not support trashing. <br />JSON data type: _boolean_. <br />Default: `false`. |
| `reassign` | Reassign the deleted member’s posts and links to this user ID. **Required**. <br />JSON data type: _integer_. |

### Definition

`DELETE /buddypress/v2/members/me`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/members/me', {
	method: 'DELETE',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			force: true,
			reassign: 1,
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object informing about the `deleted` status and the `previous` member on success.
- An object containg the error code, data and message on failure.

[^1]: the eXtended Profiles component needs to be active on the website to make these profile fields available into Members REST requests.
[^2]: data is only fetched if the eXtended Profiles component is active.
[^3]: data is only fetched if the Friends component is active.
[^4]: data is only fetched if the Activity component is active.
[^5]: This property is only available if the WordPress discussion settings allow avatars.
