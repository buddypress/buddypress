<?php
/**
 * Common functions only loaded on AJAX requests.
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the template loop for the current object.
 *
 * @since 3.0.0
 *
 * @return string Template loop for the specified object
 */
function bp_nouveau_ajax_object_template_loader() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	$post_vars = bp_parse_args(
		$_POST,
		array(
			'action'   => '',
			'object'   => '',
			'scope'    => '',
			'filter'   => '',
			'nonce'    => '',
			'template' => '',
		)
	);

	$object = sanitize_title( $post_vars['object'] );

	// Bail if object is not an active component to prevent arbitrary file inclusion.
	if ( ! bp_is_active( $object ) ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( ! $post_vars['nonce'] || ! wp_verify_nonce( $post_vars['nonce'], 'bp_nouveau_' . $object ) ) {
		wp_send_json_error();
	}

	$result = array();

	if ( 'activity' === $object ) {
		$scope = '';
		if ( $post_vars['scope'] ) {
			$scope = sanitize_text_field( $post_vars['scope'] );
		}

		// We need to calculate and return the feed URL for each scope.
		switch ( $scope ) {
			case 'friends':
				$feed_url = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_nouveau_get_component_slug( 'activity' ), 'friends', array( 'feed' ) ) ) );
				break;
			case 'groups':
				$feed_url = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_nouveau_get_component_slug( 'activity' ), 'groups', array( 'feed' ) ) ) );
				break;
			case 'favorites':
				$feed_url = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_nouveau_get_component_slug( 'activity' ), 'favorites', array( 'feed' ) ) ) );
				break;
			case 'mentions':
				$feed_url = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_nouveau_get_component_slug( 'activity' ), 'mentions', array( 'feed' ) ) ) );

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
				$feed_url = bp_get_sitewide_activity_feed_link();
				break;
		}

		/**
		 * Filters the browser URL for the template loader.
		 *
		 * @since 3.0.0
		 *
		 * @param string $feed_url Template feed url.
		 * @param string $scope    Current component scope.
		 */
		$result['feed_url'] = apply_filters( 'bp_nouveau_ajax_object_template_loader', $feed_url, $scope );
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
	$template = '';
	if ( $post_vars['template'] ) {
		$template = wp_unslash( $post_vars['template'] );
	}

	switch ( $template ) {
		case 'group_members' :
		case 'groups/single/members' :
			$template_part = 'groups/single/members-loop.php';
		break;

		case 'group_requests' :
			$template_part = 'groups/single/requests-loop.php';
		break;

		case 'friend_requests' :
			$template_part = 'members/single/friends/requests-loop.php';
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

	/**
	 * Filters the server path for the template loader.
	 *
	 * @since 3.0.0
	 *
	 * @param string Template file path.
	 */
	$template_path = apply_filters( 'bp_nouveau_object_template_path', $template_path );

	load_template( $template_path );
	$result['contents'] = ob_get_contents();
	ob_end_clean();

	/**
	 * Add additional info to the Ajax response.
	 *
	 * @since 10.0.0
	 *
	 * @param array $value     An associative array with additional information to include in the Ajax response.
	 * @param array $post_vars An associative array containing the Ajax request arguments.
	 */
	$additional_info = apply_filters( "bp_nouveau_{$object}_ajax_object_template_response", array(), $post_vars );
	if ( $additional_info ) {
		// Prevents content overrides.
		if ( isset( $additional_info['contents'] ) ) {
			unset( $additional_info['contents'] );
		}

		$result = array_merge( $result, $additional_info );
	}

	// Locate the object template.
	wp_send_json_success( $result );
}
