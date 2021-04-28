<?php
/**
 * BuddyPress Member Activity
 *
 * @package BuddyPress
 * @subpackage MembersActivity
 * @since 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the 'new member' activity type.
 *
 * @since 2.2.0
 *
 */
function bp_members_register_activity_actions() {

	bp_activity_set_action(
		buddypress()->members->id,
		'new_member',
		__( 'New member registered', 'buddypress' ),
		'bp_members_format_activity_action_new_member',
		__( 'New Members', 'buddypress' ),
		array( 'activity' )
	);

	// Register the activity stream actions for this component.
	bp_activity_set_action(
		// Older avatar activity items use 'profile' for component. See r4273.
		buddypress()->members->id,
		'new_avatar',
		__( 'Member changed profile picture', 'buddypress' ),
		'bp_members_format_activity_action_new_avatar',
		__( 'Updated Profile Photos', 'buddypress' )
	);

	/**
	 * Fires after the default 'new member' activity types are registered.
	 *
	 * @since 2.2.0
	 */
	do_action( 'bp_members_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_members_register_activity_actions' );

/**
 * Format 'new_member' activity actions.
 *
 * @since 2.2.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity object.
 * @return string $action
 */
function bp_members_format_activity_action_new_member( $action, $activity ) {
	$userlink         = bp_core_get_userlink( $activity->user_id );
	$inviter_userlink = false;
	$invite_id        = bp_get_user_meta( $activity->user_id, 'accepted_members_invitation', true );

	if ( $invite_id ) {
		$invite = new BP_Invitation( (int) $invite_id );

		if ( $invite->inviter_id ) {
			$inviter_userlink = bp_core_get_userlink( $invite->inviter_id );
		}
	}

	if ( $inviter_userlink ) {
		$action = sprintf(
			/* translators: 1: new user link. 2: inviter user link. */
			esc_html__( '%1$s accepted an invitation from %2$s and became a registered member', 'buddypress' ),
			$userlink,
			$inviter_userlink
		);
	} else {
		/* translators: %s: user link */
		$action = sprintf( esc_html__( '%s became a registered member', 'buddypress' ), $userlink );
	}

	// Legacy filter - pass $user_id instead of $activity.
	if ( has_filter( 'bp_core_activity_registered_member_action' ) ) {
		$action = apply_filters( 'bp_core_activity_registered_member_action', $action, $activity->user_id );
	}

	/**
	 * Filters the formatted 'new member' activity actions.
	 *
	 * @since 2.2.0
	 * @since 8.0.0 Added $invite_id
	 *
	 * @param string $action    Static activity action.
	 * @param object $activity  Activity object.
	 * @param int    $invite_id The ID of the invite.
	 */
	return apply_filters( 'bp_members_format_activity_action_new_member', $action, $activity, $invite_id );
}

/**
 * Format 'new_avatar' activity actions.
 *
 * @since 8.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_members_format_activity_action_new_avatar( $action, $activity ) {
	$userlink = bp_core_get_userlink( $activity->user_id );

	/* translators: %s: user link */
	$action = sprintf( esc_html__( '%s changed their profile picture', 'buddypress' ), $userlink );

	// Legacy filter - pass $user_id instead of $activity.
	if ( has_filter( 'bp_xprofile_new_avatar_action' ) ) {
		$action = apply_filters( 'bp_xprofile_new_avatar_action', $action, $activity->user_id );
	}

	/** This filter is documented in wp-includes/deprecated.php */
	$action = apply_filters_deprecated( 'bp_xprofile_format_activity_action_new_avatar', array( $action, $activity ), '8.0.0', 'bp_members_format_activity_action_new_avatar' );

	/**
	 * Filters the formatted 'new_avatar' activity stream action.
	 *
	 * @since 8.0.0
	 *
	 * @param string $action   Formatted action for activity stream.
	 * @param object $activity Activity object.
	 */
	return apply_filters( 'bp_members_format_activity_action_new_avatar', $action, $activity );
}

/**
 * Create a "became a registered user" activity item when a user activates his account.
 *
 * @since 1.2.2
 *
 * @param array $user Array of userdata passed to bp_core_activated_user hook.
 * @return bool
 */
function bp_core_new_user_activity( $user ) {
	if ( empty( $user ) ) {
		return false;
	}

	if ( is_array( $user ) ) {
		$user_id = $user['user_id'];
	} else {
		$user_id = $user;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	bp_activity_add( array(
		'user_id'   => $user_id,
		'component' => buddypress()->members->id,
		'type'      => 'new_member'
	) );
}
add_action( 'bp_core_activated_user', 'bp_core_new_user_activity' );

/**
 * Adds an activity stream item when a user has uploaded a new avatar.
 *
 * @since 8.0.0
 *
 * @param int $user_id The user id the avatar was set for.
 */
function bp_members_new_avatar_activity( $user_id = 0 ) {

	// Bail if activity component is not active.
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	/** This filter is documented in wp-includes/deprecated.php */
	$user_id = apply_filters_deprecated( 'bp_xprofile_new_avatar_user_id', array( $user_id ), '8.0.0', 'bp_members_new_avatar_user_id' );

	/**
	 * Filters the user ID when a user has uploaded a new avatar.
	 *
	 * @since 8.0.0
	 *
	 * @param int $user_id ID of the user the avatar was set for.
	 */
	$user_id = apply_filters( 'bp_members_new_avatar_user_id', $user_id );

	// Add the activity.
	bp_activity_add( array(
		'user_id'   => $user_id,
		'component' => buddypress()->members->id,
		'type'      => 'new_avatar'
	) );
}
add_action( 'bp_members_avatar_uploaded', 'bp_members_new_avatar_activity' );
