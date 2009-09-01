<?php

Class BP_Groups_Group {
	var $id;
	var $creator_id;
	var $name;
	var $slug;
	var $description;
	var $news;
	var $status;
	var $enable_wire;
	var $enable_forum;
	var $date_created;

	var $user_dataset;
	
	var $admins;
	var $total_member_count;
	var $random_members;
	var $latest_wire_posts;
	
	function bp_groups_group( $id = null, $single = false, $get_user_dataset = true ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate( $get_user_dataset );
		}
		
		if ( $single ) {
			$this->populate_meta();
		}
	}
	
	function populate( $get_user_dataset ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} WHERE id = %d", $this->id );
		$group = $wpdb->get_row($sql);
		
		if ( $group ) {
			$this->id = $group->id;
			$this->creator_id = $group->creator_id;
			$this->name = stripslashes($group->name);
			$this->slug = $group->slug;
			$this->description = stripslashes($group->description);
			$this->news = stripslashes($group->news);
			$this->status = $group->status;
			$this->enable_wire = $group->enable_wire;
			$this->enable_forum = $group->enable_forum;
			$this->date_created = strtotime($group->date_created);
			$this->total_member_count = groups_get_groupmeta( $this->id, 'total_member_count' );
			
			if ( $get_user_dataset ) {
				$this->user_dataset = $this->get_user_dataset();
				
				//if ( !$this->total_member_count ) {
				$this->total_member_count = count( $this->user_dataset );
				groups_update_groupmeta( $this->id, 'total_member_count', $this->total_member_count );
				//}
			}
		}	
	}
	
	function populate_meta() {
		if ( $this->id ) {
			$this->admins = $this->get_administrators();
			$this->random_members = $this->get_random_members();
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		$this->creator_id = apply_filters( 'groups_group_creator_id_before_save', $this->creator_id, $this->id );
		$this->name = apply_filters( 'groups_group_name_before_save', $this->name, $this->id );
 		$this->slug = apply_filters( 'groups_group_slug_before_save', $this->slug, $this->id );
		$this->description = apply_filters( 'groups_group_description_before_save', $this->description, $this->id );
 		$this->news = apply_filters( 'groups_group_news_before_save', $this->news, $this->id );
		$this->status = apply_filters( 'groups_group_status_before_save', $this->status, $this->id );
		$this->enable_wire = apply_filters( 'groups_group_enable_wire_before_save', $this->enable_wire, $this->id );
		$this->enable_forum = apply_filters( 'groups_group_enable_forum_before_save', $this->enable_forum, $this->id );
		$this->date_created = apply_filters( 'groups_group_date_created_before_save', $this->date_created, $this->id );

		do_action( 'groups_group_before_save', $this );
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( 
				"UPDATE {$bp->groups->table_name} SET 
					creator_id = %d, 
					name = %s, 
					slug = %s, 
					description = %s, 
					news = %s, 
					status = %s, 
					enable_wire = %d, 
					enable_forum = %d, 
					date_created = FROM_UNIXTIME(%d)
				WHERE
					id = %d
				",
					$this->creator_id, 
					$this->name, 
					$this->slug, 
					$this->description, 
					$this->news, 
					$this->status, 
					$this->enable_wire, 
					$this->enable_forum, 
					$this->date_created,
					$this->id
			);
		} else {
			$sql = $wpdb->prepare( 
				"INSERT INTO {$bp->groups->table_name} ( 
					creator_id,
					name,
					slug,
					description,
					news,
					status,
					enable_wire,
					enable_forum,
					date_created
				) VALUES (
					%d, %s, %s, %s, %s, %s, %d, %d, FROM_UNIXTIME(%d)
				)",
					$this->creator_id, 
					$this->name, 
					$this->slug, 
					$this->description, 
					$this->news, 
					$this->status, 
					$this->enable_wire, 
					$this->enable_forum,
					$this->date_created
			);
		}
		
		if ( false === $wpdb->query($sql) )
			return false;
		
		if ( !$this->id ) {
			$this->id = $wpdb->insert_id;
		}

		do_action( 'groups_group_after_save', $this );
		
		return true;
	}
	
	function make_private() {
		
	}
	
	function make_public() {
		
	}
	
	function get_user_dataset() {
		global $wpdb, $bp;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, is_admin, inviter_id, user_title, is_mod FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand()", $this->id ) );
	}
		
	function get_administrators() {
		for ( $i = 0; $i < count($this->user_dataset); $i++ ) {
			if ( $this->user_dataset[$i]->is_admin )
				$admins[] = new BP_Groups_Member( $this->user_dataset[$i]->user_id, $this->id );
		}	
		
		return $admins;
	}

	function get_random_members() {
		$total_randoms = ( $this->total_member_count > 5 ) ? 5 : $this->total_member_count;
		
		for ( $i = 0; $i < $total_randoms; $i++ ) {
			if ( !(int)$this->user_dataset[$i]->is_banned )
				$users[] = new BP_Groups_Member( $this->user_dataset[$i]->user_id, $this->id );
		}
		return $users;
	}
	
	function is_member() {
		global $bp;
		
		for ( $i = 0; $i < count($this->user_dataset); $i++ ) {
			if ( $this->user_dataset[$i]->user_id == $bp->loggedin_user->id ) {
				return true;
			}
		}	
		
		return false;
	}
	
	function delete() {
		global $wpdb, $bp;
		
		// Delete groupmeta for the group
		groups_delete_groupmeta( $this->id );

		// Modify group count usermeta for members
		for ( $i = 0; $i < count($this->user_dataset); $i++ ) {
			$user = $this->user_dataset[$i];
			
			$total_count = get_usermeta( $user->user_id, 'total_group_count' );
			
			if ( $total_count != '' ) {
				update_usermeta( $user->user_id, 'total_group_count', (int)$total_count - 1 );
			}
			
			// Now delete the group member record
			BP_Groups_Member::delete( $user->user_id, $this->id, false );
		}
		
		// Delete the wire posts for this group if the wire is installed
		if ( function_exists('bp_wire_install') ) {
			BP_Wire_Post::delete_all_for_item( $this->id, $bp->groups->table_name_wire );
		}
				
		// Finally remove the group entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name} WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}
	

	/* Static Functions */
		
	function group_exists( $slug, $table_name = false ) {
		global $wpdb, $bp;
		
		if ( !$table_name )
			$table_name = $bp->groups->table_name;
		
		if ( !$slug )
			return false;
			
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE slug = %s", $slug ) );
	}

	function get_id_from_slug( $slug ) {
		return BP_Groups_Group::group_exists( $slug );
	}

	function get_invites( $user_id, $group_id ) {
		global $wpdb, $bp;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d and is_confirmed = 0 AND inviter_id = %d", $group_id, $user_id ) );
	}
	
	function filter_user_groups( $filter, $user_id = false, $order = false, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		like_escape($filter);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// Get all the group ids for the current user's groups.
		$gids = BP_Groups_Member::get_group_ids( $user_id, false, false, false, true );

		if ( !$gids['groups'] )
			return false;
			
		$gids = implode( ',', $gids['groups'] );
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids}) {$pag_sql}" ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids})" ) );
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function search_groups( $filter, $limit = null, $page = null, $sort_by = false, $order = false ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $sort_by && $order ) {
			$sort_by = $wpdb->escape( $sort_by );
			$order = $wpdb->escape( $order );
			$order_sql = "ORDER BY $sort_by $order";
		}
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE status != 'hidden' AND ( name LIKE '%%$filter%%' OR description LIKE '%%$filter%%' ) {$order_sql} {$pag_sql}" ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name} WHERE status != 'hidden' AND ( name LIKE '%%$filter%%' OR description LIKE '%%$filter%%' )" ) );
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function check_slug( $slug ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->groups->table_name} WHERE slug = %s", $slug ) );		
	}
	
	function get_slug( $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->groups->table_name} WHERE id = %d", $group_id ) );		
	}
	
	function has_members( $group_id ) {
		global $wpdb, $bp;
		
		$members = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );						

		if ( !$members )
			return false;
		
		return true;
	}
	
	function has_membership_requests( $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0", $group_id ) );						
	}
	
	function get_membership_requests( $group_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}
		
		$paged_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0{$pag_sql}", $group_id ) );
		$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0", $group_id ) );

		return array( 'requests' => $paged_requests, 'total' => $total_requests );
	}
	
	function get_newest( $limit = null, $page = null ) {
		global $wpdb, $bp;
				
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE status != 'hidden' ORDER BY date_created DESC {$pag_sql}" ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name} WHERE status != 'hidden' ORDER BY date_created DESC", $limit ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_active( $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} gm, {$bp->groups->table_name} g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'last_activity' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC {$pag_sql}", $limit ) );
		$total_groups = count( $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} gm, {$bp->groups->table_name} g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'last_activity' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC", $limit ) ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_popular( $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}
			
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT gm.group_id FROM {$bp->groups->table_name_groupmeta} gm, {$bp->groups->table_name} g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'total_member_count' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC {$pag_sql}", $limit ) );
		$total_groups = count( $wpdb->get_results( $wpdb->prepare( "SELECT gm.group_id FROM {$bp->groups->table_name_groupmeta} gm, {$bp->groups->table_name} g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'total_member_count' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC", $limit ) ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_all( $limit = null, $page = null, $only_public = true, $sort_by = false, $order = false ) {
		global $wpdb, $bp;
		
		if ( $only_public )
			$public_sql = $wpdb->prepare( " WHERE g.status = 'public'" );
		
		if ( !is_site_admin() )
			$hidden_sql = $wpdb->prepare( " AND g.status != 'hidden'");
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $sort_by && $order ) {
			$sort_by = $wpdb->escape( $sort_by );
			$order = $wpdb->escape( $order );
			$order_sql = "ORDER BY g.$sort_by $order";
			
			switch ( $sort_by ) {
				default:
					$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} g {$public_sql} {$hidden_sql} {$order_sql} {$pag_sql}" ); 	
					break;
				case 'members':
					$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} g, {$bp->groups->table_name_groupmeta} gm WHERE g.id = gm.group_id AND gm.meta_key = 'total_member_count' {$hidden_sql} {$public_sql} ORDER BY CONVERT(gm.meta_value, SIGNED) {$order} {$pag_sql}" ); 
					break;
				case 'last_active':
					$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} g, {$bp->groups->table_name_groupmeta} gm WHERE g.id = gm.group_id AND gm.meta_key = 'last_activity' {$hidden_sql} {$public_sql} ORDER BY CONVERT(gm.meta_value, SIGNED) {$order} {$pag_sql}" ); 
					break;
			}
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} g {$public_sql} {$hidden_sql} {$order_sql} {$pag_sql}" ); 	
		}
		
		return $wpdb->get_results($sql);
	}
	
	function get_by_letter( $letter, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( strlen($letter) > 1 || is_numeric($letter) || !$letter )
			return false;
		
		if ( !is_site_admin() )
			$hidden_sql = $wpdb->prepare( " AND status != 'hidden'");
		
		like_escape($letter);
				
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->groups->table_name} WHERE name LIKE '$letter%%' {$hidden_sql} ORDER BY name ASC" ) );
		}
				
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE name LIKE '$letter%%' {$hidden_sql} ORDER BY name ASC {$pag_sql}" ) );
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	
	function get_random( $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( !is_site_admin() )
			$hidden_sql = $wpdb->prepare( " AND status != 'hidden'");

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT id as group_id, slug FROM {$bp->groups->table_name} WHERE status = 'public' {$hidden_sql} ORDER BY rand()" ) );
		}
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id, slug FROM {$bp->groups->table_name} WHERE status = 'public' {$hidden_sql} ORDER BY rand() {$pag_sql}" ) ); 		
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function delete_all_invites( $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d AND invite_sent = 1", $group_id ) );
	}
}

