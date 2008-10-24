<?php
/**
 * BP_Core_User class can be used by any component. It will fetch useful
 * details for any user when provided with a user_id.
 * 
 * Example:
 *    $user = new BP_Core_User( $user_id );
 *    $user_avatar = $user->avatar;
 *	  $user_email = $user->email;
 *    $user_status = $user->status;
 *    etc.
 * 
 * @package BuddyPress Core
 */
class BP_Core_User {
	var $id;
	var $avatar;
	var $avatar_thumb;
	var $avatar_mini;
	var $fullname;
	var $email;
	
	var $user_url;
	var $user_link;
	
	var $last_active;
	var $profile_last_updated;
	
	var $status;
	var $status_last_updated;
	
	/* Extras */
	var $total_friends;
	var $total_blogs;
	var $total_groups;
	
	function bp_core_user( $user_id, $populate_extras = false ) {
		if ( $user_id ) {
			$this->id = $user_id;
			$this->populate();
			
			if ( $populate_extras )
				$this->populate_extras();
		}
	}
	
	/**
	 * populate()
	 *
	 * Populate the instantiated class with data based on the User ID provided.
	 * 
	 * @package BuddyPress Core
 	 * @global $userdata WordPress user data for the current logged in user.
	 * @uses bp_core_get_userurl() Returns the URL with no HTML markup for a user based on their user id
	 * @uses bp_core_get_userlink() Returns a HTML formatted link for a user with the user's full name as the link text
	 * @uses bp_core_get_user_email() Returns the email address for the user based on user ID
	 * @uses get_usermeta() WordPress function returns the value of passed usermeta name from usermeta table
	 * @uses bp_core_get_avatar() Returns HTML formatted avatar for a user
	 * @uses bp_profile_last_updated_date() Returns the last updated date for a user.
	 */
	function populate() {
		$this->user_url = bp_core_get_userurl( $this->id );
		$this->user_link = bp_core_get_userlink( $this->id );
		
		$this->fullname = bp_core_get_userlink( $this->id, true );
		$this->email = bp_core_get_user_email( $this->id );
		$this->last_active = bp_core_get_last_activity( get_usermeta( $this->id, 'last_activity' ), __('active '), __(' ago') );
		
		if ( function_exists('xprofile_install') ) {
			$this->avatar = bp_core_get_avatar( $this->id, 2 );
			$this->avatar_thumb = bp_core_get_avatar( $this->id, 1 );
			$this->avatar_mini = bp_core_get_avatar( $this->id, 1, false, 25, 25 );
		}
		
		if ( function_exists('bp_statuses_install') ) {
			$this->status = null; // TODO: Fetch status updates.
			$this->status_last_updated = null;
		}
	}
	
	function populate_extras() {
		global $bp;
		
		$this->total_friends = BP_Friends_Friendship::total_friend_count( $this->id );

		if ( $this->total_friends ) {
			if ( $this->total_friends == 1 )
				$this->total_friends .= ' ' . __( 'friend', 'buddypress' );
			else
				$this->total_friends .= ' ' . __( 'friends', 'buddypress' );
			
			$this->total_friends = '<a href="' . $this->user_url . $bp['friends']['slug'] . '" title="' . sprintf( __( "%s's friend list", 'buddypress' ), $this->fullname ) . '">' . $this->total_friends . '</a>';
		}
		
		if ( $this->total_blogs ) {
			if ( $this->total_blogs == 1 )
				$this->total_blogs .= ' ' . __( 'blog', 'buddypress' );
			else
				$this->total_blogs .= ' ' . __( 'blogs', 'buddypress' );			
				
			$this->total_blogs = '<a href="' . $this->user_url . $bp['blogs']['slug'] . '" title="' . sprintf( __( "%s's blog list", 'buddypress' ), $this->fullname ) . '">' . $this->total_blogs . '</a>';
		}
	
		if ( function_exists('groups_install') ) {
			$this->total_groups = BP_Groups_Member::total_group_count( $this->id );
			
			if ( $this->total_groups ) {
				if ( $this->total_groups == 1 )
					$this->total_groups .= ' ' . __( 'group', 'buddypress' );
				else
					$this->total_groups .= ' ' . __( 'groups', 'buddypress' );

				$this->total_groups = '<a href="' . $this->user_url . $bp['groups']['slug'] . '" title="' . sprintf( __( "%s's group list", 'buddypress' ), $this->fullname ) . '">' . $this->total_groups . '</a>';
			}
		}
	}
	
	/* Static Functions */
	
	function get_newest_users( $limit = 5 ) {
		global $wpdb;
		
		if ( !$limit )
			$limit = 5;
			
		return $wpdb->get_results( $wpdb->prepare( "SELECT ID as user_id, DATE_ADD( user_registered, INTERVAL " . get_option('gmt_offset') . " HOUR ) as user_registered FROM {$wpdb->base_prefix}users WHERE spam = 0 AND deleted = 0 AND user_status = 0 ORDER BY user_registered DESC LIMIT %d", $limit ) );
	}
	
	function get_active_users( $limit = 5 ) {
		global $wpdb;

		if ( !$limit )
			$limit = 5;
			
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}usermeta um WHERE meta_key = 'last_activity' ORDER BY FROM_UNIXTIME(meta_value) DESC LIMIT %d", $limit ) );
	}

	function get_popular_users( $limit = 5 ) {
		global $wpdb;
				
		if ( !function_exists('friends_install') )
			return false;
		
		if ( !$limit )
			$limit = 5;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}usermeta um WHERE meta_key = 'total_friend_count' ORDER BY CONVERT(meta_value, SIGNED) DESC LIMIT %d", $limit ) );
	}
	
	function get_online_users( $limit = 5 ) {
		global $wpdb;
		
		if ( !$limit )
			$limit = 5;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}usermeta um WHERE meta_key = 'last_activity' AND DATE_ADD( FROM_UNIXTIME(meta_value), INTERVAL 5 MINUTE ) >= NOW() ORDER BY FROM_UNIXTIME(meta_value) DESC LIMIT %d", $limit ) );		
	}
	
	function get_users_by_letter( $letter, $limit = 5 ) {
		global $wpdb, $bp;
		
		if ( !function_exists('xprofile_install') )
			return false;
		
		if ( !$bp ) {
			bp_core_setup_globals();
			xprofile_setup_globals();
		}
		
		if ( !$limit )
			$limit = 5;
		
		if ( strlen($letter) > 1 || is_numeric($letter) || !$letter )
			return false;
		
		like_escape($letter);
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT um.user_id FROM {$wpdb->base_prefix}usermeta um LEFT JOIN {$bp['profile']['table_name_data']} pd ON um.user_id = pd.user_id LEFT JOIN {$bp['profile']['table_name_fields']} pf ON pd.field_id = pf.id WHERE pf.name = 'First Name' AND pd.value LIKE '$letter%%' ORDER BY pf.name DESC" ) ); 
	}
}

?>