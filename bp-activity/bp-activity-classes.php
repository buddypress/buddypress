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

		if ( !$this->item_id || !$this->user_id || $this->is_private || !$this->component_name )
			return false;
			
		// Set the table names
		$this->table_name = $wpdb->base_prefix . 'user_' . $this->user_id . '_activity';
		$this->table_name_cached = $wpdb->base_prefix . 'user_' . $this->user_id . '_activity_cached';

		if ( !$this->exists() ) {
			// Insert the new activity into the activity table.
			$activity = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->table_name} ( item_id, secondary_item_id, user_id, component_name, component_action, date_recorded, is_private, no_sitewide_cache ) VALUES ( %d, %d, %d, %s, %s, FROM_UNIXTIME(%d), %d, %d )", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_recorded, $this->is_private, $this->no_sitewide_cache ) );

			// Fetch the formatted activity content so we can add it to the cache.
			if ( function_exists( $bp->{$this->component_name}->format_activity_function ) ) {
				if ( !$activity_content = call_user_func( $bp->{$this->component_name}->format_activity_function, $this->item_id, $this->user_id, $this->component_action, $this->secondary_item_id, $this->for_secondary_user ) )
					return false;
			}
			
			// Add the cached version of the activity to the cached activity table.
			$activity_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->table_name_cached} ( item_id, secondary_item_id, content, primary_link, component_name, component_action, date_cached, date_recorded, is_private ) VALUES ( %d, %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d), %d )", $this->item_id, $this->secondary_item_id, $activity_content['content'], $activity_content['primary_link'], $this->component_name, $this->component_action, time(), $this->date_recorded, $this->is_private ) );
			
			// Add the cached version of the activity to the sitewide activity table.
			if ( !$this->no_sitewide_cache )
				$sitewide_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_sitewide} ( user_id, item_id, secondary_item_id, content, primary_link, component_name, component_action, date_cached, date_recorded ) VALUES ( %d, %d, %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d) )", $this->user_id, $this->item_id, $this->secondary_item_id, $activity_content['content'], $activity_content['primary_link'], $this->component_name, $this->component_action, time(), $this->date_recorded ) );
			
			if ( $activity && $activity_cached )
				return true;
			
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
				
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}user_{$user_id}_activity WHERE item_id = %d {$secondary_sql} AND component_name = %s {$component_action_sql}", $item_id, $component_name ) );
				
		// Delete this entry from the users' cache table and the sitewide cache table
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}user_{$user_id}_activity_cached WHERE item_id = %d {$secondary_sql} AND component_name = %s {$cached_component_action_sql}", $item_id, $component_name ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_sitewide} WHERE item_id = %d {$secondary_sql} AND component_name = %s {$component_action_sql}", $item_id, $component_name ) );

		return true;
	}
	
	function get_activity_for_user( $user_id = null, $limit = 30, $since = '-4 weeks' ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		if ( !$user_id )
			return false;
		
		$since = strtotime($since);
		
		if ( $limit )
			$limit_sql = $wpdb->prepare( " LIMIT %d", $limit ); 
		
		if ( !bp_is_home() )
			$privacy_sql = " AND is_private = 0";
		
		/* Determine whether or not to use the cached activity stream, or re-select and cache a new stream */
		$last_cached = get_usermeta( $bp->displayed_user->id, 'bp_activity_last_cached' );
		
		if ( strtotime( BP_ACTIVITY_CACHE_LENGTH, (int)$last_cached ) >= time() ) {
			
			//echo '<small style="color: green">** Debug: Using Cache **</small>';
			
			// Use the cached activity stream.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_current_user_cached} WHERE date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $limit_sql", $since ) );
	
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

		} else {
			
			//echo '<small style="color: red">** Debug: Not Using Cache **</small>';
			
			// Reselect, format and cache a new activity stream. Override the limit otherwise we might only cache 5 items when viewing a profile page.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_current_user} WHERE date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC LIMIT 30", $since ) );

			for ( $i = 0; $i < count( $activities ); $i++ ) {
				
				if ( !$activities[$i]->component_name ) continue;
						
				if ( function_exists( $bp->{$activities[$i]->component_name}->format_activity_function ) ) {	
					if ( !$content = call_user_func( $bp->{$activities[$i]->component_name}->format_activity_function, $activities[$i]->item_id, $activities[$i]->user_id, $activities[$i]->component_action, $activities[$i]->secondary_item_id ) )
						continue;
					
					if ( !$activities[$i]->is_private ) {
						$activities_formatted[$i]['content'] = $content['content'];
						$activities_formatted[$i]['primary_link'] = $content['primary_link'];
						$activities_formatted[$i]['item_id'] = $activities[$i]->item_id;
						$activities_formatted[$i]['secondary_item_id'] = $activities[$i]->secondary_item_id;
						$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
						$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
						$activities_formatted[$i]['component_action'] = $activities[$i]->component_action;
						$activities_formatted[$i]['is_private'] = $activities[$i]->is_private;
						$activities_formatted[$i]['no_sitewide_cache'] = $activities[$i]->no_sitewide_cache;
					}
				}
				
				/* Remove empty activity items so they are not cached. */
				if ( !$activities_formatted[$i]['content'] )
					unset($activities_formatted[$i]);
			}
			
			if ( count($activities_formatted) )
				BP_Activity_Activity::cache_activities( $activities_formatted, $user_id );
			
			if ( is_array( $activities_formatted ) ) {
				// Now honor the limit value, otherwise we may return 30 items on a profile page.
				$activities_formatted = array_slice($activities_formatted, 0, $limit);				
			}
		}
		
		return $activities_formatted;
	}
	
	function get_activity_for_friends( $user_id = null, $total_limit = 80, $limit_per_friend = 5 ) {
		global $wpdb, $bp;
		
		if ( !function_exists('friends_get_friend_user_ids') )
			return false;

		if ( $total_limit )
			$limit_sql = $wpdb->prepare( " LIMIT %d", $total_limit );
		
		/* Determine whether or not to use the cached friends activity stream, or re-select and cache a new stream */
		$last_cached = get_usermeta( $bp->loggedin_user->id, 'bp_activity_friends_last_cached' );
		
		if ( strtotime( BP_ACTIVITY_CACHE_LENGTH, (int)$last_cached ) >= time() ) {
		
			//echo '<small style="color: green">** Debug: Using Cache **</small>';
			
			// Use the cached activity stream.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_loggedin_user_friends_cached} ORDER BY date_recorded DESC $limit_sql" ) );
		
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
		
		} else {
			
			//echo '<small style="color: red">** Debug: Not Using Cache **</small>';
			
			$friend_ids = friends_get_friend_user_ids( $user_id );
			
			if ( $limit_per_friend )
				$limit_sql = $wpdb->prepare( " LIMIT %d", $limit_per_friend );

			for ( $i = 0; $i < count($friend_ids); $i++ ) {
				$table_name = $wpdb->base_prefix . 'user_' . $friend_ids[$i] . '_activity_cached';

				$activities[$i]['activity'] = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE is_private = 0 ORDER BY date_recorded DESC $limit_sql" ) );
				$activities[$i]['full_name'] = bp_fetch_user_fullname( $friend_ids[$i], false );
			}
		
			for ( $i = 0; $i < count($activities); $i++ ) {
			
				/* Filter activities for friends to remove 'You' and 'your' */
				for ( $j = 0; $j < count( $activities[$i]['activity']); $j++ ) {
					$activities[$i]['activity'][$j]->content = bp_activity_content_filter( $activities[$i]['activity'][$j]->content, $activities[$i]['activity'][$j]->date_recorded, $activities[$i]['full_name'], false, false, false );
					$activities_formatted[] = array( 'user_id' => $friend_ids[$i], 'content' => $activities[$i]['activity'][$j]->content, 'primary_link' => $activities[$i]['activity'][$j]->primary_link, 'date_recorded' => $activities[$i]['activity'][$j]->date_recorded, 'component_name' => $activities[$i]['activity'][$j]->component_name, 'component_action' => $activities[$i]['activity'][$j]->component_action );
				}
			}
		
			if ( is_array($activities_formatted) ) {
				usort( $activities_formatted, 'bp_activity_order_by_date' );
				
				// Limit the number of items that get cached to the total_limit variable passed.
				$activities_formatted = array_slice( $activities_formatted, 0, $total_limit );
			}
			
			if ( count($activities_formatted) )
				BP_Activity_Activity::cache_friends_activities( $activities_formatted );
		}
		
		return $activities_formatted;
	}
	
	function get_sitewide_activity( $limit = 15 ) {
		global $wpdb, $bp;
		
		if ( $limit )
			$limit_sql = $wpdb->prepare( " LIMIT %d", $limit );
		
		/* Remove entries that are older than 6 months */
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->activity->table_name_sitewide . " WHERE DATE_ADD(date_recorded, INTERVAL 6 MONTH) <= NOW()" ) );
		
		$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp->activity->table_name_sitewide . " ORDER BY date_recorded DESC $limit_sql" ) );
		
		for ( $i = 0; $i < count( $activities ); $i++ ) {
			$activities_formatted[$i]['content'] = $activities[$i]->content;
			$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
			$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
			$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
			$activities_formatted[$i]['component_action'] = $activities[$i]->component_action;
		}
		
		return $activities_formatted;
	}
	
	function get_sitewide_items_for_feed( $limit = 35 ) {
		global $wpdb, $bp;
		
		$activities = BP_Activity_Activity::get_sitewide_activity( $limit );
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

		/* Empty the cache */
		$wpdb->query( "TRUNCATE TABLE {$bp->activity->table_name_current_user_cached}" );
		
		/* Empty user's activities from the sitewide stream */
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->activity->table_name_sitewide . " WHERE user_id = %d", $user_id ) );
		
		for ( $i = 0; $i < count($activity_array); $i++ ) {
			if ( empty( $activity_array[$i]['content'] ) ) continue;
			
			// Cache that sucka...
			$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp->activity->table_name_current_user_cached . " ( content, item_id, secondary_item_id, primary_link, component_name, component_action, date_cached, date_recorded, is_private ) VALUES ( %s, %d, %d, %s, %s, %s, FROM_UNIXTIME(%d), %s, %d )", $activity_array[$i]['content'], $activity_array[$i]['item_id'], $activity_array[$i]['secondary_item_id'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], $activity_array[$i]['component_action'], time(), $activity_array[$i]['date_recorded'], $activity_array[$i]['is_private'] ) );
			
			// Add to the sitewide activity stream
			if ( !$activity_array[$i]['is_private'] && !$activity_array[$i]['no_sitewide_cache'] )
				$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp->activity->table_name_sitewide . " ( user_id, content, item_id, secondary_item_id, primary_link, component_name, component_action, date_cached, date_recorded ) VALUES ( %d, %s, %d, %d, %s, %s, %s, FROM_UNIXTIME(%d), %s )", $user_id, $activity_array[$i]['content'], $activity_array[$i]['item_id'], $activity_array[$i]['secondary_item_id'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], $activity_array[$i]['component_action'], time(), $activity_array[$i]['date_recorded'] ) );
		}
		
		update_usermeta( $bp->displayed_user->id, 'bp_activity_last_cached', time() );
	}
	
	function delete_activity_for_user( $user_id ) {
		global $wpdb, $bp;

		/* Empty user's activities from the sitewide stream */
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->activity->table_name_sitewide . " WHERE user_id = %d", $user_id ) );

		/* Empty the user's activity items and cached activity items */
		$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE wp_user_{$user_id}_activity" ) );
		$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE wp_user_{$user_id}_activity_cached" ) );	
		
		return true;
	}
	
	function get_last_updated() {
		global $bp, $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT date_recorded FROM " . $bp->activity->table_name_sitewide . " ORDER BY date_recorded ASC LIMIT 1" ) );
	}
	
	function kill_tables_for_user( $user_id ) {
		global $bp, $wpdb;
		
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_activity" ) );
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_activity_cached" ) );	
		$wpdb->query( $wpdb->prepare( "DROP TABLE wp_user_{$user_id}_friends_activity_cached" ) );	
		
		return true;
	}
	

}

?>