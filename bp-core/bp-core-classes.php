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
	 * @uses bp_core_fetch_avatar() Returns HTML formatted avatar for a user
	 * @uses bp_profile_last_updated_date() Returns the last updated date for a user.
	 */
	function populate() {
		if ( function_exists( 'xprofile_install' ) )
			$this->profile_data = $this->get_profile_data();

		if ( $this->profile_data ) {
			$this->user_url = bp_core_get_user_domain( $this->id, $this->profile_data['user_nicename'], $this->profile_data['user_login'] );
			$this->fullname = attribute_escape( $this->profile_data[BP_XPROFILE_FULLNAME_FIELD_NAME]['field_data'] );
			$this->user_link = "<a href='{$this->user_url}'>{$this->fullname}</a>";
			$this->email = attribute_escape( $this->profile_data['user_email'] );
		} else {
			$this->user_url = bp_core_get_userurl( $this->id );
			$this->user_link = bp_core_get_userlink( $this->id );
			$this->fullname = attribute_escape( bp_core_get_user_displayname( $this->id ) );
			$this->email = attribute_escape( bp_core_get_user_email( $this->id ) );
		}

		/* Cache a few things that are fetched often */
		wp_cache_set( 'bp_user_fullname_' . $this->id, $this->fullname, 'bp' );
		wp_cache_set( 'bp_user_email_' . $this->id, $this->email, 'bp' );
		wp_cache_set( 'bp_user_url_' . $this->id, $this->user_url, 'bp' );

		$this->avatar = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'full' ) );
		$this->avatar_thumb = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'thumb' ) );
		$this->avatar_mini = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'thumb', 'width' => 30, 'height' => 30 ) );

		$this->last_active = bp_core_get_last_activity( get_usermeta( $this->id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) );
	}

	function populate_extras() {
		global $bp;

		if ( function_exists('friends_install') )
			$this->total_friends = BP_Friends_Friendship::total_friend_count( $this->id );

		if ( function_exists('groups_install') ) {
			$this->total_groups = BP_Groups_Member::total_group_count( $this->id );

			if ( $this->total_groups ) {
				if ( 1 == $this->total_groups )
					$this->total_groups .= ' ' . __( 'group', 'buddypress' );
				else
					$this->total_groups .= ' ' . __( 'groups', 'buddypress' );
			}
		}
	}

	function get_profile_data() {
		return BP_XProfile_ProfileData::get_all_for_user( $this->id );
	}

	/* Static Functions */

	function get_users( $type, $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
		global $wpdb, $bp;

		$sql = array();

		$sql['select_main'] = "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email";

		if ( 'active' == $type || 'online' == $type )
			$sql['select_active'] = ", um.meta_value as last_activity";

		if ( 'popular' == $type )
			$sql['select_popular'] = ", um.meta_value as total_friend_count";

		if ( 'alphabetical' == $type )
			$sql['select_alpha'] = ", pd.value as fullname";

		$sql['from'] = "FROM " . CUSTOM_USER_TABLE . " u LEFT JOIN " . CUSTOM_USER_META_TABLE . " um ON um.user_id = u.ID";

		if ( $search_terms && function_exists( 'xprofile_install' ) || 'alphabetical' == $type )
			$sql['join_profiledata'] = "LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id";

		$sql['where'] = "WHERE u.spam = 0 AND u.deleted = 0 AND u.user_status = 0";

		if ( 'active' == $type || 'online' == $type )
			$sql['where_active'] = "AND um.meta_key = 'last_activity'";

		if ( 'popular' == $type )
			$sql['where_popular'] = "AND um.meta_key = 'total_friend_count'";

		if ( 'online' == $type )
			$sql['where_online'] = "AND DATE_ADD( FROM_UNIXTIME(um.meta_value), INTERVAL 5 MINUTE ) >= NOW()";

		if ( 'alphabetical' == $type )
			$sql['where_alpha'] = "AND pd.field_id = 1";

		if ( $user_id && function_exists( 'friends_install' ) ) {
			$friend_ids = friends_get_friend_user_ids( $user_id );
			$friend_ids = $wpdb->escape( implode( ',', (array)$friend_ids ) );

			$sql['where_friends'] = "AND u.ID IN ({$friend_ids})";
		}

		if ( $search_terms && function_exists( 'xprofile_install' ) ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$sql['where_searchterms'] = "AND pd.value LIKE '%%$search_terms%%'";
		}

		switch ( $type ) {
			case 'active': default:
				$sql[] = "ORDER BY um.meta_value DESC";
				break;
			case 'newest':
				$sql[] = "ORDER BY u.user_registered DESC";
				break;
			case 'alphabetical':
				$sql[] = "ORDER BY pd.value ASC";
				break;
			case 'random':
				$sql[] = "ORDER BY rand()";
				break;
			case 'online':
				$sql[] = "ORDER BY FROM_UNIXTIME(um.meta_value) DESC";
				break;
			case 'popular':
				$sql[] = "ORDER BY CONVERT(um.meta_value, SIGNED) DESC";
				break;
		}

		if ( $limit && $page )
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		/* Get paginated results */
		$paged_users = $wpdb->get_results( $wpdb->prepare( join( ' ', (array)$sql ) ) );

	//	var_dump( join( ' ', $sql ) );

		/* Re-jig the SQL so we can get the total user count */
		unset( $sql['select_main'] );

		if ( !empty( $sql['select_active'] ) )
			unset( $sql['select_active'] );

		if ( !empty( $sql['select_popular'] ) )
			unset( $sql['select_popular'] );

		if ( !empty( $sql['select_alpha'] ) )
			unset( $sql['select_alpha'] );

		if ( !empty( $sql['pagination'] ) )
			unset( $sql['pagination'] );

		array_unshift( $sql, "SELECT COUNT(DISTINCT u.ID)" );

		/* Get total user results */
		$total_users = $wpdb->get_var( $wpdb->prepare( join( ' ', (array)$sql ) ) );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		foreach ( $paged_users as $user )
			$user_ids[] = $user->id;

		$user_ids = $wpdb->escape( join( ',', (array)$user_ids ) );

		/* Add additional data to the returned results */
		$paged_users = BP_Core_User::get_user_extras( &$paged_users, $user_ids, $type );

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	function get_users_by_letter( $letter, $limit = null, $page = 1 ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( strlen($letter) > 1 || is_numeric($letter) || !$letter )
			return false;

		$letter = like_escape( $wpdb->escape( $letter ) );

		$total_users_sql = apply_filters( 'bp_core_users_by_letter_count_sql', $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) FROM " . CUSTOM_USER_TABLE . " u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id LEFT JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id WHERE u.spam = 0 AND u.deleted = 0 AND u.user_status = 0 AND pf.name = %s AND pd.value LIKE '$letter%%' ORDER BY pd.value ASC", BP_XPROFILE_FULLNAME_FIELD_NAME ), $letter );
		$paged_users_sql = apply_filters( 'bp_core_users_by_letter_sql', $wpdb->prepare( "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email FROM " . CUSTOM_USER_TABLE . " u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id LEFT JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id WHERE u.spam = 0 AND u.deleted = 0 AND u.user_status = 0 AND pf.name = %s AND pd.value LIKE '$letter%%' ORDER BY pd.value ASC{$pag_sql}", BP_XPROFILE_FULLNAME_FIELD_NAME ), $letter, $pag_sql );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		foreach ( $paged_users as $user )
			$user_ids[] = $user->id;

		$user_ids = $wpdb->escape( join( ',', (array)$user_ids ) );

		/* Add additional data to the returned results */
		$paged_users = BP_Core_User::get_user_extras( &$paged_users, &$user_ids );

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	function get_specific_users( $user_ids, $limit = null, $page = 1 ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$user_sql = " AND user_id IN ( " . $wpdb->escape( $user_ids ) . " ) ";

		$total_users_sql = apply_filters( 'bp_core_get_specific_users_count_sql', $wpdb->prepare( "SELECT COUNT(DISTINCT ID) FROM " . CUSTOM_USER_TABLE . " WHERE spam = 0 AND deleted = 0 AND user_status = 0 AND ID IN ( " . $wpdb->escape( $user_ids ) . " ) " ), $wpdb->escape( $user_ids ) );
		$paged_users_sql = apply_filters( 'bp_core_get_specific_users_count_sql', $wpdb->prepare( "SELECT DISTINCT ID as id, user_registered, user_nicename, user_login, user_email FROM " . CUSTOM_USER_TABLE . " WHERE spam = 0 AND deleted = 0 AND user_status = 0 AND ID IN ( " . $wpdb->escape( $user_ids ) . " ) {$pag_sql}" ), $wpdb->escape( $user_ids ) );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		foreach ( $paged_users as $user )
			$user_ids[] = $user->id;

		$user_ids = $wpdb->escape( join( ',', (array)$user_ids ) );

		/* Add additional data to the returned results */
		$paged_users = BP_Core_User::get_user_extras( &$paged_users, &$user_ids );

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	function search_users( $search_terms, $limit = null, $page = 1 ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$search_terms = like_escape( $wpdb->escape( $search_terms ) );

		$total_users_sql = apply_filters( 'bp_core_search_users_count_sql', "SELECT COUNT(DISTINCT u.ID) as id FROM " . CUSTOM_USER_TABLE . " u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id WHERE u.spam = 0 AND u.deleted = 0 AND u.user_status = 0 AND pd.value LIKE '%%$search_terms%%' ORDER BY pd.value ASC", $search_terms );
		$paged_users_sql = apply_filters( 'bp_core_search_users_sql', "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email FROM " . CUSTOM_USER_TABLE . " u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id WHERE u.spam = 0 AND u.deleted = 0 AND u.user_status = 0 AND pd.value LIKE '%%$search_terms%%' ORDER BY pd.value ASC{$pag_sql}", $search_terms, $pag_sql );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		foreach ( $paged_users as $user )
			$user_ids[] = $user->id;

		$user_ids = $wpdb->escape( join( ',', (array)$user_ids ) );

		/* Add additional data to the returned results */
		$paged_users = BP_Core_User::get_user_extras( &$paged_users, &$user_ids );

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	function get_user_extras( $paged_users, $user_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $user_ids ) )
			return $paged_users;

		/* Fetch the user's full name */
		if ( function_exists( 'xprofile_install' ) && 'alphabetical' != $type ) {
			$names = $wpdb->get_results( $wpdb->prepare( "SELECT pd.user_id as id, pd.value as fullname FROM {$bp->profile->table_name_fields} pf, {$bp->profile->table_name_data} pd WHERE pf.id = pd.field_id AND pf.name = %s AND pd.user_id IN ( {$user_ids} )", BP_XPROFILE_FULLNAME_FIELD_NAME ) );
			for ( $i = 0; $i < count( $paged_users ); $i++ ) {
				foreach ( $names as $name ) {
					if ( $name->id == $paged_users[$i]->id )
						$paged_users[$i]->fullname = $name->fullname;
				}
			}
		}

		/* Fetch the user's total friend count */
		if ( 'popular' != $type ) {
			$friend_count = $wpdb->get_results( "SELECT user_id as id, meta_value as total_friend_count FROM " . CUSTOM_USER_META_TABLE . " WHERE meta_key = 'total_friend_count' AND user_id IN ( {$user_ids} )" );
			for ( $i = 0; $i < count( $paged_users ); $i++ ) {
				foreach ( $friend_count as $count ) {
					if ( $count->id == $paged_users[$i]->id )
						$paged_users[$i]->total_friend_count = (int)$count->total_friend_count;
				}
			}
		}

		/* Fetch the user's last_activity */
		if ( 'active' != $type ) {
			$user_activity = $wpdb->get_results( "SELECT user_id as id, meta_value as last_activity FROM " . CUSTOM_USER_META_TABLE . " WHERE meta_key = 'last_activity' AND user_id IN ( {$user_ids} )" );
			for ( $i = 0; $i < count( $paged_users ); $i++ ) {
				foreach ( $user_activity as $activity ) {
					if ( $activity->id == $paged_users[$i]->id )
						$paged_users[$i]->last_activity = (int)$activity->last_activity;
				}
			}
		}

		return $paged_users;
	}
}


/**
 * BP_Core_Notification class can be used by any component.
 * It will handle the fetching, saving and deleting of a user notification.
 *
 * @package BuddyPress Core
 */

class BP_Core_Notification {
	var $id;
	var $item_id;
	var $secondary_item_id = null;
	var $user_id;
	var $component_name;
	var $component_action;
	var $date_notified;
	var $is_new;

	function bp_core_notification( $id = false ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->core->table_name_notifications} WHERE id = %d", $this->id ) ) ) {
			$this->item_id = $notification->item_id;
			$this->secondary_item_id = $notification->secondary_item_id;
			$this->user_id = $notification->user_id;
			$this->component_name = $notification->component_name;
			$this->component_action = $notification->component_action;
			$this->date_notified = $notification->date_notified;
			$this->is_new = $notification->is_new;
		}
	}

	function save() {
		global $wpdb, $bp;

		if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare( "UPDATE {$bp->core->table_name_notifications} SET item_id = %d, secondary_item_id = %d, user_id = %d, component_name = %s, component_action = %d, date_notified = FROM_UNIXTIME(%d), is_new = %d ) WHERE id = %d", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new, $this->id );
		} else {
			// Save
			$sql = $wpdb->prepare( "INSERT INTO {$bp->core->table_name_notifications} ( item_id, secondary_item_id, user_id, component_name, component_action, date_notified, is_new ) VALUES ( %d, %d, %d, %s, %s, FROM_UNIXTIME(%d), %d )", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new );
		}

		if ( !$result = $wpdb->query( $sql ) )
			return false;

		$this->id = $wpdb->insert_id;
		return true;
	}

	/* Static functions */

	function check_access( $user_id, $notification_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->core->table_name_notifications} WHERE id = %d AND user_id = %d", $notification_id, $user_id ) );
	}

	function get_all_for_user( $user_id ) {
		global $wpdb, $bp;

 		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->core->table_name_notifications} WHERE user_id = %d AND is_new = 1", $user_id ) );
	}

	function delete_for_user_by_type( $user_id, $component_name, $component_action ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE user_id = %d AND component_name = %s AND component_action = %s", $user_id, $component_name, $component_action ) );
	}

	function delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id ) {
		global $wpdb, $bp;

		if ( $secondary_item_id )
			$secondary_item_sql = $wpdb->prepare( " AND secondary_item_id = %d", $secondary_item_id );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE user_id = %d AND item_id = %d AND component_name = %s AND component_action = %s{$secondary_item_sql}", $user_id, $item_id, $component_name, $component_action ) );
	}

	function delete_from_user_by_type( $user_id, $component_name, $component_action ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = %s AND component_action = %s", $user_id, $component_name, $component_action ) );
	}

	function delete_all_by_type( $item_id, $component_name, $component_action, $secondary_item_id ) {
		global $wpdb, $bp;

		if ( $component_action )
			$component_action_sql = $wpdb->prepare( "AND component_action = %s", $component_action );

		if ( $secondary_item_id )
			$secondary_item_sql = $wpdb->prepare( "AND secondary_item_id = %d", $secondary_item_id );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = %s {$component_action_sql} {$secondary_item_sql}", $item_id, $component_name ) );
	}
}


?>