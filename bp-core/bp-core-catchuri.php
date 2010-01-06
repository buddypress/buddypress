<?php
/*
Based on contributions from: Chris Taylor - http://www.stillbreathing.co.uk/
Modified for BuddyPress by: Andy Peatling - http://apeatling.wordpress.com/
*/

/**
 * bp_core_set_uri_globals()
 *
 * Analyzes the URI structure and breaks it down into parts for use in code.
 * The idea is that BuddyPress can use complete custom friendly URI's without the
 * user having to add new re-write rules.
 *
 * Future custom components would then be able to use their own custom URI structure.
 *
 * The URI's are broken down as follows:
 *   - http:// domain.com / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 *   - OUTSIDE ROOT: http:// domain.com / sites / buddypress / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 *
 *	Example:
 *    - http://domain.com/members/andy/profile/edit/group/5/
 *    - $bp->current_component: string 'profile'
 *    - $bp->current_action: string 'edit'
 *    - $bp->action_variables: array ['group', 5]
 *
 * @package BuddyPress Core
 */
function bp_core_set_uri_globals() {
	global $current_component, $current_action, $action_variables;
	global $displayed_user_id;
	global $is_member_page;
	global $bp_unfiltered_uri;
	global $bp, $current_blog;

	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && bp_core_is_multisite() ) {
		/* Only catch URI's on the root blog if we are not running BP on multiple blogs */
		if ( BP_ROOT_BLOG != (int) $current_blog->blog_id )
			return false;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], 'wp-load.php' ) )
		$path = bp_core_referrer();
	else
		$path = clean_url( $_SERVER['REQUEST_URI'] );

	$path = apply_filters( 'bp_uri', $path );

	// Firstly, take GET variables off the URL to avoid problems,
	// they are still registered in the global $_GET variable */
	$noget = substr( $path, 0, strpos( $path, '?' ) );
	if ( $noget != '' ) $path = $noget;

	/* Fetch the current URI and explode each part separated by '/' into an array */
	$bp_uri = explode( "/", $path );

	/* Loop and remove empties */
	foreach ( (array)$bp_uri as $key => $uri_chunk )
		if ( empty( $bp_uri[$key] ) ) unset( $bp_uri[$key] );

	if ( defined( 'BP_ENABLE_MULTIBLOG' ) || 1 != BP_ROOT_BLOG ) {
		/* If we are running BuddyPress on any blog, not just a root blog, we need to first
		   shift off the blog name if we are running a subdirectory install of WPMU. */
		if ( $current_blog->path != '/' )
			array_shift( $bp_uri );
	}

	/* Set the indexes, these are incresed by one if we are not on a VHOST install */
	$component_index = 0;
	$action_index = $component_index + 1;

	// If this is a WordPress page, return from the function.
	if ( is_page( $bp_uri[$component_index] ) )
		return false;

	/* Get site path items */
	$paths = explode( '/', bp_core_get_site_path() );

	/* Take empties off the end of path */
	if ( empty( $paths[count($paths) - 1] ) )
		array_pop( $paths );

	/* Take empties off the start of path */
	if ( empty( $paths[0] ) )
		array_shift( $paths );

	foreach ( (array)$bp_uri as $key => $uri_chunk ) {
		if ( in_array( $uri_chunk, $paths )) {
			unset( $bp_uri[$key] );
		}
	}

	/* Reset the keys by merging with an empty array */
	$bp_uri = array_merge( array(), $bp_uri );
	$bp_unfiltered_uri = $bp_uri;

	/* If we are under anything with a members slug, set the correct globals */
	if ( $bp_uri[0] == BP_MEMBERS_SLUG ) {
		$is_member_page = true;
		$is_root_component = true;
	}

	/* Catch a member page and set the current member ID */
	if ( !defined( 'BP_ENABLE_ROOT_PROFILES' ) ) {
		if ( ( $bp_uri[0] == BP_MEMBERS_SLUG && !empty( $bp_uri[1] ) ) || in_array( 'wp-load.php', $bp_uri ) ) {
			// We are within a member page, set up user id globals
			$displayed_user_id = bp_core_get_displayed_userid( $bp_uri[1] );

			unset($bp_uri[0]);
			unset($bp_uri[1]);

			/* Reset the keys by merging with an empty array */
			$bp_uri = array_merge( array(), $bp_uri );
		}
	} else {
		if ( get_userdatabylogin( $bp_uri[0] ) || in_array( 'wp-load.php', $bp_uri ) ) {
			$is_member_page = true;
			$is_root_component = true;

			// We are within a member page, set up user id globals
			$displayed_user_id = bp_core_get_displayed_userid( $bp_uri[0] );

			unset($bp_uri[0]);

			/* Reset the keys by merging with an empty array */
			$bp_uri = array_merge( array(), $bp_uri );
		}
	}

	if ( !isset($is_root_component) )
		$is_root_component = in_array( $bp_uri[0], $bp->root_components );

	if ( 'no' == VHOST && !$is_root_component ) {
		$component_index++;
		$action_index++;
	}

	/* Set the current component */
	$current_component = $bp_uri[$component_index];

	/* Set the current action */
	$current_action = $bp_uri[$action_index];

	/* Set the entire URI as the action variables, we will unset the current_component and action in a second */
	$action_variables = $bp_uri;

	/* Unset the current_component and action from action_variables */
	unset($action_variables[$component_index]);
	unset($action_variables[$action_index]);

	/* Remove the username from action variables if this is not a VHOST install */
	if ( 'no' == VHOST && !$is_root_component )
		array_shift($action_variables);

	/* Reset the keys by merging with an empty array */
	$action_variables = array_merge( array(), $action_variables );

	//var_dump($current_component, $current_action, $action_variables); die;
}
add_action( 'plugins_loaded', 'bp_core_set_uri_globals', 3 );

