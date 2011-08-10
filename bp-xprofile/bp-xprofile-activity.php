<?php
/**
 * BuddyPress XProfile Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage XProfile
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function xprofile_register_activity_actions() {
	global $bp;

	if ( bp_is_active( 'activity' ) )
		return false;

	// Register the activity stream actions for this component
	bp_activity_set_action( $bp->profile->id, 'new_member',      __( 'New member registered', 'buddypress' ) );
	bp_activity_set_action( $bp->profile->id, 'updated_profile', __( 'Updated Profile',       'buddypress' ) );

	do_action( 'xprofile_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'xprofile_register_activity_actions' );

/**
 * Records activity for the logged in user within the profile component so that
 * it will show in the users activity stream (if installed)
 *
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_current_times()
 * @uses bp_activity_record() Adds an entry to the activity component tables for a specific activity
 */
function xprofile_record_activity( $args = '' ) {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	$defaults = array (
		'user_id'           => $bp->loggedin_user->id,
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => $bp->profile->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array(
		'user_id'           => $user_id,
		'action'            => $action,
		'content'           => $content,
		'primary_link'      => $primary_link,
		'component'         => $component,
		'type'              => $type,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id,
		'recorded_time'     => $recorded_time,
		'hide_sitewide'     => $hide_sitewide
	) );
}

/**
 * Deletes activity for a user within the profile component so that
 * it will be removed from the users activity stream and sitewide stream (if installed)
 *
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global object $bp Global BuddyPress settings object
 * @uses bp_activity_delete() Deletes an entry to the activity component tables for a specific activity
 */
function xprofile_delete_activity( $args = '' ) {
	global $bp;

	if ( bp_is_active( 'activity' ) ) {

		extract( $args );

		bp_activity_delete_by_item_id( array(
			'item_id'           => $item_id,
			'component'         => $bp->profile->id,
			'type'              => $type,
			'user_id'           => $user_id,
			'secondary_item_id' => $secondary_item_id
		) );
	}
}

function xprofile_register_activity_action( $key, $value ) {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	return apply_filters( 'xprofile_register_activity_action', bp_activity_set_action( $bp->profile->id, $key, $value ), $key, $value );
}

/**
 * Adds an activity stream item when a user has uploaded a new avatar.
 *
 * @package BuddyPress XProfile
 * @global object $bp Global BuddyPress settings object
 * @uses bp_activity_add() Adds an entry to the activity component tables for a specific activity
 */
function bp_xprofile_new_avatar_activity() {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	$user_id = apply_filters( 'bp_xprofile_new_avatar_user_id', $bp->displayed_user->id );

	$userlink = bp_core_get_userlink( $user_id );

	bp_activity_add( array(
		'user_id' => $user_id,
		'action' => apply_filters( 'bp_xprofile_new_avatar_action', sprintf( __( '%s changed their profile picture', 'buddypress' ), $userlink ), $user_id ),
		'component' => 'profile',
		'type' => 'new_avatar'
	) );
}
add_action( 'xprofile_avatar_uploaded', 'bp_xprofile_new_avatar_activity' );
?>