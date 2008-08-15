<?php

class BP_Friends_Friendship {
	var $id;
	var $initiator_user_id;
	var $friend_user_id;
	var $is_confirmed;
	var $is_limited;
	var $date_created;
	
	var $is_request;
	
	var $friend;
	
	function bp_friends_friendship( $id = null, $is_request = false ) {
		$this->is_request = $is_request;
		
		if ( $id ) {
			$this->id = $id;
			$this->populate( $this->id );
		}
	}

	function populate() {
		global $wpdb, $bp, $creds;
		
		$sql = $wpdb->prepare( "SELECT * FROM " . $bp['friends']['table_name'] . " WHERE id = %d", $this->id );
		$friendship = $wpdb->get_row($sql);
		
		if ( $friendship ) {
			if ( !$this->is_request ) {
				$this->initiator_user_id = $friendship->initiator_user_id;
				$this->friend_user_id = $friendship->friend_user_id;
			} else {
				$this->initiator_user_id = $friendship->friend_user_id;
				$this->friend_user_id = $friendship->initiator_user_id;		
			}

			$this->is_confirmed = $friendship->is_confirmed;
			$this->is_limited = $friendship->is_limited;
			$this->date_created = $friendship->date_created;
		}
		
		// if running from ajax.
		if ( !$bp['current_userid'] )
			$bp['current_userid'] = $creds['current_userid'];
			
		if ( $this->friend_user_id == $bp['current_userid'] ) {
			$this->friend = new BP_Core_User( $this->initiator_user_id );
		} else {
			$this->friend = new BP_Core_User( $this->friend_user_id );
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare( "UPDATE " . $bp['friends']['table_name'] . " SET initiator_user_id = %d, friend_user_id = %d, is_confirmed = %d, is_limited = %d, date_created = FROM_UNIXTIME(%d) ) WHERE id = %d", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created, $this->id );
		} else {
			// Save
			$sql = $wpdb->prepare( "INSERT INTO " . $bp['friends']['table_name'] . " ( initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created ) VALUES ( %d, %d, %d, %d, FROM_UNIXTIME(%d) )", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created );
		}
		
		return $wpdb->query($sql);
	}
	
