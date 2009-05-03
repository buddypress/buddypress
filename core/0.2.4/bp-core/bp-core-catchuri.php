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
 *   - VHOST: http:// andy.domain.com / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 *   - NO VHOST: http:// domain.com / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 * 
 * Example:
 *    - http://andy.domain.com/profile/edit/group/5/
 *    - $current_component: string 'profile'
 *    - $current_action: string 'edit'
 *    - $action_variables: array ['group', 5]
 * 
 * @package BuddyPress Core
 * @global $menu WordPress admin navigation array global
 * @global $submenu WordPress admin sub navigation array global
 * @global $thirdlevel BuddyPress admin third level navigation
 * @uses add_menu_page() WordPress function to add a new top level admin navigation tab
 */
function bp_core_set_uri_globals() {
	global $current_component, $current_action, $action_variables;
	
	/* Set the indexes, these are incresed by one if we are not on a VHOST install */
	$component_index = 0;
	$action_index = 1;

	if ( VHOST == 'no' ) {
		$component_index++;
		$action_index++;
	}

	/* Fetch the current URI and explode each part seperated by '/' into an array */
	$bp_uri = explode( "/", $_SERVER['REQUEST_URI'] );

	/* Take empties off the end */
	if ( $bp_uri[count($bp_uri) - 1] == "" )
		array_pop( $bp_uri );

	/* Take empties off the start */
	if ( $bp_uri[0] == "" )
		array_shift( $bp_uri );

	/* Get total URI segment count */
	$bp_uri_count = count( $bp_uri ) - 1;
	
	/* Set the current component */
	$current_component = $bp_uri[$component_index];
	
	/* Set the current action */
	$current_action = $bp_uri[$action_index];

	/* Set the entire URI as the action variables, we will unset the current_component and action in a second */
	$action_variables = $bp_uri;

	/* Remove the username from action variables if this is not a VHOST install */
	if ( VHOST == 'no' )
		unset($action_variables[0]);

	/* Unset the current_component and action from action_variables */
	unset($action_variables[$component_index]);
	unset($action_variables[$action_index]);
	
	/* Reset the keys by merging with an empty array */
	$action_variables = array_merge( array(), $action_variables );

}
add_action( 'wp', 'bp_core_set_uri_globals', 0 );

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
function bp_catch_uri( $pages ) {
	global $bp_path;

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

	$pages = $bp_path;
	
	if ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		if ( !file_exists( TEMPLATEPATH . "/header.php" ) || !file_exists( TEMPLATEPATH . "/footer.php" ) )
			wp_die( 'Please make sure your BuddyPress enabled theme includes a header.php and footer.php file.');

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
				load_template( TEMPLATEPATH . "/index.php" );
			}
		}
	
		do_action( 'get_footer' );
		load_template( TEMPLATEPATH . "/footer.php" );
		die;
	}
}
?>