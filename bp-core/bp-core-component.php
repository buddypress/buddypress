<?php

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
 * @since BuddyPress {unknown}
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
	 * @var string The path to the plugins files
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
	 * Component loader
	 *
	 * @since BuddyPress {unknown}
	 *
	 * @param mixed $args Required. Supports these args:
	 *  - name: Unique name (for internal identification)
	 *  - id: Unique ID (normally for custom post type)
	 *  - slug: Unique slug (used in query string and permalinks)
	 *  - query: The loop for this component (WP_Query)
	 *  - current_id: The current ID of the queried object
	 * @uses bp_Component::_setup_globals() Setup the globals needed
	 * @uses bp_Component::_includes() Include the required files
	 * @uses bp_Component::_setup_actions() Setup the hooks and actions
	 */
	function start( $id, $name, $path ) {
		// Internal identifier of component
		$this->id   = $id;

		// Internal component name
		$this->name = $name;

		// Path for includes
		$this->path = $path;

		// Move on to the next step
		$this->_setup_actions();
	}

	/**
	 * Component global variables
	 *
	 * @since BuddyPress {unknown}
	 * @access private
	 *
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_id'
	 * @uses apply_filters() Calls 'bp_{@link bp_Component::name}_slug'
	 *
	 * @param arr $args Used to
	 */
	function _setup_globals( $args = '' ) {
		global $bp;

		/** Slugs *************************************************************/

		$defaults = array(
			'slug'                  => '',
			'root_slug'             => '',
			'notification_callback' => '',
			'search_string'         => '',
			'global_tables'         => '',
		);
		$r = wp_parse_args( $args, $defaults );

		// Slug used for permalinks
		$this->slug          = apply_filters( 'bp_' . $this->id . '_slug',          $r['slug']          );

		// Slug used for root directory
		$this->root_slug     = apply_filters( 'bp_' . $this->id . '_root_slug',     $r['root_slug']     );

		// Search string
		$this->search_string = apply_filters( 'bp_' . $this->id . '_search_string', $r['search_string'] );

		// Notifications callback
		$this->notification_callback = 'bp_' . $this->id . '_notification_callback';

		// Setup global table names
		if ( !empty( $r['global_tables'] ) )
			foreach ( $r['global_tables'] as $global_name => $table_name )
				$this->$global_name = $table_name;

		/** BuddyPress ********************************************************/

		// Register this component in the active components array
		$bp->active_components[$this->slug] = $this->id;

		// Call action
		do_action( 'bp_' . $this->id . '_setup_globals' );
	}

	/**
	 * Include required files
	 *
	 * @since BuddyPress {unknown}
	 * @access private
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_includes'
	 */
	function _includes( $includes = '' ) {
		if ( empty( $includes ) )
			return;

		// Loop through files to be included
		foreach ( $includes as $file ) {

			// Check path + file
			if ( file_exists( $this->path . '/' . $file ) )
				require_once( $this->path . '/' . $file );

			// Check path + /bp-component/ + file
			elseif ( file_exists( $this->path . '/bp-' . $this->id . '/' . $file ) )
				require_once( $this->$path . '/bp-' . $this->id . '/' . $file );

			// Check buddypress/bp-component/bp-component-$file.php
			elseif ( file_exists( $this->path . '/bp-' . $this->id . '/bp-' . $this->id . '-' . $file  . '.php' ) )
				require_once( $this->path . '/bp-' . $this->id . '/bp-' . $this->id . '-' . $file . '.php' );

		}

		// Call action
		do_action( 'bp_' . $this->id . '_includes' );
	}

	/**
	 * Setup the actions
	 *
	 * @since BuddyPress {unknown}
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses do_action() Calls 'bp_{@link BP_Component::name}_setup_actions'
	 */
	function _setup_actions() {
		// Register post types
		add_action( 'bp_setup_globals',            array ( $this, '_setup_globals'           ), 10 );

		// Register post types
		add_action( 'bp_include',                  array ( $this, '_includes'                ), 10 );

		// Register post types
		add_action( 'bp_setup_nav',                array ( $this, '_setup_nav'               ), 10 );

		// Register post types
		add_action( 'bp_setup_title',              array ( $this, '_setup_title'             ), 10 );

		// Register post types
		add_action( 'bp_register_post_types',      array ( $this, 'register_post_types'      ), 10 );

		// Register taxonomies
		add_action( 'bp_register_taxonomies',      array ( $this, 'register_taxonomies'      ), 10 );

		// Add the rewrite tags
		add_action( 'bp_add_rewrite_tags',         array ( $this, 'add_rewrite_tags'         ), 10 );

		// Generate rewrite rules
		add_action( 'bp_generate_rewrite_rules',   array ( $this, 'generate_rewrite_rules'   ), 10 );

		// Additional actions can be attached here
		do_action( 'bp_' . $this->id . '_setup_actions' );
	}

	/**
	 * Setup the navigation
	 *
	 * @param arr $main_nav
	 * @param arr $sub_nav
	 */
	function _setup_nav( $main_nav, $sub_nav ) {
		bp_core_new_nav_item( $main_nav );

		foreach( $sub_nav as $nav )
			bp_core_new_subnav_item( $nav );

		// Call action
		do_action( 'bp_' . $this->id . '_setup_nav' );
	}


	function _setup_title( ) {
		
	}

	/**
	 * Setup the component post types
	 *
	 * @since BuddyPress {unknown}
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_post_types'
	 */
	function register_post_types() {
		do_action( 'bp_' . $this->id . '_register_post_types' );
	}

	/**
	 * Register component specific taxonomies
	 *
	 * @since BuddyPress {unknown}
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_register_taxonomies'
	 */
	function register_taxonomies() {
		do_action( 'bp_' . $this->id . '_register_taxonomies' );
	}

	/**
	 * Add any additional rewrite tags
	 *
	 * @since BuddyPress {unknown}
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_add_rewrite_tags'
	 */
	function add_rewrite_tags() {
		do_action( 'bp_' . $this->id . '_add_rewrite_tags' );
	}

	/**
	 * Generate any additional rewrite rules
	 *
	 * @since BuddyPress {unknown}
	 *
	 * @uses do_action() Calls 'bp_{@link bp_Component::name}_generate_rewrite_rules'
	 */
	function generate_rewrite_rules ( $wp_rewrite ) {
		do_action( 'bp_' . $this->id . '_generate_rewrite_rules' );
	}
}
endif; // BP_Component

?>
