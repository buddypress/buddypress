<?php
/**
 * BuddyPress Member Template Tags.
 *
 * Functions that are safe to use inside your template files and themes.
 *
 * @package BuddyPress
 * @subpackage MembersTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the profile component slug.
 *
 * @since 2.4.0
 */
function bp_profile_slug() {
	echo esc_attr( bp_get_profile_slug() );
}
	/**
	 * Return the profile component slug.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	function bp_get_profile_slug() {

		/**
		 * Filters the profile component slug.
		 *
		 * @since 2.4.0
		 *
		 * @param string $slug Profile component slug.
		 */
		return apply_filters( 'bp_get_profile_slug', buddypress()->profile->slug );
	}

/**
 * Output the members component slug.
 *
 * @since 1.5.0
 */
function bp_members_slug() {
	echo esc_attr( bp_get_members_slug() );
}
	/**
	 * Return the members component slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_members_slug() {

		/**
		 * Filters the Members component slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $slug Members component slug.
		 */
		return apply_filters( 'bp_get_members_slug', buddypress()->members->slug );
	}

/**
 * Output the members component root slug.
 *
 * @since 1.5.0
 */
function bp_members_root_slug() {
	echo esc_attr( bp_get_members_root_slug() );
}
	/**
	 * Return the members component root slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_members_root_slug() {

		/**
		 * Filters the Members component root slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $root_slug Members component root slug.
		 */
		return apply_filters( 'bp_get_members_root_slug', buddypress()->members->root_slug );
	}

/**
 * Output the member type base slug.
 *
 * @since 2.5.0
 */
function bp_members_member_type_base() {
	echo esc_attr( bp_get_members_member_type_base() );
}
	/**
	 * Get the member type URL base.
	 *
	 * The base slug is the string used as the base prefix when generating member type directory URLs.
	 * For example, in example.com/members/type/foo/, 'foo' is the member type and 'type' is the
	 * base slug.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	function bp_get_members_member_type_base() {

		/**
		 * Filters the member type URL base.
		 *
		 * @since 2.3.0
		 *
		 * @param string $base Base slug for the member type.
		 */
		return apply_filters( 'bp_members_member_type_base', _x( 'type', 'member type URL base', 'buddypress' ) );
	}

/**
 * Output member directory permalink.
 *
 * @since 1.5.0
 */
function bp_members_directory_permalink() {
	echo esc_url( bp_get_members_directory_permalink() );
}
	/**
	 * Return member directory permalink.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_members_directory_permalink() {
		$url = bp_rewrites_get_url(
			array(
				'component_id' => 'members',
			)
		);

		/**
		 * Filters the member directory permalink.
		 *
		 * @since 1.5.0
		 *
		 * @param string $url Members directory permalink.
		 */
		return apply_filters( 'bp_get_members_directory_permalink', $url );
	}

/**
 * Output member type directory permalink.
 *
 * @since 2.5.0
 *
 * @param string $member_type Optional. Member type. Defaults to current member type.
 */
function bp_member_type_directory_permalink( $member_type = '' ) {
	echo esc_url( bp_get_member_type_directory_permalink( $member_type ) );
}
	/**
	 * Return member type directory permalink.
	 *
	 * @since 2.5.0
	 *
	 * @param string $member_type Optional. Member type. Defaults to current member type.
	 * @return string Member type directory URL on success, an empty string on failure.
	 */
	function bp_get_member_type_directory_permalink( $member_type = '' ) {

		if ( $member_type ) {
			$_member_type = $member_type;
		} else {
			// Fall back on the current member type.
			$_member_type = bp_get_current_member_type();
		}

		$type = bp_get_member_type_object( $_member_type );

		// Bail when member type is not found or has no directory.
		if ( ! $type || ! $type->has_directory ) {
			return '';
		}

		$url = bp_rewrites_get_url(
			array(
				'component_id'   => 'members',
				'directory_type' => $type->directory_slug,
			)
		);

		/**
		 * Filters the member type directory permalink.
		 *
		 * @since 2.5.0
		 *
		 * @param string $url         Member type directory permalink.
		 * @param object $type        Member type object.
		 * @param string $member_type Member type name, as passed to the function.
		 */
		return apply_filters( 'bp_get_member_type_directory_permalink', $url, $type, $member_type );
	}

/**
 * Output the sign-up slug.
 *
 * @since 1.5.0
 */
function bp_signup_slug() {
	echo esc_attr( bp_get_signup_slug() );
}
	/**
	 * Return the sign-up slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_signup_slug() {
		$bp   = buddypress();
		$slug = 'register';

		if ( ! empty( $bp->pages->register->slug ) ) {
			$slug = $bp->pages->register->slug;
		}

		/**
		 * Filters the sign-up slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $slug Sign-up slug.
		 */
		return apply_filters( 'bp_get_signup_slug', $slug );
	}

/**
 * Output the activation slug.
 *
 * @since 1.5.0
 */
function bp_activate_slug() {
	echo esc_attr( bp_get_activate_slug() );
}
	/**
	 * Return the activation slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_activate_slug() {
		$bp   = buddypress();
		$slug = 'activate';

		if ( ! empty( $bp->pages->activate->slug ) ) {
			$slug = $bp->pages->activate->slug;
		}

		/**
		 * Filters the activation slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $slug Activation slug.
		 */
		return apply_filters( 'bp_get_activate_slug', $slug );
	}

/**
 * Output the members invitation pane slug.
 *
 * @since 8.0.0
 */
function bp_members_invitations_slug() {
	echo esc_attr( bp_get_members_invitations_slug() );
}
	/**
	 * Return the members invitations root slug.
	 *
	 * @since 8.0.0
	 *
	 * @return string
	 */
	function bp_get_members_invitations_slug() {

		/**
		 * Filters the Members invitations pane root slug.
		 *
		 * @since 8.0.0
		 *
		 * @param string $slug Members invitations pane root slug.
		 */
		return apply_filters( 'bp_get_members_invitations_slug', _x( 'invitations', 'Member profile invitations pane URL base', 'buddypress' ) );
	}

/**
 * Initialize the members loop.
 *
 * Based on the $args passed, bp_has_members() populates the $members_template
 * global, enabling the use of BuddyPress templates and template functions to
 * display a list of members.
 *
 * @since 1.2.0
 * @since 7.0.0 Added `xprofile_query` parameter. Added `user_ids` parameter.
 * @since 10.0.0 Added `date_query` parameter.
 *
 * @global BP_Core_Members_Template $members_template The main member template loop class.
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the members loop. Most arguments
 *     are in the same format as {@link BP_User_Query}. However, because
 *     the format of the arguments accepted here differs in a number of ways,
 *     and because bp_has_members() determines some default arguments in a
 *     dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL query
 *     string (eg, 'user_id=4&per_page=3').
 *
 *     @type int                   $type                Sort order. Accepts 'active', 'random', 'newest', 'popular',
 *                                                      'online', 'alphabetical'. Default: 'active'.
 *     @type int|bool              $page                Page of results to display. Default: 1.
 *     @type int|bool              $per_page            Number of results per page. Default: 20.
 *     @type int|bool              $max                 Maximum number of results to return. Default: false (unlimited).
 *     @type string                $page_arg            The string used as a query parameter in pagination links.
 *                                                      Default: 'bpage'.
 *     @type array|int|string|bool $include             Limit results by a list of user IDs. Accepts an array, a
 *                                                      single integer, a comma-separated list of IDs, or false (to
 *                                                      disable this limiting). Accepts 'active', 'alphabetical',
 *                                                      'newest', or 'random'. Default: false.
 *     @type array|int|string|bool $exclude             Exclude users from results by ID. Accepts an array, a single
 *                                                      integer, a comma-separated list of IDs, or false (to disable
 *                                                      this limiting). Default: false.
 *     @type array|string|bool     $user_ids            An array or comma-separated list of IDs, or false (to
 *                                                      disable this limiting). Default: false.
 *     @type int                   $user_id             If provided, results are limited to the friends of the specified
 *                                                      user. When on a user's Friends page, defaults to the ID of the
 *                                                      displayed user. Otherwise defaults to 0.
 *     @type string|array          $member_type         Array or comma-separated list of member types to limit
 *                                                      results to.
 *     @type string|array          $member_type__in     Array or comma-separated list of member types to limit
 *                                                      results to.
 *     @type string|array          $member_type__not_in Array or comma-separated list of member types to exclude
 *                                                      from results.
 *     @type string                $search_terms        Limit results by a search term. Default: value of
 *                                                      `$_REQUEST['members_search']` or `$_REQUEST['s']`, if present.
 *                                                      Otherwise false.
 *     @type string                $meta_key            Limit results by the presence of a usermeta key.
 *                                                      Default: false.
 *     @type mixed                 $meta_value          When used with meta_key, limits results by the a matching
 *                                                      usermeta value. Default: false.
 *     @type array                 $xprofile_query      Filter results by xprofile data. Requires the xprofile
 *                                                      component. See {@see BP_XProfile_Query} for details.
 *     @type array                 $date_query          Filter results by member last activity date. See first parameter of
 *                                                      {@link WP_Date_Query::__construct()} for syntax. Only applicable if
 *                                                      $type is either 'active', 'random', 'newest', or 'online'.
 *     @type bool                  $populate_extras     Whether to fetch optional data, such as friend counts.
 *                                                      Default: true.
 * }
 * @return bool Returns true when blogs are found, otherwise false.
 */
function bp_has_members( $args = '' ) {
	global $members_template;

	// Default user ID.
	$user_id = 0;

	// User filtering.
	if ( bp_is_user_friends() && ! bp_is_user_friend_requests() ) {
		$user_id = bp_displayed_user_id();
	}

	$member_type = bp_get_current_member_type();
	if ( ! $member_type && ! empty( $_GET['member_type'] ) ) {
		if ( is_array( $_GET['member_type'] ) ) {
			$member_type = $_GET['member_type'];
		} else {
			// Can be a comma-separated list.
			$member_type = explode( ',', $_GET['member_type'] );
		}
	}

	$search_terms_default = null;
	$search_query_arg = bp_core_get_component_search_query_arg( 'members' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	// Type: active ( default ) | random | newest | popular | online | alphabetical.
	$r = bp_parse_args(
		$args,
		array(
			'type'                => 'active',
			'page'                => 1,
			'per_page'            => 20,
			'max'                 => false,

			'page_arg'            => 'upage',  // See https://buddypress.trac.wordpress.org/ticket/3679.

			'include'             => false,    // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users.
			'exclude'             => false,    // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users.
			'user_ids'            => false,

			'user_id'             => $user_id, // Pass a user_id to only show friends of this user.
			'member_type'         => $member_type,
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'search_terms'        => $search_terms_default,

			'meta_key'            => false,    // Only return users with this usermeta.
			'meta_value'          => false,    // Only return users where the usermeta value matches. Requires meta_key.

			'xprofile_query'      => false,
			'date_query'          => false,    // Filter members by last activity.
			'populate_extras'     => true,     // Fetch usermeta? Friend count, last active etc.
		),
		'has_members'
	);

	// Pass a filter if ?s= is set.
	if ( is_null( $r['search_terms'] ) ) {
		if ( !empty( $_REQUEST['s'] ) ) {
			$r['search_terms'] = $_REQUEST['s'];
		} else {
			$r['search_terms'] = false;
		}
	}

	// Set per_page to max if max is larger than per_page.
	if ( !empty( $r['max'] ) && ( $r['per_page'] > $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	// Query for members and populate $members_template global.
	$members_template = new BP_Core_Members_Template( $r );

	/**
	 * Filters whether or not BuddyPress has members to iterate over.
	 *
	 * @since 1.2.4
	 * @since 2.6.0 Added the `$r` parameter
	 *
	 * @param bool                     $value            Whether or not there are members to iterate over.
	 * @param BP_Core_Members_Template $members_template Populated $members_template global.
	 * @param array                    $r                Array of arguments passed into the BP_Core_Members_Template class.
	 */
	return apply_filters( 'bp_has_members', $members_template->has_members(), $members_template, $r );
}

/**
 * Set up the current member inside the loop.
 *
 * @since 1.2.0
 *
 * @global BP_Core_Members_Template $members_template The main member template loop class.
 *
 * @return object
 */
function bp_the_member() {
	global $members_template;
	return $members_template->the_member();
}

/**
 * Check whether there are more members to iterate over.
 *
 * @since 1.2.0
 *
 * @global BP_Core_Members_Template $members_template The main member template loop class.
 *
 * @return bool
 */
function bp_members() {
	global $members_template;
	return $members_template->members();
}

/**
 * Output the members pagination count.
 *
 * @since 1.2.0
 */
function bp_members_pagination_count() {
	echo esc_html( bp_get_members_pagination_count() );
}
	/**
	 * Generate the members pagination count.
	 *
	 * @since 1.5.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string
	 */
	function bp_get_members_pagination_count() {
		global $members_template;

		if ( empty( $members_template->type ) ) {
			$members_template->type = '';
		}

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 'active' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 active member', 'buddypress' );
			} else {
				/* translators: 1: active member from number. 2: active member to number. 3: total active members. */
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s active member', 'Viewing %1$s - %2$s of %3$s active members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
			}
		} elseif ( 'popular' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 member with friends', 'buddypress' );
			} else {
				/* translators: 1: member with friends from number. 2: member with friends to number. 3: total members with friends. */
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s member with friends', 'Viewing %1$s - %2$s of %3$s members with friends', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
			}
		} elseif ( 'online' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 online member', 'buddypress' );
			} else {
				/* translators: 1: online member from number. 2: online member to number. 3: total online members. */
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s online member', 'Viewing %1$s - %2$s of %3$s online members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
			}
		} else {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 member', 'buddypress' );
			} else {
				/* translators: 1: member from number. 2: member to number. 3: total members. */
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s member', 'Viewing %1$s - %2$s of %3$s members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
			}
		}

		/**
		 * Filters the members pagination count.
		 *
		 * @since 1.5.0
		 *
		 * @param string $pag Pagination count string.
		 */
		return apply_filters( 'bp_members_pagination_count', $pag );
	}

