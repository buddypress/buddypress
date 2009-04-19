<?php

Class BP_Activity_Activity {
	var $id;
	var $item_id;
	var $secondary_item_id;
	var $user_id;
	var $primary_link;
	var $component_name;
	var $component_action;
	var $date_recorded;
	var $is_private = false;
	var $no_sitewide_cache = false;
	
	var $table_name;
	var $table_name_cached;
	var $for_secondary_user = false;
	
	function bp_activity_activity( $id = null, $populate = true ) {
		global $bp;
		
		if ( $id ) {
			$this->id = $id;
			
			if ( $populate )
				$this->populate();
		}
	}
	
	function populate() {
		global $wpdb, $bp;
		
		$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $this->id ) );

		$this->item_id = $activity->item_id;
		$this->secondary_item_id = $activity->secondary_item_id;
		$this->user_id = $activity->user_id;
		$this->component_name = $activity->component_name;
		$this->component_action = $activity->component_action;
		$this->date_recorded = $activity->date_recorded;
		$this->is_private = $activity->is_private;
		$this->no_sitewide_cache = $activity->no_sitewide_cache;
	}
	
	function save() {
		global $wpdb, $bp, $current_user;
		
		do_action( 'bp_activity_before_save', $this );

		if ( !$this->item_id || !$this->user_id || $this->is_private || !$this->component_name )
			return false;
			
		// Set the table names
		$this->table_name = $bp->activity->table_name_user_activity;
		$this->table_name_cached = $bp->activity->table_name_user_activity_cached;

		if ( !$this->exists() ) {
			// Insert the new activity into the activity table.
			$activity = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->table_name} ( item_id, secondary_item_id, user_id, component_name, component_action, date_recorded, is_private, no_sitewide_cache ) VALUES ( %d, %d, %d, %s, %s, FROM_UNIXTIME(%d), %d, %d )", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_recorded, $this->is_private, $this->no_sitewide_cache ) );

			// Fetch the formatted activity content so we can add it to the cache.
			if ( function_exists( $bp->{$this->component_name}->format_activity_function ) ) {
				if ( !$activity_content = call_user_func( $bp->{$this->component_name}->format_activity_function, $this->item_id, $this->user_id, $this->component_action, $this->secondary_item_id, $this->for_secondary_user ) )
					return false;
			}
			
			// Add the cached version of the activity to the cached activity table.
			$activity_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->table_name_cached} ( user_id, item_id, secondary_item_id, content, primary_link, component_name, component_action, date_cached, date_recorded, is_private ) VALUES ( %d, %d, %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d), %d )", $this->user_id, $this->item_id, $this->secondary_item_id, $activity_content['content'], $activity_content['primary_link'], $this->component_name, $this->component_action, time(), $this->date_recorded, $this->is_private ) );
			
			// Add the cached version of the activity to the sitewide activity table.
			if ( !$this->no_sitewide_cache )
				$sitewide_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_sitewide} ( user_id, item_id, secondary_item_id, content, primary_link, component_name, component_action, date_cached, date_recorded ) VALUES ( %d, %d, %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d) )", $this->user_id, $this->item_id, $this->secondary_item_id, $activity_content['content'], $activity_content['primary_link'], $this->component_name, $this->component_action, time(), $this->date_recorded ) );
			
			if ( $activity && $activity_cached ) {
				do_action( 'bp_activity_after_save', $this );
				return true;
			}
			
			return false;
		}
	}
	
	function exists() {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE item_id = %d AND secondary_item_id = %d AND user_id = %d AND component_name = %s AND component_action = %s", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action ) );		
	}
	
	/* Static Functions */ 

	function delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id = false ) {
		global $wpdb, $bp;
				
		if ( !$user_id )
			return false;
		
		if ( !$bp->activity )
			bp_activity_setup_globals();
		
		if ( $secondary_item_id )
			$secondary_sql = $wpdb->prepare( "AND secondary_item_id = %d", $secondary_item_id );
		
		if ( $component_action ) {
			$component_action_sql = $wpdb->prepare( "AND component_action = %s AND user_id = %d", $component_action, $user_id );
			$cached_component_action_sql = $wpdb->prepare( "AND component_action = %s", $component_action );
		}
				
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_user_activity} WHERE item_id = %d {$secondary_sql} AND component_name = %s {$component_action_sql}", $item_id, $component_name ) );
				
		// Delete this entry from the user activity cache table and the sitewide cache table
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d AND item_id = %d {$secondary_sql} AND component_name = %s {$cached_component_action_sql}", $user_id, $item_id, $component_name ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_sitewide} WHERE item_id = %d {$secondary_sql} AND component_name = %s {$component_action_sql}", $item_id, $component_name ) );

		return true;
	}
	
	function get_activity_for_user( $user_id, $max_items, $since, $limit, $page ) {
		global $wpdb, $bp;

		$since = strtotime($since);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );
		
		if ( !bp_is_home() )
			$privacy_sql = " AND is_private = 0";

		if ( $limit && $page && $max )
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $pag_sql", $user_id, $since ) );
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $pag_sql $max_sql", $user_id, $since ) );
	
		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $max_sql", $user_id, $since ) );

		for ( $i = 0; $i < count( $activities ); $i++ ) {
			if ( !$activities[$i]->is_private ) {
				$activities_formatted[$i]['content'] = $activities[$i]->content;
				$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
				$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
				$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
				$activities_formatted[$i]['component_action'] = $activities[$i]->component_action;
				$activities_formatted[$i]['is_private'] = $activities[$i]->is_private;
			}
		}
		
		return array( 'activities' => $activities_formatted, 'total' => (int)$total_activities );
	}
	
	function get_activity_for_friends( $user_id, $max_items, $since, $max_items_per_friend, $limit, $page ) {
		global $wpdb, $bp;
		
		// TODO: Max items per friend not yet implemented.
		
		if ( !function_exists('friends_get_friend_user_ids') )
			return false;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );
			
		$since = strtotime($since);

		$friend_ids = friends_get_friend_user_ids( $user_id );

		if ( !$friend_ids )
			return false;
			
		$friend_ids = implode( ',', $friend_ids );
		
		if ( $limit && $page && $max )
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name_sitewide} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $pag_sql", $since ) ); 
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name_sitewide} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $pag_sql $max_sql", $since ) ); 			
		
		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(user_id) FROM {$bp->activity->table_name_sitewide} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $max_sql", $since ) ); 
		
		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}
	
	function get_sitewide_activity( $max, $limit, $page ) {
		global $wpdb, $bp;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );

		/* Remove entries that are older than 6 months */
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->activity->table_name_sitewide . " WHERE DATE_ADD(date_recorded, INTERVAL 6 MONTH) <= NOW()" ) );
		
		if ( $limit && $page && $max )
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_sitewide} ORDER BY date_recorded DESC $pag_sql" ) );
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_sitewide} ORDER BY date_recorded DESC $pag_sql $max_sql" ) );

		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name_sitewide} ORDER BY date_recorded DESC $max_sql" ) );
		
		for ( $i = 0; $i < count( $activities ); $i++ ) {
			$activities_formatted[$i]['content'] = $activities[$i]->content;
			$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
			$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
			$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
			$activities_formatted[$i]['component_action'] = $activities[$i]->component_action;
		}

		return array( 'activities' => $activities_formatted, 'total' => (int)$total_activities );
	}
	
	function get_sitewide_items_for_feed( $limit = 35 ) {
		global $wpdb, $bp;
		
		$activities = bp_activity_get_sitewide_activity( $limit );
		for ( $i = 0; $i < count($activities); $i++ ) {
				$title = explode( '<span', $activities[$i]['content'] );

				$activity_feed[$i]['title'] = trim( strip_tags( $title[0] ) );
				$activity_feed[$i]['link'] = $activities[$i]['primary_link'];
				$activity_feed[$i]['description'] = @sprintf( $activities[$i]['content'], '' );
				$activity_feed[$i]['pubdate'] = $activities[$i]['date_recorded'];
		}

		return $activity_feed;	
	}
	
	function cache_friends_activities( $activity_array ) {
		global $wpdb, $bp;
		
		/* Empty the cache */
		$wpdb->query( "TRUNCATE TABLE {$bp->activity->table_name_loggedin_user_friends_cached}" );
		
		for ( $i = 0; $i < count($activity_array); $i++ ) {
			// Cache that sucka...
			$cached = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_loggedin_user_friends_cached} ( user_id, content, primary_link, component_name, component_action, date_cached, date_recorded ) VALUES ( %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), %s )", $activity_array[$i]['user_id'], $activity_array[$i]['content'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], $activity_array[$i]['component_action'], time(), $activity_array[$i]['date_recorded'] ) );
		}
		
		update_usermeta( $bp->loggedin_user->id, 'bp_activity_friends_last_cached', time() );
	}
	
	function cache_activities( $activity_array, $user_id ) {
		global $wpdb, $bp;

		/* Delete cached items older than 30 days for the user */
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d AND DATE_ADD(date_recorded, INTERVAL 30 DAY) <= NOW()", $user_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_sitewide} WHERE user_id = %d AND DATE_ADD(date_recorded, INTERVAL 30 DAY) <= NOW()", $user_id ) );

		for ( $i = 0; $i < count($activity_array); $i++ ) {
			if ( empty( $activity_array[$i]['content'] ) ) continue;

			// Cache that sucka...
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_user_activity_cached} ( user_id, content, item_id, secondary_item_id, primary_link, component_name, component_action, date_cached, date_recorded, is_private ) VALUES ( %d, %s, %d, %d, %s, %s, %s, FROM_UNIXTIME(%d), %s, %d )", $user_id, $activity_array[$i]['content'], $activity_array[$i]['item_id'], $activity_array[$i]['secondary_item_id'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], $activity_array[$i]['component_action'], time(), $activity_array[$i]['date_recorded'], $activity_array[$i]['is_private'] ) );

			// Add to the sitewide activity stream
			if ( !$activity_array[$i]['is_private'] && !$activity_array[$i]['no_sitewide_cache'] )
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_sitewide} ( user_id, content, item_id, secondary_item_id, primary_link, component_name, component_action, date_cached, date_recorded ) VALUES ( %d, %s, %d, %d, %s, %s, %s, FROM_UNIXTIME(%d), %s )", $user_id, $activity_array[$i]['content'], $activity_array[$i]['item_id'], $activity_array[$i]['secondary_item_id'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], $activity_array[$i]['component_action'], time(), $activity_array[$i]['date_recorded'] ) );
		}

		update_usermeta( $bp->displayed_user->id, 'bp_activity_last_cached', time() );
	}

	function delete_activity_for_user( $user_id ) {
		global $wpdb, $bp;

		/* Empty user's activities from the sitewide stream */
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_sitewide} WHERE user_id = %d", $user_id ) );

		/* Empty the user's activity items and cached activity items */
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_user_activity} WHERE user_id = %d", $user_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_user_activity_cached} WHERE user_id = %d", $user_id ) );
		
		return true;
	}
	
	function get_last_updated() {
		global $bp, $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT date_recorded FROM " . $bp->activity->table_name_sitewide . " ORDER BY date_recorded ASC LIMIT 1" ) );
	}
	
	function kill_tables_for_user( $user_id ) {
		global $bp, $wpdb;

		if ( !$wpdb->get_var( "SHOW TABLES LIKE 'wp_user_{$user_id}_activity'" ) )
			return false;
		
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_activity" ) );
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_activity_cached" ) );	
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_friends_activity_cached" ) );	
		
		return true;
	}
	
	function convert_tables_for_user( $user_id ) {
		global $bp, $wpdb;
		
		if ( !$wpdb->get_var( "SHOW TABLES LIKE 'wp_user_{$user_id}_activity'" ) )
			return false;
		
		$activity_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_user_{$user_id}_activity" ) );
		$activity_cached_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_user_{$user_id}_activity_cached" ) );
		
		if ( $activity_items ) {
			foreach ( $activity_items as $activity_item ) {
				$wpdb->query( $wpdb->prepare( 
					"INSERT INTO {$bp->activity->table_name_user_activity} 
						( item_id, secondary_item_id, user_id, component_name, component_action, date_recorded, is_private, no_sitewide_cache ) 
					VALUES 
						( %d, %d, %d, %s, %s, %s, %d, %d )",
					$activity_item->item_id, $activity_item->secondary_item_id, $user_id, $activity_item->component_name, $activity_item->component_action, $activity_item->date_recorded, $activity_item->is_private, $activity_item->no_sitewide_cache
				) );
			}
		}
		
		if ( $activity_cached_items ) {
			foreach ( $activity_cached_items as $activity_cached_item ) {
				$wpdb->query( $wpdb->prepare( 
					"INSERT INTO {$bp->activity->table_name_user_activity_cached} 
						( content, primary_link, item_id, secondary_item_id, user_id, component_name, component_action, date_recorded, date_cached, is_private ) 
					VALUES 
						( %s, %s, %d, %d, %d, %s, %s, %s, %s, %d )",
					$activity_cached_item->content, $activity_cached_item->primary_link, $activity_cached_item->item_id, $activity_cached_item->secondary_item_id, $user_id, $activity_cached_item->component_name, $activity_cached_item->component_action, $activity_cached_item->date_recorded, $activity_cached_item->date_cached, $activity_cached_item->is_private
				) );
			}
		}
	}
}

?>