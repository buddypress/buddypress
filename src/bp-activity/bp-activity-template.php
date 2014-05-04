<?php

/**
 * BuddyPress Activity Template Functions.
 *
 * @package BuddyPress
 * @subpackage ActivityTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the activity component slug.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_slug()
 */
function bp_activity_slug() {
	echo bp_get_activity_slug();
}
	/**
	 * Return the activity component slug.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses apply_filters() To call the 'bp_get_activity_slug' hook.
	 *
	 * @return string The activity component slug.
	 */
	function bp_get_activity_slug() {
		return apply_filters( 'bp_get_activity_slug', buddypress()->activity->slug );
	}

/**
 * Output the activity component root slug.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_root_slug()
 */
function bp_activity_root_slug() {
	echo bp_get_activity_root_slug();
}
	/**
	 * Return the activity component root slug.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses apply_filters() To call the 'bp_get_activity_root_slug' hook.
	 *
	 * @return string The activity component root slug.
	 */
	function bp_get_activity_root_slug() {
		return apply_filters( 'bp_get_activity_root_slug', buddypress()->activity->root_slug );
	}

/**
 * Output activity directory permalink.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_directory_permalink()
 */
function bp_activity_directory_permalink() {
	echo bp_get_activity_directory_permalink();
}
	/**
	 * Return activity directory permalink
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses traisingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_directory_permalink' hook.
	 *
	 * @return string Activity directory permalink.
	 */
	function bp_get_activity_directory_permalink() {
		return apply_filters( 'bp_get_activity_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ) );
	}

/**
 * The main activity template loop class.
 *
 * This is responsible for loading a group of activity items and displaying them.
 *
 * @since BuddyPress (1.0)
 */
class BP_Activity_Template {
	var $current_activity = -1;
	var $activity_count;
	var $total_activity_count;
	var $activities;
	var $activity;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;

	var $full_name;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Activity_Activity::get()}.
	 *
	 * @see BP_Activity_Activity::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Activity_Activity::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page' and 'display_comments'
	 *     differ from the originating function, and are described below.
	 *     @type string $page_arg The string used as a query parameter in
	 *           pagination links. Default: 'acpage'.
	 *     @type array|bool $include Pass an array of activity IDs to
	 *           retrieve only those items, or false to noop the 'include'
	 *           parameter. 'include' differs from 'in' in that 'in' forms
	 *           an IN clause that works in conjunction with other filters
	 *           passed to the function, while 'include' is interpreted as
	 *           an exact list of items to retrieve, which skips all other
	 *           filter-related parameters. Default: false.
	 *     @type int|bool $per_page Default: 20.
	 *     @type string|bool $display_comments Default: 'threaded'.
	 * }
	 */
	function __construct( $args ) {
		global $bp;

		// Backward compatibility with old method of passing arguments
		if ( !is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.6', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'page',
				1 => 'per_page',
				2 => 'max',
				3 => 'include',
				4 => 'sort',
				5 => 'filter',
				6 => 'search_terms',
				7 => 'display_comments',
				8 => 'show_hidden',
				9 => 'exclude',
				10 => 'in',
				11 => 'spam',
				12 => 'page_arg'
			);

			$func_args = func_get_args();
			$args = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'page'              => 1,
			'per_page'          => 20,
			'page_arg'          => 'acpage',
			'max'               => false,
			'sort'              => false,
			'include'           => false,
			'exclude'           => false,
			'in'                => false,
			'filter'            => false,
			'search_terms'      => false,
			'meta_query'        => false,
			'display_comments'  => 'threaded',
			'show_hidden'       => false,
			'spam'              => 'ham_only',
			'update_meta_cache' => true,
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_page = isset( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		// Check if blog/forum replies are disabled
		$this->disable_blogforum_replies = isset( $bp->site_options['bp-disable-blogforum-comments'] ) ? $bp->site_options['bp-disable-blogforum-comments'] : false;

		// Get an array of the logged in user's favorite activities
		$this->my_favs = maybe_unserialize( bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_activities', true ) );

		// Fetch specific activity items based on ID's
		if ( !empty( $include ) ) {
			$this->activities = bp_activity_get_specific( array(
				'activity_ids'      => explode( ',', $include ),
				'max'               => $max,
				'page'              => $this->pag_page,
				'per_page'          => $this->pag_num,
				'sort'              => $sort,
				'display_comments'  => $display_comments,
				'show_hidden'       => $show_hidden,
				'spam'              => $spam,
				'update_meta_cache' => $update_meta_cache,
			) );

		// Fetch all activity items
		} else {
			$this->activities = bp_activity_get( array(
				'display_comments'  => $display_comments,
				'max'               => $max,
				'per_page'          => $this->pag_num,
				'page'              => $this->pag_page,
				'sort'              => $sort,
				'search_terms'      => $search_terms,
				'meta_query'        => $meta_query,
				'filter'            => $filter,
				'show_hidden'       => $show_hidden,
				'exclude'           => $exclude,
				'in'                => $in,
				'spam'              => $spam,
				'update_meta_cache' => $update_meta_cache,
			) );
		}

		if ( !$max || $max >= (int) $this->activities['total'] )
			$this->total_activity_count = (int) $this->activities['total'];
		else
			$this->total_activity_count = (int) $max;

		$this->activities = $this->activities['activities'];

		if ( $max ) {
			if ( $max >= count($this->activities) ) {
				$this->activity_count = count( $this->activities );
			} else {
				$this->activity_count = (int) $max;
			}
		} else {
			$this->activity_count = count( $this->activities );
		}

		$this->full_name = bp_get_displayed_user_fullname();

		// Fetch parent content for activity comments so we do not have to query in the loop
		foreach ( (array) $this->activities as $activity ) {
			if ( 'activity_comment' != $activity->type )
				continue;

			$parent_ids[] = $activity->item_id;
		}

		if ( !empty( $parent_ids ) )
			$activity_parents = bp_activity_get_specific( array( 'activity_ids' => $parent_ids ) );

		if ( !empty( $activity_parents['activities'] ) ) {
			foreach( $activity_parents['activities'] as $parent )
				$this->activity_parents[$parent->id] = $parent;

			unset( $activity_parents );
		}

		if ( (int) $this->total_activity_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_activity_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Activity pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Activity pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	/**
	 * Whether there are activity items available in the loop.
	 *
	 * @see bp_has_activities()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_activities() {
		if ( $this->activity_count )
			return true;

		return false;
	}

	/**
	 * Set up the next activity item and iterate index.
	 *
	 * @return object The next activity item to iterate over.
	 */
	function next_activity() {
		$this->current_activity++;
		$this->activity = $this->activities[$this->current_activity];

		return $this->activity;
	}

	/**
	 * Rewind the posts and reset post index.
	 */
	function rewind_activities() {
		$this->current_activity = -1;
		if ( $this->activity_count > 0 ) {
			$this->activity = $this->activities[0];
		}
	}

	/**
	 * Whether there are activity items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_activities()} as part of the while loop
	 * that controls iteration inside the activities loop, eg:
	 *     while ( bp_activities() ) { ...
	 *
	 * @see bp_activities()
	 *
	 * @return bool True if there are more activity items to show,
	 *              otherwise false.
	 */
	function user_activities() {
		if ( $this->current_activity + 1 < $this->activity_count ) {
			return true;
		} elseif ( $this->current_activity + 1 == $this->activity_count ) {
			do_action('activity_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_activities();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current activity item inside the loop.
	 *
	 * Used by {@link bp_the_activity()} to set up the current activity item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current activity item.
	 *
	 * @see bp_the_activity()
	 */
	function the_activity() {

		$this->in_the_loop = true;
		$this->activity    = $this->next_activity();

		if ( is_array( $this->activity ) )
			$this->activity = (object) $this->activity;

		if ( $this->current_activity == 0 ) // loop has just started
			do_action('activity_loop_start');
	}
}

/**
 * Initialize the activity loop.
 *
 * Based on the $args passed, bp_has_activities() populates the
 * $activities_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of activity items.
 *
 * @since BuddyPress (1.0)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @global object $bp BuddyPress global settings.
 * @uses groups_is_user_member()
 * @uses bp_current_action()
 * @uses bp_is_current_action()
 * @uses bp_get_activity_slug()
 * @uses bp_action_variable()
 * @uses wp_parse_args()
 * @uses bp_is_active()
 * @uses friends_get_friend_user_ids()
 * @uses groups_get_user_groups()
 * @uses bp_activity_get_user_favorites()
 * @uses apply_filters() To call the 'bp_has_activities' hook.
 *
 * @param array $args {
 *     Arguments for limiting the contents of the activity loop. Most
 *     arguments are in the same format as {@link BP_Activity_Activity::get()}.
 *     However, because the format of the arguments accepted here differs in
 *     a number of ways, and because bp_has_activities() determines some
 *     default arguments in a dynamic fashion, we list all accepted arguments
 *     here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL query
 *     string (eg, 'user_id=4&display_comments=threaded').
 *
 *     @type int $page Which page of results to fetch. Using page=1 without
 *           per_page will result in no pagination. Default: 1.
 *     @type int|bool $per_page Number of results per page. Default: 20.
 *     @type string $page_arg The string used as a query parameter in
 *           pagination links. Default: 'acpage'.
 *     @type int|bool $max Maximum number of results to return.
 *           Default: false (unlimited).
 *     @type string $sort 'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool $exclude Array of activity IDs to exclude. Default: false.
 *     @type array|bool $in Array of IDs to limit query by (IN). 'in' is
 *           intended to be used in conjunction with other filter parameters.
 *           Default: false.
 *     @type array|bool $include Array of exact activity IDs to query.
 *           Providing an 'include' array will override all other filters
 *           passed in the argument array. When viewing a the permalink page
 *           for a single activity item, this value defaults to the ID of that
 *           item. Otherwise the default is false.
 *     @type array $meta_query Limit by activitymeta by passing an array of
 *           meta_query conditions. See {@link WP_Meta_Query::queries} for a
 *           description of the syntax.
 *     @type string $search_terms Limit results by a search term. Default: false.
 *     @type string|bool $scope Use one of BuddyPress's pre-built filters. In
 *           each case, the term 'current user' refers to the displayed user
 *           when looking at a user profile, and otherwise to the logged-in user.
 *             - 'just-me' retrieves items belonging only to the logged-in user;
 *               this is equivalent to passing a 'user_id' argument
 *             - 'friends' retrieves items belonging to the friends of the
 *               current user
 *             - 'groups' retrieves items associated with the groups to which
 *               the current user belongs
 *             - 'favorites' retrieves the current user's favorited activity
 *               items
 *             - 'mentions' retrieves activity items where the current user has
 *               received an @-mention
 *           The default value of 'scope' is set to one of the above if that
 *           value appears in the appropriate place in the URL; eg, 'scope' will
 *           be 'groups' when visiting http://example.com/members/joe/activity/groups/.
 *           Otherwise defaults to false.
 *     @type int|array|bool $user_id The ID(s) of user(s) whose activity should
 *           be fetched. Pass a single ID or an array of IDs. When viewing a
 *           user profile page (but not that user's activity subpages, ie My
 *           Friends, My Groups, etc), 'user_id' defaults to the ID of the
 *           displayed user. Otherwise the default is false.
 *     @type string|array|bool $object Filters by the `component` column in the
 *           database, which is generally the component ID in the case of
 *           BuddyPress components, or the plugin slug in the case of plugins.
 *           For example, 'groups' will limit results to those that are
 *           associated with the BP Groups component. Accepts a single
 *           component string, or an array of multiple components. Defaults to
 *           'groups' when viewing the page of a single group, the My Groups
 *           activity filter, or the Activity > Groups filter of a user profile.
 *           Otherwise defaults to false.
 *     @type string|array|bool $action Filters by the `type` column in the
 *           database, which is a string categorizing the activity item (eg,
 *           'new_blog_post', 'created_group'). Accepts a single type string,
 *           or an array of multiple types. Defaults to false.
 *     @type int|array|bool $primary_id Filters by the `item_id` column in the
 *           database. The meaning of 'primary_id' differs between components/
 *           types; for example, in the case of 'created_group', 'primary_id'
 *           is the ID of the group. Accepts a single ID, or an array of
 *           multiple IDs. When viewing a single group, defaults to the current
 *           group ID. When viewing a user's Groups stream page, defaults to
 *           the IDs of the user's groups. Otherwise defaults to false.
 *     @type int|array|bool $secondary_id Filters by the `secondary_item_id`
 *           column in the database. The meaning of 'secondary_id' differs
 *           between components/types. Accepts a single ID, or an array of
 *           multiple IDs. Defaults to false.
 *     @type int $offset Return only activity items with an ID greater than or
 *           equal to this one. Note that providing an offset will disable
 *           pagination. Default: false.
 *     @type string|bool $display_comments How to handle activity comments.
 *           Possible values:
 *             - 'threaded' - comments appear in a threaded tree, under their
 *               parent items
 *             - 'stream' - the activity stream is presented in a flat manner,
 *               with comments sorted in chronological order alongside other
 *               activity items
 *             - false - don't fetch activity comments at all
 *           Default: 'threaded'.
 *     @type bool $show_hidden Whether to show items marked hide_sitewide.
 *           Defaults to false, except in the following cases:
 *             - User is viewing his own activity stream
 *             - User is viewing the activity stream of a non-public group of
 *               which he is a member
 *     @type bool $show_hidden Normally defaults to false, except when:
 *             - a user is viewing his own activity stream
 *             - a user is viewing the activity stream of a non-public group of
 *               which he is a member
 *     @type string|bool $spam Spam status. 'ham_only', 'spam_only', or false
 *           to show all activity regardless of spam status. Default: 'ham_only'.
 *     @type bool $populate_extras Whether to pre-fetch the activity metadata
 *           for the queried items. Default: true.
 * }
 * @return bool Returns true when activities are found, otherwise false.
 */
function bp_has_activities( $args = '' ) {
	global $activities_template, $bp;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$user_id     = false;
	$include     = false;
	$exclude     = false;
	$in          = false;
	$show_hidden = false;
	$object      = false;
	$primary_id  = false;

	// User filtering
	if ( bp_displayed_user_id() )
		$user_id = bp_displayed_user_id();

	// Group filtering
	if ( !empty( $bp->groups->current_group ) ) {
		$object = $bp->groups->id;
		$primary_id = $bp->groups->current_group->id;

		if ( ( 'public' != $bp->groups->current_group->status ) && ( groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) || bp_current_user_can( 'bp_moderate' ) ) )
			$show_hidden = true;
	}

	// The default scope should recognize custom slugs
	if ( array_key_exists( bp_current_action(), (array) $bp->loaded_components ) ) {
		$scope = $bp->loaded_components[bp_current_action()];
	}
	else
		$scope = bp_current_action();

	// Support for permalinks on single item pages: /groups/my-group/activity/124/
	if ( bp_is_current_action( bp_get_activity_slug() ) )
		$include = bp_action_variable( 0 );

	// Note: any params used for filtering can be a single value, or multiple values comma separated.
	$defaults = array(
		'display_comments'  => 'threaded',   // false for none, stream/threaded - show comments in the stream or threaded under items
		'include'           => $include,     // pass an activity_id or string of IDs comma-separated
		'exclude'           => $exclude,     // pass an activity_id or string of IDs comma-separated
		'in'                => $in,          // comma-separated list or array of activity IDs among which to search
		'sort'              => 'DESC',       // sort DESC or ASC
		'page'              => 1,            // which page to load
		'per_page'          => 20,           // number of items per page
		'max'               => false,        // max number to return
		'show_hidden'       => $show_hidden, // Show activity items that are hidden site-wide?
		'spam'              => 'ham_only',   // Hide spammed items

		'page_arg'          => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679

		// Scope - pre-built activity filters for a user (friends/groups/favorites/mentions)
		'scope'             => $scope,

		// Filtering
		'user_id'           => $user_id,     // user_id to filter on
		'object'            => $object,      // object to filter on e.g. groups, profile, status, friends
		'action'            => false,        // action to filter on e.g. activity_update, new_forum_post, profile_updated
		'primary_id'        => $primary_id,  // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		'secondary_id'      => false,        // secondary object ID to filter on e.g. a post_id
		'offset'            => false,        // return only items >= this ID
		'since'             => false,        // return only items recorded since this Y-m-d H:i:s date

		'meta_query'        => false,        // filter on activity meta. See WP_Meta_Query for format

		// Searching
		'search_terms'      => false,        // specify terms to search on
		'update_meta_cache' => true,
	);

	$r = bp_parse_args( $args, $defaults, 'has_activities' );
	extract( $r );

	// Translate various values for 'display_comments'
	// This allows disabling comments via ?display_comments=0
	// or =none or =false. Final true is a strict type check. See #5029
	if ( in_array( $display_comments, array( 0, '0', 'none', 'false' ), true ) ) {
		$display_comments = false;
	}

	// Ignore pagination if an offset is passed
	if ( ! empty( $offset ) ) {
		$page = 0;
	}

	if ( empty( $search_terms ) && ! empty( $_REQUEST['s'] ) )
		$search_terms = $_REQUEST['s'];

	// If you have passed a "scope" then this will override any filters you have passed.
	if ( 'just-me' == $scope || 'friends' == $scope || 'groups' == $scope || 'favorites' == $scope || 'mentions' == $scope ) {
		if ( 'just-me' == $scope )
			$display_comments = 'stream';

		// determine which user_id applies
		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();

		// are we displaying user specific activity?
		if ( is_numeric( $user_id ) ) {
			$show_hidden = ( $user_id == bp_loggedin_user_id() && $scope != 'friends' ) ? 1 : 0;

			switch ( $scope ) {
				case 'friends':
					if ( bp_is_active( 'friends' ) )
						$friends = friends_get_friend_user_ids( $user_id );
						if ( empty( $friends ) )
							return false;

						$user_id = implode( ',', (array) $friends );
					break;
				case 'groups':
					if ( bp_is_active( 'groups' ) ) {
						$groups = groups_get_user_groups( $user_id );
						if ( empty( $groups['groups'] ) )
							return false;

						$object = $bp->groups->id;
						$primary_id = implode( ',', (array) $groups['groups'] );

						$user_id = 0;
					}
					break;
				case 'favorites':
					$favs = bp_activity_get_user_favorites( $user_id );
					if ( empty( $favs ) )
						return false;

					$in = implode( ',', (array) $favs );
					$display_comments = true;
					$user_id = 0;
					break;
				case 'mentions':

					// Are mentions disabled?
					if ( ! bp_activity_do_mentions() ) {
						return false;
					}

					// Start search at @ symbol and stop search at closing tag delimiter.
					$search_terms     = '@' . bp_activity_get_user_mentionname( $user_id ) . '<';
					$display_comments = 'stream';
					$user_id = 0;
					break;
			}
		}
	}

	// Do not exceed the maximum per page
	if ( !empty( $max ) && ( (int) $per_page > (int) $max ) )
		$per_page = $max;

	// Support for basic filters in earlier BP versions is disabled by default. To enable, put
	//   add_filter( 'bp_activity_enable_afilter_support', '__return_true' );
	// into bp-custom.php or your theme's functions.php
	if ( isset( $_GET['afilter'] ) && apply_filters( 'bp_activity_enable_afilter_support', false ) )
		$filter = array( 'object' => $_GET['afilter'] );
	else if ( ! empty( $user_id ) || ! empty( $object ) || ! empty( $action ) || ! empty( $primary_id ) || ! empty( $secondary_id ) || ! empty( $offset ) || ! empty( $since ) )
		$filter = array( 'user_id' => $user_id, 'object' => $object, 'action' => $action, 'primary_id' => $primary_id, 'secondary_id' => $secondary_id, 'offset' => $offset, 'since' => $since );
	else
		$filter = false;

	// If specific activity items have been requested, override the $hide_spam argument. This prevents backpat errors with AJAX.
	if ( !empty( $include ) && ( 'ham_only' == $spam ) )
		$spam = 'all';

	$template_args = array(
		'page'              => $page,
		'per_page'          => $per_page,
		'page_arg'          => $page_arg,
		'max'               => $max,
		'sort'              => $sort,
		'include'           => $include,
		'exclude'           => $exclude,
		'in'                => $in,
		'filter'            => $filter,
		'search_terms'      => $search_terms,
		'meta_query'        => $meta_query,
		'display_comments'  => $display_comments,
		'show_hidden'       => $show_hidden,
		'spam'              => $spam,
		'update_meta_cache' => $update_meta_cache,
	);

	$activities_template = new BP_Activity_Template( $template_args );

	return apply_filters( 'bp_has_activities', $activities_template->has_activities(), $activities_template, $template_args );
}

/**
 * Determine if there are still activities left in the loop.
 *
 * @since BuddyPress (1.0)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses BP_Activity_Template::user_activities() {@link BP_Activity_Template::user_activities()}
 *
 * @return bool Returns true when activities are found.
 */
function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

/**
 * Get the current activity object in the loop.
 *
 * @since BuddyPress (1.0)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses BP_Activity_Template::the_activity() {@link BP_Activity_Template::the_activity()}
 *
 * @return object The current activity within the loop.
 */
function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

/**
 * Output the activity pagination count.
 *
 * @since BuddyPress (1.0)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses BP_Activity_Template::the_activity() {@link BP_Activity_Template::the_activity()}
 */
function bp_activity_pagination_count() {
	echo bp_get_activity_pagination_count();
}

	/**
	 * Return the activity pagination count.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_core_number_format()
	 *
	 * @return string The pagination text.
	 */
	function bp_get_activity_pagination_count() {
		global $activities_template;

		$start_num = intval( ( $activities_template->pag_page - 1 ) * $activities_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $activities_template->pag_num - 1 ) > $activities_template->total_activity_count ) ? $activities_template->total_activity_count : $start_num + ( $activities_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $activities_template->total_activity_count );

		return sprintf( _n( 'Viewing item %1$s to %2$s (of %3$s item)', 'Viewing item %1$s to %2$s (of %3$s items)', $total, 'buddypress' ), $from_num, $to_num, $total );
	}

/**
 * Output the activity pagination links.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_pagination_links()
 */
function bp_activity_pagination_links() {
	echo bp_get_activity_pagination_links();
}

	/**
	 * Return the activity pagination links.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_pagination_links' hook.
	 *
	 * @return string The pagination links.
	 */
	function bp_get_activity_pagination_links() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_pagination_links', $activities_template->pag_links );
	}

/**
 * Return true when there are more activity items to be shown than currently appear.
 *
 * @since BuddyPress (1.5)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses apply_filters() To call the 'bp_activity_has_more_items' hook.
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_activity_has_more_items() {
	global $activities_template;

	$remaining_pages = 0;

	if ( ! empty( $activities_template->pag_page ) ) {
		$remaining_pages = floor( ( $activities_template->total_activity_count - 1 ) / ( $activities_template->pag_num * $activities_template->pag_page ) );
	}

	$has_more_items  = (int) $remaining_pages ? true : false;

	return apply_filters( 'bp_activity_has_more_items', $has_more_items );
}

/**
 * Output the activity count.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_count()
 */
function bp_activity_count() {
	echo bp_get_activity_count();
}

	/**
	 * Return the activity count.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_count' hook.
	 *
	 * @return int The activity count.
	 */
	function bp_get_activity_count() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_count', (int) $activities_template->activity_count );
	}

/**
 * Output the number of activities per page.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_per_page()
 */
function bp_activity_per_page() {
	echo bp_get_activity_per_page();
}

	/**
	 * Return the number of activities per page.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_per_page' hook.
	 *
	 * @return int The activities per page.
	 */
	function bp_get_activity_per_page() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_per_page', (int) $activities_template->pag_num );
	}

/**
 * Output the activities title.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activities_title()
 * @todo Deprecate.
 */
function bp_activities_title() {
	echo bp_get_activities_title();
}

	/**
	 * Return the activities title.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global string $bp_activity_title
	 * @uses apply_filters() To call the 'bp_get_activities_title' hook.
	 * @todo Deprecate.
	 *
	 * @return int The activities title.
	 */
	function bp_get_activities_title() {
		global $bp_activity_title;

		return apply_filters( 'bp_get_activities_title', $bp_activity_title );
	}

/**
 * {@internal Missing Description}
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activities_no_activity()
 * @todo Deprecate.
 */
function bp_activities_no_activity() {
	echo bp_get_activities_no_activity();
}

	/**
	 * {@internal Missing Description}
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global string $bp_activity_no_activity
	 * @uses apply_filters() To call the 'bp_get_activities_no_activity' hook
	 * @todo Deprecate.
	 *
	 * @return string
	 */
	function bp_get_activities_no_activity() {
		global $bp_activity_no_activity;

		return apply_filters( 'bp_get_activities_no_activity', $bp_activity_no_activity );
	}

/**
 * Output the activity ID.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_id()
 */
function bp_activity_id() {
	echo bp_get_activity_id();
}

	/**
	 * Return the activity ID.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_id' hook.
	 *
	 * @return int The activity ID.
	 */
	function bp_get_activity_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_id', $activities_template->activity->id );
	}

