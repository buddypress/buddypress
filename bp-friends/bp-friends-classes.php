<?php

class BP_Friends_Friendship {
	var $id;
	var $initiator_user_id;
	var $friend_user_id;
	var $is_confirmed;
	var $is_limited;
	var $date_created;
	
	var $friend;
	
	function bp_friends_friendship( $id ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate( $this->id );
		}
	}
	
	function populate() {
		global $wpdb, $bp_friends_table_name;

		$sql = $wpdb->prepare( "SELECT * FROM $bp_friends_table_name WHERE id = %d", $this->id );
		$friendship = $wpdb->get_row($sql);
		
		if ( $friendship ) {
			$this->initiator_user_id = $friendship->initiator_user_id;
			$this->friend_user_id = $friendship->friend_user_id;
			$this->is_confirmed = $friendship->is_confirmed;
			$this->is_limited = $friendship->is_limited;
			$this->date_created = $friendship->date_created;
		}

		$this->friend = new BP_Friends_Friend( $this->friend_user_id );
	}
	
	function save() {
		global $wpdb, $bp_friends_table_name;
		
		if ( $this->id ) {
			// Update
			$wpdb->prepare( "UPDATE $bp_friends_table_name SET initiator_user_id = %d, friend_user_id = %d, is_confirmed = %d, is_limited = %d, date_created = FROM_UNIXTIME(%d) ) WHERE id = %d", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created, $this->id );
		} else {
			// Save
			$wpdb->prepare( "INSERT INTO $bp_friends_table_name ( initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created ) VALUES ( %d, %d, %d, %d, FROM_UNIXTIME(%d) )", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created );
		}
		
		return $wpdb->query($sql);
	}
	
	/* Static Functions */
	
	function get_friendship_ids( $user_id, $only_confirmed = true, $only_limited = false, $limit = null, $page = null ) {
		global $wpdb, $bp_friends_table_name;
		global $current_userid;

		if ( !$user_id )
			$user_id = $current_userid;
		
		if ( $only_confirmed )
			$oc_sql = $wpdb->prepare( "AND is_confirmed = 1" );
		
		if ( $only_limited )
			$ol_sql = $wpdb->prepare( "AND is_limited = 1" );
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			
		$sql = $wpdb->prepare( "SELECT f.id FROM $bp_friends_table_name f WHERE initiator_user_id = %d $oc_sql $ol_sql $pag_sql", $user_id );

		if ( !$friendship_ids = $wpdb->get_results( $sql ) )
			return false;
		
		return $friendship_ids;
	}
	
	function total_friend_count( $user_id = false ) {
		global $wpdb, $bp_friends_table_name;
		global $current_userid;
		
		if ( !$user_id )
			$user_id = $current_userid;

		$sql = $wpdb->prepare( "SELECT count(id) FROM $bp_friends_table_name WHERE initiator_user_id = %d", $user_id );

		if ( !$friend_count = $wpdb->get_var( $sql ) )
			return false;
		
		return $friend_count;
	}
	
	function search_friends( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb, $bp_friends_table_name, $bp_xprofile_table_name_data;
		
		like_escape($filter);
		$usermeta_table = $wpdb->prefix . 'usermeta';
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( BP_XPROFILE_IS_INSTALLED ) {
			$sql = $wpdb->prepare( "SELECT f.id FROM $bp_friends_table_name AS f, $bp_xprofile_table_name_data pd WHERE f.friend_user_id = pd.user_id AND f.initiator_user_id = %d AND pd.value LIKE '$filter%%'$pag_sql", $user_id );
		} else {
			$sql = $wpdb->prepare( "SELECT f.id FROM $bp_friends_table_name AS f, $usermeta_table um WHERE f.friend_user_id = um.user_id AND f.initiator_user_id = %d AND um.meta_key = 'nickname' AND um.meta_value LIKE '$filter%%'", $user_id );
		}
		
		$f_ids = $wpdb->get_results($sql);	
		
		if ( !$f_ids )
			return false;
			
		$friendships = friends_get_friendships( $user_id, &$f_ids );

		return $friendships;
	}
	
	function search_friends_count( $filter, $user_id ) {
		global $wpdb, $bp_friends_table_name, $bp_xprofile_table_name_data;
		
		like_escape($filter);
		$usermeta_table = $wpdb->prefix . 'usermeta';

		if ( BP_XPROFILE_IS_INSTALLED ) {
			$count_sql = $wpdb->prepare( "SELECT count(f.id) FROM $bp_friends_table_name AS f, $bp_xprofile_table_name_data pd WHERE f.friend_user_id = pd.user_id AND f.initiator_user_id = %d AND pd.value LIKE '$filter%%'$pag_sql", $user_id );
		} else {
			$count_sql = $wpdb->prepare( "SELECT count(f.id) FROM $bp_friends_table_name AS f, $usermeta_table um WHERE f.friend_user_id = um.user_id AND f.initiator_user_id = %d AND um.meta_key = 'nickname' AND um.meta_value LIKE '$filter%%'", $user_id );
		}

		$f_count = $wpdb->get_var($count_sql);

		return $f_count;		
	}
	
	function check_is_friend() {
		global $current_userid, $loggedin_userid;
		global $wpdb, $bp_friends_table_name;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM $bp_friends_table_name WHERE initiator_user_id = %d AND friend_user_id = %d", $loggedin_userid, $current_userid ) );
	}
}

class BP_Friends_Friend {
	var $id;
	var $avatar;
	var $user_link;
	
	var $last_active;
	var $profile_last_updated;
	
	var $status;
	var $status_last_updated;
	
	var $content_last_updated;
	
	function bp_friends_friend( $user_id ) {
		if ( $user_id ) {
			$this->id = $user_id;
			$this->populate( $this->id );
		}
	}
	
	function populate() {
		global $userdata;

		$this->user_link = bp_core_get_userlink( $this->id );
		$this->last_active = get_usermeta( $this->id, 'last_activity' ); 

		if ( BP_XPROFILE_IS_INSTALLED ) {
			$this->avatar = xprofile_get_avatar( $this->id, 1 );
			$this->profile_last_updated = bp_profile_last_updated_date( $this->id, false );
		}
		
		if ( BP_STATUSES_IS_INSTALLED ) {
			$this->status = null; // TODO: Fetch status updates.
			$this->status_last_updated = null;
		}
	}
}
	


?>