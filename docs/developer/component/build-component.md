# Building a custom BuddyPress component

Located inside the `/bp-core/classes` directory, the `BP_Component` class can be used as the parent of your custom component's class to organize it. BuddyPress components are registered into the main `buddypress()` instance using their ID: a unique string which is generally the same as your component default slug. This registration step is hooked to the `bp_setup_components` action. Below is an example of how you can register your component in BuddyPress.

```php
function register_custom_component() {
	/*
	 * BP_Custom_Component is the class of your component.
	 * You'll discover in the rest of this documentation resource how you
	 * can build this class.
	 */ 
	buddypress()->custom = new BP_Custom_Component();
}
add_action( 'bp_setup_components', 'register_custom_component' );
```

You can use the third argument (`$priority` the priority argument) of the `add_action()` function to choose when your component will be inited as long as it is after **1** to be sure required components have been inited, in the above example `custom` is hooked at default priority ie: **10**. Here's the initiation order of built-in BuddyPress components:

- Core (required) is hooked to `bp_setup_components` at priority **0**.
- Members (required) is hooked to `bp_setup_components` at priority **1**.
- Extended Profiles (optional) are hooked to `bp_setup_components` at priority **2**.
- Activity, Blogs, Friends, Groups, Messages, Notifications, Settings (optionals) are hooked to `bp_setup_components` at priority **6**.

## Building your component’s class

It’s a [WordPress good practice](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions) to put the code of your class into a file having a `class-` prefix followed by your class name where caps are replaced by their corresponding lower case value and underscores by dashes. As our custom component's class is named `BP_Custom_Component`, we are putting its code inside a `class-bp-custom-component.php` file. Let's built it making sure you will inherit from the `BP_Component` parent class using the `extends` keyword.

```php
class BP_Custom_Component extends BP_Component {
	/*
	 * The properties and methods of for your component's class.
	 */ 
}
```

### Adding the constructor of your class

This is where you are using the `BP_Component::start()` method to inform about some of your component's globals such as your component ID, your component name, the path from where additional files should be included and additional parameters such as the position for your menu under the WP Toolbar's "My Account menu", your component optional features and the string to be used as the query argument in component search URLs.

```php
class BP_Custom_Component extends BP_Component {
	/**
	 * Your component's constructor.
	 */
	public function __construct() {
		parent::start(
			// Your component ID.
			'custom',

			// Your component Name.
			__( 'Custom component', 'custom-text-domain' ),

			// The path from where additional files should be included.
			plugin_dir_path( dirname( __FILE__ ) ),

			// Additional parameters.
			array(
				'adminbar_myaccount_order' => 100,
				'features'                 => array( 'feature-one', 'feature-two' ),
				'search_query_arg'         => 'custom-component-search',
			)
		);
	}
}
```