/**
 * Output the members pagination links.
 *
 * @since 1.2.0
 */
function bp_members_pagination_links() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_members_pagination_links();
}
	/**
	 * Fetch the members pagination links.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string
	 */
	function bp_get_members_pagination_links() {
		global $members_template;

		/**
		 * Filters the members pagination link.
		 *
		 * @since 1.2.0
		 *
		 * @param string $pag_links HTML markup for pagination links.
		 */
		return apply_filters( 'bp_get_members_pagination_links', $members_template->pag_links );
	}

/**
 * Output the ID of the current member in the loop.
 *
 * @since 1.2.0
 */
function bp_member_user_id() {
	echo intval( bp_get_member_user_id() );
}
	/**
	 * Get the ID of the current member in the loop.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return int Member ID.
	 */
	function bp_get_member_user_id() {
		global $members_template;

		$member_id = isset( $members_template->member->id )
			? (int) $members_template->member->id
			: 0;

		/**
		 * Filters the ID of the current member in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param int $member_id ID of the member being iterated over.
		 */
		return apply_filters( 'bp_get_member_user_id', (int) $member_id );
	}

/**
 * Output the row class of the current member in the loop.
 *
 * @since 1.7.0
 *
 * @param array $classes Array of custom classes.
 */
function bp_member_class( $classes = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_member_class( $classes );
}
	/**
	 * Return the row class of the current member in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array $classes Array of custom classes.
	 *
	 * @return string Row class of the member
	 */
	function bp_get_member_class( $classes = array() ) {
		global $members_template;

		// Add even/odd classes, but only if there's more than 1 member.
		if ( $members_template->member_count > 1 ) {
			$pos_in_loop = (int) $members_template->current_member;
			$classes[]   = ( $pos_in_loop % 2 ) ? 'even' : 'odd';

			// If we've only one member in the loop, don't bother with odd and even.
		} else {
			$classes[] = 'bp-single-member';
		}

		// Maybe add 'is-online' class.
		if ( ! empty( $members_template->member->last_activity ) ) {

			// Calculate some times.
			$current_time  = bp_core_current_time( true, 'timestamp' );
			$last_activity = strtotime( $members_template->member->last_activity );
			$still_online  = strtotime( '+5 minutes', $last_activity );

			// Has the user been active recently?
			if ( $current_time <= $still_online ) {
				$classes[] = 'is-online';
			}
		}

		// Add current user class.
		if ( bp_loggedin_user_id() === (int) $members_template->member->id ) {
			$classes[] = 'is-current-user';
		}

		// Add current user member types.
		if ( $member_types = bp_get_member_type( $members_template->member->id, false ) ) {
			foreach ( $member_types as $member_type ) {
				$classes[] = sprintf( 'member-type-%s', $member_type );
			}
		}

		/**
		 * Filters the determined classes to add to the HTML element.
		 *
		 * @since 1.7.0
		 *
		 * @param array $classes Classes to be added to the HTML element.
		 */
		$classes = array_map( 'sanitize_html_class', apply_filters( 'bp_get_member_class', $classes ) );
		$classes = array_merge( $classes, array() );
		$retval  = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output nicename of current member in the loop.
 *
 * @since 1.2.5
 */
function bp_member_user_nicename() {
	echo esc_html( bp_get_member_user_nicename() );
}
	/**
	 * Get the nicename of the current member in the loop.
	 *
	 * @since 1.2.5
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string Members nicename.
	 */
	function bp_get_member_user_nicename() {
		global $members_template;

		/**
		 * Filters the nicename of the current member in the loop.
		 *
		 * @since 1.2.5
		 *
		 * @param string $user_nicename Nicename for the current member.
		 */
		return apply_filters( 'bp_get_member_user_nicename', $members_template->member->user_nicename );
	}

/**
 * Output login for current member in the loop.
 *
 * @since 1.2.5
 */
function bp_member_user_login() {
	echo esc_html( bp_get_member_user_login() );
}
	/**
	 * Get the login of the current member in the loop.
	 *
	 * @since 1.2.5
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string Member's login.
	 */
	function bp_get_member_user_login() {
		global $members_template;

		/**
		 * Filters the login of the current member in the loop.
		 *
		 * @since 1.2.5
		 *
		 * @param string $user_login Login for the current member.
		 */
		return apply_filters( 'bp_get_member_user_login', $members_template->member->user_login );
	}

/**
 * Output the email address for the current member in the loop.
 *
 * @since 1.2.5
 */
function bp_member_user_email() {
	echo esc_html( bp_get_member_user_email() );
}
	/**
	 * Get the email address of the current member in the loop.
	 *
	 * @since 1.2.5
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string Member's email address.
	 */
	function bp_get_member_user_email() {
		global $members_template;

		/**
		 * Filters the email address of the current member in the loop.
		 *
		 * @since 1.2.5
		 *
		 * @param string $user_email Email address for the current member.
		 */
		return apply_filters( 'bp_get_member_user_email', $members_template->member->user_email );
	}

/**
 * Check whether the current member in the loop is the logged-in user.
 *
 * @since 1.2.5
 * @since 10.0.0 Updated to get member ID from `bp_get_member_user_id`.
 *
 * @return bool
 */
function bp_member_is_loggedin_user() {

	/**
	 * Filters whether the current member in the loop is the logged-in user.
	 *
	 * @since 1.2.5
	 *
	 * @param bool $value Whether current member in the loop is logged in.
	 */
	return apply_filters( 'bp_member_is_loggedin_user', ( bp_loggedin_user_id() === bp_get_member_user_id() ) );
}

/**
 * Output a member's avatar.
 *
 * @since 1.2.0
 *
 * @see bp_get_member_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_member_avatar()}.
 */
function bp_member_avatar( $args = '' ) {
	// phpcs:disable WordPress.Security.EscapeOutput

	/**
	 * Filters a members avatar.
	 *
	 * @since 1.2.0
	 * @since 2.6.0 Added the `$args` parameter.
	 *
	 * @param string       $value Formatted HTML <img> element, or raw avatar URL based on $html arg.
	 * @param array|string $args  See {@link bp_get_member_avatar()}.
	 */
	echo apply_filters( 'bp_member_avatar', bp_get_member_avatar( $args ), $args );
	// phpcs:enable
}
	/**
	 * Get a member's avatar.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt     Default: 'Profile picture of [user name]'.
	 *     @type string   $class   Default: 'avatar'.
	 *     @type string   $type    Default: 'thumb'.
	 *     @type int|bool $width   Default: false.
	 *     @type int|bool $height  Default: false.
	 *     @type bool     $no_grav Default: false.
	 *     @type bool     $id      Currently unused.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_member_avatar( $args = '' ) {
		global $members_template;

		$fullname = ! empty( $members_template->member->fullname )
			? $members_template->member->fullname
			: $members_template->member->display_name;

		$r = bp_parse_args(
			$args,
			array(
				'type'    => 'thumb',
				'width'   => false,
				'height'  => false,
				'class'   => 'avatar',
				'id'      => false,
				'no_grav' => false,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $fullname ),
			)
		);

		$avatar = bp_core_fetch_avatar(
			array(
				'email'   => bp_get_member_user_email(),
				'item_id' => bp_get_member_user_id(),
				'type'    => $r['type'],
				'alt'     => $r['alt'],
				'no_grav' => $r['no_grav'],
				'css_id'  => $r['id'],
				'class'   => $r['class'],
				'width'   => $r['width'],
				'height'  => $r['height'],
			)
		);

		/**
		 * Filters a member's avatar.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param string $value Formatted HTML <img> element, or raw avatar URL based on $html arg.
		 * @param array  $r     Array of parsed arguments. See {@link bp_get_member_avatar()}.
		 */
		return apply_filters( 'bp_get_member_avatar', $avatar, $r );
	}

/**
 * Output the permalink for the current member in the loop.
 *
 * @since 1.2.0
 */
function bp_member_permalink() {
	echo esc_url( bp_get_member_permalink() );
}
	/**
	 * Get the permalink for the current member in the loop.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string
	 */
	function bp_get_member_permalink() {
		global $members_template;

		$permalink = bp_members_get_user_url( $members_template->member->id );

		/**
		 * Filters the permalink for the current member in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $permalink Permalink for the current member in the loop.
		 */
		return apply_filters( 'bp_get_member_permalink', $permalink );
	}

	/**
	 * Alias of {@link bp_member_permalink()}.
	 *
	 * @since 1.2.0
	 */
	function bp_member_link() {
		echo esc_url( bp_get_member_permalink() );
	}

	/**
	 * Alias of {@link bp_get_member_permalink()}.
	 *
	 * @since 1.2.0
	 */
	function bp_get_member_link() {
		return bp_get_member_permalink();
	}

/**
 * Output display name of current member in the loop.
 *
 * @since 1.2.0
 */
