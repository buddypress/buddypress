<?php

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
add_action( 'plugins_loaded',     'bp_loaded',            10    );
add_action( 'init',               'bp_init',              10    );
add_action( 'wp',                 'bp_ready',             10    );
add_action( 'admin_bar_menu',     'bp_setup_admin_bar',   20    ); // After WP core
add_action( 'template_redirect',  'bp_template_redirect', 10    );
add_action( 'wp_enqueue_scripts', 'bp_enqueue_scripts',   10    );
add_action( 'template_redirect',  'bp_template_redirect', 10    );
add_filter( 'template_include',   'bp_template_include',  10    );
add_filter( 'after_theme_setup',  'bp_after_theme_setup', 10    );
add_filter( 'map_meta_cap',       'bp_map_meta_caps',     10, 4 );

// Piggy back WordPress theme actions
add_action( 'wp_head',   'bp_head',   10 );
add_action( 'wp_footer', 'bp_footer', 10 );

//add_action( 'generate_rewrite_rules', 'bp_generate_rewrite_rules', 10 );

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
 * bp_template_redirect - Attached to 'template_redirect' above
 *
 * Attach various template actions to the bp_template_redirect action.
 * The load order helps to execute code at the correct time.
 * 
 * Note that we currently use template_redirect versus template include because
 * BuddyPress is a bully and overrides the existing themes output in many
 * places. This won't always be this way, we promise.
 *                                                v---Load order
 */
add_action( 'bp_template_redirect', 'bp_actions', 2 );
add_action( 'bp_template_redirect', 'bp_screens', 4 );

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

/**
 * Enqueue BuddyPress specific CSS and JS
 *
 * @since bbPress (r5812)
 *
 * @uses do_action() Calls 'bp_enqueue_scripts'
 */
function bp_enqueue_scripts() {
	do_action ( 'bp_enqueue_scripts' );
}

/**
 * Piggy back action for BuddyPress sepecific theme actions once the theme has
 * been setup and the theme's functions.php has loaded.
 *
 * @since bbPress (r5812)
 *
 * @uses do_action() Calls 'bp_after_theme_setup'
 */
function bp_after_theme_setup() {
	do_action ( 'bp_after_theme_setup' );
}

/** Theme Compatibility Filter ************************************************/

/**
 * The main filter used for theme compatibility and displaying custom BuddyPress
 * theme files.
 *
 * @since BuddyPress (r5812)
 *
 * @uses apply_filters()
 *
 * @param string $template
 * @return string Template file to use
 */
function bp_template_include( $template = '' ) {
	return apply_filters( 'bp_template_include', $template );
}

/** Theme Permissions *********************************************************/

/**
 * The main action used for redirecting BuddyPress theme actions that are not
 * permitted by the current_user
 *
 * @since BuddyPress (r5812)
 *
 * @uses do_action()
 */
function bp_template_redirect() {
	do_action( 'bp_template_redirect' );
}

?>
