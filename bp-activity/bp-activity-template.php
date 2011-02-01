<?php

/**
 * BuddyPress Activity Template Functions
 *
 * @package BuddyPress
 * @subpackage Activity Template
 */

/**
 * Output the activity component slug
 *
 * @package BuddyPress
 * @subpackage Activity Template
 * @since BuddyPress {unknown}
 *
 * @uses bp_get_activity_slug()
 */
function bp_activity_slug() {
	echo bp_get_activity_slug();
}
	/**
	 * Return the activity component slug
	 *
	 * @package BuddyPress
	 * @subpackage Activity Template
	 * @since BuddyPress {unknown}
	 */
	function bp_get_activity_slug() {
		global $bp;
		return apply_filters( 'bp_get_activity_slug', $bp->activity->slug );
	}

/**
 * Output the activity component root slug
 *
 * @package BuddyPress
 * @subpackage Activity Template
 * @since BuddyPress {unknown}
 *
 * @uses bp_get_activity_root_slug()
 */
function bp_activity_root_slug() {
	echo bp_get_activity_root_slug();
}
	/**
	 * Return the activity component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Activity Template
	 * @since BuddyPress {unknown}
	 */
	function bp_get_activity_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_activity_root_slug', $bp->activity->root_slug );
	}

/**
 * The main activity template loop
 *
 * This is responsible for loading a group of activity items and displaying them
 *
 * @package BuddyPress
 * @subpackage Activity Template
 * @since {unknown}
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

	function bp_activity_template( $page, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments, $show_hidden, $exclude, $in ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['acpage'] ) ? intval( $_REQUEST['acpage'] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		// Check if blog/forum replies are disabled
		$this->disable_blogforum_replies = isset( $bp->site_options['bp-disable-blogforum-comments'] ) ? $bp->site_options['bp-disable-blogforum-comments'] : false;

		// Get an array of the logged in user's favorite activities
		$this->my_favs = maybe_unserialize( get_user_meta( $bp->loggedin_user->id, 'bp_favorite_activities', true ) );

		// Fetch specific activity items based on ID's
		if ( !empty( $include ) )
			$this->activities = bp_activity_get_specific( array( 'activity_ids' => explode( ',', $include ), 'max' => $max, 'page' => $this->pag_page, 'per_page' => $this->pag_num, 'sort' => $sort, 'display_comments' => $display_comments ) );
		// Fetch all activity items
		else
			$this->activities = bp_activity_get( array( 'display_comments' => $display_comments, 'max' => $max, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'sort' => $sort, 'search_terms' => $search_terms, 'filter' => $filter, 'show_hidden' => $show_hidden, 'exclude' => $exclude, 'in' => $in ) );

		if ( !$max || $max >= (int)$this->activities['total'] )
			$this->total_activity_count = (int)$this->activities['total'];
		else
			$this->total_activity_count = (int)$max;

		$this->activities = $this->activities['activities'];

		if ( $max ) {
			if ( $max >= count($this->activities) ) {
				$this->activity_count = count( $this->activities );
			} else {
				$this->activity_count = (int)$max;
			}
		} else {
			$this->activity_count = count( $this->activities );
		}

		$this->full_name = $bp->displayed_user->fullname;

		// Fetch parent content for activity comments so we do not have to query in the loop
		foreach ( (array)$this->activities as $activity ) {
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

		if ( (int)$this->total_activity_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'acpage', '%#%' ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_activity_count / (int)$this->pag_num ),
				'current'   => (int)$this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1
			) );
		}
	}

	function has_activities() {
		if ( $this->activity_count )
			return true;

		return false;
	}

	function next_activity() {
		$this->current_activity++;
		$this->activity = $this->activities[$this->current_activity];

		return $this->activity;
	}

	function rewind_activities() {
		$this->current_activity = -1;
		if ( $this->activity_count > 0 ) {
			$this->activity = $this->activities[0];
		}
	}

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

	function the_activity() {
		global $activity;

		$this->in_the_loop = true;
		$this->activity = $this->next_activity();

		if ( is_array( $this->activity ) )
			$this->activity = (object) $this->activity;

		if ( $this->current_activity == 0 ) // loop has just started
			do_action('activity_loop_start');
	}
}

/**
 * bp_has_activities()
 *
 * Initializes the activity loop.
 *
 * Based on the $args passed, bp_has_activities() populates the $activities_template global.
 *
 * @package BuddyPress Activity
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 *
 * @param mixed $args Arguments for limiting the contents of the activity loop. Can be passed as an associative array or as a URL argument string
 * @return bool Returns true when activities are found
 */