function bp_member_name() {
	// phpcs:disable WordPress.Security.EscapeOutput

	/**
	 * Filters the display name of current member in the loop.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Display name for current member.
	 */
	echo apply_filters( 'bp_member_name', bp_get_member_name() );
	// phpcs:enable
}
	/**
	 * Get the display name of the current member in the loop.
	 *
	 * Full name is, by default, pulled from xprofile's Full Name field.
	 * When this field is empty, we try to get an alternative name from the
	 * WP users table, in the following order of preference: display_name,
	 * user_nicename, user_login.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @return string The user's fullname for display.
	 */
	function bp_get_member_name() {
		global $members_template;

		// Generally, this only fires when xprofile is disabled.
		if ( empty( $members_template->member->fullname ) ) {
			// Our order of preference for alternative fullnames.
			$name_stack = array(
				'display_name',
				'user_nicename',
				'user_login'
			);

			foreach ( $name_stack as $source ) {
				if ( ! empty( $members_template->member->{$source} ) ) {
					// When a value is found, set it as fullname and be done with it.
					$members_template->member->fullname = $members_template->member->{$source};
					break;
				}
			}
		}

		/**
		 * Filters the display name of current member in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $fullname Display name for current member.
		 */
		return apply_filters( 'bp_get_member_name', $members_template->member->fullname );
	}
	add_filter( 'bp_get_member_name', 'wp_filter_kses' );
	add_filter( 'bp_get_member_name', 'stripslashes'   );
	add_filter( 'bp_get_member_name', 'strip_tags'     );
	add_filter( 'bp_get_member_name', 'esc_html'       );

/**
 * Output the current member's last active time.
 *
 * @since 1.2.0
 *
 * @param array $args {@see bp_get_member_last_active()}.
 */
function bp_member_last_active( $args = array() ) {
	echo esc_html( bp_get_member_last_active( $args ) );
}
	/**
	 * Return the current member's last active time.
	 *
	 * @since 1.2.0
	 * @since 2.7.0 Added 'relative' as a parameter to $args.
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type mixed $active_format If true, formatted "active 5 minutes ago". If false, formatted "5 minutes
	 *                                ago". If string, should be sprintf'able like 'last seen %s ago'.
	 *     @type bool  $relative      If true, will return relative time "5 minutes ago". If false, will return
	 *                                date from database. Default: true.
	 * }
	 * @return string
	 */
	function bp_get_member_last_active( $args = array() ) {
		global $members_template;

		// Parse the activity format.
		$r = bp_parse_args(
			$args,
			array(
				'active_format' => true,
				'relative'      => true,
			)
		);

		// Backwards compatibility for anyone forcing a 'true' active_format.
		if ( true === $r['active_format'] ) {
			/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
			$r['active_format'] = __( 'Active %s', 'buddypress' );
		}

		// Member has logged in at least one time.
		if ( isset( $members_template->member->last_activity ) ) {
			// We do not want relative time, so return now.
			// @todo Should the 'bp_member_last_active' filter be applied here?
			if ( ! $r['relative'] ) {
				return esc_attr( $members_template->member->last_activity );
			}

			// Backwards compatibility for pre 1.5 'ago' strings.
			$last_activity = ! empty( $r['active_format'] )
				? bp_core_get_last_activity( $members_template->member->last_activity, $r['active_format'] )
				: bp_core_time_since( $members_template->member->last_activity );

		// Member has never logged in or been active.
		} else {
			$last_activity = __( 'Never active', 'buddypress' );
		}

		/**
		 * Filters the current members last active time.
		 *
		 * @since 1.2.0
		 *
		 * @param string $last_activity Formatted time since last activity.
		 * @param array  $r             Array of parsed arguments for query.
		 */
		return apply_filters( 'bp_member_last_active', $last_activity, $r );
	}

/**
 * Output the latest update of the current member in the loop.
 *
 * @since 1.2.0
 *
 * @param array|string $args {@see bp_get_member_latest_update()}.
 */
function bp_member_latest_update( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_member_latest_update( $args );
}
	/**
	 * Get the latest update from the current member in the loop.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array|string $args {
	 *     Array of optional arguments.
	 *     @type int  $length    Truncation length. Default: 225.
	 *     @type bool $view_link Whether to provide a 'View' link for
	 *                           truncated entries. Default: false.
	 * }
	 * @return string
	 */
	function bp_get_member_latest_update( $args = '' ) {
		global $members_template;

		if ( ! bp_is_active( 'activity' ) ) {
			return '';
		}

		$r = bp_parse_args(
			$args,
			array(
				'length'    => 225,
				'view_link' => true,
			),
			'member_latest_update'
		);

		$length    = (int) $r['length'];
		$view_link = (bool) $r['view_link'];

		// Init default update.
		$update_content = '';
		$update         = array(
			'id'        => 0,
			'content'   => '',
			'excerpt'   => '',
			'permalink' => '',
		);

		if ( ! empty( $members_template->member->latest_update ) ) {
			$update = maybe_unserialize( $members_template->member->latest_update );
		}

		if ( ! empty( $update['content'] ) ) {
			$excerpt           = trim( strip_tags( bp_create_excerpt( $update['content'], $length ) ) );
			$update['content'] = trim( strip_tags( $update['content'] ) );
			$update['excerpt'] = $excerpt;
		} else {
			$excerpt = '';
		}

		if ( isset( $update['id'] ) ) {
			$activity_id = (int) $update['id'];
			$update['permalink'] = bp_activity_get_permalink( $activity_id );
		}

		/**
		 * Filters the excerpt of the latest update for current member in the loop.
		 *
		 * @since 1.2.5
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param string $excerpt Excerpt of the latest update for current member in the loop.
		 * @param array  $r       Array of parsed arguments.
		 */
		$excerpt = apply_filters( 'bp_get_activity_latest_update_excerpt', $excerpt, $r );

		// If we have an excerpt, set its output and eventually add a link to view the full activity.
		if ( $excerpt ) {
			/* translators: %s: the member latest activity update */
			$update_content = sprintf( _x( '- &quot;%s&quot;', 'member latest update in member directory', 'buddypress' ), $excerpt );

			/*
			* If `$view_link` is true and the text returned by `bp_create_excerpt()` is different
			* from the original text (ie it's been truncated), add the "View" link.
			*/
			if ( $view_link && $update['permalink'] && ( strlen( $excerpt ) < strlen( $update['content'] ) ) ) {
				$update_content      = sprintf(
					'%1$s<span class="activity-read-more"><a href="%2$s" rel="nofollow">%3$s</a></span>',
					$update_content . "\n",
					esc_url( $update['permalink'] ),
					esc_html__( 'View', 'buddypress' )
				);
			}
		}

		/**
		 * Filters the latest update from the current member in the loop.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$r` parameter.
		 * @since 12.0.0 Added the `$update` parameter.
		 *
		 * @param string $update_content Formatted latest update for current member.
		 * @param array  $r              Array of parsed arguments.
		 * @param array  $update         Array of the latest activity data.
		 */
		return apply_filters( 'bp_get_member_latest_update', $update_content, $r, $update );
	}

/**
 * Output a piece of user profile data.
 *
 * @since 1.2.0
 *
 * @see bp_get_member_profile_data() for a description of params.
 *
 * @param array|string $args See {@link bp_get_member_profile_data()}.
 */
function bp_member_profile_data( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_member_profile_data( $args );
}
	/**
	 * Get a piece of user profile data.
	 *
	 * When used in a bp_has_members() loop, this function will attempt
	 * to fetch profile data cached in the template global. It is also safe
	 * to use outside of the loop.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array|string $args {
	 *     Array of config parameters.
	 *     @type string $field   Name of the profile field.
	 *     @type int    $user_id ID of the user whose data is being fetched.
	 *                           Defaults to the current member in the loop, or if not
	 *                           present, to the currently displayed user.
	 * }
	 * @return string|bool Profile data if found, otherwise false.
	 */
	function bp_get_member_profile_data( $args = '' ) {
		global $members_template;

		if ( ! bp_is_active( 'xprofile' ) ) {
			return false;
		}

		// Declare local variables.
		$data = false;

		// Guess at default $user_id.
		$default_user_id = 0;
		if ( ! empty( $members_template->member->id ) ) {
			$default_user_id = $members_template->member->id;
		} elseif ( bp_displayed_user_id() ) {
			$default_user_id = bp_displayed_user_id();
		}

		$defaults = array(
			'field'   => false,
			'user_id' => $default_user_id,
		);

		$r = bp_parse_args(
			$args,
			$defaults
		);

		// If we're in a members loop, get the data from the global.
		if ( ! empty( $members_template->member->profile_data ) ) {
			$profile_data = $members_template->member->profile_data;
		}

		// Otherwise query for the data.
		if ( empty( $profile_data ) && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' ) ) {
			$profile_data = BP_XProfile_ProfileData::get_all_for_user( $r['user_id'] );
		}

		// If we're in the members loop, but the profile data has not
		// been loaded into the global, cache it there for later use.
		if ( ! empty( $members_template->member ) && empty( $members_template->member->profile_data ) ) {
			$members_template->member->profile_data = $profile_data;
		}

		// Get the data for the specific field requested.
		if ( ! empty( $profile_data ) && ! empty( $profile_data[ $r['field'] ]['field_type'] ) && ! empty( $profile_data[ $r['field'] ]['field_data'] ) ) {
			$data = xprofile_format_profile_field( $profile_data[ $r['field'] ]['field_type'], $profile_data[ $r['field'] ]['field_data'] );
		}

		/**
		 * Filters resulting piece of member profile data.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param string|bool $data Profile data if found, otherwise false.
		 * @param array       $r    Array of parsed arguments.
		 */
		$data = apply_filters( 'bp_get_member_profile_data', $data, $r );

		/**
		 * Filters the resulting piece of member profile data by field type.
		 *
		 * This is a dynamic filter based on field type of the current field requested.
		 *
		 * @since 2.7.0
		 *
		 * @param string|bool $data Profile data if found, otherwise false.
		 * @param array       $r    Array of parsed arguments.
		 */
		if ( ! empty( $profile_data[ $r['field'] ]['field_type'] ) ) {
			$data = apply_filters( 'bp_get_member_profile_data_' . $profile_data[ $r['field'] ]['field_type'], $data, $r );
		}

		return $data;
	}

/**
 * Output the 'registered [x days ago]' string for the current member.
 *
 * @since 1.2.0
 * @since 2.7.0 Added $args as a parameter.
 *
 * @param array $args Optional. {@see bp_get_member_registered()}.
 */
function bp_member_registered( $args = array() ) {
	echo esc_html( bp_get_member_registered( $args ) );
}
	/**
	 * Get the 'registered [x days ago]' string for the current member.
	 *
	 * @since 1.2.0
	 * @since 2.7.0 Added `$args` as a parameter.
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array $args {
	 *     Array of optional parameters.
	 *     @type bool $relative Optional. If true, returns relative registered date. eg. registered 5 months ago.
	 *                          If false, returns registered date value from database.
	 * }
	 * @return string
	 */
	function bp_get_member_registered( $args = array() ) {
		global $members_template;

		$r = bp_parse_args(
			$args,
			array(
				'relative' => true,
			)
		);

		// We do not want relative time, so return now.
		// @todo Should the 'bp_member_registered' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $members_template->member->user_registered );
		}

		/* translators: %s: last activity timestamp (e.g. "active 1 hour ago") */
		$registered = esc_attr( bp_core_get_last_activity( $members_template->member->user_registered, _x( 'registered %s', 'Records the timestamp that the user registered into the activity stream', 'buddypress' ) ) );

		/**
		 * Filters the 'registered [x days ago]' string for the current member.
		 *
		 * @since 2.1.0
		 *
		 * @param string $registered The 'registered [x days ago]' string.
		 * @param array  $r          Array of parsed arguments.
		 */
		return apply_filters( 'bp_member_registered', $registered, $r );
	}

/**
 * Output a random piece of profile data for the current member in the loop.
 *
 * @since 1.2.0
 * @since 10.0.0 Updated to get member ID using `bp_get_member_user_id`.
 */
function bp_member_random_profile_data() {
	if ( bp_is_active( 'xprofile' ) ) {
		$random_data = xprofile_get_random_profile_data( bp_get_member_user_id(), true );
		// phpcs:disable WordPress.Security.EscapeOutput
		?>
			<strong><?php echo wp_filter_kses( $random_data[0]->name ) ?></strong>
			<?php echo wp_filter_kses( $random_data[0]->value ) ?>
		<?php
		// phpcs:enable
	}
}

