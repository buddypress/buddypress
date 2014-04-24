<?php

/**
 * BuddyPress Groups Functions
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyPress
 * @subpackage GroupsFunctions
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
function bp_groups_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->groups->id );
}

/**
 * Pulls up the database object corresponding to a group
 *
 * When calling up a group object, you should always use this function instead
 * of instantiating BP_Groups_Group directly, so that you will inherit cache
 * support and pass through the groups_get_group filter.
 *
 * @param string $args The load_users parameter is deprecated and does nothing.
 * @return BP_Groups_Group $group The group object
 */
function groups_get_group( $args = '' ) {
	$r = wp_parse_args( $args, array(
		'group_id'          => false,
		'load_users'        => false,
		'populate_extras'   => false,
	) );

	$group_args = array(
		'populate_extras' => $r['populate_extras'],
	);

	$group = new BP_Groups_Group( $r['group_id'], $group_args );

	return apply_filters( 'groups_get_group', $group );
}

/*** Group Creation, Editing & Deletion *****************************************/

/**
 * Create a group.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {
 *     An array of arguments.
 *     @type int|bool $group_id Pass a group ID to update an existing item, or
 *           0 / false to create a new group. Default: 0.
 *     @type int $creator_id The user ID that creates the group.
 *     @type string $name The group name.
 *     @type string $description Optional. The group's description.
 *     @type string $slug The group slug.
 *     @type string $status The group's status. Accepts 'public', 'private' or
             'hidden'. Defaults to 'public'.
 *     @type int $enable_forum Optional. Whether the group has a forum enabled.
 *           If the legacy forums are enabled for this group or if a bbPress
 *           forum is enabled for the group, set this to 1. Default: 0.
 *     @type string $date_created The GMT time, in Y-m-d h:i:s format,
 *           when the group was created. Defaults to the current time.
 * }
 * @return int|bool The ID of the group on success. False on error.
 */
function groups_create_group( $args = '' ) {

	$defaults = array(
		'group_id'     => 0,
		'creator_id'   => 0,
		'name'         => '',
		'description'  => '',
		'slug'         => '',
		'status'       => 'public',
		'enable_forum' => 0,
		'date_created' => bp_core_current_time()
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	// Pass an existing group ID
	if ( ! empty( $group_id ) ) {
		$group = groups_get_group( array( 'group_id' => (int) $group_id ) );
		$name  = ! empty( $name ) ? $name : $group->name;
		$slug  = ! empty( $slug ) ? $slug : $group->slug;
		$description = ! empty( $description ) ? $description : $group->description;

		// Groups need at least a name
		if ( empty( $name ) ) {
			return false;
		}

	// Create a new group
	} else {
		// Instantiate new group object
		$group = new BP_Groups_Group;
	}

	// Set creator ID
	if ( ! empty( $creator_id ) ) {
		$group->creator_id = (int) $creator_id;
	} else {
		$group->creator_id = bp_loggedin_user_id();
	}

	// Validate status
	if ( ! groups_is_valid_status( $status ) ) {
		return false;
	}

	// Set group name
	$group->name         = $name;
	$group->description  = $description;
	$group->slug         = $slug;
	$group->status       = $status;
	$group->enable_forum = (int) $enable_forum;
	$group->date_created = $date_created;

	// Save group
	if ( ! $group->save() ) {
		return false;
	}

	// If this is a new group, set up the creator as the first member and admin
	if ( empty( $group_id ) ) {
		$member                = new BP_Groups_Member;
		$member->group_id      = $group->id;
		$member->user_id       = $group->creator_id;
		$member->is_admin      = 1;
		$member->user_title    = __( 'Group Admin', 'buddypress' );
		$member->is_confirmed  = 1;
		$member->date_modified = bp_core_current_time();
		$member->save();

		groups_update_groupmeta( $group->id, 'last_activity', bp_core_current_time() );

		do_action( 'groups_create_group', $group->id, $member, $group );

	} else {
		do_action( 'groups_update_group', $group->id, $group );
	}

	do_action( 'groups_created_group', $group->id, $group );

	return $group->id;
}

function groups_edit_base_group_details( $group_id, $group_name, $group_desc, $notify_members ) {

	if ( empty( $group_name ) || empty( $group_desc ) )
		return false;

	$group              = groups_get_group( array( 'group_id' => $group_id ) );
	$group->name        = $group_name;
	$group->description = $group_desc;

	if ( !$group->save() )
		return false;

	if ( $notify_members ) {
		groups_notification_group_updated( $group->id );
	}

	do_action( 'groups_details_updated', $group->id );

	return true;
}

function groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status = false ) {

	$group = groups_get_group( array( 'group_id' => $group_id ) );
	$group->enable_forum = $enable_forum;

	/***
	 * Before we potentially switch the group status, if it has been changed to public
	 * from private and there are outstanding membership requests, auto-accept those requests.
	 */
	if ( 'private' == $group->status && 'public' == $status )
		groups_accept_all_pending_membership_requests( $group->id );

	// Now update the status
	$group->status = $status;

	if ( !$group->save() )
		return false;

	// If forums have been enabled, and a forum does not yet exist, we need to create one.
	if ( $group->enable_forum ) {
		if ( bp_is_active( 'forums' ) && !groups_get_groupmeta( $group->id, 'forum_id' ) ) {
			groups_new_group_forum( $group->id, $group->name, $group->description );
		}
	}

	// Set the invite status
	if ( $invite_status )
		groups_update_groupmeta( $group->id, 'invite_status', $invite_status );

	groups_update_groupmeta( $group->id, 'last_activity', bp_core_current_time() );
	do_action( 'groups_settings_updated', $group->id );

	return true;
}