/**
 * Output the activity item ID.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_item_id()
 */
function bp_activity_item_id() {
	echo bp_get_activity_item_id();
}

	/**
	 * Return the activity item ID.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_item_id' hook.
	 *
	 * @return int The activity item ID.
	 */
	function bp_get_activity_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_item_id', $activities_template->activity->item_id );
	}

/**
 * Output the activity secondary item ID.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_secondary_item_id()
 */
function bp_activity_secondary_item_id() {
	echo bp_get_activity_secondary_item_id();
}

	/**
	 * Return the activity secondary item ID.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_secondary_item_id' hook.
	 *
	 * @return int The activity secondary item ID.
	 */
	function bp_get_activity_secondary_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_secondary_item_id', $activities_template->activity->secondary_item_id );
	}

/**
 * Output the date the activity was recorded.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_date_recorded()
 */
function bp_activity_date_recorded() {
	echo bp_get_activity_date_recorded();
}

	/**
	 * Return the date the activity was recorded.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_date_recorded' hook.
	 *
	 * @return string The date the activity was recorded.
	 */
	function bp_get_activity_date_recorded() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_date_recorded', $activities_template->activity->date_recorded );
	}

/**
 * Output the activity object name.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_object_name()
 */
function bp_activity_object_name() {
	echo bp_get_activity_object_name();
}

	/**
	 * Return the activity object name.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_object_name' hook.
	 *
	 * @return string The activity object name.
	 */
	function bp_get_activity_object_name() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_object_name', $activities_template->activity->component );
	}