function bp_has_activities( $args = '' ) {
	global $bp, $activities_template;

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
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	// Group filtering
	if ( !empty( $bp->groups->current_group ) ) {
		$object = $bp->groups->id;
		$primary_id = $bp->groups->current_group->id;

		if ( 'public' != $bp->groups->current_group->status && ( groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) || $bp->loggedin_user->is_super_admin ) )
			$show_hidden = true;
	}

	// The default scope should recognize custom slugs
	if ( array_key_exists( $bp->current_action, (array)$bp->active_components ) )
		$scope = $bp->active_components[$bp->current_action];
	else
		$scope = $bp->current_action;

	// Support for permalinks on single item pages: /groups/my-group/activity/124/
	if ( $bp->current_action == $bp->activity->slug )
		$include = $bp->action_variables[0];

	// Note: any params used for filtering can be a single value, or multiple values comma separated.
	$defaults = array(
		'display_comments' => 'threaded',   // false for none, stream/threaded - show comments in the stream or threaded under items
		'include'          => $include,     // pass an activity_id or string of IDs comma-separated
		'exclude'          => $exclude,     // pass an activity_id or string of IDs comma-separated
		'in'               => $in,          // comma-separated list or array of activity IDs among which to search
		'sort'             => 'DESC',       // sort DESC or ASC
		'page'             => 1,            // which page to load
		'per_page'         => 20,           // number of items per page
		'max'              => false,        // max number to return
		'show_hidden'      => $show_hidden, // Show activity items that are hidden site-wide?

		// Scope - pre-built activity filters for a user (friends/groups/favorites/mentions)
		'scope'            => $scope,

		// Filtering
		'user_id'          => $user_id,     // user_id to filter on
		'object'           => $object,      // object to filter on e.g. groups, profile, status, friends
		'action'           => false,        // action to filter on e.g. activity_update, new_forum_post, profile_updated
		'primary_id'       => $primary_id,  // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		'secondary_id'     => false,        // secondary object ID to filter on e.g. a post_id

		// Searching
		'search_terms'     => false         // specify terms to search on
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	// If you have passed a "scope" then this will override any filters you have passed.
	if ( 'just-me' == $scope || 'friends' == $scope || 'groups' == $scope || 'favorites' == $scope || 'mentions' == $scope ) {
		if ( 'just-me' == $scope )
			$display_comments = 'stream';

		// determine which user_id applies
		if ( empty( $user_id ) )
			$user_id = ( !empty( $bp->displayed_user->id ) ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

		// are we displaying user specific activity?
		if ( is_numeric( $user_id ) ) {
			$show_hidden = ( $user_id == $bp->loggedin_user->id && $scope != 'friends' ) ? 1 : 0;

			switch ( $scope ) {
				case 'friends':
					if ( function_exists( 'friends_get_friend_user_ids' ) )
						$friends = friends_get_friend_user_ids( $user_id );
						if ( empty( $friends ) )
							return false;

						$user_id = implode( ',', (array)$friends );
					break;
				case 'groups':
					if ( function_exists( 'groups_get_user_groups' ) ) {
						$groups = groups_get_user_groups( $user_id );
						if ( empty( $groups['groups'] ) )
							return false;

						$object = $bp->groups->id;
						$primary_id = implode( ',', (array)$groups['groups'] );

						$user_id = 0;
					}
					break;
				case 'favorites':
					$favs = bp_activity_get_user_favorites( $user_id );
					if ( empty( $favs ) )
						return false;

					$include = implode( ',', (array)$favs );
					break;
				case 'mentions':
					$user_nicename = ( !empty( $bp->displayed_user->id ) ) ? $bp->displayed_user->userdata->user_nicename : $bp->loggedin_user->userdata->user_nicename;
					$user_login = ( !empty( $bp->displayed_user->id ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
					$search_terms = '@' . bp_core_get_username( $user_id, $user_nicename, $user_login ) . '<'; // Start search at @ symbol and stop search at closing tag delimiter.
					$display_comments = 'stream';
					$user_id = 0;
					break;
			}
		}
	}

	// Do not exceed the maximum per page
	if ( !empty( $max ) && ( (int)$per_page > (int)$max ) )
		$per_page = $max;

	// Support for basic filters in earlier BP versions.
	if ( isset( $_GET['afilter'] ) )
		$filter = array( 'object' => $_GET['afilter'] );
	else if ( !empty( $user_id ) || !empty( $object ) || !empty( $action ) || !empty( $primary_id ) || !empty( $secondary_id ) )
		$filter = array( 'user_id' => $user_id, 'object' => $object, 'action' => $action, 'primary_id' => $primary_id, 'secondary_id' => $secondary_id );
	else
		$filter = false;

	$activities_template = new BP_Activity_Template( $page, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments, $show_hidden, $exclude, $in );

	return apply_filters( 'bp_has_activities', $activities_template->has_activities(), $activities_template );
}

function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

function bp_activity_pagination_count() {
	echo bp_get_activity_pagination_count();
}
	function bp_get_activity_pagination_count() {
		global $bp, $activities_template;

		$start_num = intval( ( $activities_template->pag_page - 1 ) * $activities_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $activities_template->pag_num - 1 ) > $activities_template->total_activity_count ) ? $activities_template->total_activity_count : $start_num + ( $activities_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $activities_template->total_activity_count );

		return sprintf( __( 'Viewing item %1$s to %2$s (of %3$s items)', 'buddypress' ), $from_num, $to_num, $total ) . ' &nbsp; <span class="ajax-loader"></span>';
	}

function bp_activity_pagination_links() {
	echo bp_get_activity_pagination_links();
}
	function bp_get_activity_pagination_links() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_pagination_links', $activities_template->pag_links );
	}

/**
 * Returns true when there are more activity items to be shown than currently appear
 *
 * @package BuddyPress Activity
 * @since 1.3
 *
 * @global $activities_template The activity data loop object created in bp_has_activities()
 */
function bp_activity_has_more_items() {
	global $activities_template;
	
	$remaining_pages = floor( ( $activities_template->total_activity_count - 1 ) / ( $activities_template->pag_num * $activities_template->pag_page ) ); 
	$has_more_items  = (int)$remaining_pages ? true : false;
	
	return apply_filters( 'bp_activity_has_more_items', $has_more_items );
}

function bp_activity_count() {
	echo bp_get_activity_count();
}
	function bp_get_activity_count() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_count', (int)$activities_template->activity_count );
	}

