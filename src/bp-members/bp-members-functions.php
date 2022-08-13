<?php
/**
 * BuddyPress Member Functions.
 *
 * Functions specific to the members component.
 *
 * @package BuddyPress
 * @subpackage MembersFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check for the existence of a Members directory page.
 *
 * @since 1.5.0
 *
 * @return bool True if found, otherwise false.
 */
function bp_members_has_directory() {
	$bp = buddypress();

	return (bool) ! empty( $bp->pages->members->id );
}

/**
 * Define the slug constants for the Members component.
 *
 * Handles the three slug constants used in the Members component -
 * BP_MEMBERS_SLUG, BP_REGISTER_SLUG, and BP_ACTIVATION_SLUG. If these
 * constants are not overridden in wp-config.php or bp-custom.php, they are
 * defined here to match the slug of the corresponding WP pages.
 *
 * In general, fallback values are only used during initial BP page creation,
 * when no slugs have been explicitly defined.
 *
 * @since 1.5.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 */
function bp_core_define_slugs() {
	$bp = buddypress();

	// No custom members slug.
	if ( !defined( 'BP_MEMBERS_SLUG' ) ) {
		if ( !empty( $bp->pages->members ) ) {
			define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );
		} else {
			define( 'BP_MEMBERS_SLUG', 'members' );
		}
	}

	// No custom registration slug.
	if ( !defined( 'BP_REGISTER_SLUG' ) ) {
		if ( !empty( $bp->pages->register ) ) {
			define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );
		} else {
			define( 'BP_REGISTER_SLUG', 'register' );
		}
	}

	// No custom activation slug.
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
 * Fetch an array of users based on the parameters passed.
 *
 * Since BuddyPress 1.7, bp_core_get_users() uses BP_User_Query. If you
 * need backward compatibility with BP_Core_User::get_users(), filter the
 * bp_use_legacy_user_query value, returning true.
 *
 * @since 1.2.0
 * @since 7.0.0 Added `xprofile_query` parameter. Added `user_ids` parameter.
 *
 * @param array|string $args {
 *     Array of arguments. All are optional. See {@link BP_User_Query} for
 *     a more complete description of arguments.
 *     @type string       $type                Sort order. Default: 'active'.
 *     @type int          $user_id             Limit results to friends of a user. Default: false.
 *     @type mixed        $exclude             IDs to exclude from results. Default: false.
 *     @type string       $search_terms        Limit to users matching search terms. Default: false.
 *     @type string       $meta_key            Limit to users with a meta_key. Default: false.
 *     @type string       $meta_value          Limit to users with a meta_value (with meta_key). Default: false.
 *     @type array|string $member_type         Array or comma-separated string of member types.
 *     @type array|string $member_type__in     Array or comma-separated string of member types.
 *                                             `$member_type` takes precedence over this parameter.
 *     @type array|string $member_type__not_in Array or comma-separated string of member types to be excluded.
 *     @type mixed        $include             Limit results by user IDs. Default: false.
 *     @type mixed        $user_ids            IDs corresponding to the users. Default: false.
 *     @type int          $per_page            Results per page. Default: 20.
 *     @type int          $page                Page of results. Default: 1.
 *     @type bool         $populate_extras     Fetch optional extras. Default: true.
 *     @type array        $xprofile_query      Filter results by xprofile data. Requires the xprofile
 *                                             component. See {@see BP_XProfile_Query} for details.
 *     @type string|bool  $count_total         How to do total user count. Default: 'count_query'.
 * }
 * @return array
 */
function bp_core_get_users( $args = '' ) {

	// Parse the user query arguments.
	$r = bp_parse_args(
		$args,
		array(
			'type'                => 'active',     // Active, newest, alphabetical, random or popular.
			'user_id'             => false,        // Pass a user_id to limit to only friend connections for this user.
			'exclude'             => false,        // Users to exclude from results.
			'search_terms'        => false,        // Limit to users that match these search terms.
			'meta_key'            => false,        // Limit to users who have this piece of usermeta.
			'meta_value'          => false,        // With meta_key, limit to users where usermeta matches this value.
			'member_type'         => '',
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'include'             => false,        // Pass comma separated list of user_ids to limit to only these users.
			'user_ids'            => false,
			'per_page'            => 20,           // The number of results to return per page.
			'page'                => 1,            // The page to return if limiting per page.
			'populate_extras'     => true,         // Fetch the last active, where the user is a friend, total friend count, latest update.
			'xprofile_query'      => false,
			'count_total'         => 'count_query', // What kind of total user count to do, if any. 'count_query', 'sql_calc_found_rows', or false.
		),
		'core_get_users'
	);

	/**
	 * For legacy users. Use of BP_Core_User::get_users() is deprecated.
	 *
	 * Forcing this filter to true will use the legacy user query. As of
	 * BuddyPress 7.0.0, mirroring of the 'last_activity' value to usermeta
	 * is also disabled if true. See bp_update_user_last_activity().
	 *
	 * @since 2.0.0
	 *
	 * @param bool   $retval   Defaults to false.
	 * @param string $function Current function name.
	 * @param array  $r        User query arguments.
	 */
	$use_legacy_query = apply_filters( 'bp_use_legacy_user_query', false, __FUNCTION__, $r );

	if ( $use_legacy_query ) {
		$retval = BP_Core_User::get_users(
			$r['type'],
			$r['per_page'],
			$r['page'],
			$r['user_id'],
			$r['include'],
			$r['search_terms'],
			$r['populate_extras'],
			$r['exclude'],
			$r['meta_key'],
			$r['meta_value']
		);

	// Default behavior as of BuddyPress 1.7.0.
	} else {

		// Get users like we were asked to do...
		$users = new BP_User_Query( $r );

		// ...but reformat the results to match bp_core_get_users() behavior.
		$retval = array(
			'users' => array_values( $users->results ),
			'total' => $users->total_users
		);
	}

	/**
	 * Filters the results of the user query.
	 *
	 * @since 1.2.0
	 *
	 * @param array $retval Array of users for the current query.
	 * @param array $r      Array of parsed query arguments.
	 */
	return apply_filters( 'bp_core_get_users', $retval, $r );
}

/**
 * Return the domain for the passed user: e.g. http://example.com/members/andy/.
 *
 * @since 1.0.0
 *
 * @param int         $user_id       The ID of the user.
 * @param string|bool $user_nicename Optional. user_nicename of the user.
 * @param string|bool $user_login    Optional. user_login of the user.
 * @return string
 */
function bp_core_get_user_domain( $user_id = 0, $user_nicename = false, $user_login = false ) {

	if ( empty( $user_id ) ) {
		return;
	}

	$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

	if ( bp_is_username_compatibility_mode() ) {
		$username = rawurlencode( $username );
	}

	$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
	$domain       = trailingslashit( bp_get_root_domain() . '/' . $after_domain );

	// Don't use this filter.  Subject to removal in a future release.
	// Use the 'bp_core_get_user_domain' filter instead.
	$domain = apply_filters( 'bp_core_get_user_domain_pre_cache', $domain, $user_id, $user_nicename, $user_login );

	/**
	 * Filters the domain for the passed user.
	 *
	 * @since 1.0.1
	 *
	 * @param string $domain        Domain for the passed user.
	 * @param int    $user_id       ID of the passed user.
	 * @param string $user_nicename User nicename of the passed user.
	 * @param string $user_login    User login of the passed user.
	 */
	return apply_filters( 'bp_core_get_user_domain', $domain, $user_id, $user_nicename, $user_login );
}

/**
 * Fetch everything in the wp_users table for a user, without any usermeta.
 *
 * @since 1.2.0
 *
 * @param int $user_id The ID of the user.
 * @return array|bool Array of data on success, false on failure.
 */
function bp_core_get_core_userdata( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Get core user data.
	$userdata = BP_Core_User::get_core_userdata( $user_id );

	/**
	 * Filters the userdata for a passed user.
	 *
	 * @since 1.2.0
	 *
	 * @param array|bool $userdata Array of user data for a passed user on success, false on failure.
	 */
	return apply_filters( 'bp_core_get_core_userdata', $userdata );
}

/**
 * Return the ID of a user, based on user_login.
 *
 * No longer used.
 *
 * @todo Deprecate.
 *
 * @param string $user_login user_login of the user being queried.
 * @return int
 */
function bp_core_get_displayed_userid( $user_login ) {
	return apply_filters( 'bp_core_get_displayed_userid', bp_core_get_userid( $user_login ) );
}

/**
 * Return the user ID based on a user's user_login.
 *
 * @since 1.0.0
 *
 * @param string $username user_login to check.
 * @return int|null The ID of the matched user on success, null on failure.
 */
function bp_core_get_userid( $username = '' ) {
	if ( empty( $username ) ) {
		return false;
	}

	$user = get_user_by( 'login', $username );

	/**
	 * Filters the ID of a user, based on user_login.
	 *
	 * @since 1.0.1
	 *
	 * @param int|null $value    ID of the user or null.
	 * @param string   $username User login to check.
	 */
	return apply_filters( 'bp_core_get_userid', ! empty( $user->ID ) ? $user->ID : null, $username );
}

/**
 * Return the user ID based on a user's user_nicename.
 *
 * @since 1.2.3
 *
 * @param string $user_nicename user_nicename to check.
 * @return int|null The ID of the matched user on success, null on failure.
 */
function bp_core_get_userid_from_nicename( $user_nicename = '' ) {
	if ( empty( $user_nicename ) ) {
		return false;
	}

	$user = get_user_by( 'slug', $user_nicename );

	/**
	 * Filters the user ID based on user_nicename.
	 *
	 * @since 1.2.3
	 *
	 * @param int|null $value         ID of the user or null.
	 * @param string   $user_nicename User nicename to check.
	 */
	return apply_filters( 'bp_core_get_userid_from_nicename', ! empty( $user->ID ) ? $user->ID : null, $user_nicename );
}

/**
 * Return the username for a user based on their user id.
 *
 * This function is sensitive to the BP_ENABLE_USERNAME_COMPATIBILITY_MODE,
 * so it will return the user_login or user_nicename as appropriate.
 *
 * @since 1.0.0
 *
 * @param int         $user_id       User ID to check.
 * @param string|bool $user_nicename Optional. user_nicename of user being checked.
 * @param string|bool $user_login    Optional. user_login of user being checked.
 * @return string The username of the matched user or an empty string if no user is found.
 */
function bp_core_get_username( $user_id = 0, $user_nicename = false, $user_login = false ) {

	if ( ! $user_nicename && ! $user_login ) {
		// Pull an audible and maybe use the login over the nicename.
		if ( bp_is_username_compatibility_mode() ) {
			$username = get_the_author_meta( 'login', $user_id );
		} else {
			$username = get_the_author_meta( 'nicename', $user_id );
		}
	} else {
		$username = bp_is_username_compatibility_mode() ? $user_login : $user_nicename;
	}

	/**
	 * Filters the username based on originally provided user ID.
	 *
	 * @since 1.0.1
	 *
	 * @param string $username Username determined by user ID.
	 */
	return apply_filters( 'bp_core_get_username', $username );
}

/**
 * Return the user_nicename for a user based on their user_id.
 *
 * This should be used for linking to user profiles and anywhere else a
 * sanitized and unique slug to a user is needed.
 *
 * @since 1.5.0
 *
 * @param int $user_id User ID to check.
 * @return string The username of the matched user or an empty string if no user is found.
 */
function bp_members_get_user_nicename( $user_id ) {

	/**
	 * Filters the user_nicename based on originally provided user ID.
	 *
	 * @since 1.5.0
	 *
	 * @param string $username User nice name determined by user ID.
	 */
	return apply_filters( 'bp_members_get_user_nicename', get_the_author_meta( 'nicename', $user_id ) );
}

/**
 * Return the email address for the user based on user ID.
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID to check.
 * @return string The email for the matched user. Empty string if no user
 *                matches the $user_id.
 */
function bp_core_get_user_email( $user_id ) {

	/**
	 * Filters the user email for user based on user ID.
	 *
	 * @since 1.0.1
	 *
	 * @param string $email Email determined for the user.
	 */
	return apply_filters( 'bp_core_get_user_email', get_the_author_meta( 'email', $user_id ) );
}

/**
 * Return a HTML formatted link for a user with the user's full name as the link text.
 *
 * Eg: <a href="http://andy.example.com/">Andy Peatling</a>
 *
 * Optional parameters will return just the name or just the URL.
 *
 * @since 1.0.0
 *
 * @param int  $user_id   User ID to check.
 * @param bool $no_anchor Disable URL and HTML and just return full name.
 *                        Default: false.
 * @param bool $just_link Disable full name and HTML and just return the URL
 *                        text. Default false.
 * @return string|bool The link text based on passed parameters, or false on
 *                     no match.
 */
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false ) {
	$display_name = bp_core_get_user_displayname( $user_id );

	if ( empty( $display_name ) ) {
		return false;
	}

	if ( ! empty( $no_anchor ) ) {
		return $display_name;
	}

	if ( !$url = bp_core_get_user_domain( $user_id ) ) {
		return false;
	}

	if ( ! empty( $just_link ) ) {
		return $url;
	}

	/**
	 * Filters the link text for the passed in user.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value   Link text based on passed parameters.
	 * @param int    $user_id ID of the user to check.
	 */
	return apply_filters( 'bp_core_get_userlink', '<a href="' . esc_url( $url ) . '">' . $display_name . '</a>', $user_id );
}

/**
 * Fetch the display name for a group of users.
 *
 * Uses the 'Name' field in xprofile if available. Falls back on WP
 * display_name, and then user_nicename.
 *
 * @since 2.0.0
 *
 * @param array $user_ids Array of user IDs to get display names for.
 * @return array Associative array of the format "id" => "displayname".
 */
