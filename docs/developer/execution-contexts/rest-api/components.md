# Components REST API routes

BuddyPress chose a modular approach using components to organize its features. Two components are loaded by default (eg: BuddyPress Core and Community Members) while the majority are optionals. BuddyPress comes with 8 built-in optional components (Account Settings, Activity Streams, Extended Profiles, Friend connections, Notifications, Private messaging, User groups and Site Tracking).

> [!IMPORTANT]
> Note: It’s important to note there can be more optional components regarding the BuddyPress plugins installed on the website : these plugins can use the BP Component API to incorpore the lists of active or inactive BuddyPress components.

## Schema

The schema defines all the fields that exist for BuddyPress components.

| Property | Description |
| --- | --- |
| `name` | Key name of the component.  <br />JSON data type: _string_. <br />Context: `view`, `edit`. |
| `is_active` | Whether the component is active or not.  <br />JSON data type: _boolean_. <br />Default: `false`. <br />Context: `view`, `edit`. |
| `status` | Whether the component is active or inactive. <br />JSON data type: _string_. <br />Context: `view`, `edit`.  <br />One of: `active`, `inactive`. |
| `title` | Title of the component. <br />JSON data type: _string_. <br />Context: `view`, `edit`. |
| `description` | Description of the component. <br />JSON data type: _string_. <br />Context: `view`, `edit`. |
| `features` | Information about active features for the component. <br />JSON data type: _object_ \| _null_. <br />Default: `null`. <br />Context: `view`, `edit`. |

## List the BuddyPress components

### Arguments

| Name | Description |
| --- | --- |
| `context` | Scope under which the request is made; determines fields present in response.  <br />JSON data type: _string_. <br />Default: `view`. <br />One of: `view`, `edit`. |
| `page` | Current page of the collection.  <br />JSON data type: _integer_. <br />Default: `1`. |
| `per_page` | Maximum number of components to be returned in result set. <br />JSON data type: _integer_. <br />Default: `10`. |
| `search` | Limit results to those matching a string. <br />JSON data type: _string_. |
| `status` | Limit result set to components with a specific status. <br />JSON data type: _string_. <br />Default: `all`. <br />One of: `all, active, inactive`. |
| `type` | Information about active features for the component. <br />JSON data type: _string_. <br />Default: `all`. <br />One of: `all, optional, retired, required`. |

### Definition

`GET /buddypress/v2/components`

### Example of use

> [!WARNING]
> The `requestHeaders` object needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).


```javascript
fetch( '/wp-json/buddypress/v2/components?context=edit&status=active', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.table( data );
} );
```

### JSON Response

- An array of objects representing the matching components on success.
- An object containg the error code, data and message on failure.

## Activate or Deactivate a BuddyPress component

### Arguments

| Name | Description |
| --- | --- |
| `name` | Key name of the component. **Required**. <br />JSON data type: _string_. |
| `action` | Whether to activate or deactivate the component. **Required**. <br />JSON data type: _string_. <br />One of: `activate`, `deactivate`. |

### Definition

`PUT /buddypress/v2/components`

### Example of use

> [!WARNING]
> The `requestHeaders` needs to be set according to the WordPress REST API nonce. Read more about the [REST API authentification](./README.md#about-authentification).

```javascript
fetch( '/wp-json/buddypress/v2/components', {
	method: 'PUT',
	headers: requestHeaders,
	body: JSON.stringify( { name: 'groups', action: 'activate' } ),
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

### JSON Response

- An object representing the updated component on success.
- An object containg the error code, data and message on failure.