function bp_activity_per_page() {
	echo bp_get_activity_per_page();
}
	function bp_get_activity_per_page() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_per_page', (int)$activities_template->pag_num );
	}

function bp_activities_title() {
	global $bp_activity_title;

	echo bp_get_activities_title();
}
	function bp_get_activities_title() {
		global $bp_activity_title;

		return apply_filters( 'bp_get_activities_title', $bp_activity_title );
	}

function bp_activities_no_activity() {
	global $bp_activity_no_activity;

	echo bp_get_activities_no_activity();
}
	function bp_get_activities_no_activity() {
		global $bp_activity_no_activity;

		return apply_filters( 'bp_get_activities_no_activity', $bp_activity_no_activity );
	}

function bp_activity_id() {
	echo bp_get_activity_id();
}
	function bp_get_activity_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_id', $activities_template->activity->id );
	}

function bp_activity_item_id() {
	echo bp_get_activity_item_id();
}
	function bp_get_activity_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_item_id', $activities_template->activity->item_id );
	}

function bp_activity_secondary_item_id() {
	echo bp_get_activity_secondary_item_id();
}
	function bp_get_activity_secondary_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_secondary_item_id', $activities_template->activity->secondary_item_id );
	}

function bp_activity_date_recorded() {
	echo bp_get_activity_date_recorded();
}
	function bp_get_activity_date_recorded() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_date_recorded', $activities_template->activity->date_recorded );
	}

function bp_activity_object_name() {
	echo bp_get_activity_object_name();
}
	function bp_get_activity_object_name() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_object_name', $activities_template->activity->component );
	}

function bp_activity_type() {
	echo bp_get_activity_type();
}
	function bp_get_activity_type() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_type', $activities_template->activity->type );
	}
	function bp_activity_action_name() { echo bp_activity_type(); }
	function bp_get_activity_action_name() { return bp_get_activity_type(); }

function bp_activity_user_id() {
	echo bp_get_activity_user_id();
}
	function bp_get_activity_user_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_user_id', $activities_template->activity->user_id );
	}

function bp_activity_user_link() {
	echo bp_get_activity_user_link();
}
	function bp_get_activity_user_link() {
		global $activities_template;
		
		if ( empty( $activities_template->activity->user_id ) )
			$link = $activities_template->activity->primary_link;
		else
			$link = bp_core_get_user_domain( $activities_template->activity->user_id, $activities_template->activity->user_nicename, $activities_template->activity->user_login );

		return apply_filters( 'bp_get_activity_user_link', $link );
	}

/**
 * bp_activity_avatar( $args )
 *
 * Output the avatar of the user that performed the action
 *
 * @param array $args
 */
function bp_activity_avatar( $args = '' ) {
	echo bp_get_activity_avatar( $args );
}
	/**
	 * bp_get_activity_avatar( $args )
	 *
	 * Return the avatar of the user that performed the action
	 *
	 * @global array $bp
	 * @global object $activities_template
	 * @param array $args optional
	 * @return string
	 */
	function bp_get_activity_avatar( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'type'   => 'thumb',
			'width'  => 20,
			'height' => 20,
			'class'  => 'avatar',
			'alt'    => __( 'Profile picture of %s', 'buddypress' ),
			'email'  => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Primary activity avatar is always a user, but can be modified via a filter
		$object  = apply_filters( 'bp_get_activity_avatar_object_' . $activities_template->activity->component, 'user' );
		$item_id = apply_filters( 'bp_get_activity_avatar_item_id', $activities_template->activity->user_id );

		// If this is a user object pass the users' email address for Gravatar so we don't have to refetch it.
		if ( 'user' == $object && empty( $email ) && isset( $activities_template->activity->user_email ) )
			$email = $activities_template->activity->user_email;

		return apply_filters( 'bp_get_activity_avatar', bp_core_fetch_avatar( array( 'item_id' => $item_id, 'object' => $object, 'type' => $type, 'alt' => $alt, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $email ) ) );
	}