/**
 * Output the activity type.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_type()
 */
function bp_activity_type() {
	echo bp_get_activity_type();
}

	/**
	 * Return the activity type.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_type' hook.
	 *
	 * @return string The activity type.
	 */
	function bp_get_activity_type() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_type', $activities_template->activity->type );
	}

	/**
	 * Output the activity action name.
	 *
	 * Just a wrapper for bp_activity_type().
	 *
	 * @since BuddyPress (1.2)
	 * @deprecated BuddyPress (1.5)
	 *
	 * @todo Properly deprecate in favor of bp_activity_type() and
	 *		 remove redundant echo
	 *
	 * @uses bp_activity_type()
	 */
	function bp_activity_action_name() { echo bp_activity_type(); }

	/**
	 * Return the activity type.
	 *
	 * Just a wrapper for bp_get_activity_type().
	 *
	 * @since BuddyPress (1.2)
	 * @deprecated BuddyPress (1.5)
	 *
	 * @todo Properly deprecate in favor of bp_get_activity_type().
	 *
	 * @uses bp_get_activity_type()
	 *
	 * @return string The activity type.
	 */
	function bp_get_activity_action_name() { return bp_get_activity_type(); }

/**
 * Output the activity user ID.
 *
 * @since BuddyPress (1.1)
 *
 * @uses bp_get_activity_user_id()
 */
function bp_activity_user_id() {
	echo bp_get_activity_user_id();
}

	/**
	 * Return the activity user ID.
	 *
	 * @since BuddyPress (1.1)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_user_id' hook.
	 *
	 * @return int The activity user ID.
	 */
	function bp_get_activity_user_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_user_id', $activities_template->activity->user_id );
	}

/**
 * Output the activity user link.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_user_link()
 */
function bp_activity_user_link() {
	echo bp_get_activity_user_link();
}

	/**
	 * Return the activity user link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_core_get_user_domain()
	 * @uses apply_filters() To call the 'bp_get_activity_user_link' hook.
	 *
	 * @return string $link The activity user link.
	 */
	function bp_get_activity_user_link() {
		global $activities_template;

		if ( empty( $activities_template->activity->user_id ) )
			$link = $activities_template->activity->primary_link;
		else
			$link = bp_core_get_user_domain( $activities_template->activity->user_id, $activities_template->activity->user_nicename, $activities_template->activity->user_login );

		return apply_filters( 'bp_get_activity_user_link', $link );
	}

/**
 * Output the avatar of the user that performed the action.
 *
 * @since BuddyPress (1.1)
 *
 * @see bp_get_activity_avatar() for description of arguments.
 * @uses bp_get_activity_avatar()
 *
 * @param array $args See {@link bp_get_activity_avatar()} for description.
 */
function bp_activity_avatar( $args = '' ) {
	echo bp_get_activity_avatar( $args );
}
	/**
	 * Return the avatar of the user that performed the action.
	 *
	 * @since BuddyPress (1.1)
	 *
	 * @see bp_core_fetch_avatar() For a description of the arguments.
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @global object $bp BuddyPress global settings
	 * @uses bp_is_single_activity()
	 * @uses wp_parse_args()
	 * @uses apply_filters() To call the 'bp_get_activity_avatar_object_' . $current_activity_item->component hook
	 * @uses apply_filters() To call the 'bp_get_activity_avatar_item_id' hook
	 * @uses bp_core_fetch_avatar()
	 * @uses apply_filters() To call the 'bp_get_activity_avatar' hook
	 *
	 * @param array $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default: 'Profile picture of [user name]' if
	 *           activity user name is available, otherwise 'Profile picture'.
	 *     @type string $class Default: 'avatar'.
	 *     @type string|bool $email Default: Email of the activity's
	 *           associated user, if available. Otherwise false.
	 *     @type string $type Default: 'full' when viewing a single activity
	 *           permalink page, otherwise 'thumb'.
	 *     @type int|bool $user_id Default: ID of the activity's user.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_activity_avatar( $args = '' ) {
		global $activities_template;

		$bp = buddypress();

		// On activity permalink pages, default to the full-size avatar
		$type_default = bp_is_single_activity() ? 'full' : 'thumb';

		// Within the activity comment loop, the current activity should be set
		// to current_comment. Otherwise, just use activity.
		$current_activity_item = isset( $activities_template->activity->current_comment ) ? $activities_template->activity->current_comment : $activities_template->activity;

		// Activity user display name
		$dn_default  = isset( $current_activity_item->display_name ) ? $current_activity_item->display_name : '';

		// Prepend some descriptive text to alt
		$alt_default = !empty( $dn_default ) ? sprintf( __( 'Profile picture of %s', 'buddypress' ), $dn_default ) : __( 'Profile picture', 'buddypress' );

		$defaults = array(
			'alt'     => $alt_default,
			'class'   => 'avatar',
			'email'   => false,
			'type'    => $type_default,
			'user_id' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !isset( $height ) && !isset( $width ) ) {

			// Backpat
			if ( isset( $bp->avatar->full->height ) || isset( $bp->avatar->thumb->height ) ) {
				$height = ( 'full' == $type ) ? $bp->avatar->full->height : $bp->avatar->thumb->height;
			} else {
				$height = 20;
			}

			// Backpat
			if ( isset( $bp->avatar->full->width ) || isset( $bp->avatar->thumb->width ) ) {
				$width = ( 'full' == $type ) ? $bp->avatar->full->width : $bp->avatar->thumb->width;
			} else {
				$width = 20;
			}
		}

		// Primary activity avatar is always a user, but can be modified via a filter
		$object  = apply_filters( 'bp_get_activity_avatar_object_' . $current_activity_item->component, 'user' );
		$item_id = !empty( $user_id ) ? $user_id : $current_activity_item->user_id;
		$item_id = apply_filters( 'bp_get_activity_avatar_item_id', $item_id );

		// If this is a user object pass the users' email address for Gravatar so we don't have to refetch it.
		if ( 'user' == $object && empty( $user_id ) && empty( $email ) && isset( $current_activity_item->user_email ) )
			$email = $current_activity_item->user_email;

		return apply_filters( 'bp_get_activity_avatar', bp_core_fetch_avatar( array(
			'item_id' => $item_id,
			'object'  => $object,
			'type'    => $type,
			'alt'     => $alt,
			'class'   => $class,
			'width'   => $width,
			'height'  => $height,
			'email'   => $email
		) ) );
	}

/**
 * Output the avatar of the object that action was performed on.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_activity_secondary_avatar() for description of arguments.
 * @uses bp_get_activity_secondary_avatar()
 *
 * @param array $args See {@link bp_get_activity_secondary_avatar} for description.
 */