/**
 * Delete a group and all of its associated meta
 *
 * @global object $bp BuddyPress global settings
 * @param int $group_id
 * @since BuddyPress (1.0)
 */
function groups_delete_group( $group_id ) {

	do_action( 'groups_before_delete_group', $group_id );

	// Get the group object
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	// Bail if group cannot be deleted
	if ( ! $group->delete() ) {
		return false;
	}

	// Remove all outstanding invites for this group
	groups_delete_all_group_invites( $group_id );

	do_action( 'groups_delete_group', $group_id );

	return true;
}

function groups_is_valid_status( $status ) {
	global $bp;

	return in_array( $status, (array) $bp->groups->valid_status );
}

function groups_check_slug( $slug ) {
	global $bp;

	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );

	if ( in_array( $slug, (array) $bp->groups->forbidden_names ) )
		$slug = $slug . '-' . rand();

	if ( BP_Groups_Group::check_slug( $slug ) ) {
		do {
			$slug = $slug . '-' . rand();
		}
		while ( BP_Groups_Group::check_slug( $slug ) );
	}

	return $slug;
}

/**
 * Get a group slug by its ID
 *
 * @param int $group_id The numeric ID of the group
 * @return string The group's slug
 */
function groups_get_slug( $group_id ) {
	$group = groups_get_group( array( 'group_id' => $group_id ) );
	return !empty( $group->slug ) ? $group->slug : '';
}

/**
 * Get a group ID by its slug
 *
 * @since BuddyPress (1.6)
 *
 * @param string $group_slug The group's slug
 * @return int The ID
 */
function groups_get_id( $group_slug ) {
	return (int)BP_Groups_Group::group_exists( $group_slug );
}

/*** User Actions ***************************************************************/

function groups_leave_group( $group_id, $user_id = 0 ) {
	global $bp;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Don't let single admins leave the group.
	if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {
		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			bp_core_add_message( __( 'As the only Admin, you cannot leave the group.', 'buddypress' ), 'error' );
			return false;
		}
	}

	// This is exactly the same as deleting an invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $user_id, $group_id ) ) {
		return false;
	}

	bp_core_add_message( __( 'You successfully left the group.', 'buddypress' ) );

	do_action( 'groups_leave_group', $group_id, $user_id );

	return true;
}

function groups_join_group( $group_id, $user_id = 0 ) {
	global $bp;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Check if the user has an outstanding invite. If so, delete it.
	if ( groups_check_user_has_invite( $user_id, $group_id ) )
		groups_delete_invite( $user_id, $group_id );

	// Check if the user has an outstanding request. If so, delete it.
	if ( groups_check_for_membership_request( $user_id, $group_id ) )
		groups_delete_membership_request( $user_id, $group_id );

	// User is already a member, just return true
	if ( groups_is_user_member( $user_id, $group_id ) )
		return true;

	$new_member                = new BP_Groups_Member;
	$new_member->group_id      = $group_id;
	$new_member->user_id       = $user_id;
	$new_member->inviter_id    = 0;
	$new_member->is_admin      = 0;
	$new_member->user_title    = '';
	$new_member->date_modified = bp_core_current_time();
	$new_member->is_confirmed  = 1;

	if ( !$new_member->save() )
		return false;

	if ( !isset( $bp->groups->current_group ) || !$bp->groups->current_group || $group_id != $bp->groups->current_group->id )
		$group = groups_get_group( array( 'group_id' => $group_id ) );
	else
		$group = $bp->groups->current_group;

	// Record this in activity streams
	groups_record_activity( array(
		'type'    => 'joined_group',
		'item_id' => $group_id,
		'user_id' => $user_id,
	) );

	// Modify group meta
	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );

	do_action( 'groups_join_group', $group_id, $user_id );

	return true;
}