function bp_core_get_user_displaynames( $user_ids ) {

	// Sanitize.
	$user_ids = wp_parse_id_list( $user_ids );

	// Remove dupes and empties.
	$user_ids = array_unique( array_filter( $user_ids ) );

	if ( empty( $user_ids ) ) {
		return array();
	}

	// Warm the WP users cache with a targeted bulk update.
	cache_users( $user_ids );

	$retval = array();
	foreach ( $user_ids as $user_id ) {
		$retval[ $user_id ] = bp_core_get_user_displayname( $user_id );
	}

	return $retval;
}

/**
 * Fetch the display name for a user.
 *
 * @since 1.0.1
 *
 * @param int|string|bool $user_id_or_username User ID or username.
 * @return string|bool The display name for the user in question, or false if
 *                     user not found.
 */
function bp_core_get_user_displayname( $user_id_or_username ) {
	if ( empty( $user_id_or_username ) ) {
		return false;
	}

	if ( ! is_numeric( $user_id_or_username ) ) {
		$user_id = bp_core_get_userid( $user_id_or_username );
	} else {
		$user_id = $user_id_or_username;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filters the display name for the passed in user.
	 *
	 * @since 1.0.1
	 *
	 * @param string $fullname Display name for the user.
	 * @param int    $user_id  ID of the user to check.
	 */
	return apply_filters( 'bp_core_get_user_displayname', get_the_author_meta( 'display_name', $user_id ), $user_id );
}
add_filter( 'bp_core_get_user_displayname', 'strip_tags', 1 );
add_filter( 'bp_core_get_user_displayname', 'trim' );
add_filter( 'bp_core_get_user_displayname', 'stripslashes' );
add_filter( 'bp_core_get_user_displayname', 'esc_html' );

/**
 * Return the user link for the user based on user email address.
 *
 * @since 1.0.0
 *
 * @param string $email The email address for the user.
 * @return string The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by( 'email', $email );

	/**
	 * Filters the user link for the user based on user email address.
	 *
	 * @since 1.0.1
	 *
	 * @param string|bool $value URL for the user if found, otherwise false.
	 */
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false ) );
}

/**
 * Return the user link for the user based on the supplied identifier.
 *
 * @since 1.0.0
 *
 * @param string $username If BP_ENABLE_USERNAME_COMPATIBILITY_MODE is set,
 *                         this should be user_login, otherwise it should
 *                         be user_nicename.
 * @return string|bool The link to the user's domain, false on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	if ( bp_is_username_compatibility_mode() ) {
		$user_id = bp_core_get_userid( $username );
	} else {
		$user_id = bp_core_get_userid_from_nicename( $username );
	}

	/**
	 * Filters the user link for the user based on username.
	 *
	 * @since 1.0.1
	 *
	 * @param string|bool $value URL for the user if found, otherwise false.
	 */
	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false ) );
}

/**
 * Return the total number of members for the installation.
 *
 * Note that this is a raw count of non-spam, activated users. It does not
 * account for users who have logged activity (last_active). See
 * {@link bp_core_get_active_member_count()}.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb;

	$count = wp_cache_get( 'bp_total_member_count', 'bp' );

	if ( false === $count ) {
		$status_sql = bp_core_get_status_sql();
		$count = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->users} WHERE {$status_sql}" );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	/**
	 * Filters the total number of members for the installation.
	 *
	 * @since 1.2.0
	 *
	 * @param int $count Total number of members.
	 */
	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Return the total number of members, limited to those members with last_activity.
 *
 * @since 1.6.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @return int The number of active members.
 */
function bp_core_get_active_member_count() {
	global $wpdb;

	$count = get_transient( 'bp_active_member_count' );
	if ( false === $count ) {
		$bp = buddypress();

		// Avoid a costly join by splitting the lookup.
		if ( is_multisite() ) {
			$sql = "SELECT ID FROM {$wpdb->users} WHERE (user_status != 0 OR deleted != 0 OR user_status != 0)";
		} else {
			$sql = "SELECT ID FROM {$wpdb->users} WHERE user_status != 0";
		}

		$exclude_users     = $wpdb->get_col( $sql );
		$exclude_users_sql = !empty( $exclude_users ) ? "AND user_id NOT IN (" . implode( ',', wp_parse_id_list( $exclude_users ) ) . ")" : '';
		$count             = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity' {$exclude_users_sql}", $bp->members->id ) );

		set_transient( 'bp_active_member_count', $count );
	}

	/**
	 * Filters the total number of members for the installation limited to those with last_activity.
	 *
	 * @since 1.6.0
	 *
	 * @param int $count Total number of active members.
	 */
	return apply_filters( 'bp_core_get_active_member_count', $count );
}

/**
 * Update the spam status of the member on multisite configs.
 *
 * @since 5.0.0
 *
 * @param int $user_id The user ID to spam or ham.
 * @param int $value   0 to mark the user as `ham`, 1 to mark as `spam`.
 * @return bool          True if the spam status of the member changed.
 *                       False otherwise.
 */
function bp_core_update_member_status( $user_id = 0, $value = 0 ) {
	if ( ! is_multisite() || ! $user_id ) {
		return false;
	}

	/**
	 * The `update_user_status()` function is deprecated since WordPress 5.3.0.
	 * Continue to use it if WordPress current major version is lower than 5.3.
	 */
	if ( bp_get_major_wp_version() < 5.3 ) {
		return update_user_status( $user_id, 'spam', $value );
	}

	// Otherwise use the replacement function.
	$user = wp_update_user( array(
		'ID'   => $user_id,
		'spam' => $value,
	) );

	if ( is_wp_error( $user ) ) {
		return false;
	}

	return true;
}

/**
 * Process a spammed or unspammed user.
 *
 * This function is called from three places:
 *
 * - in bp_settings_action_capabilities() (from the front-end)
 * - by bp_core_mark_user_spam_admin()    (from wp-admin)
 * - bp_core_mark_user_ham_admin()        (from wp-admin)
 *
 * @since 1.6.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param int    $user_id       The ID of the user being spammed/hammed.
 * @param string $status        'spam' if being marked as spam, 'ham' otherwise.
 * @param bool   $do_wp_cleanup Optional. True to force the cleanup of WordPress content
 *                              and status, otherwise false. Generally, this should
 *                              only be false if WordPress is expected to have
 *                              performed this cleanup independently, as when hooked
 *                              to 'make_spam_user'.
 * @return bool True on success, false on failure.
 */
function bp_core_process_spammer_status( $user_id, $status, $do_wp_cleanup = true ) {
	global $wpdb;

	// Bail if no user ID.
	if ( empty( $user_id ) ) {
		return;
	}

	// Bail if user ID is super admin.
	if ( is_super_admin( $user_id ) ) {
		return;
	}

	// Get the functions file.
	if ( is_multisite() ) {
		require_once( ABSPATH . 'wp-admin/includes/ms.php' );
	}

	$is_spam = ( 'spam' == $status );

	// Only you can prevent infinite loops.
	remove_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	remove_action( 'make_ham_user',  'bp_core_mark_user_ham_admin' );

	// Force the cleanup of WordPress content and status for multisite configs.
	if ( $do_wp_cleanup ) {

		// Mark blogs as spam if the user is the sole admin of a site.
		if ( is_multisite() ) {
			/*
			 * No native function to fetch a user's blogs by role, so do it manually.
			 *
			 * This logic is mostly copied from get_blogs_of_user().
			 */
			$meta = get_user_meta( $user_id );

			foreach ( $meta as $key => $val ) {
				if ( 'capabilities' !== substr( $key, -12 ) ) {
					continue;
				}
				if ( $wpdb->base_prefix && 0 !== strpos( $key, $wpdb->base_prefix ) ) {
					continue;
				}
				$site_id = str_replace( array( $wpdb->base_prefix, '_capabilities' ), '', $key );
				if ( ! is_numeric( $site_id ) ) {
					continue;
				}

				$site_id = (int) $site_id;

				// Do not mark the main or current root blog as spam.
				if ( 1 === $site_id || bp_get_root_blog_id() === $site_id ) {
					continue;
				}

				// Now, do check for administrator role.
				$role = maybe_unserialize( $val );
				if ( empty( $role['administrator'] ) ) {
					continue;
				}

				// Check if the site has more than 1 admin. If so, bail.
				$counts = count_users( 'time', $site_id );
				if ( empty( $counts['avail_roles']['administrator'] ) || $counts['avail_roles']['administrator'] > 1 ) {
					continue;
				}

				// Now we can spam the blog.
				update_blog_status( $site_id, 'spam', $is_spam );
			}
		}

		// Finally, mark this user as a spammer.
		bp_core_update_member_status( $user_id, $is_spam );
	}

	// Update the user status.
	$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $user_id ) );

	// Clean user cache.
	clean_user_cache( $user_id );

	if ( ! is_multisite() ) {
		// Call multisite actions in single site mode for good measure.
		if ( true === $is_spam ) {

			/**
			 * Fires at end of processing spammer in Dashboard if not multisite and user is spam.
			 *
			 * @since 1.5.0
			 *
			 * @param int $value user ID.
			 */
			do_action( 'make_spam_user', $user_id );
		} else {

			/**
			 * Fires at end of processing spammer in Dashboard if not multisite and user is not spam.
			 *
			 * @since 1.5.0
			 *
			 * @param int $value user ID.
			 */
			do_action( 'make_ham_user', $user_id );
		}
	}

	// Hide this user's activity.
	if ( ( true === $is_spam ) && bp_is_active( 'activity' ) ) {
		bp_activity_hide_user_activity( $user_id );
	}

	// We need a special hook for is_spam so that components can delete data at spam time.
	if ( true === $is_spam ) {

		/**
		 * Fires at the end of the process spammer process if the user is spam.
		 *
		 * @since 1.5.0
		 *
		 * @param int $value Displayed user ID.
		 */
		do_action( 'bp_make_spam_user', $user_id );
	} else {

		/**
		 * Fires at the end of the process spammer process if the user is not spam.
		 *
		 * @since 1.5.0
		 *
		 * @param int $value Displayed user ID.
		 */
		do_action( 'bp_make_ham_user', $user_id );
	}

	/**
	 * Fires at the end of the process for hanlding spammer status.
	 *
	 * @since 1.5.5
	 *
	 * @param int  $user_id ID of the processed user.
	 * @param bool $is_spam The determined spam status of processed user.
	 */
	do_action( 'bp_core_process_spammer_status', $user_id, $is_spam );

	// Put things back how we found them.
	add_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	add_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

	return true;
}
/**
 * Hook to WP's make_spam_user and run our custom BP spam functions.
 *
 * @since 1.6.0
 *
 * @param int $user_id The user ID passed from the make_spam_user hook.
 */
function bp_core_mark_user_spam_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'spam', false );
}
add_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );

/**
 * Hook to WP's make_ham_user and run our custom BP spam functions.
 *
 * @since 1.6.0
 *
 * @param int $user_id The user ID passed from the make_ham_user hook.
 */
function bp_core_mark_user_ham_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'ham', false );
}
add_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

/**
 * Check whether a user has been marked as a spammer.
 *
 * @since 1.6.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param int $user_id The ID for the user.
 * @return bool True if spammer, otherwise false.
 */
function bp_is_user_spammer( $user_id = 0 ) {

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	$bp = buddypress();

	// Assume user is not spam.
	$is_spammer = false;

	// Setup our user.
	$user = false;

	// Get locally-cached data if available.
	switch ( $user_id ) {
		case bp_loggedin_user_id() :
			$user = ! empty( $bp->loggedin_user->userdata ) ? $bp->loggedin_user->userdata : false;
			break;

		case bp_displayed_user_id() :
			$user = ! empty( $bp->displayed_user->userdata ) ? $bp->displayed_user->userdata : false;
			break;

		case bp_get_member_user_id() :
			global $members_template;
			$user = isset( $members_template ) && isset( $members_template->member ) ? $members_template->member :  false;
			break;
	}

	// Manually get userdata if still empty.
	if ( empty( $user ) ) {
		$user = get_userdata( $user_id );
	}

	// No user found.
	if ( empty( $user ) ) {
		$is_spammer = false;

	// User found.
	} else {

		// Check if spam.
		if ( !empty( $user->spam ) ) {
			$is_spammer = true;
		}

		if ( 1 == $user->user_status ) {
			$is_spammer = true;
		}
	}

	/**
	 * Filters whether a user is marked as a spammer.
	 *
	 * @since 1.6.0
	 *
	 * @param bool     $is_spammer Whether or not user is marked as spammer.
	 * @param \WP_User $user       The user to which we are acting on.
	 */
	return apply_filters( 'bp_is_user_spammer', (bool) $is_spammer, $user );
}

/**
 * Check whether a user has been marked as deleted.
 *
 * @since 1.6.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param int $user_id The ID for the user.
 * @return bool True if deleted, otherwise false.
 */
function bp_is_user_deleted( $user_id = 0 ) {

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	$bp = buddypress();

	// Assume user is not deleted.
	$is_deleted = false;

	// Setup our user.
	$user = false;

	// Get locally-cached data if available.
	switch ( $user_id ) {
		case bp_loggedin_user_id() :
			$user = ! empty( $bp->loggedin_user->userdata ) ? $bp->loggedin_user->userdata : false;
			break;

		case bp_displayed_user_id() :
			$user = ! empty( $bp->displayed_user->userdata ) ? $bp->displayed_user->userdata : false;
			break;
	}

	// Manually get userdata if still empty.
	if ( empty( $user ) ) {
		$user = get_userdata( $user_id );
	}

	// No user found.
	if ( empty( $user ) ) {
		$is_deleted = true;

	// User found.
	} else {

		// Check if deleted.
		if ( !empty( $user->deleted ) ) {
			$is_deleted = true;
		}

		if ( 2 == $user->user_status ) {
			$is_deleted = true;
		}
	}

	/**
	 * Filters whether a user is marked as deleted.
	 *
	 * @since 1.6.0
	 *
	 * @param bool     $is_deleted Whether or not user is marked as deleted.
	 * @param \WP_User $user       The user to which we are acting on.
	 */
	return apply_filters( 'bp_is_user_deleted', (bool) $is_deleted, $user );
}