function bp_activity_secondary_avatar( $args = '' ) {
	echo bp_get_activity_secondary_avatar( $args );
}

	/**
	 * Return the avatar of the object that action was performed on
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @see bp_core_fetch_avatar() for description of arguments.
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses wp_parse_args()
	 * @uses get_blog_option()
	 * @uses apply_filters() To call the 'bp_get_activity_secondary_avatar_object_' . $activities_template->activity->component hook.
	 * @uses apply_filters() To call the 'bp_get_activity_secondary_avatar_item_id' hook.
	 * @uses bp_core_fetch_avatar()
	 * @uses apply_filters() To call the 'bp_get_activity_secondary_avatar' hook.
	 *
	 * @param array $args  {
	 *     For a complete description of arguments, see {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default value varies based on current activity
	 *           item component.
	 *     @type string $type Default: 'full' when viewing a single activity
	 *           permalink page, otherwise 'thumb'.
	 *     @type string $class Default: 'avatar'.
	 *     @type string|bool $email Default: email of the activity's user.
	 *     @type int|bool $user_id Default: ID of the activity's user.
	 * }
	 * @return string The secondary avatar
	 */
	function bp_get_activity_secondary_avatar( $args = '' ) {
		global $activities_template;

		$r = wp_parse_args( $args, array(
			'alt'        => '',
			'type'       => 'thumb',
			'width'      => 20,
			'height'     => 20,
			'class'      => 'avatar',
			'link_class' => '',
			'linked'     => true,
			'email'      => false
		) );
		extract( $r, EXTR_SKIP );

		// Set item_id and object (default to user)
		switch ( $activities_template->activity->component ) {
			case 'groups' :
				$object  = 'group';
				$item_id = $activities_template->activity->item_id;
				$link    = '';
				$name    = '';

				// Only if groups is active
				if ( bp_is_active( 'groups' ) ) {
					$group = groups_get_group( array(
						'group_id'          => $item_id,
						'populate_extras'   => false,
						'update_meta_cache' => false,
					) );
					$link  = bp_get_group_permalink( $group );
					$name  = $group->name;
				}

				if ( empty( $alt ) ) {
					$alt = __( 'Group logo', 'buddypress' );

					if ( ! empty( $name ) ) {
						$alt = sprintf( __( 'Group logo of %s', 'buddypress' ), $name );
					}
				}

				break;
			case 'blogs' :
				$object  = 'blog';
				$item_id = $activities_template->activity->item_id;
				$link    = home_url();

				if ( empty( $alt ) ) {
					$alt = sprintf( __( 'Profile picture of the author of the site %s', 'buddypress' ), get_blog_option( $item_id, 'blogname' ) );
				}

				break;
			case 'friends' :
				$object  = 'user';
				$item_id = $activities_template->activity->secondary_item_id;
				$link    = bp_core_get_userlink( $item_id, false, true );

				if ( empty( $alt ) ) {
					$alt = sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $activities_template->activity->secondary_item_id ) );
				}

				break;
			default :
				$object  = 'user';
				$item_id = $activities_template->activity->user_id;
				$email   = $activities_template->activity->user_email;
				$link    = bp_core_get_userlink( $item_id, false, true );

				if ( empty( $alt ) ) {
					$alt = sprintf( __( 'Profile picture of %s', 'buddypress' ), $activities_template->activity->display_name );
				}

				break;
		}

		// Allow object, item_id, and link to be filtered
		$object  = apply_filters( 'bp_get_activity_secondary_avatar_object_' . $activities_template->activity->component, $object );
		$item_id = apply_filters( 'bp_get_activity_secondary_avatar_item_id', $item_id );

		// If we have no item_id or object, there is no avatar to display
		if ( empty( $item_id ) || empty( $object ) ) {
			return false;
		}

		// Get the avatar
		$avatar = bp_core_fetch_avatar( array(
			'item_id' => $item_id,
			'object'  => $object,
			'type'    => $type,
			'alt'     => $alt,
			'class'   => $class,
			'width'   => $width,
			'height'  => $height,
			'email'   => $email
		) );

		if ( !empty( $linked ) ) {
			$link = apply_filters( 'bp_get_activity_secondary_avatar_link', $link, $activities_template->activity->component );

			return sprintf( '<a href="%s" class="%s">%s</a>',
				$link,
				$link_class,
				apply_filters( 'bp_get_activity_secondary_avatar', $avatar )
			);
		}

		// else
		return apply_filters( 'bp_get_activity_secondary_avatar', $avatar );
	}

/**
 * Output the activity action
 *
 * @since BuddyPress (1.2)
 *
 * @param array $args See bp_get_activity_action()
 * @uses bp_get_activity_action()
 */
function bp_activity_action( $args = array() ) {
	echo bp_get_activity_action( $args );
}

	/**
	 * Return the activity action
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @param array $args Only parameter is "no_timestamp". If true, timestamp is shown in output.
	 * @uses apply_filters_ref_array() To call the 'bp_get_activity_action_pre_meta' hook
	 * @uses bp_insert_activity_meta()
	 * @uses apply_filters_ref_array() To call the 'bp_get_activity_action' hook.
	 *
	 * @param array $args {
	 *     @type bool $no_timestamp Whether to exclude the timestamp.
	 * }
	 * @return string The activity action.
	 */
	function bp_get_activity_action( $args = array() ) {
		global $activities_template;

		$defaults = array(
			'no_timestamp' => false,
		);

		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		$action = $activities_template->activity->action;
		$action = apply_filters_ref_array( 'bp_get_activity_action_pre_meta', array( $action, &$activities_template->activity, $args ) );

		if ( ! empty( $action ) && ! $no_timestamp )
			$action = bp_insert_activity_meta( $action );

		return apply_filters_ref_array( 'bp_get_activity_action', array( $action, &$activities_template->activity, $args ) );
	}

/**
 * Output the activity content body
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_content_body()
 */
function bp_activity_content_body() {
	echo bp_get_activity_content_body();
}

	/**
	 * Return the activity content body
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_insert_activity_meta()
	 * @uses apply_filters_ref_array() To call the 'bp_get_activity_content_body' hook.
	 *
	 * @return string The activity content body.
	 */
	function bp_get_activity_content_body() {
		global $activities_template;

		// Backwards compatibility if action is not being used
		if ( empty( $activities_template->activity->action ) && !empty( $activities_template->activity->content ) )
			$activities_template->activity->content = bp_insert_activity_meta( $activities_template->activity->content );

		return apply_filters_ref_array( 'bp_get_activity_content_body', array( $activities_template->activity->content, &$activities_template->activity ) );
	}

/**
 * Does the activity have content?
 *
 * @since BuddyPress (1.2)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool True if activity has content, false otherwise.
 */
function bp_activity_has_content() {
	global $activities_template;

	if ( !empty( $activities_template->activity->content ) )
		return true;

	return false;
}

/**
 * Output the activity content.
 *
 * @since BuddyPress (1.0)
 * @deprecated BuddyPress (1.5)
 *
 * @todo properly deprecate this function.
 *
 * @uses bp_get_activity_content()
 */
function bp_activity_content() {
	echo bp_get_activity_content();
}

	/**
	 * Return the activity content.
	 *
	 * @since BuddyPress (1.0)
	 * @deprecated BuddyPress (1.5)
	 *
	 * @todo properly deprecate this function.
	 *
	 * @uses bp_get_activity_action()
	 * @uses bp_get_activity_content_body()
	 * @uses apply_filters() To call the 'bp_get_activity_content' hook.
	 *
	 * @return string The activity content.
	 */
	function bp_get_activity_content() {
		/**
		 * If you want to filter activity update content, please use
		 * the filter 'bp_get_activity_content_body'
		 *
		 * This function is mainly for backwards comptibility.
		 */

		$content = bp_get_activity_action() . ' ' . bp_get_activity_content_body();
		return apply_filters( 'bp_get_activity_content', $content );
	}

/**
 * Attach metadata about an activity item to the activity content.
 *
 * This metadata includes the time since the item was posted (which will appear
 * as a link to the item's permalink).
 *
 * @since BuddyPress (1.2)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses bp_core_time_since()
 * @uses apply_filters_ref_array() To call the 'bp_activity_time_since' hook.
 * @uses bp_is_single_activity()
 * @uses bp_activity_get_permalink()
 * @uses esc_attr__()
 * @uses apply_filters_ref_array() To call the 'bp_activity_permalink' hook.
 * @uses apply_filters() To call the 'bp_insert_activity_meta' hook.
 *
 * @param string $content The activity content.
 * @return string The activity content with the metadata string attached.
 */
function bp_insert_activity_meta( $content ) {
	global $activities_template;

	// Strip any legacy time since placeholders from BP 1.0-1.1
	$content = str_replace( '<span class="time-since">%s</span>', '', $content );

	// Insert the time since.
	$time_since = apply_filters_ref_array( 'bp_activity_time_since', array( '<span class="time-since">' . bp_core_time_since( $activities_template->activity->date_recorded ) . '</span>', &$activities_template->activity ) );

	// Insert the permalink
	if ( !bp_is_single_activity() )
		$content = apply_filters_ref_array( 'bp_activity_permalink', array( sprintf( '%1$s <a href="%2$s" class="view activity-time-since" title="%3$s">%4$s</a>', $content, bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ), esc_attr__( 'View Discussion', 'buddypress' ), $time_since ), &$activities_template->activity ) );
	else
		$content .= str_pad( $time_since, strlen( $time_since ) + 2, ' ', STR_PAD_BOTH );

	return apply_filters( 'bp_insert_activity_meta', $content );
}

/**
 * Determine if the current user can delete an activity item.
 *
 * @since BuddyPress (1.2)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses apply_filters() To call the 'bp_activity_user_can_delete' hook
 *
 * @param object $activity Optional. Falls back on the current item in the loop.
 * @return bool True if can delete, false otherwise.
 */
function bp_activity_user_can_delete( $activity = false ) {
	global $activities_template;

	if ( !$activity )
		$activity = $activities_template->activity;

	if ( isset( $activity->current_comment ) )
		$activity = $activity->current_comment;

	$can_delete = false;

	if ( bp_current_user_can( 'bp_moderate' ) )
		$can_delete = true;

	if ( is_user_logged_in() && $activity->user_id == bp_loggedin_user_id() )
		$can_delete = true;

	if ( bp_is_item_admin() && bp_is_single_item() )
		$can_delete = true;

	return apply_filters( 'bp_activity_user_can_delete', $can_delete, $activity );
}

/**
 * Output the activity parent content.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_activity_parent_content() for a description of arguments.
 * @uses bp_get_activity_parent_content()
 *
 * @param array $args See {@link bp_get_activity_parent_content} for description.
 */