/*** General Group Functions ****************************************************/

function groups_get_group_admins( $group_id ) {
	return BP_Groups_Member::get_group_administrator_ids( $group_id );
}

function groups_get_group_mods( $group_id ) {
	return BP_Groups_Member::get_group_moderator_ids( $group_id );
}

/**
 * Fetch the members of a group.
 *
 * Since BuddyPress 1.8, a procedural wrapper for BP_Group_Member_Query.
 * Previously called BP_Groups_Member::get_all_for_group().
 *
 * To use the legacy query, filter 'bp_use_legacy_group_member_query',
 * returning true.
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int $group_id ID of the group whose members are being queried.
 *           Default: current group ID.
 *     @type int $page Page of results to be queried. Default: 1.
 *     @type int $per_page Number of items to return per page of results.
 *           Default: 20.
 *     @type int $max Optional. Max number of items to return.
 *     @type array $exclude Optional. Array of user IDs to exclude.
 *     @type bool|int True (or 1) to exclude admins and mods from results.
 *           Default: 1.
 *     @type bool|int True (or 1) to exclude banned users from results.
 *           Default: 1.
 *     @type array $group_role Optional. Array of group roles to include.
 *     @type string $search_terms Optional. Filter results by a search string.
 *     @type string $type Optional. Sort the order of results. 'last_joined',
 *           'first_joined', or any of the $type params available in
 *           {@link BP_User_Query}. Default: 'last_joined'.
 * }
 * @return array Multi-d array of 'members' list and 'count'.
 */
function groups_get_group_members( $args = array() ) {

	// Backward compatibility with old method of passing arguments
	if ( ! is_array( $args ) || func_num_args() > 1 ) {
		_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'group_id',
			1 => 'per_page',
			2 => 'page',
			3 => 'exclude_admins_mods',
			4 => 'exclude_banned',
			5 => 'exclude',
			6 => 'group_role',
		);

		$func_args = func_get_args();
		$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
	}

	$r = wp_parse_args( $args, array(
		'group_id'            => bp_get_current_group_id(),
		'per_page'            => false,
		'page'                => false,
		'exclude_admins_mods' => true,
		'exclude_banned'      => true,
		'exclude'             => false,
		'group_role'          => array(),
		'search_terms'        => false,
		'type'                => 'last_joined',
	) );

	// For legacy users. Use of BP_Groups_Member::get_all_for_group()
	// is deprecated. func_get_args() can't be passed to a function in PHP
	// 5.2.x, so we create a variable
	$func_args = func_get_args();
	if ( apply_filters( 'bp_use_legacy_group_member_query', false, __FUNCTION__, $func_args ) ) {
		$retval = BP_Groups_Member::get_all_for_group( $r['group_id'], $r['per_page'], $r['page'], $r['exclude_admins_mods'], $r['exclude_banned'], $r['exclude'] );
	} else {

		// exclude_admins_mods and exclude_banned are legacy arguments.
		// Convert to group_role
		if ( empty( $r['group_role'] ) ) {
			$r['group_role'] = array( 'member' );

			if ( ! $r['exclude_admins_mods'] ) {
				$r['group_role'][] = 'mod';
				$r['group_role'][] = 'admin';
			}

			if ( ! $r['exclude_banned'] ) {
				$r['group_role'][] = 'banned';
			}
		}

		// Perform the group member query (extends BP_User_Query)
		$members = new BP_Group_Member_Query( array(
			'group_id'       => $r['group_id'],
			'per_page'       => $r['per_page'],
			'page'           => $r['page'],
			'group_role'     => $r['group_role'],
			'exclude'        => $r['exclude'],
			'search_terms'   => $r['search_terms'],
			'type'           => $r['type'],
		) );

		// Structure the return value as expected by the template functions
		$retval = array(
			'members' => array_values( $members->results ),
			'count'   => $members->total_users,
		);
	}

	return $retval;
}

