# Group Extension API

The group extension API makes it very easy to add custom creation steps, edit screens, navigation items and pages to a group. It essentially allows you to create fully functional extensions to BuddyPress created groups.

The Group Extension API consists of a base class called `BP_Group_Extension`, which you extend in your own add-on. `BP_Group_Extension` does most of the work necessary to integrate your content into BP group – creating new navigation tabs, registering a step during group creation, etc.

**NB**: the group extension requires the Groups component to be active. Please make sure to wrap your extended class in a `if ( bp_is_active( 'groups' ) ) :` check.

Located inside the `/bp-groups/classes` directory, the `BP_Group_Extension` class will help you to organize your custom group extension. BuddyPress group extensions are registered into the `$group_extensions` property of the `buddypress()->groups` global using the name of your custom class. This registration step needs to be hooked to the `bp_init` action at a priority lower than `11` (you can omit the hook `$priority` argument to use the default one - `10`) and is done passing the name of your class to the `bp_register_group_extension()` function. Below is an example of how you can register your group extension in BuddyPress.

```php
/**
 * Registers the custom group extension.
 */
function bp_custom_add_on_register_group_extension() {
	bp_register_group_extension( 'BP_Custom_AddOn_Group_Extension' );
}
add_action( 'bp_init', 'bp_custom_add_on_register_group_extension' );
```

## Building your group extension’s class

It’s a [WordPress good practice](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions) to put the code of your class into a file having a `class-` prefix followed by your class name where caps are replaced by their corresponding lower case value and underscores by dashes. As our custom group extension's class is named `BP_Custom_Group_Extension`, we are putting its code inside a `class-bp-custom-group-extension.php` file. Let's built it making sure you will inherit from the `BP_Component` parent class using the `extends` keyword.

```php
class BP_Custom_Group_Extension extends BP_Group_Extension {
	/*
	 * The properties and methods of for your group extension's class.
	 */ 
}
```

### Adding the constructor of your class

This is where you are using the `BP_Group_Extension::init()` method to configure your custom group extension. This method expects an associative array where specific keys will be used to define the name & URL slug for your group extension tab within groups, who can access to it, and init additional screens such as the one BuddyPress can load during the group's creation process, or the one to let group Administrators customize some settings about your group extension. Let's build a very basic tab that will be displayed on each single groups.

screenshot

To get this result, here's how you need to define the arguments of the parameter you will send to the `parent::init()`  method at the end of your constructor.

```php
if ( bp_is_active( 'groups' ) ) {
	/**
	 * BP Custom group extension Class.
	 */
	class BP_Custom_AddOn_Group_Extension extends BP_Group_Extension {
		/**
		 * Your group extension's constructor.
		 */
		public function __construct() {
			$args = array(
				'slug'              => 'custom-group-extension',
				'name'              => __( 'Custom group extension', 'custom-text-domain' ),
				'nav_item_position' => 105,
				'access'            => 'anyone',
				'show_tab'          => 'anyone',
			);

			parent::init( $args );
		}

		/**
		 * Outputs the content of your group extension tab.
		 *
		 * @param int|null $group_id ID of the displayed group.
		 */
		public function display( $group_id = null ) {
			printf( '<p>%1$s %2$s</p>', esc_html__( 'It works! The displayed group ID is', 'custom-text-domain' ), $group_id );
		}
	}
}
```

The above code early introduced the `display()`  method of your group extension's class so that something is actually displayed into groups! Before looking to it more in details, let's first list all possible keys and values for the `$args` configuration array.