function bp_activity_parent_content( $args = '' ) {
	echo bp_get_activity_parent_content($args);
}

	/**
	 * Return the activity content.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses wp_parse_args()
	 * @uses apply_filters() To call the 'bp_get_activity_parent_content' hook.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @deprecated bool $hide_user No longer used.
	 * }
	 * @return mixed False on failure, otherwise the activity parent content
	 */
	function bp_get_activity_parent_content( $args = '' ) {
		global $activities_template;

		$defaults = array(
			'hide_user' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Get the ID of the parent activity content
		if ( !$parent_id = $activities_template->activity->item_id )
			return false;

		// Bail if no parent content
		if ( empty( $activities_template->activity_parents[$parent_id] ) )
			return false;

		// Bail if no action
		if ( empty( $activities_template->activity_parents[$parent_id]->action ) )
			return false;

		// Content always includes action
		$content = $activities_template->activity_parents[$parent_id]->action;

		// Maybe append activity content, if it exists
		if ( ! empty( $activities_template->activity_parents[$parent_id]->content ) )
			$content .= ' ' . $activities_template->activity_parents[$parent_id]->content;

		// Remove the time since content for backwards compatibility
		$content = str_replace( '<span class="time-since">%s</span>', '', $content );

		// Remove images
		$content = preg_replace( '/<img[^>]*>/Ui', '', $content );

		return apply_filters( 'bp_get_activity_parent_content', $content );
	}

/**
 * Output the parent activity's user ID.
 *
 * @since BuddyPress (1.7)
 */
function bp_activity_parent_user_id() {
	echo bp_get_activity_parent_user_id();
}

	/**
	 * Return the parent activity's user ID.
	 *
	 * @since BuddyPress (1.7)
	 *
	 * @global BP_Activity_Template $activities_template
	 *
	 * @return bool|int False if parent activity can't be found, otherwise
	 *         the parent activity's user ID.
	 */
	function bp_get_activity_parent_user_id() {
		global $activities_template;

		// Bail if no activity on no item ID
		if ( empty( $activities_template->activity ) || empty( $activities_template->activity->item_id ) )
			return false;

		// Get the ID of the parent activity content
		if ( !$parent_id = $activities_template->activity->item_id )
			return false;

		// Bail if no parent item
		if ( empty( $activities_template->activity_parents[$parent_id] ) )
			return false;

		// Bail if no parent user ID
		if ( empty( $activities_template->activity_parents[$parent_id]->user_id ) )
			return false;

		$retval = $activities_template->activity_parents[$parent_id]->user_id;

		return (int) apply_filters( 'bp_get_activity_parent_user_id', $retval );
	}

/**
 * Output whether or not the current activity is in a current user's favorites.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_is_favorite()
 */
function bp_activity_is_favorite() {
	echo bp_get_activity_is_favorite();
}

	/**
	 * Return whether the current activity is in a current user's favorites.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_is_favorite' hook.
	 *
	 * @return bool True if user favorite, false otherwise.
	 */
	function bp_get_activity_is_favorite() {
		global $activities_template;

 		return apply_filters( 'bp_get_activity_is_favorite', in_array( $activities_template->activity->id, (array) $activities_template->my_favs ) );
	}

/**
 * Output the comment markup for an activity item.
 *
 * @since BuddyPress (1.2)
 *
 * @todo deprecate $args param
 *
 * @param string $args Unused. Appears to be left over from an earlier implementation.
 */
function bp_activity_comments( $args = '' ) {
	echo bp_activity_get_comments( $args );
}

	/**
	 * Get the comment markup for an activity item.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @todo deprecate $args param
	 *
	 * @todo Given that checks for children already happen in bp_activity_recurse_comments(),
	 *    this function can probably be streamlined or removed.
	 *
	 * @param string $args Unused. Appears to be left over from an earlier implementation.
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_activity_recurse_comments()
	 */
	function bp_activity_get_comments( $args = '' ) {
		global $activities_template;

		if ( !isset( $activities_template->activity->children ) || !$activities_template->activity->children )
			return false;

		bp_activity_recurse_comments( $activities_template->activity );
	}

		/**
		 * Loops through a level of activity comments and loads the template for each
		 *
		 * Note: The recursion itself used to happen entirely in this function. Now it is
		 * split between here and the comment.php template.
		 *
		 * @since BuddyPress (1.2)
		 *
		 * @param object $comment The activity object currently being recursed
		 *
		 * @global object $activities_template {@link BP_Activity_Template}
		 * @uses locate_template()
		 */
		function bp_activity_recurse_comments( $comment ) {
			global $activities_template;

			if ( empty( $comment ) )
				return false;

			if ( empty( $comment->children ) )
				return false;

			echo apply_filters( 'bp_activity_recurse_comments_start_ul', '<ul>');
			foreach ( (array) $comment->children as $comment_child ) {
				// Put the comment into the global so it's available to filters
				$activities_template->activity->current_comment = $comment_child;

				$template = bp_locate_template( 'activity/comment.php', false, false );

				// Backward compatibility. In older versions of BP, the markup was
				// generated in the PHP instead of a template. This ensures that
				// older themes (which are not children of bp-default and won't
				// have the new template) will still work.
				if ( !$template ) {
					$template = buddypress()->plugin_dir . '/bp-themes/bp-default/activity/comment.php';
				}

				load_template( $template, false );

				unset( $activities_template->activity->current_comment );
			}
			echo apply_filters( 'bp_activity_recurse_comments_end_ul', '</ul>');
		}

/**
 * Utility function that returns the comment currently being recursed.
 *
 * @since BuddyPress (1.5)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses apply_filters() To call the 'bp_activity_current_comment' hook.
 *
 * @return object|bool $current_comment The activity comment currently being
 *         displayed. False on failure.
 */
function bp_activity_current_comment() {
	global $activities_template;

	$current_comment = !empty( $activities_template->activity->current_comment ) ? $activities_template->activity->current_comment : false;

	return apply_filters( 'bp_activity_current_comment', $current_comment );
}


/**
 * Output the ID of the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_id()
 */
function bp_activity_comment_id() {
	echo bp_get_activity_comment_id();
}

	/**
	 * Return the ID of the activity comment currently being displayed.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_activity_comment_id' hook.
	 *
	 * @return int|bool $comment_id The ID of the activity comment
	 *         currently being displayed, false if none is found.
	 */
	function bp_get_activity_comment_id() {
		global $activities_template;

		$comment_id = isset( $activities_template->activity->current_comment->id ) ? $activities_template->activity->current_comment->id : false;

		return apply_filters( 'bp_activity_comment_id', $comment_id );
	}

/**
 * Output the ID of the author of the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_user_id()
 */
function bp_activity_comment_user_id() {
	echo bp_get_activity_comment_user_id();
}

	/**
	 * Return the ID of the author of the activity comment currently being displayed.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_activity_comment_user_id' hook.
	 *
	 * @return int|bool $user_id The user_id of the author of the displayed
	 *         activity comment. False on failure.
	 */
	function bp_get_activity_comment_user_id() {
		global $activities_template;

		$user_id = isset( $activities_template->activity->current_comment->user_id ) ? $activities_template->activity->current_comment->user_id : false;

		return apply_filters( 'bp_activity_comment_user_id', $user_id );
	}

/**
 * Output the author link for the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_user_link()
 */
function bp_activity_comment_user_link() {
	echo bp_get_activity_comment_user_link();
}

	/**
	 * Return the author link for the activity comment currently being displayed.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses bp_core_get_user_domain()
	 * @uses bp_get_activity_comment_user_id()
	 * @uses apply_filters() To call the 'bp_activity_comment_user_link' hook
	 *
	 * @return string $user_link The URL of the activity comment author's profile.
	 */
	function bp_get_activity_comment_user_link() {
		$user_link = bp_core_get_user_domain( bp_get_activity_comment_user_id() );

		return apply_filters( 'bp_activity_comment_user_link', $user_link );
	}

/**
 * Output the author name for the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_name()
 */
function bp_activity_comment_name() {
	echo bp_get_activity_comment_name();
}

	/**
	 * Return the author name for the activity comment currently being displayed.
	 *
	 * The use of the 'bp_acomment_name' filter is deprecated. Please use
	 * 'bp_activity_comment_name'.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_acomment_name' hook.
	 * @uses apply_filters() To call the 'bp_activity_comment_name' hook.
	 *
	 * @return string $name The full name of the activity comment author.
	 */
	function bp_get_activity_comment_name() {
		global $activities_template;

		if ( isset( $activities_template->activity->current_comment->user_fullname ) )
			$name = apply_filters( 'bp_acomment_name', $activities_template->activity->current_comment->user_fullname, $activities_template->activity->current_comment );  // backward compatibility
		else
			$name = $activities_template->activity->current_comment->display_name;

		return apply_filters( 'bp_activity_comment_name', $name );
	}

/**
 * Output the date_recorded of the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_date_recorded()
 */
function bp_activity_comment_date_recorded() {
	echo bp_get_activity_comment_date_recorded();
}

	/**
	 * Return the date_recorded for the activity comment currently being displayed.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_core_time_since()
	 * @uses apply_filters() To call the 'bp_activity_comment_date_recorded' hook
	 *
	 * @return string|bool $date_recorded Time since the activity was recorded,
	 *         in the form "%s ago". False on failure.
	 */
	function bp_get_activity_comment_date_recorded() {
		global $activities_template;

		if ( empty( $activities_template->activity->current_comment->date_recorded ) )
			return false;

		$date_recorded = bp_core_time_since( $activities_template->activity->current_comment->date_recorded );

		return apply_filters( 'bp_activity_comment_date_recorded', $date_recorded );
	}

/**
 * Output the 'delete' URL for the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_delete_link()
 */
function bp_activity_comment_delete_link() {
	echo bp_get_activity_comment_delete_link();
}

	/**
	 * Gets the 'delete' URL for the activity comment currently being displayed
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @uses wp_nonce_url()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_activity_slug()
	 * @uses bp_get_activity_comment_id()
	 * @uses apply_filters() To call the 'bp_activity_comment_delete_link' hook.
	 *
	 * @return string $link The nonced URL for deleting the current
	 *         activity comment.
	 */
	function bp_get_activity_comment_delete_link() {
		$link = wp_nonce_url( bp_get_root_domain() . '/' . bp_get_activity_slug() . '/delete/' . bp_get_activity_comment_id() . '?cid=' . bp_get_activity_comment_id(), 'bp_activity_delete_link' );

		return apply_filters( 'bp_activity_comment_delete_link', $link );
	}

/**
 * Output the content of the activity comment currently being displayed.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_activity_comment_content()
 */
function bp_activity_comment_content() {
	echo bp_get_activity_comment_content();
}

	/**
	 * Return the content of the activity comment currently being displayed.
	 *
	 * The content is run through two filters. 'bp_get_activity_content'
	 * will apply all filters applied to activity items in general. Use
	 * 'bp_activity_comment_content' to modify the content of activity
	 * comments only.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_content' hook.
	 * @uses apply_filters() To call the 'bp_activity_comment_content' hook.
	 *
	 * @return string $content The content of the current activity comment.
	 */
	function bp_get_activity_comment_content() {
		global $activities_template;

		$content = apply_filters( 'bp_get_activity_content', $activities_template->activity->current_comment->content );

		return apply_filters( 'bp_activity_comment_content', $content );
	}

/**
 * Output the activity comment count.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_activity_get_comment_count()
 */
function bp_activity_comment_count() {
	echo bp_activity_get_comment_count();
}

	/**
	 * Return the content of the activity comment currently being displayed.
	 *
	 * The content is run through two filters. 'bp_get_activity_content'
	 * will apply all filters applied to activity items in general. Use
	 * 'bp_activity_comment_content' to modify the content of activity
	 * comments only.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_activity_recurse_comment_count()
	 * @uses apply_filters() To call the 'bp_activity_get_comment_count' hook.
	 * @todo deprecate $args
	 *
	 * @param array $args Deprecated.
	 * @return int $count The activity comment count.
	 */
	function bp_activity_get_comment_count( $args = '' ) {
		global $activities_template;

		if ( !isset( $activities_template->activity->children ) || !$activities_template->activity->children )
			return 0;

		$count = bp_activity_recurse_comment_count( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comment_count', (int) $count );
	}

		/**
		 * Return the content of the activity comment currently being displayed.
		 *
		 * The content is run through two filters. 'bp_get_activity_content'
		 * will apply all filters applied to activity items in general.
		 * Use bp_activity_comment_content to modify the content of
		 * activity comments only.
		 *
		 * @since BuddyPress (1.2)
		 *
		 * @uses bp_activity_recurse_comment_count()
		 * @uses apply_filters() To call the 'bp_activity_get_comment_count' hook
		 * @todo investigate why bp_activity_recurse_comment_count() is used while being declared
		 *
		 * @param object $comment Activity comment object.
		 * @param int $count The current iteration count.
		 * @return int $count The activity comment count.
		 */
		function bp_activity_recurse_comment_count( $comment, $count = 0 ) {

			if ( empty( $comment->children ) )
				return $count;

			foreach ( (array) $comment->children as $comment ) {
				$count++;
				$count = bp_activity_recurse_comment_count( $comment, $count );
			}

			return $count;
		}

/**
 * Output the depth of the current activity comment.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_activity_comment_depth() {
	echo bp_activity_get_comment_depth();
}
	/**
	 * Return the current activity comment depth.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return int
	 */
	function bp_activity_get_comment_depth() {
		global $activities_template;
		return apply_filters( 'bp_activity_get_comment_depth', $activities_template->activity->current_comment->depth );
	}

/**
 * Output the activity comment link.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_comment_link()
 */
function bp_activity_comment_link() {
	echo bp_get_activity_comment_link();
}

	/**
	 * Return the activity comment link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_comment_link' hook.
	 *
	 * @return string The activity comment link.
	 */
	function bp_get_activity_comment_link() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_comment_link', '?ac=' . $activities_template->activity->id . '/#ac-form-' . $activities_template->activity->id );
	}

/**
 * Output the activity comment form no javascript display CSS.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_comment_form_nojs_display()
 */
function bp_activity_comment_form_nojs_display() {
	echo bp_get_activity_comment_form_nojs_display();
}

	/**
	 * Return the activity comment form no javascript display CSS.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 *
	 * @return string|bool The activity comment form no javascript
	 *         display CSS. False on failure
	 */
	function bp_get_activity_comment_form_nojs_display() {
		global $activities_template;
		if ( isset( $_GET['ac'] ) && $_GET['ac'] == $activities_template->activity->id . '/' )
			return 'style="display: block"';

		return false;
	}

/**
 * Output the activity comment form action.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_comment_form_action()
 */
function bp_activity_comment_form_action() {
	echo bp_get_activity_comment_form_action();
}

	/**
	 * Return the activity comment form action.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses home_url()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_comment_form_action' hook.
	 *
	 * @return string The activity comment form action.
	 */
	function bp_get_activity_comment_form_action() {
		return apply_filters( 'bp_get_activity_comment_form_action', home_url( bp_get_activity_root_slug() . '/reply/' ) );
	}

/**
 * Output the activity permalink ID.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_permalink_id()
 */
function bp_activity_permalink_id() {
	echo bp_get_activity_permalink_id();
}

	/**
	 * Return the activity permalink ID.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses apply_filters() To call the 'bp_get_activity_permalink_id' hook.
	 *
	 * @return string The activity permalink ID.
	 */
	function bp_get_activity_permalink_id() {
		return apply_filters( 'bp_get_activity_permalink_id', bp_current_action() );
	}

/**
 * Output the activity thread permalink.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_permalink_id()
 */
function bp_activity_thread_permalink() {
	echo bp_get_activity_thread_permalink();
}

	/**
	 * Return the activity thread permalink.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_activity_get_permalink()
	 * @uses apply_filters() To call the 'bp_get_activity_thread_permalink' hook.
	 *
	 * @return string $link The activity thread permalink.
	 */
	function bp_get_activity_thread_permalink() {
		global $activities_template;

		$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );

	 	return apply_filters( 'bp_get_activity_thread_permalink', $link );
	}

