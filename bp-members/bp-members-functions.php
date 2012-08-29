<?php

/**
 * BuddyPress Member Functions
 *
 * Functions specific to the members component.
 *
 * @package BuddyPress
 * @subpackage MembersFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Checks $bp pages global and looks for directory page
 *
 * @since BuddyPress (1.5)
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @return bool True if set, False if empty
 */
function bp_members_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->members->id );
}

/**
 * Define the slugs used for BuddyPress pages, based on the slugs of the WP pages used.
 * These can be overridden manually by defining these slugs in wp-config.php.
 *
 * The fallback values are only used during initial BP page creation, when no slugs have been
 * explicitly defined.
 *
 * @package BuddyPress Core Core
 * @global BuddyPress $bp The one true BuddyPress instance
 */
function bp_core_define_slugs() {
	global $bp;

	// No custom members slug
	if ( !defined( 'BP_MEMBERS_SLUG' ) ) {
		if ( !empty( $bp->pages->members ) ) {
			define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );
		} else {
			define( 'BP_MEMBERS_SLUG', 'members' );
		}
	}

	// No custom registration slug
	if ( !defined( 'BP_REGISTER_SLUG' ) ) {
		if ( !empty( $bp->pages->register ) ) {
			define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );
		} else {
			define( 'BP_REGISTER_SLUG', 'register' );
		}
	}

	// No custom activation slug
	if ( !defined( 'BP_ACTIVATION_SLUG' ) ) {
		if ( !empty( $bp->pages->activate ) ) {
			define( 'BP_ACTIVATION_SLUG', $bp->pages->activate->slug );
		} else {
			define( 'BP_ACTIVATION_SLUG', 'activate' );
		}
	}
}
add_action( 'bp_setup_globals', 'bp_core_define_slugs', 11 );

/**
 * Return an array of users IDs based on the parameters passed.
 *
 * @package BuddyPress Core
 */
