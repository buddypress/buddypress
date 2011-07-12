<?php

/**
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function friends_action_add_friend() {
	global $bp;

	if ( !bp_is_friends_component() || !bp_is_current_action( 'add-friend' ) )
		return false;

	$potential_friend_id = $bp->action_variables[0];

	if ( !is_numeric( $potential_friend_id ) || !isset( $potential_friend_id ) )
		return false;

	if ( $potential_friend_id == $bp->loggedin_user->id )
		return false;

	$friendship_status = BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $potential_friend_id );

	if ( 'not_friends' == $friendship_status ) {

		if ( !check_admin_referer( 'friends_add_friend' ) )
			return false;

		if ( !friends_add_friend( $bp->loggedin_user->id, $potential_friend_id ) ) {
			bp_core_add_message( __( 'Friendship could not be requested.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Friendship requested', 'buddypress' ) );
		}

	} else if ( 'is_friend' == $friendship_status ) {
		bp_core_add_message( __( 'You are already friends with this user', 'buddypress' ), 'error' );
	} else {
		bp_core_add_message( __( 'You already have a pending friendship request with this user', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_init', 'friends_action_add_friend' );

function friends_action_remove_friend() {
	global $bp;

	if ( !bp_is_friends_component() || !bp_is_current_action( 'remove-friend' ) )
		return false;

	$potential_friend_id = $bp->action_variables[0];

	if ( !is_numeric( $potential_friend_id ) || !isset( $potential_friend_id ) )
		return false;

	if ( $potential_friend_id == $bp->loggedin_user->id )
		return false;

	$friendship_status = BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $potential_friend_id );

	if ( 'is_friend' == $friendship_status ) {

		if ( !check_admin_referer( 'friends_remove_friend' ) )
			return false;

		if ( !friends_remove_friend( $bp->loggedin_user->id, $potential_friend_id ) ) {
			bp_core_add_message( __( 'Friendship could not be canceled.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Friendship canceled', 'buddypress' ) );
		}

	} else if ( 'is_friends' == $friendship_status ) {
		bp_core_add_message( __( 'You are not yet friends with this user', 'buddypress' ), 'error' );
	} else {
		bp_core_add_message( __( 'You have a pending friendship request with this user', 'buddypress' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_init', 'friends_action_remove_friend' );

?>