/**
 * Check whether a user is "active", ie neither deleted nor spammer.
 *
 * @since 1.6.0
 *
 * @param int $user_id Optional. The user ID to check.
 * @return bool True if active, otherwise false.
 */
function bp_is_user_active( $user_id = 0 ) {

	// Default to current user.
	if ( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = bp_loggedin_user_id();
	}

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Check spam.
	if ( bp_is_user_spammer( $user_id ) ) {
		return false;
	}

	// Check deleted.
	if ( bp_is_user_deleted( $user_id ) ) {
		return false;
	}

	// Assume true if not spam or deleted.
	return true;
}

/**
 * Check whether user is not active.
 *
 * @since 1.6.0
 *
 * @param int $user_id Optional. The user ID to check.
 * @return bool True if inactive, otherwise false.
 */
function bp_is_user_inactive( $user_id = 0 ) {
	// Return the inverse of active.
	return ! bp_is_user_active( $user_id );
}

/**
 * Update a user's last activity.
 *
 * @since 1.9.0
 * @since 7.0.0 Backward compatibility usermeta mirroring is only allowed if the
 *              legacy user query is enabled.
 *
 * @param int    $user_id Optional. ID of the user being updated.
 * @param string $time    Optional. Time of last activity, in 'Y-m-d H:i:s' format.
 * @return bool True on success, false on failure.
 */
function bp_update_user_last_activity( $user_id = 0, $time = '' ) {

	// Fall back on current user.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// Bail if the user id is 0, as there's nothing to update.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Fall back on current time.
	if ( empty( $time ) ) {
		$time = bp_core_current_time();
	}

	/** This filter is documented in bp_core_get_users() */
	$use_legacy_query = apply_filters( 'bp_use_legacy_user_query', false, __FUNCTION__, [ 'user_id' => $user_id ] );

	/*
	 * As of BuddyPress 2.0, last_activity is no longer stored in usermeta.
	 * However, we mirror it there for backward compatibility. Do not use!
	 *
	 * As of BuddyPress 7.0, mirroring is only allowed if the legacy user
	 * query is enabled.
	 */
	if ( $use_legacy_query ) {
		remove_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10 );
		remove_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10 );
		bp_update_user_meta( $user_id, 'last_activity', $time );
		add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );
		add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );
	}

	return BP_Core_User::update_last_activity( $user_id, $time );
}

/**
 * Backward compatibility for 'last_activity' usermeta fetching.
 *
 * In BuddyPress 2.0, user last_activity data was moved out of usermeta. For
 * backward compatibility, we continue to mirror the data there. This function
 * serves two purposes: it warns plugin authors of the change, and it returns
 * the data from the proper location.
 *
 * @since 2.0.0
 * @since 2.9.3 Added the `$single` parameter.
 *
 * @access private For internal use only.
 *
 * @param null   $retval Null retval value.
 * @param int    $object_id ID of the user.
 * @param string $meta_key  Meta key being fetched.
 * @param bool   $single    Whether a single key is being fetched (vs an array).
 * @return string|null
 */
function _bp_get_user_meta_last_activity_warning( $retval, $object_id, $meta_key, $single ) {
	static $warned = false;

	if ( 'last_activity' === $meta_key ) {
		// Don't send the warning more than once per pageload.
		if ( false === $warned ) {
			_doing_it_wrong( 'get_user_meta( $user_id, \'last_activity\' )', __( 'User last_activity data is no longer stored in usermeta. Use bp_get_user_last_activity() instead.', 'buddypress' ), '2.0.0' );
			$warned = true;
		}

		$user_last_activity = bp_get_user_last_activity( $object_id );
		if ( $single ) {
			return $user_last_activity;
		} else {
			return array( $user_last_activity );
		}
	}

	return $retval;
}
add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );

/**
 * Backward compatibility for 'last_activity' usermeta setting.
 *
 * In BuddyPress 2.0, user last_activity data was moved out of usermeta. For
 * backward compatibility, we continue to mirror the data there. This function
 * serves two purposes: it warns plugin authors of the change, and it updates
 * the data in the proper location.
 *
 * @since 2.0.0
 *
 * @access private For internal use only.
 *
 * @param int    $meta_id    ID of the just-set usermeta row.
 * @param int    $object_id  ID of the user.
 * @param string $meta_key   Meta key being fetched.
 * @param string $meta_value Active time.
 */
function _bp_update_user_meta_last_activity_warning( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( 'last_activity' === $meta_key ) {
		_doing_it_wrong( 'update_user_meta( $user_id, \'last_activity\' )', __( 'User last_activity data is no longer stored in usermeta. Use bp_update_user_last_activity() instead.', 'buddypress' ), '2.0.0' );
		bp_update_user_last_activity( $object_id, $meta_value );
	}
}
add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );

/**
 * Get the last activity for a given user.
 *
 * @since 1.9.0
 *
 * @param int $user_id The ID of the user.
 * @return string Time of last activity, in 'Y-m-d H:i:s' format, or an empty
 *                string if none is found.
 */
function bp_get_user_last_activity( $user_id = 0 ) {
	$activity = '';

	$last_activity = BP_Core_User::get_last_activity( $user_id );
	if ( ! empty( $last_activity[ $user_id ] ) ) {
		$activity = $last_activity[ $user_id ]['date_recorded'];
	}

	/**
	 * Filters the last activity for a given user.
	 *
	 * @since 1.9.0
	 *
	 * @param string $activity Time of last activity, in 'Y-m-d H:i:s' format or
	 *                         an empty string if none found.
	 * @param int    $user_id  ID of the user being checked.
	 */
	return apply_filters( 'bp_get_user_last_activity', $activity, $user_id );
}

/**
 * Migrate last_activity data from the usermeta table to the activity table.
 *
 * Generally, this function is only run when BP is upgraded to 2.0. It can also
 * be called directly from the BuddyPress Tools panel.
 *
 * @since 2.0.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 * @global wpdb $wpdb WordPress database object.
 *
 * @return bool
 */
function bp_last_activity_migrate() {
	global $wpdb;

	$bp = buddypress();

	// Wipe out existing last_activity data in the activity table -
	// this helps to prevent duplicates when pulling from the usermeta
	// table.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity'", $bp->members->id ) );

	$sql = "INSERT INTO {$bp->members->table_name_last_activity} (`user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `date_recorded` ) (
		  SELECT user_id, '{$bp->members->id}' as component, 'last_activity' as type, '' as action, '' as content, '' as primary_link, 0 as item_id, meta_value AS date_recorded
		  FROM {$wpdb->usermeta}
		  WHERE
		    meta_key = 'last_activity'
	);";

	return $wpdb->query( $sql );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * No longer used in BuddyPress.
 *
 * @todo Deprecate.
 *
 * @param int $user_id ID of the user being queried.
 * @return array Post IDs.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	return apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
}

/**
 * Process account deletion requests.
 *
 * Primarily used for self-deletions, as requested through Settings.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. ID of the user to be deleted. Default: the
 *                     logged-in user.
 * @return bool True on success, false on failure.
 */
function bp_core_delete_account( $user_id = 0 ) {

	// Use logged in user ID if none is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// Site admins cannot be deleted.
	if ( is_super_admin( $user_id ) ) {
		return false;
	}

	// Extra checks if user is not deleting themselves.
	if ( bp_loggedin_user_id() !== absint( $user_id ) ) {

		// Bail if current user cannot delete any users.
		if ( ! bp_current_user_can( 'delete_users' ) ) {
			return false;
		}

		// Bail if current user cannot delete this user.
		if ( ! current_user_can_for_blog( bp_get_root_blog_id(), 'delete_user', $user_id ) ) {
			return false;
		}
	}

	/**
	 * Fires before the processing of an account deletion.
	 *
	 * @since 1.6.0
	 *
	 * @param int $user_id ID of the user account being deleted.
	 */
	do_action( 'bp_core_pre_delete_account', $user_id );

	// Specifically handle multi-site environment.
	if ( is_multisite() ) {
		require_once( ABSPATH . '/wp-admin/includes/ms.php'   );
		require_once( ABSPATH . '/wp-admin/includes/user.php' );

		$retval = wpmu_delete_user( $user_id );

	// Single site user deletion.
	} else {
		require_once( ABSPATH . '/wp-admin/includes/user.php' );
		$retval = wp_delete_user( $user_id );
	}

	/**
	 * Fires after the deletion of an account.
	 *
	 * @since 1.6.0
	 *
	 * @param int $user_id ID of the user account that was deleted.
	 */
	do_action( 'bp_core_deleted_account', $user_id );

	return $retval;
}

/**
 * Determines whether user data should be removed on the 'delete_user' hook.
 *
 * WordPress's 'delete_user' hook is ambiguous: on a standard installation, it means that a user
 * account is being removed from the system, while on Multisite it simply means the user is
 * being removed from a specific site (ie its roles are being revoked). As a rule, this means
 * that BuddyPress should remove user data on the delete_user hook only on non-Multisite
 * installations - only when the user account is being removed altogether. However, this behavior
 * can be filtered in a global, per-user, or per-component fashion.
 *
 * @since 6.0.0
 *
 * @param string $data_type Type of data to be removed.
 * @param int    $user_id   ID of the user, as passed to 'delete_user'.
 * @return bool
 */
function bp_remove_user_data_on_delete_user_hook( $component, $user_id ) {
	$remove = ! is_multisite();

	/**
	 * Filters whether to remove user data on the 'delete_user' hook.
	 *
	 * @param bool   $remove    Whether data should be removed.
	 * @param string $data_type Type of data to be removed.
	 * @param int    $user_id   ID of the user, as passed to 'delete_user'.
	 */
	return apply_filters( 'bp_remove_user_data_on_delete_user_hook', $remove, $component, $user_id );
}

/**
 * Delete a user's avatar when the user is deleted.
 *
 * @since 1.9.0
 *
 * @param int $user_id ID of the user who is about to be deleted.
 * @return bool True on success, false on failure.
 */
function bp_core_delete_avatar_on_user_delete( $user_id ) {
	return bp_core_delete_existing_avatar( array(
		'item_id' => $user_id,
		'object'  => 'user',
	) );
}
add_action( 'wpmu_delete_user', 'bp_core_delete_avatar_on_user_delete' );

/**
 * Deletes last_activity data on the 'delete_user' hook.
 *
 * @since 6.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function bp_core_delete_avatar_on_delete_user( $user_id ) {
	if ( ! bp_remove_user_data_on_delete_user_hook( 'avatar', $user_id ) ) {
		return;
	}

	bp_core_delete_avatar_on_user_delete( $user_id );
}
add_action( 'delete_user', 'bp_core_delete_avatar_on_delete_user' );

/**
 * Multibyte-safe ucfirst() support.
 *
 * Uses multibyte functions when available on the PHP build.
 *
 * @since 1.0.0
 *
 * @param string $str String to be upper-cased.
 * @return string
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
 * Prevent spammers from logging in.
 *
 * When a user logs in, check if they have been marked as a spammer. If yes
 * then simply redirect them to the home page and stop them from logging in.
 *
 * @since 1.1.2
 *
 * @param WP_User|WP_Error $user Either the WP_User object or the WP_Error
 *                               object, as passed to the 'authenticate' filter.
 * @return WP_User|WP_Error If the user is not a spammer, return the WP_User
 *                          object. Otherwise a new WP_Error object.
 */
function bp_core_boot_spammer( $user ) {

	// Check to see if the $user has already failed logging in, if so return $user as-is.
	if ( is_wp_error( $user ) || empty( $user ) ) {
		return $user;
	}

	// The user exists; now do a check to see if the user is a spammer
	// if the user is a spammer, stop them in their tracks!
	if ( is_a( $user, 'WP_User' ) && ( ( is_multisite() && (int) $user->spam ) || 1 == $user->user_status ) ) {
		return new WP_Error( 'invalid_username', __( '<strong>Error</strong>: Your account has been marked as a spammer.', 'buddypress' ) );
	}

	// User is good to go!
	return $user;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 30 );

/**
 * Delete last_activity data for the user when the user is deleted.
 *
 * @since 1.0.0
 *
 * @param int $user_id The user ID for the user to delete usermeta for.
 */
function bp_core_remove_data( $user_id ) {

	// Remove last_activity data.
	BP_Core_User::delete_last_activity( $user_id );

	// Flush the cache to remove the user from all cached objects.
	wp_cache_flush();
}
add_action( 'wpmu_delete_user',  'bp_core_remove_data' );
add_action( 'bp_make_spam_user', 'bp_core_remove_data' );

/**
 * Deletes last_activity data on the 'delete_user' hook.
 *
 * @since 6.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function bp_core_remove_data_on_delete_user( $user_id ) {
	if ( ! bp_remove_user_data_on_delete_user_hook( 'last_activity', $user_id ) ) {
		return;
	}

	bp_core_remove_data( $user_id );
}
add_action( 'delete_user', 'bp_core_remove_data_on_delete_user' );

/**
 * Check whether the logged-in user can edit settings for the displayed user.
 *
 * @since 1.5.0
 *
 * @return bool True if editing is allowed, otherwise false.
 */