function groups_get_total_member_count( $group_id ) {
	return BP_Groups_Group::get_total_member_count( $group_id );
}

/*** Group Fetching, Filtering & Searching  *************************************/

/**
 * Get a collection of groups, based on the parameters passed
 *
 * @uses apply_filters_ref_array() Filter 'groups_get_groups' to modify return value
 * @uses BP_Groups_Group::get()
 * @param array $args See inline documentation for details
 * @return array
 */
function groups_get_groups( $args = '' ) {

	$defaults = array(
		'type'              => false,    // active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts
		'order'             => 'DESC',   // 'ASC' or 'DESC'
		'orderby'           => 'date_created', // date_created, last_activity, total_member_count, name, random
		'user_id'           => false,    // Pass a user_id to limit to only groups that this user is a member of
		'include'           => false,    // Only include these specific groups (group_ids)
		'exclude'           => false,    // Do not include these specific groups (group_ids)
		'search_terms'      => false,    // Limit to groups that match these search terms
		'meta_query'        => false,    // Filter by groupmeta. See WP_Meta_Query for syntax
		'show_hidden'       => false,    // Show hidden groups to non-admins
		'per_page'          => 20,       // The number of results to return per page
		'page'              => 1,        // The page to return if limiting per page
		'populate_extras'   => true,     // Fetch meta such as is_banned and is_member
		'update_meta_cache' => true,   // Pre-fetch groupmeta for queried groups
	);

	$r = wp_parse_args( $args, $defaults );

	$groups = BP_Groups_Group::get( array(
		'type'              => $r['type'],
		'user_id'           => $r['user_id'],
		'include'           => $r['include'],
		'exclude'           => $r['exclude'],
		'search_terms'      => $r['search_terms'],
		'meta_query'        => $r['meta_query'],
		'show_hidden'       => $r['show_hidden'],
		'per_page'          => $r['per_page'],
		'page'              => $r['page'],
		'populate_extras'   => $r['populate_extras'],
		'update_meta_cache' => $r['update_meta_cache'],
		'order'             => $r['order'],
		'orderby'           => $r['orderby'],
	) );

	return apply_filters_ref_array( 'groups_get_groups', array( &$groups, &$r ) );
}

function groups_get_total_group_count() {
	if ( !$count = wp_cache_get( 'bp_total_group_count', 'bp' ) ) {
		$count = BP_Groups_Group::get_total_group_count();
		wp_cache_set( 'bp_total_group_count', $count, 'bp' );
	}

	return $count;
}

function groups_get_user_groups( $user_id = 0, $pag_num = 0, $pag_page = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	return BP_Groups_Member::get_group_ids( $user_id, $pag_num, $pag_page );
}

function groups_total_groups_for_user( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	if ( !$count = wp_cache_get( 'bp_total_groups_for_user_' . $user_id, 'bp' ) ) {
		$count = BP_Groups_Member::total_group_count( $user_id );
		wp_cache_set( 'bp_total_groups_for_user_' . $user_id, $count, 'bp' );
	}

	return $count;
}

/**
 * Returns the group object for the group currently being viewed
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return BP_Groups_Group The current group object
 */
function groups_get_current_group() {
	global $bp;

	$current_group = isset( $bp->groups->current_group ) ? $bp->groups->current_group : false;

	return apply_filters( 'groups_get_current_group', $current_group );
}

/*** Group Avatars *************************************************************/

