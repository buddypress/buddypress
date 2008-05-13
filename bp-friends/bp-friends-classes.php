<?php

/**************************************************************************
 bp_Friends
 
	model representing a user's friends
 **************************************************************************/
 
class BP_Friends
{

	/**************************************************************************
 	 bp_friends()
 	  
 	 Contructor function.
 	 **************************************************************************/
	function bp_friends()
	{
		global $wpdb, $userdata, $bp_friends_table_name;
		 
		$this->wpdb = &$wpdb;
		$this->userdata = &$userdata;
		$this->basePrefix = $wpdb->base_prefix;	
	}


	/**************************************************************************
 	 get_friends()
 	  
	 Get a list of friends for the current user.
 	 **************************************************************************/	
		
	function get_friends()
	{
		$id = $this->userdata->ID;
		
		global $bp_friends_table_name;
		
		if(bp_core_validate($id))
		{
			$sql = "SELECT initiator_user_id, friend_user_id
			 		FROM " . $bp_friends_table_name . "
					WHERE initiator_user_id = " . $id . "
					OR friend_user_id = " . $id . " 
					AND is_confirmed = 1";

			if(!$friends = $this->wpdb->get_results($sql))
			{
				return false;
			}
			
			for($i=0; $i<count($friends); $i++)
			{
				if($friends[$i]->initiator_user_id != $id)
				{
					$friend_id = $friends[$i]->initiator_user_id;
				}
				else
				{
					$friend_id = $friends[$i]->friend_user_id;
				}
				
				$sql = "SELECT meta_key, meta_value FROM " . $this->basePrefix . "usermeta 
						WHERE user_id = " . $friend_id;

				$friends_details[] = $this->wpdb->get_results($sql);
			
			}
			
			return $friends_details;
		}
		else {
			return false;
		}
	}

	
	/**************************************************************************
 	 search()
 	  
	 Find a user on the site based on someone entering search terms such as
	 a name, username or email address.
 	 **************************************************************************/	
 	 
	function search($terms) 
	{
		$terms = bp_core_clean($terms);
		
		$sql = "SELECT ID, display_name FROM " . $this->basePrefix . "users 
				WHERE user_login LIKE '%" . $terms . "%'
				OR user_nicename LIKE '%" . $terms . "%'
				OR user_email LIKE '%" . $terms . "%'
				ORDER BY user_nicename ASC";
		
		return $this->wpdb->get_results($sql);

	}

}

?>