/**
 * bp_activity_secondary_avatar( $args )
 *
 * Output the avatar of the object that action was performed on
 *
 * @param array $args optional
 */
function bp_activity_secondary_avatar( $args = '' ) {
	echo bp_get_activity_secondary_avatar( $args );
}
	/**
	 * bp_get_activity_secondary_avatar( $args )
	 *
	 * Return the avatar of the object that action was performed on
	 *
	 * @global array $bp
	 * @global object $activities_template
	 * @param array $args optional
	 * @return string
	 */
	function bp_get_activity_secondary_avatar( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'type'   => 'thumb',
			'width'  => 20,
			'height' => 20,
			'class'  => 'avatar',
			'email'  => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Set item_id and object (default to user)
		switch ( $activities_template->activity->component ) {
			case 'groups' :
				$object = 'group';
				$item_id = $activities_template->activity->item_id;

				if ( empty( $alt ) )
					$alt = __( 'Group logo of %s', 'buddypress' );

				break;
			case 'blogs' :
				$object = 'blog';
				$item_id = $activities_template->activity->item_id;

				if ( !$alt )
					$alt = sprintf( __( 'Blog authored by %s', 'buddypress' ), get_blog_option( $item_id, 'blogname' ) );

				break;
			case 'friends' :
				$object  = 'user';
				$item_id = $activities_template->activity->secondary_item_id;

				if ( empty( $alt ) )
					$alt = __( 'Profile picture of %s', 'buddypress' );

				break;
			default :
				$object  = 'user';
				$item_id = $activities_template->activity->user_id;
				$email = $activities_template->activity->user_email;

				if ( !$alt )
					$alt = __( 'Profile picture of %s', 'buddypress' );

				break;
		}

		// Allow object and item_id to be filtered
		$object  = apply_filters( 'bp_get_activity_secondary_avatar_object_' . $activities_template->activity->component, $object );
		$item_id = apply_filters( 'bp_get_activity_secondary_avatar_item_id', $item_id );

		// If we have no item_id or object, there is no avatar to display
		if ( empty( $item_id ) || empty( $object ) )
			return false;

		return apply_filters( 'bp_get_activity_secondary_avatar', bp_core_fetch_avatar( array( 'item_id' => $item_id, 'object' => $object, 'type' => $type, 'alt' => $alt, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $email ) ) );
	}

function bp_activity_action() {
	echo bp_get_activity_action();
}
	function bp_get_activity_action() {
		global $activities_template;

		$action = $activities_template->activity->action;
		$action = apply_filters_ref_array( 'bp_get_activity_action_pre_meta', array( $action, &$activities_template->activity ) );

		if ( !empty( $action ) )
			$action = bp_insert_activity_meta( $action );

		return apply_filters_ref_array( 'bp_get_activity_action', array( $action, &$activities_template->activity ) );
	}

function bp_activity_content_body() {
	echo bp_get_activity_content_body();
}
	function bp_get_activity_content_body() {
		global $activities_template;

		// Backwards compatibility if action is not being used
		if ( empty( $activities_template->activity->action ) && !empty( $activities_template->activity->content ) )
			$activities_template->activity->content = bp_insert_activity_meta( $activities_template->activity->content );

		return apply_filters_ref_array( 'bp_get_activity_content_body', array( $activities_template->activity->content, &$activities_template->activity ) );
	}

	function bp_activity_has_content() {
		global $activities_template;

		if ( !empty( $activities_template->activity->content ) )
			return true;

		return false;
	}

function bp_activity_content() {
	echo bp_get_activity_content();
}
	function bp_get_activity_content() {
		global $activities_template;

		/***
		 * If you want to filter activity update content, please use
		 * the filter 'bp_get_activity_content_body'
		 *
		 * This function is mainly for backwards comptibility.
		 */

		$content = bp_get_activity_action() . ' ' . bp_get_activity_content_body();
		return apply_filters( 'bp_get_activity_content', $content );
	}

	function bp_insert_activity_meta( $content ) {
		global $activities_template, $bp;

		// Strip any legacy time since placeholders -- TODO: Remove this in 1.3
		$content = str_replace( '<span class="time-since">%s</span>', '', $content );

		// Insert the time since.
		$content .= ' ' . apply_filters_ref_array( 'bp_activity_time_since', array( '<span class="time-since">' . sprintf( __( '&nbsp; %s ago', 'buddypress' ), bp_core_time_since( $activities_template->activity->date_recorded ) ) . '</span>', &$activities_template->activity ) );

		// Insert the permalink
		if ( !bp_is_single_activity() )
			$content .= apply_filters_ref_array( 'bp_activity_permalink', array( ' &middot; <a href="' . bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ) . '" class="view" title="' . __( 'View Thread / Permalink', 'buddypress' ) . '">' . __( 'View', 'buddypress' ) . '</a>', &$activities_template->activity ) );

		// Add the delete link if the user has permission on this item
		if ( bp_activity_user_can_delete() )
			 $content .= apply_filters_ref_array( 'bp_activity_delete_link', array( ' &middot; ' . bp_get_activity_delete_link(), &$activities_template->activity ) );

		return apply_filters( 'bp_insert_activity_meta', $content );
	}

