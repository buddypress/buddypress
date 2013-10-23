<?php
/**
 * Component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BP_Component' ) ) :
/**
 * BuddyPress Component Class.
 *
 * The BuddyPress component class is responsible for simplifying the creation
 * of components that share similar behaviors and routines. It is used
 * internally by BuddyPress to create the bundled components, but can be
 * extended to create other really neat things.
 *
 * @package BuddyPress
 * @subpackage Component
 *
 * @since BuddyPress (1.5.0)
 */
class BP_Component {

	/** Variables *************************************************************/

	/**
	 * Translatable name for the component.
	 *
	 * @internal
	 * @var string $name
	 */
	public $name = '';

	/**
	 * Unique ID for the component.
	 *
	 * @var string $id
	 */
	public $id = '';

	/**
	 * Unique slug for the component, for use in query strings and URLs.
	 *
	 * @var string $slug
	 */
	public $slug = '';

	/**
	 * Does the component need a top-level directory?
	 *
	 * @var bool $has_directory
	 */
	public $has_directory = false;

	/**
	 * The path to the component's files.
	 *
	 * @var string $path
	 */
	public $path = '';

	/**
	 * The WP_Query loop for this component.
	 *
	 * @var WP_Query $query
	 */
	public $query = false;

	/**
	 * The current ID of the queried object.
	 *
	 * @var string $current_id
	 */
	public $current_id = '';

	/**
	 * Callback for formatting notifications.
	 *
	 * @var callable $notification_callback
	 */
	public $notification_callback = '';

	/**
	 * WordPress Toolbar links.
	 *
	 * @var array $admin_menu
	 */
	public $admin_menu = '';

	/**
	 * Placeholder text for component directory search box.
	 *
	 * @since BuddyPress (1.5.0)
	 * @var string $search_string
	 */
	public $search_string = '';

	/**
	 * Root slug for the component.
	 *
	 * @since BuddyPress (1.5.0)
	 * @var string $root_slug
	 */
	public $root_slug = '';

	/** Methods ***************************************************************/

	/**
	 * Component loader.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses BP_Component::setup_actions() Set up the hooks and actions.
	 *
	 * @param string $id Unique ID (for internal identification). Letters,
	 *        numbers, and underscores only.
	 * @param string $name Unique name. This should be a translatable name,
	 *        eg __( 'Groups', 'buddypress' ).
	 * @param string $path The file path for the component's files. Used by
	 *        {@link BP_Component::includes()}.
	 * @param array $params Additional parameters used by the component.
	 *        The config array supports the following values:
	 *        - 'adminbar_myaccount_order' Sets the position for our
	 *          component menu under the WP Toolbar's "My Account" menu.
	 */
	public function start( $id = '', $name = '', $path = '', $params = array() ) {

		// Internal identifier of component
		$this->id   = $id;

		// Internal component name
		$this->name = $name;

		// Path for includes
		$this->path = $path;

		// Miscellaneous component parameters that need to be set early on
		if ( ! empty( $params ) ) {
			// Sets the position for our menu under the WP Toolbar's "My Account" menu
			if ( ! empty( $params['adminbar_myaccount_order'] ) ) {
				$this->adminbar_myaccount_order = (int) $params['adminbar_myaccount_order'];
			}

		// Set defaults if not passed
		} else {
			// new component menus are added before the settings menu if not set
			$this->adminbar_myaccount_order = 90;
		}

		// Move on to the next step
		$this->setup_actions();
	}