function groups_avatar_upload_dir( $group_id = 0 ) {
	global $bp;

	if ( !$group_id )
		$group_id = $bp->groups->current_group->id;

	$path    = bp_core_avatar_upload_path() . '/group-avatars/' . $group_id;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl    = bp_core_avatar_url() . '/group-avatars/' . $group_id;
	$newburl   = $newurl;
	$newsubdir = '/group-avatars/' . $group_id;

	return apply_filters( 'groups_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

/*** Group Member Status Checks ************************************************/

function groups_is_user_admin( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_admin( $user_id, $group_id );
}

function groups_is_user_mod( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_mod( $user_id, $group_id );
}

function groups_is_user_member( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_member( $user_id, $group_id );
}

function groups_is_user_banned( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_banned( $user_id, $group_id );
}

/**
 * Is the specified user the creator of the group?
 *
 * @param int $user_id
 * @param int $group_id
 * @since BuddyPress (1.2.6)
 * @uses BP_Groups_Member
 */
function groups_is_user_creator( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_creator( $user_id, $group_id );
}

/*** Group Activity Posting **************************************************/

function groups_post_update( $args = '' ) {
	global $bp;

	$defaults = array(
		'content'  => false,
		'user_id'  => bp_loggedin_user_id(),
		'group_id' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty( $group_id ) && !empty( $bp->groups->current_group->id ) )
		$group_id = $bp->groups->current_group->id;

	if ( empty( $content ) || !strlen( trim( $content ) ) || empty( $user_id ) || empty( $group_id ) )
		return false;

	$bp->groups->current_group = groups_get_group( array( 'group_id' => $group_id ) );

	// Be sure the user is a member of the group before posting.
	if ( !bp_current_user_can( 'bp_moderate' ) && !groups_is_user_member( $user_id, $group_id ) )
		return false;

	// Record this in activity streams
	$activity_action  = sprintf( __( '%1$s posted an update in the group %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' );
	$activity_content = $content;

	$activity_id = groups_record_activity( array(
		'user_id' => $user_id,
		'action'  => apply_filters( 'groups_activity_new_update_action',  $activity_action  ),
		'content' => apply_filters( 'groups_activity_new_update_content', $activity_content ),
		'type'    => 'activity_update',
		'item_id' => $group_id
	) );

	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );
	do_action( 'bp_groups_posted_update', $content, $user_id, $group_id, $activity_id );

	return $activity_id;
}

/*** Group Invitations *********************************************************/

function groups_get_invites_for_user( $user_id = 0, $limit = false, $page = false, $exclude = false ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	return BP_Groups_Member::get_invites( $user_id, $limit, $page, $exclude );
}

/**
 * Gets the total group invite count for a user.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $user_id The user ID
 * @return int
 */
function groups_get_invite_count_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return BP_Groups_Member::get_invite_count_for_user( $user_id );
}

function groups_invite_user( $args = '' ) {

	$defaults = array(
		'user_id'       => false,
		'group_id'      => false,
		'inviter_id'    => bp_loggedin_user_id(),
		'date_modified' => bp_core_current_time(),
		'is_confirmed'  => 0
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	if ( empty( $user_id ) || empty( $group_id ) )
		return false;

	// if the user has already requested membership, accept the request
	if ( $membership_id = groups_check_for_membership_request( $user_id, $group_id ) ) {
		groups_accept_membership_request( $membership_id, $user_id, $group_id );

	// Otherwise, create a new invitation
	} else if ( ! groups_is_user_member( $user_id, $group_id ) && ! groups_check_user_has_invite( $user_id, $group_id, 'all' ) ) {
		$invite                = new BP_Groups_Member;
		$invite->group_id      = $group_id;
		$invite->user_id       = $user_id;
		$invite->date_modified = $date_modified;
		$invite->inviter_id    = $inviter_id;
		$invite->is_confirmed  = $is_confirmed;

		if ( !$invite->save() )
			return false;

		do_action( 'groups_invite_user', $args );
	}

	return true;
}

function groups_uninvite_user( $user_id, $group_id ) {

	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_uninvite_user', $group_id, $user_id );

	return true;
}

/**
 * Process the acceptance of a group invitation.
 *
 * Returns true if a user is already a member of the group.
 *
 * @param int $user_id
 * @param int $group_id
 * @return bool True when the user is a member of the group, otherwise false
 */
function groups_accept_invite( $user_id, $group_id ) {

	// If the user is already a member (because BP at one point allowed two invitations to
	// slip through), delete all existing invitations/requests and return true
	if ( groups_is_user_member( $user_id, $group_id ) ) {
		if ( groups_check_user_has_invite( $user_id, $group_id ) ) {
			groups_delete_invite( $user_id, $group_id );
		}

		if ( groups_check_for_membership_request( $user_id, $group_id ) ) {
			groups_delete_membership_request( $user_id, $group_id );
		}

		return true;
	}

	$member = new BP_Groups_Member( $user_id, $group_id );
	$member->accept_invite();

	if ( !$member->save() ) {
		return false;
	}

	// Remove request to join
	if ( $member->check_for_membership_request( $user_id, $group_id ) ) {
		$member->delete_request( $user_id, $group_id );
	}

	// Modify group meta
	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );

	do_action( 'groups_accept_invite', $user_id, $group_id );

	return true;
}

function groups_reject_invite( $user_id, $group_id ) {
	if ( ! BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_reject_invite', $user_id, $group_id );

	return true;
}

function groups_delete_invite( $user_id, $group_id ) {
	if ( ! BP_Groups_Member::delete_invite( $user_id, $group_id ) )
		return false;

	do_action( 'groups_delete_invite', $user_id, $group_id );

	return true;
}

function groups_send_invites( $user_id, $group_id ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Send friend invites.
	$invited_users = groups_get_invites_for_group( $user_id, $group_id );
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	for ( $i = 0, $count = count( $invited_users ); $i < $count; ++$i ) {
		$member = new BP_Groups_Member( $invited_users[$i], $group_id );

		// Send the actual invite
		groups_notification_group_invites( $group, $member, $user_id );

		$member->invite_sent = 1;
		$member->save();
	}

	do_action( 'groups_send_invites', $group_id, $invited_users );
}

function groups_get_invites_for_group( $user_id, $group_id ) {
	return BP_Groups_Group::get_invites( $user_id, $group_id );
}

/**
 * Check to see whether a user has already been invited to a group
 *
 * By default, the function checks for invitations that have been sent. Entering 'all' as the $type
 * parameter will return unsent invitations as well (useful to make sure AJAX requests are not
 * duplicated)
 *
 * @package BuddyPress Groups
 *
 * @param int $user_id Potential group member
 * @param int $group_id Potential group
 * @param string $type Optional. Use 'sent' to check for sent invites, 'all' to check for all
 * @return bool Returns true if an invitation is found
 */
function groups_check_user_has_invite( $user_id, $group_id, $type = 'sent' ) {
	return BP_Groups_Member::check_has_invite( $user_id, $group_id, $type );
}

function groups_delete_all_group_invites( $group_id ) {
	return BP_Groups_Group::delete_all_invites( $group_id );
}

/*** Group Promotion & Banning *************************************************/

function groups_promote_member( $user_id, $group_id, $status ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	// Don't use this action. It's deprecated as of BuddyPress 1.6.
	do_action( 'groups_premote_member', $group_id, $user_id, $status );

	// Use this action instead.
	do_action( 'groups_promote_member', $group_id, $user_id, $status );

	return $member->promote( $status );
}

function groups_demote_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_demote_member', $group_id, $user_id );

	return $member->demote();
}

function groups_ban_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_ban_member', $group_id, $user_id );

	return $member->ban();
}

