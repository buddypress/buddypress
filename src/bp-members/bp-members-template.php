<?php

/**
 * BuddyPress Member Template Tags
 *
 * Functions that are safe to use inside your template files and themes.
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the members component slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_members_slug()
 */
function bp_members_slug() {
	echo bp_get_members_slug();
}
	/**
	 * Return the members component slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_members_slug() {
		return apply_filters( 'bp_get_members_slug', buddypress()->members->slug );
	}

/**
 * Output the members component root slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_members_root_slug()
 */
function bp_members_root_slug() {
	echo bp_get_members_root_slug();
}
	/**
	 * Return the members component root slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_members_root_slug() {
		return apply_filters( 'bp_get_members_root_slug', buddypress()->members->root_slug );
	}

/**
 * Output member directory permalink.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_members_directory_permalink()
 */
function bp_members_directory_permalink() {
	echo bp_get_members_directory_permalink();
}
	/**
	 * Return member directory permalink.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_members_directory_permalink() {
		return apply_filters( 'bp_get_members_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) );
	}

/**
 * Output the sign-up slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_signup_slug()
 */
function bp_signup_slug() {
	echo bp_get_signup_slug();
}
	/**
	 * Return the sign-up slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_signup_slug() {
		$bp = buddypress();

		if ( !empty( $bp->pages->register->slug ) ) {
			$slug = $bp->pages->register->slug;
		} elseif ( defined( 'BP_REGISTER_SLUG' ) ) {
			$slug = BP_REGISTER_SLUG;
		} else {
			$slug = 'register';
		}

		return apply_filters( 'bp_get_signup_slug', $slug );
	}

/**
 * Output the activation slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_activate_slug()
 */
function bp_activate_slug() {
	echo bp_get_activate_slug();
}
	/**
	 * Return the activation slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_activate_slug() {
		$bp = buddypress();

		if ( !empty( $bp->pages->activate->slug ) ) {
			$slug = $bp->pages->activate->slug;
		} elseif ( defined( 'BP_ACTIVATION_SLUG' ) ) {
			$slug = BP_ACTIVATION_SLUG;
		} else {
			$slug = 'activate';
		}

		return apply_filters( 'bp_get_activate_slug', $slug );
	}

/**
 * The main member template loop class.
 *
 * Responsible for loading a group of members into a loop for display.
 */
