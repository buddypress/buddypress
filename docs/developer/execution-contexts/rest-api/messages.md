# Private Messaging REST API routes

The Private Messaging component allow your users to talk to each other directly and in private. It’s not just limited to one-on-one discussions, private conversations can involve any number of members.

> [!IMPORTANT]
> The Private Messaging component is an optional one. This means the following endpoints will only be available if the component is active on the community site.

## Schema

The schema defines all the fields that exist for a Thread object.

| Property | Description |
| --- | --- |
| `id` | A unique numeric ID for the Thread. <br />JSON data type: _integer_. <br />Read only. <br />Context: `view`, `edit`. |
| `message_id` | The ID of the latest message of the Thread. <br />JSON data type: _integer_. <br />Read only. <br />Context: `view`, `edit`. |
| `last_sender_id` | The ID of latest sender of the Thread. <br />JSON data type: _integer_. <br />Read only. <br />Context: `view`, `edit`. |
| `subject` | Title of the latest message of the Thread. <br />JSON data type: _object_ (properties: `raw`, `rendered` ). <br />Context: `view`, `edit`. |
| `excerpt` | Summary of the latest message of the Thread. <br />JSON data type: _object_ (properties: `raw`, `rendered` ). <br />Read only. <br />Context: `view`, `edit`. |
| `message` | Content of the latest message of the Thread. <br />JSON data type: _object_ (properties: `raw`, `rendered` ). <br />**Required**. <br />Context: `view`, `edit`. |
| `date` | Date of the latest message of the Thread, in the site's timezone. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context: `view`, `edit`. |
| `date_gmt` | Date of the latest message of the Thread, as GMT. <br />JSON data type: _string_ \| _null_, format: _date-time_. <br />Read only. <br />Context:  `view`, `edit`. |
| `unread_count` | Total count of unread messages into the Thread for the requested user. <br />JSON data type: _integer_. <br />Read only. <br />Context: `view`, `edit`. |
| `sender_ids` | The list of user IDs for all messages in the Thread. <br />JSON data type: _array_. <br />Read only. <br />Context: `view`, `edit`. |
| `recipients` | The list of Avatar[^1] URL objects (full, thumb) for the recipient involved into the Thread. <br />JSON data type: _array_. <br />Context: `view`, `edit`. |
| `messages` | List of message objects for the thread. <br />JSON data type: _array_. <br />Read only. <br />Context: `view`, `edit`. |
| `starred_message_ids` | List of starred message IDs. <br />JSON data type: _array_. <br />Read only. <br />Context: `view`, `edit`. |

## List Activities

Only logged in users will be able to fetch the threads they are involved in. Administrators can also fetch another user's threads.

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response. <br />JSON data type: _string_. <br/>Default: `view`. <br/>One of: `view`, `edit`. |
| `page` | Current page of the threads collection. <br />JSON data type: _integer_. <br />Default: `1`. |
| `per_page` | Maximum number of threads to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. <br />Min.: `1`, Max.: `100`. |
| `messages_page` | Current page of the messages collection. <br />JSON data type: _integer_. <br />Default: `1`.  <br />Min.: `1`. |
| `messages_per_page` | Maximum number of messages to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. <br />Min.: `1`, Max.: `100`. |
| `recipients_page` | Current page of the recipients collection. <br />JSON data type: _integer_. <br />Default: `1`.  <br />Min.: `1`. |
| `recipients_per_page` | Maximum number of recipients to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. <br />Min.: `1`, Max.: `100`. |
| `search` | Limit results to those matching a string. <br />JSON data type: _string_. |
| `type` | Filter the result by thread status. <br />JSON data type: _string_. <br />One of: `read`, `unread`, `all`. <br />Default: `all`. |
| `user_id` | Limit result set to activity items created by a specific user (ID). <br />JSON data type: _integer_. <br />Default: `0`. |
| `box` | Filter the result by box. <br />JSON data type: _string_. <br />One of: `sentbox`, `inbox`, `starred`. <br />Default: `inbox`. |

### Definition

