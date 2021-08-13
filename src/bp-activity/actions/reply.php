<?php
/**
 * Activity: Reply action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Post new activity comment.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_post_comment() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'reply' ) ) {
		return false;
	}

	if ( ! isset( $_POST['comment_form_id'] ) ) {
		return false;
	}

	$activity_id = absint( wp_unslash( $_POST['comment_form_id'] ) );

	if ( ! isset( $activity_id ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'new_activity_comment', '_wpnonce_new_activity_comment_' . $activity_id );

	/**
	 * Filters the activity ID a comment will be in reply to.
	 *
	 * @since 1.2.0
	 *
	 * @param string $activity_id ID of the activity being replied to.
	 */
	$activity_id = apply_filters( 'bp_activity_post_comment_activity_id', $activity_id );

	/**
	 * Filters the comment content for a comment reply.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Comment content being posted.
	 */
	$content = apply_filters( 'bp_activity_post_comment_content', $_POST['ac_input_' . $activity_id] );

	if ( empty( $content ) ) {
		bp_core_add_message( __( 'Please do not leave the comment area blank.', 'buddypress' ), 'error' );
		bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
	}

	$activity_item = new BP_Activity_Activity( $activity_id );
	if ( ! bp_activity_user_can_read( $activity_item ) ) {
		bp_core_add_message( __( 'There was an error posting that reply. Please try again.', 'buddypress' ), 'error' );
		bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
	}

	$comment_id = bp_activity_new_comment(
		array(
			'content'     => $content,
			'activity_id' => $activity_id,
			'parent_id'   => false
		)
	);

	if ( ! empty( $comment_id ) ) {
		bp_core_add_message( __( 'Reply Posted!', 'buddypress' ) );
	} else {
		bp_core_add_message( __( 'There was an error posting that reply. Please try again.', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
}
add_action( 'bp_actions', 'bp_activity_action_post_comment' );
