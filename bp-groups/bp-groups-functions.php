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
	$defaults = array(
		'group_id'   => false,
		'load_users' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$cache_key = 'bp_groups_group_' . $group_id . ( $load_users ? '_load_users' : '_noload_users' );

	if ( !$group = wp_cache_get( $cache_key, 'bp' ) ) {
		$group = new BP_Groups_Group( $group_id, true, $load_users );
		wp_cache_set( $cache_key, $group, 'bp' );
	}

	return apply_filters( 'groups_get_group', $group );
}

/*** Group Creation, Editing & Deletion *****************************************/

function groups_create_group( $args = '' ) {

	extract( $args );

	/**
	 * Possible parameters (pass as assoc array):
	 *	'group_id'
	 *	'creator_id'
	 *	'name'
	 *	'description'
	 *	'slug'
	 *	'status'
	 *	'enable_forum'
	 *	'date_created'
	 */

	if ( !empty( $group_id ) )
		$group = groups_get_group( array( 'group_id' => $group_id ) );
	else
		$group = new BP_Groups_Group;

	if ( !empty( $creator_id ) )
		$group->creator_id = $creator_id;
	else
		$group->creator_id = bp_loggedin_user_id();

	if ( isset( $name ) )
		$group->name = $name;

	if ( isset( $description ) )
		$group->description = $description;

	if ( isset( $slug ) && groups_check_slug( $slug ) )
		$group->slug = $slug;

	if ( isset( $status ) ) {
		if ( groups_is_valid_status( $status ) ) {
			$group->status = $status;
		}
	}

	if ( isset( $enable_forum ) )
		$group->enable_forum = $enable_forum;
	else if ( empty( $group_id ) && !isset( $enable_forum ) )
		$group->enable_forum = 1;

	if ( isset( $date_created ) )
		$group->date_created = $date_created;

	if ( !$group->save() )
		return false;

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
	global $bp;

	// Check the user is the group admin.
	if ( ! bp_is_item_admin() )
		return false;

	do_action( 'groups_before_delete_group', $group_id );

	// Get the group object
	$group = groups_get_group( array( 'group_id' => $group_id ) );
	if ( !$group->delete() )
		return false;

	// Delete all group activity from activity streams
	if ( bp_is_active( 'activity' ) )
		bp_activity_delete_by_item_id( array( 'item_id' => $group_id, 'component' => $bp->groups->id ) );

	// Remove all outstanding invites for this group
	groups_delete_all_group_invites( $group_id );

	// Remove all notifications for any user belonging to this group
	bp_core_delete_all_notifications_by_type( $group_id, $bp->groups->id );

	do_action( 'groups_delete_group', $group_id);

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

	$membership = new BP_Groups_Member( $user_id, $group_id );

	// This is exactly the same as deleting an invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $user_id, $group_id ) )
		return false;

	/**
	 * If the user joined this group less than five minutes ago, remove the
	 * joined_group activity so users cannot flood the activity stream by
	 * joining/leaving the group in quick succession.
	 */
	if ( bp_is_active( 'activity' ) && gmmktime() <= strtotime( '+5 minutes', (int)strtotime( $membership->date_modified ) ) )
		bp_activity_delete( array( 'component' => $bp->groups->id, 'type' => 'joined_group', 'user_id' => $user_id, 'item_id' => $group_id ) );

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
		'action'  => apply_filters( 'groups_activity_joined_group', sprintf( __( '%1$s joined the group %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( bp_get_group_name( $group ) ) . '</a>' ) ),
		'type'    => 'joined_group',
		'item_id' => $group_id,
		'user_id' => $user_id
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
 * Fetch the members of a group
 *
 * Since BuddyPress 1.8, a procedural wrapper for BP_Group_Member_Query.
 * Previously called BP_Groups_Member::get_all_for_group().
 *
 * To use the legacy query, filter 'bp_use_legacy_group_member_query',
 * returning true.
 *
 * @param int $group_id
 * @param int $limit Maximum members to return
 * @param int $page The page of results to return (requires $limit)
 * @param bool $exclude_admins_mods Whether to exclude admins and mods
 * @param bool $exclude_banned Whether to exclude banned users
 * @param array|string $exclude Array or comma-sep list of users to exclude
 * @return array Multi-d array of 'members' list and 'count'
 */
function groups_get_group_members( $group_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true, $exclude = false, $group_role = false ) {

	// For legacy users. Use of BP_Groups_Member::get_all_for_group()
	// is deprecated. func_get_args() can't be passed to a function in PHP
	// 5.2.x, so we create a variable
	$func_args = func_get_args();
	if ( apply_filters( 'bp_use_legacy_group_member_query', false, __FUNCTION__, $func_args ) ) {
		$retval = BP_Groups_Member::get_all_for_group( $group_id, $limit, $page, $exclude_admins_mods, $exclude_banned, $exclude );
	} else {

		// exclude_admins_mods and exclude_banned are legacy arguments.
		// Convert to group_role
		if ( empty( $group_role ) ) {
			$group_role = array( 'member' );

			if ( ! $exclude_admins_mods ) {
				$group_role[] = 'mod';
				$group_role[] = 'admin';
			}

			if ( ! $exclude_banned ) {
				$group_role[] = 'banned';
			}
		}

		// Perform the group member query (extends BP_User_Query)
		$members = new BP_Group_Member_Query( array(
			'group_id'       => $group_id,
			'per_page'       => $limit,
			'page'           => $page,
			'group_role'     => $group_role,
			'exclude'        => $exclude,
			'type'           => 'last_modified',
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
		'type'            => false,    // active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts
		'order'           => 'DESC',   // 'ASC' or 'DESC'
		'orderby'         => 'date_created', // date_created, last_activity, total_member_count, name, random
		'user_id'         => false,    // Pass a user_id to limit to only groups that this user is a member of
		'include'         => false,    // Only include these specific groups (group_ids)
		'exclude'         => false,    // Do not include these specific groups (group_ids)
		'search_terms'    => false,    // Limit to groups that match these search terms
		'meta_query'      => false,    // Filter by groupmeta. See WP_Meta_Query for syntax
		'show_hidden'     => false,    // Show hidden groups to non-admins
		'per_page'        => 20,       // The number of results to return per page
		'page'            => 1,        // The page to return if limiting per page
		'populate_extras' => true,     // Fetch meta such as is_banned and is_member
	);

	$r = wp_parse_args( $args, $defaults );

	$groups = BP_Groups_Group::get( array(
		'type'            => $r['type'],
		'user_id'         => $r['user_id'],
		'include'         => $r['include'],
		'exclude'         => $r['exclude'],
		'search_terms'    => $r['search_terms'],
		'meta_query'      => $r['meta_query'],
		'show_hidden'     => $r['show_hidden'],
		'per_page'        => $r['per_page'],
		'page'            => $r['page'],
		'populate_extras' => $r['populate_extras'],
		'order'           => $r['order'],
		'orderby'         => $r['orderby'],
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

	if ( !groups_is_user_member( $user_id, $group_id ) && !groups_check_user_has_invite( $user_id, $group_id, 'all' ) ) {
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
	global $bp;

	// If the user is already a member (because BP at one point allowed two invitations to
	// slip through), delete all existing invitations/requests and return true
	if ( groups_is_user_member( $user_id, $group_id ) ) {
		if ( groups_check_user_has_invite( $user_id, $group_id ) )
			groups_delete_invite( $user_id, $group_id );

		if ( groups_check_for_membership_request( $user_id, $group_id ) )
			groups_delete_membership_request( $user_id, $group_id );

		return true;
	}

	$member = new BP_Groups_Member( $user_id, $group_id );
	$member->accept_invite();

	if ( !$member->save() )
		return false;

	// Remove request to join
	if ( $member->check_for_membership_request( $user_id, $group_id ) )
		$member->delete_request( $user_id, $group_id );

	// Modify group meta
	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );

	bp_core_delete_notifications_by_item_id( $user_id, $group_id, $bp->groups->id, 'group_invite' );

	do_action( 'groups_accept_invite', $user_id, $group_id );
	return true;
}

function groups_reject_invite( $user_id, $group_id ) {
	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_reject_invite', $user_id, $group_id );

	return true;
}

function groups_delete_invite( $user_id, $group_id ) {
	global $bp;

	$delete = BP_Groups_Member::delete_invite( $user_id, $group_id );

	if ( $delete )
		bp_core_delete_notifications_by_item_id( $user_id, $group_id, $bp->groups->id, 'group_invite' );

	return $delete;
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

	if ( !empty( $user_id ) && !empty( $group_id ) )
		$membership = new BP_Groups_Member( $user_id, $group_id );
	else
		$membership = new BP_Groups_Member( false, false, $membership_id );

	$membership->accept_request();

	if ( !$membership->save() )
		return false;

	// Check if the user has an outstanding invite, if so delete it.
	if ( groups_check_user_has_invite( $membership->user_id, $membership->group_id ) )
		groups_delete_invite( $membership->user_id, $membership->group_id );

	// Record this in activity streams
	$group = groups_get_group( array( 'group_id' => $membership->group_id ) );

	groups_record_activity( array(
		'action'  => apply_filters_ref_array( 'groups_activity_membership_accepted_action', array( sprintf( __( '%1$s joined the group %2$s', 'buddypress'), bp_core_get_userlink( $membership->user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' ), $membership->user_id, &$group ) ),
		'type'    => 'joined_group',
		'item_id' => $membership->group_id,
		'user_id' => $membership->user_id
	) );

	// Send a notification to the user.
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, true );

	do_action( 'groups_membership_accepted', $membership->user_id, $membership->group_id );

	return true;
}

function groups_reject_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {
	if ( !$membership = groups_delete_membership_request( $membership_id, $user_id, $group_id ) )
		return false;

	// Send a notification to the user.
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, false );

	do_action( 'groups_membership_rejected', $membership->user_id, $membership->group_id );

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

function groups_delete_groupmeta( $group_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	if ( !is_numeric( $group_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_array( $meta_value ) || is_object( $meta_value ) )
		$meta_value = serialize($meta_value);

	$meta_value = trim( $meta_value );

	if ( !$meta_key )
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d", $group_id ) );
	else if ( $meta_value )
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s AND meta_value = %s", $group_id, $meta_key, $meta_value ) );
	else
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key ) );

	// Delete the cached object
	wp_cache_delete( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, 'bp' );

	return true;
}

function groups_get_groupmeta( $group_id, $meta_key = '') {
	global $wpdb, $bp;

	$group_id = (int) $group_id;

	if ( !$group_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

		$metas = wp_cache_get( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, 'bp' );
		if ( false === $metas ) {
			$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key ) );
			wp_cache_set( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, $metas, 'bp' );
		}
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d", $group_id ) );
	}

	if ( empty( $metas ) ) {
		if ( empty( $meta_key ) )
			return array();
		else
			return '';
	}

	$metas = array_map( 'maybe_unserialize', (array) $metas );

	if ( 1 == count( $metas ) )
		return $metas[0];
	else
		return $metas;
}

function groups_update_groupmeta( $group_id, $meta_key, $meta_value ) {
	global $wpdb, $bp;

	if ( !is_numeric( $group_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_string( $meta_value ) )
		$meta_value = stripslashes( esc_sql( $meta_value ) );

	$meta_value = maybe_serialize( $meta_value );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key ) );

	if ( !$cur )
		$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp->groups->table_name_groupmeta . " ( group_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $group_id, $meta_key, $meta_value ) );
	else if ( $cur->meta_value != $meta_value )
		$wpdb->query( $wpdb->prepare( "UPDATE " . $bp->groups->table_name_groupmeta . " SET meta_value = %s WHERE group_id = %d AND meta_key = %s", $meta_value, $group_id, $meta_key ) );
	else
		return false;

	// Update the cached object and recache
	wp_cache_set( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, $meta_value, 'bp' );

	return true;
}

/*** Group Cleanup Functions ****************************************************/

function groups_remove_data_for_user( $user_id ) {
	global $bp;

	BP_Groups_Member::delete_all_for_user( $user_id );

	bp_core_delete_notifications_from_user( $user_id, $bp->groups->id, 'new_membership_request' );

	do_action( 'groups_remove_data_for_user', $user_id );
}
add_action( 'wpmu_delete_user',  'groups_remove_data_for_user' );
add_action( 'delete_user',       'groups_remove_data_for_user' );
add_action( 'bp_make_spam_user', 'groups_remove_data_for_user' );
