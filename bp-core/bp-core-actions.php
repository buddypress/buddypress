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

/**
 * Attach BuddyPress to WordPress
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--BuddyPress Sub-actions
  */
add_action( 'plugins_loaded',          'bp_loaded',                 10    );
add_action( 'init',                    'bp_init',                   10    );
add_action( 'wp',                      'bp_ready',                  10    );
add_action( 'setup_theme',             'bp_setup_theme',            10    );
add_action( 'after_theme_setup',       'bp_after_theme_setup',      10    );
add_action( 'wp_enqueue_scripts',      'bp_enqueue_scripts',        10    );
add_action( 'admin_bar_menu',          'bp_setup_admin_bar',        20    ); // After WP core
add_action( 'template_redirect',       'bp_template_redirect',      10    );
add_action( 'widgets_init',            'bp_widgets_init',           10    );
add_filter( 'template_include',        'bp_template_include',       10    );
add_filter( 'map_meta_cap',            'bp_map_meta_caps',          10, 4 );

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
 *                                                	     v---Load order
 */
add_action( 'bp_template_redirect', 'bp_redirect_canonical', 2 );
add_action( 'bp_template_redirect', 'bp_actions', 	     4 );
add_action( 'bp_template_redirect', 'bp_screens', 	     6 );

// Load the admin
if ( is_admin() ) {
	add_action( 'bp_loaded', 'bp_admin' );
}

/**
 * Plugin Dependency
 *
 * The purpose of the following actions is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in BuddyPress by mirroring existing WordPress actions in many places
 * allowing dependant plugins to hook into the BuddyPress specific ones, thus
 * guaranteeing proper code execution only whenBuddyPresss is active.
 *
 * The following functions are wrappers for their actions, allowing them to be
 * manually called and/or piggy-backed on top of other actions if needed.
 */

/** Sub-actions ***************************************************************/

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
 * Set up BuddyPress implementation of the WP Toolbar
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

/**
 * Initialize widgets
 */
function bp_widgets_init() {
	do_action ( 'bp_widgets_init' );
}

/** Theme *********************************************************************/

/**
 * Enqueue BuddyPress specific CSS and JS
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_enqueue_scripts'
 */
function bp_enqueue_scripts() {
	do_action ( 'bp_enqueue_scripts' );
}

/**
 * Piggy back action for BuddyPress sepecific theme actions before the theme has
 * been setup and the theme's functions.php has loaded.
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_setup_theme'
 */
function bp_setup_theme() {
	do_action ( 'bp_setup_theme' );
}

/**
 * Piggy back action for BuddyPress sepecific theme actions once the theme has
 * been setup and the theme's functions.php has loaded.
 *
 * @since BuddyPress (1.6)
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
 * @since BuddyPress (1.6)
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
 * @since BuddyPress (1.6)
 *
 * @uses do_action()
 */
function bp_template_redirect() {
	do_action( 'bp_template_redirect' );
}

?>
