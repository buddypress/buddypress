<?php
/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function friends_add_friend( $initiator_userid, $friend_userid, $force_accept = false ) {
	global $bp;

	$friendship = new BP_Friends_Friendship;

	if ( (int)$friendship->is_confirmed )
		return true;

	$friendship->initiator_user_id = $initiator_userid;
	$friendship->friend_user_id    = $friend_userid;
	$friendship->is_confirmed      = 0;
	$friendship->is_limited        = 0;
	$friendship->date_created      = bp_core_current_time();

	if ( $force_accept )
		$friendship->is_confirmed = 1;

	if ( $friendship->save() ) {

		if ( !$force_accept ) {
			// Add the on screen notification
			bp_core_add_notification( $friendship->initiator_user_id, $friendship->friend_user_id, $bp->friends->id, 'friendship_request' );

			// Send the email notification
			friends_notification_new_request( $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );

			do_action( 'friends_friendship_requested', $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );
		} else {
			do_action( 'friends_friendship_accepted', $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );
		}

		return true;
	}

	return false;
}

function friends_remove_friend( $initiator_userid, $friend_userid ) {
	global $bp;

	$friendship_id = BP_Friends_Friendship::get_friendship_id( $initiator_userid, $friend_userid );
	$friendship    = new BP_Friends_Friendship( $friendship_id );

	do_action( 'friends_before_friendship_delete', $friendship_id, $initiator_userid, $friend_userid );

	// Remove the activity stream item for the user who canceled the friendship
	friends_delete_activity( array( 'item_id' => $friendship_id, 'type' => 'friendship_accepted', 'user_id' => $bp->displayed_user->id ) );

	do_action( 'friends_friendship_deleted', $friendship_id, $initiator_userid, $friend_userid );

	if ( $friendship->delete() ) {
		friends_update_friend_totals( $initiator_userid, $friend_userid, 'remove' );

		return true;
	}

	return false;
}

function friends_accept_friendship( $friendship_id ) {
	global $bp;

	$friendship = new BP_Friends_Friendship( $friendship_id, true, false );

	if ( !$friendship->is_confirmed && BP_Friends_Friendship::accept( $friendship_id ) ) {
		friends_update_friend_totals( $friendship->initiator_user_id, $friendship->friend_user_id );

		// Remove the friend request notice
		bp_core_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, $bp->friends->id, 'friendship_request' );

		// Add a friend accepted notice for the initiating user
		bp_core_add_notification( $friendship->friend_user_id, $friendship->initiator_user_id, $bp->friends->id, 'friendship_accepted' );

		$initiator_link = bp_core_get_userlink( $friendship->initiator_user_id );
		$friend_link = bp_core_get_userlink( $friendship->friend_user_id );

		// Record in activity streams for the initiator
		friends_record_activity( array(
			'user_id'           => $friendship->initiator_user_id,
			'type'              => 'friendship_created',
			'action'            => apply_filters( 'friends_activity_friendship_accepted_action', sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), $initiator_link, $friend_link ), $friendship ),
			'item_id'           => $friendship_id,
			'secondary_item_id' => $friendship->friend_user_id
		) );

		// Record in activity streams for the friend
		friends_record_activity( array(
			'user_id'           => $friendship->friend_user_id,
			'type'              => 'friendship_created',
			'action'            => apply_filters( 'friends_activity_friendship_accepted_action', sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), $friend_link, $initiator_link ), $friendship ),
			'item_id'           => $friendship_id,
			'secondary_item_id' => $friendship->initiator_user_id,
			'hide_sitewide'     => true // We've already got the first entry site wide
		) );

		// Send the email notification
		friends_notification_accepted_request( $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );

		do_action( 'friends_friendship_accepted', $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );

		return true;
	}

	return false;
}

function friends_reject_friendship( $friendship_id ) {
	global $bp;

	$friendship = new BP_Friends_Friendship( $friendship_id, true, false );

	if ( !$friendship->is_confirmed && BP_Friends_Friendship::reject( $friendship_id ) ) {
		// Remove the friend request notice
		bp_core_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, $bp->friends->id, 'friendship_request' );

		do_action_ref_array( 'friends_friendship_rejected', array( $friendship_id, &$friendship ) );
		return true;
	}

	return false;
}

function friends_check_friendship( $user_id, $possible_friend_id ) {
	global $bp;

	if ( 'is_friend' == BP_Friends_Friendship::check_is_friend( $user_id, $possible_friend_id ) )
		return true;

	return false;
}

// Returns - 'is_friend', 'not_friends', 'pending'
function friends_check_friendship_status( $user_id, $possible_friend_id ) {
	return BP_Friends_Friendship::check_is_friend( $user_id, $possible_friend_id );
}

function friends_get_total_friend_count( $user_id = 0 ) {
	global $bp;

	if ( !$user_id )
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	if ( !$count = wp_cache_get( 'bp_total_friend_count_' . $user_id, 'bp' ) ) {
		$count = bp_get_user_meta( $user_id, 'total_friend_count', true );
		if ( empty( $count ) ) $count = 0;
		wp_cache_set( 'bp_total_friend_count_' . $user_id, $count, 'bp' );
	}

	return apply_filters( 'friends_get_total_friend_count', $count );
}