function bp_activity_user_can_delete() {
	global $activities_template, $bp;

	$can_delete = false;

	if ( $bp->loggedin_user->is_super_admin )
		$can_delete = true;
	
	if ( $activities_template->activity->user_id == $bp->loggedin_user->id )
		$can_delete = true;
		
	if ( $bp->is_item_admin && $bp->is_single_item )
		$can_delete = true;
	
	return apply_filters( 'bp_activity_user_can_delete', $can_delete );
}

function bp_activity_parent_content( $args = '' ) {
	echo bp_get_activity_parent_content($args);
}
	function bp_get_activity_parent_content( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'hide_user' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Get the ID of the parent activity content
		if ( !$parent_id = $activities_template->activity->item_id )
			return false;

		// Get the content of the parent
		if ( empty( $activities_template->activity_parents[$parent_id] ) )
			return false;

		if ( empty( $activities_template->activity_parents[$parent_id]->content ) )
			$content = $activities_template->activity_parents[$parent_id]->action;
		else
			$content = $activities_template->activity_parents[$parent_id]->action . ' ' . $activities_template->activity_parents[$parent_id]->content;

		// Remove the time since content for backwards compatibility
		$content = str_replace( '<span class="time-since">%s</span>', '', $content );

		// Remove images
		$content = preg_replace( '/<img[^>]*>/Ui', '', $content );

		return apply_filters( 'bp_get_activity_parent_content', $content );
	}

function bp_activity_is_favorite() {
	echo bp_get_activity_is_favorite();
}
	function bp_get_activity_is_favorite() {
		global $bp, $activities_template;

 		return apply_filters( 'bp_get_activity_is_favorite', in_array( $activities_template->activity->id, (array)$activities_template->my_favs ) );
	}