Class BP_Groups_Member {
	var $id;
	var $group_id;
	var $user_id;
	var $inviter_id;
	var $is_admin;
	var $is_mod;
	var $is_banned;
	var $user_title;
	var $date_modified;
	var $is_confirmed;
	var $comments;
	var $invite_sent;
	
	var $user;
	
	function bp_groups_member( $user_id = false, $group_id = false, $id = false, $populate = true ) {
		if ( $user_id && $group_id && !$id ) {
			$this->user_id = $user_id;
			$this->group_id = $group_id;
			
			if ( $populate )
				$this->populate();
		}
		
		if ( $id ) {
			$this->id = $id;
			
			if ( $populate )
				$this->populate();
		}		
	}
	
	function populate() {
		global $wpdb, $bp;
		
		if ( $this->user_id && $this->group_id && !$this->id )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );
		
		if ( $this->id )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE id = %d", $this->id );
			
		$member = $wpdb->get_row($sql);
		
		if ( $member ) {
			$this->id = $member->id;
			$this->group_id = $member->group_id;
			$this->user_id = $member->user_id;
			$this->inviter_id = $member->inviter_id;
			$this->is_admin = $member->is_admin;
			$this->is_mod = $member->is_mod;
			$this->is_banned = $member->is_banned;
			$this->user_title = $member->user_title;
			$this->date_modified = strtotime($member->date_modified);
			$this->is_confirmed = $member->is_confirmed;
			$this->comments = $member->comments;
			$this->invite_sent = $member->invite_sent;
			
			$this->user = new BP_Core_User( $this->user_id );
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		$this->user_id = apply_filters( 'groups_member_user_id_before_save', $this->user_id, $this->id );
		$this->group_id = apply_filters( 'groups_member_group_id_before_save', $this->group_id, $this->id );
		$this->inviter_id = apply_filters( 'groups_member_inviter_id_before_save', $this->inviter_id, $this->id );
		$this->is_admin = apply_filters( 'groups_member_is_admin_before_save', $this->is_admin, $this->id );
		$this->is_mod = apply_filters( 'groups_member_is_mod_before_save', $this->is_mod, $this->id );
		$this->is_banned = apply_filters( 'groups_member_is_banned_before_save', $this->is_banned, $this->id );
		$this->user_title = apply_filters( 'groups_member_user_title_before_save', $this->user_title, $this->id );
		$this->date_modified = apply_filters( 'groups_member_date_modified_before_save', $this->date_modified, $this->id );
		$this->is_confirmed = apply_filters( 'groups_member_is_confirmed_before_save', $this->is_confirmed, $this->id );
		$this->comments = apply_filters( 'groups_member_comments_before_save', $this->comments, $this->id );
		$this->invite_sent = apply_filters( 'groups_member_invite_sent_before_save', $this->invite_sent, $this->id );
		
		do_action( 'groups_member_before_save', $this );
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name_members} SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = FROM_UNIXTIME(%d), is_confirmed = %d, comments = %s, invite_sent = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->groups->table_name_members} ( user_id, group_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments, invite_sent ) VALUES ( %d, %d, %d, %d, %d, %d, %s, FROM_UNIXTIME(%d), %d, %s, %d )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent );
		}

		if ( !$wpdb->query($sql) )
			return false;
		
		$this->id = $wpdb->insert_id;
		
		do_action( 'groups_member_after_save', $this );
		
		return true;
	}
	
	function promote() {
		// Check the users current status
		
		// Not letting mods be promoted to admins right now. In the future though yes.
		if ( $this->is_admin || $this->is_mod )
			return false;
		
		$this->is_mod = 1;
		return $this->save();
	}
	
	function demote() {
		if ( $this->is_admin ) 
			return false;
		
		if ( !$this->is_mod )
			return false;
		
		$this->is_mod = 0;
		return $this->save();		
	}
	
	function ban() {
		if ( $this->is_admin ) 
			return false;
		
		$this->is_mod = 0;
		$this->is_banned = 1;
		
		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) - 1 ) );
		
		return $this->save();		
	}
	
	function unban() {
		if ( $this->is_admin ) 
			return false;
		
		$this->is_banned = 0;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) + 1 ) );
		
		return $this->save();		
	}
	
	function accept_invite() {
		$this->inviter_id = 0;
		$this->is_confirmed = 1;
		$this->date_modified = time();
	}
	
	function accept_request() {
		$this->is_confirmed = 1;
		$this->date_modified = time();		
	}
		
	/* Static Functions */

	function delete( $user_id, $group_id, $check_empty = true ) {
		global $wpdb, $bp;

		$delete_result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
		
		return $delete_result;
	}
	
	function get_group_ids( $user_id, $limit = false, $page = false, $get_total = true, $as_col = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// If the user is logged in and viewing their own groups, we can show hidden and private groupss
		if ( bp_is_home() ) {
			$group_sql = $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND inviter_id = 0 AND is_banned = 0{$pag_sql}", $user_id );	
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(group_id) FROM {$bp->groups->table_name_members} WHERE user_id = %d AND inviter_id = 0 AND is_banned = 0", $user_id ) );	
		} else {
			$group_sql = $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0{$pag_sql}", $user_id );	
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0", $user_id ) );
		}
		
		if ( $as_col )
			$paged_groups = $wpdb->get_col( $group_sql );
		else
			$paged_groups = $wpdb->get_results( $group_sql );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_recently_joined( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC", $user_id ) );	

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_most_popular( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_confirmed = 1 LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id AND gm.meta_key = 'total_member_count' ORDER BY CONVERT( gm.meta_value, SIGNED ) DESC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_confirmed = 1 LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id AND gm.meta_key = 'total_member_count' ORDER BY CONVERT( gm.meta_value, SIGNED ) DESC", $user_id ) );	
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_recently_active( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;
	
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			
		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 ORDER BY gm.meta_value DESC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 ORDER BY gm.meta_value DESC", $user_id ) );	

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_alphabetically( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;
	
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";	

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 ORDER BY g.name ASC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 ORDER BY g.name ASC", $user_id ) );	
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_is_admin_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;
	
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";	
		
		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC", $user_id ) );	
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function get_is_mod_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;
	
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			like_escape($filter);
			$filter_sql = " AND ( g.name LIKE '{$filter}%%' OR g.description LIKE '{$filter}%%' )";			
		}

		if ( !bp_is_home() )
			$hidden_sql = " AND g.status != 'hidden'";			

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC {$pag_sql}", $user_id ) );	
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC", $user_id ) );	
		
		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}
	
	function total_group_count( $user_id = false ) {
		global $bp, $wpdb;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
			
		if ( bp_is_home() ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(group_id) FROM {$bp->groups->table_name_members} WHERE user_id = %d AND inviter_id = 0 AND is_banned = 0", $user_id ) );			
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0", $user_id ) );			
		}
	}
	
	function get_invites( $user_id ) {
		global $wpdb, $bp;
		
		$group_ids = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d and is_confirmed = 0 AND inviter_id != 0 AND invite_sent = 1", $user_id ) );
		
		return $group_ids;
	}
	
	function check_has_invite( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
			
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND inviter_id != 0 AND invite_sent = 1", $user_id, $group_id ) );		
	}
	
	function delete_invite( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND inviter_id != 0 AND invite_sent = 1", $user_id, $group_id ) );				
	}
	
	function check_is_admin( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_admin = 1 AND is_banned = 0", $user_id, $group_id ) );
	}
	
	function check_is_mod( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
				
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_mod = 1 AND is_banned = 0", $user_id, $group_id ) );
	}
	
	function check_is_member( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id, $group_id ) );	
	}
	
	function check_is_banned( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT is_banned FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}
	
	function check_for_membership_request( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			return false;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND is_banned = 0 AND inviter_id = 0", $user_id, $group_id ) );	
	}
	
	function get_random_groups( $user_id, $total_groups = 5 ) {
		global $wpdb, $bp;
		
		// If the user is logged in and viewing their random groups, we can show hidden and private groups
		if ( bp_is_home() ) {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );			
		}
	}
	
	function get_group_administrator_ids( $group_id ) {
		global $bp, $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_admin = 1 AND is_banned = 0", $group_id ) );
	}
	
	function get_group_moderator_ids( $group_id ) {
		global $bp, $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_mod = 1 AND is_banned = 0", $group_id ) );
	}
	
	function get_all_membership_request_user_ids( $group_id ) {
		global $bp, $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0", $group_id ) );		
	}
	
	function get_all_for_group( $group_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true ) {
		global $bp, $wpdb;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $exclude_admins_mods )
			$exclude_sql = $wpdb->prepare( "AND is_admin = 0 AND is_mod = 0" );
		
		if ( $exclude_banned )
			$banned_sql = $wpdb->prepare( " AND is_banned = 0" );
		
		$members = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql} {$pag_sql}", $group_id ) );

		if ( !$members )
			return false;
		
		if ( !isset($pag_sql) ) 
			$total_member_count = count($members);
		else
			$total_member_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(user_id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql}", $group_id ) );
	
		return array( 'members' => $members, 'count' => $total_member_count );
	}
	
	function delete_all_for_user( $user_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d", $user_id ) ); 		
	}
}

