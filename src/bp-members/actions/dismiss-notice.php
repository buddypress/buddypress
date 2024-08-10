<?php
/**
 * BuddyPress Dismiss Notice Action handler.
 *
 * @package buddypress\bp-members\actions\dismiss-notice
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles a notice dismissal.
 *
 * @since 15.0.0
 *
 * @return void
 */
function bp_members_notice_action_dismiss() {
	// Bail if current user is not viewing their pages and is not an Admin.
	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	$notice_id = (int) bp_action_variable( 1 );
	if ( 'dismiss' === bp_action_variable( 0 ) && $notice_id ) {
		// Check the nonce.
		check_admin_referer( 'members_dismiss_notice' );

		// By default redirect to the referer.
		$referer     = wp_get_referer();
		$redirect_to = '';

		// If specified, redirect to the validated URL to redirect to.
		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = sanitize_url( wp_unslash( $_GET['redirect_to'] ) );

			if ( wp_http_validate_url( $redirect_to ) ) {
				$referer = str_replace( 'amp;', '', $redirect_to );
			}
		}

		$is_referer_admin = 0 === strpos( wp_parse_url( $referer, PHP_URL_PATH ), '/wp-admin' );

		// Dismiss the notice.
		$dismissed = bp_members_dismiss_notice( bp_displayed_user_id(), $notice_id );

		// Only add feedbacks when the URL to redirect to is not specified.
		if ( ! $redirect_to ) {
			if ( is_wp_error( $dismissed ) ) {
				if ( $is_referer_admin ) {
					$referer = add_query_arg( 'bp-dismissed', 0, $referer );
				} else {
					bp_core_add_message( $dismissed->get_error_message(), 'error' );
				}
			} else {
				if ( $is_referer_admin ) {
					$referer = add_query_arg( 'bp-dismissed', $notice_id, $referer );
				} else {
					bp_core_add_message( 'The notice was successfully dismissed.' );
				}
			}
		}

		// Always redirect to the referer.
		bp_core_redirect( $referer );
	}
}
add_action( 'bp_actions', 'bp_members_notice_action_dismiss' );
