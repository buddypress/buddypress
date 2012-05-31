<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BP_Component' ) ) :
/**
 * BuddyPress Component Class
 *
 * The BuddyPress component class is responsible for simplifying the creation
 * of components that share similar behaviors and routines. It is used
 * internally by BuddyPress to create the bundled components, but can be
 * extended to create other really neat things.
 *
 * @package BuddyPress
 * @subpackage Component
 *
 * @since BuddyPress (1.5)
 */
class BP_Component {

	/**
	 * @var string Unique name (for internal identification)
	 * @internal
	 */
	var $name;

	/**
	 * @var Unique ID (normally for custom post type)
	 */
	var $id;

	/**
	 * @var string Unique slug (used in query string and permalinks)
	 */
	var $slug;

	/**
	 * @var bool Does this component need a top-level directory?
	 */
	var $has_directory;

	/**
	 * @var string The path to the component's files
	 */
	var $path;

	/**
	 * @var WP_Query The loop for this component
	 */
	var $query;

	/**
	 * @var string The current ID of the queried object
	 */
	var $current_id;

	/**
	 * @var string Function to call for notifications
	 */
	var $notification_callback;

	/**
	 * @var array WordPress Toolbar links
	 */
	var $admin_menu;

	/**
	 * Search input box placeholder string for the component
	 *
	 * @since BuddyPress (1.5)
	 * @var string
	 */
	public $search_string;

	/**
	 * Component's root slug
	 *
	 * @since BuddyPress (1.5)
	 * @var string
	 */
	public $root_slug;

	/**
	 * Component loader
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @param mixed $args Required. Supports these args:
	 *  - id: Unique ID (for internal identification). Letters, numbers, and underscores only
	 *  - name: Unique name. This should be a translatable name, eg __( 'Groups', 'buddypress' )
	 *  - path: The file path for the component's files. Used by BP_Component::includes()
	 * @uses bp_Component::setup_actions() Setup the hooks and actions
	 */
	function start( $id, $name, $path ) {
		// Internal identifier of component
		$this->id   = $id;

		// Internal component name
		$this->name = $name;

		// Path for includes
		$this->path = $path;

		// Move on to the next step
		$this->setup_actions();
	}

	/**
	 * Component global variables
	 *
	 * @since BuddyPress (1.5)
	 * @access private
	 *
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_id'
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_slug'
	 *
	 * @param arr $args Used to
	 */
	function setup_globals( $args = '' ) {
		global $bp;

		/** Slugs *************************************************************/

		$defaults = array(
			'slug'                  => $this->id,
			'root_slug'             => '',
			'has_directory'         => false,
			'notification_callback' => '',
			'search_string'         => '',
			'global_tables'         => ''
		);
		$r = wp_parse_args( $args, $defaults );

		// Slug used for permalink URI chunk after root
		$this->slug          = apply_filters( 'bp_' . $this->id . '_slug',          $r['slug']          );

		// Slug used for root directory
		$this->root_slug     = apply_filters( 'bp_' . $this->id . '_root_slug',     $r['root_slug']     );

		// Does this component have a top-level directory?
		$this->has_directory = apply_filters( 'bp_' . $this->id . '_has_directory', $r['has_directory'] );

		// Search string
		$this->search_string = apply_filters( 'bp_' . $this->id . '_search_string', $r['search_string'] );

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
		$bp->loaded_components[$this->slug] = $this->id;

		// Call action
		do_action( 'bp_' . $this->id . '_setup_globals' );
	}