function groups_unban_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_unban_member', $group_id, $user_id );

	return $member->unban();
}

/*** Group Removal *******************************************************/

function groups_remove_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_remove_member', $group_id, $user_id );

	return $member->remove();
}

/*** Group Membership ****************************************************/

function groups_send_membership_request( $requesting_user_id, $group_id ) {

	// Prevent duplicate requests
	if ( groups_check_for_membership_request( $requesting_user_id, $group_id ) )
		return false;

	// Check if the user is already a member or is banned
	if ( groups_is_user_member( $requesting_user_id, $group_id ) || groups_is_user_banned( $requesting_user_id, $group_id ) )
		return false;

	// Check if the user is already invited - if so, simply accept invite
	if ( groups_check_user_has_invite( $requesting_user_id, $group_id ) ) {
		groups_accept_invite( $requesting_user_id, $group_id );
		return true;
	}

	$requesting_user                = new BP_Groups_Member;
	$requesting_user->group_id      = $group_id;
	$requesting_user->user_id       = $requesting_user_id;
	$requesting_user->inviter_id    = 0;
	$requesting_user->is_admin      = 0;
	$requesting_user->user_title    = '';
	$requesting_user->date_modified = bp_core_current_time();
	$requesting_user->is_confirmed  = 0;
	$requesting_user->comments      = isset( $_POST['group-request-membership-comments'] ) ? $_POST['group-request-membership-comments'] : '';

	if ( $requesting_user->save() ) {
		$admins = groups_get_group_admins( $group_id );

		// Saved okay, now send the email notification
		for ( $i = 0, $count = count( $admins ); $i < $count; ++$i )
			groups_notification_new_membership_request( $requesting_user_id, $admins[$i]->user_id, $group_id, $requesting_user->id );

		do_action( 'groups_membership_requested', $requesting_user_id, $admins, $group_id, $requesting_user->id );

		return true;
	}

	return false;
}

