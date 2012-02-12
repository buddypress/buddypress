<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * BuddyPress Filters & Actions
 *
 * @package BuddyPress
 * @subpackage Hooks
 *
 * This file contains the actions and filters that are used through-out BuddyPress.
 * They are consolidated here to make searching for them easier, and to help
 * developers understand at a glance the order in which things occur.
 *
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** ACTIONS *******************************************************************/

/**
 * Attach BuddyPress to WordPress
 *
 * BuddyPress uses its own internal actions to help aid in additional plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress occur.
 */
add_action( 'plugins_loaded',         'bp_loaded',                 10 );
add_action( 'init',                   'bp_init',                   10 );
add_action( 'wp',                     'bp_ready',                  10 );
//add_action( 'generate_rewrite_rules', 'bp_generate_rewrite_rules', 10 );
//add_action( 'wp_enqueue_scripts',     'bp_enqueue_scripts',        10 );
//add_filter( 'template_include',       'bp_template_include',       10 );

/**
 * bp_loaded - Attached to 'plugins_loaded' above
 *
 * Attach various loader actions to the bp_loaded action.
 * The load order helps to execute code at the correct time.
 *                                                 v---Load order
 */
add_action( 'bp_loaded', 'bp_setup_components',    2  );
add_action( 'bp_loaded', 'bp_include',             4  );
add_action( 'bp_loaded', 'bp_setup_widgets',       6  );
add_action( 'bp_loaded', 'bp_core_load_admin_bar', 10 );

/**
 * bp_init - Attached to 'init' above
 *
 * Attach various initialization actions to the bp_init action.
 * The load order helps to execute code at the correct time.
 *                                                 v---Load order
 */
add_action( 'bp_init', 'bp_core_set_uri_globals',  2 );
add_action( 'bp_init', 'bp_setup_globals',         4 );
add_action( 'bp_init', 'bp_setup_nav',             6 );
add_action( 'bp_init', 'bp_setup_title',           8 );

/**
 * bp_ready - Attached to 'wp' above
 *
 * Attach various initialization actions to the bp_init action.
 * The load order helps to execute code at the correct time.
 *                                    v---Load order
 */
add_action( 'bp_ready', 'bp_actions', 2 );
add_action( 'bp_ready', 'bp_screens', 4 );

/** Theme *********************************************************************/

// Piggy back WordPress theme actions
add_action( 'wp_head',   'bp_head'   );
add_action( 'wp_footer', 'bp_footer' );

/** Admin Bar *****************************************************************/

// Setup the navigation menu
add_action( 'admin_bar_menu', 'bp_setup_admin_bar', 11 );

/** The hooks *****************************************************************/

/**
 * Include files on this action
 */
function bp_include() {
	do_action( 'bp_include' );
}

/**
 * Include files on this action
 */
function bp_setup_components() {
	do_action( 'bp_setup_components' );
}

/**
 * Setup global variables and objects
 */
function bp_setup_globals() {
	do_action( 'bp_setup_globals' );
}

/**
 * Set navigation elements
 */
function bp_setup_nav() {
	do_action( 'bp_setup_nav' );
}

/**
 * Set up BuddyPress implementation of the WP admin bar
 */
function bp_setup_admin_bar() {
	if ( bp_use_wp_admin_bar() )
		do_action( 'bp_setup_admin_bar' );
}

/**
 * Set the page title
 */
function bp_setup_title() {
	do_action( 'bp_setup_title' );
}

/**
 * Register widgets
 */
function bp_setup_widgets() {
	do_action( 'bp_register_widgets' );
}

/**
 * Initlialize code
 */
function bp_init() {
	do_action( 'bp_init' );
}

/**
 * Attached to plugins_loaded
 */
function bp_loaded() {
	do_action( 'bp_loaded' );
}

/**
 * Attached to wp
 */
function bp_ready() {
	do_action( 'bp_ready' );
}

/**
 * Attach potential template actions
 */
function bp_actions() {
	do_action( 'bp_actions' );
}

/**
 * Attach potential template screens
 */
function bp_screens() {
	do_action( 'bp_screens' );
}

/** Activation Actions ********************************************************/

/**
 * Runs on BuddyPress activation
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_activation' hook
 */
function bp_activation() {
	do_action( 'bp_activation' );
}

/**
 * Runs on BuddyPress deactivation
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_deactivation' hook
 */
function bp_deactivation() {
	do_action( 'bp_deactivation' );
}

/**
 * Runs when uninstalling BuddyPress
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_uninstall' hook
 */
function bp_uninstall() {
	do_action( 'bp_uninstall' );
}

/** Admin *********************************************************************/

if ( is_admin() ) {

	/** Actions ***************************************************************/

	add_action( 'bp_loaded',         'bp_admin'                   );
	//add_action( 'bp_admin_init',     'bp_admin_settings_help'     );
	//add_action( 'admin_menu',        'bp_admin_separator'         );
	//add_action( 'custom_menu_order', 'bp_admin_custom_menu_order' );
	//add_action( 'menu_order',        'bp_admin_menu_order'        );

	/**
	 * Run the updater late on 'bp_admin_init' to ensure that all alterations
	 * to the permalink structure have taken place. This fixes the issue of
	 * permalinks not being flushed properly when a bbPress update occurs.
	 */
	//add_action( 'bp_admin_init',    'bp_setup_updater', 999 );

	/** Filters ***************************************************************/
}

/** Theme *********************************************************************/

/**
 * Piggy-back action for BuddyPress specific <head> actions in a theme
 *
 * @since BuddyPress (1.6)
 * 
 * @uses do_action() Calls 'bp_head' hook
 */
function bp_head() {
	do_action( 'bp_head' );
}

/**
 * Piggy-back action for BuddyPress specific footer actions in a theme
 *
 * @since BuddyPress (1.6)
 * 
 * @uses do_action() Calls 'bp_footer' hook
 */
function bp_footer() {
	do_action( 'bp_footer' );
}

?>
