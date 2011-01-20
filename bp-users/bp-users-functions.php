<?php

/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/**
 * Define the slugs used for BuddyPress pages, based on the slugs of the WP pages used.
 * These can be overridden manually by defining these slugs in wp-config.php.
 *
 * The fallback values are only used during initial BP page creation, when no slugs have been
 * explicitly defined.
 *
 * @package BuddyPress Core Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_define_slugs() {
	global $bp;

	if ( !defined( 'BP_MEMBERS_SLUG' ) )
		define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );

	if ( !defined( 'BP_REGISTER_SLUG' ) )
		define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );

	if ( !defined( 'BP_ACTIVATION_SLUG' ) )
		define( 'BP_ACTIVATION_SLUG', $bp->pages->activate->slug );

}
add_action( 'bp_setup_globals', 'bp_core_define_slugs' );

/**
 * Return an array of users IDs based on the parameters passed.
 *
 * @package BuddyPress Core
 */
function bp_core_get_users( $args = '' ) {
	global $bp;

	$defaults = array(
		'type'            => 'active', // active, newest, alphabetical, random or popular
		'user_id'         => false,    // Pass a user_id to limit to only friend connections for this user
		'exclude'         => false,    // Users to exclude from results
		'search_terms'    => false,    // Limit to users that match these search terms

		'include'         => false,    // Pass comma separated list of user_ids to limit to only these users
		'per_page'        => 20,       // The number of results to return per page
		'page'            => 1,        // The page to return if limiting per page
		'populate_extras' => true,     // Fetch the last active, where the user is a friend, total friend count, latest update
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_core_get_users', BP_Core_User::get_users( $type, $per_page, $page, $user_id, $include, $search_terms, $populate_extras, $exclude ), $params );
}

/**
 * Returns the domain for the passed user: e.g. http://domain.com/members/andy/
 *
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @param user_id The ID of the user.
 * @uses get_user_meta() WordPress function to get the usermeta for a user.
 */
function bp_core_get_user_domain( $user_id, $user_nicename = false, $user_login = false ) {
	global $bp;

	if ( empty( $user_id ) )
		return;

	if ( !$domain = wp_cache_get( 'bp_user_domain_' . $user_id, 'bp' ) ) {
		$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

		// If we are using a members slug, include it.
		if ( !defined( 'BP_ENABLE_ROOT_PROFILES' ) )
			$domain = $bp->root_domain . '/' . $bp->members->root_slug . '/' . $username;
		else
			$domain = $bp->root_domain . '/' . $username;

		// Add a slash at the end
		$domain = trailingslashit( $domain );

		// Cache the link
		if ( !empty( $domain ) )
			wp_cache_set( 'bp_user_domain_' . $user_id, $domain, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_domain', $domain );
}

/**
 * Fetch everything in the wp_users table for a user, without any usermeta.
 *
 * @package BuddyPress Core
 * @param user_id The ID of the user.
 * @uses BP_Core_User::get_core_userdata() Performs the query.
 */
function bp_core_get_core_userdata( $user_id ) {
	if ( empty( $user_id ) )
		return false;

	if ( !$userdata = wp_cache_get( 'bp_core_userdata_' . $user_id, 'bp' ) ) {
		$userdata = BP_Core_User::get_core_userdata( $user_id );
		wp_cache_set( 'bp_core_userdata_' . $user_id, $userdata, 'bp' );
	}
	return apply_filters( 'bp_core_get_core_userdata', $userdata );
}

/**
 * Returns the user id for the user that is currently being displayed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 *
 * @package BuddyPress Core
 * @uses bp_core_get_userid_from_user_login() Returns the user id for the username passed
 * @return The user id for the user that is currently being displayed, return zero if this is not a user home and just a normal blog.
 */
function bp_core_get_displayed_userid( $user_login ) {
	return apply_filters( 'bp_core_get_displayed_userid', bp_core_get_userid( $user_login ) );
}

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_random_member() {
	global $bp;

	if ( isset( $_GET['random-member'] ) ) {
		$user = bp_core_get_users( array( 'type' => 'random', 'per_page' => 1 ) );
		bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->id ) );
	}
}
add_action( 'wp', 'bp_core_get_random_member' );

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid( $username ) {
	global $wpdb;

	if ( empty( $username ) )
		return false;

	return apply_filters( 'bp_core_get_userid', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) ) );
}

/**
 * Returns the user_id for a user based on their user_nicename.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid_from_nicename( $user_nicename ) {
	global $wpdb;

	if ( empty( $user_nicename ) )
		return false;

	return apply_filters( 'bp_core_get_userid_from_nicename', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_nicename = %s", $user_nicename ) ) );
}

/**
 * Returns the username for a user based on their user id.
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str the username of the matched user.
 */