/**
 * API for creating group extensions without having to hardcode the content into
 * the theme. 
 *
 * This class must be extended for each group extension and the following methods overridden:
 * 
 * BP_Group_Extension::widget_display(), BP_Group_Extension::display(),
 * BP_Group_Extension::edit_screen_save(), BP_Group_Extension::edit_screen(),
 * BP_Group_Extension::create_screen_save(), BP_Group_Extension::create_screen()
 *
 * @package BuddyPress
 * @subpackage Groups
 * @since 1.1
 */
class BP_Group_Extension {
	var $name = false;
	var $slug = false;
	
	/* Will this extension be visible to non-members of a group? Options: public/private */
	var $visibility = 'public';
	
	var $create_step_position = 81;
	var $nav_item_position = 81;
	
	var $enable_create_step = true;
	var $enable_nav_item = true;
	var $enable_edit_item = true;
	
	var $nav_item_name = false;
	
	var $template_file = 'plugin-template';
	
	// Methods you should override
	
	function display() {
		die( 'function BP_Group_Extension::display() must be over-ridden in a sub-class.' );
	}
	
	function widget_display() {
		die( 'function BP_Group_Extension::widget_display() must be over-ridden in a sub-class.' );
	}
	
	function edit_screen() {
		die( 'function BP_Group_Extension::edit_screen() must be over-ridden in a sub-class.' );
	}
	