/**
 * bp_catch_uri()
 *
 * Takes either a single page name or array of page names and
 * loads the first template file that can be found.
 *
 * Please don't call this function directly anymore, use: bp_core_load_template()
 *
 * @package BuddyPress Core
 * @global $bp_path BuddyPress global containing the template file names to use.
 * @param $pages Template file names to use.
 * @uses add_action() Hooks a function on to a specific action
 */
function bp_catch_uri( $pages, $skip_blog_check = false ) {
	global $bp_path, $bp_skip_blog_check;

	$bp_skip_blog_check = $skip_blog_check;

	$bp_path = $pages;

	if ( !bp_is_blog_page() ) {
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
	add_action( 'template_redirect', 'bp_core_do_catch_uri', 2 );
}

/**
 * bp_core_do_catch_uri()
 *
 * Loads the first template file found based on the $bp_path global.
 *
 * @package BuddyPress Core
 * @global $bp_path BuddyPress global containing the template file names to use.
 */
function bp_core_do_catch_uri() {
	global $bp_path, $bp, $wpdb;
	global $current_blog, $bp_skip_blog_check;
	global $bp_no_status_set;
	global $wp_query;

	/* Can be a single template or an array of templates */
	$templates = $bp_path;

	/* Don't hijack any URLs on blog pages */
	if ( bp_is_blog_page() ) {
		if ( !$bp_skip_blog_check )
			return false;
	} else {
		$wp_query->is_home = false;
	}

	/* Make sure this is not reported as a 404 */
	if ( !$bp_no_status_set ) {
		status_header( 200 );
		$wp_query->is_404 = false;

		if ( $bp->current_component != BP_HOME_BLOG_SLUG )
			$wp_query->is_page = true;
	}

	foreach ( (array)$templates as $template )
		$filtered_templates[] = $template . '.php';

	if ( $located_template = apply_filters( 'bp_located_template', locate_template( (array) $filtered_templates, false ), $filtered_templates ) ) {
		load_template( apply_filters( 'bp_load_template', $located_template ) );
	} else {
		if ( $located_template = locate_template( array( '404.php' ) ) ) {
			status_header( 404 );
			load_template( $located_template );
		} else
			bp_core_redirect( $bp->root_domain );
	}
	die;
}

function bp_core_catch_no_access() {
	global $bp, $bp_path, $bp_unfiltered_uri, $bp_no_status_set;

	// If bp_core_redirect() and $bp_no_status_set is true,
	// we are redirecting to an accessable page, so skip this check.
	if ( $bp_no_status_set )
		return false;

	/* If this user has been marked as a spammer and the logged in user is not a site admin, redirect. */
	if ( isset( $bp->displayed_user->id ) && bp_core_is_user_spammer( $bp->displayed_user->id ) ) {
		if ( !is_site_admin() )
			bp_core_redirect( $bp->root_domain );
		else
			bp_core_add_message( __( 'This user has been marked as a spammer. Only site admins can view this profile.', 'buddypress' ), 'error' );
	}

	// If this user does not exist, redirect to the root domain.
	if ( !$bp->displayed_user->id && $bp_unfiltered_uri[0] == BP_MEMBERS_SLUG && isset($bp_unfiltered_uri[1]) )
		bp_core_redirect( $bp->root_domain );

	// If the template file doesn't exist, redirect to the root domain.
	if ( !bp_is_blog_page() && !file_exists( locate_template( array( $bp_path . '.php' ), false ) ) )
		bp_core_redirect( $bp->root_domain );

	if ( !$bp_path && !bp_is_blog_page() ) {
		if ( is_user_logged_in() ) {
			wp_redirect( $bp->root_domain );
		} else {
			wp_redirect( site_url( 'wp-login.php?redirect_to=' . site_url() . $_SERVER['REQUEST_URI'] ) );
		}
	}
}
add_action( 'wp', 'bp_core_catch_no_access' );

/**
 * bp_core_catch_profile_uri()
 *
 * If the extended profiles component is not installed we still need
 * to catch the /profile URI's and display whatever we have installed.
 *
 */
function bp_core_catch_profile_uri() {
	global $bp;

	if ( !function_exists('xprofile_install') )
		bp_core_load_template( apply_filters( 'bp_core_template_display_profile', 'profile/index' ) );
}

?>