/**
 * Output the activity comment permalink.
 *
 * @since BuddyPress (1.8)
 *
 * @uses bp_get_activity_permalink_id()
 */
function bp_activity_comment_permalink() {
	echo bp_get_activity_comment_permalink();
}
	/**
	 * Return the activity comment permalink.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @uses bp_activity_get_permalink()
	 * @uses apply_filters() To call the 'bp_get_activity_comment_permalink' hook.
	 *
	 * @return string $link The activity comment permalink.
	 */
	function bp_get_activity_comment_permalink() {
		global $activities_template;

		$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ) . '#acomment-' . $activities_template->activity->current_comment->id;

		return apply_filters( 'bp_get_activity_comment_permalink', $link );
	}

/**
 * Output the activity favorite link.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_favorite_link()
 */
function bp_activity_favorite_link() {
	echo bp_get_activity_favorite_link();
}

	/**
	 * Return the activity favorite link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses wp_nonce_url()
	 * @uses home_url()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_favorite_link' hook
	 *
	 * @return string The activity favorite link.
	 */
	function bp_get_activity_favorite_link() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_favorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/favorite/' . $activities_template->activity->id . '/' ), 'mark_favorite' ) );
	}

/**
 * Output the activity unfavorite link.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_unfavorite_link()
 */
function bp_activity_unfavorite_link() {
	echo bp_get_activity_unfavorite_link();
}

	/**
	 * Return the activity unfavorite link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses wp_nonce_url()
	 * @uses home_url()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_unfavorite_link' hook.
	 *
	 * @return string The activity unfavorite link.
	 */
	function bp_get_activity_unfavorite_link() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_unfavorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/unfavorite/' . $activities_template->activity->id . '/' ), 'unmark_favorite' ) );
	}

/**
 * Output the activity CSS class.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_css_class()
 */
function bp_activity_css_class() {
	echo bp_get_activity_css_class();
}

	/**
	 * Return the current activity item's CSS class.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_activity_mini_activity_types' hook.
	 * @uses bp_activity_get_comment_count()
	 * @uses bp_activity_can_comment()
	 * @uses apply_filters() To call the 'bp_get_activity_css_class' hook.
	 *
	 * @return string The activity item's CSS class.
	 */
	function bp_get_activity_css_class() {
		global $activities_template;

		$mini_activity_actions = apply_filters( 'bp_activity_mini_activity_types', array(
			'friendship_accepted',
			'friendship_created',
			'new_blog',
			'joined_group',
			'created_group',
			'new_member'
		) );

		$class = ' activity-item';

		if ( in_array( $activities_template->activity->type, (array) $mini_activity_actions ) || empty( $activities_template->activity->content ) )
			$class .= ' mini';

		if ( bp_activity_get_comment_count() && bp_activity_can_comment() )
			$class .= ' has-comments';

		return apply_filters( 'bp_get_activity_css_class', $activities_template->activity->component . ' ' . $activities_template->activity->type . $class );
	}

/**
 * Output the activity delete link.
 *
 * @since BuddyPress (1.1)
 *
 * @uses bp_get_activity_delete_link()
 */
function bp_activity_delete_link() {
	echo bp_get_activity_delete_link();
}

	/**
	 * Return the activity delete link.
	 *
	 * @since BuddyPress (1.1)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses bp_get_root_domain()
	 * @uses bp_get_activity_root_slug()
	 * @uses bp_is_activity_component()
	 * @uses bp_current_action()
	 * @uses add_query_arg()
	 * @uses wp_get_referer()
	 * @uses wp_nonce_url()
	 * @uses apply_filters() To call the 'bp_get_activity_delete_link' hook.
	 *
	 * @return string $link Activity delete link. Contains $redirect_to arg
	 *         if on single activity page.
	 */
	function bp_get_activity_delete_link() {
		global $activities_template;

		$url   = bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/delete/' . $activities_template->activity->id;
		$class = 'delete-activity';

		// Determine if we're on a single activity page, and customize accordingly
		if ( bp_is_activity_component() && is_numeric( bp_current_action() ) ) {
			$url   = add_query_arg( array( 'redirect_to' => wp_get_referer() ), $url );
			$class = 'delete-activity-single';
		}

		$link = '<a href="' . wp_nonce_url( $url, 'bp_activity_delete_link' ) . '" class="button item-button bp-secondary-action ' . $class . ' confirm" rel="nofollow">' . __( 'Delete', 'buddypress' ) . '</a>';
		return apply_filters( 'bp_get_activity_delete_link', $link );
	}

/**
 * Output the activity latest update link.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_activity_latest_update() for description of parameters.
 * @uses bp_get_activity_latest_update()
 *
 * @param int $user_id See {@link bp_get_activity_latest_update()} for description.
 */
function bp_activity_latest_update( $user_id = 0 ) {
	echo bp_get_activity_latest_update( $user_id );
}

	/**
	 * Return the activity latest update link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_is_user_inactive()
	 * @uses bp_core_is_user_deleted()
	 * @uses bp_get_user_meta()
	 * @uses apply_filters() To call the 'bp_get_activity_latest_update_excerpt' hook
	 * @uses bp_create_excerpt()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_latest_update' hook
	 *
	 * @param int $user_id If empty, will fall back on displayed user.
	 * @return string|bool $latest_update The activity latest update link.
	 *         False on failure
	 */
	function bp_get_activity_latest_update( $user_id = 0 ) {

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		if ( bp_is_user_inactive( $user_id ) )
			return false;

		if ( !$update = bp_get_user_meta( $user_id, 'bp_latest_update', true ) )
			return false;

		$latest_update = apply_filters( 'bp_get_activity_latest_update_excerpt', trim( strip_tags( bp_create_excerpt( $update['content'], 358 ) ) ) );
		$latest_update .= ' <a href="' . bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $update['id'] . '/"> ' . __( 'View', 'buddypress' ) . '</a>';

		return apply_filters( 'bp_get_activity_latest_update', $latest_update  );
	}

/**
 * Output the activity filter links.
 *
 * @since BuddyPress (1.1)
 *
 * @see bp_get_activity_filter_links() for description of parameters.
 * @uses bp_get_activity_filter_links()
 *
 * @param array $args See {@link bp_get_activity_filter_links()} for description.
 */
