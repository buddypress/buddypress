<?php

/**************************************************************************
 bp_Friends
 
	model representing a user's friends
 **************************************************************************/
 
class BP_Friends {
	var $table_name;

	function bp_friends() {
		global $bp_friends_table_name;
		
		$this->table_name = $bp_friends_table_name;
	}

	function get_friends() {
		global $wpdb, $userdata;
		
		$id = $userdata->ID;
		
		$sql = $wpdb->prepare("SELECT initiator_user_id, friend_user_id	FROM $this->table_name	WHERE initiator_user_id = %d OR friend_user_id = %d	AND is_confirmed = 1", $id, $id);

		if ( !$friends = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($friends); $i++ ) {
			if ( $friends[$i]->initiator_user_id != $id ) {
				$friend_id = $friends[$i]->initiator_user_id;
			} else {
				$friend_id = $friends[$i]->friend_user_id;
			}
			
			$table_name = $wpdb->base_prefix . 'usermeta';
			
			$sql = $wpdb->prepare("SELECT meta_key, meta_value FROM $table_name WHERE user_id = %d", $friend_id);

			$friends_details[] = $wpdb->get_results($sql);
		
		}
		
		return $friends_details;
	}

	
	/**************************************************************************
 	 search()
 	  
	 Find a user on the site based on someone entering search terms such as
	 a name, username or email address.
 	 **************************************************************************/	
 	 
	function search($terms) {
		global $wpdb;
		
		$terms = bp_core_clean($terms);
		$table_name = $wpdb->base_prefix . 'users';
		
		$sql = $wpdb->prepare("SELECT ID, display_name FROM $table_name WHERE user_login LIKE '%%s%' OR user_nicename LIKE '%%s%' OR user_email LIKE '%%s%' ORDER BY user_nicename ASC", $terms, $terms, $terms);
		
		return $wpdb->get_results($sql);

	}

}

?>