function bp_core_get_users( $args = '' ) {

	$defaults = array(
		'type'            => 'active', // active, newest, alphabetical, random or popular
		'user_id'         => false,    // Pass a user_id to limit to only friend connections for this user
		'exclude'         => false,    // Users to exclude from results
		'search_terms'    => false,    // Limit to users that match these search terms
		'meta_key'        => false,    // Limit to users who have this piece of usermeta
		'meta_value'      => false,    // With meta_key, limit to users where usermeta matches this value

		'include'         => false,    // Pass comma separated list of user_ids to limit to only these users
		'per_page'        => 20,       // The number of results to return per page
		'page'            => 1,        // The page to return if limiting per page
		'populate_extras' => true,     // Fetch the last active, where the user is a friend, total friend count, latest update
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_core_get_users', BP_Core_User::get_users( $type, $per_page, $page, $user_id, $include, $search_terms, $populate_extras, $exclude, $meta_key, $meta_value ), $params );
}

/**
 * Returns the domain for the passed user: e.g. http://domain.com/members/andy/
 *
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @param user_id The ID of the user.
 */
function bp_core_get_user_domain( $user_id, $user_nicename = false, $user_login = false ) {

	if ( empty( $user_id ) )
		return;

	if ( !$domain = wp_cache_get( 'bp_user_domain_' . $user_id, 'bp' ) ) {
		$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

		if ( bp_is_username_compatibility_mode() )
			$username = rawurlencode( $username );

		$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
		$domain       = trailingslashit( bp_get_root_domain() . '/' . $after_domain );
		$domain       = apply_filters( 'bp_core_get_user_domain_pre_cache', $domain, $user_id, $user_nicename, $user_login );

		// Cache the link
		if ( !empty( $domain ) ) {
			wp_cache_set( 'bp_user_domain_' . $user_id, $domain, 'bp' );
		}
	}

	return apply_filters( 'bp_core_get_user_domain', $domain, $user_id, $user_nicename, $user_login );
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
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid( $username ) {
	global $wpdb;

	if ( empty( $username ) )
		return false;

	return apply_filters( 'bp_core_get_userid', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login = %s", $username ) ) );
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

	return apply_filters( 'bp_core_get_userid_from_nicename', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s", $user_nicename ) ) );
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

		// Cache not found so prepare to update it
		$update_cache = true;

		// Nicename and login were not passed
		if ( empty( $user_nicename ) && empty( $user_login ) ) {

			// User ID matches logged in user
			if ( bp_loggedin_user_id() == $user_id ) {
				$userdata = &$bp->loggedin_user->userdata;

			// User ID matches displayed in user
			} elseif ( bp_displayed_user_id() == $user_id ) {
				$userdata = &$bp->displayed_user->userdata;

			// No user ID match
			} else {
				$userdata = false;
			}

			// No match so go dig
			if ( empty( $userdata ) ) {

				// User not found so return false
				if ( !$userdata = bp_core_get_core_userdata( $user_id ) ) {
					return false;
				}
			}

			// Update the $user_id for later
			$user_id       = $userdata->ID;

			// Two possible options
			$user_nicename = $userdata->user_nicename;
			$user_login    = $userdata->user_login;
		}

		// Pull an audible and maybe use the login over the nicename
		$username = bp_is_username_compatibility_mode() ? $user_login : $user_nicename;

	// Username found in cache so don't update it again
	} else {
		$update_cache = false;
	}

	// Check $username for empty spaces and default to nicename if found
	if ( strstr( $username, ' ' ) )
		$username = bp_members_get_user_nicename( $user_id );

	// Add this to cache
	if ( ( true == $update_cache ) && !empty( $username ) )
		wp_cache_set( 'bp_user_username_' . $user_id, $username, 'bp' );

	return apply_filters( 'bp_core_get_username', $username );
}

/**
 * Returns the user_nicename for a user based on their user_id. This should be
 * used for linking to user profiles and anywhere else a sanitized and unique
 * slug to a user is needed.
 *
 * @since BuddyPress (1.5)
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str the username of the matched user.
 */
function bp_members_get_user_nicename( $user_id ) {
	global $bp;

	if ( !$user_nicename = wp_cache_get( 'bp_members_user_nicename_' . $user_id, 'bp' ) ) {
		$update_cache = true;

		// User ID matches logged in user
		if ( bp_loggedin_user_id() == $user_id ) {
			$userdata = &$bp->loggedin_user->userdata;

		// User ID matches displayed in user
		} elseif ( bp_displayed_user_id() == $user_id ) {
			$userdata = &$bp->displayed_user->userdata;

		// No user ID match
		} else {
			$userdata = false;
		}

		// No match so go dig
		if ( empty( $userdata ) ) {

			// User not found so return false
			if ( !$userdata = bp_core_get_core_userdata( $user_id ) ) {
				return false;
			}
		}

		// User nicename found
		$user_nicename = $userdata->user_nicename;

	// Nicename found in cache so don't update it again
	} else {
		$update_cache = false;
	}

	// Add this to cache
	if ( true == $update_cache && !empty( $user_nicename ) )
		wp_cache_set( 'bp_members_user_nicename_' . $user_id, $user_nicename, 'bp' );

	return apply_filters( 'bp_members_get_user_nicename', $user_nicename );
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
		// User exists
		if ( $ud = bp_core_get_core_userdata( $uid ) )
			$email = $ud->user_email;

		// User was deleted
		else
			$email = '';

		wp_cache_set( 'bp_user_email_' . $uid, $email, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_email', $email );
}

/**
 * Returns a HTML formatted link for a user with the user's full name as the link text.
 * eg: <a href="http://andy.domain.com/">Andy Peatling</a>
 * Optional parameters will return just the name or just the URL.
 *
 * @param int $user_id User ID to check.
 * @param $no_anchor bool Disable URL and HTML and just return full name. Default false.
 * @param $just_link bool Disable full name and HTML and just return the URL text. Default false.
 * @return false on no match
 * @return str The link text based on passed parameters.
 * @todo This function needs to be cleaned up or split into separate functions
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
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses wp_cache_get() Will try and fetch the value from the cache, rather than querying the DB again.
 * @uses get_userdata() Fetches the WP userdata for a specific user.
 * @uses xprofile_set_field_data() Will update the field data for a user based on field name and user id.
 * @uses wp_cache_set() Adds a value to the cache.
 * @return str The display name for the user in question.
 */
function bp_core_get_user_displayname( $user_id_or_username ) {
	global $bp;

	$fullname = '';

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
			$fullname = xprofile_get_field_data( 1, $user_id );

			if ( empty($fullname) ) {
				$ud = bp_core_get_core_userdata( $user_id );

				if ( !empty( $ud->display_name ) )
					$fullname = $ud->display_name;
				elseif ( !empty( $ud->user_nicename ) )
					$fullname = $ud->user_nicename;

				xprofile_set_field_data( 1, $user_id, $fullname );
			}
		} else {
			$ud = bp_core_get_core_userdata($user_id);

			if ( !empty( $ud->display_name ) )
				$fullname = $ud->display_name;
			elseif ( !empty( $ud->user_nicename ) )
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
add_filter( 'bp_core_get_user_displayname', 'esc_html'      );

/**
 * Returns the user link for the user based on user email address
 *
 * @package BuddyPress Core
 * @param $email str The email address for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @uses get_user_by() WordPress function to get userdata via an email address
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by( 'email', $email );
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false, true ) );
}

/**
 * Returns the user link for the user based on the supplied identifier
 *
 * @param $username str If BP_ENABLE_USERNAME_COMPATIBILITY_MODE is set, this will be user_login, otherwise it will be user_nicename.
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	if ( bp_is_username_compatibility_mode() )
		$user_id = bp_core_get_userid( $username );
	else
		$user_id = bp_core_get_userid_from_nicename( $username );

	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false, true ) );
}

/**
 * Returns the total number of members for the installation.
 *
 * @package BuddyPress Core
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb;

	if ( !$count = wp_cache_get( 'bp_total_member_count', 'bp' ) ) {
		$status_sql = bp_core_get_status_sql();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE {$status_sql}" ) );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Returns the total number of members, limited to those members with last_activity
 *
 * @return int The number of active members
 */
function bp_core_get_active_member_count() {
	global $wpdb;

	if ( !$count = get_transient( 'bp_active_member_count' ) ) {
		// Avoid a costly join by splitting the lookup
		if ( is_multisite() ) {
			$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE (user_status != 0 OR deleted != 0 OR user_status != 0)" );
		} else {
			$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_status != 0" );
		}

		$exclude_users = $wpdb->get_col( $sql );
		$exclude_users_sql = !empty( $exclude_users ) ? $wpdb->prepare( "AND user_id NOT IN (" . implode( ',', wp_parse_id_list( $exclude_users ) ) . ")" ) : '';

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM $wpdb->usermeta WHERE meta_key = %s {$exclude_users_sql}", bp_get_user_meta_key( 'last_activity' ) ) );
		set_transient( 'bp_active_member_count', $count );
	}

	return apply_filters( 'bp_core_get_active_member_count', $count );
}

/**
 * Processes a spammed or unspammed user
 *
 * This function is called in three ways:
 *  - in bp_settings_action_capabilities() (from the front-end)
 *  - by bp_core_mark_user_spam_admin()    (from wp-admin)
 *  - bp_core_mark_user_ham_admin()        (from wp-admin)
 *
 * @since BuddyPress (1.6)
 *
 * @param int $user_id The user being spammed/hammed
 * @param string $status 'spam' if being marked as spam, 'ham' otherwise
 */
function bp_core_process_spammer_status( $user_id, $status ) {
	global $wpdb;

	// Only super admins can currently spam users
	if ( !is_super_admin() || bp_is_my_profile() )
		return;

	// Bail if no user ID
	if ( empty( $user_id ) )
		return;

	// Bail if user ID is super admin
	if ( is_super_admin( $user_id ) )
		return;

	// Get the functions file
	if ( is_multisite() ) {
		require_once( ABSPATH . 'wp-admin/includes/ms.php' );
	}

	$is_spam = ( 'spam' == $status );

	// Only you can prevent infinite loops
	remove_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	remove_action( 'make_ham_user',  'bp_core_mark_user_ham_admin'  );

	// When marking as spam in the Dashboard, these actions are handled by WordPress
	if ( !is_admin() ) {

		// Get the blogs for the user
		$blogs = get_blogs_of_user( $user_id, true );

		foreach ( (array) $blogs as $key => $details ) {

			// Do not mark the main or current root blog as spam
			if ( 1 == $details->userblog_id || bp_get_root_blog_id() == $details->userblog_id ) {
				continue;
			}

			// Update the blog status
			update_blog_status( $details->userblog_id, 'spam', $is_spam );
		}

		// Finally, mark this user as a spammer
		if ( is_multisite() ) {
			update_user_status( $user_id, 'spam', $is_spam );
		}

		// Always set single site status
		$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $user_id ) );

		// Call multisite actions in single site mode for good measure
		if ( !is_multisite() ) {
			$wp_action = ( true === $is_spam ) ? 'make_spam_user' : 'make_ham_user';
			do_action( $wp_action, bp_displayed_user_id() );
		}
	}

	// Hide this user's activity
	if ( ( true === $is_spam ) && bp_is_active( 'activity' ) ) {
		bp_activity_hide_user_activity( $user_id );
	}

	// We need a special hook for is_spam so that components can delete data at spam time
	$bp_action = ( true === $is_spam ) ? 'bp_make_spam_user' : 'bp_make_ham_user';
	do_action( $bp_action, $user_id );

	// Allow plugins to do neat things
	do_action( 'bp_core_process_spammer_status', $user_id, $is_spam );

	return true;
}

/**
 * Hook to WP's make_spam_user and run our custom BP spam functions
 *
 * @since BuddyPress (1.6)
 *
 * @param int $user_id The user id passed from the make_spam_user hook
 */
function bp_core_mark_user_spam_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'spam' );
}
add_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );

