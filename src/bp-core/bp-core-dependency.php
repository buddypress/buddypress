<?php

/**
 * Plugin Dependency Action Hooks.
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in BuddyPress by mirroring existing WordPress hooks in many places
 * allowing dependant plugins to hook into the BuddyPress specific ones, thus
 * guaranteeing proper code execution only when BuddyPress is active.
 *
 * The following functions are wrappers for hooks, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 *
 * @todo use anonymous functions when PHP minimun requirement allows (5.3)
 */

/**
 * Fire the 'bp_include' action, where plugins should include files.
 */
function bp_include() {
	do_action( 'bp_include' );
}

/**
 * Fire the 'bp_setup_components' action, where plugins should initialize components.
 */
function bp_setup_components() {
	do_action( 'bp_setup_components' );
}

/**
 * Fire the 'bp_setup_canonical_stack' action, where plugins should set up their canonical URL.
 */
function bp_setup_canonical_stack() {
	do_action( 'bp_setup_canonical_stack' );
}

/**
 * Fire the 'bp_setup_globals' action, where plugins should initialize global settings.
 */
function bp_setup_globals() {
	do_action( 'bp_setup_globals' );
}

/**
 * Fire the 'bp_setup_nav' action, where plugins should register their navigation items.
 */
function bp_setup_nav() {
	do_action( 'bp_setup_nav' );
}

/**
 * Fire the 'bp_setup_admin_bar' action, where plugins should add items to the WP admin bar.
 */
function bp_setup_admin_bar() {
	if ( bp_use_wp_admin_bar() )
		do_action( 'bp_setup_admin_bar' );
}

/**
 * Fire the 'bp_setup_title' action, where plugins should modify the page title.
 */
function bp_setup_title() {
	do_action( 'bp_setup_title' );
}

/**
 * Fire the 'bp_register_widgets' action, where plugins should register widgets.
 */
function bp_setup_widgets() {
	do_action( 'bp_register_widgets' );
}

/**
 * Set up the currently logged-in user.
 *
 * @uses did_action() To make sure the user isn't loaded out of order.
 * @uses do_action() Calls 'bp_setup_current_user'.
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
 * Fire the 'bp_init' action, BuddyPress's main initialization hook.
 */
function bp_init() {
	do_action( 'bp_init' );
}

/**
 * Fire the 'bp_loaded' action, which fires after BP's core plugin files have been loaded.
 *
 * Attached to 'plugins_loaded'.
 */
function bp_loaded() {
	do_action( 'bp_loaded' );
}

/**
 * Fire the 'bp_ready' action, which runs after BP is set up and the page is about to render.
 *
 * Attached to 'wp'.
 */
function bp_ready() {
	do_action( 'bp_ready' );
}

/**
 * Fire the 'bp_actions' action, which runs just before rendering.
 *
 * Attach potential template actions, such as catching form requests or routing
 * custom URLs.
 */
function bp_actions() {
	do_action( 'bp_actions' );
}

/**
 * Fire the 'bp_screens' action, which runs just before rendering.
 *
 * Runs just after 'bp_actions'. Use this hook to attach your template
 * loaders.
 */
function bp_screens() {
	do_action( 'bp_screens' );
}

/**
 * Fire 'bp_widgets_init', which runs after widgets have been set up.
 *
 * Hooked to 'widgets_init'.
 */
function bp_widgets_init() {
	do_action ( 'bp_widgets_init' );
}

/**
 * Fire 'bp_head', which is used to hook scripts and styles in the <head>.
 *
 * Hooked to 'wp_head'.
 */
function bp_head() {
	do_action ( 'bp_head' );
}

/** Theme Permissions *********************************************************/

/**
 * Fire the 'bp_template_redirect' action.
 *
 * Run at 'template_redirect', just before WordPress selects and loads a theme
 * template. The main purpose of this hook in BuddyPress is to redirect users
 * who do not have the proper permission to access certain content.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses do_action()
 */
function bp_template_redirect() {
	do_action( 'bp_template_redirect' );
}

/** Theme Helpers *************************************************************/

/**
 * Fire the 'bp_register_theme_directory' action.
 *
 * The main action used registering theme directories.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses do_action()
 */
function bp_register_theme_directory() {
	do_action( 'bp_register_theme_directory' );
}

/**
 * Fire the 'bp_register_theme_packages' action.
 *
 * The main action used registering theme packages.
 *
 * @since BuddyPress (1.7.0)
 *
 * @uses do_action()
 */
function bp_register_theme_packages() {
	do_action( 'bp_register_theme_packages' );
}

/**
 * Fire the 'bp_enqueue_scripts' action, where BP enqueues its CSS and JS.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses do_action() Calls 'bp_enqueue_scripts'.
 */
function bp_enqueue_scripts() {
	do_action ( 'bp_enqueue_scripts' );
}

/**
 * Fire the 'bp_add_rewrite_tag' action, where BP adds its custom rewrite tags.
 *
 * @since BuddyPress (1.8.0)
 *
 * @uses do_action() Calls 'bp_add_rewrite_tags'.
 */
function bp_add_rewrite_tags() {
	do_action( 'bp_add_rewrite_tags' );
}

