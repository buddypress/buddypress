<?php

Class BP_Activity_Activity {
	var $id;
	var $item_id;
	var $user_id;
	var $primary_link;
	var $component_name;
	var $component_action;
	var $date_recorded;
	var $is_private;
	
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
		
		$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $this->table_name . " WHERE id = %d", $this->id ) );

		$this->item_id = $activity->item_id;
		$this->user_id = $activity->user_id;
		$this->component_name = $activity->component_name;
		$this->component_action = $activity->component_action;
		$this->date_recorded = $activity->date_recorded;
		$this->is_private = $activity->is_private;
	}
	
	function save() {
		global $wpdb, $bp, $current_user;

		if ( !$this->item_id || !$this->user_id || $this->is_private )
			return false;
			
		// Set the table names
		$this->table_name = $wpdb->base_prefix . 'user_' . $this->user_id . '_activity';
		$this->table_name_cached = $wpdb->base_prefix . 'user_' . $this->user_id . '_activity_cached';

		if ( !$this->exists() ) {
			// Insert the new activity into the activity table.
			$activity = $wpdb->query( $wpdb->prepare( "INSERT INTO " . $this->table_name . " ( item_id, user_id, component_name, component_action, date_recorded, is_private ) VALUES ( %d, %d, %s, %s, FROM_UNIXTIME(%d), %d )", $this->item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_recorded, $this->is_private ) );

			// Fetch the formatted activity content so we can add it to the cache.
			if ( function_exists( $bp[$this->component_name]['format_activity_function'] ) ) {
				if ( !$activity_content = call_user_func($bp[$this->component_name]['format_activity_function'], $this->item_id, $this->user_id, $this->component_action, $this->for_secondary_user ) )
					return false;
			}
			
			// Add the cached version of the activity to the cached activity table.
			$activity_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO " . $this->table_name_cached . " ( content, primary_link, component_name, date_cached, date_recorded, is_private ) VALUES ( %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d), %d )", $activity_content['content'], $activity_content['primary_link'], $this->component_name, time(), $this->date_recorded, $this->is_private ) );
		
			// Add the cached version of the activity to the cached activity table.
			$sitewide_cached = $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['activity']['table_name_sitewide'] . " ( user_id, content, primary_link, component_name, date_cached, date_recorded ) VALUES ( %d, %s, %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d) )", $bp['loggedin_userid'], $activity_content['content'], $activity_content['primary_link'], $this->component_name, time(), $this->date_recorded ) );
			
			if ( $activity && $activity_cached )
				return true;
			
			return false;
		}
	}
	
	function exists() {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $this->table_name . " WHERE item_id = %d AND user_id = %d AND component_name = %s AND component_action = %s", $this->item_id, $this->user_id, $this->component_name, $this->component_action ) );		
	}
	
	/* Static Functions */ 

	function delete( $item_id, $user_id, $component_name, $component_action ) {
		global $wpdb, $bp;
			
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['activity']['table_name_loggedin_user'] . " WHERE item_id = %d AND user_id = %d AND component_name = %s AND component_action = %s", $item_id, $user_id, $component_name, $component_action ) );
	}
	
	function get_activity_for_user( $user_id = null, $limit = 30, $since = '-1 week' ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];
		
		$since = strtotime($since);
		
		if ( $limit )
			$limit_sql = $wpdb->prepare( " LIMIT %d", $limit ); 
		
		if ( !bp_is_home() )
			$privacy_sql = " AND is_private = 0";
		
		/* Determine whether or not to use the cached activity stream, or re-select and cache a new stream */
		$last_cached = get_usermeta( $bp['current_userid'], 'bp_activity_last_cached' );
		
		if ( strtotime( BP_ACTIVITY_CACHE_LENGTH, (int)$last_cached ) >= time() ) {
			
			//echo '<small style="color: green">** Debug: Using Cache **</small>';
			
			// Use the cached activity stream.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp['activity']['table_name_current_user_cached'] . " WHERE date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $limit_sql", $since ) );
			
			for ( $i = 0; $i < count( $activities ); $i++ ) {
				if ( !$activities[$i]->is_private ) {
					$activities_formatted[$i]['content'] = $activities[$i]->content;
					$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
					$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
					$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
					$activities_formatted[$i]['is_private'] = $activities[$i]->is_private;
				}
			}
							
		} else {
			
			//echo '<small style="color: red">** Debug: Not Using Cache **</small>';
			
			// Reselect, format and cache a new activity stream.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp['activity']['table_name_current_user'] . " WHERE date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $limit_sql", $since ) );

			for ( $i = 0; $i < count( $activities ); $i++ ) {
				if ( function_exists( $bp[$activities[$i]->component_name]['format_activity_function'] ) ) {
					if ( !$content = call_user_func($bp[$activities[$i]->component_name]['format_activity_function'], $activities[$i]->item_id, $activities[$i]->user_id, $activities[$i]->component_action ) )
						continue;
					
					if ( !$activities[$i]->is_private ) {
						$activities_formatted[$i]['content'] = $content['content'];
						$activities_formatted[$i]['primary_link'] = $content['primary_link'];
						$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
						$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
						$activities_formatted[$i]['is_private'] = $activities[$i]->is_private;
					}
				}
				
				/* Remove empty activity items so they are not cached. */
				if ( !$activities_formatted[$i]['content'] )
					unset($activities_formatted[$i]);
			}
		
			if ( count($activities_formatted) )
				BP_Activity_Activity::cache_activities( $activities_formatted, $user_id );
		}
		
		return $activities_formatted;
	}
	
	function get_activity_for_friends( $user_id = null, $limit = 30, $since = '-3 days' ) {
		global $wpdb, $bp;
		
		if ( !function_exists('friends_get_friend_user_ids') )
			return false;

		$since = strtotime($since);
		
		if ( $limit )
			$limit_sql = $wpdb->prepare( " LIMIT %d", $limit );
		
		/* Determine whether or not to use the cached friends activity stream, or re-select and cache a new stream */
		$last_cached = get_usermeta( $bp['loggedin_userid'], 'bp_activity_friends_last_cached' );
		
		if ( strtotime( BP_ACTIVITY_CACHE_LENGTH, (int)$last_cached ) >= time() ) {
		
			//echo '<small style="color: green">** Debug: Using Cache **</small>';
			
			// Use the cached activity stream.
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp['activity']['table_name_loggedin_user_friends_cached'] . " WHERE date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $limit_sql", $since ) );

			for ( $i = 0; $i < count( $activities ); $i++ ) {
				if ( !$activities[$i]->is_private ) {
					$activities_formatted[$i]['content'] = $activities[$i]->content;
					$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
					$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
					$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
					$activities_formatted[$i]['is_private'] = $activities[$i]->is_private;
				}
			}
		
		} else {
			
			//echo '<small style="color: red">** Debug: Not Using Cache **</small>';
			
			$friend_ids = friends_get_friend_user_ids( $user_id );

			for ( $i = 0; $i < count($friend_ids); $i++ ) {
				$table_name = $wpdb->base_prefix . 'user_' . $friend_ids[$i] . '_activity_cached';

				$activities[$i]['activity'] = $wpdb->get_results( $wpdb->prepare( "SELECT content, date_recorded, component_name FROM " . $table_name . " WHERE is_private = 0 ORDER BY date_recorded LIMIT 5" ) );
				$activities[$i]['full_name'] = bp_fetch_user_fullname( $friend_ids[$i], false );
			}
		
			for ( $i = 0; $i < count($activities); $i++ ) {
			
				/* Filter activities for friends to remove 'You' and 'your' */
				for ( $j = 0; $j < count( $activities[$i]['activity']); $j++ ) {
					$activities[$i]['activity'][$j]->content = bp_activity_content_filter( $activities[$i]['activity'][$j]->content, $activities[$i]['activity'][$j]->date_recorded, $activities[$i]['full_name'], false, false, false );
					$activities_formatted[] = array( 'user_id' => $friend_ids[$i], 'content' => $activities[$i]['activity'][$j]->content, 'primary_link' => $activities[$i]['activity'][$j]->primary_link, 'date_recorded' => $activities[$i]['activity'][$j]->date_recorded, 'component_name' => $activities[$i]['activity'][$j]->component_name );
				}
			}
		
			if ( is_array($activities_formatted) )
				usort( $activities_formatted, 'bp_activity_order_by_date' );
			
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
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['activity']['table_name_sitewide'] . " WHERE DATE_ADD(date_recorded, INTERVAL 6 MONTHS) <= NOW()" ) );
		
		$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp['activity']['table_name_sitewide'] . " ORDER BY date_recorded DESC $limit_sql" ) );
		
		for ( $i = 0; $i < count( $activities ); $i++ ) {
			$activities_formatted[$i]['content'] = $activities[$i]->content;
			$activities_formatted[$i]['primary_link'] = $activities[$i]->primary_link;
			$activities_formatted[$i]['date_recorded'] = $activities[$i]->date_recorded;
			$activities_formatted[$i]['component_name'] = $activities[$i]->component_name;
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
				$activity_feed[$i]['description'] = sprintf ( $activities[$i]['content'], '' );
				$activity_feed[$i]['pubdate'] = $activities[$i]['date_recorded'];
		}

		return $activity_feed;	
	}
	
	function cache_friends_activities( $activity_array ) {
		global $wpdb, $bp;
		
		/* Empty the cache */
		$wpdb->query( "TRUNCATE TABLE " . $bp['activity']['table_name_loggedin_user_friends_cached'] );
		
		for ( $i = 0; $i < count($activity_array); $i++ ) {
			// Cache that sucka...
			$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['activity']['table_name_loggedin_user_friends_cached'] . " ( user_id, content, primary_link, component_name, date_cached, date_recorded ) VALUES ( %d, %s, %s, %s, FROM_UNIXTIME(%d), %s )", $activity_array[$i]['user_id'], $activity_array[$i]['content'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], time(), $activity_array[$i]['date_recorded'] ) );
		}
		
		update_usermeta( $bp['loggedin_userid'], 'bp_activity_friends_last_cached', time() );
	}
	
	function cache_activities( $activity_array, $user_id ) {
		global $wpdb, $bp;
		
		/* Empty the cache */
		$wpdb->query( "TRUNCATE TABLE " . $bp['activity']['table_name_current_user_cached'] );
		
		/* Empty user's activities from the sitewide stream */
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['activity']['table_name_sitewide'] . " WHERE user_id = %d", $user_id ) );
		
		for ( $i = 0; $i < count($activity_array); $i++ ) {
			if ( $activity_array[$i]['content'] == '' ) continue;
			
			// Cache that sucka...
			
			//echo $wpdb->prepare( "INSERT INTO " . $bp['activity']['table_name_current_user_cached'] . " ( content, primary_link, component_name, date_cached, date_recorded, is_private ) VALUES ( %s, %s, FROM_UNIXTIME(%d), %s, %d )", $activity_array[$i]['content'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], time(), $activity_array[$i]['date_recorded'], $activity_array[$i]['is_private'] );
			
			$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['activity']['table_name_current_user_cached'] . " ( content, primary_link, component_name, date_cached, date_recorded, is_private ) VALUES ( %s, %s, %s, FROM_UNIXTIME(%d), %s, %d )", $activity_array[$i]['content'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], time(), $activity_array[$i]['date_recorded'], $activity_array[$i]['is_private'] ) );
			
			// Add to the sitewide activity stream
			if ( !$activity_array[$i]['is_private'] )
				$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['activity']['table_name_sitewide'] . " ( user_id, content, primary_link, component_name, date_cached, date_recorded ) VALUES ( %d, %s, %s, %s, FROM_UNIXTIME(%d), %s )", $user_id, $activity_array[$i]['content'], $activity_array[$i]['primary_link'], $activity_array[$i]['component_name'], time(), $activity_array[$i]['date_recorded'] ) );
		}
		
		update_usermeta( $bp['current_userid'], 'bp_activity_last_cached', time() );
	}
	
	function get_last_updated() {
		global $bp, $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT date_recorded FROM " . $bp['activity']['table_name_sitewide'] . " ORDER BY date_recorded ASC LIMIT 1" ) );
	}
	

}

?>