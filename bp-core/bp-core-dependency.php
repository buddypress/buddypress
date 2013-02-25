<?php

/**
 * Plugin Dependency
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in BuddyPress by mirroring existing WordPress hookss in many places
 * allowing dependant plugins to hook into the BuddyPress specific ones, thus
 * guaranteeing proper code execution only when BuddyPress is active.
 *
 * The following functions are wrappers for hookss, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 *
 * @todo use anonymous functions when PHP minimun requirement allows (5.3)
 */

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
 * Setup the currently logged-in user
 *
 * @uses did_action() To make sure the user isn't loaded out of order
 * @uses do_action() Calls 'bp_setup_current_user'
 */
function bp_setup_current_user() {

	// If the current user is being setup before the "init" action has fired,
	// strange (and difficult to debug) role/capability issues will occur.
	if ( ! did_action( 'after_setup_theme' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'The current user is being initialized without using $wp->init().', 'buddypress' ), '1.7' );
	}

	do_action( 'bp_setup_current_user' );
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

/**
 * BuddyPress head scripts
 */
function bp_head() {
	do_action ( 'bp_head' );
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

/** Theme Helpers *************************************************************/

/**
 * The main action used registering theme directory
 *
 * @since BuddyPress (1.5)
 * @uses do_action()
 */
function bp_register_theme_directory() {
	do_action( 'bp_register_theme_directory' );
}

/**
 * The main action used registering theme packages
 *
 * @since BuddyPress (1.7)
 * @uses do_action()
 */
function bp_register_theme_packages() {
	do_action( 'bp_register_theme_packages' );
}

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
 * Hooked to 'after_setup_theme' with a priority of 100. This allows plenty of
 * time for other themes to load their features, such as BuddyPress support,
 * before our theme compatibility layer kicks in.
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_after_setup_theme'
 */
function bp_after_setup_theme() {
	do_action ( 'bp_after_setup_theme' );
}

/** Theme Compatibility Filter ************************************************/

/**
 * Piggy back filter for WordPress's 'request' filter
 *
 * @since BuddyPress (1.7)
 * @param array $query_vars
 * @return array
 */
function bp_request( $query_vars = array() ) {
	return apply_filters( 'bp_request', $query_vars );
}

/**
 * Piggy back filter to handle login redirects.
 *
 * @since BuddyPress (1.7)
 *
 * @param string $redirect_to
 * @param string $redirect_to_raw
 * @param string $user
 */
function bp_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {
	return apply_filters( 'bp_login_redirect', $redirect_to, $redirect_to_raw, $user );
}

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

/**
 * Generate BuddyPress-specific rewrite rules
 *
 * @since BuddyPress (1.7)
 * @param WP_Rewrite $wp_rewrite
 * @uses do_action() Calls 'bp_generate_rewrite_rules' with {@link WP_Rewrite}
 */
function bp_generate_rewrite_rules( $wp_rewrite ) {
	do_action_ref_array( 'bp_generate_rewrite_rules', array( &$wp_rewrite ) );
}

/**
 * Filter the allowed themes list for BuddyPress specific themes
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters() Calls 'bp_allowed_themes' with the allowed themes list
 */
function bp_allowed_themes( $themes ) {
	return apply_filters( 'bp_allowed_themes', $themes );
}