	/**
	 * Include required files
	 *
	 * Please note that, by default, this method is fired on the bp_include hook, with priority
	 * 8. This is necessary so that core components are loaded in time to be available to
	 * third-party plugins. However, this load order means that third-party plugins whose main
	 * files are loaded at bp_include with priority 10 (as recommended), will not be loaded in
	 * time for their includes() method to fire automatically.
	 *
	 * For this reason, it is recommended that your plugin has its own method or function for
	 * requiring necessary files. If you must use this method, you will have to call it manually
	 * in your constructor class, ie
	 *   $this->includes();
	 *
	 * Note that when you pass an array value like 'actions' to includes, it looks for the
	 * following three files (assuming your component is called 'my_component'):
	 *   - ./actions
	 *   - ./bp-my_component/actions
	 *   - ./bp-my_component/bp-my_component-actions.php
	 *
	 * @since BuddyPress (1.5)
	 * @access private
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}includes'
	 */
	function includes( $includes = '' ) {
		if ( empty( $includes ) )
			return;

		$slashed_path = trailingslashit( $this->path );

		// Loop through files to be included
		foreach ( $includes as $file ) {

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
					continue;
				}
			}
		}

		// Call action
		do_action( 'bp_' . $this->id . '_includes' );
	}

	/**
	 * Setup the actions
	 *
	 * @since BuddyPress (1.5)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses do_action() Calls 'bp_{@link BP_Component::name}setup_actions'
	 */
	function setup_actions() {

		// Setup globals
		add_action( 'bp_setup_globals',          array ( $this, 'setup_globals'          ), 10 );

		// Include required files. Called early to ensure that BP core
		// components are loaded before plugins that hook their loader functions
		// to bp_include with the default priority of 10. This is for backwards
		// compatibility; henceforth, plugins should register themselves by
		// extending this base class.
		add_action( 'bp_include',                array ( $this, 'includes'               ), 8 );

		// Setup navigation
		add_action( 'bp_setup_nav',              array ( $this, 'setup_nav'              ), 10 );

		// Setup WP Toolbar menus
		add_action( 'bp_setup_admin_bar',        array ( $this, 'setup_admin_bar'        ), 10 );

		// Setup component title
		add_action( 'bp_setup_title',            array ( $this, 'setup_title'            ), 10 );

		// Register post types
		add_action( 'bp_register_post_types',    array ( $this, 'register_post_types'    ), 10 );

		// Register taxonomies
		add_action( 'bp_register_taxonomies',    array ( $this, 'register_taxonomies'    ), 10 );

		// Add the rewrite tags
		add_action( 'bp_add_rewrite_tags',       array ( $this, 'add_rewrite_tags'       ), 10 );

		// Generate rewrite rules
		add_action( 'bp_generate_rewrite_rules', array ( $this, 'generate_rewrite_rules' ), 10 );

		// Additional actions can be attached here
		do_action( 'bp_' . $this->id . '_setup_actions' );
	}

	/**
	 * Setup the navigation
	 *
	 * @param arr $main_nav Optional
	 * @param arr $sub_nav Optional
	 */
	function setup_nav( $main_nav = '', $sub_nav = '' ) {

		// No sub nav items without a main nav item
		if ( !empty( $main_nav ) ) {
			bp_core_new_nav_item( $main_nav );

			// Sub nav items are not required
			if ( !empty( $sub_nav ) ) {
				foreach( $sub_nav as $nav ) {
					bp_core_new_subnav_item( $nav );
				}
			}
		}

		// Call action
		do_action( 'bp_' . $this->id . '_setup_nav' );
	}

	/**
	 * Setup the Toolbar
	 *
	 * @global obj $wp_admin_bar
	 * @param array $wp_admin_menus
	 */
	function setup_admin_bar( $wp_admin_nav = '' ) {

		// Bail if this is an ajax request
		if ( defined( 'DOING_AJAX' ) )
			return;

		// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false
		if ( !bp_use_wp_admin_bar() )
			return;

		// Do we have Toolbar menus to add?
		if ( !empty( $wp_admin_nav ) ) {

			// Set this objects menus
			$this->admin_menu = $wp_admin_nav;

			// Define the WordPress global
			global $wp_admin_bar;

			// Add each admin menu
			foreach( $this->admin_menu as $admin_menu )
				$wp_admin_bar->add_menu( $admin_menu );
		}

		// Call action
		do_action( 'bp_' . $this->id . '_setup_admin_bar' );
	}

	/**
	 * Setup the component title
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}setup_title'
	 */
	function setup_title( ) {
		do_action(  'bp_' . $this->id . '_setup_title' );
	}

	/**
	 * Setup the component post types
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_post_types'
	 */
	function register_post_types() {
		do_action( 'bp_' . $this->id . '_register_post_types' );
	}

	/**
	 * Register component specific taxonomies
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_taxonomies'
	 */
	function register_taxonomies() {
		do_action( 'bp_' . $this->id . '_register_taxonomies' );
	}

	/**
	 * Add any additional rewrite tags
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_add_rewrite_tags'
	 */
	function add_rewrite_tags() {
		do_action( 'bp_' . $this->id . '_add_rewrite_tags' );
	}

	/**
	 * Generate any additional rewrite rules
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_generate_rewrite_rules'
	 */
	function generate_rewrite_rules ( $wp_rewrite ) {
		do_action( 'bp_' . $this->id . '_generate_rewrite_rules' );
	}
}
endif; // BP_Component

?>
