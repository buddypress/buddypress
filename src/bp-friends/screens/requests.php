<?php
/**
 * Friends: User's "Friends > Requests" screen handler
 *
 * @package BuddyPress
 * @subpackage FriendsScreens
 * @since 3.0.0
 */

/**
 * Catch and process the Requests page.
 *
 * @since 1.0.0
 */
function friends_screen_requests() {
	$redirect = false;

	if ( bp_is_action_variable( 'accept', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_accept_friendship' );

		if ( friends_accept_friendship( bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Friendship accepted', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'Friendship could not be accepted', 'buddypress' ), 'error' );
		}

		$redirect = true;

	} elseif ( bp_is_action_variable( 'reject', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_reject_friendship' );

		if ( friends_reject_friendship( bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Friendship rejected', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'Friendship could not be rejected', 'buddypress' ), 'error' );
		}

		$redirect = true;

	} elseif ( bp_is_action_variable( 'cancel', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_withdraw_friendship' );

		if ( friends_withdraw_friendship( bp_loggedin_user_id(), bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Friendship request withdrawn', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'Friendship request could not be withdrawn', 'buddypress' ), 'error' );
		}

		$redirect = true;
	}

	if ( $redirect ) {
		bp_core_redirect(
			bp_loggedin_user_url(
				bp_members_get_path_chunks( array( bp_get_friends_slug(), 'requests' ) )
			)
		);
	}

	/**
	 * Fires before the loading of template for the friends requests page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'friends_screen_requests' );

	/**
	 * Filters the template used to display the My Friends page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the friends request template to load.
	 */
	bp_core_load_template( apply_filters( 'friends_template_requests', 'members/single/home' ) );
}