	/**
	 * Set up component global variables.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_id'.
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_slug'.
	 *
	 * @param array $args {
	 *     All values are optional.
	 *     @type string $slug The component slug. Used to construct certain
	 *           URLs, such as 'friends' in http://example.com/members/joe/friends/
	 *           Default: the value of $this->id.
	 *     @type string $root_slug The component root slug. Note that this
	 *           value is generally unused if the component has a root
	 *           directory (the slug will be overridden by the post_name of
	 *           the directory page). Default: the slug of the directory
	 *           page if one is found, otherwise an empty string.
	 *     @type bool $has_directory Set to true if the component requires
	 *           an associated WordPress page.
	 *     @type callable $notification_callback Optional. The callable
	 *           function that formats the component's notifications.
	 *     @type string $search_term Optional. The placeholder text in the
	 *           component directory search box. Eg, 'Search Groups...'.
	 *     @type array $global_tables Optional. An array of database table
	 *           names.
	 * }
	 */
	public function setup_globals( $args = array() ) {

		/** Slugs *************************************************************/

		// If a WP directory page exists for the component, it should
		// be the default value of 'root_slug'.
		$default_root_slug = isset( buddypress()->pages->{$this->id}->slug ) ? buddypress()->pages->{$this->id}->slug : '';

		$r = wp_parse_args( $args, array(
			'slug'                  => $this->id,
			'root_slug'             => $default_root_slug,
			'has_directory'         => false,
			'notification_callback' => '',
			'search_string'         => '',
			'global_tables'         => ''
		) );

		// Slug used for permalink URI chunk after root
		$this->slug                  = apply_filters( 'bp_' . $this->id . '_slug',                  $r['slug']                  );

		// Slug used for root directory
		$this->root_slug             = apply_filters( 'bp_' . $this->id . '_root_slug',             $r['root_slug']             );

		// Does this component have a top-level directory?
		$this->has_directory         = apply_filters( 'bp_' . $this->id . '_has_directory',         $r['has_directory']         );

		// Search string
		$this->search_string         = apply_filters( 'bp_' . $this->id . '_search_string',         $r['search_string']         );

		// Notifications callback
		$this->notification_callback = apply_filters( 'bp_' . $this->id . '_notification_callback', $r['notification_callback'] );

		// Set up global table names
		if ( !empty( $r['global_tables'] ) ) {

			// This filter allows for component-specific filtering of table names
			// To filter *all* tables, use the 'bp_core_get_table_prefix' filter instead
			$r['global_tables'] = apply_filters( 'bp_' . $this->id . '_global_tables', $r['global_tables'] );

			foreach ( $r['global_tables'] as $global_name => $table_name ) {
				$this->$global_name = $table_name;
			}
		}

		/** BuddyPress ********************************************************/

		// Register this component in the loaded components array
		buddypress()->loaded_components[$this->slug] = $this->id;

		// Call action
		do_action( 'bp_' . $this->id . '_setup_globals' );
	}