function bp_activity_comments( $args = '' ) {
	echo bp_activity_get_comments( $args );
}
	function bp_activity_get_comments( $args = '' ) {
		global $activities_template, $bp;

		if ( !isset( $activities_template->activity->children ) || !$activities_template->activity->children )
			return false;

		$comments_html = bp_activity_recurse_comments( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comments', $comments_html );
	}
		// TODO: The HTML in this function is temporary and will be moved
		// to the template in a future version
		function bp_activity_recurse_comments( $comment ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return false;

			$content = '<ul>';
			foreach ( (array)$comment->children as $comment_child ) {
				if ( empty( $comment_child->user_fullname ) )
					$comment_child->user_fullname = $comment_child->display_name;

				if ( empty( $comment_child->user_nicename ) )
					$comment_child->user_nicename = '';

				if ( empty( $comment_child->user_login ) )
					$comment_child->user_login = '';

				if ( empty( $comment_child->user_email ) )
					$comment_child->user_email = '';

				$content .= '<li id="acomment-' . $comment_child->id . '">';
				$content .= '<div class="acomment-avatar"><a href="' .
					bp_core_get_user_domain(
						$comment_child->user_id,
						$comment_child->user_nicename,
						$comment_child->user_login ) . '">' .
					bp_core_fetch_avatar( array(
						'item_id' => $comment_child->user_id,
						'width'   => 30,
						'height'  => 30,
						'email'   => $comment_child->user_email
					) ) .
				'</a></div>';

				$content .= '<div class="acomment-meta"><a href="' .
					bp_core_get_user_domain(
						$comment_child->user_id,
						$comment_child->user_nicename,
						$comment_child->user_login ) . '">' .
					apply_filters( 'bp_acomment_name', $comment_child->user_fullname, $comment_child ) .
				'</a> &middot; ' . sprintf( __( '%s ago', 'buddypress' ), bp_core_time_since( $comment_child->date_recorded ) );

				// Reply link - the span is so that threaded reply links can be
				// hidden when JS is off.
				if ( is_user_logged_in() && bp_activity_can_comment_reply( $comment ) )
					$content .= apply_filters( 'bp_activity_comment_reply_link', '<span class="acomment-replylink"> &middot; <a href="#acomment-' . $comment->id . '" class="acomment-reply" id="acomment-reply-' . $activities_template->activity->id . '">' . __( 'Reply', 'buddypress' ) . '</a></span>', $comment );

				// Delete link
				if ( $bp->loggedin_user->is_super_admin || $bp->loggedin_user->id == $comment->user_id ) {
					$delete_url = wp_nonce_url( bp_get_root_domain() . '/' . $bp->activity->slug . '/delete/?cid=' . $comment->id, 'bp_activity_delete_link' );
					$content .= apply_filters( 'bp_activity_comment_delete_link', ' &middot; <a href="' . $delete_url . '" class="delete acomment-delete" rel="nofollow">' . __( 'Delete', 'buddypress' ) . '</a>', $comment, $delete_url );
				}

				$content .= '</div>';
				$content .= '<div class="acomment-content">' . apply_filters( 'bp_get_activity_content', $comment->content ) . '</div>';

				$content .= bp_activity_recurse_comments( $comment_child );
				$content .= '</li>';
			}
			$content .= '</ul>';

			return apply_filters( 'bp_activity_recurse_comments', $content );
		}

function bp_activity_comment_count() {
	echo bp_activity_get_comment_count();
}
	function bp_activity_get_comment_count( $args = '' ) {
		global $activities_template, $bp;

		if ( !isset( $activities_template->activity->children ) || !$activities_template->activity->children )
			return 0;

		$count = bp_activity_recurse_comment_count( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comment_count', (int)$count );
	}
		function bp_activity_recurse_comment_count( $comment, $count = 0 ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return $count;

			foreach ( (array)$comment->children as $comment ) {
				$count++;
				$count = bp_activity_recurse_comment_count( $comment, $count );
			}

			return $count;
		}

function bp_activity_comment_link() {
	echo bp_get_activity_comment_link();
}
	function bp_get_activity_comment_link() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_comment_link', '?ac=' . $activities_template->activity->id . '/#ac-form-' . $activities_template->activity->id );
	}

function bp_activity_comment_form_nojs_display() {
	echo bp_get_activity_comment_form_nojs_display();
}
	function bp_get_activity_comment_form_nojs_display() {
		global $activities_template;
		if ( isset( $_GET['ac'] ) && $_GET['ac'] == $activities_template->activity->id . '/' )
			return 'style="display: block"';

		return false;
	}

function bp_activity_comment_form_action() {
	echo bp_get_activity_comment_form_action();
}
	function bp_get_activity_comment_form_action() {
		global $bp;

		return apply_filters( 'bp_get_activity_comment_form_action', home_url( $bp->activity->root_slug . '/reply/' ) );
	}

function bp_activity_permalink_id() {
	echo bp_get_activity_permalink_id();
}
	function bp_get_activity_permalink_id() {
		global $bp;

		return apply_filters( 'bp_get_activity_permalink_id', $bp->current_action );
	}

function bp_activity_thread_permalink() {
	echo bp_get_activity_thread_permalink();
}
	function bp_get_activity_thread_permalink() {
		global $bp, $activities_template;

		$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );

	 	return apply_filters( 'bp_get_activity_thread_permalink', $link );
	}

function bp_activity_favorite_link() {
	echo bp_get_activity_favorite_link();
}
	function bp_get_activity_favorite_link() {
		global $bp, $activities_template;
		return apply_filters( 'bp_get_activity_favorite_link', wp_nonce_url( home_url( $bp->activity->root_slug . '/favorite/' . $activities_template->activity->id . '/' ), 'mark_favorite' ) );
	}

function bp_activity_unfavorite_link() {
	echo bp_get_activity_unfavorite_link();
}
	function bp_get_activity_unfavorite_link() {
		global $bp, $activities_template;
		return apply_filters( 'bp_get_activity_unfavorite_link', wp_nonce_url( home_url( $bp->activity->root_slug . '/unfavorite/' . $activities_template->activity->id . '/' ), 'unmark_favorite' ) );
	}

function bp_activity_css_class() {
	echo bp_get_activity_css_class();
}
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

		$class = '';
		if ( in_array( $activities_template->activity->type, (array)$mini_activity_actions ) || empty( $activities_template->activity->content ) )
			$class = ' mini';

		if ( bp_activity_get_comment_count() && bp_activity_can_comment() )
			$class .= ' has-comments';

		return apply_filters( 'bp_get_activity_css_class', $activities_template->activity->component . ' ' . $activities_template->activity->type . $class );
	}

function bp_activity_delete_link() {
	echo bp_get_activity_delete_link();
}
	function bp_get_activity_delete_link() {
		global $activities_template, $bp;

		return apply_filters( 'bp_get_activity_delete_link', '<a href="' . wp_nonce_url( bp_get_root_domain() . '/' . $bp->activity->slug . '/delete/' . $activities_template->activity->id, 'bp_activity_delete_link' ) . '" class="item-button delete-activity confirm" rel="nofollow">' . __( 'Delete', 'buddypress' ) . '</a>' );
	}