/**
 * Hook to WP's make_ham_user and run our custom BP spam functions
 *
 * @since BuddyPress (1.6)
 *
 * @param int $user_id The user id passed from the make_ham_user hook
 */
function bp_core_mark_user_ham_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'ham' );
}
add_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

/**
 * Checks if the user has been marked as a spammer.
 *
 * @package BuddyPress Core
 * @param int $user_id int The id for the user.
 * @return bool True if spammer, False if not.
 */
function bp_is_user_spammer( $user_id = 0 ) {

	// No user to check
	if ( empty( $user_id ) )
		return false;

	// Assume user is not spam
	$is_spammer = false;

	// Get user data
	$user = get_userdata( $user_id );

	// No user found
	if ( empty( $user ) ) {
		$is_spammer = false;

	// User found
	} else {

		// Check if spam
		if ( !empty( $user->spam ) )
			$is_spammer = true;

		if ( 1 == $user->user_status )
			$is_spammer = true;
	}

	return apply_filters( 'bp_is_user_spammer', (bool) $is_spammer );
}

/**
 * Checks if the user has been marked as deleted.
 *
 * @package BuddyPress Core
 * @param int $user_id int The id for the user.
 * @return bool True if deleted, False if not.
 */
function bp_is_user_deleted( $user_id = 0 ) {

	// No user to check
	if ( empty( $user_id ) )
		return false;

	// Assume user is not deleted
	$is_deleted = false;

	// Get user data
	$user = get_userdata( $user_id );

	// No user found
	if ( empty( $user ) ) {
		$is_deleted = true;

	// User found
	} else {

		// Check if deleted
		if ( !empty( $user->deleted ) )
			$is_deleted = true;

		if ( 2 == $user->user_status )
			$is_deleted = true;

	}

	return apply_filters( 'bp_is_user_deleted', (bool) $is_deleted );
}

