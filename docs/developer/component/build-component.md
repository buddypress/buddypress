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

			/*
			 * The path from where additional files should be included.
			 * 
			 * FYI: this class is inside an `/inc` subdirectory of your add-on directory.
			 * 
			 * Below is a typical relative path for it:
			 * `/wp-content/plugins/bp-custom/inc/classes/class-bp-custom-component.php
			 */ 
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

### Setting up your component global variables

Your component's class can include a `setup_globals()` method to define BP specific globals or custom ones. If you do add this method to your class, it will override the `BP_Component::setup_globals()`. In this case, to be sure to reference your component inside the BuddyPress `loaded_components` queue and your component's BP specific globals, don't forget to call `parent::setup_globals( $bp_globals );` inside your method.

```php
class BP_Custom_Component extends BP_Component {
	/**
	 * A custom global for your component only.
	 * 
	 * @var boolean
	 */
	public $custom_global = false;

	public function __construct() { /** Your component's constructor code. */ }

	/**
	 * Setup BP Specific globals and custom ones.
	 * 
	 * @param array $bp_globals {
	 *     All values are optional.
	 *     @type string   $slug                  The portion of URL to use for a member's page about your component.
	 *                                           Default: the component's ID.
	 *     @type string   $root_slug             The portion of URL to use for your component's directory page.
	 *     @type boolean  $has_directory         Whether your component is using a directory page or not.
	 *     @type array    $rewrite_ids           Your components rewrite IDs.
	 *     @type string   $directory_title       The title of your component's directory page.
	 *     @type string   $search_string         The placeholder text in the component directory search box.
	 *                                           Eg: 'Search Custom objects...'.
	 *     @type callable $notification_callback The callable function that formats the component's notifications.
	 *     @type array    $global_tables         An array of database table names.
	 *     @type array    $meta_tables           An array of metadata table names.
	 *     @type array    $block_globals         An array of globalized data for your component's Blocks.
	 * }
	 */
	public function setup_globals( $bp_globals = array() ) {
		$bp_globals = array(
			'slug'          => 'custom-slug',
			'has_directory' => false,
		);

		// BP Specigic globals.
		parent::setup_globals( $bp_globals );

		// Your component's globals (if needed).
		$this->custom_global = true;
	}
}
```

5 BP Specific globals are about including a directory page to your component: `$root_slug`, `$has_directory`, `$rewrite_ids`, `$directory_title`, & `$search_string`. To generate and be able to parse requests about your component's directory page, you need to at least set the directory attribute of your component Rewrite IDs and the has_directory global to `true`. For example:

```php
$bp_globals = array(
	'root_slug'     => 'custom-root-slug',
	'has_directory' => true,
	'rewrite_ids'   => array(
		'directory'                    => 'custom_directory',
		'single_item_action'           => 'custom_directory_action',
		'single_item_action_variables' => 'custom_directory_action_variables',
	)
);
```

Rewrite rules about the `$directory`, `$single_item_action` & `$single_item_action_variables` keys will automatically be generated by BuddyPress, if you use other/additional keys, you'll need to handle the corresponding rewrites rules from the `add_rewrite_tags()` & `add_rewrite_rules()` methods of your component.

### Including files

Depending on how you will organize your add-on, you may need to include some other PHP files. The `includes()` method of your component can be used to do so. You'll simply need to call `parent::includes( $files )` where `$files` is the list of the filenames you need to load.

```php
class BP_Custom_AddOn_Component extends BP_Component {
	public function __construct() { /** Your component's constructor code. */ }
	public function setup_globals( $bp_globals = array() ) { /** Your component's code to set custom/BP globals. */ }

	/**
	 * Include your component's required files.
	 *
	 * @param array $files An array of file names located into `$this->path`.
	 *                     NB: `$this->path` in this example is `/wp-content/plugins/bp-custom/inc`
	 */
	public function includes( $files = array() ) {
		parent::includes(
			array(
				'functions.php',
			)
		);
	}
}
```

Even if you can directly include your files without overriding/calling from your component's method `BP_Component::includes( $files )`, as BuddyPress triggers a dynamic hook at the end of this method, it's a good practice to use it!

### Adding your custom menu items to the Member's page navigation

Here's a method with a more concrete result!