function bp_activity_filter_links( $args = false ) {
	echo bp_get_activity_filter_links( $args );
}

	/**
	 * Return the activity filter links.
	 *
	 * @since BuddyPress (1.1)
	 *
	 * @uses wp_parse_args()
	 * @uses BP_Activity_Activity::get_recorded_components() {@link BP_Activity_Activity}
	 * @uses esc_attr()
	 * @uses add_query_arg()
	 * @uses remove_query_arg()
	 * @uses apply_filters() To call the 'bp_get_activity_filter_link_href' hook.
	 * @uses apply_filters() To call the 'bp_get_activity_filter_links' hook.
	 *
	 * @param array $args {
	 *     @type string $style The type of markup to use for the links.
	 *           'list', 'paragraph', or 'span'. Default: 'list'.
	 * }
	 * @return string|bool $component_links The activity filter links.
	 *         False on failure.
	 */
	function bp_get_activity_filter_links( $args = false ) {

		$defaults = array(
			'style' => 'list'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Define local variable
		$component_links = array();

		// Fetch the names of components that have activity recorded in the DB
		$components = BP_Activity_Activity::get_recorded_components();

		if ( empty( $components ) )
			return false;

		foreach ( (array) $components as $component ) {

			// Skip the activity comment filter
			if ( 'activity' == $component )
				continue;

			if ( isset( $_GET['afilter'] ) && $component == $_GET['afilter'] )
				$selected = ' class="selected"';
			else
				unset($selected);

			$component = esc_attr( $component );

			switch ( $style ) {
				case 'list':
					$tag = 'li';
					$before = '<li id="afilter-' . $component . '"' . $selected . '>';
					$after = '</li>';
				break;
				case 'paragraph':
					$tag = 'p';
					$before = '<p id="afilter-' . $component . '"' . $selected . '>';
					$after = '</p>';
				break;
				case 'span':
					$tag = 'span';
					$before = '<span id="afilter-' . $component . '"' . $selected . '>';
					$after = '</span>';
				break;
			}

			$link = add_query_arg( 'afilter', $component );
			$link = remove_query_arg( 'acpage' , $link );
			$link = apply_filters( 'bp_get_activity_filter_link_href', $link, $component );

			$component_links[] = $before . '<a href="' . esc_attr( $link ) . '">' . ucwords( $component ) . '</a>' . $after;
		}

		$link = remove_query_arg( 'afilter' , $link );

		if ( isset( $_GET['afilter'] ) )
			$component_links[] = '<' . $tag . ' id="afilter-clear"><a href="' . esc_attr( $link ) . '">' . __( 'Clear Filter', 'buddypress' ) . '</a></' . $tag . '>';

 		return apply_filters( 'bp_get_activity_filter_links', implode( "\n", $component_links ) );
	}

/**
 * Determine if a comment can be made on an activity item.
 *
 * @since BuddyPress (1.2)
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses bp_get_activity_action_name()
 * @uses apply_filters() To call the 'bp_activity_can_comment' hook.
 *
 * @return bool $can_comment True if item can receive comments.
 */
function bp_activity_can_comment() {
	global $activities_template;

	$can_comment = true;

	if ( false === $activities_template->disable_blogforum_replies || (int) $activities_template->disable_blogforum_replies ) {
		if ( 'new_blog_post' == bp_get_activity_action_name() || 'new_blog_comment' == bp_get_activity_action_name() || 'new_forum_topic' == bp_get_activity_action_name() || 'new_forum_post' == bp_get_activity_action_name() )
			$can_comment = false;
	}

	if ( 'activity_comment' == bp_get_activity_action_name() )
		$can_comment = false;

	return apply_filters( 'bp_activity_can_comment', $can_comment );
}

/**
 * Determine if a comment can be made on an activity reply item.
 *
 * Defaults to true, but can be modified by plugins.
 *
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() To call the 'bp_activity_can_comment_reply' hook
 *
 * @param object $comment Activity comment.
 * @return bool $can_comment True if comment can receive comments.
 */
function bp_activity_can_comment_reply( $comment ) {
	$can_comment = true;

	if ( get_option( 'thread_comments' ) && bp_activity_get_comment_depth() >= get_option( 'thread_comments_depth' ) ) {
		$can_comment = false;
	}

	return apply_filters( 'bp_activity_can_comment_reply', $can_comment, $comment );
}

/**
 * Determine if an favorites are allowed.
 *
 * Defaults to true, but can be modified by plugins.
 *
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() To call the 'bp_activity_can_favorite' hook.
 *
 * @return bool $can_favorite True if comment can receive comments.
 */
function bp_activity_can_favorite() {
	$can_favorite = true;

	return apply_filters( 'bp_activity_can_favorite', $can_favorite );
}

/**
 * Output the total favorite count for a specified user.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_total_favorite_count_for_user() for description of parameters.
 * @uses bp_get_total_favorite_count_for_user()
 *
 * @param int $user_id See {@link bp_get_total_favorite_count_for_user()}.
 */
function bp_total_favorite_count_for_user( $user_id = 0 ) {
	echo bp_get_total_favorite_count_for_user( $user_id );
}

	/**
	 * Return the total favorite count for a specified user.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_activity_total_favorites_for_user()
	 * @uses apply_filters() To call the 'bp_get_total_favorite_count_for_user' hook
	 *
	 * @param int $user_id ID of user being queried. Default: displayed user ID.
	 * @return int The total favorite count for the specified user.
	 */
	function bp_get_total_favorite_count_for_user( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		return apply_filters( 'bp_get_total_favorite_count_for_user', bp_activity_total_favorites_for_user( $user_id ) );
	}
	add_filter( 'bp_get_total_favorite_count_for_user', 'bp_core_number_format' );

/**
 * Output the total mention count for a specified user.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_total_mention_count_for_user() for description of parameters.
 * @uses bp_get_total_favorite_count_for_user()
 *
 * @param int $user_id See {@link bp_get_total_mention_count_for_user()}.
 */
function bp_total_mention_count_for_user( $user_id = 0 ) {
	echo bp_get_total_mention_count_for_user( $user_id );
}

	/**
	 * Return the total mention count for a specified user.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_get_user_meta()
	 * @uses apply_filters() To call the 'bp_get_total_mention_count_for_user' hook.
	 *
	 * @param int $user_id ID of user being queried. Default: displayed user ID.
	 * @return int The total mention count for the specified user.
	 */
	function bp_get_total_mention_count_for_user( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		return apply_filters( 'bp_get_total_mention_count_for_user', bp_get_user_meta( $user_id, 'bp_new_mention_count', true ) );
	}
	add_filter( 'bp_get_total_mention_count_for_user', 'bp_core_number_format' );

/**
 * Output the public message link for displayed user.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_send_public_message_link()
 */
function bp_send_public_message_link() {
	echo bp_get_send_public_message_link();
}

	/**
	 * Return the public message link for the displayed user.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses is_user_logged_in()
	 * @uses bp_is_my_profile()
	 * @uses bp_is_user()
	 * @uses wp_nonce_url()
	 * @uses bp_get_activity_directory_permalink()
	 * @uses apply_filters() To call the 'bp_get_send_public_message_link' hook
	 *
	 * @return string The public message link for the displayed user.
	 */
	function bp_get_send_public_message_link() {

		if ( ! is_user_logged_in() || ! bp_is_user() || bp_is_my_profile() )
			return false;

		return apply_filters( 'bp_get_send_public_message_link', wp_nonce_url( bp_get_activity_directory_permalink() . '?r=' . bp_get_displayed_user_mentionname() ) );
	}

/**
 * Recurse through all activity comments and return the activity comment IDs.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $activity Array of activities generated from {@link bp_activity_get()}.
 * @param array $activity_ids Used for recursion purposes in this function.
 * @return array
 */
function bp_activity_recurse_comments_activity_ids( $activity = array(), $activity_ids = array() ) {
	if ( is_array( $activity ) && ! empty( $activity['activities'] ) ) {
		$activity = $activity['activities'][0];
	}

	if ( ! empty( $activity->children ) ) {
		foreach ($activity->children as $child ) {
			$activity_ids[] = $child->id;

			if( ! empty( $child->children ) ) {
				$activity_ids = bp_activity_recurse_comments_activity_ids( $child, $activity_ids );
			}
		}
	}

	return $activity_ids;
}

/**
 * Output the mentioned user display name.
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_mentioned_user_display_name() for description of parameters.
 * @uses bp_get_mentioned_user_display_name()
 *
 * @param int|string $user_id_or_username See {@link bp_get_mentioned_user_display_name()}.
 */
function bp_mentioned_user_display_name( $user_id_or_username ) {
	echo bp_get_mentioned_user_display_name( $user_id_or_username );
}

	/**
	 * Returns the mentioned user display name
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_core_get_user_displayname()
	 * @uses apply_filters() To call the 'bp_get_mentioned_user_display_name' hook.
	 *
	 * @param int|string User ID or username.
	 * @return string The mentioned user's display name.
	 */
	function bp_get_mentioned_user_display_name( $user_id_or_username ) {
		if ( !$name = bp_core_get_user_displayname( $user_id_or_username ) )
			$name = __( 'a user', 'buddypress' );

		return apply_filters( 'bp_get_mentioned_user_display_name', $name, $user_id_or_username );
	}

/**
 * Output button for sending a public message (an @-mention).
 *
 * @since BuddyPress (1.2)
 *
 * @see bp_get_send_public_message_button() for description of parameters.
 * @uses bp_get_send_public_message_button()
 *
 * @param array $args See {@link bp_get_send_public_message_button()}.
 */
function bp_send_public_message_button( $args = '' ) {
	echo bp_get_send_public_message_button( $args );
}

	/**
	 * Return button for sending a public message (an @-mention).
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_get_send_public_message_link()
	 * @uses wp_parse_args()
	 * @uses bp_get_button()
	 * @uses apply_filters() To call the 'bp_get_send_public_message_button' hook.
	 *
	 * @param array $args {
	 *     All arguments are optional. See {@link BP_Button} for complete
	 *     descriptions.
	 *     @type string $id Default: 'public_message'.
	 *     @type string $component Default: 'activity'.
	 *     @type bool $must_be_logged_in Default: true.
	 *     @type bool $block_self Default: true.
	 *     @type string $wrapper_id Default: 'post-mention'.
	 *     @type string $link_href Default: the public message link for
	 *           the current member in the loop.
	 *     @type string $link_title Default: 'Send a public message on your
	 *           activity stream.'.
	 *     @type string $link_text Default: 'Public Message'.
	 *     @type string $link_class Default: 'activity-button mention'.
	 * }
	 * @return string The button for sending a public message.
	 */
	function bp_get_send_public_message_button( $args = '' ) {
		$defaults = array(
			'id'                => 'public_message',
			'component'         => 'activity',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_id'        => 'post-mention',
			'link_href'         => bp_get_send_public_message_link(),
			'link_title'        => __( 'Send a public message on your activity stream.', 'buddypress' ),
			'link_text'         => __( 'Public Message', 'buddypress' ),
			'link_class'        => 'activity-button mention'
		);

		$button = wp_parse_args( $args, $defaults );

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_send_public_message_button', $button ) );
	}

/**
 * Output the activity post form action.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_activity_post_form_action()
 */
function bp_activity_post_form_action() {
	echo bp_get_activity_post_form_action();
}

	/**
	 * Return the activity post form action.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses home_url()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_activity_post_form_action' hook.
	 *
	 * @return string The activity post form action.
	 */
	function bp_get_activity_post_form_action() {
		return apply_filters( 'bp_get_activity_post_form_action', home_url( bp_get_activity_root_slug() . '/post/' ) );
	}

/**
 * Echo a list of linked avatars of users who have commented on the current activity item.
 *
 * Use this function to easily output activity comment authors' avatars.
 *
 * Avatars are wrapped in <li> elements, but you've got to provide your own
 * <ul> or <ol> wrapper markup.
 *
 * @since BuddyPress (1.7)
 *
 * @see bp_core_fetch_avatar() for a description of arguments.
 *
 * @param array $args See {@link bp_core_fetch_avatar()}.
 */
function bp_activity_comments_user_avatars( $args = array() ) {
	$defaults = array(
		'height' => false,
		'html'   => true,
		'type'   => 'thumb',
		'width'  => false,
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	// Get the user IDs of everyone who has left a comment to the current activity item
	$user_ids = bp_activity_get_comments_user_ids();

	$output = array();
	foreach ( (array) $user_ids as $user_id ) {
		$profile_link = bp_core_get_user_domain( $user_id );
		$image_html   = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'height' => $height, 'html' => $html, 'type' => $type, 'width' => $width, ) );

		$output[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $profile_link ), $image_html );
	}

	echo apply_filters( 'bp_activity_comments_user_avatars', '<li>' . implode( '</li><li>', $output ) . '</li>', $args, $output );
}

