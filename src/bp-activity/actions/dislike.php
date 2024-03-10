<?php
/**
 * Activity: Dislike action
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
 * Dislike an activity.
 *
 * @since 14.0.0
 *
 * @return void
 */
function bp_activity_dislike_action() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'dislike' ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'bp_activity_like' );

	$feedback        = __( 'Ouch something went wrong when trying to dislike this activity.', 'buddypress' );
	$feedback_type   = 'error';
	$activities      = bp_activity_get_specific(
		array(
			'activity_ids'     => bp_action_variable( 0 ),
			'display_comments' => 'threaded',
		)
	);
	$parent_activity = reset( $activities['activities'] );

	if ( empty( $parent_activity->id ) ) {
		$feedback = __( 'The activity you want to dislike does not exist.', 'buddypress' );

	} else {
		$user_like = array();
		if ( isset( $parent_activity->reactions ) ) {
			$user_like = wp_filter_object_list( $parent_activity->reactions, array( 'type' => 'activity_like', 'user_id' => bp_loggedin_user_id() ), 'AND', 'id' );
		}

		// The user didn't like it!
		if ( empty( $user_like ) || 1 !== count( $user_like ) ) {
			$feedback = __( 'Ouch! Are you sure you liked this activity? Looks like it’s not the case.', 'buddypress' );

		} else {
			$reaction_id = reset( $user_like );
			$disliked    = bp_activity_remove_reaction( $reaction_id );

			if ( is_wp_error( $disliked ) ) {
				$feedback = $disliked->get_error_message();
			} else {
				$feedback      = __( 'You successfully disliked it!', 'buddypress' );
				$feedback_type = 'success';
			}
		}
	}

	bp_core_add_message( $feedback, $feedback_type );
	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_dislike_action' );
