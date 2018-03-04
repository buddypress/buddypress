<?php
/**
 * Common functions only loaded on AJAX requests.
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the template loop for the current object.
 *
 * @return string Prints template loop for the specified object
 * @since 1.0.0
 */
function bp_nouveau_ajax_object_template_loader() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['object'] ) ) {
		wp_send_json_error();
	}

	$object = sanitize_title( $_POST['object'] );

	// Bail if object is not an active component to prevent arbitrary file inclusion.
	if ( ! bp_is_active( $object ) ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_' . $object ) ) {
		wp_send_json_error();
	}

	$result = array();

	if ( 'activity' === $object ) {
		$scope = '';
		if ( ! empty( $_POST['scope'] ) ) {
			$scope = $_POST['scope'];
		}

		// We need to calculate and return the feed URL for each scope.
		switch ( $scope ) {
			case 'friends':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/friends/feed/';
				break;
			case 'groups':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/groups/feed/';
				break;
			case 'favorites':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/feed/';
				break;
			case 'mentions':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/mentions/feed/';

				// Get user new mentions
				$new_mentions = bp_get_user_meta( bp_loggedin_user_id(), 'bp_new_mentions', true );

				// If we have some, include them into the returned json before deleting them
				if ( is_array( $new_mentions ) ) {
					$result['new_mentions'] = $new_mentions;

					// Clear new mentions
					bp_activity_clear_new_mentions( bp_loggedin_user_id() );
				}

				break;
			default:
				$feed_url = home_url( bp_get_activity_root_slug() . '/feed/' );
				break;
		}

		$result['feed_url'] = apply_filters( 'bp_legacy_theme_activity_feed_url', $feed_url, $scope );
	}

	/*
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( ! bp_current_action() ) {
		bp_update_is_directory( true, bp_current_component() );
	}

	// Get the template path based on the 'template' variable via the AJAX request.
	$template = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';

	switch ( $template ) {
		case 'group_members' :
		case 'groups/single/members' :
			$template_part = 'groups/single/members-loop.php';
		break;

		case 'group_requests' :
			$template_part = 'groups/single/requests-loop.php';
		break;

		case 'member_notifications' :
			$template_part = 'members/single/notifications/notifications-loop.php';
		break;

		default :
			$template_part = $object . '/' . $object . '-loop.php';
		break;
	}

	ob_start();

	$template_path = bp_locate_template( array( $template_part ), false );
	$template_path = apply_filters( 'bp_legacy_object_template_path', $template_path );

	load_template( $template_path );
	$result['contents'] = ob_get_contents();
	ob_end_clean();

	// Locate the object template.
	wp_send_json_success( $result );
}

/**
 * Register AJAX hooks.
 *
 * @since 1.0.0
 *
 * @param array $ajax_actions {
 *      Multi-dimensional array. For example:
 *
 *      $ajax_actions = array(
 *	    array( 'messages_send_message' => array( 'function' => 'bp_nouveau_ajax_messages_send_message', 'nopriv' => false ) ),
 *          array( 'messages_send_reply'   => array( 'function' => 'bp_nouveau_ajax_messages_send_reply',   'nopriv' => false ) ),
 *      );
 *
 *     - 'messages_send_message' is the AJAX action.
 *     - 'bp_nouveau_ajax_messages_send_message' is the hooked function to the AJAX action.
 *     - 'nopriv' indicates whether the AJAX action is allowed for logged-out users.
 * }
 * @return array
 */
function bp_nouveau_register_ajax_actions( $ajax_actions = array() ) {
	foreach ( $ajax_actions as $ajax_action ) {
		$action = key( $ajax_action );

		add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

		if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
		}
	}
}
