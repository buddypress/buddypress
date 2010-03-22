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
	global $displayed_user_id, $bp_pages;
	global $is_member_page;
	global $bp_unfiltered_uri, $bp_unfiltered_uri_offset;
	global $bp, $current_blog;

	/* Fetch all the WP page names for each component */
	if ( empty( $bp_pages ) )
		$bp_pages = bp_core_get_page_names();

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

	/* Find a match within registered BuddyPress controlled WP pages (check members first) */
	foreach ( (array)$bp_pages as $page_key => $bp_page ) {
		if ( in_array( $bp_page->name, (array)$bp_uri ) ) {
			/* Match found, now match the slug to make sure. */
			$uri_chunks = explode( '/', $bp_page->slug );

			foreach ( (array)$uri_chunks as $key => $uri_chunk ) {
				if ( $bp_uri[$key] == $uri_chunk ) {
					$matches[] = 1;
				} else {
					$matches[] = 0;
				}
			}

			if ( !in_array( 0, (array) $matches ) ) {
				$match = $bp_page;
				$match->key = $page_key;
				break;
			};

			unset( $matches );
		}

		unset( $uri_chunks );
	}

	/* This is not a BuddyPress page, so just return. */
	if ( in_array( 0, (array) $matches ) )
		return false;

	/* Find the offset */
	$uri_offset = 0;
	$slug = explode( '/', $match->slug );

	if ( !empty( $slug ) && 1 != count( $slug ) ) {
		array_pop( $slug );
		$uri_offset = count( $slug );
	}

	/* Global the unfiltered offset to use in bp_core_load_template() */
	$bp_unfiltered_uri_offset = $uri_offset;

	/* This is a members page so lets check if we have a displayed member */
	if ( 'members' == $match->key ) {
		if ( !empty( $bp_uri[$uri_offset + 1] ) ) {
			if ( defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) )
				$displayed_user_id = (int) bp_core_get_userid( $bp_uri[$uri_offset + 1] );
			else
				$displayed_user_id = (int) bp_core_get_userid_from_nicename( $bp_uri[$uri_offset + 1] );

			$uri_offset = $uri_offset + 2;

			/* Remove everything from the URI up to the offset and take it from there. */
			for ( $i = 0; $i < $uri_offset; $i++ ) {
				unset( $bp_uri[$i] );
			}

			$current_component = $bp_uri[$uri_offset];
		}
	}

	/* Reset the keys by merging with an empty array */
	$bp_uri = array_merge( array(), $bp_uri );

	/* Set the current component */
	if ( empty( $current_component ) ) {
		for ( $i = 0; $i <= $uri_offset; $i++ ) {
			if ( !empty( $bp_uri[$i] ) ) {
				$current_component .= $bp_uri[$i];

				if ( $i != $uri_offset )
					$current_component .= '/';
			}
		}
	} else
		$i = 1;

	/* Set the current action */
	$current_action = $bp_uri[$i];

	/* Unset the current_component and action from action_variables */
	for ( $j = 0; $j <= $i; $j++ )
		unset( $bp_uri[$j] );

	/* Set the entire URI as the action variables, we will unset the current_component and action in a second */
	$action_variables = $bp_uri;

	/* Remove the username from action variables if this is not a VHOST install */
	if ( 'no' == VHOST && !$is_root_component )
		array_shift($bp_uri);

	/* Reset the keys by merging with an empty array */
	$action_variables = array_merge( array(), $action_variables );

	//var_dump($current_component, $current_action, $bp_uri);
}
add_action( 'plugins_loaded', 'bp_core_set_uri_globals', 3 );

/**
 * bp_core_load_template()
 *
 * Load a specific template file with fallback support.
 *
 * Example:
 *   bp_core_load_template( 'members/index' );
 * Loads:
 *   wp-content/themes/[activated_theme]/members/index.php
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_load_template( $templates ) {
	global $bp, $wpdb, $wp_query, $bp_unfiltered_uri, $bp_unfiltered_uri_offset;

	/* Determine if the root object WP page exists for this request (is there an API function for this?)*/
	if ( !$page_exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s", $bp_unfiltered_uri[$bp_unfiltered_uri_offset] ) ) )
		return false;

	/* Set the root object as the current wp_query-ied item */
	foreach ( $bp->pages as $page ) {
		if ( $page->name == $bp_unfiltered_uri[$bp_unfiltered_uri_offset] )
			$object_id = $page->id;
	}

	$wp_query->queried_object = &get_post( $object_id );
	$wp_query->queried_object_id = $object_id;

	/* Fetch each template and add the php suffix */
	foreach ( (array)$templates as $template )
		$filtered_templates[] = $template . '.php';

	/* Filter the template locations so that plugins can alter where they are located */
	if ( $located_template = apply_filters( 'bp_located_template', locate_template( (array) $filtered_templates, false ), $filtered_templates ) ) {
		/* Template was located, lets set this as a valid page and not a 404. */
 		status_header( 200 );
		$wp_query->is_page = true;
		$wp_query->is_404 = false;

		load_template( apply_filters( 'bp_load_template', $located_template ) );
	}

	/* Kill any other output after this. */
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
		bp_core_load_template( apply_filters( 'bp_core_template_display_profile', 'members/single/home' ) );
}

?>