/**
 * Output hidden input for preserving member search params on form submit.
 *
 * @since 1.2.0
 */
function bp_member_hidden_fields() {
	$query_arg = bp_core_get_component_search_query_arg( 'members' );

	if ( isset( $_REQUEST[ $query_arg ] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST[ $query_arg ] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['members_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['members_search'] ) . '" name="search_terms" />';
	}
}

/**
 * Output the Members directory search form.
 *
 * @since 1.0.0
 */
function bp_directory_members_search_form() {

	$query_arg = bp_core_get_component_search_query_arg( 'members' );

	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'members' );
	}

	$search_form_html = '<form action="" method="get" id="search-members-form">
		<label for="members_search"><input type="text" name="' . esc_attr( $query_arg ) . '" id="members_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="' . esc_html__( 'Search', 'buddypress' ) . '" />
	</form>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters the Members component search form.
		 *
		 * @since 1.9.0
		 *
		 * @param string $search_form_html HTML markup for the member search form.
		 */
		'bp_directory_members_search_form',
		$search_form_html
	);
}

/**
 * Output the total member count.
 *
 * @since 1.2.0
 */
function bp_total_site_member_count() {
	echo esc_html( bp_get_total_site_member_count() );
}
	/**
	 * Get the total site member count.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function bp_get_total_site_member_count() {

		/**
		 * Filters the total site member count.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Number-formatted total site member count.
		 */
		return apply_filters( 'bp_get_total_site_member_count', bp_core_number_format( bp_core_get_total_member_count() ) );
	}

/** Navigation and other misc template tags ***********************************/

/**
 * Render the navigation markup for the logged-in user.
 *
 * Each component adds to this navigation array within its own
 * [component_name]setup_nav() function.
 *
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Friends] ...
 *
 * The function will also analyze the current component the user is in, to
 * determine whether or not to highlight a particular nav item.
 *
 * @since 1.1.0
 *
 * @todo Move to a back-compat file?
 * @deprecated Does not seem to be called anywhere in BP core.
 */
function bp_get_loggedin_user_nav() {
	$bp = buddypress();

	// Loop through each navigation item.
	foreach ( (array) $bp->members->nav->get_primary() as $nav_item ) {

		$selected = '';

		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( ! bp_is_directory() && ! empty( $bp->active_components[ bp_current_component() ] ) && $bp->active_components[ bp_current_component() ] == $nav_item->css_id ) {
			$selected = ' class="current selected"';
		}

		// If we are viewing another person (current_userid does not equal
		// loggedin_user->id then check to see if the two users are friends.
		// if they are, add a highlight CSS class to the friends nav item
		// if it exists.
		if ( !bp_is_my_profile() && bp_displayed_user_id() ) {
			$selected = '';

			if ( bp_is_active( 'friends' ) ) {
				if ( $nav_item->css_id == $bp->friends->id ) {
					if ( friends_check_friendship( bp_loggedin_user_id(), bp_displayed_user_id() ) ) {
						$selected = ' class="current selected"';
					}
				}
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item->css_id, array( '<li id="li-nav-' . esc_attr( $nav_item->css_id ) . '" ' . $selected . '><a id="my-' . esc_attr( $nav_item->css_id ) . '" href="' . esc_url( $nav_item->link ) . '">' . esc_html( $nav_item->name ) . '</a></li>', &$nav_item ) );
	}

	// Always add a log out list item to the end of the navigation.
	$logout_link = '<li><a id="wp-logout" href="' .  esc_url( wp_logout_url( bp_get_root_url() ) ) . '">' . esc_html__( 'Log Out', 'buddypress' ) . '</a></li>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters( 'bp_logout_nav_link', $logout_link );
}

/**
 * Output the contents of the current user's home page.
 *
 * @since 2.6.0
 */
function bp_displayed_user_front_template_part() {
	$located = bp_displayed_user_get_front_template();

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Locate a custom user front template if it exists.
 *
 * @since 2.6.0
 *
 * @param  object|null $displayed_user Optional. Falls back to current user if not passed.
 * @return string|bool                 Path to front template on success; boolean false on failure.
 */
function bp_displayed_user_get_front_template( $displayed_user = null ) {
	if ( ! is_object( $displayed_user ) || empty( $displayed_user->id ) ) {
		$displayed_user = bp_get_displayed_user();
	}

	if ( ! isset( $displayed_user->id ) ) {
		return false;
	}

	if ( isset( $displayed_user->front_template ) ) {
		return $displayed_user->front_template;
	}

	// Init the hierarchy.
	$template_names = array(
		'members/single/front-id-' . (int) $displayed_user->id . '.php',
		'members/single/front-nicename-' . sanitize_file_name( $displayed_user->userdata->user_nicename ) . '.php',
	);

	/**
	 * Check for member types and add it to the hierarchy
	 *
	 * Make sure to register your member
	 * type using the hook 'bp_register_member_types'
	 */
	if ( bp_get_member_types() ) {
		$displayed_user_member_type = bp_get_member_type( $displayed_user->id );
		if ( ! $displayed_user_member_type ) {
			$displayed_user_member_type = 'none';
		}

		$template_names[] = 'members/single/front-member-type-' . sanitize_file_name( $displayed_user_member_type ) . '.php';
	}

	// Add The generic template to the end of the hierarchy.
	$template_names[] = 'members/single/front.php';

	/**
	 * Filters the hierarchy of user front templates corresponding to a specific user.
	 *
	 * @since 2.6.0
	 *
	 * @param array $template_names Array of template paths.
	 */
	$template_names = apply_filters( 'bp_displayed_user_get_front_template', $template_names );

	return bp_locate_template( $template_names, false, true );
}

/**
 * Check if the displayed user has a custom front template.
 *
 * @since 2.6.0
 */
function bp_displayed_user_has_front_template() {
	$displayed_user = bp_get_displayed_user();

	return ! empty( $displayed_user->front_template );
}

/**
 * Render the navigation markup for the displayed user.
 *
 * @since 1.1.0
 */
function bp_get_displayed_user_nav() {
	$bp = buddypress();

	foreach ( $bp->members->nav->get_primary() as $user_nav_item ) {
		if ( empty( $user_nav_item->show_for_displayed_user ) && ! bp_is_my_profile() ) {
			continue;
		}

		$selected = '';
		if ( bp_is_current_component( $user_nav_item->slug ) ) {
			$selected = ' class="current selected"';
		}

		if ( bp_loggedin_user_url() ) {
			$link = str_replace( bp_loggedin_user_url(), bp_displayed_user_url(), $user_nav_item->link );
		} else {
			$link = $user_nav_item->link;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo apply_filters_ref_array(
			/**
			 * Filters the navigation markup for the displayed user.
			 *
			 * This is a dynamic filter that is dependent on the navigation tab component being rendered.
			 *
			 * @since 1.1.0
			 *
			 * @param string $value         Markup for the tab list item including link.
			 * @param array  $user_nav_item Array holding parts used to construct tab list item.
			 *                              Passed by reference.
			 */
			'bp_get_displayed_user_nav_' . $user_nav_item->css_id,
			array(
				'<li id="' . esc_attr( $user_nav_item->css_id ) . '-personal-li" ' . $selected . '><a id="user-' . esc_attr( $user_nav_item->css_id ) . '" href="' . esc_url( $link ) . '">' . wp_kses( $user_nav_item->name, array( 'span' => array( 'class' => true ) ) ) . '</a></li>',
				&$user_nav_item
			)
		);
	}
}

/** Cover image ***************************************************************/

/**
 * Should we use the cover image header
 *
 * @since 2.4.0
 *
 * @return bool True if the displayed user has a cover image,
 *              False otherwise
 */
function bp_displayed_user_use_cover_image_header() {
	return (bool) bp_is_active( 'members', 'cover_image' ) && ! bp_disable_cover_image_uploads();
}

/** Avatars *******************************************************************/

/**
 * Output the logged-in user's avatar.
 *
 * @since 1.1.0
 *
 * @see bp_get_loggedin_user_avatar() for a description of params.
 *
 * @param array|string $args {@see bp_get_loggedin_user_avatar()}.
 */
function bp_loggedin_user_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_loggedin_user_avatar( $args );
}
	/**
	 * Get the logged-in user's avatar.
	 *
	 * @since 1.1.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt    Default: 'Profile picture of [user name]'.
	 *     @type bool     $html   Default: true.
	 *     @type string   $type   Default: 'thumb'.
	 *     @type int|bool $width  Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_loggedin_user_avatar( $args = '' ) {

		$r = bp_parse_args(
			$args,
			array(
				'item_id' => bp_loggedin_user_id(),
				'type'    => 'thumb',
				'width'   => false,
				'height'  => false,
				'html'    => true,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_loggedin_user_fullname() )
			)
		);

		/**
		 * Filters the logged in user's avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value User avatar string.
		 * @param array  $r     Array of parsed arguments.
		 * @param array  $args  Array of initial arguments.
		 */
		return apply_filters( 'bp_get_loggedin_user_avatar', bp_core_fetch_avatar( $r ), $r, $args );
	}

/**
 * Output the displayed user's avatar.
 *
 * @since 1.1.0
 *
 * @see bp_get_displayed_user_avatar() for a description of params.
 *
 * @param array|string $args {@see bp_get_displayed_user_avatar()}.
 */
function bp_displayed_user_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_displayed_user_avatar( $args );
}
	/**
	 * Get the displayed user's avatar.
	 *
	 * @since 1.1.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt    Default: 'Profile picture of [user name]'.
	 *     @type bool     $html   Default: true.
	 *     @type string   $type   Default: 'thumb'.
	 *     @type int|bool $width  Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_displayed_user_avatar( $args = '' ) {

		$r = bp_parse_args(
			$args,
			array(
				'item_id' => bp_displayed_user_id(),
				'type'    => 'thumb',
				'width'   => false,
				'height'  => false,
				'html'    => true,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
			)
		);

		/**
		 * Filters the displayed user's avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value User avatar string.
		 * @param array  $r     Array of parsed arguments.
		 * @param array  $args  Array of initial arguments.
		 */
		return apply_filters( 'bp_get_displayed_user_avatar', bp_core_fetch_avatar( $r ), $r, $args );
	}

/**
 * Output the email address of the displayed user.
 *
 * @since 1.5.0
 */
function bp_displayed_user_email() {
	echo esc_html( bp_get_displayed_user_email() );
}
	/**
	 * Get the email address of the displayed user.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_displayed_user_email() {
		$bp = buddypress();

		// If displayed user exists, return email address.
		if ( isset( $bp->displayed_user->userdata->user_email ) ) {
			$retval = $bp->displayed_user->userdata->user_email;
		} else {
			$retval = '';
		}

		/**
		 * Filters the email address of the displayed user.
		 *
		 * @since 1.5.0
		 *
		 * @param string $retval Email address for displayed user.
		 */
		return apply_filters( 'bp_get_displayed_user_email', esc_attr( $retval ) );
	}

/**
 * Output the "active [x days ago]" string for a user.
 *
 * @since 1.0.0
 *
 * @see bp_get_last_activity() for a description of parameters.
 *
 * @param int $user_id See {@link bp_get_last_activity()}.
 */
function bp_last_activity( $user_id = 0 ) {
	echo esc_html( bp_get_last_activity( $user_id ) );
}
	/**
	 * Get the "active [x days ago]" string for a user.
	 *
	 * @since 1.5.0
	 *
	 * @param int $user_id ID of the user. Default: displayed user ID.
	 * @return string
	 */
	function bp_get_last_activity( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
		$last_activity = bp_core_get_last_activity( bp_get_user_last_activity( $user_id ), __( 'Active %s', 'buddypress') );

		/**
		 * Filters the 'active [x days ago]' string for a user.
		 *
		 * @since 1.5.0
		 * @since 2.6.0 Added the `$user_id` parameter.
		 *
		 * @param string $value   Formatted 'active [x days ago]' string.
		 * @param int    $user_id ID of the user.
		 */
		return apply_filters( 'bp_get_last_activity', $last_activity, $user_id );
	}

