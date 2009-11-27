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

	function bp_activity_activity( $id = false ) {
		global $bp;

		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id = %d", $this->id ) );

		if ( $activity ) {
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
	}

	function save() {
		global $wpdb, $bp, $current_user;

		do_action( 'bp_activity_before_save', $this );

		if ( !$this->component_name || !$this->component_action )
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

		/* If we have an existing ID, update the activity item, otherwise insert it. */
		if ( $this->id )
			$q = $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET user_id = %d, component_name = %s, component_action = %s, content = %s, primary_link = %s, date_recorded = FROM_UNIXTIME(%d), item_id = %s, secondary_item_id = %s, hide_sitewide = %d WHERE id = %d", $this->user_id, $this->component_name, $this->component_action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide, $this->id );
		else
			$q = $wpdb->prepare( "INSERT INTO {$bp->activity->table_name} ( user_id, component_name, component_action, content, primary_link, date_recorded, item_id, secondary_item_id, hide_sitewide ) VALUES ( %d, %s, %s, %s, %s, FROM_UNIXTIME(%d), %s, %s, %d )", $this->user_id, $this->component_name, $this->component_action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide );

		if ( !$wpdb->query( $q ) )
			return false;

		$this->id = $wpdb->insert_id;

		do_action( 'bp_activity_after_save', $this );
		return true;
	}

	/* Static Functions */

	function delete( $item_id, $component_name, $component_action, $user_id = false, $secondary_item_id = false ) {
		global $wpdb, $bp;

		if ( $secondary_item_id )
			$secondary_sql = $wpdb->prepare( "AND secondary_item_id = %s", $secondary_item_id );

		if ( $component_action )
			$component_action_sql = $wpdb->prepare( "AND component_action = %s", $component_action );

		if ( $user_id )
			$user_sql = $wpdb->prepare( "AND user_id = %d", $user_id );

		/* Fetch the activity IDs so we can delete any comments for this activity item */
		$activity_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE item_id = %s {$secondary_sql} AND component_name = %s {$component_action_sql} {$user_sql}", $item_id, $component_name ) );

		if ( $activity_ids )
			BP_Activity_Activity::delete_activity_item_comments( $activity_ids );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE item_id = %s {$secondary_sql} AND component_name = %s {$component_action_sql} {$user_sql}", $item_id, $component_name ) );
	}

	function delete_by_item_id( $item_id, $component_name, $component_action, $user_id = false, $secondary_item_id = false ) {
		return BP_Activity_Activity::delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}

	function delete_by_activity_id( $activity_id ) {
		global $bp, $wpdb;

		/* Delete the comments for this activity ID */
		BP_Activity_Activity::delete_activity_item_comments( $activity_id );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE id = %d", $activity_id ) );
	}

	function delete_by_content( $user_id, $content, $component_name, $component_action ) {
		global $bp, $wpdb;

		/* Fetch the activity ID so we can delete any comments for this activity item */
		$activity_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d AND content = %s AND component_name = %s AND component_action = %s", $user_id, $content, $component_name, $component_action ) );

		if ( $activity_id )
			BP_Activity_Activity::delete_activity_item_comments( $activity_id );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d AND content = %s AND component_name = %s AND component_action = %s", $user_id, $content, $component_name, $component_action ) );
	}

	function delete_for_user_by_component( $user_id, $component_name ) {
		global $bp, $wpdb;

		/* Fetch the activity IDs so we can delete any comments for this activity item */
		$activity_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d AND component_name = %s", $user_id, $component_name ) );

		if ( $activity_ids )
			BP_Activity_Activity::delete_activity_item_comments( $activity_ids );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d AND component_name = %s", $user_id, $component_name ) );
	}

	function delete_for_user( $user_id ) {
		global $wpdb, $bp;

		/* Fetch the activity IDs so we can delete any comments for this activity item */
		$activity_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d", $user_id ) );

		if ( $activity_ids )
			BP_Activity_Activity::delete_activity_item_comments( $activity_ids );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE user_id = %d", $user_id ) );
	}

	function delete_activity_item_comments( $activity_ids ) {
		global $bp, $wpdb;

		if ( is_array($activity_ids) )
			$activity_ids = implode( ',', $activity_ids );

		$activity_ids = $wpdb->escape( $activity_ids );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name} WHERE component_action = 'activity_comment' AND item_id IN ({$activity_ids})" ) );
	}

	function get_activity_for_user( $user_id, $max = false, $page = 1, $per_page = 25, $sort = 'DESC', $search_terms = false, $filter = false, $display_comments = false, $show_hidden = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );

		/* Searching */
		if ( $search_terms ) {
			$search_terms = $wpdb->escape( $search_terms );
			$search_sql = "AND content LIKE '%%" . like_escape( $search_terms ) . "%%'";
		}

		/* Filtering */
		if ( $filter ) {
			unset($filter['user_id']); // Unset duplicate user_id filter - TODO: merge all to sitewide.
			$filter_sql = BP_Activity_Activity::get_filter_sql( $filter );
		}

		/* Sorting */
		if ( $sort != 'ASC' && $sort != 'DESC' )
			$sort = 'DESC';

		/* Alter the query based on whether we want to show activity item comments in the stream like normal comments or threaded below the activity */
		if ( !$display_comments || 'threaded' == $display_comments ) {
			if ( $per_page && $page && $max )
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} AND component_action != 'activity_comment' ORDER BY date_recorded {$sort} {$pag_sql}", $user_id ) );
			else
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} AND component_action != 'activity_comment' ORDER BY date_recorded {$sort} {$pag_sql} {$max_sql}", $user_id ) );

			$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} AND component_action != 'activity_comment' ORDER BY date_recorded {$sort} {$max_sql}", $user_id ) );

			if ( $activities && $display_comments )
				$activities = BP_Activity_Activity::append_comments( &$activities );
		} else {
			if ( $per_page && $page && $max )
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} ORDER BY date_recorded {$sort} {$pag_sql}", $user_id ) );
			else
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} ORDER BY date_recorded {$sort} {$pag_sql} {$max_sql}", $user_id ) );

			$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} WHERE user_id = %d {$search_sql} {$filter_sql} ORDER BY date_recorded {$sort} {$max_sql}", $user_id ) );

			if ( $activities )
				$activities = BP_Activity_Activity::append_comments( &$activities );
		}

		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}

	function get_activity_for_friends( $user_id, $max_items, $max_items_per_friend, $limit, $page, $filter ) {
		global $wpdb, $bp;

		// TODO: Max items per friend not yet implemented.

		if ( !function_exists('friends_get_friend_user_ids') )
			return false;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $max_items )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max_items );

		/* Sort out filtering */
		if ( $filter )
			$filter_sql = BP_Activity_Activity::get_filter_sql( $filter );

		$friend_ids = friends_get_friend_user_ids( $user_id );

		if ( !$friend_ids )
			return false;

		$friend_ids = $wpdb->escape( implode( ',', $friend_ids ) );

		if ( $limit && $page && $max_items )
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT id, user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) $filter_sql ORDER BY date_recorded DESC $pag_sql"  ) );
		else
			$activities = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT id, user_id, content, primary_link, date_recorded, component_name, component_action FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) $filter_sql ORDER BY date_recorded DESC $pag_sql $max_sql" ) );

		$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$bp->activity->table_name} WHERE user_id IN ({$friend_ids}) $filter_sql ORDER BY date_recorded DESC $max_sql" ) );

		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}

	function get_sitewide_activity( $max = false, $page = 1, $per_page = 25, $sort = 'DESC', $search_terms = false, $filter = false, $display_comments = false, $show_hidden = false ) {
		global $wpdb, $bp;

		$where_conditions = array();

		if ( $per_page && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );

		/* Searching */
		if ( $search_terms ) {
			$search_terms = $wpdb->escape( $search_terms );
			$where_conditions['search_sql'] = "content LIKE '%%" . like_escape( $search_terms ) . "%%'";
		}

		/* Filtering */
		if ( $filter && $filter_sql = BP_Activity_Activity::get_filter_sql( $filter ) )
			$where_conditions['filter_sql'] = $filter_sql;

		/* Sorting */
		if ( $sort != 'ASC' && $sort != 'DESC' )
			$sort = 'DESC';

		/* Hide Hidden Items? */
		if ( !$show_hidden )
			$where_conditions['hidden_sql'] = "hide_sitewide = 0";

		/* Alter the query based on whether we want to show activity item comments in the stream like normal comments or threaded below the activity */
		if ( !$display_comments || 'threaded' == $display_comments ) {
			$where_conditions[] = "component_action != 'activity_comment'";
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			if ( $per_page && $page && $max )
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} {$where_sql} ORDER BY date_recorded {$sort} {$pag_sql}" ) );
			else
				$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} {$where_sql} ORDER BY date_recorded {$sort} {$pag_sql} {$max_sql}" ) );

			$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} {$where_sql} ORDER BY date_recorded {$sort} {$max_sql}" ) );

			if ( $activities && $display_comments )
				$activities = BP_Activity_Activity::append_comments( &$activities );
		} else {
			/***
			 * If we are filtering, this is going to stop activity comments showing in the stream,
			 * we will need to do things slightly differently.
			 */
			if ( !empty( $where_conditions ) )
				$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			 if ( !empty( $filter_sql ) ) {
				$all_activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} {$where_sql} ORDER BY id {$sort}" ) );

				foreach ( (array)$all_activities as $activity ) {
					$tmp_activities[$activity->id] = $activity;
					$a_ids[] = $activity->id;
				}
				$activity_ids = $wpdb->escape( implode( ',', $a_ids ) );

				/* Fetch the comments for the activity items */
				$search_sql = $where_conditions['search_sql'];
				$all_comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE item_id IN ({$activity_ids}) {$search_sql} ORDER BY id {$sort}" ) );

				foreach ( (array)$all_comments as $comment ) {
					$tmp_comments[$comment->id] = $comment;
				}

				/* Merge, sort and splice the activities and comments */
				$activities = $tmp_comments + $tmp_activities;
				ksort( $activities );
				$activities = array_reverse( array_merge( array(), (array)$activities ) );
				$activities = array_slice( (array)$activities, intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

			 	/* Fetch the totals */
				$total_activities = count($all_activities) + count($all_comments);

			 } else {
				if ( $per_page && $page && $max )
					$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} {$where_sql} ORDER BY id {$sort} {$pag_sql}" ) );
				else
					$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} {$where_sql} ORDER BY id {$sort} {$pag_sql} {$max_sql}" ) );

				$total_activities = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} {$where_sql} ORDER BY date_recorded {$sort} {$max_sql}" ) );
			 }

			/* Append threaded comments to those activites that have them */
			if ( $activities )
				$activities = BP_Activity_Activity::append_comments( &$activities );
		}

		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}

	function get_specific( $activity_ids, $max = false, $page = 1, $per_page = 25, $sort = 'DESC', $display_comments = false ) {
		global $wpdb, $bp;

		if ( is_array( $activity_ids ) )
			$activity_ids = implode( ',', $activity_ids );

		$activity_ids = $wpdb->escape( $activity_ids );

		if ( $per_page && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

		if ( $max )
			$max_sql = $wpdb->prepare( "LIMIT %d", $max );

		if ( $sort != 'ASC' && $sort != 'DESC' )
			$sort = 'DESC';

		$activities = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id IN ({$activity_ids}) ORDER BY date_recorded {$sort} $pag_sql $max_sql" ) );
		$total_activities = $wpdb->get_results( $wpdb->prepare( "SELECT count(id) FROM {$bp->activity->table_name} WHERE id IN ({$activity_ids}) ORDER BY date_recorded {$sort}" ) );

		if ( $display_comments )
			$activities = BP_Activity_Activity::append_comments( $activities );

		return array( 'activities' => $activities, 'total' => (int)$total_activities );
	}

	function append_comments( $activities ) {
		global $bp, $wpdb;

		/* Now fetch the activity comments and parse them into the correct position in the activities array. */
		foreach( $activities as $activity ) {
			if ( 'activity_comment' != $activity->component_action && $activity->mptt_left && $activity->mptt_right )
				$activity_comments[$activity->id] = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right );
		}

		/* Merge the comments with the activity items */
		foreach( $activities as $key => $activity )
			$activities[$key]->children = $activity_comments[$activity->id];

		return $activities;
	}

	function get_activity_comments( $activity_id, $left, $right ) {
		global $wpdb, $bp;

		/* Start with an empty $stack */
		$stack = array();

		/* Retrieve all descendants of the $root node */
		$descendants = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE component_action = 'activity_comment' AND item_id = %d AND mptt_left BETWEEN %d AND %d ORDER BY date_recorded ASC", $activity_id, $left, $right ) );

		/* Loop descendants and build an assoc array */
		foreach ( $descendants as $d ) {
		    $d->children = array();

			/* If we have a reference on the parent */
		    if ( isset( $ref[ $d->secondary_item_id ] ) ) {
		        $ref[ $d->secondary_item_id ]->children[ $d->id ] = $d;
		        $ref[ $d->id ] =& $ref[ $d->secondary_item_id ]->children[ $d->id ];

			/* If we don't have a reference on the parent, put in the root level */
		    } else {
		        $menu[ $d->id ] = $d;
		        $ref[ $d->id ] =& $menu[ $d->id ];
		    }
		}

		return $menu;
	}

	function rebuild_activity_comment_tree( $parent_id, $left = 1 ) {
		global $wpdb, $bp;

		/* The right value of this node is the left value + 1 */
		$right = $left + 1;

		/* Get all descendants of this node */
		$descendants = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE component_action = 'activity_comment' AND secondary_item_id = %d", $parent_id ) );

		/* Loop the descendants and recalculate the left and right values */
		foreach ( $descendants as $descendant )
			$right = BP_Activity_Activity::rebuild_activity_comment_tree( $descendant->id, $right );

		/* We've got the left value, and now that we've processed the children of this node we also know the right value */
		if ( 1 == $left )
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET mptt_left = %d, mptt_right = %d WHERE id = %d", $left, $right, $parent_id ) );
		else
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET mptt_left = %d, mptt_right = %d WHERE component_action = 'activity_comment' AND id = %d", $left, $right, $parent_id ) );

		/* Return the right value of this node + 1 */
		return $right + 1;
	}

	function get_recorded_component_names() {
		global $wpdb, $bp;

		return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT component_name FROM {$bp->activity->table_name} ORDER BY component_name ASC" ) );
	}

	function get_sitewide_items_for_feed( $limit = 35 ) {
		global $wpdb, $bp;

		$activities = bp_activity_get_sitewide( array( 'max' => $limit ) );

		for ( $i = 0; $i < count($activities); $i++ ) {
				$title = explode( '<span', $activities[$i]['content'] );

				$activity_feed[$i]['title'] = trim( strip_tags( $title[0] ) );
				$activity_feed[$i]['link'] = $activities[$i]['primary_link'];
				$activity_feed[$i]['description'] = @sprintf( $activities[$i]['content'], '' );
				$activity_feed[$i]['pubdate'] = $activities[$i]['date_recorded'];
		}

		return $activity_feed;
	}

	function get_filter_sql( $filter_array ) {
		global $wpdb;

		if ( !empty( $filter_array['user_id'] ) ) {
			$user_filter = explode( ',', $filter_array['user_id'] );
			$user_sql = ' ( ';

			$counter = 1;
			foreach( (array) $user_filter as $user ) {
				$user_sql .= $wpdb->prepare( "user_id = %d", trim( $user ) );

				if ( $counter != count( $user_filter ) )
					$user_sql .= ' || ';

				$counter++;
			}

			$user_sql .= ' )';
			$filter_sql[] = $user_sql;
		}

		if ( !empty( $filter_array['object'] ) ) {
			$object_filter = explode( ',', $filter_array['object'] );
			$object_sql = ' ( ';

			$counter = 1;
			foreach( (array) $object_filter as $object ) {
				$object_sql .= $wpdb->prepare( "component_name = %s", trim( $object ) );

				if ( $counter != count( $object_filter ) )
					$object_sql .= ' || ';

				$counter++;
			}

			$object_sql .= ' )';
			$filter_sql[] = $object_sql;
		}

		if ( !empty( $filter_array['action'] ) ) {
			$action_filter = explode( ',', $filter_array['action'] );
			$action_sql = ' ( ';

			$counter = 1;
			foreach( (array) $action_filter as $action ) {
				$action_sql .= $wpdb->prepare( "component_action = %s", trim( $action ) );

				if ( $counter != count( $action_filter ) )
					$action_sql .= ' || ';

				$counter++;
			}

			$action_sql .= ' )';
			$filter_sql[] = $action_sql;
		}

		if ( !empty( $filter_array['primary_id'] ) ) {
			$pid_filter = explode( ',', $filter_array['primary_id'] );
			$pid_sql = ' ( ';

			$counter = 1;
			foreach( (array) $pid_filter as $pid ) {
				$pid_sql .= $wpdb->prepare( "item_id = %s", trim( $pid ) );

				if ( $counter != count( $pid_filter ) )
					$pid_sql .= ' || ';

				$counter++;
			}

			$pid_sql .= ' )';
			$filter_sql[] = $pid_sql;
		}

		if ( !empty( $filter_array['secondary_id'] ) ) {
			$sid_filter = explode( ',', $filter_array['secondary_id'] );
			$sid_sql = ' ( ';

			$counter = 1;
			foreach( (array) $sid_filter as $sid ) {
				$sid_sql .= $wpdb->prepare( "secondary_item_id = %s", trim( $sid ) );

				if ( $counter != count( $sid_filter ) )
					$sid_sql .= ' || ';

				$counter++;
			}

			$sid_sql .= ' )';
			$filter_sql[] = $sid_sql;
		}

		if ( empty($filter_sql) )
			return false;

		return join( ' AND ', $filter_sql );
	}

	function get_last_updated() {
		global $bp, $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT date_recorded FROM {$bp->activity->table_name} ORDER BY date_recorded ASC LIMIT 1" ) );
	}

	function check_exists_by_content( $content ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE content = %s", $content ) );
	}
}

?>