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
	global $current_userid;
	global $is_member_page, $is_new_friend;
	
	$path = apply_filters( 'bp_uri', $_SERVER['REQUEST_URI'] );
	
	// Firstly, take GET variables off the URL to avoid problems,
	// they are still registered in the global $_GET variable */
	$noget = substr( $path, 0, strpos( $path, '?' ) );
	if ( $noget != '' ) $path = $noget;
	
	/* Fetch the current URI and explode each part seperated by '/' into an array */
	$bp_uri = explode( "/", $path );
	
	/* Take empties off the end of complete URI */
	if ( $bp_uri[count($bp_uri) - 1] == "" )
		array_pop( $bp_uri );

	/* Take empties off the start of complete URI */
	if ( $bp_uri[0] == "" )
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
	if ( $paths[count($paths) - 1] == "" )
		array_pop( $paths );

	/* Take empties off the start of path */
	if ( $paths[0] == "" )
		array_shift( $paths );

	for ( $i = 0; $i < $bp_uri_count; $i++ ) {
		if ( in_array( $bp_uri[$i], $paths )) {
			unset( $bp_uri[$i] );
		}
	}
	
	/* Reset the keys by merging with an empty array */
	$bp_uri = array_merge( array(), $bp_uri );	

	/* Catch a member page and set the current member ID */
	if ( $bp_uri[0] == MEMBERS_SLUG && $bp_uri[1] != '' ) {
		$is_member_page = true;
		$is_root_component = true;
		
		// We are within a member page, set up user id globals
		$current_userid = bp_core_get_current_userid( $bp_uri[1] );
				
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
	
	/* This is used to determine where the component and action indexes should start */
	$root_components = explode( ',', BP_CORE_ROOT_COMPONENTS );
	
	if ( !isset($is_root_component) )
		$is_root_component = in_array( $bp_uri[0], $root_components );

	if ( VHOST == 'no' && !$is_root_component ) {
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
	if ( VHOST == 'no' && !$is_root_component )
		array_shift($action_variables);
	
	/* Reset the keys by merging with an empty array */
	$action_variables = array_merge( array(), $action_variables );

	//var_dump($current_component, $current_action, $action_variables);
}
add_action( 'wp', 'bp_core_set_uri_globals', 1 );

/**
 * bp_catch_uri()
 *
 * Takes either a single page name or array of page names and 
 * loads the first template file that can be found.
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

	add_action( "template_redirect", "bp_core_do_catch_uri", 10, 1 );
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

	$pages = $bp_path;

	/* Don't hijack any URLs on blog pages */
	if ( !$bp_skip_blog_check ) {
		if ( bp_is_blog_page() )
			return false;
	}
	
	if ( !file_exists( TEMPLATEPATH . "/header.php" ) || !file_exists( TEMPLATEPATH . "/footer.php" ) )
		wp_redirect( $bp['root_domain'] );

	/* Make sure this is not reported as a 404 */
	// This is causing too many problems right now.
	header( "Status: 200 OK", true, 200 );
		
	do_action( 'get_header' );
	load_template( TEMPLATEPATH . "/header.php" );

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
				load_template( TEMPLATEPATH . "/404.php" );
			} else if ( file_exists( TEMPLATEPATH . "/home.php" ) ) {
				load_template( TEMPLATEPATH . "/home.php" );
			} else {
				load_template( TEMPLATEPATH . "/index.php" );
			}	
		}
	}

	do_action( 'get_footer' );
	load_template( TEMPLATEPATH . "/footer.php" );
	die;
}


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
		bp_catch_uri( 'profile/index' );
}

function bp_core_force_buddypress_theme() {
	global $current_component, $current_action;
	global $is_member_page;
	
	$member_theme = get_site_option('active-member-theme');
	
	if ( $member_theme == '' )
		$member_theme = 'buddypress-member';
	
	// The theme filter does not recognize any globals, where as the stylesheet filter does.
	// We have to set up the globals to use manually.
	bp_core_set_uri_globals();
	
	if ( function_exists('groups_setup_globals') )
		$groups_bp = groups_setup_globals(true);

	if ( $current_component == $groups_bp['groups']['slug'] )
		$is_single_group = BP_Groups_Group::group_exists( $current_action, $groups_bp['groups']['table_name'] );
	
	if ( $is_member_page || ( $current_component == $groups_bp['groups']['slug'] && $is_single_group ) ) {
		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

		return $member_theme;
	} else {
		return get_option('template');
	}
	
	return $theme;
}
add_filter( 'template', 'bp_core_force_buddypress_theme' );

function bp_core_force_buddypress_stylesheet() {
	global $bp, $is_single_group, $is_member_page;

	$member_theme = get_site_option('active-member-theme');
	
	if ( $member_theme == '' )
		$member_theme = 'buddypress-member';
	
	if ( $is_member_page || ( $bp['current_component'] == $bp['groups']['slug'] && $is_single_group ) ) {
		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );
		
		return $member_theme;
	} else {
		return get_option('stylesheet');	
	}
}
add_filter( 'stylesheet', 'bp_core_force_buddypress_stylesheet' );



?>