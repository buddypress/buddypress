<?php

/**
 * BuddyPress XProfile Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity items and
 * notifications for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage XProfileActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function xprofile_register_activity_actions() {
	global $bp;

	// Register the activity stream actions for this component
	bp_activity_set_action(
		// older avatar activity items use 'profile' for component
		// see r4273
		'profile',

		'new_avatar',
		__( 'Member changed profile picture', 'buddypress' ),
		'bp_xprofile_format_activity_action_new_avatar'
	);

	bp_activity_set_action(
		$bp->profile->id,
		'new_member',
		__( 'New member registered', 'buddypress' ),
		'bp_xprofile_format_activity_action_new_member'
	);

	bp_activity_set_action(
		$bp->profile->id,
		'updated_profile',
		__( 'Updated Profile', 'buddypress' ),
		'bp_xprofile_format_activity_action_updated_profile'
	);

	do_action( 'xprofile_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'xprofile_register_activity_actions' );

/**
 * Format 'new_avatar' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_xprofile_format_activity_action_new_avatar( $action, $activity ) {
	$userlink = bp_core_get_userlink( $activity->user_id );
	$action   = sprintf( __( '%s changed their profile picture', 'buddypress' ), $userlink );

	// Legacy filter - pass $user_id instead of $activity
	if ( has_filter( 'bp_xprofile_new_avatar_action' ) ) {
		$action = apply_filters( 'bp_xprofile_new_avatar_action', $action, $activity->user_id );
	}

	return apply_filters( 'bp_xprofile_format_activity_action_new_avatar', $action, $activity );
}

/**
 * Format 'new_member' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_xprofile_format_activity_action_new_member( $action, $activity ) {
	$userlink = bp_core_get_userlink( $activity->user_id );
	$action   = sprintf( __( '%s became a registered member', 'buddypress' ), $userlink );

	// Legacy filter - pass $user_id instead of $activity
	if ( has_filter( 'bp_core_activity_registered_member_action' ) ) {
		$action = apply_filters( 'bp_core_activity_registered_member_action', $action, $activity->user_id );
	}

	return apply_filters( 'bp_xprofile_format_activity_action_new_member', $action, $activity );
}

/**
 * Format 'updated_profile' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_xprofile_format_activity_action_updated_profile( $action, $activity ) {
	// Note for translators: The natural phrasing in English, "Joe updated
	// his profile", requires that we know Joe's gender, which we don't. If
	// your language doesn't have this restriction, feel free to use a more
	// natural translation.
	$profile_link = trailingslashit( bp_core_get_user_domain( $activity->user_id ) . buddypress()->profile->slug );
	$action	      = sprintf( __( '%s&#8217;s profile was updated', 'buddypress' ), '<a href="' . $profile_link . '">' . bp_core_get_user_displayname( $activity->user_id ) . '</a>' );

	return apply_filters( 'bp_xprofile_format_activity_action_updated_profile', $action, $activity );
}

/**
 * Records activity for the logged in user within the profile component so that
 * it will show in the users activity stream (if installed)
 *
 * @package BuddyPress
 * @subpackage XProfileActivity
 * @param string $args String containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_current_times()
 * @uses bp_activity_add() Adds an entry to the activity component tables for a specific activity
 */