	function delete() {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['friends']['table_name'] . " WHERE id = %d", $this->id ) );
	}
	
	/* Static Functions */
	
	function get_friendship_ids( $user_id, $only_limited = false, $limit = null, $page = null, $get_requests = false ) {
		global $wpdb, $bp;

		if ( !$user_id )
			$user_id = $bp['current_userid'];
		
		if ( $get_requests )
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 0" );
		else
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 1" );
			
		if ( $only_limited )
			$ol_sql = $wpdb->prepare( "AND is_limited = 1" );
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			
		if ( $get_requests )
			$friend_sql = $wpdb->prepare ( " WHERE friend_user_id = %d", $user_id );
		else
			$friend_sql = $wpdb->prepare ( " WHERE (initiator_user_id = %d OR friend_user_id = %d)", $user_id, $user_id );
			
		$sql = "SELECT id FROM " . $bp['friends']['table_name'] . " $friend_sql $oc_sql $ol_sql $pag_sql";
		
		if ( !$friendship_ids = $wpdb->get_results( $sql ) )
			return false;
		
		return $friendship_ids;
	}
	
	function total_friend_count( $user_id = false ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];

		$sql = $wpdb->prepare( "SELECT count(id) FROM " . $bp['friends']['table_name'] . " WHERE (initiator_user_id = %d OR friend_user_id = %d) AND is_confirmed = 1", $user_id, $user_id );

		if ( !$friend_count = $wpdb->get_var( $sql ) )
			return false;
		
		return $friend_count;
	}
	
	function search_friends( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		$usermeta_table = $wpdb->prefix . 'usermeta';
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// Get all the user ids for the current user's friends.
		$fids = implode( ',', BP_Friends_Friendship::get_friend_ids( $user_id ) );

		// filter the user_ids based on the search criteria.
		if ( BP_XPROFILE_IS_INSTALLED ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id as id FROM " . $bp['xprofile']['table_name_data'] . " WHERE user_id IN ($fids) AND value LIKE '$filter%%'" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id as id FROM $usermeta_table WHERE user_id IN ($fids) AND meta_key = 'nickname' AND meta_value LIKE '$filter%%'" );
		}

		$filtered_fids = $wpdb->get_col($sql);	

		if ( !$filtered_fids )
			return false;

		$filtered_fids = implode( ',', $filtered_fids );
		
		// Get the friendship ids for the friends
		$fs_ids = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM " . $bp['friends']['table_name'] . " WHERE (friend_user_id IN ($filtered_fids) AND initiator_user_id = %d) OR (initiator_user_id IN ($filtered_fids) AND friend_user_id = %d) $pag_sql", $user_id, $user_id ) );
		
		// Get the total number of friendships
		$fs_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM " . $bp['friends']['table_name'] . " WHERE (friend_user_id IN ($filtered_fids) AND initiator_user_id = %d) OR (initiator_user_id IN ($filtered_fids) AND friend_user_id = %d)", $user_id, $user_id ) );
		
		$friendships = friends_get_friendships( $user_id, $fs_ids, 5, 1, false, $fs_count );

		return $friendships;
	}
		
	function check_is_friend( $loggedin_userid, $possible_friend_userid ) {
		global $wpdb, $bp;
		
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT id, is_confirmed FROM " . $bp['friends']['table_name'] . " WHERE (initiator_user_id = %d AND friend_user_id = %d) OR (initiator_user_id = %d AND friend_user_id = %d)", $bp['loggedin_userid'], $possible_friend_userid, $possible_friend_userid, $bp['loggedin_userid'] ) );
		
		if ( $result ) {
			if ( (int)$result[0]->is_confirmed == 0 ) {
				return 'pending';
			} else {
				return 'is_friend';
			}
		} else {
			return 'not_friends';
		}
	}
	
	function accept($friend_userid) {
		global $wpdb, $bp;
		return $wpdb->query( $wpdb->prepare( "UPDATE " . $bp['friends']['table_name'] . " SET is_confirmed = 1, date_created = FROM_UNIXTIME(%d) WHERE initiator_user_id = %d AND friend_user_id = %d", time(), $friend_userid, $bp['loggedin_userid'] ) );
	}
	
	function reject($friend_userid) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['friends']['table_name'] . " WHERE initiator_user_id = %d AND friend_user_id = %d", $friend_userid, $bp['loggedin_userid'] ) );
	}
	
	function search_users( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		$usermeta_table = $wpdb->base_prefix . 'usermeta';
		$users_table = $wpdb->base_prefix . 'users';

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// filter the user_ids based on the search criteria.
		if ( BP_XPROFILE_IS_INSTALLED ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT d.user_id as id FROM " . $bp['xprofile']['table_name_data'] . " d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%' ORDER BY d.value DESC $pag_sql" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id as id FROM $usermeta_table WHERE meta_value LIKE '$filter%%' ORDER BY d.value DESC $pag_sql" );
		}
		
		$filtered_fids = $wpdb->get_col($sql);	
		
		if ( !$filtered_fids )
			return false;

		return $filtered_fids;
	}
	
	function search_users_count( $filter ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		$usermeta_table = $wpdb->prefix . 'usermeta';
		$users_table = $wpdb->base_prefix . 'users';
		
		// filter the user_ids based on the search criteria.
		if ( BP_XPROFILE_IS_INSTALLED ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT count(d.user_id) FROM " . $bp['xprofile']['table_name_data'] . " d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%'" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT count(user_id) FROM $usermeta_table WHERE meta_value LIKE '$filter%%'" );
		}

		$user_count = $wpdb->get_col($sql);	
		
		if ( !$user_count )
			return false;

		return $user_count[0];
	}
	
	function get_friend_ids( $user_id ) {
		global $wpdb, $bp;
		
		$sql = $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM " . $bp['friends']['table_name'] . " WHERE (friend_user_id = %d || initiator_user_id = %d) && is_confirmed = 1", $user_id, $user_id );
		$results = $wpdb->get_results($sql);
		
		for ( $i = 0; $i < count($results); $i++ ) {
			$fids[] = ( $results[$i]->friend_user_id == $user_id ) ? $results[$i]->initiator_user_id : $results[$i]->friend_user_id;
		}
		
		// remove duplicates
		return array_flip(array_flip($fids));
	}
}
	


?>