/**
 * Return the IDs of every user who's left a comment on the current activity item.
 *
 * @since BuddyPress (1.7)
 *
 * @return bool|array An array of IDs, or false if none are found.
 */
function bp_activity_get_comments_user_ids() {
	if ( empty( $GLOBALS['activities_template']->activity ) || empty( $GLOBALS['activities_template']->activity->children ) )
		return false;

	$user_ids = (array) bp_activity_recurse_comments_user_ids( $GLOBALS['activities_template']->activity->children );
	return apply_filters( 'bp_activity_get_comments_user_ids', array_unique( $user_ids ) );
}

	/**
	 * Recurse through all activity comments and collect the IDs of the users who wrote them.
	 *
	 * @since BuddyPress (1.7)
	 *
	 * @param array $comments Array of {@link BP_Activity_Activity} items.
	 * @return array Array of user IDs.
	 */
	function bp_activity_recurse_comments_user_ids( array $comments ) {
		$user_ids = array();

		foreach ( $comments as $comment ) {
			// If a user is a spammer, their activity items will have been automatically marked as spam. Skip these.
			if ( $comment->is_spam )
				continue;

			$user_ids[] = $comment->user_id;

			// Check for commentception
			if ( ! empty( $comment->children ) )
				$user_ids = array_merge( $user_ids, bp_activity_recurse_comments_user_ids( $comment->children ) );
		}

		return $user_ids;
	}

/**
 * Output the mentionname for the displayed user.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_displayed_user_mentionname() {
	echo bp_get_displayed_user_mentionname();
}
	/**
	 * Get the mentionname for the displayed user.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return string Mentionname for the displayed user, if available.
	 */
	function bp_get_displayed_user_mentionname() {
		return apply_filters( 'bp_get_displayed_user_mentionname', bp_activity_get_user_mentionname( bp_displayed_user_id() ) );
	}

/**
 * Echo a list of all registered activity types for use in dropdowns or checkbox lists.
 *
 * @since BuddyPress (1.7)
 *
 * @param string $output Optional. Either 'select' or 'checkbox'. Default: 'select'.
 * @param array $args {
 *     Optional extra arguments.
 *     @type string $checkbox_name When returning checkboxes, sets the 'name'
 *           attribute.
 *     @type array|string $selected A list of types that should be checked/
 *           selected.
 * }
 */
function bp_activity_types_list( $output = 'select', $args = '' ) {
	$defaults = array(
		'checkbox_name' => 'bp_activity_types',
		'selected'      => array(),
	);
	$args = wp_parse_args( $args, $defaults );

	$activities = bp_activity_get_types();
	natsort( $activities );

	// Loop through the activity types and output markup
	foreach ( $activities as $type => $description ) {

		// See if we need to preselect the current type
		$checked  = checked(  true, in_array( $type, (array) $args['selected'] ), false );
		$selected = selected( true, in_array( $type, (array) $args['selected'] ), false );

		if ( 'select' == $output )
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $type ), $selected, esc_html( $description ) );

		elseif ( 'checkbox' == $output )
			printf( '<label style="">%1$s<input type="checkbox" name="%2$s[]" value="%3$s" %4$s/></label>', esc_html( $description ), esc_attr( $args['checkbox_name'] ), esc_attr( $type ), $checked );

		// Allow custom markup
		do_action( 'bp_activity_types_list_' . $output, $args, $type, $description );
	}

	// Backpat with BP-Default for dropdown boxes only
	if ( 'select' == $output )
		do_action( 'bp_activity_filter_options' );
}


/* RSS Feed Template Tags ****************************************************/

/**
 * Output the sitewide activity feed link.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_sitewide_activity_feed_link()
 */
function bp_sitewide_activity_feed_link() {
	echo bp_get_sitewide_activity_feed_link();
}

	/**
	 * Returns the sitewide activity feed link.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @uses home_url()
	 * @uses bp_get_activity_root_slug()
	 * @uses apply_filters() To call the 'bp_get_sitewide_activity_feed_link' hook.
	 *
	 * @return string The sitewide activity feed link.
	 */
	function bp_get_sitewide_activity_feed_link() {
		return apply_filters( 'bp_get_sitewide_activity_feed_link', bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/feed/' );
	}

/**
 * Output the member activity feed link.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_member_activity_feed_link()
 */
function bp_member_activity_feed_link() {
	echo bp_get_member_activity_feed_link();
}

/**
 * Output the member activity feed link.
 *
 * @since BuddyPress (1.0)
 * @deprecated BuddyPress (1.2)
 *
 * @todo properly deprecate in favor of bp_member_activity_feed_link().
 *
 * @uses bp_get_member_activity_feed_link()
 */
function bp_activities_member_rss_link() { echo bp_get_member_activity_feed_link(); }

	/**
	 * Return the member activity feed link.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @uses bp_is_profile_component()
	 * @uses bp_is_current_action()
	 * @uses bp_displayed_user_domain()
	 * @uses bp_get_activity_slug()
	 * @uses bp_is_active()
	 * @uses bp_get_friends_slug()
	 * @uses bp_get_groups_slug()
	 * @uses apply_filters() To call the 'bp_get_activities_member_rss_link' hook.
	 *
	 * @return string $link The member activity feed link.
	 */
	function bp_get_member_activity_feed_link() {

		if ( bp_is_profile_component() || bp_is_current_action( 'just-me' ) )
			$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/feed/';
		elseif ( bp_is_active( 'friends' ) && bp_is_current_action( bp_get_friends_slug() ) )
			$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() . '/feed/';
		elseif ( bp_is_active( 'groups'  ) && bp_is_current_action( bp_get_groups_slug()  ) )
			$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() . '/feed/';
		elseif ( 'favorites' == bp_current_action() )
			$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/favorites/feed/';
		elseif ( 'mentions' == bp_current_action() && bp_activity_do_mentions() )
			$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/mentions/feed/';
		else
			$link = '';

		return apply_filters( 'bp_get_activities_member_rss_link', $link );
	}

	/**
	 * Return the member activity feed link.
	 *
	 * @since BuddyPress (1.0)
	 * @deprecated BuddyPress (1.2)
	 *
	 * @todo properly deprecate in favor of bp_get_member_activity_feed_link().
	 *
	 * @uses bp_get_member_activity_feed_link()
	 *
	 * @return string The member activity feed link.
	 */
	function bp_get_activities_member_rss_link() { return bp_get_member_activity_feed_link(); }


/** Template tags for RSS feed output ****************************************/

/**
 * Outputs the activity feed item guid.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_activity_feed_item_guid()
 */
function bp_activity_feed_item_guid() {
	echo bp_get_activity_feed_item_guid();
}

	/**
	 * Returns the activity feed item guid.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_feed_item_guid' hook.
	 *
	 * @return string The activity feed item guid.
	 */
	function bp_get_activity_feed_item_guid() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_guid', md5( $activities_template->activity->date_recorded . '-' . $activities_template->activity->content ) );
	}

/**
 * Output the activity feed item title.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_feed_item_title()
 */
function bp_activity_feed_item_title() {
	echo bp_get_activity_feed_item_title();
}

	/**
	 * Return the activity feed item title.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses ent2ncr()
	 * @uses convert_chars()
	 * @uses bp_create_excerpt()
	 * @uses apply_filters() To call the 'bp_get_activity_feed_item_title' hook.
	 *
	 * @return string $title The activity feed item title.
	 */
	function bp_get_activity_feed_item_title() {
		global $activities_template;

		if ( !empty( $activities_template->activity->action ) )
			$content = $activities_template->activity->action;
		else
			$content = $activities_template->activity->content;

		$content = explode( '<span', $content );
		$title = strip_tags( ent2ncr( trim( convert_chars( $content[0] ) ) ) );

		if ( ':' == substr( $title, -1 ) )
			$title = substr( $title, 0, -1 );

		if ( 'activity_update' == $activities_template->activity->type )
			$title .= ': ' . strip_tags( ent2ncr( trim( convert_chars( bp_create_excerpt( $activities_template->activity->content, 70, array( 'ending' => " [&#133;]" ) ) ) ) ) );

		return apply_filters( 'bp_get_activity_feed_item_title', $title );
	}

/**
 * Output the activity feed item link
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_feed_item_link()
 */
function bp_activity_feed_item_link() {
	echo bp_get_activity_feed_item_link();
}

	/**
	 * Return the activity feed item link
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_feed_item_link' hook.
	 *
	 * @return string The activity feed item link.
	 */
	function bp_get_activity_feed_item_link() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_link', $activities_template->activity->primary_link );
	}

/**
 * Output the activity feed item date.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_feed_item_date()
 */
function bp_activity_feed_item_date() {
	echo bp_get_activity_feed_item_date();
}

	/**
	 * Return the activity feed item date.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses apply_filters() To call the 'bp_get_activity_feed_item_date' hook.
	 *
	 * @return string The activity feed item date.
	 */
	function bp_get_activity_feed_item_date() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_date', $activities_template->activity->date_recorded );
	}

/**
 * Output the activity feed item description.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_get_activity_feed_item_description()
 */
function bp_activity_feed_item_description() {
	echo bp_get_activity_feed_item_description();
}

	/**
	 * Return the activity feed item description.
	 *
	 * @since BuddyPress (1.0)
	 *
	 * @global object $activities_template {@link BP_Activity_Template}
	 * @uses ent2ncr()
	 * @uses convert_chars()
	 * @uses apply_filters() To call the 'bp_get_activity_feed_item_description' hook.
	 *
	 * @return string The activity feed item description.
	 */
	function bp_get_activity_feed_item_description() {
		global $activities_template;

		$content = '';
		if ( ! empty( $activities_template->activity->content ) )
			$content = $activities_template->activity->content;

		return apply_filters( 'bp_get_activity_feed_item_description', ent2ncr( convert_chars( str_replace( '%s', '', $content ) ) ) );
	}

/**
 * Template tag so we can hook activity feed to <head>.
 *
 * @since BuddyPress (1.5)
 *
 * @uses bloginfo()
 * @uses bp_sitewide_activity_feed_link()
 */
function bp_activity_sitewide_feed() {
?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php _e( 'Site Wide Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_sitewide_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_activity_sitewide_feed' );