/**
 * Output the calculated first name of the displayed or logged-in user.
 *
 * @since 1.2.0
 */
function bp_user_firstname() {
	echo esc_html( bp_get_user_firstname() );
}
	/**
	 * Output the first name of a user.
	 *
	 * Simply takes all the characters before the first space in a name.
	 *
	 * @since 1.2.0
	 *
	 * @param string|bool $name Full name to use when generating first name.
	 *                          Defaults to displayed user's first name, or to
	 *                          logged-in user's first name if it's unavailable.
	 * @return string
	 */
	function bp_get_user_firstname( $name = false ) {

		// Try to get displayed user.
		if ( empty( $name ) ) {
			$name = bp_get_displayed_user_fullname();
		}

		// Fall back on logged in user.
		if ( empty( $name ) ) {
			$name = bp_get_loggedin_user_fullname();
		}

		$fullname = (array) explode( ' ', $name );

		/**
		 * Filters the first name of a user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value    First name of user.
		 * @param string $fullname Full name of user.
		 */
		return apply_filters( 'bp_get_user_firstname', $fullname[0], $fullname );
	}

/**
 * Alias of {@link bp_displayed_user_id()}.
 *
 * @since 1.0.0
 */
function bp_current_user_id() {
	return bp_displayed_user_id();
}

/**
 * Output the link for the displayed user's profile.
 *
 * @since 1.2.4
 * @since 12.0.0 Introduced the `$chunk` argument.
 *
 * @param array $chunk A list of slugs to append to the URL.
 */
function bp_displayed_user_link( $chunks = array() ) {
	$path_chunks = array();
	$chunks      = (array) $chunks;

	if ( $chunks ) {
		$path_chunks = bp_members_get_path_chunks( $chunks );
	}

	echo esc_url( bp_displayed_user_url( $path_chunks ) );
}

/**
 * Builds the logged-in user's profile URL.
 *
 * @since 12.0.0
 *
 * @param array $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type string $single_item_component        The component slug the action is relative to.
 *     @type string $single_item_action           The slug of the action to perform.
 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
 * }
 * @return string The logged-in user's profile URL.
 */
function bp_displayed_user_url( $path_chunks = array() ) {
	$bp  = buddypress();
	$url = '';

	if ( isset( $bp->displayed_user->domain ) ) {
		$url = $bp->displayed_user->domain;
	}

	if ( $path_chunks ) {
		$url = bp_members_get_user_url( bp_displayed_user_id(), $path_chunks );
	}

	/**
	 * Filter here to edit the displayed user's profile URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string $url         The displayed user's profile URL.
	 * @param array  $path_chunks {
	 *     An array of arguments. Optional.
	 *
	 *     @type string $single_item_component        The component slug the action is relative to.
	 *     @type string $single_item_action           The slug of the action to perform.
	 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
	 * }
	 */
	return apply_filters( 'bp_displayed_user_url', $url, $path_chunks );
}

/**
 * Generate the link for the displayed user's profile.
 *
 * @since 1.0.0
 * @since 12.0.0 This function is now an alias of `bp_displayed_user_url()`.
 *               You should only use it to get the "home" URL of the displayed
 *               user's profile page. If you need to build an URL to reach another
 *               page, we strongly advise you to use `bp_displayed_user_url()`.
 *
 * @todo Deprecating this function would be safer.
 * @return string
 */
function bp_displayed_user_domain() {
	$url = bp_displayed_user_url();

	/**
	 * Filters the generated link for the displayed user's profile.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Generated link for the displayed user's profile.
	 */
	return apply_filters( 'bp_displayed_user_domain', $url );
}

/**
 * Output the link for the logged-in user's profile.
 *
 * @since 1.2.4
 * @since 12.0.0 Introduced the `$chunk` argument.
 *
 * @param array $chunk A list of slugs to append to the URL.
 */
function bp_loggedin_user_link( $chunks = array() ) {
	$path_chunks = array();
	$chunks      = (array) $chunks;

	if ( $chunks ) {
		$path_chunks = bp_members_get_path_chunks( $chunks );
	}

	echo esc_url( bp_loggedin_user_url( $path_chunks ) );
}

/**
 * Builds the logged-in user's profile URL.
 *
 * @since 12.0.0
 *
 * @param array $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type string $single_item_component        The component slug the action is relative to.
 *     @type string $single_item_action           The slug of the action to perform.
 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
 * }
 * @return string The logged-in user's profile URL.
 */
function bp_loggedin_user_url( $path_chunks = array() ) {
	$bp  = buddypress();
	$url = '';

	if ( isset( $bp->loggedin_user->domain ) ) {
		$url = $bp->loggedin_user->domain;
	}

	if ( $path_chunks ) {
		$url = bp_members_get_user_url( bp_loggedin_user_id(), $path_chunks );
	}

	/**
	 * Filter here to edit the logged-in user's profile URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string $url         The logged-in user's profile URL.
	 * @param array  $path_chunks {
	 *     An array of arguments. Optional.
	 *
	 *     @type string $single_item_component        The component slug the action is relative to.
	 *     @type string $single_item_action           The slug of the action to perform.
	 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
	 * }
	 */
	return apply_filters( 'bp_loggedin_user_url', $url, $path_chunks );
}

/**
 * Generate the link for the logged-in user's profile.
 *
 * @since 1.0.0
 * @since 12.0.0 This function is now an alias of `bp_loggedin_user_url()`.
 *               You should only use it to get the "home" URL of the logged-in
 *               user's profile page. If you need to build an URL to reach another
 *               page, we strongly advise you to use `bp_loggedin_user_url()`.
 *
 * @todo Deprecating this function would be safer.
 * @return string
 */
function bp_loggedin_user_domain() {
	$url = bp_loggedin_user_url();

	/**
	 * Filters the generated link for the logged-in user's profile.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Generated link for the logged-in user's profile.
	 */
	return apply_filters( 'bp_loggedin_user_domain', $url );
}

/**
 * Output the displayed user's display name.
 *
 * @since 1.0.0
 */
function bp_displayed_user_fullname() {
	echo esc_html( bp_get_displayed_user_fullname() );
}
	/**
	 * Get the displayed user's display name.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function bp_get_displayed_user_fullname() {
		$bp = buddypress();

		/**
		 * Filters the displayed user's display name.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Displayed user's display name.
		 */
		return apply_filters( 'bp_displayed_user_fullname', isset( $bp->displayed_user->fullname ) ? $bp->displayed_user->fullname : '' );
	}

	/**
	 * Alias of {@link bp_get_displayed_user_fullname()}.
	 *
	 * @since 1.0.0
	 */
	function bp_user_fullname() { echo esc_html( bp_get_displayed_user_fullname() ); }


/**
 * Output the logged-in user's display name.
 *
 * @since 1.0.0
 */
function bp_loggedin_user_fullname() {
	echo esc_html( bp_get_loggedin_user_fullname() );
}
	/**
	 * Get the logged-in user's display name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_loggedin_user_fullname() {
		$bp = buddypress();

		/**
		 * Filters the logged-in user's display name.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Logged-in user's display name.
		 */
		return apply_filters( 'bp_get_loggedin_user_fullname', isset( $bp->loggedin_user->fullname ) ? $bp->loggedin_user->fullname : '' );
	}

/**
 * Output the username of the displayed user.
 *
 * @since 1.2.0
 */
function bp_displayed_user_username() {
	echo esc_html( bp_get_displayed_user_username() );
}
	/**
	 * Get the username of the displayed user.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function bp_get_displayed_user_username() {
		$bp = buddypress();

		if ( bp_displayed_user_id() ) {
			$username = bp_members_get_user_slug( bp_displayed_user_id() );
		} else {
			$username = '';
		}

		/**
		 * Filters the username of the displayed user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $username Username of the displayed user.
		 */
		return apply_filters( 'bp_get_displayed_user_username', $username );
	}

/**
 * Output the username of the logged-in user.
 *
 * @since 1.2.0
 */
function bp_loggedin_user_username() {
	echo esc_html( bp_get_loggedin_user_username() );
}
	/**
	 * Get the username of the logged-in user.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function bp_get_loggedin_user_username() {
		$bp = buddypress();

		if ( bp_loggedin_user_id() ) {
			$username = bp_members_get_user_slug( bp_loggedin_user_id() );
		} else {
			$username = '';
		}

		/**
		 * Filters the username of the logged-in user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $username Username of the logged-in user.
		 */
		return apply_filters( 'bp_get_loggedin_user_username', $username );
	}

/**
 * Echo the current member type message.
 *
 * @since 2.3.0
 */
function bp_current_member_type_message() {
	echo wp_kses( bp_get_current_member_type_message(), array( 'strong' => true ) );
}
	/**
	 * Generate the current member type message.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	function bp_get_current_member_type_message() {
		$type_object = bp_get_member_type_object( bp_get_current_member_type() );

		/* translators: %s: member type singular name */
		$message = sprintf( __( 'Viewing members of the type: %s', 'buddypress' ), '<strong>' . $type_object->labels['singular_name'] . '</strong>' );

		/**
		 * Filters the current member type message.
		 *
		 * @since 2.3.0
		 *
		 * @param string $message Message to filter.
		 */
		return apply_filters( 'bp_get_current_member_type_message', $message );
	}

/**
 * Output member type directory link.
 *
 * @since 7.0.0
 *
 * @param string $member_type Unique member type identifier as used in bp_register_member_type().
 */
function bp_member_type_directory_link( $member_type = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_member_type_directory_link( $member_type );
}
	/**
	 * Return member type directory link.
	 *
	 * @since 7.0.0
	 *
	 * @param string $member_type Unique member type identifier as used in bp_register_member_type().
	 * @return string
	 */
	function bp_get_member_type_directory_link( $member_type = '' ) {
		if ( empty( $member_type ) ) {
			return '';
		}

		$member_type_object = bp_get_member_type_object( $member_type );

		if ( ! isset( $member_type_object->labels['name'] ) ) {
			return '';
		}

		$member_type_text = $member_type_object->labels['name'];
		if ( isset( $member_type_object->labels['singular_name'] ) && $member_type_object->labels['singular_name'] ) {
			$member_type_text = $member_type_object->labels['singular_name'];
		}

		if ( empty( $member_type_object->has_directory ) ) {
			return esc_html( $member_type_text );
		}

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( bp_get_member_type_directory_permalink( $member_type ) ),
			esc_html( $member_type_text )
		);
	}

/**
 * Output a comma-delimited list of member types.
 *
 * @since 7.0.0
 *
 * @see bp_get_member_type_list() For additional information on default arguments.
 *
 * @param int   $user_id User ID.
 * @param array $r       Optional. Member type list arguments. Default empty array.
 */