function bp_activity_latest_update( $user_id = 0 ) {
	echo bp_get_activity_latest_update( $user_id );
}
	function bp_get_activity_latest_update( $user_id = 0 ) {
		global $bp;

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		if ( !$update = get_user_meta( $user_id, 'bp_latest_update', true ) )
			return false;

		$latest_update = '&quot;' . apply_filters( 'bp_get_activity_latest_update_excerpt', trim( strip_tags( bp_create_excerpt( $update['content'], 180 ) ) ) ) . '&quot;';
		$latest_update .= ' &middot; <a href="' . bp_get_root_domain() . '/' . $bp->activity->root_slug . '/p/' . $update['id'] . '/"> ' . __( 'View', 'buddypress' ) . '</a>';

		return apply_filters( 'bp_get_activity_latest_update', $latest_update  );
	}

function bp_activity_filter_links( $args = false ) {
	echo bp_get_activity_filter_links( $args );
}
	function bp_get_activity_filter_links( $args = false ) {
		global $activities_template, $bp;

		$defaults = array(
			'style' => 'list'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Fetch the names of components that have activity recorded in the DB
		$components = BP_Activity_Activity::get_recorded_components();

		if ( !$components )
			return false;

		foreach ( (array) $components as $component ) {
			/* Skip the activity comment filter */
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

			// Make sure all core internal component names are translatable
			$translatable_components = array( __( 'xprofile', 'buddypress'), __( 'friends', 'buddypress' ), __( 'groups', 'buddypress' ), __( 'status', 'buddypress' ), __( 'blogs', 'buddypress' ) );

			$component_links[] = $before . '<a href="' . esc_attr( $link ) . '">' . ucwords( __( $component, 'buddypress' ) ) . '</a>' . $after;
		}

		$link = remove_query_arg( 'afilter' , $link );

		if ( isset( $_GET['afilter'] ) )
			$component_links[] = '<' . $tag . ' id="afilter-clear"><a href="' . esc_attr( $link ) . '"">' . __( 'Clear Filter', 'buddypress' ) . '</a></' . $tag . '>';

 		return apply_filters( 'bp_get_activity_filter_links', implode( "\n", $component_links ) );
	}

function bp_activity_can_comment() {
	global $activities_template, $bp;

	$can_comment = true;

	if ( false === $activities_template->disable_blogforum_replies || (int)$activities_template->disable_blogforum_replies ) {
		if ( 'new_blog_post' == bp_get_activity_action_name() || 'new_blog_comment' == bp_get_activity_action_name() || 'new_forum_topic' == bp_get_activity_action_name() || 'new_forum_post' == bp_get_activity_action_name() )
			$can_comment = false;
	}

	if ( 'activity_comment' == bp_get_activity_action_name() )
		$can_comment = false;

	return apply_filters( 'bp_activity_can_comment', $can_comment );
}

function bp_activity_can_comment_reply( $comment ) {
	$can_comment = true;

	return apply_filters( 'bp_activity_can_comment_reply', $can_comment, $comment );
}

function bp_activity_can_favorite() {
	$can_favorite = true;

	return apply_filters( 'bp_activity_can_favorite', $can_favorite );
}

function bp_total_favorite_count_for_user( $user_id = 0 ) {
	echo bp_get_total_favorite_count_for_user( $user_id );
}
	function bp_get_total_favorite_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_favorite_count_for_user', bp_activity_total_favorites_for_user( $user_id ) );
	}

function bp_total_mention_count_for_user( $user_id = 0 ) {
	echo bp_get_total_favorite_count_for_user( $user_id );
}
	function bp_get_total_mention_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_mention_count_for_user', get_user_meta( $user_id, 'bp_new_mention_count', true ) );
	}