function bp_core_can_edit_settings() {
	$status = false;

	if ( bp_is_my_profile() ) {
		$status = true;
	} elseif ( is_super_admin( bp_displayed_user_id() ) && ! is_super_admin() ) {
		$status = false;
	} elseif ( bp_current_user_can( 'bp_moderate' ) || current_user_can( 'edit_users' ) ) {
		$status = true;
	}

	/**
	 * Filters the status of whether the logged-in user can edit settings for the displayed user or not.
	 *
	 * @since 2.8.0
	 *
	 * @param bool True if editing is allowed, otherwise false.
	 */
	return apply_filters( 'bp_core_can_edit_settings', $status );
}

/** Sign-up *******************************************************************/

/**
 * Flush illegal names by getting and setting 'illegal_names' site option.
 *
 * @since 1.2.5
 */
function bp_core_flush_illegal_names() {
	$illegal_names = get_site_option( 'illegal_names' );
	update_site_option( 'illegal_names', $illegal_names );
}

/**
 * Add BuddyPress-specific items to the illegal_names array.
 *
 * @since 1.2.7
 *
 * @param array|string $value Illegal names as being saved defined in
 *                            Multisite settings.
 * @return array Merged and unique array of illegal names.
 */
function bp_core_get_illegal_names( $value = '' ) {

	// Make sure $value is array.
	if ( empty( $value ) ) {
		$db_illegal_names = array();
	}

	if ( is_array( $value ) ) {
		$db_illegal_names = $value;
	} elseif ( is_string( $value ) ) {
		$db_illegal_names = explode( ' ', $value );
	}

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
		'notifications',
		'register',
		'activate',
	);

	// Core constants.
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
		'BP_NOTIFICATIONS_SLUG',
		'BP_REGISTER_SLUG',
		'BP_ACTIVATION_SLUG',
	);
	foreach ( $slug_constants as $constant ) {
		if ( defined( $constant ) ) {
			$bp_component_slugs[] = constant( $constant );
		}
	}

	/**
	 * Filters the array of default illegal usernames.
	 *
	 * @since 1.2.2
	 *
	 * @param array $value Merged and unique array of illegal usernames.
	 */
	$filtered_illegal_names = apply_filters( 'bp_core_illegal_usernames', array_merge( array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' ), $bp_component_slugs ) );

	/**
	 * Filters the list of illegal usernames from WordPress.
	 *
	 * @since 3.0
	 *
	 * @param array Array of illegal usernames.
	 */
	$wp_filtered_illegal_names = apply_filters( 'illegal_user_logins', array() );

	// First merge BuddyPress illegal names.
	$bp_merged_names = array_merge( (array) $filtered_illegal_names, (array) $db_illegal_names );

	// Then merge WordPress and BuddyPress illegal names.
	$merged_names = array_merge( (array) $wp_filtered_illegal_names, (array) $bp_merged_names );

	// Remove duplicates.
	$illegal_names = array_unique( (array) $merged_names );

	/**
	 * Filters the array of default illegal names.
	 *
	 * @since 1.2.5
	 *
	 * @param array $value Merged and unique array of illegal names.
	 */
	return apply_filters( 'bp_core_illegal_names', $illegal_names );
}
add_filter( 'pre_update_site_option_illegal_names', 'bp_core_get_illegal_names' );

/**
 * Check that an email address is valid for use.
 *
 * Performs the following checks:
 *   - Is the email address well-formed?
 *   - Is the email address already used?
 *   - If there are disallowed email domains, is the current domain among them?
 *   - If there's an email domain whitelest, is the current domain on it?
 *
 * @since 1.6.2
 *
 * @param string $user_email The email being checked.
 * @return bool|array True if the address passes all checks; otherwise an array
 *                    of error codes.
 */
function bp_core_validate_email_address( $user_email ) {
	$errors = array();

	$user_email = sanitize_email( $user_email );

	// Is the email well-formed?
	if ( ! is_email( $user_email ) ) {
		$errors['invalid'] = 1;
	}

	// Is the email on the Banned Email Domains list?
	// Note: This check only works on Multisite.
	if ( function_exists( 'is_email_address_unsafe' ) && is_email_address_unsafe( $user_email ) ) {
		$errors['domain_banned'] = 1;
	}

	// Is the email on the Limited Email Domains list?
	// Note: This check only works on Multisite.
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );
		if ( ! in_array( $emaildomain, $limited_email_domains ) ) {
			$errors['domain_not_allowed'] = 1;
		}
	}

	// Is the email alreday in use?
	if ( email_exists( $user_email ) ) {
		$errors['in_use'] = 1;
	}

	$retval = ! empty( $errors ) ? $errors : true;

	return $retval;
}

/**
 * Add the appropriate errors to a WP_Error object, given results of a validation test.
 *
 * Functions like bp_core_validate_email_address() return a structured array
 * of error codes. bp_core_add_validation_error_messages() takes this array and
 * parses, adding the appropriate error messages to the WP_Error object.
 *
 * @since 1.7.0
 *
 * @see bp_core_validate_email_address()
 *
 * @param WP_Error $errors             WP_Error object.
 * @param array    $validation_results The return value of a validation function
 *                                     like bp_core_validate_email_address().
 */
function bp_core_add_validation_error_messages( WP_Error $errors, $validation_results ) {
	if ( ! empty( $validation_results['invalid'] ) ) {
		$errors->add( 'user_email', __( 'Please check your email address.', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['domain_banned'] ) ) {
		$errors->add( 'user_email',  __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['domain_not_allowed'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['in_use'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddypress' ) );
	}
}

/**
 * Validate a user name and email address when creating a new user.
 *
 * @since 1.2.2
 *
 * @param string $user_name  Username to validate.
 * @param string $user_email Email address to validate.
 * @return array Results of user validation including errors, if any.
 */
function bp_core_validate_user_signup( $user_name, $user_email ) {

	// Make sure illegal names include BuddyPress slugs and values.
	bp_core_flush_illegal_names();

	// WordPress Multisite has its own validation. Use it, so that we
	// properly mirror restrictions on username, etc.
	if ( function_exists( 'wpmu_validate_user_signup' ) ) {
		$result = wpmu_validate_user_signup( $user_name, $user_email );

	// When not running Multisite, we perform our own validation. What
	// follows reproduces much of the logic of wpmu_validate_user_signup(),
	// minus the multisite-specific restrictions on user_login.
	} else {
		$errors = new WP_Error();

		/**
		 * Filters the username before being validated.
		 *
		 * @since 1.5.5
		 *
		 * @param string $user_name Username to validate.
		 */
		$user_name = apply_filters( 'pre_user_login', $user_name );

		// User name can't be empty.
		if ( empty( $user_name ) ) {
			$errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );
		}

		// User name can't be on the list of illegal names.
		$illegal_names = get_site_option( 'illegal_names' );
		if ( in_array( $user_name, (array) $illegal_names ) ) {
			$errors->add( 'user_name', __( 'That username is not allowed', 'buddypress' ) );
		}

		// User name must pass WP's validity check.
		if ( ! validate_username( $user_name ) ) {
			$errors->add( 'user_name', __( 'Usernames can contain only letters, numbers, ., -, and @', 'buddypress' ) );
		}

		// Minimum of 4 characters.
		if ( strlen( $user_name ) < 4 ) {
			$errors->add( 'user_name',  __( 'Username must be at least 4 characters', 'buddypress' ) );
		}

		// No underscores. @todo Why not?
		if ( false !== strpos( ' ' . $user_name, '_' ) ) {
			$errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );
		}

		// No usernames that are all numeric. @todo Why?
		$match = array();
		preg_match( '/[0-9]*/', $user_name, $match );
		if ( $match[0] == $user_name ) {
			$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );
		}

		// Check into signups.
		$signups = BP_Signup::get( array(
			'user_login' => $user_name,
		) );

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Check if the username has been used already.
		if ( username_exists( $user_name ) || ! empty( $signup ) ) {
			$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );
		}

		// Validate the email address and process the validation results into
		// error messages.
		$validate_email = bp_core_validate_email_address( $user_email );
		bp_core_add_validation_error_messages( $errors, $validate_email );

		// Assemble the return array.
		$result = array(
			'user_name'  => $user_name,
			'user_email' => $user_email,
			'errors'     => $errors,
		);

		// Apply WPMU legacy filter.
		$result = apply_filters( 'wpmu_validate_user_signup', $result );
	}

	/**
	 * Filters the result of the user signup validation.
	 *
	 * @since 1.2.2
	 *
	 * @param array $result Results of user validation including errors, if any.
	 */
	return apply_filters( 'bp_core_validate_user_signup', $result );
}

/**
 * Validate a user password.
 *
 * @since 7.0.0
 *
 * @param string       $pass         The password.
 * @param string       $confirm_pass The confirmed password.
 * @param null|WP_User $userdata     Null or the userdata object when a member updates their password from front-end.
 * @return WP_Error A WP error object possibly containing error messages.
 */
function bp_members_validate_user_password( $pass, $confirm_pass, $userdata = null ) {
	$errors = new WP_Error();

	if ( ! $pass || ! $confirm_pass ) {
		$errors->add( 'missing_user_password', __( 'Please make sure you enter your password twice', 'buddypress' ) );
	}

	if ( $pass && $confirm_pass && $pass !== $confirm_pass ) {
		$errors->add( 'mismatching_user_password', __( 'The passwords you entered do not match.', 'buddypress' ) );
	}

	/**
	 * Filter here to add password validation errors.
	 *
	 * @since 7.0.0
	 *
	 * @param WP_Error     $errors       Password validation errors.
	 * @param string       $pass         The password.
	 * @param string       $confirm_pass The confirmed password.
	 * @param null|WP_User $userdata     Null or the userdata object when a member updates their password from front-end.
	 */
	return apply_filters( 'bp_members_validate_user_password', $errors, $pass, $confirm_pass, $userdata );
}

/**
 * Validate blog URL and title provided at signup.
 *
 * @since 1.2.2
 *
 * @todo Why do we have this wrapper?
 *
 * @param string $blog_url   Blog URL requested during registration.
 * @param string $blog_title Blog title requested during registration.
 * @return array
 */
function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_validate_blog_signup' ) ) {
		return false;
	}

	/**
	 * Filters the validated blog url and title provided at signup.
	 *
	 * @since 1.2.2
	 *
	 * @param array $value Array with the new site data and error messages.
	 */
	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

/**
 * Process data submitted at user registration and convert to a signup object.
 *
 * @since 1.2.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @todo There appears to be a bug in the return value on success.
 *
 * @param string $user_login    Login name requested by the user.
 * @param string $user_password Password requested by the user.
 * @param string $user_email    Email address entered by the user.
 * @param array  $usermeta      Miscellaneous metadata about the user (blog-specific
 *                              signup data, xprofile data, etc).
 * @return int|false True on success, WP_Error on failure.
 */
function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	$bp = buddypress();

	// We need to cast $user_id to pass to the filters.
	$user_id = false;

	// Multisite installs have their own install procedure.
	if ( is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

	} else {
		// Format data.
		$user_login     = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
		$user_email     = sanitize_email( $user_email );
		$activation_key = wp_generate_password( 32, false );

		/**
		 * WordPress's default behavior is to create user accounts
		 * immediately at registration time. BuddyPress uses a system
		 * borrowed from WordPress Multisite, where signups are stored
		 * separately and accounts are only created at the time of
		 * activation. For backward compatibility with plugins that may
		 * be anticipating WP's default behavior, BP silently creates
		 * accounts for registrations (though it does not use them). If
		 * you know that you are not running any plugins dependent on
		 * these pending accounts, you may want to save a little DB
		 * clutter by defining setting the BP_SIGNUPS_SKIP_USER_CREATION
		 * to true in your wp-config.php file.
		 */
		if ( ! defined( 'BP_SIGNUPS_SKIP_USER_CREATION' ) || ! BP_SIGNUPS_SKIP_USER_CREATION ) {
			$user_id = BP_Signup::add_backcompat( $user_login, $user_password, $user_email, $usermeta );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			bp_update_user_meta( $user_id, 'activation_key', $activation_key );
		}

		$args = array(
			'user_login'     => $user_login,
			'user_email'     => $user_email,
			'activation_key' => $activation_key,
			'meta'           => $usermeta,
		);

		BP_Signup::add( $args );

		/**
		 * Filters if BuddyPress should send an activation key for a new signup.
		 *
		 * @since 1.2.3
		 *
		 * @param bool   $value          Whether or not to send the activation key.
		 * @param int    $user_id        User ID to send activation key to.
		 * @param string $user_email     User email to send activation key to.
		 * @param string $activation_key Activation key to be sent.
		 * @param array  $usermeta       Miscellaneous metadata about the user (blog-specific
		 *                               signup data, xprofile data, etc).
		 */
		if ( apply_filters( 'bp_core_signup_send_activation_key', true, $user_id, $user_email, $activation_key, $usermeta ) ) {
			$salutation = $user_login;
			if ( bp_is_active( 'xprofile' ) && isset( $usermeta[ 'field_' . bp_xprofile_fullname_field_id() ] ) ) {
				$salutation = $usermeta[ 'field_' . bp_xprofile_fullname_field_id() ];
			}

			bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key, $salutation );
		}
	}

	$bp->signup->username = $user_login;

	/**
	 * Fires at the end of the process to sign up a user.
	 *
	 * @since 1.2.2
	 *
	 * @param bool|WP_Error   $user_id       True on success, WP_Error on failure.
	 * @param string          $user_login    Login name requested by the user.
	 * @param string          $user_password Password requested by the user.
	 * @param string          $user_email    Email address requested by the user.
	 * @param array           $usermeta      Miscellaneous metadata about the user (blog-specific
	 *                                       signup data, xprofile data, etc).
	 */
	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