function bp_member_type_list( $user_id = 0, $r = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_member_type_list( $user_id, $r );
}
	/**
	 * Return a comma-delimited list of member types.
	 *
	 * @since 7.0.0
	 *
	 * @param int          $user_id User ID. Defaults to displayed user ID if on a member page.
	 * @param array|string $r       {
	 *     Array of parameters. All items are optional.
	 *     @type string $parent_element     Element to wrap around the list. Defaults to 'p'.
	 *     @type array  $parent_attr        Element attributes for parent element. Defaults to
	 *                                      array( 'class' => 'bp-member-type-list' ).
	 *     @type array  $label              Plural and singular labels to use before the list. Defaults to
	 *                                      array( 'plural' => 'Member Types:', 'singular' => 'Member Type:' ).
	 *     @type string $label_element      Element to wrap around the label. Defaults to 'strong'.
	 *     @type array  $label_attr         Element attributes for label element. Defaults to array().
	 *     @type bool   $show_all           Whether to show all registered group types. Defaults to 'false'. If
	 *                                      'false', only shows member types with the 'show_in_list' parameter set to
	 *                                      true. See bp_register_member_type() for more info.
	 *     @type string $list_element       Element to wrap around the comma separated list of membet types. Defaults to ''.
	 *     @type string $list_element_attr  Element attributes for list element. Defaults to array().
	 * }
	 * @return string
	 */
	function bp_get_member_type_list( $user_id = 0, $r = array() ) {
		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		$r = bp_parse_args(
			$r,
			array(
				'parent_element'    => 'p',
				'parent_attr'       => array(
					'class' => 'bp-member-type-list',
				),
				'label'             => array(),
				'label_element'     => 'strong',
				'label_attr'        => array(),
				'show_all'          => false,
				'list_element'      => '',
				'list_element_attr' => array(),
			),
			'member_type_list'
		);

		// Should the label be output?
		$has_label = ! empty( $r['label'] );

		$labels = bp_parse_args(
			$r['label'],
			array(
				'plural'   => __( 'Member Types:', 'buddypress' ),
				'singular' => __( 'Member Type:', 'buddypress' ),
			)
		);

		$retval = '';
		$types  = bp_get_member_type( $user_id, false );

		if ( $types ) {
			// Make sure we can show the type in the list.
			if ( false === $r['show_all'] ) {
				$types = array_intersect( bp_get_member_types( array( 'show_in_list' => true ) ), $types );
				if ( empty( $types ) ) {
					return $retval;
				}
			}

			$before = $after = $label = '';
			$count  = count( $types );

			if ( 1 === $count ) {
				$label_text = $labels['singular'];
			} else {
				$label_text = $labels['plural'];
			}

			// Render parent element.
			if ( ! empty( $r['parent_element'] ) ) {
				$parent_elem = new BP_Core_HTML_Element( array(
					'element' => $r['parent_element'],
					'attr'    => $r['parent_attr'],
				) );

				// Set before and after.
				$before = $parent_elem->get( 'open_tag' );
				$after  = $parent_elem->get( 'close_tag' );
			}

			// Render label element.
			if ( ! empty( $r['label_element'] ) ) {
				$label = new BP_Core_HTML_Element( array(
					'element'    => $r['label_element'],
					'attr'       => $r['label_attr'],
					'inner_html' => esc_html( $label_text ),
				) );
				$label = $label->contents() . ' ';

			// No element, just the label.
			} elseif ( $has_label ) {
				$label = esc_html( $label_text );
			}

			// The list of types.
			$list = implode( ', ', array_map( 'bp_get_member_type_directory_link', $types ) );

			// Render the list of types element.
			if ( ! empty( $r['list_element'] ) ) {
				$list_element = new BP_Core_HTML_Element( array(
					'element'    => $r['list_element'],
					'attr'       => $r['list_element_attr'],
					'inner_html' => $list,
				) );

				$list = $list_element->contents();
			}

			// Comma-delimit each type into the group type directory link.
			$label .= $list;

			// Retval time!
			$retval = $before . $label . $after;
		}

		return $retval;
	}

/** Signup Form ***************************************************************/

/**
 * Do we have a working custom sign up page?
 *
 * @since 1.5.0
 *
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_signup_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_signup_slug() && bp_locate_template( array( 'registration/register.php', 'members/register.php', 'register.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL to the signup page.
 *
 * @since 1.0.0
 */
function bp_signup_page() {
	echo esc_url( bp_get_signup_page() );
}
	/**
	 * Get the URL to the signup page.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_signup_page() {
		if ( bp_has_custom_signup_page() ) {
			$page = bp_rewrites_get_url(
				array(
					'component_id'    => 'members',
					'member_register' => 1,
				)
			);

		} else {
			$page = trailingslashit( bp_get_root_url() ) . 'wp-signup.php';
		}

		/**
		 * Filters the URL to the signup page.
		 *
		 * @since 1.1.0
		 *
		 * @param string $page URL to the signup page.
		 */
		return apply_filters( 'bp_get_signup_page', $page );
	}

/**
 * Do we have a working custom activation page?
 *
 * @since 1.5.0
 *
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_activation_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_activate_slug() && bp_locate_template( array( 'registration/activate.php', 'members/activate.php', 'activate.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL of the activation page.
 *
 * @since 1.0.0
 */
function bp_activation_page() {
	echo esc_url( bp_get_activation_page() );
}
	/**
	 * Get the URL of the activation page.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function bp_get_activation_page() {
		if ( bp_has_custom_activation_page() ) {
			$page = bp_rewrites_get_url(
				array(
					'component_id'    => 'members',
					'member_activate' => 1,
				)
			);

		} else {
			$page = trailingslashit( bp_get_root_url() ) . 'wp-activate.php';
		}

		/**
		 * Filters the URL of the activation page.
		 *
		 * @since 1.2.0
		 *
		 * @param string $page URL to the activation page.
		 */
		return apply_filters( 'bp_get_activation_page', $page );
	}

/**
 * Get the activation key from the current request URL.
 *
 * @since 3.0.0
 *
 * @return string
 */
function bp_get_current_activation_key() {
	$key = '';

	if ( bp_is_current_component( 'activate' ) ) {
		if ( isset( $_GET['key'] ) ) {
			$key = wp_unslash( $_GET['key'] );
		} else {
			$key = bp_current_action();
		}
	}

	/**
	 * Filters the activation key from the current request URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key Activation key.
	 */
	return apply_filters( 'bp_get_current_activation_key', $key );
}

/**
 * Output the username submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_username_value() {
	echo esc_html( bp_get_signup_username_value() );
}
	/**
	 * Get the username submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_username_value() {
		$value = '';
		if ( isset( $_POST['signup_username'] ) )
			$value = $_POST['signup_username'];

		/**
		 * Filters the username submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Username submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_username_value', $value );
	}

/**
 * Output the user email address submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_email_value() {
	echo esc_html( bp_get_signup_email_value() );
}
	/**
	 * Get the email address submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_email_value() {
		$value = '';
		if ( isset( $_POST['signup_email'] ) ) {
			$value = $_POST['signup_email'];
		} else if ( bp_get_members_invitations_allowed() ) {
			$invite = bp_get_members_invitation_from_request();
			if ( $invite ) {
				$value = $invite->invitee_email;
			}
		}

		/**
		 * Filters the email address submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Email address submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_email_value', $value );
	}

/**
 * Output the 'signup_with_blog' value submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_with_blog_value() {
	echo intval( bp_get_signup_with_blog_value() );
}
	/**
	 * Get the 'signup_with_blog' value submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_signup_with_blog_value() {
		$value = '';
		if ( isset( $_POST['signup_with_blog'] ) )
			$value = $_POST['signup_with_blog'];

		/**
		 * Filters the 'signup_with_blog' value submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value 'signup_with_blog' value submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_with_blog_value', $value );
	}

/**
 * Output the 'signup_blog_url' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_url_value() {
	echo esc_url( bp_get_signup_blog_url_value() );
}
	/**
	 * Get the 'signup_blog_url' value submitted at signup.
	 *
	 * @since 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_url_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_url'] ) )
			$value = $_POST['signup_blog_url'];

		/**
		 * Filters the 'signup_blog_url' value submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value 'signup_blog_url' value submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_blog_url_value', $value );
	}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since 2.1.0
 */
function bp_signup_subdomain_base() {
	echo esc_attr( bp_signup_get_subdomain_base() );
}
	/**
	 * Return the base URL for subdomain installations of WordPress Multisite.
	 *
	 * Replaces bp_blogs_get_subdomain_base()
	 *
	 * @since 2.1.0
	 *
	 * @global WP_Network $current_site
	 *
	 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
	 */
	function bp_signup_get_subdomain_base() {
		global $current_site;

		// In case plugins are still using this filter.
		$subdomain_base = apply_filters( 'bp_blogs_subdomain_base', preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path );

		/**
		 * Filters the base URL for subdomain installations of WordPress Multisite.
		 *
		 * @since 2.1.0
		 *
		 * @param string $subdomain_base The base URL - eg, 'example.com' for
		 *                               site_url() example.com or www.example.com.
		 */
		return apply_filters( 'bp_signup_subdomain_base', $subdomain_base );
	}

/**
 * Output the 'signup_blog_titl' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_title_value() {
	echo esc_html( bp_get_signup_blog_title_value() );
}
	/**
	 * Get the 'signup_blog_title' value submitted at signup.
	 *
	 * @since 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_title_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_title'] ) )
			$value = $_POST['signup_blog_title'];

		/**
		 * Filters the 'signup_blog_title' value submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value 'signup_blog_title' value submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_blog_title_value', $value );
	}

/**
 * Output the 'signup_blog_privacy' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_privacy_value() {
	echo esc_html( bp_get_signup_blog_privacy_value() );
}
	/**
	 * Get the 'signup_blog_privacy' value submitted at signup.
	 *
	 * @since 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_privacy_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_privacy'] ) )
			$value = $_POST['signup_blog_privacy'];

		/**
		 * Filters the 'signup_blog_privacy' value submitted during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value 'signup_blog_privacy' value submitted during signup.
		 */
		return apply_filters( 'bp_get_signup_blog_privacy_value', $value );
	}

/**
 * Output the avatar dir used during signup.
 *
 * @since 1.1.0
 */
function bp_signup_avatar_dir_value() {
	echo esc_html( bp_get_signup_avatar_dir_value() );
}
	/**
	 * Get the avatar dir used during signup.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_signup_avatar_dir_value() {
		$bp = buddypress();

		// Check if signup_avatar_dir is passed.
		if ( ! empty( $_POST['signup_avatar_dir'] ) ) {
			$signup_avatar_dir = $_POST['signup_avatar_dir'];

			// If not, check if global is set.
		} elseif ( ! empty( $bp->signup->avatar_dir ) ) {
			$signup_avatar_dir = $bp->signup->avatar_dir;

			// If not, set false.
		} else {
			$signup_avatar_dir = false;
		}

		/**
		 * Filters the avatar dir used during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string|bool $signup_avatar_dir Avatar dir used during signup or false.
		 */
		return apply_filters( 'bp_get_signup_avatar_dir_value', $signup_avatar_dir );
	}

/**
 * Determines whether privacy policy acceptance is required for registration.
 *
 * @since 4.0.0
 *
 * @return bool
 */
function bp_signup_requires_privacy_policy_acceptance() {

	// Default to true when a published Privacy Policy page exists.
	$privacy_policy_url = get_privacy_policy_url();
	$required           = ! empty( $privacy_policy_url );

	/**
	 * Filters whether privacy policy acceptance is required for registration.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $required Whether privacy policy acceptance is required.
	 */
	return (bool) apply_filters( 'bp_signup_requires_privacy_policy_acceptance', $required );
}

/**
 * Output the current signup step.
 *
 * @since 1.1.0
 */
function bp_current_signup_step() {
	echo esc_html( bp_get_current_signup_step() );
}
	/**
	 * Get the current signup step.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_current_signup_step() {
		return (string) buddypress()->signup->step;
	}

/**
 * Output the user avatar during signup.
 *
 * @since 1.1.0
 *
 * @see bp_get_signup_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_signup_avatar(}.
 */