	function edit_screen_save() {
		die( 'function BP_Group_Extension::edit_screen_save() must be over-ridden in a sub-class.' );
	}
	
	function create_screen() {
		die( 'function BP_Group_Extension::create_screen() must be over-ridden in a sub-class.' );
	}
	
	function create_screen_save() {
		die( 'function BP_Group_Extension::create_screen_save() must be over-ridden in a sub-class.' );	
	}
	
	// Private Methods
	
	function _register() {
		global $bp;

		if ( $this->enable_create_step ) {
			/* Insert the group creation step for the new group extension */
			$bp->groups->group_creation_steps[$this->slug] = array( 'name' => $this->name, 'slug' => $this->slug, 'position' => $this->create_step_position );

			/* Attach the group creation step display content action */
			add_action( 'groups_custom_create_steps', array( &$this, 'create_screen' ) );
	
			/* Attach the group creation step save content action */
			add_action( 'groups_create_group_step_save_' . $slug, array( &$this, 'create_screen_save' ) );
		}
	
		/* Construct the admin edit tab for the new group extension */
		if ( $this->enable_edit_item ) {
			add_action( 'groups_admin_tabs', create_function( '$current, $group_slug', 'if ( "' . attribute_escape( $this->slug ) . '" == $current ) $selected = " class=\"current\""; echo "<li{$selected}><a href=\"' . $bp->root_domain . '/' . $bp->groups->slug . '/{$group_slug}/admin/' . attribute_escape( $this->slug ) . '\">' . attribute_escape( $this->name ) . '</a></li>";' ), 10, 2 );	

			/* Catch the edit screen and forward it to the plugin template */
			if ( $bp->current_component == $bp->groups->slug && 'admin' == $bp->current_action && $this->slug == $bp->action_variables[0] ) {
				add_action( 'wp', array( &$this, 'edit_screen_save' ) );
				add_action( 'groups_custom_edit_steps', array( &$this, 'edit_screen' ) );

				if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) ) {
					bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/admin' ) );		
				} else {
					add_action( 'bp_template_content_header', create_function( '', 'echo "<ul class=\"content-header-nav\">"; bp_group_admin_tabs(); echo "</ul>";' ) );
					add_action( 'bp_template_content', array( &$this, 'edit_screen' ) );
					bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );	
				}
			}
		}

		/* When we are viewing a single group, add the group extension nav item */
		if ( $this->visbility == 'public' || ( $this->visbility != 'public' && $bp->groups->current_group->user_has_access ) ) {
			if ( $this->enable_nav_item ) {
				if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item )
					bp_core_new_subnav_item( array( 'name' => ( !$this->nav_item_name ) ? $this->name : $this->nav_item_name, 'slug' => $this->slug, 'parent_slug' => BP_GROUPS_SLUG, 'parent_url' => bp_get_group_permalink( $bp->groups->current_group ) . '/', 'position' => $this->nav_item_position, 'item_css_id' => 'nav-' . $this->slug, 'screen_function' => array( &$this, '_display_hook' ), 'user_has_access' => $this->enable_nav_item ) );			

				/* When we are viewing the extension display page, set the title and options title */
				if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item && $bp->current_action == $this->slug ) {
					add_action( 'bp_template_content_header', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
			 		add_action( 'bp_template_title', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
				}
			}
			
			/* Hook the group home widget */
			if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item && ( !$bp->current_action || 'home' == $bp->current_action ) )
				add_action( 'groups_custom_group_boxes', array( &$this, 'widget_display' ) );
		}
	}
	
	function _display_hook() {
		add_action( 'bp_template_content', array( &$this, 'display' ) );
		bp_core_load_template( $this->template_file );
	}
}

function bp_register_group_extension( $group_extension_class ) {
	global $bp;
	
	if ( !class_exists( $group_extension_class ) )
		return false;
	
	$extension = new $group_extension_class;
	add_action( 'wp', array( &$extension, '_register' ), 2 );
}


?>