/**
 * Create a blog and user based on data supplied at user registration.
 *
 * @since 1.2.2
 *
 * @param string $blog_domain Domain requested by user.
 * @param string $blog_path   Path requested by user.
 * @param string $blog_title  Title as entered by user.
 * @param string $user_name   user_login of requesting user.
 * @param string $user_email  Email address of requesting user.
 * @param string $usermeta    Miscellaneous metadata for the user.
 * @return bool
 */
function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_signup_blog' ) ) {
		return false;
	}

	/**
	 * Filters the result of wpmu_signup_blog().
	 *
	 * This filter provides no value and is retained for
	 * backwards compatibility.
	 *
	 * @since 1.2.2
	 *
	 * @param void $value
	 */
	return apply_filters( 'bp_core_signup_blog', wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) );
}

/**
 * Activate a signup, as identified by an activation key.
 *
 * @since 1.2.2
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param string $key Activation key.
 * @return int|bool User ID on success, false on failure.
 */
function bp_core_activate_signup( $key ) {
	global $wpdb;

	$user = false;

	// Multisite installs have their own activation routine.
	if ( is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		// If there were errors, add a message and redirect.
		if ( ! empty( $user->errors ) ) {
			return $user;
		}

		$user_id = $user['user_id'];

	} else {
		$signups = BP_Signup::get( array(
			'activation_key' => $key,
		) );

		if ( empty( $signups['signups'] ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid activation key.', 'buddypress' ) );
		}

		$signup = $signups['signups'][0];

		if ( $signup->active ) {
			if ( empty( $signup->domain ) ) {
				return new WP_Error( 'already_active', __( 'The user is already active.', 'buddypress' ), $signup );
			} else {
				return new WP_Error( 'already_active', __( 'The site is already active.', 'buddypress' ), $signup );
			}
		}

		// Password is hashed again in wp_insert_user.
		$password = wp_generate_password( 12, false );

		$user_id = username_exists( $signup->user_login );

		// Create the user. This should only be necessary if BP_SIGNUPS_SKIP_USER_CREATION is true.
		if ( ! $user_id ) {
			$user_id = wp_create_user( $signup->user_login, $password, $signup->user_email );

		// Otherwise, update the existing user's status.
		} elseif ( $key === bp_get_user_meta( $user_id, 'activation_key', true ) || $key === wp_hash( $user_id ) ) {

			// Change the user's status so they become active.
			if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 0 WHERE ID = %d", $user_id ) ) ) {
				return new WP_Error( 'invalid_key', __( 'Invalid activation key.', 'buddypress' ) );
			}

			bp_delete_user_meta( $user_id, 'activation_key' );

			$user_already_created = true;

		} else {
			$user_already_exists = true;
		}

		if ( ! $user_id ) {
			return new WP_Error( 'create_user', __( 'Could not create user', 'buddypress' ), $signup );
		}

		// Fetch the signup so we have the data later on.
		$signups = BP_Signup::get( array(
			'activation_key' => $key,
		) );

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Activate the signup.
		BP_Signup::validate( $key );

		if ( isset( $user_already_exists ) ) {
			return new WP_Error( 'user_already_exists', __( 'That username is already activated.', 'buddypress' ), $signup );
		}

		// Set up data to pass to the legacy filter.
		$user = array(
			'user_id'  => $user_id,
			'password' => isset( $signup->meta['password'] ) ? $signup->meta['password'] : '',
			'meta'     => $signup->meta,
		);

		/**
		 * Maybe notify the site admin of a new user registration.
		 *
		 * @since 1.2.2
		 *
		 * @param bool $notification Whether to send the notification or not.
		 */
		if ( apply_filters( 'bp_core_send_user_registration_admin_notification', true ) ) {
			wp_new_user_notification( $user_id );
		}

		if ( isset( $user_already_created ) ) {

			/**
			 * Fires if the user has already been created.
			 *
			 * @since 1.2.2
			 *
			 * @param int    $user_id ID of the user being checked.
			 * @param string $key     Activation key.
			 * @param array  $user    Array of user data.
			 */
			do_action( 'bp_core_activated_user', $user_id, $key, $user );
			return $user_id;
		}
	}

	// Set any profile data.
	if ( bp_is_active( 'xprofile' ) ) {
		if ( ! empty( $user['meta']['profile_field_ids'] ) ) {
			$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

			foreach( (array) $profile_field_ids as $field_id ) {
				$current_field = isset( $user['meta']["field_{$field_id}"] ) ? $user['meta']["field_{$field_id}"] : false;

				if ( ! empty( $current_field ) ) {
					xprofile_set_field_data( $field_id, $user_id, $current_field );
				}

				/*
				 * Save the visibility level.
				 *
				 * Use the field's default visibility if not present, and 'public' if a
				 * default visibility is not defined.
				 */
				$key = "field_{$field_id}_visibility";
				if ( isset( $user['meta'][ $key ] ) ) {
					$visibility_level = $user['meta'][ $key ];
				} else {
					$vfield           = xprofile_get_field( $field_id, null, false );
					$visibility_level = isset( $vfield->default_visibility ) ? $vfield->default_visibility : 'public';
				}
				xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
			}
		}
	}

	// Replace the password automatically generated by WordPress by the one the user chose.
	if ( ! empty( $user['meta']['password'] ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

		/**
		 * Make sure to clean the user's cache as we've
		 * directly edited the password without using
		 * wp_update_user().
		 *
		 * If we can't use wp_update_user() that's because
		 * we already hashed the password at the signup step.
		 */
		$uc = wp_cache_get( $user_id, 'users' );

		if ( ! empty( $uc->ID ) ) {
			clean_user_cache( $uc->ID );
		}
	}

	/**
	 * Fires at the end of the user activation process.
	 *
	 * @since 1.2.2
	 *
	 * @param int    $user_id ID of the user being checked.
	 * @param string $key     Activation key.
	 * @param array  $user    Array of user data.
	 */
	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

/**
 * Add default WordPress role for new signups on the BP root blog.
 *
 * @since 3.0.0
 *
 * @param int $user_id The user ID to add the default role for.
 */
function bp_members_add_role_after_activation( $user_id ) {
	// Get default role to add.
	$role = bp_get_option( 'default_role' );

	// Multisite.
	if ( is_multisite() && ! is_user_member_of_blog( $user_id, bp_get_root_blog_id() ) ) {
		add_user_to_blog( bp_get_root_blog_id(), $user_id, $role );

	// Single-site.
	} elseif ( ! is_multisite() ) {
		$member = get_userdata( $user_id );
		$member->set_role( $role );
	}
}
add_action( 'bp_core_activated_user', 'bp_members_add_role_after_activation', 1 );

/**
 * Migrate signups from pre-2.0 configuration to wp_signups.
 *
 * @since 2.0.1
 *
 * @global wpdb $wpdb WordPress database object.
 */
function bp_members_migrate_signups() {
	global $wpdb;

	$status_2_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_status = '2'" );

	if ( ! empty( $status_2_ids ) ) {
		$signups = get_users( array(
			'fields'  => array(
				'ID',
				'user_login',
				'user_pass',
				'user_registered',
				'user_email',
				'display_name',
			),
			'include' => $status_2_ids,
		) );

		// Fetch activation keys separately, to avoid the all_with_meta
		// overhead.
		$status_2_ids_sql = implode( ',', $status_2_ids );
		$ak_data = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'activation_key' AND user_id IN ({$status_2_ids_sql})" );

		// Rekey.
		$activation_keys = array();
		foreach ( $ak_data as $ak_datum ) {
			$activation_keys[ intval( $ak_datum->user_id ) ] = $ak_datum->meta_value;
		}

		unset( $status_2_ids_sql, $status_2_ids, $ak_data );

		// Merge.
		foreach ( $signups as &$signup ) {
			if ( isset( $activation_keys[ $signup->ID ] ) ) {
				$signup->activation_key = $activation_keys[ $signup->ID ];
			}
		}

		// Reset the signup var as we're using it to process the migration.
		unset( $signup );

	} else {
		return;
	}

	foreach ( $signups as $signup ) {
		$meta = array();

		// Rebuild the activation key, if missing.
		if ( empty( $signup->activation_key ) ) {
			$signup->activation_key = wp_generate_password( 32, false );
		}

		if ( bp_is_active( 'xprofile' ) ) {
			$meta['field_1'] = $signup->display_name;
		}

		$meta['password'] = $signup->user_pass;

		$user_login = preg_replace( '/\s+/', '', sanitize_user( $signup->user_login, true ) );
		$user_email = sanitize_email( $signup->user_email );

		BP_Signup::add( array(
			'user_login'     => $user_login,
			'user_email'     => $user_email,
			'registered'     => $signup->user_registered,
			'activation_key' => $signup->activation_key,
			'meta'           => $meta
		) );

		// Deleting these options will remove signups from users count.
		delete_user_option( $signup->ID, 'capabilities' );
		delete_user_option( $signup->ID, 'user_level'   );
	}
}

/**
 * Map a user's WP display name to the XProfile fullname field, if necessary.
 *
 * This only happens when a user is registered in wp-admin by an administrator;
 * during normal registration, XProfile data is provided directly by the user.
 *
 * @since 1.2.0
 *
 * @param int $user_id ID of the user.
 * @return bool
 */
function bp_core_map_user_registration( $user_id ) {

	// Only map data when the site admin is adding users, not on registration.
	if ( ! is_admin() ) {
		return false;
	}

	// Add the user's fullname to Xprofile.
	if ( bp_is_active( 'xprofile' ) ) {
		$firstname = bp_get_user_meta( $user_id, 'first_name', true );
		$lastname = ' ' . bp_get_user_meta( $user_id, 'last_name', true );
		$name = $firstname . $lastname;

		if ( empty( $name ) || ' ' == $name ) {
			$name = bp_get_user_meta( $user_id, 'nickname', true );
		}

		xprofile_set_field_data( 1, $user_id, $name );
	}
}
add_action( 'user_register', 'bp_core_map_user_registration' );

/**
 * Get the avatar storage directory for use during registration.
 *
 * @since 1.1.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @return string|bool Directory path on success, false on failure.
 */
function bp_core_signup_avatar_upload_dir() {
	$bp = buddypress();

	if ( empty( $bp->signup->avatar_dir ) ) {
		return false;
	}

	$directory = 'avatars/signups';
	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $bp->signup->avatar_dir;

	/**
	 * Filters the avatar storage directory for use during registration.
	 *
	 * @since 1.1.1
	 *
	 * @param array $value Array of path and URL values for created storage directory.
	 */
	return apply_filters( 'bp_core_signup_avatar_upload_dir', array(
		'path'    => $path,
		'url'     => $newurl,
		'subdir'  => $newsubdir,
		'basedir' => $newbdir,
		'baseurl' => $newburl,
		'error'   => false,
	) );
}

/**
 * Send activation email to a newly registered user.
 *
 * @since 1.2.2
 * @since 2.5.0 Add the $user_login parameter.
 * @since 5.0.0 Change $user_login parameter to more general $salutation.
 *
 * @param int|bool $user_id    ID of the new user, false if BP_SIGNUPS_SKIP_USER_CREATION is true.
 * @param string   $user_email   Email address of the new user.
 * @param string   $key          Activation key.
 * @param string   $salutation   Optional. The name to be used as a salutation in the email.
 */
function bp_core_signup_send_validation_email( $user_id, $user_email, $key, $salutation = '' ) {
	$args = array(
		'tokens' => array(
			'activate.url' => esc_url( trailingslashit( bp_get_activation_page() ) . "{$key}/" ),
			'key'          => $key,
			'user.email'   => $user_email,
			'user.id'      => $user_id,
		),
	);

	$to = array( array( $user_email => $salutation ) );

	bp_send_email( 'core-user-registration', $to, $args );

	// Record that the activation email has been sent.
	$signup = bp_members_get_signup_by( 'activation_key', $key );

	if ( $signup ) {
		$meta = array(
			'sent_date'  => current_time( 'mysql', true ),
			'count_sent' => $signup->count_sent + 1
		);

		BP_Signup::update( array(
			'signup_id' => $signup->id,
			'meta'      => $meta,
		) );
	}
}

/**
 * Display a "resend email" link when an unregistered user attempts to log in.
 *
 * @since 1.2.2
 *
 * @param WP_User|WP_Error|null $user     Either the WP_User or the WP_Error object.
 * @param string                $username The inputted, attempted username.
 * @param string                $password The inputted, attempted password.
 * @return WP_User|WP_Error
 */
function bp_core_signup_disable_inactive( $user = null, $username = '', $password ='' ) {
	// Login form not used.
	if ( empty( $username ) && empty( $password ) ) {
		return $user;
	}

	// An existing WP_User with a user_status of 2 is either a legacy
	// signup, or is a user created for backward compatibility. See
	// {@link bp_core_signup_user()} for more details.
	if ( is_a( $user, 'WP_User' ) && 2 == $user->user_status ) {
		$user_login = $user->user_login;

	// If no WP_User is found corresponding to the username, this
	// is a potential signup.
	} elseif ( is_wp_error( $user ) && 'invalid_username' == $user->get_error_code() ) {
		$user_login = $username;

	// This is an activated user, so bail.
	} else {
		return $user;
	}

	// Look for the unactivated signup corresponding to the login name.
	$signup = BP_Signup::get( array( 'user_login' => sanitize_user( $user_login ) ) );

	// No signup or more than one, something is wrong. Let's bail.
	if ( empty( $signup['signups'][0] ) || $signup['total'] > 1 ) {
		return $user;
	}

	// Unactivated user account found!
	/*
	 * Don't allow users to resend their own activation email
	 * when membership requests are enabled.
	 */
	if ( bp_get_membership_requests_required() ) {
		$error_message = sprintf(
			'<strong>%1$s</strong> %2$s',
			esc_html_x( 'Error:', 'Warning displayed on the WP Login screen', 'buddypress' ),
			esc_html_x( 'Your membership request has not yet been approved.', 'Error message displayed on the WP Login screen', 'buddypress' )
		);
	} else {
		// Set up the feedback message.
		$signup_id = $signup['signups'][0]->signup_id;

		$resend_url_params = array(
			'action' => 'bp-resend-activation',
			'id'     => $signup_id,
		);

		$resend_url = wp_nonce_url(
			add_query_arg( $resend_url_params, wp_login_url() ),
			'bp-resend-activation'
		);

		$error_message = sprintf(
			'<strong>%1$s</strong> %2$s<br /><br />%3$s',
			esc_html_x( 'Error:', 'Warning displayed on the WP Login screen', 'buddypress' ),
			esc_html_x( 'Your account has not been activated. Check your email for the activation link.', 'Error message displayed on the WP Login screen', 'buddypress' ),
			sprintf(
				/* translators: %s: the link to resend the activation email. */
				esc_html_x( 'If you have not received an email yet, %s.', 'WP Login screen message', 'buddypress' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $resend_url ),
					esc_html_x( 'click here to resend it', 'Text of the link to resend the activation email', 'buddypress' )
				)
			)
		);
	}

	return new WP_Error( 'bp_account_not_activated', $error_message );
}
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 30, 3 );

