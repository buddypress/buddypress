# BuddyPress REST API (v2)

> [!IMPORTANT]
> This is the documentation for the BP REST API **v2**. This version was introduced in BuddyPress 15.0.0. The BP REST API v1 is deprecated as of BuddyPress 15.0.0. If you still need to use v1, you can download and activate the archived [BP REST plugin](https://github.com/buddypress/BP-REST) and refer to this [documentation](https://developer.buddypress.org/bp-rest-api/). We strongly advise you to update your code to support **v2** as soon as you can.

Just like the WordPress REST API does it for content data types the BP REST API provides API endpoints for BuddyPress community actions data types that allow developers to interact with sites remotely by sending and receiving [JSON](https://en.wikipedia.org/wiki/JSON) (JavaScript Object Notation) objects. JSON is an open standard data format that is lightweight and human-readable, and looks like Objects do in JavaScript; hence the name.

When you send content to or make a request to the API, the response will be returned in JSON. This enables developers to create, read and update BuddyPress user generated content from client-side JavaScript or from external applications, even those written in languages beyond PHP.

## Why use the BP REST API?

The BP REST API makes it easier than ever to use BuddyPress in new and exciting ways, such as creating Single Page Applications on top of BuddyPress. You could create a Template Pack to provide an entirely new front-end experience for BuddyPress, create brand new & interactive community features, or great BuddyPress blocks for the Block Editor.

The BP REST API can also serve as a strong replacement for the WordPress admin-ajax API. By using the BP REST API, you can more easily structure the way you want to get data into and out of BuddyPress. AJAX calls can be greatly simplified by using the BP REST API, enabling you to improve the performance of your custom features.

## About authentification

**Cookie authentication** is the standard authentication method included with WordPress, the **BP REST API use it**.

### The REST API Nonce

The WordPress REST API includes a technique called [nonces](https://developer.wordpress.org/apis/security/nonces/) to avoid [CSRF](https://en.wikipedia.org/wiki/Cross-site_request_forgery) issues. This prevents other sites from forcing you to perform actions without explicitly intending to do so. This requires slightly special handling for the API.

The API uses nonces with the action set to `wp_rest`. To generate it and pass it to your script you can use the `wp_add_inline_script()` function.

```php
<?php
// Generating the nonce.
function example_enqueue_script() {
	wp_enqueue_script( 'my-script-handle', 'url-to/my-script.js' );

	//Setting your script's data.
	$script_data = array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
	);

	// Pass the nonce to your script.
	wp_add_inline_script(
		'my-script-handle',
		sprintf( 'const %1$s = %2$s;', 'bpRestApi', wp_json_encode( $script_data ) ),
		'before'
	);
}
add_action( 'bp_enqueue_community_scripts', 'example_enqueue_script' );
```

### Sending the nonce

For developers making their own requests, the nonce will need to be passed with each request. The recommended way to send the nonce value is in the request headers. Below is an example using the JavaScript Fetch API.

```javascript
// Set headers.
const requestHeaders = new Headers( {
	'X-WP-Nonce': bpRestApi.nonce,
	'Content-Type': 'application/json',
} );

// Send & handle the request.
fetch( '/wp-json/buddypress/v2/components', {
	method: 'GET',
	headers: requestHeaders,
} ).then( ( response ) => {
	return response.json();
} ).then( ( data ) => {
	 console.log( data );
} );
```

## Next Steps

Familiarize yourself with the [key technical concepts](https://developer.wordpress.org/rest-api/key-concepts/) behind how the REST API functions.

For a comprehensive overview of the resources and routes available by default, review the [API reference](./reference.md).
