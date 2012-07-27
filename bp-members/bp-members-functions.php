<?php
/**
 * BuddyPress Member Functions
 *
 * Functions specific to the members component.
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Checks $bp pages global and looks for directory page
 *
 * @since 1.5
 *
 * @global object $bp Global BuddyPress settings object
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
 * @global object $bp Global BuddyPress settings object
 */
function bp_core_define_slugs() {
	global $bp;

	// No custom members slug
	if ( !defined( 'BP_MEMBERS_SLUG' ) )
		if ( !empty( $bp->pages->members ) )
			define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );
		else
			define( 'BP_MEMBERS_SLUG', 'members' );

	// No custom registration slug
	if ( !defined( 'BP_REGISTER_SLUG' ) )
		if ( !empty( $bp->pages->register ) )
			define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );
		else
			define( 'BP_REGISTER_SLUG', 'register' );

	// No custom activation slug
	if ( !defined( 'BP_ACTIVATION_SLUG' ) )
		if ( !empty( $bp->pages->activate ) )
			define( 'BP_ACTIVATION_SLUG', $bp->pages->activate->slug );
		else
			define( 'BP_ACTIVATION_SLUG', 'activate' );

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
	global $bp;

	if ( empty( $user_id ) )
		return;

	if ( !$domain = wp_cache_get( 'bp_user_domain_' . $user_id, 'bp' ) ) {
		$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

		if ( bp_is_username_compatibility_mode() )
			$username = rawurlencode( $username );

		// If we are using a members slug, include it.
		if ( !defined( 'BP_ENABLE_ROOT_PROFILES' ) )
			$domain = bp_get_root_domain() . '/' . bp_get_members_root_slug() . '/' . $username;
		else
			$domain = bp_get_root_domain() . '/' . $username;

		// Add a slash at the end, and filter before caching
		$domain = apply_filters( 'bp_core_get_user_domain_pre_cache', trailingslashit( $domain ), $user_id, $user_nicename, $user_login );

		// Cache the link
		if ( !empty( $domain ) )
			wp_cache_set( 'bp_user_domain_' . $user_id, $domain, 'bp' );
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
			if ( isset( $bp->loggedin_user->id ) && $bp->loggedin_user->id == $user_id ) {
				$userdata = &$bp->loggedin_user->userdata;

			// User ID matches displayed in user
			} elseif ( isset( $bp->displayed_user->id ) && $bp->displayed_user->id == $user_id ) {
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

		// Pull an audible and use the login over the nicename
		if ( bp_is_username_compatibility_mode() )
			$username = $user_login;
		else
			$username = $user_nicename;

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
		if ( isset( $bp->loggedin_user->id ) && $bp->loggedin_user->id == $user_id ) {
			$userdata = &$bp->loggedin_user->userdata;

		// User ID matches displayed in user
		} elseif ( isset( $bp->displayed_user->id ) && $bp->displayed_user->id == $user_id ) {
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
 * @global object $bp Global BuddyPress settings object
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
	global $wpdb, $bp;

	if ( !$count = wp_cache_get( 'bp_total_member_count', 'bp' ) ) {
		$status_sql = bp_core_get_status_sql();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE {$status_sql}" ) );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Processes a spammed or unspammed user
 *
 * This function is called in two ways:
 *  - in bp_core_action_set_spammer_status() (when spamming from the front-end)
 *  - by bp_core_mark_user_spam_admin() or bp_core_mark_user_ham_admin() (when spamming from the
 *    Dashboard)
 *
 * @since 1.5.5
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

	$is_spam = 'spam' == $status;

	// Only you can prevent infinite loops
	remove_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	remove_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

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
			$wp_action = $is_spam ? 'make_spam_user' : 'make_ham_user';
			do_action( $wp_action, bp_displayed_user_id() );
		}
	}

	// Hide this user's activity
	if ( $is_spam && bp_is_active( 'activity' ) ) {
		bp_activity_hide_user_activity( $user_id );
	}

	// We need a special hook for is_spam so that components can delete data at spam time
	$bp_action = $is_spam ? 'bp_make_spam_user' : 'bp_make_ham_user';
	do_action( $bp_action, $user_id );

	// Allow plugins to do neat things
	do_action( 'bp_core_process_spammer_status', $user_id, $is_spam );
	
	return true;
}

/**
 * Hook to WP's make_spam_user and run our custom BP spam functions
 *
 * @since 1.5.5
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
 * @since 1.5.5
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
function bp_core_is_user_spammer( $user_id = 0 ) {
	global $wpdb;

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

	return apply_filters( 'bp_core_is_user_spammer', (bool) $is_spammer );
}

/**
 * Checks if the user has been marked as deleted.
 *
 * @package BuddyPress Core
 * @param int $user_id int The id for the user.
 * @return bool True if deleted, False if not.
 */
function bp_core_is_user_deleted( $user_id = 0 ) {
	global $wpdb;

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

	return apply_filters( 'bp_core_is_user_deleted', (bool) $is_deleted );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 * @global $wpdb WordPress user data for the current logged in user.
 * @return array of post ids.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $bp, $wpdb;

	if ( empty( $user_id ) )
		$user_id = $bp->displayed_user->id;

	return apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
}

/**
 * Allows a user to completely remove their account from the system
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 * @uses is_super_admin() Checks to see if the user is a site administrator.
 * @uses wpmu_delete_user() Deletes a user from the system on multisite installs.
 * @uses wp_delete_user() Deletes a user from the system on singlesite installs.
 */
function bp_core_delete_account( $user_id = 0 ) {
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
			require( ABSPATH . '/wp-admin/includes/ms.php' );
		else
			require( ABSPATH . '/wp-admin/includes/mu.php' );

		require( ABSPATH . '/wp-admin/includes/user.php' );

		return wpmu_delete_user( $user_id );

	// Single site user deletion
	} else {
		require( ABSPATH . '/wp-admin/includes/user.php' );
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
	if ( username_exists( $username ) && ( !bp_is_username_compatibility_mode() ) )
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
 * @uses get_user_by() Get the userdata object for a user based on their username
 * @uses bp_core_redirect() Safe redirect to a page
 * @return $auth_obj If the user is not a spammer, return the authorization object
 */
function bp_core_boot_spammer( $auth_obj, $username ) {
	global $bp;

	if ( !$user = get_user_by( 'login',  $username ) )
		return $auth_obj;

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

	if ( is_super_admin() || current_user_can( 'edit_users' ) )
		return true;

	return false;
}

?>
