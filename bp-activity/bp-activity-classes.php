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
	var $hide_sitewide = false;
	
	function bp_activity_activity( $args = false, $populate = false ) {
		global $bp;
		
		if ( $args ) {
			extract( $args );
			
			$this->user_id = $user_id;
			$this->component_name = $component_name;
			$this->component_action = $component_action;
			$this->item_id = $item_id;
			$this->secondary_item_id = $secondary_item_id;
			
			if ( $populate )
				$this->populate();
		}
	}
	
	function populate() {
		global $wpdb, $bp;
		
		$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id = %d", $this->id ) );
		
		$this->id = $activity->id;
		$this->item_id = $activity->item_id;
		$this->secondary_item_id = $activity->secondary_item_id;
		$this->user_id = $activity->user_id;
		$this->content = $activity->content;
		$this->primary_link = $activity->primary_link;
		$this->component_name = $activity->component_name;
		$this->component_action = $activity->component_action;
		$this->date_recorded = $activity->date_recorded;
		$this->hide_sitewide = $activity->hide_sitewide;
	}
	
	function save() {
		global $wpdb, $bp, $current_user;
		
		do_action( 'bp_activity_before_save', $this );

		if ( !$this->user_id || !$this->component_name || !$this->component_action )
			return false;
		
		/***
		 * Before v1.1 of BuddyPress, activity content was calculated at a later point. This is no longer the
		 * case, to to be backwards compatible we need to fetch content here to continue.
		 */
		if ( empty( $this->content ) || !$this->content ) {
			if ( function_exists( $bp->{$this->component_name}->format_activity_function ) ) {
				if ( !$fetched_content = call_user_func( $bp->{$this->component_name}->format_activity_function, $this->item_id, $this->user_id, $this->component_action, $this->secondary_item_id, $this->for_secondary_user ) )
					return false;
				
				$this->content = $fetched_content['content'];
				$this->primary_link = $fetched_content['primary_link'];
			}
		}
		
		if ( !$this->primary_link )
			$this->primary_link = $bp->loggedin_user->domain;
		
		if ( $existing_activity_id = $this->exists() )
			BP_Activity_Activity::delete_by_activity_id( $existing_activity_id );
		
		/* If we have an existing ID, update the activity item, otherwise insert it. */
		if ( $this->id ) {
			if ( $wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET user_id = %d, component_name = %s, component_action = %s, content = %s, primary_link = %s, date_recorded = FROM_UNIXTIME(%d), item_id = %d, secondary_item_id = %d, hide_sitewide = %d WHERE id = %d", $this->user_id, $this->component_name, $this->component_action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide, $this->id ) ) ) {
				do_action( 'bp_activity_after_save', $this );
				return true;
			}
		} else {
			if ( $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name} ( user_id, component_name, component_action, content, primary_link, date_recorded, item_id, secondary_item_id, hide_sitewide ) VALUES ( %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), %d, %d, %d )", $this->user_id, $this->component_name, $this->component_action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide ) ) ) {
				do_action( 'bp_activity_after_save', $this );
				return true;
			}
		}

		return false;
	}
	
	function exists() {
		global $wpdb, $bp;
		
		/* If we have an item id, try and match on that, if not do a content match */
		if ( $this->item_id ) {
			if ( $this->secondary_item_id )
				$secondary_sql = $wpdb->prepare( " AND secondary_item_id = %d", $secondary_item_id );
				
			return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d AND item_id = %d{$secondary_sql} AND component_name = %s AND component_action = %s", $this->user_id, $this->item_id, $this->component_name, $this->component_action ) );		
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d AND content = %s AND component_name = %s AND component_action = %s", $this->user_id, $this->content, $this->component_name, $this->component_action ) );				
		}
	}
	
	/* Static Functions */ 

	function delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id = false ) {
		global $wpdb, $bp;
				
		if ( !$user_id )
			return false;

		if ( $secondary_item_id )
			$secondary_sql = $wpdb->prepare( "AND secondary_item_id = %d", $secondary_item_id );
		
		if ( $component_action )
			$component_action_sql = $wpdb->prepare( "AND component_action = %s AND user_id = %d", $component_action, $user_id );
				
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d AND item_id = %d {$secondary_sql} AND component_name = %s {$cached_component_action_sql}", $user_id, $item_id, $component_name ) );
	}
	
	function delete_by_item_id( $user_id, $component_name, $component_action, $item_id, $secondary_item_id = false ) {
		return BP_Activity_Activity::delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}

	function delete_by_activity_id( $activity_id ) {
		global $bp, $wpdb;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE id = %d", $activity_id ) );
	}
	
	function delete_by_content( $user_id, $content, $component_name, $component_action ) {
		global $bp, $wpdb;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d AND content = %s AND component_name = %s AND component_action = %s", $user_id, $content, $component_name, $component_action ) );		
	}
	
	function delete_for_user_by_component( $user_id, $component_name ) {
		global $bp, $wpdb;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d AND component_name = %s", $user_id, $component_name ) );		
	}
	
	function delete_for_user( $user_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d", $user_id ) );
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
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $pag_sql", $user_id, $since ) );
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $pag_sql $max_sql", $user_id, $since ) );
	
		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} WHERE user_id = %d AND date_recorded >= FROM_UNIXTIME(%d) $privacy_sql ORDER BY date_recorded DESC $max_sql", $user_id, $since ) );

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
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $pag_sql", $since ) ); 
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $pag_sql $max_sql", $since ) ); 			
		
		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(user_id) FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) AND date_recorded >= FROM_UNIXTIME(%d) ORDER BY date_recorded DESC $max_sql", $since ) ); 
		
		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}
	
	function get_sitewide_activity( $max, $limit, $page ) {
		global $wpdb, $bp;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );
		
		if ( $limit && $page && $max )
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE hide_sitewide = 0 ORDER BY date_recorded DESC $pag_sql" ) );
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE hide_sitewide = 0 ORDER BY date_recorded DESC $pag_sql $max_sql" ) );

		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} WHERE hide_sitewide = 0 ORDER BY date_recorded DESC $max_sql" ) );
		
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
	
	function get_last_updated() {
		global $bp, $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT date_recorded FROM {$bp->activity->table_name} ORDER BY date_recorded ASC LIMIT 1" ) );
	}
}

?>