	/**
	 * Include required files.
	 *
	 * Please note that, by default, this method is fired on the bp_include
	 * hook, with priority 8. This is necessary so that core components are
	 * loaded in time to be available to third-party plugins. However, this
	 * load order means that third-party plugins whose main files are
	 * loaded at bp_include with priority 10 (as recommended), will not be
	 * loaded in time for their includes() method to fire automatically.
	 *
	 * For this reason, it is recommended that your plugin has its own
	 * method or function for requiring necessary files. If you must use
	 * this method, you will have to call it manually in your constructor
	 * class, ie
	 *   $this->includes();
	 *
	 * Note that when you pass an array value like 'actions' to includes,
	 * it looks for the following three files (assuming your component is
	 * called 'my_component'):
	 *   - ./actions
	 *   - ./bp-my_component/actions
	 *   - ./bp-my_component/bp-my_component-actions.php
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}includes'.
	 *
	 * @param array $includes An array of file names, or file name chunks,
	 *        to be parsed and then included.
	 */
	public function includes( $includes = array() ) {

		// Bail if no files to include
		if ( empty( $includes ) )
			return;

		$slashed_path = trailingslashit( $this->path );

		// Loop through files to be included
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension
				'bp-' . $this->id . '/bp-' . $this->id . '-' . $file  . '.php',
				'bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '/' . $file . '.php',

				// Passed with extension
				$file,
				'bp-' . $this->id . '-' . $file,
				'bp-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) {
					require( $slashed_path . $path );
					break;
				}
			}
		}

		// Call action
		do_action( 'bp_' . $this->id . '_includes' );
	}

	/**
	 * Set up the actions.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses add_action() To add various actions.
	 * @uses do_action() Calls 'bp_{@link BP_Component::name}setup_actions'.
	 */
	public function setup_actions() {

		// Setup globals
		add_action( 'bp_setup_globals',          array( $this, 'setup_globals'          ), 10 );

		// Include required files. Called early to ensure that BP core
		// components are loaded before plugins that hook their loader functions
		// to bp_include with the default priority of 10. This is for backwards
		// compatibility; henceforth, plugins should register themselves by
		// extending this base class.
		add_action( 'bp_include',                array( $this, 'includes'               ), 8 );

		// Setup navigation
		add_action( 'bp_setup_nav',              array( $this, 'setup_nav'              ), 10 );

		// Setup WP Toolbar menus
		add_action( 'bp_setup_admin_bar',        array( $this, 'setup_admin_bar'        ), $this->adminbar_myaccount_order );

		// Setup component title
		add_action( 'bp_setup_title',            array( $this, 'setup_title'            ), 10 );

		// Register post types
		add_action( 'bp_register_post_types',    array( $this, 'register_post_types'    ), 10 );

		// Register taxonomies
		add_action( 'bp_register_taxonomies',    array( $this, 'register_taxonomies'    ), 10 );

		// Add the rewrite tags
		add_action( 'bp_add_rewrite_tags',       array( $this, 'add_rewrite_tags'       ), 10 );

		// Add the rewrite rules
		add_action( 'bp_add_rewrite_rules',      array( $this, 'add_rewrite_rules'      ), 10 );

		// Add the permalink structure
		add_action( 'bp_add_permastructs',       array( $this, 'add_permastructs'       ), 10 );

		// Allow components to parse the main query
		add_action( 'bp_parse_query',            array( $this, 'parse_query'            ), 10 );

		// Generate rewrite rules
		add_action( 'bp_generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ), 10 );

		// Additional actions can be attached here
		do_action( 'bp_' . $this->id . '_setup_actions' );
	}

	/**
	 * Set up component navigation.
	 *
	 * @see bp_core_new_nav_item() For a description of the $main_nav
	 *      parameter formatting.
	 * @see bp_core_new_subnav_item() For a description of how each item
	 *      in the $sub_nav parameter array should be formatted.
	 *
	 * @param array $main_nav Optional. Passed directly to
	 *        bp_core_new_nav_item(). See that function for a description.
	 * @param array $sub_nav Optional. Multidimensional array, each item in
	 *        which is passed to bp_core_new_subnav_item(). See that
	 *        function for a description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// No sub nav items without a main nav item
		if ( !empty( $main_nav ) ) {
			bp_core_new_nav_item( $main_nav );

			// Sub nav items are not required
			if ( !empty( $sub_nav ) ) {
				foreach( (array) $sub_nav as $nav ) {
					bp_core_new_subnav_item( $nav );
				}
			}
		}

		// Call action
		do_action( 'bp_' . $this->id . '_setup_nav' );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @see WP_Admin_Bar::add_menu() for a description of the syntax
	 *      required by each item in the $wp_admin_nav parameter array.
	 * @global obj $wp_admin_bar
	 *
	 * @param array $wp_admin_nav An array of nav item arguments. Each item
	 *        in this parameter array is passed to {@link WP_Admin_Bar::add_menu()}.
	 *        See that method for a description of the required syntax for
	 *        each item.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Bail if this is an ajax request
		if ( defined( 'DOING_AJAX' ) )
			return;

		// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false
		if ( !bp_use_wp_admin_bar() )
			return;

		// Filter the passed admin nav
		$wp_admin_nav = apply_filters( 'bp_' . $this->id . '_admin_nav', $wp_admin_nav );

		// Do we have Toolbar menus to add?
		if ( !empty( $wp_admin_nav ) ) {

			// Set this objects menus
			$this->admin_menu = $wp_admin_nav;

			// Define the WordPress global
			global $wp_admin_bar;

			// Add each admin menu
			foreach( $this->admin_menu as $admin_menu ) {
				$wp_admin_bar->add_menu( $admin_menu );
			}
		}

		// Call action
		do_action( 'bp_' . $this->id . '_setup_admin_bar' );
	}

	/**
	 * Set up the component title.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}setup_title'.
	 */
	public function setup_title() {
		do_action(  'bp_' . $this->id . '_setup_title' );
	}

	/**
	 * Set up the component post types.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_post_types'.
	 */
	public function register_post_types() {
		do_action( 'bp_' . $this->id . '_register_post_types' );
	}

	/**
	 * Register component-specific taxonomies.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_taxonomies'.
	 */
	public function register_taxonomies() {
		do_action( 'bp_' . $this->id . '_register_taxonomies' );
	}

	/**
	 * Add any additional rewrite tags.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_add_rewrite_tags'.
	 */
	public function add_rewrite_tags() {
		do_action( 'bp_' . $this->id . '_add_rewrite_tags' );
	}

	/**
	 * Add any additional rewrite rules.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_add_rewrite_rules'.
	 */
	public function add_rewrite_rules() {
		do_action( 'bp_' . $this->id . '_add_rewrite_rules' );
	}

	/**
	 * Add any permalink structures
	 *
	 * @since BuddyPress (1.9)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_add_permastruct'
	 */
	public function add_permastructs() {
		do_action( 'bp_' . $this->id . '_add_permastructs' );
	}

	/**
	 * Allow components to parse the main query
	 *
	 * @since BuddyPress (1.9)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_parse_query'
	 * @param object The main WP_Query
	 */
	public function parse_query( $query ) {
		do_action_ref_array( 'bp_' . $this->id . '_parse_query', array( &$query ) );
	}

	/**
	 * Generate any additional rewrite rules
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_generate_rewrite_rules'
	 */
	public function generate_rewrite_rules() {
		do_action( 'bp_' . $this->id . '_generate_rewrite_rules' );
	}
}
endif; // BP_Component