/**
 * On the login screen, resends the activation email for a user.
 *
 * @since 2.0.0
 *
 * @see bp_core_signup_disable_inactive()
 */
function bp_members_login_resend_activation_email() {
	global $error;

	if ( empty( $_GET['id'] ) || empty( $_GET['_wpnonce'] ) ) {
		return;
	}

	// Verify nonce.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bp-resend-activation' ) ) {
		die( 'Security check' );
	}

	$signup_id = (int) $_GET['id'];

	// Resend the activation email.
	// also updates the 'last sent' and '# of emails sent' values.
	$resend = BP_Signup::resend( array( $signup_id ) );

	// Add feedback message.
	if ( ! empty( $resend['errors'] ) ) {
		$error = __( '<strong>Error</strong>: Your account has already been activated.', 'buddypress' );
	} else {
		$error = __( 'Activation email resent! Please check your inbox or spam folder.', 'buddypress' );
	}
}
add_action( 'login_form_bp-resend-activation', 'bp_members_login_resend_activation_email' );

/**
 * Redirect away from wp-signup.php if BP registration templates are present.
 *
 * @since 1.1.0
 */
function bp_core_wpsignup_redirect() {

	// Bail in admin or if custom signup page is broken.
	if ( is_admin() || ! bp_has_custom_signup_page() ) {
		return;
	}

	$is_wp_signup = false;
	if ( ! empty( $_SERVER['SCRIPT_NAME'] ) ) {
		$script_name_path = wp_parse_url( $_SERVER['SCRIPT_NAME'], PHP_URL_PATH );

		if ( 'wp-signup.php' === basename( $script_name_path ) || ( 'wp-login.php' === basename( $script_name_path ) && ! empty( $_GET['action'] ) && 'register' === $_GET['action'] ) ) {
			$is_wp_signup = true;
		}
	}

	// If this is not wp-signup.php, there's nothing to do here.
	if ( ! $is_wp_signup ) {
		return;
	}

	/*
	 * We redirect wp-signup.php to the registration page except when it's a site signup.
	 * In that case, redirect to the BP site creation page if available, otherwise allow
	 * access to wp-signup.php.
	 */
	$redirect_to = bp_get_signup_page();

	$is_site_creation = false;

	$referer = wp_get_referer();

	// A new site is being added.
	if ( isset( $_POST['stage'] ) && $_POST['stage'] === 'gimmeanotherblog' ) {
		$is_site_creation = true;

	// We've arrived at wp-signup.php from my-sites.php.
	} elseif ( $referer ) {
		$referer_path     = wp_parse_url( $referer, PHP_URL_PATH );
		$is_site_creation = false !== strpos( $referer_path, 'wp-admin/my-sites.php' );
	}

	if ( $is_site_creation ) {
		if ( bp_is_active( 'blogs' ) ) {
			$redirect_to = trailingslashit( bp_get_blogs_directory_permalink() . 'create' );
		} else {
			// Perform no redirect in this case.
			$redirect_to = '';
		}
	}

	if ( ! $redirect_to ) {
		return;
	}

	bp_core_redirect( $redirect_to );
}
add_action( 'bp_init', 'bp_core_wpsignup_redirect' );

/**
 * Stop a logged-in user who is marked as a spammer.
 *
 * When an admin marks a live user as a spammer, that user can still surf
 * around and cause havoc on the site until that person is logged out.
 *
 * This code checks to see if a logged-in user is marked as a spammer.  If so,
 * we redirect the user back to wp-login.php with the 'reauth' parameter.
 *
 * This clears the logged-in spammer's cookies and will ask the spammer to
 * reauthenticate.
 *
 * Note: A spammer cannot log back in - {@see bp_core_boot_spammer()}.
 *
 * Runs on 'bp_init' at priority 5 so the members component globals are setup
 * before we do our spammer checks.
 *
 * This is important as the $bp->loggedin_user object is setup at priority 4.
 *
 * @since 1.8.0
 */
function bp_stop_live_spammer() {
	// If we're on the login page, stop now to prevent redirect loop.
	$is_login = false;
	if ( isset( $GLOBALS['pagenow'] ) && ( false !== strpos( $GLOBALS['pagenow'], 'wp-login.php' ) ) ) {
		$is_login = true;
	} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) ) {
		$is_login = true;
	}

	if ( $is_login ) {
		return;
	}

	// User isn't logged in, so stop!
	if ( ! is_user_logged_in() ) {
		return;
	}

	// If spammer, redirect to wp-login.php and reauthorize.
	if ( bp_is_user_spammer( bp_loggedin_user_id() ) ) {
		// Setup login args.
		$args = array(
			// Custom action used to throw an error message.
			'action' => 'bp-spam',

			// Reauthorize user to login.
			'reauth' => 1
		);

		/**
		 * Filters the url used for redirection for a logged in user marked as spam.
		 *
		 * @since 1.8.0
		 *
		 * @param string $value URL to redirect user to.
		 */
		$login_url = apply_filters( 'bp_live_spammer_redirect', add_query_arg( $args, wp_login_url() ) );

		// Redirect user to login page.
		wp_redirect( $login_url );
		die();
	}
}
add_action( 'bp_init', 'bp_stop_live_spammer', 5 );

/**
 * Show a custom error message when a logged-in user is marked as a spammer.
 *
 * @since 1.8.0
 */
function bp_live_spammer_login_error() {
	global $error;

	$error = __( '<strong>Error</strong>: Your account has been marked as a spammer.', 'buddypress' );

	// Shake shake shake!
	add_action( 'login_head', 'wp_shake_js', 12 );
}
add_action( 'login_form_bp-spam', 'bp_live_spammer_login_error' );

/**
 * Get the displayed user Object
 *
 * @since 2.6.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @return object The displayed user object, null otherwise.
 */
function bp_get_displayed_user() {
	$bp = buddypress();

	$displayed_user = null;
	if ( ! empty( $bp->displayed_user->id ) ) {
		$displayed_user = $bp->displayed_user;
	}

	/**
	 * Filters the displayed_user object corresponding to the displayed member.
	 *
	 * @since 2.6.0
	 *
	 * @param object $displayed_user The displayed_user object.
	 */
	return apply_filters( 'bp_get_displayed_user', $displayed_user );
}

/** Member Types *************************************************************/

/**
 * Output the slug of the member type taxonomy.
 *
 * @since 2.7.0
 */
function bp_member_type_tax_name() {
	echo bp_get_member_type_tax_name();
}
	/**
	 * Return the slug of the member type taxonomy.
	 *
	 * @since 2.7.0
	 *
	 * @return string The unique member taxonomy slug.
	 */
	function bp_get_member_type_tax_name() {

		/**
		 * Filters the slug of the member type taxonomy.
		 *
		 * @since 2.7.0
		 *
		 * @param string $value Member type taxonomy slug.
		 */
		return apply_filters( 'bp_get_member_type_tax_name', 'bp_member_type' );
	}

/**
 * Returns labels used by the member type taxonomy.
 *
 * @since 7.0.0
 *
 * @return array
 */
function bp_get_member_type_tax_labels() {

	/**
	 * Filters Member type taxonomy labels.
	 *
	 * @since 7.0.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters(
		'bp_get_member_type_tax_labels',
		array(
			// General labels.
			'name'                       => _x( 'Member Types', 'Member type taxonomy name', 'buddypress' ),
			'singular_name'              => _x( 'Member Type', 'Member type taxonomy singular name', 'buddypress' ),
			'search_items'               => _x( 'Search Member Types', 'Member type taxonomy search items label', 'buddypress' ),
			'popular_items'              => _x( 'Popular Member Types', 'Member type taxonomy popular items label', 'buddypress' ),
			'all_items'                  => _x( 'All Member Types', 'Member type taxonomy all items label', 'buddypress' ),
			'parent_item'                => _x( 'Parent Member Type', 'Member type taxonomy parent item label', 'buddypress' ),
			'parent_item_colon'          => _x( 'Parent Member Type:', 'Member type taxonomy parent item label', 'buddypress' ),
			'edit_item'                  => _x( 'Edit Member Type', 'Member type taxonomy edit item label', 'buddypress' ),
			'view_item'                  => _x( 'View Member Type', 'Member type taxonomy view item label', 'buddypress' ),
			'update_item'                => _x( 'Update Member Type', 'Member type taxonomy update item label', 'buddypress' ),
			'add_new_item'               => _x( 'Add New Member Type', 'Member type taxonomy add new item label', 'buddypress' ),
			'new_item_name'              => _x( 'New Member Type Name', 'Member type taxonomy new item name label', 'buddypress' ),
			'separate_items_with_commas' => _x( 'Separate member types with commas', 'Member type taxonomy separate items with commas label', 'buddypress' ),
			'add_or_remove_items'        => _x( 'Add or remove member types', 'Member type taxonomy add or remove items label', 'buddypress' ),
			'choose_from_most_used'      => _x( 'Choose from the most used meber types', 'Member type taxonomy choose from most used label', 'buddypress' ),
			'not_found'                  => _x( 'No member types found.', 'Member type taxonomy not found label', 'buddypress' ),
			'no_terms'                   => _x( 'No member types', 'Member type taxonomy no terms label', 'buddypress' ),
			'items_list_navigation'      => _x( 'Member Types list navigation', 'Member type taxonomy items list navigation label', 'buddypress' ),
			'items_list'                 => _x( 'Member Types list', 'Member type taxonomy items list label', 'buddypress' ),

			/* translators: Tab heading when selecting from the most used terms. */
			'most_used'                  => _x( 'Most Used', 'Member type taxonomy most used items label', 'buddypress' ),
			'back_to_items'              => _x( '&larr; Back to Member Types', 'Member type taxonomy back to items label', 'buddypress' ),

			// Specific to BuddyPress.
			'bp_type_id_label'           => _x( 'Member Type ID', 'BP Member type ID label', 'buddypress' ),
			'bp_type_id_description'     => _x( 'Enter a lower-case string without spaces or special characters (used internally to identify the member type).', 'BP Member type ID description', 'buddypress' ),
			'bp_type_show_in_list'       => _x( 'Show on Member', 'BP Member type show in list', 'buddypress' ),
		)
	);
}

/**
 * Returns arguments used by the Member type taxonomy.
 *
 * @since 7.0.0
 *
 * @return array
 */
function bp_get_member_type_tax_args() {

	/**
	 * Filters Member type taxonomy args.
	 *
	 * @since 7.0.0
	 *
	 * @param array $value Associative array (key => arg).
	 */
	return apply_filters(
		'bp_get_member_type_tax_args',
		array_merge(
			array(
				'description' => _x( 'BuddyPress Member Types', 'Member type taxonomy description', 'buddypress' ),
				'labels'      => array_merge( bp_get_member_type_tax_labels(), bp_get_taxonomy_common_labels() ),
			),
			bp_get_taxonomy_common_args()
		)
	);
}

/**
 * Extend generic Type metadata schema to match Member Type needs.
 *
 * @since 7.0.0
 *
 * @param array  $schema   The generic Type metadata schema.
 * @param string $taxonomy The taxonomy name the schema applies to.
 * @return array           The Member Type metadata schema.
 */
function bp_get_member_type_metadata_schema( $schema = array(), $taxonomy = '' ) {
	if ( bp_get_member_type_tax_name() === $taxonomy ) {

		// Directory.
		if ( isset( $schema['bp_type_has_directory']['description'] ) ) {
			$schema['bp_type_has_directory']['description'] = __( 'Make a list of members matching this type available on the members directory.', 'buddypress' );
		}

		// Slug.
		if ( isset( $schema['bp_type_directory_slug']['description'] ) ) {
			$schema['bp_type_directory_slug']['description'] = __( 'Enter if you want the type slug to be different from its ID.', 'buddypress' );
		}

		// List.
		$schema['bp_type_show_in_list'] = array(
			'description'       => __( 'Show where member types may be listed, like in the member header.', 'buddypress' ),
			'type'              => 'boolean',
			'single'            => true,
			'sanitize_callback' => 'absint',
		);
	}

	return $schema;
}
add_filter( 'bp_get_type_metadata_schema', 'bp_get_member_type_metadata_schema', 1, 2 );

/**
 * Registers the Member type metadata.
 *
 * @since 7.0.0
 */
