<?php
/**
 * Activity: Post action
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Post user/group activity update.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_post_update() {
	// Do not proceed if user is not logged in, not viewing activity, or not posting.
	if ( !is_user_logged_in() || !bp_is_activity_component() || !bp_is_current_action( 'post' ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'post_update', '_wpnonce_post_update' );

	/**
	 * Filters the content provided in the activity input field.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Activity message being posted.
	 */
	$content = apply_filters( 'bp_activity_post_update_content', $_POST['whats-new'] );

	if ( ! empty( $_POST['whats-new-post-object'] ) ) {

		/**
		 * Filters the item type that the activity update should be associated with.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Item type to associate with.
		 */
		$object = apply_filters( 'bp_activity_post_update_object', $_POST['whats-new-post-object'] );
	}

	if ( ! empty( $_POST['whats-new-post-in'] ) ) {

		/**
		 * Filters what component the activity is being to.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Chosen component to post activity to.
		 */
		$item_id = apply_filters( 'bp_activity_post_update_item_id', $_POST['whats-new-post-in'] );
	}

	// No activity content so provide feedback and redirect.
	if ( empty( $content ) ) {
		bp_core_add_message( __( 'Please enter some content to post.', 'buddypress' ), 'error' );
		bp_core_redirect( wp_get_referer() );
	}

	// No existing item_id.
	if ( empty( $item_id ) ) {
		$activity_id = bp_activity_post_update( array( 'content' => $content ) );

	// Post to groups object.
	} elseif ( 'groups' == $object && bp_is_active( 'groups' ) ) {
		if ( (int) $item_id ) {
			$activity_id = groups_post_update( array( 'content' => $content, 'group_id' => $item_id ) );
		}

	} else {

		/**
		 * Filters activity object for BuddyPress core and plugin authors before posting activity update.
		 *
		 * @since 1.2.0
		 *
		 * @param string $object  Activity item being associated to.
		 * @param string $item_id Component ID being posted to.
		 * @param string $content Activity content being posted.
		 */
		$activity_id = apply_filters( 'bp_activity_custom_update', $object, $item_id, $content );
	}

	// Provide user feedback.
	if ( !empty( $activity_id ) )
		bp_core_add_message( __( 'Update Posted!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error when posting your update. Please try again.', 'buddypress' ), 'error' );

	// Redirect.
	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_post_update' );