/**
 * Checks if user is active
 *
 * @since BuddyPress (1.6)
 *
 * @uses is_user_logged_in() To check if user is logged in
 * @uses bp_loggedin_user_id() To get current user ID
 * @uses bp_is_user_spammer() To check if user is spammer
 * @uses bp_is_user_deleted() To check if user is deleted
 *
 * @param int $user_id The user ID to check
 * @return bool True if public, false if not
 */
function bp_is_user_active( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) && is_user_logged_in() )
		$user_id = bp_loggedin_user_id();

	// No user to check
	if ( empty( $user_id ) )
		return false;

	// Check spam
	if ( bp_is_user_spammer( $user_id ) )
		return false;

	// Check deleted
	if ( bp_is_user_deleted( $user_id ) )
		return false;

	// Assume true if not spam or deleted
	return true;
}

/**
 * Checks if user is not active.
 *
 * @since BuddyPress (1.6)
 *
 * @uses is_user_logged_in() To check if user is logged in
 * @uses bp_get_displayed_user_id() To get current user ID
 * @uses bp_is_user_active() To check if user is active
 *
 * @param int $user_id The user ID to check
 * @return bool True if inactive, false if active
 */
function bp_is_user_inactive( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) && is_user_logged_in() )
		$user_id = bp_loggedin_user_id();

	// No user to check
	if ( empty( $user_id ) )
		return false;

	// Return the inverse of active
	return !bp_is_user_active( $user_id );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * @package BuddyPress Core
 * @global $wpdb WordPress user data for the current logged in user.
 * @return array of post ids.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	return apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
}

/**
 * Allows a user to completely remove their account from the system
 *
 * @package BuddyPress Core
 * @uses wpmu_delete_user() Deletes a user from the system on multisite installs.
 * @uses wp_delete_user() Deletes a user from the system on singlesite installs.
 */
