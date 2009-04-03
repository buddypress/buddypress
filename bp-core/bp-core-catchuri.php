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
 *    - $current_component: string 'profile'
 *    - $current_action: string 'edit'
 *    - $action_variables: array ['group', 5]
 * 
 * @package BuddyPress Core
 */
function bp_core_set_uri_globals() {
	global $current_component, $current_action, $action_variables;
	global $displayed_user_id;
	global $is_member_page, $is_new_friend;
	global $bp_unfiltered_uri;
	global $bp, $current_blog;
	
	/* Only catch URI's on the root blog */
	if ( BP_ROOT_BLOG != (int) $current_blog->blog_id )
		return false;
	
	if ( strpos( $_SERVER['REQUEST_URI'], 'bp-core-ajax-handler.php' ) )
		$path = bp_core_referrer();
	else
		$path = clean_url( $_SERVER['REQUEST_URI'] );

	$path = apply_filters( 'bp_uri', $path );

	// Firstly, take GET variables off the URL to avoid problems,
	// they are still registered in the global $_GET variable */
	$noget = substr( $path, 0, strpos( $path, '?' ) );
	if ( $noget != '' ) $path = $noget;
	
	/* Fetch the current URI and explode each part seperated by '/' into an array */
	$bp_uri = explode( "/", $path );
	
	/* Take empties off the end of complete URI */
	if ( empty( $bp_uri[count($bp_uri) - 1] ) )
		array_pop( $bp_uri );

	/* Take empties off the start of complete URI */
	if ( empty( $bp_uri[0] ) )
		array_shift( $bp_uri );
		
	/* Get total URI segment count */
	$bp_uri_count = count( $bp_uri ) - 1;
	$is_member_page = false;
	
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

	for ( $i = 0; $i < $bp_uri_count; $i++ ) {
		if ( in_array( $bp_uri[$i], $paths )) {
			unset( $bp_uri[$i] );
		}
	}

	/* Reset the keys by merging with an empty array */
	$bp_uri = array_merge( array(), $bp_uri );
	$bp_unfiltered_uri = $bp_uri;
	
	/* Catch a member page and set the current member ID */
	if ( $bp_uri[0] == BP_MEMBERS_SLUG || in_array( 'bp-core-ajax-handler.php', $bp_uri ) ) {
		$is_member_page = true;
		$is_root_component = true;
		
		// We are within a member page, set up user id globals
		$displayed_user_id = bp_core_get_displayed_userid( $bp_uri[1] );
				
		unset($bp_uri[0]);
		unset($bp_uri[1]);
		
		// if the get variable 'new' is set this the first visit to a new friends profile.
		// this means we need to delete friend acceptance notifications, so we set a flag of is_new_friend.
		if ( isset($_GET['new']) ) {
			$is_new_friend = 1;
			unset($bp_uri[2]);
		}
		
		/* Reset the keys by merging with an empty array */
		$bp_uri = array_merge( array(), $bp_uri );
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

	//var_dump($current_component, $current_action, $action_variables);
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
	
	$pages = $bp_path;

	/* Don't hijack any URLs on blog pages */
	if ( !$bp_skip_blog_check ) {
		if ( bp_is_blog_page() )
			return false;
	}
	
	/* Make sure this is not reported as a 404 */
	if ( !$bp_no_status_set ) {
		status_header( 200 );
		$wp_query->is_404 = false;
		
		if ( $bp->current_component != BP_HOME_BLOG_SLUG )
			$wp_query->is_page = true;
	}

	if ( is_array( $pages ) ) {
		foreach( $pages as $page ) {
			if ( file_exists( TEMPLATEPATH . "/" . $page . ".php" ) ) {
				load_template( TEMPLATEPATH . "/" . $page . ".php" );
			}
		}
	} else {
		if ( file_exists( TEMPLATEPATH . "/" . $pages . ".php" ) ) {
			load_template( TEMPLATEPATH . "/" . $pages . ".php" );
		} else {
			if ( file_exists( TEMPLATEPATH . "/404.php" ) ) {
				status_header( 404 );
				load_template( TEMPLATEPATH . "/404.php" );
			} else {
				wp_die( __( '<strong>You do not have any BuddyPress themes installed.</strong><br />Please download the <a href="http://buddypress.org/extend/themes" title="Download">Default BuddyPress Theme</a> and install it in /wp-content/bp-themes/' ) );
			}
		}
	}
	die;
}

function bp_core_catch_no_access() {
	global $bp, $bp_path, $bp_unfiltered_uri, $bp_no_status_set;

	// If bp_core_redirect() and $bp_no_status_set is true,
	// we are redirecting to an accessable page, so skip this check.
	if ( $bp_no_status_set )
		return false;
		
	// If this user does not exist, redirect to the root domain.
	if ( !$bp->displayed_user->id && $bp_unfiltered_uri[0] == BP_MEMBERS_SLUG && isset($bp_unfiltered_uri[1]) )
		bp_core_redirect( $bp->root_domain );

	if ( !$bp_path && !bp_is_blog_page() ) {
		if ( is_user_logged_in() ) {
			wp_redirect( $bp->loggedin_user->domain );
		} else {
			wp_redirect( site_url( 'wp-login.php?redirect_to=' . site_url() . $_SERVER['REQUEST_URI'] ) );
		}
	}
}
add_action( 'wp', 'bp_core_catch_no_access', 10 );

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
		bp_core_load_template( 'profile/index' );
}

function bp_core_force_buddypress_theme( $template ) {
	global $is_member_page, $bp;

	$member_theme = get_site_option( 'active-member-theme' );
	
	if ( empty( $member_theme ) )
		$member_theme = 'buddypress-member';
	
	if ( $is_member_page ) {

		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

		return $member_theme;
	} else {
		return $template;
	}
}
add_filter( 'template', 'bp_core_force_buddypress_theme', 1, 1 );

function bp_core_force_buddypress_stylesheet( $stylesheet ) {
	global $is_member_page;

	$member_theme = get_site_option( 'active-member-theme' );
	
	if ( empty( $member_theme ) )
		$member_theme = 'buddypress-member';

	if ( $is_member_page ) {
		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

		return $member_theme;
	} else {
		return $stylesheet;
	}
}
add_filter( 'stylesheet', 'bp_core_force_buddypress_stylesheet', 1, 1 );

?>