class BP_Core_Members_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	var $current_member = -1;

	/**
	 * The number of members returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	var $member_count;

	/**
	 * Array of members located by the query.
	 *
	 * @access public
	 * @var array
	 */
	var $members;

	/**
	 * The member object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	var $member;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	var $in_the_loop;

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	var $pag_links;

	/**
	 * The total number of members matching the query parameters.
	 *
	 * @access public
	 * @var int
	 */
	var $total_member_count;

	/**
	 * Constructor method.
	 *
	 * @see BP_User_Query for an in-depth description of parameters.
	 *
	 * @param string $type Sort order.
	 * @param int $page_number Page of results.
	 * @param int $per_page Number of results per page.
	 * @param int $max Max number of results to return.
	 * @param int $user_id Limit to friends of a user.
	 * @param string $search_terms Limit to users matching search terms.
	 * @param array $include Limit results by these user IDs.
	 * @param bool $populate_extras Fetch optional extras.
	 * @param array $exclude Exclude these IDs from results.
	 * @param array $meta_key Limit to users with a meta_key.
	 * @param array $meta_value Limit to users with a meta_value (with meta_key).
	 * @param array $page_arg Optional. The string used as a query
	 *        parameter in pagination links. Default: 'upage'.
	 */
	function __construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value, $page_arg = 'upage' ) {

		$this->pag_page = !empty( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : (int) $page_number;
		$this->pag_num  = !empty( $_REQUEST['num'] )   ? intval( $_REQUEST['num'] )   : (int) $per_page;
		$this->type     = $type;

		if ( !empty( $_REQUEST['letter'] ) )
			$this->members = BP_Core_User::get_users_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page, $populate_extras, $exclude );
		else
			$this->members = bp_core_get_users( array( 'type' => $this->type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'include' => $include, 'search_terms' => $search_terms, 'populate_extras' => $populate_extras, 'exclude' => $exclude, 'meta_key' => $meta_key, 'meta_value' => $meta_value ) );

		if ( !$max || $max >= (int) $this->members['total'] )
			$this->total_member_count = (int) $this->members['total'];
		else
			$this->total_member_count = (int) $max;

		$this->members = $this->members['users'];

		if ( $max ) {
			if ( $max >= count( $this->members ) ) {
				$this->member_count = count( $this->members );
			} else {
				$this->member_count = (int) $max;
			}
		} else {
			$this->member_count = count( $this->members );
		}

		if ( (int) $this->total_member_count && (int) $this->pag_num ) {
			$pag_args = array(
				$page_arg => '%#%',
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			if ( ! empty( $search_terms ) ) {
				$pag_args['s'] = urlencode( $search_terms );
			}

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $pag_args, $base ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_member_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Member pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Member pagination next text', 'buddypress' ),
				'mid_size'   => 1
			) );
		}
	}

	/**
	 * Whether there are members available in the loop.
	 *
	 * @see bp_has_members()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_members() {
		if ( $this->member_count )
			return true;

		return false;
	}

	/**
	 * Set up the next member and iterate index.
	 *
	 * @return object The next member to iterate over.
	 */
	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];

		return $this->member;
	}

	/**
	 * Rewind the members and reset member index.
	 */
	function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}

	/**
	 * Whether there are members left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_members()} as part of the while loop
	 * that controls iteration inside the members loop, eg:
	 *     while ( bp_members() ) { ...
	 *
	 * @see bp_members()
	 *
	 * @return bool True if there are more members to show, otherwise false.
	 */
	function members() {
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {
			do_action('member_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current member inside the loop.
	 *
	 * Used by {@link bp_the_member()} to set up the current member data
	 * while looping, so that template tags used during that iteration make
	 * reference to the current member.
	 *
	 * @see bp_the_member()
	 */
	function the_member() {

		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		// loop has just started
		if ( 0 == $this->current_member )
			do_action( 'member_loop_start' );
	}
}

/**
 * Rewind the members and reset member index.
 */
function bp_rewind_members() {
	global $members_template;

	return $members_template->rewind_members();
}

/**
 * Initialize the members loop.
 *
 * Based on the $args passed, bp_has_members() populates the $members_template
 * global, enabling the use of BuddyPress templates and template functions to
 * display a list of members.
 *
 * @global object $members_template {@link BP_Members_Template}
 *
 * @param array $args {
 *     Arguments for limiting the contents of the members loop. Most arguments
 *     are in the same format as {@link BP_User_Query}. However, because
 *     the format of the arguments accepted here differs in a number of ways,
 *     and because bp_has_members() determines some default arguments in a
 *     dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL query
 *     string (eg, 'user_id=4&per_page=3').
 *
 *     @type int $type Sort order. 'active', 'random', 'newest', 'popular',
 *           'online', 'alphabetical'. Default: 'active'.
 *     @type int|bool $page Page of results to display. Default: 1.
 *     @type int|bool $per_page Number of results per page. Default: 20.
 *     @type int|bool $max Maximum number of results to return.
 *           Default: false (unlimited).
 *     @type string $page_arg The string used as a query parameter in
 *           pagination links. Default: 'bpage'.
 *     @type array|int|string|bool $include Limit results by a list of user
 *           IDs. Accepts an array, a single integer, a comma-separated list of
 *           IDs, or false (to disable this limiting). Default: false.
 *	     'active', 'alphabetical', 'newest', or 'random'.
 *     @type array|int|string|bool $exclude Exclude users from results by ID.
 *           Accepts an array, a single integer, a comma-separated list of
 *           IDs, or false (to disable this limiting). Default: false.
 *     @type int $user_id If provided, results are limited to the friends of
 *           the specified user. When on a user's Friends page, defaults to
 *           the ID of the displayed user. Otherwise defaults to 0.
 *     @type string $search_terms Limit results by a search term. Default: null.
 *     @type string $meta_key Limit results by the presence of a usermeta key.
 *           Default: false.
 *     @type mixed $meta_value When used with meta_key, limits results by the
 *           a matching usermeta value. Default: false.
 *     @type bool $populate_extras Whether to fetch optional data, such as
 *           friend counts. Default: true.
 * }
 * @return bool Returns true when blogs are found, otherwise false.
 */
function bp_has_members( $args = '' ) {
	global $members_template;

	// Default user ID
	$user_id = 0;

	// User filtering
	if ( bp_is_user_friends() && ! bp_is_user_friend_requests() ) {
		$user_id = bp_displayed_user_id();
	}

	// type: active ( default ) | random | newest | popular | online | alphabetical
	$r = bp_parse_args( $args, array(
		'type'            => 'active',
		'page'            => 1,
		'per_page'        => 20,
		'max'             => false,

		'page_arg'        => 'upage',  // See https://buddypress.trac.wordpress.org/ticket/3679

		'include'         => false,    // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users
		'exclude'         => false,    // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users

		'user_id'         => $user_id, // Pass a user_id to only show friends of this user
		'search_terms'    => null,     // Pass search_terms to filter users by their profile data

		'meta_key'        => false,	   // Only return users with this usermeta
		'meta_value'	  => false,	   // Only return users where the usermeta value matches. Requires meta_key

		'populate_extras' => true      // Fetch usermeta? Friend count, last active etc.
	), 'has_members' );

	// Pass a filter if ?s= is set.
	if ( is_null( $r['search_terms'] ) ) {
		if ( !empty( $_REQUEST['s'] ) ) {
			$r['search_terms'] = $_REQUEST['s'];
		} else {
			$r['search_terms'] = false;
		}
	}

	// Set per_page to max if max is larger than per_page
	if ( !empty( $r['max'] ) && ( $r['per_page'] > $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	// Query for members and populate $members_template global
	$members_template = new BP_Core_Members_Template(
		$r['type'],
		$r['page'],
		$r['per_page'],
		$r['max'],
		$r['user_id'],
		$r['search_terms'],
		$r['include'],
		$r['populate_extras'],
		$r['exclude'],
		$r['meta_key'],
		$r['meta_value'],
		$r['page_arg']
	);

	return apply_filters( 'bp_has_members', $members_template->has_members(), $members_template );
}

/**
 * Set up the current member inside the loop.
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
 * @return bool
 */
function bp_members() {
	global $members_template;
	return $members_template->members();
}

/**
 * Output the members pagination count.
 */
function bp_members_pagination_count() {
	echo bp_get_members_pagination_count();
}
	/**
	 * Generate the members pagination count.
	 *
	 * @return string
	 */
	function bp_get_members_pagination_count() {
		global $members_template;

		if ( empty( $members_template->type ) )
			$members_template->type = '';

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 'active' == $members_template->type )
			$pag = sprintf( _n( 'Viewing 1 active member', 'Viewing %1$s - %2$s of %3$s active members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
		else if ( 'popular' == $members_template->type )
			$pag = sprintf( _n( 'Viewing 1 member with friends', 'Viewing %1$s - %2$s of %3$s members with friends', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
		else if ( 'online' == $members_template->type )
			$pag = sprintf( _n( 'Viewing 1 online member', 'Viewing %1$s - %2$s of %3$s online members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );
		else
			$pag = sprintf( _n( 'Viewing 1 member', 'Viewing %1$s - %2$s of %3$s members', $members_template->total_member_count, 'buddypress' ), $from_num, $to_num, $total );

		return apply_filters( 'bp_members_pagination_count', $pag );
	}

/**
 * Output the members pagination links.
 */
function bp_members_pagination_links() {
	echo bp_get_members_pagination_links();
}
	/**
	 * Fetch the members pagination links.
	 *
	 * @return string
	 */
	function bp_get_members_pagination_links() {
		global $members_template;

		return apply_filters( 'bp_get_members_pagination_links', $members_template->pag_links );
	}

/**
 * Output the ID of the current member in the loop.
 *
 * @uses bp_get_member_user_id()
 */
function bp_member_user_id() {
	echo bp_get_member_user_id();
}
	/**
	 * Get the ID of the current member in the loop.
	 *
	 * @return string Member ID.
	 */
	function bp_get_member_user_id() {
		global $members_template;
		$member_id = isset( $members_template->member->id ) ? (int) $members_template->member->id : false;
		return apply_filters( 'bp_get_member_user_id', $member_id );
	}

/**
 * Output the row class of the current member in the loop.
 *
 * @since BuddyPress (1.7.0)
 */
function bp_member_class() {
	echo bp_get_member_class();
}
	/**
	 * Return the row class of the current member in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @return string Row class of the member.
	 */
	function bp_get_member_class() {
		global $members_template;

		$classes      = array();
		$current_time = bp_core_current_time();
		$pos_in_loop  = (int) $members_template->current_member;

		// If we've only one group in the loop, don't both with odd and even.
		if ( $members_template->member_count > 1 )
			$classes[] = ( $pos_in_loop % 2 ) ? 'even' : 'odd';
		else
			$classes[] = 'bp-single-member';

		// Has the user been active recently?
		if ( ! empty( $members_template->member->last_activity ) ) {
			if ( strtotime( $current_time ) <= strtotime( '+5 minutes', strtotime( $members_template->member->last_activity ) ) )
				$classes[] = 'is-online';
		}

		$classes = apply_filters( 'bp_get_member_class', $classes );
		$classes = array_merge( $classes, array() );
		$retval  = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output nicename of current member in the loop.
 */
function bp_member_user_nicename() {
	echo bp_get_member_user_nicename();
}
	/**
	 * Get the nicename of the current member in the loop.
	 *
	 * @return string Members nicename.
	 */
	function bp_get_member_user_nicename() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_nicename', $members_template->member->user_nicename );
	}

/**
 * Output login for current member in the loop.
 */
function bp_member_user_login() {
	echo bp_get_member_user_login();
}
	/**
	 * Get the login of the current member in the loop.
	 *
	 * @return string Member's login.
	 */
	function bp_get_member_user_login() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_login', $members_template->member->user_login );
	}

/**
 * Output the email address for the current member in the loop.
 */
function bp_member_user_email() {
	echo bp_get_member_user_email();
}
	/**
	 * Get the email address of the current member in the loop.
	 *
	 * @return string Member's email address.
	 */
	function bp_get_member_user_email() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_email', $members_template->member->user_email );
	}

/**
 * Check whether the current member in the loop is the logged-in user.
 *
 * @return bool
 */
function bp_member_is_loggedin_user() {
	global $members_template;
	return apply_filters( 'bp_member_is_loggedin_user', bp_loggedin_user_id() == $members_template->member->id ? true : false );
}

/**
 * Output a member's avatar.
 *
 * @see bp_get_member_avatar() for description of arguments.
 *
 * @param array $args See {@link bp_get_member_avatar()}.
 */
function bp_member_avatar( $args = '' ) {
	echo apply_filters( 'bp_member_avatar', bp_get_member_avatar( $args ) );
}
	/**
	 * Get a member's avatar.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default: 'Profile picture of [user name]'.
	 *     @type string $class Default: 'avatar'.
	 *     @type string $type Default: 'thumb'.
	 *     @type int|bool $width Default: false.
	 *     @type int|bool $height Default: false.
	 *     @type bool $id Currently unused.
	 *     @type bool $no_grav Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_member_avatar( $args = '' ) {
		global $members_template;

		$fullname = !empty( $members_template->member->fullname ) ? $members_template->member->fullname : $members_template->member->display_name;

		$defaults = array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => sprintf( __( 'Profile picture of %s', 'buddypress' ), $fullname )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_member_avatar', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->id, 'type' => $type, 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $members_template->member->user_email ) ) );
	}

/**
 * Output the permalink for the current member in the loop.
 */
function bp_member_permalink() {
	echo bp_get_member_permalink();
}
	/**
	 * Get the permalink for the current member in the loop.
	 *
	 * @return string
	 */
	function bp_get_member_permalink() {
		global $members_template;

		return apply_filters( 'bp_get_member_permalink', bp_core_get_user_domain( $members_template->member->id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}

	/**
	 * Alias of {@link bp_member_permalink()}.
	 */
	function bp_member_link() { echo bp_get_member_permalink(); }

	/**
	 * Alias of {@link bp_get_member_permalink()}.
	 */
	function bp_get_member_link() { return bp_get_member_permalink(); }

/**
 * Output display name of current member in the loop.
 */
function bp_member_name() {
	echo apply_filters( 'bp_member_name', bp_get_member_name() );
}
	/**
	 * Get the display name of the current member in the loop.
	 *
	 * Full name is, by default, pulled from xprofile's Full Name field.
	 * When this field is empty, we try to get an alternative name from the
	 * WP users table, in the following order of preference: display_name,
	 * user_nicename, user_login.
	 *
	 * @return string The user's fullname for display.
	 */
	function bp_get_member_name() {
		global $members_template;

		// Generally, this only fires when xprofile is disabled
		if ( empty( $members_template->member->fullname ) ) {
			// Our order of preference for alternative fullnames
			$name_stack = array(
				'display_name',
				'user_nicename',
				'user_login'
			);

			foreach ( $name_stack as $source ) {
				if ( !empty( $members_template->member->{$source} ) ) {
					// When a value is found, set it as fullname and be done
					// with it
					$members_template->member->fullname = $members_template->member->{$source};
					break;
				}
			}
		}

		return apply_filters( 'bp_get_member_name', $members_template->member->fullname );
	}
	add_filter( 'bp_get_member_name', 'wp_filter_kses' );
	add_filter( 'bp_get_member_name', 'stripslashes'   );
	add_filter( 'bp_get_member_name', 'strip_tags'     );
	add_filter( 'bp_get_member_name', 'esc_html'       );

/**
 * Output the current member's last active time.
 *
 * @param array $args See {@link bp_get_member_last_active()}.
 */
function bp_member_last_active( $args = array() ) {
	echo bp_get_member_last_active( $args );
}
	/**
	 * Return the current member's last active time.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type mixed $active_format If true, formatted "active 5 minutes
	 *           ago". If false, formatted "5 minutes ago". If string, should
	 *           be sprintf'able like 'last seen %s ago'.
	 * }
	 * @return string
	 */
	function bp_get_member_last_active( $args = array() ) {
		global $members_template;

		// Parse the activity format
		$r = bp_parse_args( $args, array(
			'active_format' => true
		) );

		// Backwards compatibilty for anyone forcing a 'true' active_format
		if ( true === $r['active_format'] ) {
			$r['active_format'] = __( 'active %s', 'buddypress' );
		}

		// Member has logged in at least one time
		if ( isset( $members_template->member->last_activity ) ) {

			// Backwards compatibility for pre 1.5 'ago' strings
			$last_activity = ! empty( $r['active_format'] )
				? bp_core_get_last_activity( $members_template->member->last_activity, $r['active_format'] )
				: bp_core_time_since( $members_template->member->last_activity );

		// Member has never logged in or been active
		} else {
			$last_activity = __( 'Never active', 'buddypress' );
		}

		// Filter and return
		return apply_filters( 'bp_member_last_active', $last_activity, $r );
	}

/**
 * Output the latest update of the current member in the loop.
 */
function bp_member_latest_update( $args = '' ) {
	echo bp_get_member_latest_update( $args );
}
	/**
	 * Get the latest update from the current member in the loop.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type int $length Truncation length. Default: 225.
	 *     @type bool $view_link Whether to provide a 'View' link for
	 *           truncated entries. Default: false.
	 * }
	 * @return string
	 */
	function bp_get_member_latest_update( $args = '' ) {
		global $members_template;

		$defaults = array(
			'length'    => 225,
			'view_link' => true
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		if ( !bp_is_active( 'activity' ) || empty( $members_template->member->latest_update ) || !$update = maybe_unserialize( $members_template->member->latest_update ) )
			return false;

		$update_content = apply_filters( 'bp_get_activity_latest_update_excerpt', trim( strip_tags( bp_create_excerpt( $update['content'], $length ) ) ) );

		$update_content = sprintf( _x( '- &quot;%s&quot;', 'member latest update in member directory', 'buddypress' ), $update_content );

		// If $view_link is true and the text returned by bp_create_excerpt() is different from the original text (ie it's
		// been truncated), add the "View" link.
		if ( $view_link && ( $update_content != $update['content'] ) ) {
			$view = __( 'View', 'buddypress' );

			$update_content .= '<span class="activity-read-more"><a href="' . bp_activity_get_permalink( $update['id'] ) . '" rel="nofollow">' . $view . '</a></span>';
		}

		return apply_filters( 'bp_get_member_latest_update', $update_content );
	}

/**
 * Output a piece of user profile data.
 *
 * @see bp_get_member_profile_data() for a description of params.
 *
 * @param array $args See {@link bp_get_member_profile_data()}.
 */
function bp_member_profile_data( $args = '' ) {
	echo bp_get_member_profile_data( $args );
}
	/**
	 * Get a piece of user profile data.
	 *
	 * When used in a bp_has_members() loop, this function will attempt
	 * to fetch profile data cached in the template global. It is also safe
	 * to use outside of the loop.
	 *
	 * @param array $args {
	 *     Array of config paramaters.
	 *     @type string $field Name of the profile field.
	 *     @type int $user_id ID of the user whose data is being fetched.
	 *           Defaults to the current member in the loop, or if not
	 *           present, to the currently displayed user.
	 * }
	 * @return string|bool Profile data if found, otherwise false.
	 */
	function bp_get_member_profile_data( $args = '' ) {
		global $members_template;

		if ( ! bp_is_active( 'xprofile' ) ) {
			return false;
		}

		// Declare local variables
		$data = false;

		// Guess at default $user_id
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

		$r = wp_parse_args( $args, $defaults );

		// If we're in a members loop, get the data from the global
		if ( ! empty( $members_template->member->profile_data ) ) {
			$profile_data = $members_template->member->profile_data;
		}

		// Otherwise query for the data
		if ( empty( $profile_data ) && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' ) ) {
			$profile_data = BP_XProfile_ProfileData::get_all_for_user( $r['user_id'] );
		}

		// If we're in the members loop, but the profile data has not
		// been loaded into the global, cache it there for later use
		if ( ! empty( $members_template->member ) && empty( $members_template->member->profile_data ) ) {
			$members_template->member->profile_data = $profile_data;
		}

		// Get the data for the specific field requested
		if ( ! empty( $profile_data ) && ! empty( $profile_data[ $r['field'] ]['field_type'] ) && ! empty( $profile_data[ $r['field'] ]['field_data'] ) ) {
			$data = xprofile_format_profile_field( $profile_data[ $r['field'] ]['field_type'], $profile_data[ $r['field'] ]['field_data'] );
		}

		return apply_filters( 'bp_get_member_profile_data', $data );
	}

/**
 * Output the 'registered [x days ago]' string for the current member.
 */
function bp_member_registered() {
	echo bp_get_member_registered();
}
	/**
	 * Get the 'registered [x days ago]' string for the current member.
	 *
	 * @return string
	 */
	function bp_get_member_registered() {
		global $members_template;

		$registered = esc_attr( bp_core_get_last_activity( $members_template->member->user_registered, _x( 'registered %s', 'Records the timestamp that the user registered into the activy stream', 'buddypress' ) ) );

		return apply_filters( 'bp_member_registered', $registered );
	}

/**
 * Output a random piece of profile data for the current member in the loop.
 */
function bp_member_random_profile_data() {
	global $members_template;

	if ( bp_is_active( 'xprofile' ) ) { ?>
		<?php $random_data = xprofile_get_random_profile_data( $members_template->member->id, true ); ?>
			<strong><?php echo wp_filter_kses( $random_data[0]->name ) ?></strong>
			<?php echo wp_filter_kses( $random_data[0]->value ) ?>
	<?php }
}

/**
 * Output hidden input for preserving member search params on form submit.
 */
function bp_member_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ) . '" name="search_terms" />';

	if ( isset( $_REQUEST['letter'] ) )
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';

	if ( isset( $_REQUEST['members_search'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['members_search'] ) . '" name="search_terms" />';
}

/**
 * Output the Members directory search form.
 */
function bp_directory_members_search_form() {

	$default_search_value = bp_get_search_default_text( 'members' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;

	$search_form_html = '<form action="" method="get" id="search-members-form">
		<label><input type="text" name="s" id="members_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="' . __( 'Search', 'buddypress' ) . '" />
	</form>';

	echo apply_filters( 'bp_directory_members_search_form', $search_form_html );
}

/**
 * Output the total member count.
 */
function bp_total_site_member_count() {
	echo bp_get_total_site_member_count();
}
	/**
	 * Get the total site member count.
	 *
	 * @return int
	 */
	function bp_get_total_site_member_count() {
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
 * @todo Move to a back-compat file?
 * @deprecated Does not seem to be called anywhere in BP core.
 */
function bp_get_loggedin_user_nav() {
	global $bp;

	// Loop through each navigation item
	foreach( (array) $bp->bp_nav as $nav_item ) {

		$selected = '';

		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( !bp_is_directory() && !empty( $bp->active_components[bp_current_component()] ) && $bp->active_components[bp_current_component()] == $nav_item['css_id'] ) {
			$selected = ' class="current selected"';
		}

		// If we are viewing another person (current_userid does not equal
		// loggedin_user->id then check to see if the two users are friends.
		// if they are, add a highlight CSS class to the friends nav item
		// if it exists.
		if ( !bp_is_my_profile() && bp_displayed_user_id() ) {
			$selected = '';

			if ( bp_is_active( 'friends' ) ) {
				if ( $nav_item['css_id'] == $bp->friends->id ) {
					if ( friends_check_friendship( bp_loggedin_user_id(), bp_displayed_user_id() ) ) {
						$selected = ' class="current selected"';
					}
				}
			}
		}

		// echo out the final list item
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item['css_id'], array( '<li id="li-nav-' . $nav_item['css_id'] . '" ' . $selected . '><a id="my-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>', &$nav_item ) );
	}

	// Always add a log out list item to the end of the navigation
	$logout_link = '<li><a id="wp-logout" href="' .  wp_logout_url( bp_get_root_domain() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';

	echo apply_filters( 'bp_logout_nav_link', $logout_link );
}

/**
 * Render the navigation markup for the displayed user.
 */
function bp_get_displayed_user_nav() {
	global $bp;

	foreach ( (array) $bp->bp_nav as $user_nav_item ) {
		if ( empty( $user_nav_item['show_for_displayed_user'] ) && !bp_is_my_profile() )
			continue;

		$selected = '';
		if ( bp_is_current_component( $user_nav_item['slug'] ) ) {
			$selected = ' class="current selected"';
		}

		if ( bp_loggedin_user_domain() ) {
			$link = str_replace( bp_loggedin_user_domain(), bp_displayed_user_domain(), $user_nav_item['link'] );
		} else {
			$link = trailingslashit( bp_displayed_user_domain() . $user_nav_item['link'] );
		}

		echo apply_filters_ref_array( 'bp_get_displayed_user_nav_' . $user_nav_item['css_id'], array( '<li id="' . $user_nav_item['css_id'] . '-personal-li" ' . $selected . '><a id="user-' . $user_nav_item['css_id'] . '" href="' . $link . '">' . $user_nav_item['name'] . '</a></li>', &$user_nav_item ) );
	}
}

/** Avatars *******************************************************************/

/**
 * Output the logged-in user's avatar.
 *
 * @see bp_get_loggedin_user_avatar() for a description of params.
 */
function bp_loggedin_user_avatar( $args = '' ) {
	echo bp_get_loggedin_user_avatar( $args );
}
	/**
	 * Get the logged-in user's avatar.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default: 'Profile picture of [user name]'.
	 *     @type bool $html Default: true.
	 *     @type string $type Default: 'thumb'.
	 *     @type int|bool $width Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_loggedin_user_avatar( $args = '' ) {

		$defaults = array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'html'   => true,
			'alt'    => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_loggedin_user_fullname() )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_loggedin_user_avatar', bp_core_fetch_avatar( array( 'item_id' => bp_loggedin_user_id(), 'type' => $type, 'width' => $width, 'height' => $height, 'html' => $html, 'alt' => $alt ) ) );
	}

/**
 * Output the displayed user's avatar.
 *
 * @see bp_get_displayed_user_avatar() for a description of params.
 */
function bp_displayed_user_avatar( $args = '' ) {
	echo bp_get_displayed_user_avatar( $args );
}
	/**
	 * Get the displayed user's avatar.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default: 'Profile picture of [user name]'.
	 *     @type bool $html Default: true.
	 *     @type string $type Default: 'thumb'.
	 *     @type int|bool $width Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_displayed_user_avatar( $args = '' ) {

		$defaults = array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'html'   => true,
			'alt'    => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_displayed_user_avatar', bp_core_fetch_avatar( array( 'item_id' => bp_displayed_user_id(), 'type' => $type, 'width' => $width, 'height' => $height, 'html' => $html, 'alt' => $alt ) ) );
	}

/**
 * Output the email address of the displayed user.
 */
function bp_displayed_user_email() {
	echo bp_get_displayed_user_email();
}
	/**
	 * Get the email address of the displayed user.
	 *
	 * @return string
	 */
	function bp_get_displayed_user_email() {
		global $bp;

		// If displayed user exists, return email address
		if ( isset( $bp->displayed_user->userdata->user_email ) )
			$retval = $bp->displayed_user->userdata->user_email;
		else
			$retval = '';

		return apply_filters( 'bp_get_displayed_user_email', esc_attr( $retval ) );
	}

/**
 * Output the "active [x days ago]" string for a user.
 *
 * @see bp_get_last_activity() for a description of parameters.
 *
 * @param int $user_id See {@link bp_get_last_activity()}.
 */
function bp_last_activity( $user_id = 0 ) {
	echo apply_filters( 'bp_last_activity', bp_get_last_activity( $user_id ) );
}
	/**
	 * Get the "active [x days ago]" string for a user.
	 *
	 * @param int $user_id ID of the user. Default: displayed user ID.
	 * @return string
	 */
	function bp_get_last_activity( $user_id = 0 ) {

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$last_activity = bp_core_get_last_activity( bp_get_user_last_activity( $user_id ), __('active %s', 'buddypress') );

		return apply_filters( 'bp_get_last_activity', $last_activity );
	}

/**
 * Output the calculated first name of the displayed or logged-in user.
 */
function bp_user_firstname() {
	echo bp_get_user_firstname();
}
	/**
	 * Output the first name of a user.
	 *
	 * Simply takes all the characters before the first space in a name.
	 *
	 * @param string $name Full name to use when generating first name.
	 *        Defaults to displayed user's first name, or to logged-in
	 *        user's first name if it's unavailable.
	 * @return string
	 */
	function bp_get_user_firstname( $name = false ) {

		// Try to get displayed user
		if ( empty( $name ) )
			$name = bp_get_displayed_user_fullname();

		// Fall back on logged in user
		if ( empty( $name ) )
			$name = bp_get_loggedin_user_fullname();

		$fullname = (array) explode( ' ', $name );

		return apply_filters( 'bp_get_user_firstname', $fullname[0], $fullname );
	}

/**
 * Output the link for the logged-in user's profile.
 */
function bp_loggedin_user_link() {
	echo bp_get_loggedin_user_link();
}
	/**
	 * Get the link for the logged-in user's profile.
	 *
	 * @return string
	 */
	function bp_get_loggedin_user_link() {
		return apply_filters( 'bp_get_loggedin_user_link', bp_loggedin_user_domain() );
	}

/**
 * Output the link for the displayed user's profile.
 */
function bp_displayed_user_link() {
	echo bp_get_displayed_user_link();
}
	/**
	 * Get the link for the displayed user's profile.
	 *
	 * @return string
	 */
	function bp_get_displayed_user_link() {
		return apply_filters( 'bp_get_displayed_user_link', bp_displayed_user_domain() );
	}

	/**
	 * Alias of {@link bp_displayed_user_domain()}.
	 *
	 * @deprecated
	 */
	function bp_user_link() { bp_displayed_user_domain(); }

/**
 * Alias of {@link bp_displayed_user_id()}.
 */
function bp_current_user_id() { return bp_displayed_user_id(); }

/**
 * Generate the link for the displayed user's profile.
 *
 * @return string
 */
function bp_displayed_user_domain() {
	global $bp;
	return apply_filters( 'bp_displayed_user_domain', isset( $bp->displayed_user->domain ) ? $bp->displayed_user->domain : '' );
}

/**
 * Generate the link for the logged-in user's profile.
 *
 * @return string
 */
function bp_loggedin_user_domain() {
	global $bp;
	return apply_filters( 'bp_loggedin_user_domain', isset( $bp->loggedin_user->domain ) ? $bp->loggedin_user->domain : '' );
}

/**
 * Output the displayed user's display name.
 */
function bp_displayed_user_fullname() {
	echo bp_get_displayed_user_fullname();
}
	/**
	 * Get the displayed user's display name.
	 *
	 * @return string
	 */
	function bp_get_displayed_user_fullname() {
		global $bp;
		return apply_filters( 'bp_displayed_user_fullname', isset( $bp->displayed_user->fullname ) ? $bp->displayed_user->fullname : '' );
	}

	/**
	 * Alias of {@link bp_get_displayed_user_fullname()}.
	 */
	function bp_user_fullname() { echo bp_get_displayed_user_fullname(); }


/**
 * Output the logged-in user's display name.
 */
function bp_loggedin_user_fullname() {
	echo bp_get_loggedin_user_fullname();
}
	/**
	 * Get the logged-in user's display name.
	 *
	 * @return string
	 */
	function bp_get_loggedin_user_fullname() {
		global $bp;
		return apply_filters( 'bp_get_loggedin_user_fullname', isset( $bp->loggedin_user->fullname ) ? $bp->loggedin_user->fullname : '' );
	}

/**
 * Output the username of the displayed user.
 */
function bp_displayed_user_username() {
	echo bp_get_displayed_user_username();
}
	/**
	 * Get the username of the displayed user.
	 *
	 * @return string
	 */
	function bp_get_displayed_user_username() {
		global $bp;

		if ( bp_displayed_user_id() ) {
			$username = bp_core_get_username( bp_displayed_user_id(), $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login );
		} else {
			$username = '';
		}

		return apply_filters( 'bp_get_displayed_user_username', $username );
	}

/**
 * Output the username of the logged-in user.
 */
function bp_loggedin_user_username() {
	echo bp_get_loggedin_user_username();
}
	/**
	 * Get the username of the logged-in user.
	 *
	 * @return string
	 */
	function bp_get_loggedin_user_username() {
		global $bp;

		if ( bp_loggedin_user_id() ) {
			$username = bp_core_get_username( bp_loggedin_user_id(), $bp->loggedin_user->userdata->user_nicename, $bp->loggedin_user->userdata->user_login );
		} else {
			$username = '';
		}

		return apply_filters( 'bp_get_loggedin_user_username', $username );
	}

/** Signup Form ***************************************************************/

/**
 * Do we have a working custom sign up page?
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_signup_slug() To make sure there is a slug assigned to the page.
 * @uses bp_locate_template() To make sure a template exists to provide output.
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_signup_page() {
	static $has_page = false;

	if ( empty( $has_page ) )
		$has_page = bp_get_signup_slug() && bp_locate_template( array( 'registration/register.php', 'members/register.php', 'register.php' ), false );

	return (bool) $has_page;
}

/**
 * Output the URL to the signup page.
 */
function bp_signup_page() {
	echo bp_get_signup_page();
}
	/**
	 * Get the URL to the signup page.
	 *
	 * @return string
	 */
	function bp_get_signup_page() {
		if ( bp_has_custom_signup_page() ) {
			$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_signup_slug() );
		} else {
			$page = bp_get_root_domain() . '/wp-signup.php';
		}

		return apply_filters( 'bp_get_signup_page', $page );
	}

/**
 * Do we have a working custom activation page?
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_activate_slug() To make sure there is a slug assigned to the page.
 * @uses bp_locate_template() To make sure a template exists to provide output.
 * @return boolean True if page and template exist, false if not.
 */
function bp_has_custom_activation_page() {
	static $has_page = false;

	if ( empty( $has_page ) )
		$has_page = bp_get_activate_slug() && bp_locate_template( array( 'registration/activate.php', 'members/activate.php', 'activate.php' ), false );

	return (bool) $has_page;
}

/**
 * Output the URL of the actvitation page.
 */
function bp_activation_page() {
	echo bp_get_activation_page();
}
	/**
	 * Get the URL of the activation page.
	 *
	 * @return string
	 */
	function bp_get_activation_page() {
		if ( bp_has_custom_activation_page() ) {
			$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_activate_slug() );
		} else {
			$page = trailingslashit( bp_get_root_domain() ) . 'wp-activate.php';
		}

		return apply_filters( 'bp_get_activation_page', $page );
	}

/**
 * Output the username submitted during signup.
 */
function bp_signup_username_value() {
	echo bp_get_signup_username_value();
}
	/**
	 * Get the username submitted during signup.
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_username_value() {
		$value = '';
		if ( isset( $_POST['signup_username'] ) )
			$value = $_POST['signup_username'];

		return apply_filters( 'bp_get_signup_username_value', $value );
	}

/**
 * Output the user email address submitted during signup.
 */
function bp_signup_email_value() {
	echo bp_get_signup_email_value();
}
	/**
	 * Get the email address submitted during signup.
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_email_value() {
		$value = '';
		if ( isset( $_POST['signup_email'] ) )
			$value = $_POST['signup_email'];

		return apply_filters( 'bp_get_signup_email_value', $value );
	}

/**
 * Output the 'signup_with_blog' value submitted during signup.
 */
function bp_signup_with_blog_value() {
	echo bp_get_signup_with_blog_value();
}
	/**
	 * Get the 'signup_with_blog' value submitted during signup.
	 *
	 * @return string
	 */
	function bp_get_signup_with_blog_value() {
		$value = '';
		if ( isset( $_POST['signup_with_blog'] ) )
			$value = $_POST['signup_with_blog'];

		return apply_filters( 'bp_get_signup_with_blog_value', $value );
	}

/**
 * Output the 'signup_blog_url' value submitted at signup.
 */
function bp_signup_blog_url_value() {
	echo bp_get_signup_blog_url_value();
}
	/**
	 * Get the 'signup_blog_url' value submitted at signup.
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_url_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_url'] ) )
			$value = $_POST['signup_blog_url'];

		return apply_filters( 'bp_get_signup_blog_url_value', $value );
	}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since BuddyPress (2.1.0)
 */
function bp_signup_subdomain_base() {
	echo bp_signup_get_subdomain_base();
}
	/**
	 * Return the base URL for subdomain installations of WordPress Multisite.
	 *
	 * Replaces bp_blogs_get_subdomain_base()
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
	 */
	function bp_signup_get_subdomain_base() {
		global $current_site;

		// In case plugins are still using this filter
		$subdomain_base = apply_filters( 'bp_blogs_subdomain_base', preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path );

		return apply_filters( 'bp_signup_subdomain_base', $subdomain_base );
	}

/**
 * Output the 'signup_blog_titl' value submitted at signup.
 */
function bp_signup_blog_title_value() {
	echo bp_get_signup_blog_title_value();
}
	/**
	 * Get the 'signup_blog_title' value submitted at signup.
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_title_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_title'] ) )
			$value = $_POST['signup_blog_title'];

		return apply_filters( 'bp_get_signup_blog_title_value', $value );
	}

/**
 * Output the 'signup_blog_privacy' value submitted at signup.
 */
function bp_signup_blog_privacy_value() {
	echo bp_get_signup_blog_privacy_value();
}
	/**
	 * Get the 'signup_blog_privacy' value submitted at signup.
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
	function bp_get_signup_blog_privacy_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_privacy'] ) )
			$value = $_POST['signup_blog_privacy'];

		return apply_filters( 'bp_get_signup_blog_privacy_value', $value );
	}

/**
 * Output the avatar dir used during signup.
 */
function bp_signup_avatar_dir_value() {
	echo bp_get_signup_avatar_dir_value();
}
	/**
	 * Get the avatar dir used during signup.
	 *
	 * @return string
	 */
	function bp_get_signup_avatar_dir_value() {
		global $bp;

		// Check if signup_avatar_dir is passed
		if ( !empty( $_POST['signup_avatar_dir'] ) )
			$signup_avatar_dir = $_POST['signup_avatar_dir'];

		// If not, check if global is set
		elseif ( !empty( $bp->signup->avatar_dir ) )
			$signup_avatar_dir = $bp->signup->avatar_dir;

		// If not, set false
		else
			$signup_avatar_dir = false;

		return apply_filters( 'bp_get_signup_avatar_dir_value', $bp->signup->avatar_dir );
	}

/**
 * Output the current signup step.
 */
function bp_current_signup_step() {
	echo bp_get_current_signup_step();
}
	/**
	 * Get the current signup step.
	 *
	 * @return string
	 */
	function bp_get_current_signup_step() {
		global $bp;

		return $bp->signup->step;
	}

/**
 * Output the user avatar during signup.
 *
 * @see bp_get_signup_avatar() for description of arguments.
 *
 * @param array $args See {@link bp_get_signup_avatar(}.
 */
function bp_signup_avatar( $args = '' ) {
	echo bp_get_signup_avatar( $args );
}
	/**
	 * Get the user avatar during signup.
	 *
	 * @see bp_core_fetch_avatar() for description of arguments.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type int $size Height/weight in pixels. Default: value of
	 *           bp_core_avatar_full_width().
	 *     @type string $class CSS class. Default: 'avatar'.
	 *     @type string $alt HTML 'alt' attribute. Default: 'Your Avatar'.
	 * }
	 * @return string
	 */
	function bp_get_signup_avatar( $args = '' ) {
		$bp = buddypress();

		$defaults = array(
			'size' => bp_core_avatar_full_width(),
			'class' => 'avatar',
			'alt' => __( 'Your Profile Photo', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Avatar DIR is found
		if ( $signup_avatar_dir = bp_get_signup_avatar_dir_value() ) {
			$gravatar_img = bp_core_fetch_avatar( array(
				'item_id'    => $signup_avatar_dir,
				'object'     => 'signup',
				'avatar_dir' => 'avatars/signups',
				'type'       => 'full',
				'width'      => $size,
				'height'     => $size,
				'alt'        => $alt,
				'class'      => $class
			) );

		// No avatar DIR was found
		} else {

			// Set default gravatar type
			if ( empty( $bp->grav_default->user ) )
				$default_grav = 'wavatar';
			else if ( 'mystery' == $bp->grav_default->user )
				$default_grav = $bp->plugin_url . 'bp-core/images/mystery-man.jpg';
			else
				$default_grav = $bp->grav_default->user;

			// Create
			$gravatar_url    = apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' );
			$md5_lcase_email = md5( strtolower( bp_get_signup_email_value() ) );
			$gravatar_img    = '<img src="' . $gravatar_url . $md5_lcase_email . '?d=' . $default_grav . '&amp;s=' . $size . '" width="' . $size . '" height="' . $size . '" alt="' . $alt . '" class="' . $class . '" />';
		}

		return apply_filters( 'bp_get_signup_avatar', $gravatar_img, $args );
	}

/**
 * Output whether signup is allowed.
 *
 * @todo Remove this function. Echoing a bool is pointless.
 */
function bp_signup_allowed() {
	echo bp_get_signup_allowed();
}
	/**
	 * Is user signup allowed?
	 *
	 * @return bool
	 */
	function bp_get_signup_allowed() {
		global $bp;

		$signup_allowed = false;

		if ( is_multisite() ) {
			if ( in_array( $bp->site_options['registration'], array( 'all', 'user' ) ) ) {
				$signup_allowed = true;
			}

		} else {
			if ( bp_get_option( 'users_can_register') ) {
				$signup_allowed = true;
			}
		}

		return apply_filters( 'bp_get_signup_allowed', $signup_allowed );
	}

/**
 * Hook member activity feed to <head>.
 *
 * @since BuddyPress (1.5.0)
 */
function bp_members_activity_feed() {
	if ( !bp_is_active( 'activity' ) || !bp_is_user() )
		return; ?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php bp_displayed_user_fullname() ?> | <?php _e( 'Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_member_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_members_activity_feed' );

/**
 * Output a link to a members component subpage.
 *
 * @see bp_get_members_component_link() for description of parameters.
 *
 * @param string $component See {@bp_get_members_component_link()}.
 * @param string $action See {@bp_get_members_component_link()}.
 * @param string $query_args See {@bp_get_members_component_link()}.
 * @param string $nonce See {@bp_get_members_component_link()}.
 */
function bp_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
	echo bp_get_members_component_link( $component, $action, $query_args, $nonce );
}
	/**
	 * Generate a link to a members component subpage.
	 *
	 * @param string $component ID of the component (eg 'friends').
	 * @param string $action Optional. 'action' slug (eg 'invites').
	 * @param array $query_args Optional. Array of URL params to add to the
	 *        URL. See {@link add_query_arg()} for format.
	 * @param array $nonce Optional. If provided, the URL will be passed
	 *        through wp_nonce_url() with $nonce as the action string.
	 * @return string
	 */
	function bp_get_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
		global $bp;

		// Must be displayed user
		if ( !bp_displayed_user_id() )
			return;

		// Append $action to $url if there is no $type
		if ( !empty( $action ) )
			$url = bp_displayed_user_domain() . $bp->{$component}->slug . '/' . $action;
		else
			$url = bp_displayed_user_domain() . $bp->{$component}->slug;

		// Add a slash at the end of our user url
		$url = trailingslashit( $url );

		// Add possible query arg
		if ( !empty( $query_args ) && is_array( $query_args ) )
			$url = add_query_arg( $query_args, $url );

		// To nonce, or not to nonce...
		if ( true === $nonce )
			$url = wp_nonce_url( $url );
		elseif ( is_string( $nonce ) )
			$url = wp_nonce_url( $url, $nonce );

		// Return the url, if there is one
		if ( !empty( $url ) )
			return $url;
	}