function bp_core_delete_account( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Make sure account deletion is not disabled
	if ( !bp_current_user_can( 'delete_users' ) && bp_disable_account_deletion() )
		return false;

	// Site admins cannot be deleted
	if ( is_super_admin( $user_id ) )
		return false;

	do_action( 'bp_core_pre_delete_account', $user_id );

	// Specifically handle multi-site environment
	if ( is_multisite() ) {
		require( ABSPATH . '/wp-admin/includes/ms.php'   );
		require( ABSPATH . '/wp-admin/includes/user.php' );

		$retval = wpmu_delete_user( $user_id );

	// Single site user deletion
	} else {
		require( ABSPATH . '/wp-admin/includes/user.php' );
		$retval = wp_delete_user( $user_id );
	}

	do_action( 'bp_core_deleted_account', $user_id );

	return $retval;
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
	if ( username_exists( $username ) && ( !bp_is_username_compatibility_mode() ) )
		return $username;

	return str_replace( ' ', '-', $username );
}
add_action( 'pre_user_login', 'bp_core_strip_username_spaces' );

/**
 * When a user logs in, check if they have been marked as a spammer. If yes then simply
 * redirect them to the home page and stop them from logging in.
 *
 * @param obj $user Either the WP_User object or the WP_Error object
 * @return obj If the user is not a spammer, return the WP_User object. Otherwise a new WP_Error object.
 *
 * @since 1.1.2
 */
function bp_core_boot_spammer( $user ) {
	// check to see if the $user has already failed logging in, if so return $user as-is
	if ( is_wp_error( $user ) || empty( $user ) )
		return $user;

	// the user exists; now do a check to see if the user is a spammer
	// if the user is a spammer, stop them in their tracks!
	if ( is_a( $user, 'WP_User' ) && ( ( is_multisite() && (int) $user->spam ) || 1 == $user->user_status ) )
		return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been marked as a spammer.', 'buddypress' ) );

	// user is good to go!
	return $user;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 30 );

/**
 * Deletes usermeta for the user when the user is deleted.
 *
 * @package BuddyPress Core
 * @param $user_id The user id for the user to delete usermeta for
 * @uses bp_delete_user_meta() deletes a row from the wp_usermeta table based on meta_key
 */
function bp_core_remove_data( $user_id ) {

	// Remove usermeta
	bp_delete_user_meta( $user_id, 'last_activity' );

	// Flush the cache to remove the user from all cached objects
	wp_cache_flush();
}
add_action( 'wpmu_delete_user',  'bp_core_remove_data' );
add_action( 'delete_user',       'bp_core_remove_data' );
add_action( 'bp_make_spam_user', 'bp_core_remove_data' );

function bp_core_can_edit_settings() {
	if ( bp_is_my_profile() )
		return true;

	if ( bp_current_user_can( 'bp_moderate' ) || current_user_can( 'edit_users' ) )
		return true;

	return false;
}

/** Sign-up *******************************************************************/

/**
 * Flush illegal names by getting and setting 'illegal_names' site option
 */
function bp_core_flush_illegal_names() {
	$illegal_names = get_site_option( 'illegal_names' );
	update_site_option( 'illegal_names', $illegal_names );
}

/**
 * Filter the illegal_names site option and make sure it includes a few
 * specific BuddyPress and Multi-site slugs
 *
 * @param array|string $value Illegal names from field
 * @param array|string $oldvalue The value as it is currently
 * @return array Merged and unique array of illegal names
 */