`GET /buddypress/v2/messages`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages?context=view&box=sentbox', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.table( data );
} );
```

### JSON Response

- An array of objects representing the matching thread items on success.
- An object containg the error code, data and message on failure.

## Start a new Messages Thread or reply to an existing one

Only logged in users will be able to init a new thread or reply to an existing one.

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Thread. Required when replying to an existing Thread. <br />JSON data type: _integer_. <br />Default: `0`. |
| `message` | Content of the Message to add to the Thread. <br />JSON data type: _string_. <br />**Required**. |
| `recipients` | The list of the recipients user IDs of the Message. <br />JSON data type: _array_. <br />**Required**. |
| `sender_id` | The user ID of the Message sender. <br />JSON data type: _integer_. <br />Default: the ID of the logged in member. |
| `subject` | Subject of the Message initializing the Thread. <br />JSON data type: _string_. <br />Default: `false`. |

### Definition

`POST /buddypress/v2/messages`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages', {
	method: 'POST',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			message: 'bapuu is the BuddyPress wapuu',
			recipients: [ 2, 3 ],
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the created thread item or the thread reply on success.
- An object containg the error code, data and message on failure.

## Retrieve a specific Messages Thread

Only logged in users will be able to fetch a thread they are involved in. Administrators can also fetch another user's thread.

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Thread. **Required**. <br />JSON data type: _integer_. |
| `messages_page` | Current page of the messages collection. <br />JSON data type: _integer_. <br />Default: `1`.  <br />Min.: `1`. |
| `messages_per_page` | Maximum number of messages to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. <br />Min.: `1`, Max.: `100`. |
| `recipients_page` | Current page of the recipients collection. <br />JSON data type: _integer_. <br />Default: `1`.  <br />Min.: `1`. |
| `recipients_per_page` | Maximum number of recipients to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. <br />Min.: `1`, Max.: `100`. |
| `user_id` | The user ID to get the thread for. <br />JSON data type: _integer_. |
| `order` | Order sort attribute ascending or descending. <br />JSON data type: _string_. <br />One of: `asc`, `desc`. <br />Default: `asc`. |

### Definition

`GET /buddypress/v2/messages/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages/2', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the requested thread.
- An object containg the error code, data and message on failure.

## Update metadata about a specific message of the Thread

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the Thread. **Required**. <br />JSON data type: _integer_. |
| `message_id` | By default the latest message of the thread will be updated. Specify this message ID to edit another message of the thread. <br />JSON data type: _integer_. |
| `read` | Whether to mark the thread as read. <br />JSON data type: _boolean_. <br /> Default: `false` |
| `unread` | Whether to mark the thread as unread. <br />JSON data type: _boolean_. <br /> Default: `false` |
| `user_id` | The user ID to get the thread for. <br />JSON data type: _integer_. |

### Definition

`PUT /buddypress/v2/messages/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages/2', {
	method: 'PUT',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			user_id: 2,
			read: true,
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the updated thread on success.
- An object containg the error code, data and message on failure.

## Delete a specific Messages Thread for a user

> [!NOTE]
> When users request to delete a Messages Thread, they are removed from the list of recipients. As soon as the recipients list is empty, the Messages Thread is fully deleted from the database.

### Arguments

| Name | Description |
| --- | --- |
| `id` | A unique numeric ID for the activity item. **Required**. <br />JSON data type: _integer_. |
| `user_id` | The user ID to get the thread for. <br />JSON data type: _integer_. |

### Definition

`DELETE /buddypress/v2/messages/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages/2', {
	method: 'DELETE',
	headers: requestHeaders,
	body: JSON.stringify(
		{
			user_id: 2,
		}
	),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object informing about the `deleted` status and the `previous` thread item on success.
- An object containg the error code, data and message on failure.

## Star or unstar a specific message of a Thread

Logged in users can star/unstar a specific message of a Thread`.

### Arguments

| Name | Description |
| --- | --- |
| `id` | The ID of the a specific message of the Thread. **Required**. <br />JSON data type: _integer_. |

> [!IMPORTANT]
> The ID here is not a Messages Thread ID. It’s the ID of a specific message of the Messages Thread.

### Definition

`PUT /buddypress/v2/messages/starred/<id>`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/messages/starred/2', {
	method: 'POST',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the thread item on success.
- An object containg the error code, data and message on failure.

[^1]: This property is only available if the WordPress discussion settings allow avatars.