function bp_signup_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_avatar( $args );
}
	/**
	 * Get the user avatar during signup.
	 *
	 * @since 1.1.0
	 *
	 * @see bp_core_fetch_avatar() for description of arguments.
	 *
	 * @param array|string $args {
	 *     Array of optional arguments.
	 *     @type int    $size  Height/weight in pixels. Default: value of
	 *                         bp_core_avatar_full_width().
	 *     @type string $class CSS class. Default: 'avatar'.
	 *     @type string $alt   HTML 'alt' attribute. Default: 'Your Avatar'.
	 * }
	 * @return string
	 */
	function bp_get_signup_avatar( $args = '' ) {
		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'size'  => bp_core_avatar_full_width(),
				'class' => 'avatar',
				'alt'   => __( 'Your Profile Photo', 'buddypress' ),
			)
		);

		$signup_avatar_dir = bp_get_signup_avatar_dir_value();

		// Avatar DIR is found.
		if ( $signup_avatar_dir ) {
			$gravatar_img = bp_core_fetch_avatar( array(
				'item_id'    => $signup_avatar_dir,
				'object'     => 'signup',
				'avatar_dir' => 'avatars/signups',
				'type'       => 'full',
				'width'      => $r['size'],
				'height'     => $r['size'],
				'alt'        => $r['alt'],
				'class'      => $r['class'],
			) );

			// No avatar DIR was found.
		} else {

			// Set default gravatar type.
			if ( empty( $bp->grav_default->user ) ) {
				$default_grav = 'wavatar';
			} elseif ( 'mystery' === $bp->grav_default->user ) {
				$default_grav = $bp->plugin_url . 'bp-core/images/mystery-man.jpg';
			} else {
				$default_grav = $bp->grav_default->user;
			}

			/**
			 * Filters the base Gravatar url used for signup avatars when no avatar dir found.
			 *
			 * @since 1.0.2
			 *
			 * @param string $value Gravatar url to use.
			 */
			$gravatar_url    = apply_filters( 'bp_gravatar_url', '//www.gravatar.com/avatar/' );
			$md5_lcase_email = md5( strtolower( bp_get_signup_email_value() ) );
			$gravatar_img    = '<img src="' . $gravatar_url . $md5_lcase_email . '?d=' . $default_grav . '&amp;s=' . $r['size'] . '" width="' . esc_attr( $r['size'] ) . '" height="' . esc_attr( $r['size'] ) . '" alt="' . esc_attr( $r['alt'] ) . '" class="' . esc_attr( $r['class'] ) . '" />';
		}

		/**
		 * Filters the user avatar during signup.
		 *
		 * @since 1.1.0
		 *
		 * @param string $gravatar_img Avatar HTML image tag.
		 * @param array  $args         Array of parsed args for avatar query.
		 */
		return apply_filters( 'bp_get_signup_avatar', $gravatar_img, $args );
	}

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 *
 * @todo Remove this function. Echoing a bool is pointless.
 */
function bp_signup_allowed() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_allowed();
}
	/**
	 * Is user signup allowed?
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	function bp_get_signup_allowed() {

		/**
		 * Filters whether or not new signups are allowed.
		 *
		 * @since 1.5.0
		 *
		 * @param bool $signup_allowed Whether or not new signups are allowed.
		 */
		return apply_filters( 'bp_get_signup_allowed', (bool) bp_get_option( 'users_can_register' ) );
	}

/**
 * Are users allowed to invite users to join this site?
 *
 * @since 8.0.0
 *
 * @return bool
 */
function bp_get_members_invitations_allowed() {
	/**
	 * Filters whether or not community invitations are allowed.
	 *
	 * @since 8.0.0
	 *
	 * @param bool $allowed Whether or not community invitations are allowed.
	 */
	return apply_filters( 'bp_get_members_invitations_allowed', bp_is_active( 'members', 'invitations' ) && (bool) bp_get_option( 'bp-enable-members-invitations' ) );
}

/**
 * Are membership requests required for joining this site?
 *
 * @since 10.0.0
 *
 * @param bool $context "raw" to fetch value from database,
 *                      "site" to take "anyone can register" setting into account.
 * @return bool
 */
function bp_get_membership_requests_required( $context = 'site' ) {
	if ( 'raw' === $context ) {
		$retval = bp_is_active( 'members', 'membership_requests' ) && (bool) bp_get_option( 'bp-enable-membership-requests' );
	} else {
		$retval = bp_is_active( 'members', 'membership_requests' ) && ! bp_get_signup_allowed() && (bool) bp_get_option( 'bp-enable-membership-requests' );
	}

	/**
	 * Filters whether or not prospective members may submit network membership requests.
	 *
	 * @since 10.0.0
	 *
	 * @param bool $retval Whether or not membership requests are required.
	 * @param bool $retval Whether this is the value stored in the database ('raw')
	 *                     or whether the site's "anyone can register" setting is
	 *                     being considered ('site' or anything else).
	 */
	return apply_filters( 'bp_get_membership_requests_required', $retval, $context );
}

/**
 * Should the system create and allow access
 * to the Register and Activate pages?
 *
 * @since 10.0.0
 *
 * @return bool
 */
function bp_allow_access_to_registration_pages() {
	$retval = bp_get_signup_allowed() || bp_get_members_invitations_allowed() || bp_get_membership_requests_required();

	/**
	 * Filters whether or not the system should create and allow access
	 * to the Register and Activate pages.
	 *
	 * @since 10.0.0
	 *
	 * @param bool $retval Whether or not to allow access to
	 *                     the Register and Activate pages.
	 */
	return apply_filters( 'bp_allow_access_to_registration_pages', $retval );
}

/**
 * Hook member activity feed to <head>.
 *
 * @since 1.5.0
 */
function bp_members_activity_feed() {
	if ( ! bp_is_active( 'activity' ) || ! bp_is_user() ) {
		return;
	}
	// phpcs:disable WordPress.Security.EscapeOutput
	?>
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php bp_displayed_user_fullname() ?> | <?php esc_attr_e( 'Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_member_activity_feed_link() ?>" />
	<?php
	// phpcs:enable
}
add_action( 'bp_head', 'bp_members_activity_feed' );

/**
 * Output a link to a members component subpage.
 *
 * @since 1.5.0
 *
 * @see bp_get_members_component_link() for description of parameters.
 *
 * @param string      $component See {@bp_get_members_component_link()}.
 * @param string      $action See {@bp_get_members_component_link()}.
 * @param string      $query_args See {@bp_get_members_component_link()}.
 * @param string|bool $nonce See {@bp_get_members_component_link()}.
 */
function bp_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
	echo esc_url( bp_get_members_component_link( $component, $action, $query_args, $nonce ) );
}
	/**
	 * Generate a link to a members component subpage.
	 *
	 * @since 1.5.0
	 *
	 * @param string       $component  ID of the component (eg 'friends').
	 * @param string       $action     Optional. 'action' slug (eg 'invites').
	 * @param array|string $query_args Optional. Array of URL params to add to the
	 *                                 URL. See {@link add_query_arg()} for format.
	 * @param array|bool   $nonce      Optional. If provided, the URL will be passed
	 *                                 through wp_nonce_url() with $nonce as the
	 *                                 action string.
	 * @return string
	 */
	function bp_get_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
		// Must be displayed user.
		if ( ! bp_displayed_user_id() ) {
			return;
		}

		$bp = buddypress();

		if ( 'xprofile' === $component ) {
			$component = 'profile';
		}

		$path_chunks = array( $bp->{$component}->slug );

		// Append $action to $url if needed.
		if ( ! empty( $action ) ) {
			$path_chunks[] = $action;
		}

		// Check for slugs customization.
		$path_chunks = bp_members_get_path_chunks( $path_chunks );

		// Generate user url.
		$url = bp_displayed_user_url( $path_chunks );

		// Add possible query arg.
		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		// To nonce, or not to nonce...
		if ( true === $nonce ) {
			$url = wp_nonce_url( $url );
		} elseif ( is_string( $nonce ) ) {
			$url = wp_nonce_url( $url, $nonce );
		}

		// Return the url, if there is one.
		if ( ! empty( $url ) ) {
			return $url;
		}
	}


/**
 * Render an avatar delete link.
 *
 * @since 1.1.0
 * @since 6.0.0 Moved from /bp-xprofile/bp-xprofile-template.php to this file.
 */
function bp_avatar_delete_link() {
	echo esc_url( bp_get_avatar_delete_link() );
}
	/**
	 * Return an avatar delete link.
	 *
	 * @since 1.1.0
	 * @since 6.0.0 Moved from /bp-xprofile/bp-xprofile-template.php to this file.
	 *
	 * @return string
	 */
	function bp_get_avatar_delete_link() {
		$profile_slug = bp_get_profile_slug();
		$url          = wp_nonce_url(
			bp_displayed_user_url(
				bp_members_get_path_chunks( array( $profile_slug, 'change-avatar', array( 'delete-avatar' ) ) )
			),
			'bp_delete_avatar_link'
		);

		/**
		 * Filters the link used for deleting an avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url Nonced URL used for deleting an avatar.
		 */
		return apply_filters( 'bp_get_avatar_delete_link', $url );
	}


/** The Members Invitations Loop ******************************************************************/

/**
 * Initialize the community invitations loop.
 *
 * Based on the $args passed, bp_has_invitations() populates
 * buddypress()->invitations->query_loop global, enabling the use of BP
 * templates and template functions to display a list of invitations.
 *
 * @since 8.0.0
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the invitations loop. Can be
 *     passed as an associative array, or as a URL query string.
 *
 *     See {@link BP_Invitations_Invitation::get()} for detailed
 *     information on the arguments.  In addition, also supports:
 *
 *     @type int    $max      Optional. Max items to display. Default: false.
 *     @type string $page_arg URL argument to use for pagination.
 *                            Default: 'ipage'.
 * }
 * @return bool
 */
function bp_has_members_invitations( $args = '' ) {
	$bp = buddypress();

	// Get the user ID.
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	} else {
		$user_id = bp_loggedin_user_id();
	}

	// Set the search terms (by default an empty string to get all notifications).
	$search_terms = '';

	if ( isset( $_REQUEST['s'] ) ) {
		$search_terms = stripslashes( $_REQUEST['s'] );
	}

	// Parse the args.
	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,
			'inviter_id'    => $user_id,
			'invitee_email' => false,
			'item_id'       => false,
			'type'          => 'invite',
			'invite_sent'   => 'all',
			'accepted'      => 'pending',
			'search_terms'  => $search_terms,
			'order_by'      => 'date_modified',
			'sort_order'    => 'DESC',
			'page'          => 1,
			'per_page'      => 25,
			'fields'        => 'all',

			// These are additional arguments that are not available in
			// BP_Invitations_Invitation::get().
			'page_arg'      => 'ipage',
		),
		'has_members_invitations'
	);

	// Get the notifications.
	$query_loop = new BP_Members_Invitations_Template( $r );

	// Setup the global query loop.
	$bp->members->invitations->query_loop = $query_loop;

	/**
	 * Filters whether or not the user has network invitations to display.
	 *
	 * @since 8.0.0
	 *
	 * @param bool                      $value      Whether or not there are network invitations to display.
	 * @param BP_Notifications_Template $query_loop BP_Members_Invitations_Template object instance.
	 * @param array                     $r          Array of arguments passed into the BP_Members_Invitations_Template class.
	 */
	return apply_filters( 'bp_has_members_invitations', $query_loop->has_invitations(), $query_loop, $r );
}

/**
 * Get the network invitations returned by the template loop.
 *
 * @since 8.0.0
 *
 * @return array List of network invitations.
 */
function bp_the_members_invitations() {
	return buddypress()->members->invitations->query_loop->invitations();
}

/**
 * Get the current network invitation object in the loop.
 *
 * @since 8.0.0
 *
 * @return object The current network invitation within the loop.
 */
function bp_the_members_invitation() {
	return buddypress()->members->invitations->query_loop->the_invitation();
}

/**
 * Output the pagination count for the current network invitations loop.
 *
 * @since 8.0.0
 */