function bp_core_get_illegal_names( $value = '', $oldvalue = '' ) {

	// Make sure $value is array
	if ( empty( $value ) )
		$db_illegal_names = array();
	if ( is_array( $value ) )
		$db_illegal_names = $value;
	elseif ( is_string( $value ) )
		$db_illegal_names = explode( ' ', $value );

	// Add the core components' slugs to the banned list even if their components aren't active.
	$bp_component_slugs = array(
		'groups',
		'members',
		'forums',
		'blogs',
		'activity',
		'profile',
		'friends',
		'search',
		'settings',
		'register',
		'activate'
	);

	// Core constants
	$slug_constants = array(
		'BP_GROUPS_SLUG',
		'BP_MEMBERS_SLUG',
		'BP_FORUMS_SLUG',
		'BP_BLOGS_SLUG',
		'BP_ACTIVITY_SLUG',
		'BP_XPROFILE_SLUG',
		'BP_FRIENDS_SLUG',
		'BP_SEARCH_SLUG',
		'BP_SETTINGS_SLUG',
		'BP_REGISTER_SLUG',
		'BP_ACTIVATION_SLUG',
	);
	foreach( $slug_constants as $constant )
		if ( defined( $constant ) )
			$bp_component_slugs[] = constant( $constant );

	// Add our slugs to the array and allow them to be filtered
	$filtered_illegal_names = apply_filters( 'bp_core_illegal_usernames', array_merge( array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' ), $bp_component_slugs ) );

	// Merge the arrays together
	$merged_names           = array_merge( (array) $filtered_illegal_names, (array) $db_illegal_names );

	// Remove duplicates
	$illegal_names          = array_unique( (array) $merged_names );

	return apply_filters( 'bp_core_illegal_names', $illegal_names );
}
add_filter( 'pre_update_site_option_illegal_names', 'bp_core_get_illegal_names', 10, 2 );

/**
 * Check that an email address is valid for use
 *
 * Performs the following checks:
 *   - Is the email address well-formed?
 *   - Is the email address already used?
 *   - If there's an email domain blacklist, is the current domain on it?
 *   - If there's an email domain whitelest, is the current domain on it?
 *
 * @since 1.6.2
 *
 * @param string $user_email The email being checked
 * @return bool|array True if the address passes all checks; otherwise an array
 *   of error codes
 */
function bp_core_validate_email_address( $user_email ) {
	$errors = array();

	$user_email = sanitize_email( $user_email );

	// Is the email well-formed?
	if ( ! is_email( $user_email ) )
		$errors['invalid'] = 1;

	// Is the email on the Banned Email Domains list?
	// Note: This check only works on Multisite
	if ( function_exists( 'is_email_address_unsafe' ) && is_email_address_unsafe( $user_email ) )
		$errors['domain_banned'] = 1;

	// Is the email on the Limited Email Domains list?
	// Note: This check only works on Multisite
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );
		if ( ! in_array( $emaildomain, $limited_email_domains ) ) {
			$errors['domain_not_allowed'] = 1;
		}
	}

	// Is the email alreday in use?
	if ( email_exists( $user_email ) )
		$errors['in_use'] = 1;

	$retval = ! empty( $errors ) ? $errors : true;

	return $retval;
}

/**
 * Validate a user name and email address when creating a new user.
 *
 * @todo Refactor to use bp_core_validate_email_address()
 *
 * @param string $user_name Username to validate
 * @param string $user_email Email address to validate
 * @return array Results of user validation including errors, if any
 */
function bp_core_validate_user_signup( $user_name, $user_email ) {

	$errors = new WP_Error();
	$user_email = sanitize_email( $user_email );

	// Apply any user_login filters added by BP or other plugins before validating
	$user_name = apply_filters( 'pre_user_login', $user_name );

	if ( empty( $user_name ) )
		$errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );

	// Make sure illegal names include BuddyPress slugs and values
	bp_core_flush_illegal_names();

	$illegal_names = get_site_option( 'illegal_names' );

	if ( !validate_username( $user_name ) || in_array( $user_name, (array) $illegal_names ) )
		$errors->add( 'user_name', __( 'Only lowercase letters and numbers allowed', 'buddypress' ) );

	if( strlen( $user_name ) < 4 )
		$errors->add( 'user_name',  __( 'Username must be at least 4 characters', 'buddypress' ) );

	if ( strpos( ' ' . $user_name, '_' ) != false )
		$errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );

	// Is the user_name all numeric?
	$match = array();
	preg_match( '/[0-9]*/', $user_name, $match );

	if ( $match[0] == $user_name )
		$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );

	if ( !is_email( $user_email ) )
		$errors->add( 'user_email', __( 'Please check your email address.', 'buddypress' ) );

	if ( function_exists( 'is_email_address_unsafe' ) && is_email_address_unsafe( $user_email ) )
		$errors->add( 'user_email',  __( 'Sorry, that email address is not allowed!', 'buddypress' ) );

	$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

		if ( in_array( $emaildomain, (array) $limited_email_domains ) == false )
			$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	// Check if the username has been used already.
	if ( username_exists( $user_name ) )
		$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );

	// Check if the email address has been used already.
	if ( email_exists( $user_email ) )
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddypress' ) );

	$result = array( 'user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors );

	// Apply WPMU legacy filter
	$result = apply_filters( 'wpmu_validate_user_signup', $result );

 	return apply_filters( 'bp_core_validate_user_signup', $result );
}

