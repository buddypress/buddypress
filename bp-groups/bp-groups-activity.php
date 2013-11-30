<?php

/**
 * BuddyPress Groups Activity Functions
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage GroupsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register activity actions for the Groups component.
 */
function groups_register_activity_actions() {
	global $bp;

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	bp_activity_set_action( $bp->groups->id, 'created_group',   __( 'Created a group',       'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'joined_group',    __( 'Joined a group',        'buddypress' ) );

	// These actions are for the legacy forums
	// Since the bbPress plugin also shares the same 'forums' identifier, we also
	// check for the legacy forums loader class to be extra cautious
	if ( bp_is_active( 'forums' ) && class_exists( 'BP_Forums_Component' ) ) {
		bp_activity_set_action( $bp->groups->id, 'new_forum_topic', __( 'New group forum topic', 'buddypress' ) );
		bp_activity_set_action( $bp->groups->id, 'new_forum_post',  __( 'New group forum post',  'buddypress' ) );
	}

	do_action( 'groups_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'groups_register_activity_actions' );

/**
 * Record an activity item related to the Groups component.
 *
 * A wrapper for {@link bp_activity_add()} that provides some Groups-specific
 * defaults.
 *
 * @see bp_activity_add() for more detailed description of parameters and
 *      return values.
 *
 * @param array $args {
 *     An array of arguments for the new activity item. Accepts all parameters
 *     of {@link bp_activity_add()}. However, this wrapper provides some
 *     additional defaults, as described below:
 *     @type string $component Default: the id of your Groups component
 *           (usually 'groups').
 *     @type bool $hide_sitewide Default: True if the current group is not
 *           public, otherwise false.
 * }
 * @return bool See {@link bp_activity_add()}.
 */
function groups_record_activity( $args = '' ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Set the default for hide_sitewide by checking the status of the group
	$hide_sitewide = false;
	if ( !empty( $args['item_id'] ) ) {
		if ( bp_get_current_group_id() == $args['item_id'] ) {
			$group = groups_get_current_group();
		} else {
			$group = groups_get_group( array( 'group_id' => $args['item_id'] ) );
		}

		if ( isset( $group->status ) && 'public' != $group->status ) {
			$hide_sitewide = true;
		}
	}

	$r = wp_parse_args( $args, array(
		'id'                => false,
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => buddypress()->groups->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => $hide_sitewide
	) );

	return bp_activity_add( $r );
}

/**
 * Update the last_activity meta value for a given group.
 *
 * @param int $group_id Optional. The ID of the group whose last_activity is
 *        being updated. Default: the current group's ID.
 */
function groups_update_last_activity( $group_id = 0 ) {

	if ( empty( $group_id ) ) {
		$group_id = buddypress()->groups->current_group->id;
	}

	if ( empty( $group_id ) ) {
		return false;
	}

	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );
}
add_action( 'groups_leave_group',          'groups_update_last_activity' );
add_action( 'groups_created_group',        'groups_update_last_activity' );
add_action( 'groups_new_forum_topic',      'groups_update_last_activity' );
add_action( 'groups_new_forum_topic_post', 'groups_update_last_activity' );

/**
 * Add an activity stream item when a member joins a group
 *
 * @since BuddyPress (1.9.0)
 * @param int $user_id
 * @param int $group_id
 */
function bp_groups_membership_accepted_add_activity( $user_id, $group_id ) {

	// Bail if Activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Get the group so we can get it's name
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	// Record in activity streams
	groups_record_activity( array(
		'action'  => apply_filters_ref_array( 'groups_activity_membership_accepted_action', array( sprintf( __( '%1$s joined the group %2$s', 'buddypress' ), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' ), $user_id, &$group ) ),
		'type'    => 'joined_group',
		'item_id' => $group_id,
		'user_id' => $user_id
	) );	
}
add_action( 'groups_membership_accepted', 'bp_groups_membership_accepted_add_activity', 10, 2 );

/**
 * Delete all group activity from activity streams
 *
 * @since BuddyPress (1.9.0)
 */
function bp_groups_delete_group_delete_all_activity( $group_id ) {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_delete_by_item_id( array(
			'item_id'   => $group_id,
			'component' => buddypress()->groups->id
		) );
	}
}
add_action( 'groups_delete_group', 'bp_groups_delete_group_delete_all_activity', 10 );

/**
 * Delete group member activity if they leave or are removed within 5 minutes of
 * membership modification.
 *
 * If the user joined this group less than five minutes ago, remove the
 * joined_group activity so users cannot flood the activity stream by
 * joining/leaving the group in quick succession.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_groups_leave_group_delete_recent_activity( $group_id, $user_id ) {

	// Bail if Activity component is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Get the member's group membership information
	$membership = new BP_Groups_Member( $user_id, $group_id );

	// Check the time period, and maybe delete their recent group activity
	if ( time() <= strtotime( '+5 minutes', (int) strtotime( $membership->date_modified ) ) ) {
		bp_activity_delete( array(
			'component' => buddypress()->groups->id,
			'type'      => 'joined_group',
			'user_id'   => $user_id,
			'item_id'   => $group_id
		) );
	}
}
add_action( 'groups_leave_group',   'bp_groups_leave_group_delete_recent_activity', 10, 2 );
add_action( 'groups_remove_member', 'bp_groups_leave_group_delete_recent_activity', 10, 2 );
add_action( 'groups_ban_member',    'bp_groups_leave_group_delete_recent_activity', 10, 2 );