function bp_members_invitations_pagination_count() {
	echo esc_html( bp_get_members_invitations_pagination_count() );
}
	/**
	 * Return the pagination count for the current network invitation loop.
	 *
	 * @since 8.0.0
	 *
	 * @return string HTML for the pagination count.
	 */
	function bp_get_members_invitations_pagination_count() {
		$bp         = buddypress();
		$query_loop = $bp->members->invitations->query_loop;
		$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
		$from_num   = bp_core_number_format( $start_num );
		$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_invitation_count ) ? $query_loop->total_invitation_count : $start_num + ( $query_loop->pag_num - 1 ) );
		$total      = bp_core_number_format( $query_loop->total_invitation_count );

		if ( 1 == $query_loop->total_invitation_count ) {
			$pag = __( 'Viewing 1 invitation', 'buddypress' );
		} else {
			/* translators: 1: Invitations from number. 2: Invitations to number. 3: Total invitations. */
			$pag = sprintf( _nx( 'Viewing %1$s - %2$s of %3$s invitation', 'Viewing %1$s - %2$s of %3$s invitations', $query_loop->total_invitation_count, 'Community invites pagination', 'buddypress' ), $from_num, $to_num, $total );
		}

		/**
		 * Filters the pagination count for the current network invitation loop.
		 *
		 * @since 8.0.0
		 *
		 * @param string $pag HTML for the pagination count.
		 */
		return apply_filters( 'bp_get_members_invitations_pagination_count', $pag );
	}

/**
 * Output the pagination links for the current network invitation loop.
 *
 * @since 8.0.0
 */
function bp_members_invitations_pagination_links() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_members_invitations_pagination_links();
}
	/**
	 * Return the pagination links for the current network invitations loop.
	 *
	 * @since 8.0.0
	 *
	 * @return string HTML for the pagination links.
	 */
	function bp_get_members_invitations_pagination_links() {
		$bp = buddypress();

		/**
		 * Filters the pagination links for the current network invitations loop.
		 *
		 * @since 8.0.0
		 *
		 * @param string $pag_links HTML for the pagination links.
		 */
		return apply_filters( 'bp_get_members_invitations_pagination_links', $bp->members->invitations->query_loop->pag_links );
	}

/**
 * Output the requested property of the invitation currently being iterated on.
 *
 * @since 8.0.0
 *
 * @param string $property The name of the property to display.
 * @param string $context  The context of display.
 *                         Possible values are 'attribute' and 'html'.
 */
function bp_the_members_invitation_property( $property = '', $context = 'html' ) {
	if ( ! $property ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Use this filter to sanitize the output.
		 *
		 * @since 8.0.0
		 *
		 * @param int|string $value    The value for the requested property.
		 * @param string     $property The name of the requested property.
		 * @param string     $context  The context of display.
		 */
		'bp_the_members_invitation_property',
		bp_get_the_members_invitation_property( $property ),
		$property,
		$context
	);
}
	/**
	 * Return the value for a property of the network invitation currently being iterated on.
	 *
	 * @since 8.0.0
	 *
	 * @return int ID of the current network invitation.
	 */
	function bp_get_the_members_invitation_property( $property = 'id' ) {

		switch ( $property ) {
			case 'id':
			case 'user_id':
			case 'item_id':
			case 'secondary_item_id':
			case 'invite_sent':
			case 'accepted':
				$value = 0;
				break;
			case 'invitee_email':
			case 'type':
			case 'content':
			case 'date_modified':
				$value = '';
				break;
			default:
				// A known property has not been specified.
				$property = null;
				$value = '';
				break;
		}

		if ( isset( buddypress()->members->invitations->query_loop->invitation->{$property} ) ) {
			$value = buddypress()->members->invitations->query_loop->invitation->{$property};
		}

		/**
		 * Filters the property of the network invitation currently being iterated on.
		 *
		 * @since 8.0.0
		 *
		 * @param int|string $value Property value of the network invitation being iterated on.
		 */
		return apply_filters( 'bp_get_the_members_invitation_property_' . $property, $value );
	}

/**
 * Output the action links for the current invitation.
 *
 * @since 8.0.0
 *
 * @param array|string $args Array of arguments.
 */
function bp_the_members_invitation_action_links( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_the_members_invitation_action_links( $args );
}
	/**
	 * Return the action links for the current invitation.
	 *
	 * @since 8.0.0
	 *
	 * @param array|string $args {
	 *     @type string $before  HTML before the links.
	 *     @type string $after   HTML after the links.
	 *     @type string $sep     HTML between the links.
	 *     @type array  $links   Array of links to implode by 'sep'.
	 *     @type int    $user_id User ID to fetch action links for. Defaults to displayed user ID.
	 * }
	 * @return string HTML links for actions to take on single notifications.
	 */
	function bp_get_the_members_invitation_action_links( $args = '' ) {
		// Set default user ID to use.
		$inviter_id = isset( $args['inviter_id'] ) ? $args['inviter_id'] : bp_displayed_user_id();

		// Parse.
		$r = bp_parse_args(
			$args,
			array(
				'before' => '',
				'after'  => '',
				'sep'    => ' | ',
				'links'  => array(
					bp_get_the_members_invitation_resend_link( $inviter_id ),
					bp_get_the_members_invitation_delete_link( $inviter_id )
				)
			)
		);

		// Build the links.
		$retval = $r['before'] . implode( $r['sep'], $r['links'] ) . $r['after'];

		/**
		 * Filters the action links for the current invitation.
		 *
		 * @since 8.0.0
		 *
		 * @param string $retval HTML links for actions to take on single invitation.
		 * @param array  $r      Array of parsed arguments.
		 */
		return apply_filters( 'bp_get_the_members_invitation_action_links', $retval, $r );
	}

/**
 * Output the resend link for the current invitation.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_the_members_invitations_resend_link( $user_id = 0 ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_the_members_invitation_delete_link( $user_id );
}
	/**
	 * Return the resend link for the current notification.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_members_invitation_resend_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		// Don't allow resending of accepted invitations.
		if ( bp_get_the_members_invitation_property( 'accepted' ) ) {
			return;
		}

		$retval = sprintf( '<a href="%1$s" class="resend secondary confirm bp-tooltip">%2$s</a>', esc_url( bp_get_the_members_invitations_resend_url( $user_id ) ), esc_html__( 'Resend', 'buddypress' ) );

		/**
		 * Filters the resend link for the current invitation.
		 *
		 * @since 8.0.0
		 *
		 * @param string $retval  HTML for the delete link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_members_invitation_resend_link', $retval, $user_id );
	}

/**
 * Output the URL used for resending a single invitation.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_the_members_invitations_resend_url( $user_id = 0 ) {
	echo esc_url( bp_get_the_members_invitations_resend_url( $user_id ) );
}
	/**
	 * Return the URL used for resending a single invitation.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_members_invitations_resend_url( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;
		$link = bp_get_members_invitations_list_invites_permalink( $user_id );

		// Get the ID.
		$id = bp_get_the_members_invitation_property( 'id' );

		// Get the args to add to the URL.
		$args = array(
			'action'        => 'resend',
			'invitation_id' => $id
		);

		// Add the args.
		$url = add_query_arg( $args, $link );

		// Add the nonce.
		$url = wp_nonce_url( $url, 'bp_members_invitation_resend_' . $id );

		/**
		 * Filters the URL used for resending a single invitation.
		 *
		 * @since 8.0.0
		 *
		 * @param string $url     URL used for deleting a single invitation.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_members_invitations_resend_url', $url, $user_id );
	}

/**
 * Output the delete link for the current invitation.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_the_members_invitations_delete_link( $user_id = 0 ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_the_members_invitation_delete_link( $user_id );
}
	/**
	 * Return the delete link for the current invitation.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_members_invitation_delete_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		// Modify the message for accepted/not accepted invitatons.
		if ( bp_get_the_members_invitation_property( 'accepted' ) ) {
			$message = __( 'Delete', 'buddypress' );
		} else {
			$message = __( 'Cancel', 'buddypress' );
		}

		$retval = sprintf(
			'<a href="%1$s" class="delete secondary confirm bp-tooltip">%2$s</a>',
			esc_url( bp_get_the_members_invitations_delete_url( $user_id ) ),
			esc_html( $message )
		);

		/**
		 * Filters the delete link for the current invitation.
		 *
		 * @since 8.0.0
		 *
		 * @param string $retval  HTML for the delete link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_members_invitation_delete_link', $retval, $user_id );
	}

/**
 * Output the URL used for deleting a single invitation.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_the_members_invitations_delete_url( $user_id = 0 ) {
	echo esc_url( bp_get_the_members_invitations_delete_url( $user_id ) );
}
	/**
	 * Return the URL used for deleting a single invitation.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_members_invitations_delete_url( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;
		$link = bp_get_members_invitations_list_invites_permalink( $user_id );

		// Get the ID.
		$id = bp_get_the_members_invitation_property( 'id' );

		// Get the args to add to the URL.
		$args = array(
			'action'        => 'cancel',
			'invitation_id' => $id
		);

		// Add the args.
		$url = add_query_arg( $args, $link );

		// Add the nonce.
		$url = wp_nonce_url( $url, 'bp_members_invitations_cancel_' . $id );

		/**
		 * Filters the URL used for deleting a single invitation.
		 *
		 * @since 8.0.0
		 *
		 * @param string $url     URL used for deleting a single invitation.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_members_invitations_delete_url', $url, $user_id );
	}

/**
 * Output the members invitations list permalink for a user.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_members_invitations_list_invites_permalink( $user_id = 0 ) {
	echo esc_url( bp_get_members_invitations_list_invites_permalink( $user_id ) );
}
	/**
	 * Return the members invitations list permalink for a user.
	 *
	 * @since 8.0.0
	 *
	 * @return string Members invitations list permalink for a user.
	 */
	function bp_get_members_invitations_list_invites_permalink( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = bp_loggedin_user_id();
		}

		$retval = bp_members_get_user_url(
			(int) $user_id,
			bp_members_get_path_chunks( array( bp_get_members_invitations_slug(), 'list-invites' ) )
		);

		/**
		 * Filters the members invitations list permalink for a user.
		 *
		 * @since 8.0.0
		 *
		 * @param string $retval  Permalink for the sent invitation list screen.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_members_invitations_list_invites_permalink', $retval, $user_id );
	}

/**
 * Output the send invitation permalink for a user.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_members_invitations_send_invites_permalink( $user_id = 0 ) {
	echo esc_url( bp_get_members_invitations_send_invites_permalink( $user_id ) );
}
	/**
	 * Return the send invitations permalink.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return string      The send invitations permalink.
	 */
	function bp_get_members_invitations_send_invites_permalink( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = bp_loggedin_user_id();
		}

		$retval = bp_members_get_user_url(
			(int) $user_id,
			bp_members_get_path_chunks( array( bp_get_members_invitations_slug(), 'send-invites' ) )
		);

		/**
		 * Filters the send invitations permalink.
		 *
		 * @since 8.0.0
		 *
		 * @param string $retval  Permalink for the sent invitation list screen.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_members_invitations_send_invites_permalink', $retval, $user_id );
	}

/**
 * Output the dropdown for bulk management of invitations.
 *
 * @since 8.0.0
 */
function bp_members_invitations_bulk_management_dropdown() {
	?>
	<label class="bp-screen-reader-text" for="invitation-select">
		<?php
		esc_html_e( 'Select Bulk Action', 'buddypress' );
		?>
	</label>

	<select name="invitation_bulk_action" id="invitation-select">
		<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'buddypress' ); ?></option>
		<option value="resend"><?php echo esc_html_x( 'Resend', 'button', 'buddypress' ); ?></option>
		<option value="cancel"><?php echo esc_html_x( 'Cancel', 'button', 'buddypress' ); ?></option>
	</select>

	<input type="submit" id="invitation-bulk-manage" class="button action" value="<?php echo esc_attr_x( 'Apply', 'button', 'buddypress' ); ?>">
	<?php
}