function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( !is_multisite() || !function_exists( 'wpmu_validate_blog_signup' ) )
		return false;

	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	global $bp, $wpdb;

	// Multisite installs have their own install procedure
	if ( is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

		// On multisite, the user id is not created until the user activates the account
		// but we need to cast $user_id to pass to the filters
		$user_id = false;

	} else {
		$errors = new WP_Error();

		$user_id = wp_insert_user( array(
			'user_login' => $user_login,
			'user_pass' => $user_password,
			'display_name' => sanitize_title( $user_login ),
			'user_email' => $user_email
		) );

		if ( is_wp_error( $user_id ) || empty( $user_id ) ) {
			$errors->add( 'registerfail', sprintf( __('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'buddypress' ), bp_get_option( 'admin_email' ) ) );
			return $errors;
		}

		// Update the user status to '2' which we will use as 'not activated' (0 = active, 1 = spam, 2 = not active)
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 2 WHERE ID = %d", $user_id ) );

		// Set any profile data
		if ( bp_is_active( 'xprofile' ) ) {
			if ( !empty( $usermeta['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $usermeta['profile_field_ids'] );

				foreach( (array) $profile_field_ids as $field_id ) {
					if ( empty( $usermeta["field_{$field_id}"] ) )
						continue;

					$current_field = $usermeta["field_{$field_id}"];
					xprofile_set_field_data( $field_id, $user_id, $current_field );

					// Save the visibility level
					$visibility_level = !empty( $usermeta['field_' . $field_id . '_visibility'] ) ? $usermeta['field_' . $field_id . '_visibility'] : 'public';
					xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
				}
			}
		}
	}
	$bp->signup->username = $user_login;

	/***
	 * Now generate an activation key and send an email to the user so they can activate their
	 * account and validate their email address. Multisite installs send their own email, so
	 * this is only for single blog installs.
	 *
	 * To disable sending activation emails you can user the filter
	 * 'bp_core_signup_send_activation_key' and return false. Note that this will only disable
	 * the email - a key will still be generated, and the account must still be activated
	 * before use.
	 */
	if ( !is_multisite() ) {
		$activation_key = wp_hash( $user_id );
		update_user_meta( $user_id, 'activation_key', $activation_key );

		if ( apply_filters( 'bp_core_signup_send_activation_key', true ) ) {
			bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key );
		}
	}

	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( !is_multisite() || !function_exists( 'wpmu_signup_blog' ) )
		return false;

	return apply_filters( 'bp_core_signup_blog', wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) );
}

function bp_core_activate_signup( $key ) {
	global $wpdb;

	$user = false;

	// Multisite installs have their own activation routine
	if ( is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		// If there were errors, add a message and redirect
		if ( !empty( $user->errors ) ) {
			return $user;
		}

		$user_id = $user['user_id'];

		// Set any profile data
		if ( bp_is_active( 'xprofile' ) ) {
			if ( !empty( $user['meta']['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

				foreach( (array) $profile_field_ids as $field_id ) {
					$current_field = isset( $user['meta']["field_{$field_id}"] ) ? $user['meta']["field_{$field_id}"] : false;

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $user_id, $current_field );

					// Save the visibility level
					$visibility_level = !empty( $user['meta']['field_' . $field_id . '_visibility'] ) ? $user['meta']['field_' . $field_id . '_visibility'] : 'public';
					xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
				}
			}
		}
	} else {

		// Get the user_id based on the $key
		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'activation_key' AND meta_value = %s", $key ) );

		if ( empty( $user_id ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		// Change the user's status so they become active
		if ( !$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 0 WHERE ID = %d", $user_id ) ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		// Notify the site admin of a new user registration
		wp_new_user_notification( $user_id );

		// Remove the activation key meta
		delete_user_meta( $user_id, 'activation_key' );
	}

	// Update the display_name
	wp_update_user( array( 'ID' => $user_id, 'display_name' => bp_core_get_user_displayname( $user_id ) ) );

	// Set the password on multisite installs
	if ( is_multisite() && !empty( $user['meta']['password'] ) )
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

function bp_core_new_user_activity( $user ) {
	if ( empty( $user ) || !bp_is_active( 'activity' ) )
		return false;

	if ( is_array( $user ) )
		$user_id = $user['user_id'];
	else
		$user_id = $user;

	if ( empty( $user_id ) )
		return false;

	$userlink = bp_core_get_userlink( $user_id );

	bp_activity_add( array(
		'user_id'   => $user_id,
		'action'    => apply_filters( 'bp_core_activity_registered_member_action', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $user_id ),
		'component' => 'xprofile',
		'type'      => 'new_member'
	) );
}
add_action( 'bp_core_activated_user', 'bp_core_new_user_activity' );

function bp_core_map_user_registration( $user_id ) {

	// Only map data when the site admin is adding users, not on registration.
	if ( !is_admin() )
		return false;

	// Add the user's fullname to Xprofile
	if ( bp_is_active( 'xprofile' ) ) {
		$firstname = bp_get_user_meta( $user_id, 'first_name', true );
		$lastname = ' ' . bp_get_user_meta( $user_id, 'last_name', true );
		$name = $firstname . $lastname;

		if ( empty( $name ) || ' ' == $name )
			$name = bp_get_user_meta( $user_id, 'nickname', true );

		xprofile_set_field_data( 1, $user_id, $name );
	}
}
add_action( 'user_register', 'bp_core_map_user_registration' );

function bp_core_signup_avatar_upload_dir() {
	global $bp;

	if ( !$bp->signup->avatar_dir )
		return false;

	$path  = bp_core_avatar_upload_path() . '/avatars/signups/' . $bp->signup->avatar_dir;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl = bp_core_avatar_url() . '/avatars/signups/' . $bp->signup->avatar_dir;
	$newburl = $newurl;
	$newsubdir = '/avatars/signups/' . $bp->signup->avatar_dir;

	return apply_filters( 'bp_core_signup_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

function bp_core_signup_send_validation_email( $user_id, $user_email, $key ) {
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = esc_url( $activate_url );

	$from_name = ( '' == bp_get_option( 'blogname' ) ) ? __( 'BuddyPress', 'buddypress' ) : esc_html( bp_get_option( 'blogname' ) );

	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%1\$s\n\n", 'buddypress' ), $activate_url );
	$subject = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	// Send the message
	$to      = apply_filters( 'bp_core_signup_send_validation_email_to',     $user_email, $user_id                );
	$subject = apply_filters( 'bp_core_signup_send_validation_email_subject', $subject,    $user_id                );
	$message = apply_filters( 'bp_core_signup_send_validation_email_message', $message,    $user_id, $activate_url );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_core_sent_user_validation_email', $subject, $message, $user_id, $user_email, $key );
}

/**
 * Stop user accounts logging in that have not been activated yet (user_status = 2).
 *
 * Note: This is only applicable for single site WordPress installs.
 * Multisite has their own DB table - 'wp_signups' - dedicated for unactivated users.
 * See {@link wpmu_signup_user()} and {@link wpmu_validate_user_signup()}.
 *
 * @param obj $user Either the WP_User object or the WP_Error object
 * @return obj If the user is not a spammer, return the WP_User object. Otherwise a new WP_Error object.
 *
 * @since 1.2.2
 */
function bp_core_signup_disable_inactive( $user ) {
	// check to see if the $user has already failed logging in, if so return $user as-is
	if ( is_wp_error( $user ) || empty( $user ) )
		return $user;

	// the user exists; now do a check to see if the user has activated their account or not
	// NOTE: this is only applicable for single site WordPress installs!
	// if unactivated, stop the login now!
	if ( is_a( $user, 'WP_User' ) && 2 == $user->user_status )
		return new WP_Error( 'bp_account_not_activated', __( '<strong>ERROR</strong>: Your account has not been activated. Check your email for the activation link.', 'buddypress' ) );

	// user has activated their account! all clear!
	return $user;
}
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 30 );

/**
 * Kill the wp-signup.php if custom registration signup templates are present
 */
function bp_core_wpsignup_redirect() {
	$action = !empty( $_GET['action'] ) ? $_GET['action'] : '';

	if ( is_admin() || is_network_admin() )
		return;

	// Not at the WP core signup page and action is not register
	if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php' ) && ( 'register' != $action ) )
		return;

	// Redirect to sign-up page
	if ( locate_template( array( 'registration/register.php' ), false ) || locate_template( array( 'register.php' ), false ) )
		bp_core_redirect( bp_get_signup_page() );
}
add_action( 'bp_init', 'bp_core_wpsignup_redirect' );

?>