function bp_register_member_type_metadata() {
	$type_taxonomy = bp_get_member_type_tax_name();

	foreach ( bp_get_type_metadata_schema( false, $type_taxonomy ) as $meta_key => $meta_args ) {
		bp_register_type_meta( $type_taxonomy, $meta_key, $meta_args );
	}
}
add_action( 'bp_register_type_metadata', 'bp_register_member_type_metadata' );

/**
 * Register a member type.
 *
 * @since 2.2.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @param string $member_type Unique string identifier for the member type.
 * @param array  $args {
 *     Array of arguments describing the member type.
 *
 *     @type array       $labels {
 *         Array of labels to use in various parts of the interface.
 *
 *         @type string $name          Default name. Should typically be plural.
 *         @type string $singular_name Singular name.
 *     }
 *     @type bool|string $has_directory Whether the member type should have its own type-specific directory.
 *                                      Pass `true` to use the `$member_type` string as the type's slug.
 *                                      Pass a string to customize the slug. Pass `false` to disable.
 *                                      Default: true.
 *     @type bool        $show_in_list  Whether this member type should be shown in lists rendered by
 *                                      bp_member_type_list(). Default: false.
 *     @type bool        $code          Whether this member type is registered using code. Default: true.
 *     @type int         $db_id         The member type term ID. Default: 0.
 * }
 * @return object|WP_Error Member type object on success, WP_Error object on failure.
 */
function bp_register_member_type( $member_type, $args = array() ) {
	$bp = buddypress();

	if ( isset( $bp->members->types[ $member_type ] ) ) {
		return new WP_Error( 'bp_member_type_exists', __( 'Member type already exists.', 'buddypress' ), $member_type );
	}

	$r = bp_parse_args(
		$args,
		array(
			'labels'        => array(),
			'has_directory' => true,
			'show_in_list'  => false,
			'code'          => true,
			'db_id'         => 0,
		),
		'register_member_type'
	);

	$member_type = sanitize_key( $member_type );

	/**
	 * Filters the list of illegal member type names.
	 *
	 * - 'any' is a special pseudo-type, representing items unassociated with any member type.
	 * - 'null' is a special pseudo-type, representing users without any type.
	 * - '_none' is used internally to denote an item that should not apply to any member types.
	 *
	 * @since 2.4.0
	 *
	 * @param array $illegal_names Array of illegal names.
	 */
	$illegal_names = apply_filters( 'bp_member_type_illegal_names', array( 'any', 'null', '_none' ) );
	if ( in_array( $member_type, $illegal_names, true ) ) {
		return new WP_Error( 'bp_member_type_illegal_name', __( 'You may not register a member type with this name.', 'buddypress' ), $member_type );
	}

	// Store the post type name as data in the object (not just as the array key).
	$r['name'] = $member_type;

	// Make sure the relevant labels have been filled in.
	$default_name = isset( $r['labels']['name'] ) ? $r['labels']['name'] : ucfirst( $r['name'] );
	$r['labels'] = array_merge( array(
		'name'          => $default_name,
		'singular_name' => $default_name,
	), $r['labels'] );

	// Directory slug.
	if ( $r['has_directory'] ) {
		// A string value is intepreted as the directory slug. Otherwise fall back on member type.
		if ( is_string( $r['has_directory'] ) ) {
			$directory_slug = $r['has_directory'];
		} else {
			$directory_slug = $member_type;
		}

		// Sanitize for use in URLs.
		$r['directory_slug'] = sanitize_title( $directory_slug );
		$r['has_directory']  = true;
	} else {
		$r['directory_slug'] = '';
		$r['has_directory']  = false;
	}

	// Show the list of member types on front-end (member header, for now).
	$r['show_in_list'] = (bool) $r['show_in_list'];

	$bp->members->types[ $member_type ] = $type = (object) $r;

	/**
	 * Fires after a member type is registered.
	 *
	 * @since 2.2.0
	 *
	 * @param string $member_type Member type identifier.
	 * @param object $type        Member type object.
	 */
	do_action( 'bp_registered_member_type', $member_type, $type );

	return $type;
}

/**
 * Retrieve a member type object by name.
 *
 * @since 2.2.0
 *
 * @param string $member_type The name of the member type.
 * @return object|null A member type object or null if it doesn't exist.
 */
function bp_get_member_type_object( $member_type ) {
	$types = bp_get_member_types( array(), 'objects' );

	if ( empty( $types[ $member_type ] ) ) {
		return null;
	}

	return $types[ $member_type ];
}

/**
 * Get a list of all registered member type objects.
 *
 * @since 2.2.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @see bp_register_member_type() for accepted arguments.
 *
 * @param array|string $args     Optional. An array of key => value arguments to match against
 *                               the member type objects. Default empty array.
 * @param string       $output   Optional. The type of output to return. Accepts 'names'
 *                               or 'objects'. Default 'names'.
 * @param string       $operator Optional. The logical operation to perform. 'or' means only one
 *                               element from the array needs to match; 'and' means all elements
 *                               must match. Accepts 'or' or 'and'. Default 'and'.
 * @return array A list of member type names or objects.
 */
function bp_get_member_types( $args = array(), $output = 'names', $operator = 'and' ) {
	$bp    = buddypress();
	$types = $bp->members->types;

	// Merge with types available into the database.
	if ( ! isset( $args['code'] ) || true !== $args['code'] ) {
		$types = bp_get_taxonomy_types( bp_get_member_type_tax_name(), $types );
	}

	$types = array_filter( wp_filter_object_list( $types, $args, $operator ) );

	/**
	 * Filters the array of member type objects.
	 *
	 * This filter is run before the $output filter has been applied, so that
	 * filtering functions have access to the entire member type objects.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $types     Member type objects, keyed by name.
	 * @param array  $args      Array of key=>value arguments for filtering.
	 * @param string $operator  'or' to match any of $args, 'and' to require all.
	 */
	$types = (array) apply_filters( 'bp_get_member_types', $types, $args, $operator );

	if ( $types && 'names' === $output ) {
		$types = wp_list_pluck( $types, 'name' );
	}

	return $types;
}

/**
 * Only gets the member types registered by code.
 *
 * @since 7.0.0
 *
 * @return array The member types registered by code.
 */
function bp_get_member_types_registered_by_code() {
	return bp_get_member_types(
		array(
			'code' => true,
		),
		'objects'
	);
}
add_filter( bp_get_member_type_tax_name() . '_registered_by_code', 'bp_get_member_types_registered_by_code' );

/**
 * Generates missing metadata for a type registered by code.
 *
 * @since 7.0.0
 *
 * @return array The member type metadata.
 */
function bp_set_registered_by_code_member_type_metadata( $metadata = array(), $type = '' ) {
	$member_type = bp_get_member_type_object( $type );

	foreach ( get_object_vars( $member_type ) as $object_key => $object_value ) {
		if ( 'labels' === $object_key ) {
			foreach ( $object_value as $label_key => $label_value ) {
				$metadata[ 'bp_type_' . $label_key ] = $label_value;
			}
		} elseif ( ! in_array( $object_key, array( 'name', 'code', 'db_id' ), true ) ) {
			$metadata[ 'bp_type_' . $object_key ] = $object_value;
		}
	}

	/**
	 * Save metadata into database to avoid generating metadata
	 * each time a type is listed into the Types Admin screen.
	 */
	if ( isset( $member_type->db_id ) && $member_type->db_id ) {
		bp_update_type_metadata( $member_type->db_id, bp_get_member_type_tax_name(), $metadata );
	}

	return $metadata;
}
add_filter( bp_get_member_type_tax_name() . '_set_registered_by_code_metada', 'bp_set_registered_by_code_member_type_metadata', 10, 2 );

/**
 * Insert member types registered by code not yet saved into the database as WP Terms.
 *
 * @since 7.0.0
 */
function bp_insert_member_types_registered_by_code() {
	$all_types     = bp_get_member_types( array(), 'objects' );
	$unsaved_types = wp_filter_object_list( $all_types, array( 'db_id' => 0 ), 'and', 'name' );

	if ( $unsaved_types ) {
		foreach ( $unsaved_types as $type_name ) {
			bp_insert_term(
				$type_name,
				bp_get_member_type_tax_name(),
				array(
					'slug' => $type_name,
				)
			);
		}
	}
}
add_action( bp_get_member_type_tax_name() . '_add_form', 'bp_insert_member_types_registered_by_code', 1 );

/**
 * Set type for a member.
 *
 * @since 2.2.0
 * @since 7.0.0 $member_type parameter also accepts an array of member type names.
 *
 * @param int          $user_id     ID of the user.
 * @param string|array $member_type The member type name or an array of member type names.
 * @param bool         $append      Optional. True to append this to existing types for user,
 *                                  false to replace. Default: false.
 * @return bool|array $retval See {@see bp_set_object_terms()}.
 */
function bp_set_member_type( $user_id, $member_type, $append = false ) {
	// Pass an empty $member_type to remove a user's type.
	if ( ! empty( $member_type ) ) {
		$member_types = (array) $member_type;
		$valid_types  = array_filter( array_map( 'bp_get_member_type_object', $member_types ) );

		if ( $valid_types ) {
			$member_type = wp_list_pluck( $valid_types, 'name' );
		} else {
			return false;
		}
	}

	$retval = bp_set_object_terms( $user_id, $member_type, bp_get_member_type_tax_name(), $append );

	// Bust the cache if the type has been updated.
	if ( ! is_wp_error( $retval ) ) {
		wp_cache_delete( $user_id, 'bp_member_member_type' );

		/**
		 * Fires just after a user's member type has been changed.
		 *
		 * @since 2.2.0
		 *
		 * @param int          $user_id     ID of the user whose member type has been updated.
		 * @param string|array $member_type The member type name or an array of member type names.
		 * @param bool         $append      Whether the type is being appended to existing types.
		 */
		do_action( 'bp_set_member_type', $user_id, $member_type, $append );
	}

	return $retval;
}

/**
 * Remove type for a member.
 *
 * @since 2.3.0
 *
 * @param int    $user_id     ID of the user.
 * @param string $member_type Member Type.
 * @return bool|WP_Error
 */
function bp_remove_member_type( $user_id, $member_type ) {
	// Bail if no valid member type was passed.
	if ( empty( $member_type ) || ! bp_get_member_type_object( $member_type ) ) {
		return false;
	}

	// No need to continue if the member doesn't have the type.
	$existing_types = bp_get_member_type( $user_id, false );
	if ( ! is_array( $existing_types ) || ! in_array( $member_type, $existing_types, true ) ) {
		return false;
	}

	$deleted = bp_remove_object_terms( $user_id, $member_type, bp_get_member_type_tax_name() );

	// Bust the cache if the type has been removed.
	if ( ! is_wp_error( $deleted ) ) {
		wp_cache_delete( $user_id, 'bp_member_member_type' );

		/**
		 * Fires just after a user's member type has been removed.
		 *
		 * @since 2.3.0
		 *
		 * @param int    $user_id     ID of the user whose member type has been updated.
		 * @param string $member_type Member type.
		 */
		do_action( 'bp_remove_member_type', $user_id, $member_type );
	}

	return $deleted;
}

/**
 * Get type for a member.
 *
 * @since 2.2.0
 * @since 7.0.0 Adds the `$use_db` parameter.
 *
 * @param int  $user_id ID of the user.
 * @param bool $single  Optional. Whether to return a single type string. If multiple types are found
 *                      for the user, the oldest one will be returned. Default: true.
 * @param bool $use_db  Optional. Whether to request all member types or only the ones registered by code.
 *                      Default: true.
 * @return string|array|bool On success, returns a single member type (if $single is true) or an array of member
 *                           types (if $single is false). Returns false on failure.
 */
function bp_get_member_type( $user_id, $single = true, $use_db = true ) {
	$types = wp_cache_get( $user_id, 'bp_member_member_type' );

	if ( false === $types ) {
		$raw_types = bp_get_object_terms( $user_id, bp_get_member_type_tax_name() );

		if ( ! is_wp_error( $raw_types ) ) {
			$types = array();

			// Only include currently registered group types.
			foreach ( $raw_types as $mtype ) {
				if ( bp_get_member_type_object( $mtype->name ) ) {
					$types[] = $mtype->name;
				}
			}

			wp_cache_set( $user_id, $types, 'bp_member_member_type' );
		}
	}

	if ( false === $use_db && $types ) {
		$registred_by_code = bp_get_member_types_registered_by_code();
		$ctype_names       = wp_list_pluck( $registred_by_code, 'name' );
		$types             = array_intersect( $types, $ctype_names );
	}

	$type = false;
	if ( ! empty( $types ) ) {
		if ( $single ) {
			$type = array_pop( $types );
		} else {
			$type = $types;
		}
	}

	/**
	 * Filters a user's member type(s).
	 *
	 * @since 2.2.0
	 *
	 * @param string|array|bool $type    A single member type (if $single is true) or an array of member types
	 *                                   (if $single is false) or false on failure.
	 * @param int               $user_id ID of the user.
	 * @param bool              $single  Whether to return a single type string, or an array.
	 */
	return apply_filters( 'bp_get_member_type', $type, $user_id, $single );
}

/**
 * Check whether the given user has a certain member type.
 *
 * @since 2.3.0
 *
 * @param int    $user_id     $user_id ID of the user.
 * @param string $member_type Member Type.
 * @return bool Whether the user has the given member type.
 */
function bp_has_member_type( $user_id, $member_type ) {
	// Bail if no valid member type was passed.
	if ( empty( $member_type ) || ! bp_get_member_type_object( $member_type ) ) {
		return false;
	}

	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return false;
	}

	// Get all user's member types.
	$types = bp_get_member_type( $user_id, false );

	if ( ! is_array( $types ) ) {
		return false;
	}

	return in_array( $member_type, $types, true );
}