function friends_check_user_has_friends( $user_id ) {
	$friend_count = friends_get_total_friend_count( $user_id );

	if ( empty( $friend_count ) )
		return false;

	if ( !(int)$friend_count )
		return false;

	return true;
}

function friends_get_friendship_id( $initiator_user_id, $friend_user_id ) {
	return BP_Friends_Friendship::get_friendship_id( $initiator_user_id, $friend_user_id );
}

function friends_get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false, $filter = false ) {
	return BP_Friends_Friendship::get_friend_user_ids( $user_id, $friend_requests_only, $assoc_arr );
}

function friends_search_friends( $search_terms, $user_id, $pag_num = 10, $pag_page = 1 ) {
	return BP_Friends_Friendship::search_friends( $search_terms, $user_id, $pag_num, $pag_page );
}

function friends_get_friendship_request_user_ids( $user_id ) {
	return BP_Friends_Friendship::get_friendship_request_user_ids( $user_id );
}

function friends_get_recently_active( $user_id, $per_page = 0, $page = 0, $filter = '' ) {
	return apply_filters( 'friends_get_recently_active', BP_Core_User::get_users( 'active', $per_page, $page, $user_id, $filter ) );
}

function friends_get_alphabetically( $user_id, $per_page = 0, $page = 0, $filter = '' ) {
	return apply_filters( 'friends_get_alphabetically', BP_Core_User::get_users( 'alphabetical', $per_page, $page, $user_id, $filter ) );
}

function friends_get_newest( $user_id, $per_page = 0, $page = 0, $filter = '' ) {
	return apply_filters( 'friends_get_newest', BP_Core_User::get_users( 'newest', $per_page, $page, $user_id, $filter ) );
}

function friends_get_bulk_last_active( $friend_ids ) {
	return BP_Friends_Friendship::get_bulk_last_active( $friend_ids );
}

function friends_get_friends_invite_list( $user_id = 0 ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	if ( bp_has_members( 'user_id=' . $user_id . '&type=alphabetical&per_page=0' ) ) {
		while ( bp_members() ) : bp_the_member();
			$friends[] = array(
				'id' => bp_get_member_user_id(),
				'full_name' => bp_get_member_name()
			);
		endwhile;
	}

	if ( empty($friends) )
		return false;

	return $friends;
}

function friends_count_invitable_friends( $user_id, $group_id ) {
	return BP_Friends_Friendship::get_invitable_friend_count( $user_id, $group_id );
}

function friends_get_friend_count_for_user( $user_id ) {
	return BP_Friends_Friendship::total_friend_count( $user_id );
}

function friends_search_users( $search_terms, $user_id, $pag_num = 0, $pag_page = 0 ) {
	global $bp;

	$user_ids = BP_Friends_Friendship::search_users( $search_terms, $user_id, $pag_num, $pag_page );

	if ( !$user_ids )
		return false;

	for ( $i = 0, $count = count( $user_ids ); $i < $count; ++$i )
		$users[] = new BP_Core_User( $user_ids[$i] );

	return array( 'users' => $users, 'count' => BP_Friends_Friendship::search_users_count( $search_terms ) );
}

function friends_is_friendship_confirmed( $friendship_id ) {
	$friendship = new BP_Friends_Friendship( $friendship_id );
	return $friendship->is_confirmed;
}

function friends_update_friend_totals( $initiator_user_id, $friend_user_id, $status = 'add' ) {
	global $bp;

	if ( 'add' == $status ) {
		bp_update_user_meta( $initiator_user_id, 'total_friend_count', (int)bp_get_user_meta( $initiator_user_id, 'total_friend_count', true ) + 1 );
		bp_update_user_meta( $friend_user_id, 'total_friend_count', (int)bp_get_user_meta( $friend_user_id, 'total_friend_count', true ) + 1 );
	} else {
		bp_update_user_meta( $initiator_user_id, 'total_friend_count', (int)bp_get_user_meta( $initiator_user_id, 'total_friend_count', true ) - 1 );
		bp_update_user_meta( $friend_user_id, 'total_friend_count', (int)bp_get_user_meta( $friend_user_id, 'total_friend_count', true ) - 1 );
	}
}

function friends_remove_data( $user_id ) {
	global $bp;

	do_action( 'friends_before_remove_data', $user_id );

	BP_Friends_Friendship::delete_all_for_user($user_id);

	// Remove usermeta
	bp_delete_user_meta( $user_id, 'total_friend_count' );

	// Remove friendship requests FROM user
	bp_core_delete_notifications_from_user( $user_id, $bp->friends->id, 'friendship_request' );

	do_action( 'friends_remove_data', $user_id );
}
add_action( 'wpmu_delete_user',  'friends_remove_data' );
add_action( 'delete_user',       'friends_remove_data' );
add_action( 'bp_make_spam_user', 'friends_remove_data' );

?>