<?php
/**
 * Activity: Like action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Like an activity.
 *
 * @since 14.0.0
 *
 * @return void
 */
function bp_activity_like_action() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'like' ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'bp_activity_like' );

	$feedback        = __( 'Ouch something went wrong when trying to like this activity.', 'buddypress' );
	$feedback_type   = 'error';
	$activities      = bp_activity_get_specific(
		array(
			'activity_ids'     => bp_action_variable( 0 ),
			'display_comments' => 'threaded',
		)
	);
	$parent_activity = reset( $activities['activities'] );

	if ( empty( $parent_activity->id ) ) {
		$feedback = __( 'The activity you want to like does not exist.', 'buddypress' );

	} elseif ( ! bp_activity_user_can_read( $parent_activity, bp_loggedin_user_id() ) ) {
		$feedback = __( 'You are not allowed to like this activity.', 'buddypress' );

	} else {
		$likes = array();
		if ( isset( $parent_activity->reactions ) ) {
			$likes = wp_filter_object_list( $parent_activity->reactions, array( 'type' => 'activity_like' ), 'AND', 'user_id' );
		}

		// The user already liked it!
		if ( in_array( bp_loggedin_user_id(), $likes, true ) ) {
			$feedback = __( 'Oh wait! You already liked this activity.', 'buddypress' );

		} else {
			$like_id = bp_activity_add_reaction(
				array(
					'activity'     => $parent_activity,
					'primary_link' => bp_activity_get_permalink( $parent_activity->id, $parent_activity ),
				)
			);

			if ( is_wp_error( $like_id ) ) {
				$feedback = $like_id->get_error_message();
			} else {
				$feedback      = __( 'You successfully liked it!', 'buddypress' );
				$feedback_type = 'success';
			}
		}
	}

	bp_core_add_message( $feedback, $feedback_type );
	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_like_action' );