/**
 * Delete a user's member type when the user when the user is deleted.
 *
 * @since 2.2.0
 *
 * @param int $user_id ID of the user.
 * @return bool|array $value See {@see bp_set_member_type()}.
 */
function bp_remove_member_type_on_user_delete( $user_id ) {
	return bp_set_member_type( $user_id, '' );
}
add_action( 'wpmu_delete_user', 'bp_remove_member_type_on_user_delete' );

/**
 * Deletes user member type on the 'delete_user' hook.
 *
 * @since 6.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function bp_remove_member_type_on_delete_user( $user_id ) {
	if ( ! bp_remove_user_data_on_delete_user_hook( 'member_type', $user_id ) ) {
		return;
	}

	bp_remove_member_type_on_user_delete( $user_id );
}
add_action( 'delete_user', 'bp_remove_member_type_on_delete_user' );

/**
 * Get the "current" member type, if one is provided, in member directories.
 *
 * @since 2.3.0
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @return string
 */
function bp_get_current_member_type() {
	$bp = buddypress();

	/**
	 * Filters the "current" member type, if one is provided, in member directories.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value "Current" member type.
	 */
	return apply_filters( 'bp_get_current_member_type', $bp->current_member_type );
}

/**
 * Setup the avatar upload directory for a user.
 *
 * @since 6.0.0
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 * @return array Array containing the path, URL, and other helpful settings.
 */
function bp_members_avatar_upload_dir( $directory = 'avatars', $user_id = 0 ) {

	// Use displayed user if no user ID was passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Failsafe against accidentally nooped $directory parameter.
	if ( empty( $directory ) ) {
		$directory = 'avatars';
	}

	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $user_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $user_id;

	/**
	 * Filters the avatar upload directory for a user.
	 *
	 * @since 6.0.0
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters( 'bp_members_avatar_upload_dir', array(
		'path'    => $path,
		'url'     => $newurl,
		'subdir'  => $newsubdir,
		'basedir' => $newbdir,
		'baseurl' => $newburl,
		'error'   => false,
	) );
}

/**
 * Send welcome email on successful user activation.
 *
 * @since 8.0.0
 *
 * @param int $user_id The new user's ID.
 */
function bp_send_welcome_email( $user_id = 0 ) {
	if ( ! $user_id ) {
		return;
	}

	$profile_url = bp_core_get_user_domain( $user_id );

	/**
	 * Use this filter to add/edit/remove tokens to use for your welcome email.
	 *
	 * @since 8.0.0
	 *
	 * @param array $value   An array of BP Email tokens.
	 * @param int   $user_id The user ID.
	 */
	$welcome_tokens = apply_filters(
		'bp_send_welcome_email_tokens',
		array(
			'displayname'      => bp_core_get_user_displayname( $user_id ),
			'profile.url'      => $profile_url,
			'lostpassword.url' => wp_lostpassword_url( $profile_url ),
		),
		$user_id
	);

	bp_send_email( 'core-user-activation', $user_id, array( 'tokens' => $welcome_tokens ) );
}
add_action( 'bp_core_activated_user', 'bp_send_welcome_email', 10, 1 );

/**
 * Get invitations to the BP community filtered by arguments.
 *
 * @since 8.0.0
 *
 * @param array $args Invitation arguments. See BP_Invitation::get() for list.
 * @return array $invites Matching BP_Invitation objects.
 */
function bp_members_invitations_get_invites( $args = array() ) {
	$invites_class = new BP_Members_Invitation_Manager();
	return $invites_class->get_invitations( $args );
}

/**
 * Check whether a user has sent any community invitations.
 *
 * @since 8.0.0
 *
 * @param int $user_id ID of user to check for invitations sent by.
 *                     Defaults to the current user's ID.
 *
 * @return bool $invites True if user has sent invites.
 */
function bp_members_invitations_user_has_sent_invites( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bp_loggedin_user_id();
		if ( ! $user_id ) {
			return false;
		}
	}
	$invites_class = new BP_Members_Invitation_Manager();
	$args = array(
		'inviter_id' => $user_id,
	);
	return (bool) $invites_class->invitation_exists( $args );
}

/**
 * Invite a user to a BP community.
 *
 * @since 8.0.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $invitee_email Email address of the user being invited.
 *     @type int    $network_id    ID of the network to which the user is being invited.
 *     @type int    $inviter_id    Optional. ID of the inviting user. Default:
 *                                 ID of the logged-in user.
 *     @type string $date_modified Optional. Modified date for the invitation.
 *                                 Default: current date/time.
 *     @type string $content       Optional. Message to invitee.
 *     @type bool   $send_invite   Optional. Whether the invitation should be
 *                                 sent now. Default: false.
 * }
 * @return bool True on success, false on failure.
 */
function bp_members_invitations_invite_user( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'invitee_email' => '',
			'network_id'    => get_current_network_id(),
			'inviter_id'    => bp_loggedin_user_id(),
			'date_modified' => bp_core_current_time(),
			'content'       => '',
			'send_invite'   => 0,
		),
		'members_invitations_invite_user'
	);

	$inv_args = array(
		'invitee_email' => $r['invitee_email'],
		'item_id'       => $r['network_id'],
		'inviter_id'    => $r['inviter_id'],
		'date_modified' => $r['date_modified'],
		'content'       => $r['content'],
		'send_invite'   => $r['send_invite'],
	);

	// Create the invitataion.
	$invites_class = new BP_Members_Invitation_Manager();
	$created       = $invites_class->add_invitation( $inv_args );

	/**
	 * Fires after the creation of a new network invite.
	 *
	 * @since 8.0.0
	 *
	 * @param array    $r       Array of parsed arguments for the network invite.
	 * @param int|bool $created The ID of the invitation or false if it couldn't be created.
	 */
	do_action( 'bp_members_invitations_invite_user', $r, $created );

	return $created;
}

/**
 * Resend a membership invitation email by id.
 *
 * @since 8.0.0
 *
 * @param int $id ID of the invitation to resend.
 * @return bool True on success, false on failure.
 */
function bp_members_invitation_resend_by_id( $id = 0 ) {

	// Find the invitation before resending it.
	$existing_invite = new BP_Invitation( $id );
	$invites_class   = new BP_Members_Invitation_Manager();
	$success         = $invites_class->send_invitation_by_id( $id );

	if ( ! $success ) {
		return $success;
	}

	/**
	 * Fires after the re-sending of a network invite.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $existing_invite The invitation that was resent.
	 */
	do_action( 'bp_members_invitations_resend_invitation', $existing_invite );

	return $success;
}

/**
 * Delete a membership invitation by id.
 *
 * @since 8.0.0
 *
 * @param int $id ID of the invitation to delete.
 * @return int|bool Number of rows deleted on success, false on failure.
 */
function bp_members_invitations_delete_by_id( $id = 0 ) {

	// Find the invitation before deleting it.
	$existing_invite = new BP_Invitation( $id );
	$invites_class   = new BP_Members_Invitation_Manager();
	$success         = $invites_class->delete_by_id( $id );

	if ( ! $success ) {
		return $success;
	}

	// Run a different action depending on the status of the invite.
	if ( ! $existing_invite->invite_sent ) {
		/**
		 * Fires after the deletion of an unsent community invite.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_Invitation $existing_invite The invitation to be deleted.
		 */
		do_action( 'bp_members_invitations_canceled_invitation', $existing_invite );
	} else if ( ! $existing_invite->accepted ) {
		/**
		 * Fires after the deletion of a sent, but not yet accepted, community invite.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_Invitation $existing_invite The invitation to be deleted.
		 */
		do_action( 'bp_members_invitations_revoked_invitation', $existing_invite );
	} else {
		/**
		 * Fires after the deletion of a sent and accepted community invite.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_Invitation $existing_invite The invitation to be deleted.
		 */
		do_action( 'bp_members_invitations_deleted_invitation', $existing_invite );
	}

	return $success;
}

/**
 * Delete a membership invitation.
 *
 * @since 8.0.0
 *
 * @param intring $args {
 *     Array of arguments.
 *     @type int|array $id            Id(s) of the invitation(s) to remove.
 *     @type int       $invitee_email Email address of the user being invited.
 *     @type int       $network_id    ID of the network to which the user is being invited.
 *     @type int       $inviter_id    ID of the inviting user.
 *     @type int       $accepted      Whether the invitation has been accepted yet.
 *     @type int       $invite_sent   Whether the invitation has been sent yet.
 * }
 * @return bool True if all were deleted.
 */
function bp_members_invitations_delete_invites( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,
			'invitee_email' => '',
			'network_id'    => get_current_network_id(),
			'inviter_id'    => null,
			'accepted'      => null,
			'invite_sent'   => null,
		),
		'members_invitations_delete_invites'
	);

	$inv_args = array(
		'id'            => $r['id'],
		'invitee_email' => $r['invitee_email'],
		'item_id'       => $r['network_id'],
		'inviter_id'    => $r['inviter_id'],
		'accepted'      => $r['accepted'],
		'invite_sent'   => $r['invite_sent'],
	);

	// Find the invitation(s).
	$invites     = bp_members_invitations_get_invites( $inv_args );
	$total_count = count( $invites );

	// Loop through, deleting each invitation.
	$deleted = 0;
	foreach ( $invites as $invite ) {
		$success = bp_members_invitations_delete_by_id( $invite->id );
		if ( $success ) {
			$deleted++;
		}
	}

	return $deleted === $total_count;
}

/**
 * Get hash based on details of a membership invitation and the inviter.
 *
 * @since 8.0.0
 *
 * @param BP_Invitation $invitation Invitation to create hash from.
 *
 * @return string $hash Calculated sha1 hash.
 */
function bp_members_invitations_get_hash( $invitation ) {
	$hash = false;

	if ( ! empty( $invitation->id ) ) {
		$inviter_ud = get_userdata( $invitation->inviter_id );
		if ( $inviter_ud ) {
			/*
			 * Use some inviter details as part of the hash so that invitations from
			 * users who are subsequently marked as spam will be invalidated.
			 */
			$hash = wp_hash( "{$invitation->inviter_id}:{$invitation->invitee_email}:{$inviter_ud->user_status}:{$inviter_ud->user_registered}" );
		}
	}

	// If there's a problem, return a string that will change and thus fail.
	if ( ! $hash ) {
		$hash = wp_generate_password( 32, false );
	}

	/**
	 * Filters the hash calculated by the invitation details.
	 *
	 * @since 8.0.0
	 *
	 * @param string        $hash       Calculated sha1 hash.
	 * @param BP_Invitation $invitation Invitation hash was created from.
	 */
	return apply_filters( 'bp_members_invitations_get_hash', $hash, $invitation );
}

/**
 * Get the current invitation specified by the $_GET parameters.
 *
 * @since 8.0.0
 *
 * @return BP_Invitation $invite Invitation specified by the $_GET parameters.
 */
function bp_get_members_invitation_from_request() {
	$invites_class = new BP_Members_Invitation_Manager();
	$invite        = $invites_class->get_by_id( 0 );

	if ( bp_get_members_invitations_allowed() && ! empty( $_GET['inv'] ) ) {
		// Check to make sure the passed hash matches a calculated hash.
		$maybe_invite = $invites_class->get_by_id( absint( $_GET['inv'] ) );
		$hash         = bp_members_invitations_get_hash( $maybe_invite );

		if ( $_GET['ih'] === $hash ) {
			$invite = $maybe_invite;
		}
	}

	/**
	 * Filters the invitation specified by the $_GET parameters.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite Invitation specified by the $_GET parameters.
	 */
	return apply_filters( 'bp_get_members_invitation_from_request', $invite );
}

/**
 * Get WP_User object corresponding to a record in the signups table.
 *
 * @since 10.0.0
 *
 * @param string $field Which fields to search by. Possible values are
 *                      activation_key, user_email, id.
 * @param string $value Value to search by.
 * @return bool|BP_Signup $signup Found signup, returns first found
 *                                if more than one is found.
 */
function bp_members_get_signup_by( $field = 'activation_key', $value = '' ) {
	switch ( $field ) {
		case 'activation_key':
		case 'user_email':
			$key = $field;
			break;

		case 'id':
		default:
			$key = 'include';
			break;
	}

	$signups = BP_Signup::get(
		array(
			$key => $value,
		)
	);

	if ( ! empty( $signups['signups'] ) ) {
		$signup = current( $signups['signups'] );
	} else {
		$signup = false;
	}

	return $signup;
}

/**
 * Are site creation requests currently enabled?
 *
 * @since 10.0.0
 *
 * @return bool Whether site requests are currently enabled.
 */
function bp_members_site_requests_enabled() {

	$matches = array( 'blog', 'all' );

	return is_multisite() && in_array( bp_core_get_root_option( 'registration' ), $matches, true );
}

/**
 * Returns the strength score a password needs to have to be used by a member.
 *
 * Score => Allowed Strength.
 * 0     => any passwords.
 * 1     => at least short passwords.
 * 2     => at least weak passwords.
 * 3     => at least good passwords.
 * 4     => at least strong passwords.
 *
 * @since 10.0.0
 *
 * @return int the strength score a password needs to have to be used by a member.
 */
function bp_members_user_pass_required_strength() {
	$default_strength = 0;
	if ( defined( 'BP_MEMBERS_REQUIRED_PASSWORD_STRENGTH' ) && BP_MEMBERS_REQUIRED_PASSWORD_STRENGTH ) {
		$default_strength = (int) BP_MEMBERS_REQUIRED_PASSWORD_STRENGTH;
	}

	/**
	 * Filter here to raise the strength score user passwords need to reach to be allowed.
	 *
	 * @since 10.0.0
	 *
	 * @param int $default_strength The strength score user passwords need to reach to be allowed.
	 */
	return (int) apply_filters( 'bp_members_user_pass_required_strength', $default_strength );
}