function xprofile_record_activity( $args = '' ) {

	if ( !bp_is_active( 'activity' ) )
		return false;

	global $bp;

	$defaults = array (
		'user_id'           => bp_loggedin_user_id(),
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
 * @param string $args Containing all variables used after extract() call
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses bp_activity_delete() Deletes an entry to the activity component tables for a specific activity
 */
function xprofile_delete_activity( $args = '' ) {

	if ( ! bp_is_active( 'activity' ) )
		return false;

	global $bp;

	extract( $args );

	bp_activity_delete_by_item_id( array(
		'item_id'           => $item_id,
		'component'         => $bp->profile->id,
		'type'              => $type,
		'user_id'           => $user_id,
		'secondary_item_id' => $secondary_item_id
	) );
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
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses bp_activity_add() Adds an entry to the activity component tables for a specific activity
 */
function bp_xprofile_new_avatar_activity() {

	if ( !bp_is_active( 'activity' ) )
		return false;

	$user_id = apply_filters( 'bp_xprofile_new_avatar_user_id', bp_displayed_user_id() );

	$userlink = bp_core_get_userlink( $user_id );

	bp_activity_add( array(
		'user_id' => $user_id,
		'component' => 'profile',
		'type' => 'new_avatar'
	) );
}
add_action( 'xprofile_avatar_uploaded', 'bp_xprofile_new_avatar_activity' );

/**
 * Add an activity item when a user has updated his profile.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $user_id ID of the user who has updated his profile.
 * @param array $field_ids IDs of the fields submitted.
 * @param bool $errors True if validation or saving errors occurred, otherwise
 *        false.
 * @param array $old_values Pre-save xprofile field values and visibility
 *        levels.
 * @param array $new_values Post-save xprofile field values and visibility
 *        levels.
 * @return bool True on success, false on failure.
 */
function bp_xprofile_updated_profile_activity( $user_id, $field_ids, $errors, $old_values = array(), $new_values = array() ) {
	// If there were errors, don't post
	if ( ! empty( $errors ) ) {
		return false;
	}

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Don't post if there have been no changes, or if the changes are
	// related solely to non-public fields
	$public_changes = false;
	foreach ( $new_values as $field_id => $new_value ) {
		$old_value            = isset( $old_values[ $field_id ] ) ? $old_values[ $field_id ] : '';
		$old_value_value      = isset( $old_value['value'] ) ? $old_value['value'] : '';
		$old_value_visibility = isset( $old_value['visibility'] ) ? $old_value['visibility'] : '';

		// Don't register changes to private fields
		if ( 'public' !== $new_value['visibility'] ) {
			continue;
		}

		// Don't register if there have been no changes
		if ( $new_value === $old_value ) {
			continue;
		}

		// Looks like we have public changes - no need to keep checking
		$public_changes = true;
		break;
	}

	if ( ! $public_changes ) {
		return false;
	}

	// Throttle to one activity of this type per 2 hours
	$existing = bp_activity_get( array(
		'max' => 1,
		'filter' => array(
			'user_id' => $user_id,
			'object'  => buddypress()->profile->id,
			'action'  => 'updated_profile',
		),
	) );

	if ( empty( $existing['activities'] ) ) {
		$throttle = false;
	} else {
		// Default throttle time is 2 hours. Filter to change (in seconds)
		$throttle_period = apply_filters( 'bp_xprofile_updated_profile_activity_throttle_time', 60 * 60 * 2 );
		$then = strtotime( $existing['activities'][0]->date_recorded );
		$now  = strtotime( bp_core_current_time() );

		$throttle = ( $now - $then ) < $throttle_period;
	}

	if ( $throttle ) {
		return false;
	}

	// If we've reached this point, assemble and post the activity item
	$profile_link = trailingslashit( bp_core_get_user_domain( $user_id ) . buddypress()->profile->slug );

	$retval = xprofile_record_activity( array(
		'user_id'      => $user_id,
		'primary_link' => $profile_link,
		'component'    => buddypress()->profile->id,
		'type'         => 'updated_profile',
	) );

	return (bool) $retval;
}
add_action( 'xprofile_updated_profile', 'bp_xprofile_updated_profile_activity', 10, 5 );

/**
 * Add filters for xprofile activity types to Show dropdowns.
 *
 * @since BuddyPress (2.0.0)
 */
function xprofile_activity_filter_options() {
	?>

	<option value="updated_profile"><?php _e( 'Profile Updates', 'buddypress' ) ?></option>

	<?php
}
add_action( 'bp_activity_filter_options', 'xprofile_activity_filter_options' );