function bp_core_get_username( $user_id, $user_nicename = false, $user_login = false ) {
	global $bp;

	if ( !$username = wp_cache_get( 'bp_user_username_' . $user_id, 'bp' ) ) {
		if ( empty( $user_nicename ) && empty( $user_login ) ) {
			$ud = false;

			if ( isset( $bp->loggedin_user->id ) && $bp->loggedin_user->id == $user_id )
				$ud = &$bp->loggedin_user->userdata;

			if ( isset( $bp->displayed_user->id ) && $bp->displayed_user->id == $user_id )
				$ud = &$bp->displayed_user->userdata;

			if ( empty( $ud ) ) {
				if ( !$ud = bp_core_get_core_userdata( $user_id ) )
					return false;
			}

			$user_nicename = $ud->user_nicename;
			$user_login = $ud->user_login;
		}

		if ( defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) )
			$username = $user_login;
		else
			$username = $user_nicename;
	}

	// Add this to cache
	if ( !empty( $username ) )
		wp_cache_set( 'bp_user_username_' . $user_id, $username, 'bp' );

	return apply_filters( 'bp_core_get_username', $username );
}

/**
 * Returns the email address for the user based on user ID
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str The email for the matched user.
 */
function bp_core_get_user_email( $uid ) {
	if ( !$email = wp_cache_get( 'bp_user_email_' . $uid, 'bp' ) ) {
		$ud = bp_core_get_core_userdata($uid);
		$email = $ud->user_email;
		wp_cache_set( 'bp_user_email_' . $uid, $email, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_email', $email );
}

/**
 * Returns a HTML formatted link for a user with the user's full name as the link text.
 * eg: <a href="http://andy.domain.com/">Andy Peatling</a>
 * Optional parameters will return just the name, or just the URL, or disable "You" text when
 * user matches the logged in user.
 *
 * [NOTES: This function needs to be cleaned up or split into separate functions]
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @param $no_anchor bool Disable URL and HTML and just return full name. Default false.
 * @param $just_link bool Disable full name and HTML and just return the URL text. Default false.
 * @param $no_you bool Disable replacing full name with "You" when logged in user is equal to the current user. Default false.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @uses bp_fetch_user_fullname() Returns the full name for a user based on user ID.
 * @uses bp_core_get_userurl() Returns the URL for the user with no anchor tag based on user ID
 * @return false on no match
 * @return str The link text based on passed parameters.
 */
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false ) {
	$display_name = bp_core_get_user_displayname( $user_id );

	if ( empty( $display_name ) )
		return false;

	if ( $no_anchor )
		return $display_name;

	if ( !$url = bp_core_get_user_domain( $user_id ) )
		return false;

	if ( $just_link )
		return $url;

	return apply_filters( 'bp_core_get_userlink', '<a href="' . $url . '" title="' . $display_name . '">' . $display_name . '</a>', $user_id );
}


/**
 * Fetch the display name for a user. This will use the "Name" field in xprofile if it is installed.
 * Otherwise, it will fall back to the normal WP display_name, or user_nicename, depending on what has been set.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_cache_get() Will try and fetch the value from the cache, rather than querying the DB again.
 * @uses get_userdata() Fetches the WP userdata for a specific user.
 * @uses xprofile_set_field_data() Will update the field data for a user based on field name and user id.
 * @uses wp_cache_set() Adds a value to the cache.
 * @return str The display name for the user in question.
 */
function bp_core_get_user_displayname( $user_id_or_username ) {
	global $bp;

	if ( !$user_id_or_username )
		return false;

	if ( !is_numeric( $user_id_or_username ) )
		$user_id = bp_core_get_userid( $user_id_or_username );
	else
		$user_id = $user_id_or_username;

	if ( !$user_id )
		return false;

	if ( !$fullname = wp_cache_get( 'bp_user_fullname_' . $user_id, 'bp' ) ) {
		if ( bp_is_active( 'xprofile' ) ) {
			$fullname = xprofile_get_field_data( stripslashes( $bp->site_options['bp-xprofile-fullname-field-name'] ), $user_id );

			if ( empty($fullname) ) {
				$ud = bp_core_get_core_userdata( $user_id );

				if ( !empty( $ud->display_name ) )
					$fullname = $ud->display_name;
				else
					$fullname = $ud->user_nicename;

				xprofile_set_field_data( 1, $user_id, $fullname );
			}
		} else {
			$ud = bp_core_get_core_userdata($user_id);

			if ( !empty( $ud->display_name ) )
				$fullname = $ud->display_name;
			else
				$fullname = $ud->user_nicename;
		}

		if ( !empty( $fullname ) )
			wp_cache_set( 'bp_user_fullname_' . $user_id, $fullname, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_displayname', $fullname, $user_id );
}
add_filter( 'bp_core_get_user_displayname', 'strip_tags', 1 );
add_filter( 'bp_core_get_user_displayname', 'trim'          );
add_filter( 'bp_core_get_user_displayname', 'stripslashes'  );


/**
 * Returns the user link for the user based on user email address
 *
 * @package BuddyPress Core
 * @param $email str The email address for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @uses get_user_by_email() WordPress function to get userdata via an email address
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by_email( $email );
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false, true ) );
}

/**
 * Returns the user link for the user based on user's username
 *
 * @package BuddyPress Core
 * @param $username str The username for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	global $wpdb;

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) );
	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false, true ) );
}

/**
 * Returns the total number of members for the installation.
 *
 * @package BuddyPress Core
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb, $bp;

	if ( !$count = wp_cache_get( 'bp_total_member_count', 'bp' ) ) {
		$status_sql = bp_core_get_status_sql();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM " . CUSTOM_USER_TABLE . " WHERE {$status_sql}" ) );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Checks if the user has been marked as a spammer.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if spammer, 0 if not.
 */
function bp_core_is_user_spammer( $user_id ) {
	global $wpdb;

	if ( is_multisite() )
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT spam FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );
	else
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_status FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );

	return apply_filters( 'bp_core_is_user_spammer', $is_spammer );
}

/**
 * Checks if the user has been marked as deleted.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if deleted, 0 if not.
 */
function bp_core_is_user_deleted( $user_id ) {
	global $wpdb;

	return apply_filters( 'bp_core_is_user_spammer', (int) $wpdb->get_var( $wpdb->prepare( "SELECT deleted FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) ) );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress user data for the current logged in user.
 * @return array of post ids.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $bp, $wpdb;

	if ( empty( $user_id ) )
		$user_id = $bp->displayed_user->id;

	return apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
}

/**
 * Allows a user to completely remove their account from the system
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses is_super_admin() Checks to see if the user is a site administrator.
 * @uses wpmu_delete_user() Deletes a user from the system on multisite installs.
 * @uses wp_delete_user() Deletes a user from the system on singlesite installs.
 * @uses get_site_option Checks if account deletion is allowed
 */
function bp_core_delete_account( $user_id = false ) {
	global $bp, $wp_version;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Make sure account deletion is not disabled
	if ( !empty( $bp->site_options['bp-disable-account-deletion'] ) && !$bp->loggedin_user->is_super_admin )
		return false;

	// Site admins cannot be deleted
	if ( is_super_admin( bp_core_get_username( $user_id ) ) )
		return false;

	// Specifically handle multi-site environment
	if ( is_multisite() ) {
		if ( $wp_version >= '3.0' )
			require_once( ABSPATH . '/wp-admin/includes/ms.php' );
		else
			require_once( ABSPATH . '/wp-admin/includes/mu.php' );

		require_once( ABSPATH . '/wp-admin/includes/user.php' );

		return wpmu_delete_user( $user_id );

	// Single site user deletion
	} else {
		require_once( ABSPATH . '/wp-admin/includes/user.php' );
		return wp_delete_user( $user_id );
	}
}

/**
 * Localization safe ucfirst() support.
 *
 * @package BuddyPress Core
 */
function bp_core_ucfirst( $str ) {
	if ( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$fc = mb_strtoupper( mb_substr( $str, 0, 1 ) );
		return $fc.mb_substr( $str, 1 );
	} else {
		return ucfirst( $str );
	}
}

/**
 * Strips spaces from usernames that are created using add_user() and wp_insert_user()
 *
 * @package BuddyPress Core
 */
function bp_core_strip_username_spaces( $username ) {
	// Don't alter the user_login of existing users, as it causes user_nicename problems.
	// See http://trac.buddypress.org/ticket/2642
	if ( username_exists( $username ) && ( !defined( 'BP_ENABLE_USER_COMPATIBILITY_MODE' ) || !BP_ENABLE_USER_COMPATIBILITY_MODE ) )
		return $username;

	return str_replace( ' ', '-', $username );
}
add_action( 'pre_user_login', 'bp_core_strip_username_spaces' );

/**
 * When a user logs in, check if they have been marked as a spammer. If yes then simply
 * redirect them to the home page and stop them from logging in.
 *
 * @package BuddyPress Core
 * @param $auth_obj The WP authorization object
 * @param $username The username of the user logging in.
 * @uses get_userdatabylogin() Get the userdata object for a user based on their username
 * @uses bp_core_redirect() Safe redirect to a page
 * @return $auth_obj If the user is not a spammer, return the authorization object
 */
function bp_core_boot_spammer( $auth_obj, $username ) {
	global $bp;

	if ( !$user = get_userdatabylogin( $username ) )
		return false;

	if ( ( is_multisite() && (int)$user->spam ) || 1 == (int)$user->user_status )
		return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been marked as a spammer.', 'buddypress' ) );
	else
		return $auth_obj;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 30, 2 );

/**
 * Deletes usermeta for the user when the user is deleted.
 *
 * @package BuddyPress Core
 * @param $user_id The user id for the user to delete usermeta for
 * @uses delete_user_meta() deletes a row from the wp_usermeta table based on meta_key
 */
function bp_core_remove_data( $user_id ) {
	// Remove usermeta
	delete_user_meta( $user_id, 'last_activity' );

	// Flush the cache to remove the user from all cached objects
	wp_cache_flush();
}
add_action( 'wpmu_delete_user',  'bp_core_remove_data' );
add_action( 'delete_user',       'bp_core_remove_data' );
add_action( 'bp_make_spam_user', 'bp_core_remove_data' );

function bp_users_can_edit_settings() {
	if ( bp_is_my_profile() )
		return true;

	if ( is_super_admin() )
		return true;

	return false;
}

?>
