<?php
/**
 * BuddyPress Friend Filters.
 *
 * @package BuddyPress
 * @subpackage FriendsFilters
 * @since 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Format numerical output.
add_filter( 'friends_get_total_friend_count', 'bp_core_number_format' );
add_filter( 'bp_get_total_friend_count', 'bp_core_number_format' );

/**
 * Filter BP_User_Query::populate_extras to add confirmed friendship status.
 *
 * Each member in the user query is checked for confirmed friendship status
 * against the logged-in user.
 *
 * @since 1.7.0
 *
 * @param BP_User_Query $user_query   The BP_User_Query object.
 * @param string        $user_ids_sql Comma-separated list of user IDs to fetch extra
 *                                    data for, as determined by BP_User_Query.
 */
function bp_friends_filter_user_query_populate_extras( $user_query, $user_ids_sql ) {

	// Stop if user isn't logged in.
	$user_id = bp_loggedin_user_id();
	if ( ! $user_id ) {
		return;
	}

	$maybe_friend_ids = wp_parse_id_list( $user_ids_sql );

	// Bulk prepare the friendship cache.
	BP_Friends_Friendship::update_bp_friends_cache( $user_id, $maybe_friend_ids );

	foreach ( $maybe_friend_ids as $friend_id ) {
		$status = BP_Friends_Friendship::check_is_friend( $user_id, $friend_id );
		$user_query->results[ $friend_id ]->friendship_status = $status;
		if ( 'is_friend' === $status ) {
			$user_query->results[ $friend_id ]->is_friend = 1;
		}
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_friends_filter_user_query_populate_extras', 4, 2 );

/**
 * Registers Friends personal data exporter.
 *
 * @since 4.0.0
 * @since 5.0.0 adds an `exporter_bp_friendly_name` param to exporters.
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_friends_register_personal_data_exporters( $exporters ) {
	$exporters['buddypress-friends'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Friends', 'buddypress' ),
		'callback'                  => 'bp_friends_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Friends', 'BuddyPress Friends data exporter friendly name', 'buddypress' ),
	);

	$exporters['buddypress-friends-pending-sent-requests'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Friend Requests (Sent)', 'buddypress' ),
		'callback'                  => 'bp_friends_pending_sent_requests_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Friend Requests (Sent)', 'BuddyPress Friend Requests data exporter friendly name', 'buddypress' ),
	);

	$exporters['buddypress-friends-pending-received-requests'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Friend Requests (Received)', 'buddypress' ),
		'callback'                  => 'bp_friends_pending_received_requests_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Friend Requests (Received)', 'BuddyPress Friend Requests data exporter friendly name', 'buddypress' ),
	);

	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'bp_friends_register_personal_data_exporters' );