/**
 * Fire the 'bp_add_rewrite_rules' action, where BP adds its custom rewrite rules.
 *
 * @since BuddyPress (1.9.0)
 *
 * @uses do_action() Calls 'bp_add_rewrite_rules'.
 */
function bp_add_rewrite_rules() {
	do_action( 'bp_add_rewrite_rules' );
}

/**
 * Fire the 'bp_add_permastructs' action, where BP adds its BP-specific permalink structure.
 *
 * @since BuddyPress (1.9.0)
 *
 * @uses do_action() Calls 'bp_add_permastructs'.
 */
function bp_add_permastructs() {
	do_action( 'bp_add_permastructs' );
}

/**
 * Fire the 'bp_setup_theme' action.
 *
 * The main purpose of 'bp_setup_theme' is give themes a place to load their
 * BuddyPress-specific functionality.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses do_action() Calls 'bp_setup_theme'.
 */
function bp_setup_theme() {
	do_action ( 'bp_setup_theme' );
}

/**
 * Fire the 'bp_after_setup_theme' action.
 *
 * Piggy-back action for BuddyPress-specific theme actions once the theme has
 * been set up and the theme's functions.php has loaded.
 *
 * Hooked to 'after_setup_theme' with a priority of 100. This allows plenty of
 * time for other themes to load their features, such as BuddyPress support,
 * before our theme compatibility layer kicks in.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses do_action() Calls 'bp_after_setup_theme'.
 */
function bp_after_setup_theme() {
	do_action ( 'bp_after_setup_theme' );
}

/** Theme Compatibility Filter ************************************************/

/**
 * Fire the 'bp_request' filter, a piggy-back of WP's 'request'.
 *
 * @since BuddyPress (1.7.0)
 *
 * @see WP::parse_request() for a description of parameters.
 *
 * @param array $query_vars See {@link WP::parse_request()}.
 * @return array $query_vars See {@link WP::parse_request()}.
 */
function bp_request( $query_vars = array() ) {
	return apply_filters( 'bp_request', $query_vars );
}

/**
 * Fire the 'bp_login_redirect' filter, a piggy-back of WP's 'login_redirect'.
 *
 * @since BuddyPress (1.7.0)
 *
 * @param string $redirect_to See 'login_redirect'.
 * @param string $redirect_to_raw See 'login_redirect'.
 * @param string $user See 'login_redirect'.
 */
function bp_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {
	return apply_filters( 'bp_login_redirect', $redirect_to, $redirect_to_raw, $user );
}

/**
 * Fire 'bp_template_include', main filter used for theme compatibility and displaying custom BP theme files.
 *
 * Hooked to 'template_include'.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses apply_filters()
 *
 * @param string $template See 'template_include'.
 * @return string Template file to use.
 */
function bp_template_include( $template = '' ) {
	return apply_filters( 'bp_template_include', $template );
}

/**
 * Fire the 'bp_generate_rewrite_rules' filter, where BP generates its rewrite rules.
 *
 * @since BuddyPress (1.7.0)
 *
 * @uses do_action() Calls 'bp_generate_rewrite_rules' with {@link WP_Rewrite}.
 *
 * @param WP_Rewrite $wp_rewrite See 'generate_rewrite_rules'.
 */
function bp_generate_rewrite_rules( $wp_rewrite ) {
	do_action_ref_array( 'bp_generate_rewrite_rules', array( &$wp_rewrite ) );
}

/**
 * Fire the 'bp_allowed_themes' filter.
 *
 * Filter the allowed themes list for BuddyPress-specific themes.
 *
 * @since BuddyPress (1.7.0)
 *
 * @uses apply_filters() Calls 'bp_allowed_themes' with the allowed themes list.
 */
function bp_allowed_themes( $themes ) {
	return apply_filters( 'bp_allowed_themes', $themes );
}

/** Requests ******************************************************************/

/**
 * The main action used for handling theme-side POST requests
 *
 * @since BuddyPress (1.9.0)
 * @uses do_action()
 */
function bp_post_request() {

	// Bail if not a POST action
	if ( ! bp_is_post_request() )
		return;

	// Bail if no action
	if ( empty( $_POST['action'] ) )
		return;

	// This dynamic action is probably the one you want to use. It narrows down
	// the scope of the 'action' without needing to check it in your function.
	do_action( 'bp_post_request_' . $_POST['action'] );

	// Use this static action if you don't mind checking the 'action' yourself.
	do_action( 'bp_post_request',   $_POST['action'] );
}

/**
 * The main action used for handling theme-side GET requests
 *
 * @since BuddyPress (1.9.0)
 * @uses do_action()
 */
function bp_get_request() {

	// Bail if not a POST action
	if ( ! bp_is_get_request() )
		return;

	// Bail if no action
	if ( empty( $_GET['action'] ) )
		return;

	// This dynamic action is probably the one you want to use. It narrows down
	// the scope of the 'action' without needing to check it in your function.
	do_action( 'bp_get_request_' . $_GET['action'] );

	// Use this static action if you don't mind checking the 'action' yourself.
	do_action( 'bp_get_request',   $_GET['action'] );
}
