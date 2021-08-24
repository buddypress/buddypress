<?php
/**
 * Friends: Add action.
 *
 * @package BuddyPress
 * @subpackage FriendsActions
 * @since 3.0.0
 */

/**
 * Catch and process friendship requests.
 *
 * @since 1.0.1
 */
function friends_action_add_friend() {
	if ( ! bp_is_friends_component() || ! bp_is_current_action( 'add-friend' ) ) {
		return false;
	}

	$potential_friend_id = (int) bp_action_variable( 0 );
	if ( ! $potential_friend_id ) {
		return false;
	}

	if ( bp_loggedin_user_id() === $potential_friend_id ) {
		return false;
	}

	$friendship_status = BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $potential_friend_id );

	if ( 'not_friends' === $friendship_status ) {

		if ( ! check_admin_referer( 'friends_add_friend' ) ) {
			return false;
		}

		if ( ! friends_add_friend( bp_loggedin_user_id(), $potential_friend_id ) ) {
			bp_core_add_message( __( 'Friendship could not be requested.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Friendship requested', 'buddypress' ) );
		}
	} elseif ( 'is_friend' === $friendship_status ) {
		bp_core_add_message( __( 'You are already friends with this user', 'buddypress' ), 'error' );
	} else {
		bp_core_add_message( __( 'You already have a pending friendship request with this user', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_actions', 'friends_action_add_friend' );