function bp_send_public_message_link() {
	echo bp_get_send_public_message_link();
}
	function bp_get_send_public_message_link() {
		global $bp;

		if ( bp_is_my_profile() || !is_user_logged_in() )
			return false;

		return apply_filters( 'bp_get_send_public_message_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->activity->slug . '/?r=' . bp_core_get_username( $bp->displayed_user->id, $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login ) ) );
	}

function bp_mentioned_user_display_name( $user_id_or_username ) {
	echo bp_get_mentioned_user_display_name( $user_id_or_username );
}
	function bp_get_mentioned_user_display_name( $user_id_or_username ) {
		if ( !$name = bp_core_get_user_displayname( $user_id_or_username ) )
			$name = __( 'a user', 'buddypress' );

		return apply_filters( 'bp_get_mentioned_user_display_name', $name, $user_id_or_username );
	}

/**
 * bp_send_public_message_button( $args )
 *
 * Output button for sending a public message
 *
 * @param array $args
 */
function bp_send_public_message_button( $args = '' ) {
	echo bp_get_send_public_message_button( $args );
}
	/**
	 * bp_get_send_public_message_button( $args )
	 *
	 * Return button for sending a public message
	 *
	 * @param array $args
	 * @return string
	 */
	function bp_get_send_public_message_button( $args = '' ) {
		$defaults = array(
			'id'                => 'public_message',
			'component'         => 'activity',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_id'        => 'post-mention',
			'link_href'         => bp_get_send_public_message_link(),
			'link_title'        => __( 'Mention this user in a new public message, this will send the user a notification to get their attention.', 'buddypress' ),
			'link_text'         => __( 'Mention this User', 'buddypress' ),
			'link_class'        => 'activity-button mention'
		);

		$button = wp_parse_args( $args, $defaults );

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_send_public_message_button', $button ) );
	}

function bp_activity_post_form_action() {
	echo bp_get_activity_post_form_action();
}
	function bp_get_activity_post_form_action() {
		return apply_filters( 'bp_get_activity_post_form_action', home_url( bp_get_activity_root_slug() . '/post/' ) );
	}

/* RSS Feed Template Tags ***************************/

function bp_sitewide_activity_feed_link() {
	echo bp_get_sitewide_activity_feed_link();
}
	function bp_get_sitewide_activity_feed_link() {
		return apply_filters( 'bp_get_sitewide_activity_feed_link', home_url( bp_get_activity_root_slug() . '/feed/' ) );
	}

function bp_member_activity_feed_link() {
	echo bp_get_member_activity_feed_link();
}
function bp_activities_member_rss_link() {
	echo bp_get_member_activity_feed_link();
}

	function bp_get_member_activity_feed_link() {
		global $bp;

		if ( $bp->current_component == $bp->profile->slug || 'just-me' == $bp->current_action )
			$link = $bp->displayed_user->domain . $bp->activity->slug . '/feed/';
		elseif ( $bp->friends->slug == $bp->current_action )
			$link = $bp->displayed_user->domain . $bp->activity->slug . '/' . $bp->friends->slug . '/feed/';
		elseif ( $bp->groups->slug == $bp->current_action )
			$link = $bp->displayed_user->domain . $bp->activity->slug . '/' . $bp->groups->slug . '/feed/';
		elseif ( 'favorites' == $bp->current_action )
			$link = $bp->displayed_user->domain . $bp->activity->slug . '/favorites/feed/';
		elseif ( 'mentions' == $bp->current_action )
			$link = $bp->displayed_user->domain . $bp->activity->slug . '/mentions/feed/';
		else
			$link = '';

		return apply_filters( 'bp_get_activities_member_rss_link', $link );
	}
	function bp_get_activities_member_rss_link() { return bp_get_member_activity_feed_link(); }


/** Template tags for RSS feed output *****************************************/

function bp_activity_feed_item_guid() {
	echo bp_get_activity_feed_item_guid();
}
	function bp_get_activity_feed_item_guid() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_guid', md5( $activities_template->activity->date_recorded . '-' . $activities_template->activity->content ) );
	}

function bp_activity_feed_item_title() {
	echo bp_get_activity_feed_item_title();
}
	function bp_get_activity_feed_item_title() {
		global $activities_template;

		if ( !empty( $activities_template->activity->action ) )
			$content = $activities_template->activity->action;
		else
			$content = $activities_template->activity->content;

		$content = explode( '<span', $content );
		$title = trim( strip_tags( html_entity_decode( utf8_encode( $content[0] ) ) ) );

		if ( ':' == substr( $title, -1 ) )
			$title = substr( $title, 0, -1 );

		if ( 'activity_update' == $activities_template->activity->type )
			$title .= ': ' . strip_tags( bp_create_excerpt( $activities_template->activity->content, 70 ) );

		return apply_filters( 'bp_get_activity_feed_item_title', $title );
	}

function bp_activity_feed_item_link() {
	echo bp_get_activity_feed_item_link();
}
	function bp_get_activity_feed_item_link() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_link', $activities_template->activity->primary_link );
	}

function bp_activity_feed_item_date() {
	echo bp_get_activity_feed_item_date();
}
	function bp_get_activity_feed_item_date() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_date', $activities_template->activity->date_recorded );
	}

function bp_activity_feed_item_description() {
	echo bp_get_activity_feed_item_description();
}
	function bp_get_activity_feed_item_description() {
		global $activities_template;

		if ( empty( $activities_template->activity->action ) )
			$content = $activities_template->activity->content;
		else
			$content = $activities_template->activity->action . ' ' . $activities_template->activity->content;

		return apply_filters( 'bp_get_activity_feed_item_description', html_entity_decode( str_replace( '%s', '', $content ) ) );
	}

/**
 * Template tag so we can hook activity feed to <head>
 *
 * @since 1.3
 */
function bp_activity_sitewide_feed() {
?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php _e( 'Site Wide Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_sitewide_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_activity_sitewide_feed' );

?>
