<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Friends_Friendship {
	var $id;
	var $initiator_user_id;
	var $friend_user_id;
	var $is_confirmed;
	var $is_limited;
	var $date_created;

	var $is_request;
	var $populate_friend_details;

	var $friend;

	function __construct( $id = null, $is_request = false, $populate_friend_details = true ) {
		$this->is_request = $is_request;

		if ( !empty( $id ) ) {
			$this->id                      = $id;
			$this->populate_friend_details = $populate_friend_details;
			$this->populate( $this->id );
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $friendship = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->friends->table_name} WHERE id = %d", $this->id ) ) ) {
			$this->initiator_user_id = $friendship->initiator_user_id;
			$this->friend_user_id    = $friendship->friend_user_id;
			$this->is_confirmed      = $friendship->is_confirmed;
			$this->is_limited        = $friendship->is_limited;
			$this->date_created      = $friendship->date_created;
		}

		if ( !empty( $this->populate_friend_details ) ) {
			if ( $this->friend_user_id == bp_displayed_user_id() ) {
				$this->friend = new BP_Core_User( $this->initiator_user_id );
			} else {
				$this->friend = new BP_Core_User( $this->friend_user_id );
			}
		}
	}

	function save() {
		global $wpdb, $bp;

		$this->initiator_user_id = apply_filters( 'friends_friendship_initiator_user_id_before_save', $this->initiator_user_id, $this->id );
		$this->friend_user_id    = apply_filters( 'friends_friendship_friend_user_id_before_save',    $this->friend_user_id,    $this->id );
		$this->is_confirmed      = apply_filters( 'friends_friendship_is_confirmed_before_save',      $this->is_confirmed,      $this->id );
		$this->is_limited        = apply_filters( 'friends_friendship_is_limited_before_save',        $this->is_limited,        $this->id );
		$this->date_created      = apply_filters( 'friends_friendship_date_created_before_save',      $this->date_created,      $this->id );

		do_action_ref_array( 'friends_friendship_before_save', array( &$this ) );

		// Update
		if (!empty( $this->id ) ) {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->friends->table_name} SET initiator_user_id = %d, friend_user_id = %d, is_confirmed = %d, is_limited = %d, date_created = %s ) WHERE id = %d", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created, $this->id ) );

		// Save
		} else {
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->friends->table_name} ( initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created ) VALUES ( %d, %d, %d, %d, %s )", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created ) );
			$this->id = $wpdb->insert_id;
		}

		do_action( 'friends_friendship_after_save', array( &$this ) );

		return $result;
	}

	function delete() {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d", $this->id ) );
	}

	/** Static Methods ********************************************************/

	function get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false ) {
		global $wpdb, $bp;

		if ( !empty( $friend_requests_only ) ) {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 0" );
			$friend_sql = $wpdb->prepare ( " WHERE friend_user_id = %d", $user_id );
		} else {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 1" );
			$friend_sql = $wpdb->prepare ( " WHERE (initiator_user_id = %d OR friend_user_id = %d)", $user_id, $user_id );
		}

		$friends = $wpdb->get_results( $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM {$bp->friends->table_name} $friend_sql $oc_sql ORDER BY date_created DESC" ) );
		$fids = array();

		for ( $i = 0, $count = count( $friends ); $i < $count; ++$i ) {
			if ( !empty( $assoc_arr ) ) {
				$fids[] = array( 'user_id' => ( $friends[$i]->friend_user_id == $user_id ) ? $friends[$i]->initiator_user_id : $friends[$i]->friend_user_id );
			} else {
				$fids[] = ( $friends[$i]->friend_user_id == $user_id ) ? $friends[$i]->initiator_user_id : $friends[$i]->friend_user_id;
			}
		}

		return $fids;
	}

	function get_friendship_id( $user_id, $friend_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->friends->table_name} WHERE ( initiator_user_id = %d AND friend_user_id = %d ) OR ( initiator_user_id = %d AND friend_user_id = %d ) AND is_confirmed = 1", $user_id, $friend_id, $friend_id, $user_id ) );
	}

	function get_friendship_request_user_ids( $user_id ) {
		global $wpdb, $bp;

		return $wpdb->get_col( $wpdb->prepare( "SELECT initiator_user_id FROM {$bp->friends->table_name} WHERE friend_user_id = %d AND is_confirmed = 0", $user_id ) );
	}

	function total_friend_count( $user_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

		/* This is stored in 'total_friend_count' usermeta.
		   This function will recalculate, update and return. */

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d OR friend_user_id = %d) AND is_confirmed = 1", $user_id, $user_id ) );

		// Do not update meta if user has never had friends
		if ( empty( $count ) && !bp_get_user_meta( $user_id, 'total_friend_count', true ) )
			return 0;

		bp_update_user_meta( $user_id, 'total_friend_count', (int) $count );
		return (int) $count;
	}

	function search_friends( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp;

		// TODO: Optimize this function.

		if ( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id ) )
			return false;

		// Get all the user ids for the current user's friends.
		$fids = implode( ',', $friend_ids );

		if ( empty( $fids ) )
			return false;

		// filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql = "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data} WHERE user_id IN ($fids) AND value LIKE '$filter%%' {$pag_sql}";
			$total_sql = "SELECT COUNT(DISTINCT user_id) FROM {$bp->profile->table_name_data} WHERE user_id IN ($fids) AND value LIKE '$filter%%'";
		} else {
			$sql = "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE user_id IN ($fids) AND meta_key = 'nickname' AND meta_value LIKE '$filter%%' {$pag_sql}";
			$total_sql = "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE user_id IN ($fids) AND meta_key = 'nickname' AND meta_value LIKE '$filter%%'";
		}

		$filtered_friend_ids = $wpdb->get_col( $sql );
		$total_friend_ids    = $wpdb->get_var( $total_sql );

		if ( empty( $filtered_friend_ids ) )
			return false;

		return array( 'friends' => $filtered_friend_ids, 'total' => (int) $total_friend_ids );
	}

	function check_is_friend( $loggedin_userid, $possible_friend_userid ) {
		global $wpdb, $bp;

		if ( empty( $loggedin_userid ) || empty( $possible_friend_userid ) )
			return false;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id = %d) OR (initiator_user_id = %d AND friend_user_id = %d)", $loggedin_userid, $possible_friend_userid, $possible_friend_userid, $loggedin_userid ) );

		if ( !empty( $result ) ) {
			if ( 0 == (int) $result[0]->is_confirmed ) {
				return 'pending';
			} else {
				return 'is_friend';
			}
		} else {
			return 'not_friends';
		}
	}

	function get_bulk_last_active( $user_ids ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT meta_value as last_activity, user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ( {$user_ids} ) ORDER BY meta_value DESC", bp_get_user_meta_key( 'last_activity' ) ) );
	}

	function accept($friendship_id) {
		global $wpdb, $bp;

	 	return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->friends->table_name} SET is_confirmed = 1, date_created = %s WHERE id = %d AND friend_user_id = %d", bp_core_current_time(), $friendship_id, bp_loggedin_user_id() ) );
	}

	function withdraw($friendship_id) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND initiator_user_id = %d", $friendship_id, bp_loggedin_user_id() ) );
	}

	function reject($friendship_id) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND friend_user_id = %d", $friendship_id, bp_loggedin_user_id() ) );
	}

	function search_users( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb;

		$filter = like_escape( $wpdb->escape( $filter ) );

		$usermeta_table = $wpdb->base_prefix . 'usermeta';
		$users_table    = $wpdb->base_prefix . 'users';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * intval( $limit ) ), intval( $limit ) );

		// filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT d.user_id as id FROM {$bp->profile->table_name_data} d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%' ORDER BY d.value DESC $pag_sql" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id as id FROM $usermeta_table WHERE meta_value LIKE '$filter%%' ORDER BY d.value DESC $pag_sql" );
		}

		$filtered_fids = $wpdb->get_col($sql);

		if ( empty( $filtered_fids ) )
			return false;

		return $filtered_fids;
	}

	function search_users_count( $filter ) {
		global $wpdb, $bp;

		$filter = like_escape( $wpdb->escape( $filter ) );

		$usermeta_table = $wpdb->prefix . 'usermeta';
		$users_table    = $wpdb->base_prefix . 'users';

		// filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql = $wpdb->prepare( "SELECT COUNT(DISTINCT d.user_id) FROM {$bp->profile->table_name_data} d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%'" );
		} else {
			$sql = $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $usermeta_table WHERE meta_value LIKE '$filter%%'" );
		}

		$user_count = $wpdb->get_col($sql);

		if ( empty( $user_count ) )
			return false;

		return $user_count[0];
	}

	function sort_by_name( $user_ids ) {
		global $wpdb, $bp;

		if ( !bp_is_active( 'xprofile' ) )
			return false;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} pd, {$bp->profile->table_name_fields} pf WHERE pf.id = pd.field_id AND pf.name = %s AND pd.user_id IN ( {$user_ids} ) ORDER BY pd.value ASC", bp_xprofile_fullname_field_name() ) );
	}

	function get_random_friends( $user_id, $total_friends = 5 ) {
		global $wpdb, $bp;

		$fids    = array();
		$sql     = $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM {$bp->friends->table_name} WHERE (friend_user_id = %d || initiator_user_id = %d) && is_confirmed = 1 ORDER BY rand() LIMIT %d", $user_id, $user_id, $total_friends );
		$results = $wpdb->get_results( $sql );

		for ( $i = 0, $count = count( $results ); $i < $count; ++$i ) {
			$fids[] = ( $results[$i]->friend_user_id == $user_id ) ? $results[$i]->initiator_user_id : $results[$i]->friend_user_id;
		}

		// remove duplicates
		if ( count( $fids ) > 0 )
			return array_flip( array_flip( $fids ) );
		else
			return false;
	}

	function get_invitable_friend_count( $user_id, $group_id ) {

		// Setup some data we'll use below
		$is_group_admin  = BP_Groups_Member::check_is_admin( $user_id, $group_id );
		$friend_ids      = BP_Friends_Friendship::get_friend_user_ids( $user_id );
		$invitable_count = 0;

		for ( $i = 0, $count = count( $friend_ids ); $i < $count; ++$i ) {

			// If already a member, they cannot be invited again
			if ( BP_Groups_Member::check_is_member( (int) $friend_ids[$i], $group_id ) )
				continue;

			// If user already has invite, they cannot be added
			if ( BP_Groups_Member::check_has_invite( (int) $friend_ids[$i], $group_id )  )
				continue;

			// If user is not group admin and friend is banned, they cannot be invited
			if ( ( false === $is_group_admin ) && BP_Groups_Member::check_is_banned( (int) $friend_ids[$i], $group_id ) )
				continue;

			$invitable_count++;
		}

		return $invitable_count;
	}

	function get_user_ids_for_friendship( $friendship_id ) {
		global $wpdb, $bp;

		return $wpdb->get_row( $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM {$bp->friends->table_name} WHERE id = %d", $friendship_id ) );
	}

	function delete_all_for_user( $user_id ) {
		global $wpdb, $bp;

		// Get friends of $user_id
		$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id );

		// Delete all friendships related to $user_id
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE friend_user_id = %d OR initiator_user_id = %d", $user_id, $user_id ) );

		// Delete friend request notifications for members who have a notification from this user.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE component_name = 'friends' AND ( component_action = 'friendship_request' OR component_action = 'friendship_accepted' ) AND item_id = %d", $user_id ) );

		// Loop through friend_ids and update their counts
		foreach ( (array) $friend_ids as $friend_id ) {
			BP_Friends_Friendship::total_friend_count( $friend_id );
		}
	}
}

?>
