<?php

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
	
	function bp_friends_friendship( $id = null, $is_request = false, $populate_friend_details = true ) {
		$this->is_request = $is_request;
		
		if ( $id ) {
			$this->id = $id;
			$this->populate_friend_details = $populate_friend_details;
			$this->populate( $this->id );
		}
	}

	function populate() {
		global $wpdb, $bp, $creds;
		
		if ( $friendship = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $bp['friends']['table_name'] . " WHERE id = %d", $this->id ) ) ) {
			$this->initiator_user_id = $friendship->initiator_user_id;
			$this->friend_user_id = $friendship->friend_user_id;
			$this->is_confirmed = $friendship->is_confirmed;
			$this->is_limited = $friendship->is_limited;
			$this->date_created = $friendship->date_created;
		}
		
		// if running from ajax.
		if ( !$bp['current_userid'] )
			$bp['current_userid'] = $creds['current_userid'];
		
		if ( $this->populate_friend_details ) {
			if ( $this->friend_user_id == $bp['current_userid'] ) {
				$this->friend = new BP_Core_User( $this->initiator_user_id );
			} else {
				$this->friend = new BP_Core_User( $this->friend_user_id );
			}
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			// Update
			$result = $wpdb->query( $wpdb->prepare( "UPDATE " . $bp['friends']['table_name'] . " SET initiator_user_id = %d, friend_user_id = %d, is_confirmed = %d, is_limited = %d, date_created = FROM_UNIXTIME(%d) ) WHERE id = %d", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created, $this->id ) );
		} else {
			// Save
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['friends']['table_name'] . " ( initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created ) VALUES ( %d, %d, %d, %d, FROM_UNIXTIME(%d) )", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created ) );
			$this->id = $wpdb->insert_id;
		}
		
		return $result;
	}
	
	function delete() {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['friends']['table_name'] . " WHERE id = %d", $this->id ) );
	}
	
	/* Static Functions */
	
	function get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false ) {
		global $wpdb, $bp;
		
		if ( $friend_requests_only ) {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 0" );
			$friend_sql = $wpdb->prepare ( " WHERE friend_user_id = %d", $user_id );
		} else {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 1" );
			$friend_sql = $wpdb->prepare ( " WHERE (initiator_user_id = %d OR friend_user_id = %d)", $user_id, $user_id );
		}

		$friends = $wpdb->get_results( $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM " . $bp['friends']['table_name'] . " $friend_sql $oc_sql ORDER BY date_created DESC" ) );
		
		for ( $i = 0; $i < count($friends); $i++ ) {
			if ( $assoc_arr )
				$fids[] = array( 'user_id' => ( $friends[$i]->friend_user_id == $user_id ) ? $friends[$i]->initiator_user_id : $friends[$i]->friend_user_id );
			else
				$fids[] = ( $friends[$i]->friend_user_id == $user_id ) ? $friends[$i]->initiator_user_id : $friends[$i]->friend_user_id;
		}
		
		return $fids;
	}
	
	function get_friendship_ids( $user_id, $friend_requests_only = false ) {
		global $wpdb, $bp;

		if ( $friend_requests_only ) {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 0" );
			$friend_sql = $wpdb->prepare ( " WHERE friend_user_id = %d", $user_id );
		} else {
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 1" );
			$friend_sql = $wpdb->prepare ( " WHERE (initiator_user_id = %d OR friend_user_id = %d)", $user_id, $user_id );
		}
		
		return $wpdb->get_col( $wpdb->prepare( "SELECT id FROM " . $bp['friends']['table_name'] . " $friend_sql $oc_sql" ) );
	}
	
	function get_friendship_id( $user_id, $friend_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $bp['friends']['table_name'] . " WHERE ( initiator_user_id = %d AND friend_user_id = %d ) OR ( initiator_user_id = %d AND friend_user_id = %d ) AND is_confirmed = 1", $user_id, $friend_id, $friend_id, $user_id ) );
	}
	
	function total_friend_count( $user_id ) {
		global $wpdb, $bp;
		
		/* This is stored in 'total_friend_count' usermeta. 
		   This function will recalculate, update and return. */
		
		$sql = $wpdb->prepare( "SELECT count(id) FROM " . $bp['friends']['table_name'] . " WHERE (initiator_user_id = %d OR friend_user_id = %d) AND is_confirmed = 1", $user_id, $user_id );

		if ( !$friend_count = $wpdb->get_var( $sql ) )
			return 0;
	
		if ( !$friend_count )
			return 0;
		
		update_usermeta( $user_id, 'total_friend_count', $friend_count );
		
		return $friend_count;
	}
	
	function search_friends( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		$usermeta_table = $wpdb->prefix . 'usermeta';
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( !$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id ) )
			return false;

		// Get all the user ids for the current user's friends.
		$fids = implode( ',', $friend_ids );

		// filter the user_ids based on the search criteria.
		if ( function_exists('xprofile_install') ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM " . $bp['profile']['table_name_data'] . " WHERE user_id IN ($fids) AND value LIKE '$filter%%'" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM $usermeta_table WHERE user_id IN ($fids) AND meta_key = 'nickname' AND meta_value LIKE '$filter%%'" );
		}

		$filtered_friends = $wpdb->get_results( $sql, ARRAY_A );	

		if ( !$filtered_friends )
			return false;

		// Get the total number of friendships
		$total_friends = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM " . $bp['friends']['table_name'] . " WHERE (friend_user_id IN ($filtered_fids) AND initiator_user_id = %d) OR (initiator_user_id IN ($filtered_fids) AND friend_user_id = %d)", $user_id, $user_id ) );
		
		return array( 'friends' => $filtered_friends, 'total' => $total_friends );
	}
		
	function check_is_friend( $loggedin_userid, $possible_friend_userid ) {
		global $wpdb, $bp;
		
		if ( !$loggedin_userid || !$possible_friend_userid )
			return false;
			
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT id, is_confirmed FROM " . $bp['friends']['table_name'] . " WHERE (initiator_user_id = %d AND friend_user_id = %d) OR (initiator_user_id = %d AND friend_user_id = %d)", $loggedin_userid, $possible_friend_userid, $possible_friend_userid, $loggedin_userid ) );
		
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
	
	function get_bulk_last_active( $user_ids ) {
		global $wpdb, $bp;

		return $wpdb->get_results( $wpdb->prepare( "SELECT meta_value as last_activity, user_id FROM {$wpdb->base_prefix}usermeta WHERE meta_key = 'last_activity' AND user_id IN ( {$user_ids} ) ORDER BY meta_value DESC" ) );
	}
	
	function accept($friendship_id) {
		global $wpdb, $bp;

	 	return $wpdb->query( $wpdb->prepare( "UPDATE " . $bp['friends']['table_name'] . " SET is_confirmed = 1, date_created = FROM_UNIXTIME(%d) WHERE id = %d AND friend_user_id = %d", time(), $friendship_id, $bp['loggedin_userid'] ) );
	}
	
	function reject($friendship_id) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['friends']['table_name'] . " WHERE id = %d AND friend_user_id = %d", $friendship_id, $bp['loggedin_userid'] ) );
	}
	
	function search_users( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		$usermeta_table = $wpdb->base_prefix . 'usermeta';
		$users_table = $wpdb->base_prefix . 'users';

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// filter the user_ids based on the search criteria.
		if ( function_exists('xprofile_install') ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT d.user_id as id FROM " . $bp['profile']['table_name_data'] . " d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%' ORDER BY d.value DESC $pag_sql" );
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
		if ( function_exists('xprofile_install') ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT count(d.user_id) FROM " . $bp['profile']['table_name_data'] . " d, $users_table u WHERE d.user_id = u.id AND d.value LIKE '$filter%%'" );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT count(user_id) FROM $usermeta_table WHERE meta_value LIKE '$filter%%'" );
		}

		$user_count = $wpdb->get_col($sql);	
		
		if ( !$user_count )
			return false;

		return $user_count[0];
	}
	
	function sort_by_name( $user_ids ) {
		global $wpdb, $bp;
		
		if ( !function_exists( 'xprofile_install') )
			return false;
	
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM " . $bp['profile']['table_name_data'] . " pd, " . $bp['profile']['table_name_fields'] . " pf WHERE pf.id = pd.field_id AND pf.name = %s AND pd.user_id IN ( {$user_ids} ) ORDER BY pd.value ASC", BP_XPROFILE_FULLNAME_FIELD_NAME ) );
	}
	
	function get_random_friends( $user_id, $total_friends = 5 ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM " . $bp['friends']['table_name'] . " WHERE (friend_user_id = %d || initiator_user_id = %d) && is_confirmed = 1 ORDER BY rand() LIMIT %d", $user_id, $user_id, $total_friends );
		$results = $wpdb->get_results($sql);

		for ( $i = 0; $i < count($results); $i++ ) {
			$fids[] = ( $results[$i]->friend_user_id == $user_id ) ? $results[$i]->initiator_user_id : $results[$i]->friend_user_id;
		}
		
		// remove duplicates
		if ( count($fids) > 0 )
			return array_flip(array_flip($fids));
		else
			return false;
	}
	
	function get_invitable_friend_count( $user_id, $group_id ) {
		global $wpdb, $bp;

		$friend_ids = BP_Friends_Friendship::get_friend_ids( $user_id );
		
		$invitable_count = 0;
		for ( $i = 0; $i < count($friend_ids); $i++ ) {
			
			if ( BP_Groups_Member::check_is_member( (int)$friend_ids[$i], $group_id ) )
				continue;
			
			if ( BP_Groups_Member::check_has_invite( (int)$friend_ids[$i], $group_id )  )
				continue;
				
			$invitable_count++;
		}

		return $invitable_count;
	}
	
	function get_user_ids_for_friendship( $friendship_id ) {
		global $wpdb, $bp;

		return $wpdb->get_row( $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM " . $bp['friends']['table_name'] . " WHERE id = %d", $friendship_id ) );
	}
	
	function delete_all_for_user( $user_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['friends']['table_name'] . " WHERE friend_user_id = %d OR initiator_user_id = %d", $user_id, $user_id ) ); 		
	}
}
	


?>