function groups_accept_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {

	if ( !empty( $user_id ) && !empty( $group_id ) ) {
		$membership = new BP_Groups_Member( $user_id, $group_id );
	} else {
		$membership = new BP_Groups_Member( false, false, $membership_id );
	}

	$membership->accept_request();

	if ( !$membership->save() ) {
		return false;
	}

	// Check if the user has an outstanding invite, if so delete it.
	if ( groups_check_user_has_invite( $membership->user_id, $membership->group_id ) ) {
		groups_delete_invite( $membership->user_id, $membership->group_id );
	}

	do_action( 'groups_membership_accepted', $membership->user_id, $membership->group_id, true );

	return true;
}

function groups_reject_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {
	if ( !$membership = groups_delete_membership_request( $membership_id, $user_id, $group_id ) ) {
		return false;
	}

	do_action( 'groups_membership_rejected', $membership->user_id, $membership->group_id, false );

	return true;
}

function groups_delete_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {
	if ( !empty( $user_id ) && !empty( $group_id ) )
		$membership = new BP_Groups_Member( $user_id, $group_id );
	else
		$membership = new BP_Groups_Member( false, false, $membership_id );

	if ( !BP_Groups_Member::delete( $membership->user_id, $membership->group_id ) )
		return false;

	return $membership;
}

function groups_check_for_membership_request( $user_id, $group_id ) {
	return BP_Groups_Member::check_for_membership_request( $user_id, $group_id );
}

function groups_accept_all_pending_membership_requests( $group_id ) {
	$user_ids = BP_Groups_Member::get_all_membership_request_user_ids( $group_id );

	if ( !$user_ids )
		return false;

	foreach ( (array) $user_ids as $user_id )
		groups_accept_membership_request( false, $user_id, $group_id );

	do_action( 'groups_accept_all_pending_membership_requests', $group_id );

	return true;
}

/*** Group Meta ****************************************************/

/**
 * Delete metadata for a group.
 *
 * @param int $group_id ID of the group.
 * @param string $meta_key The key of the row to delete.
 * @param string $meta_value Optional. Metadata value. If specified, only delete
 *        metadata entries with this value.
 * @param bool $delete_all Optional. If true, delete matching metadata entries
 *        for all groups. Default: false.
 * @param bool $delete_all Optional. If true, delete matching metadata entries
 * 	  for all objects, ignoring the specified group_id. Otherwise, only
 * 	  delete matching metadata entries for the specified group.
 * 	  Default: false.
 * @return bool True on success, false on failure.
 */
function groups_delete_groupmeta( $group_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Legacy - if no meta_key is passed, delete all for the item
	if ( empty( $meta_key ) ) {
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->groupmeta} WHERE group_id = %d", $group_id ) );

		// With no meta_key, ignore $delete_all
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'group', $group_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of group metadata.
 *
 * @param int $group_id ID of the group.
 * @param string $meta_key Metadata key.
 * @param bool $single Optional. If true, return only the first value of the
 *        specified meta_key. This parameter has no effect if meta_key is
 *        empty.
 * @return mixed Metadata value.
 */
function groups_get_groupmeta( $group_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'group', $group_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of group metadata.
 *
 * @param int $group_id ID of the group.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Value to store.
 * @param mixed $prev_value Optional. If specified, only update existing
 *        metadata entries with the specified value. Otherwise, update all
 *        entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *         metadata, returns true. On successful creation of new metadata,
 *         returns the integer ID of the new metadata row.
 */
function groups_update_groupmeta( $group_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'group', $group_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of group metadata.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $group_id ID of the group.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique. Optional. Whether to enforce a single metadata value
 *        for the given key. If true, and the object already has a value for
 *        the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function groups_add_groupmeta( $group_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'group', $group_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/*** Group Cleanup Functions ****************************************************/

function groups_remove_data_for_user( $user_id ) {
	BP_Groups_Member::delete_all_for_user( $user_id );

	do_action( 'groups_remove_data_for_user', $user_id );
}
add_action( 'wpmu_delete_user',  'groups_remove_data_for_user' );
add_action( 'delete_user',       'groups_remove_data_for_user' );
add_action( 'bp_make_spam_user', 'groups_remove_data_for_user' );
