<?php
/**
 * Friends: Remove action
 *
 * @package BuddyPress
 * @subpackage FriendsActions
 * @since 3.0.0
 */

/**
 * Catch and process Remove Friendship requests.
 *
 * @since 1.0.1
 */
function friends_action_remove_friend() {
	if ( !bp_is_friends_component() || !bp_is_current_action( 'remove-friend' ) )
		return false;

	if ( !$potential_friend_id = (int)bp_action_variable( 0 ) )
		return false;

	if ( $potential_friend_id == bp_loggedin_user_id() )
		return false;

	$friendship_status = BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $potential_friend_id );

	if ( 'is_friend' == $friendship_status ) {

		if ( !check_admin_referer( 'friends_remove_friend' ) )
			return false;

		if ( !friends_remove_friend( bp_loggedin_user_id(), $potential_friend_id ) ) {
			bp_core_add_message( __( 'Friendship could not be canceled.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Friendship canceled', 'buddypress' ) );
		}

	} elseif ( 'not_friends' == $friendship_status ) {
		bp_core_add_message( __( 'You are not yet friends with this user', 'buddypress' ), 'error' );
	} else {
		bp_core_add_message( __( 'You have a pending friendship request with this user', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_actions', 'friends_action_remove_friend' );