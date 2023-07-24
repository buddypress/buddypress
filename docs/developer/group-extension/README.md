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

![Basic group extension](../assets/bp-custom-basic-group-extension.png)

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

### Possible arguments for your configuration array

As we've just seen, the `__construct()` method of your group extension class should be used to pass a set of arguments to the `parent::init( $args )` method. Below are these `$args` possible keys and values:

#### `slug`

**(required)** A unique string identifier for your group extension. Used, among other places, in the construction of URLs.

#### `name`

**(required)** The translatable name of your extension. Used as the default value for navigation items and page titles.

#### `access`

_(optional)_ Which users can visit the group extension’s tab. Possible values: 'anyone', 'loggedin', 'member', 'mod', 'admin' or 'noone'. ('member', 'mod', 'admin' refer to user’s role in the current group.) Defaults to 'anyone' for public groups and 'member' for private groups. Note that the 'mod' level targets only moderators, so if you want to allow access for group moderators and administrators, specify `array( 'mod', 'admin' )`. **This argument was introduced in BuddyPress 2.1.0.**

#### `show_tab`

_(optional)_ Which users can see the group extension’s navigation tab. Possible values: 'anyone', 'loggedin', 'member', 'mod', 'admin' or 'noone'. ('member', 'mod', 'admin' refer to user’s role in the current group.) Defaults to 'anyone' for public groups and 'member' for private groups. Note that the 'mod' level targets only moderators, so if you want to show the tab to group moderators and administrators, specify `array( 'mod', 'admin' )`. **This argument was introduced in BuddyPress 2.1.0.**

#### `show_tab_callback`

_(optional)_ The name of the function to use to define the `show_tab` argument. If you need to check for a specific setting to define whether or not your group extension tab should be shown to members, the static `show_tab` argument won't reply to your need. Use the `show_tab_callback` argument so that your callback function will be called once BuddyPress has set everything about the displayed group. Your callback function needs to return one of the possible values of the `show_tab` one. **This argument was introduced in BuddyPress 12.0.0.**

#### `nav_item_position`

_(optional)_ An integer describing where your extension’s tab should appear. A number between `1` and `100` is recommended. Defaults to `81`.

#### `nav_item_name`

_(optional)_ The string you want to appear in the navigation tab for your group extension. Defaults to the value of the `name` argument, described above.

#### `template_file`

_(optional)_ The template file that BuddyPress will use as a wrapper to display the content of your group extension. Defaults to  `groups/single/plugins.php`. This template is provided by built-in BP Template Packs (nouveau & legacy). If you are building a BuddyPress standalone theme, please make sure to provide this template.

#### `screens`

_(optional)_ A multi-dimensional array of options related to the three secondary “screens” available to group extensions: `create` (the step dedicated to the extension during the group creation process), `edit` (the subtab dedicated to the extension under the Admin tab of the group), and `admin` (the extension’s metabox that appears on the group page when editing via the Groups Administration Dashboard panels). Each of these screens has a set of configuration options, to be described below. Note that all config values are optional, and you only need to override those values where you want to change the default – BuddyPress will parse your `screens` array, using your provided values when available, otherwise falling back on the defaults.
