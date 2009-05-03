<?php

Class BP_Groups_Group {
	var $id;
	var $creator_id;
	var $name;
	var $slug;
	var $description;
	var $news;
	var $status;
	var $is_invitation_only;
	var $enable_wire;
	var $enable_forum;
	var $enable_photos;
	var $photos_admin_only;
	var $date_created;
	
	var $avatar_thumb;
	var $avatar_full;
	
	var $user_dataset;
	
	var $admins;
	var $total_member_count;
	var $random_members;
	var $latest_wire_posts;
	var $random_photos;	
	
	function bp_groups_group( $id = null, $single = false ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
		
		if ( $single ) {
			$this->populate_meta();
		}
	}
	
	function populate() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name'] . " WHERE id = %d", $this->id );
		$group = $wpdb->get_row($sql);

		if ( $group ) {
			$this->creator_id = $group->creator_id;
			$this->name = $group->name;
			$this->slug = $group->slug;
			$this->description = $group->description;
			$this->news = $group->news;
			$this->status = $group->status;
			$this->is_invitation_only = $group->is_invitation_only;
			$this->enable_wire = $group->enable_wire;
			$this->enable_forum = $group->enable_forum;
			$this->enable_photos = $group->enable_photos;
			$this->photos_admin_only = $group->photos_admin_only;
			$this->date_created = $group->date_created;
			
			if ( !$group->avatar_thumb )
				$this->avatar_thumb = $bp['groups']['image_base'] . '/none-thumbnail.gif';
			else
				$this->avatar_thumb = $group->avatar_thumb;
			
			if ( !$group->avatar_full )
				$this->avatar_full = $bp['groups']['image_base'] . '/none.gif';
			else
				$this->avatar_full = $group->avatar_full;
			
			$this->user_dataset = $this->get_user_dataset();
			$this->total_member_count = count( $this->user_dataset );
		}	
	}
	
	function populate_meta() {
		if ( $this->id ) {
			$this->admins = $this->get_administrators();
			$this->random_members = $this->get_random_members();
			$this->latest_wire_posts = $this->get_latest_wire_posts();
			$this->random_photos = $this->get_random_photos();
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( 
				"UPDATE " . $bp['groups']['table_name'] . " SET 
					creator_id = %d, 
					name = %s, 
					slug = %s, 
					description = %s, 
					news = %s, 
					status = %s, 
					is_invitation_only = %d, 
					enable_wire = %d, 
					enable_forum = %d, 
					enable_photos = %d, 
					photos_admin_only = %d, 
					date_created = FROM_UNIXTIME(%d), 
					avatar_thumb = %s, 
					avatar_full = %s
				WHERE
					id = %d
				",
					$this->creator_id, 
					$this->name, 
					$this->slug, 
					$this->description, 
					$this->news, 
					$this->status, 
					$this->is_invitation_only, 
					$this->enable_wire, 
					$this->enable_forum, 
					$this->enable_photos, 
					$this->photos_admin_only, 
					$this->date_created, 
					$this->avatar_thumb, 
					$this->avatar_full,
					$this->id
			);
		} else {
			$sql = $wpdb->prepare( 
				"INSERT INTO " . $bp['groups']['table_name'] . " ( 
					creator_id,
					name,
					slug,
					description,
					news,
					status,
					is_invitation_only,
					enable_wire,
					enable_forum,
					enable_photos,
					photos_admin_only,
					date_created,
					avatar_thumb,
					avatar_full
				) VALUES (
					%d, %s, %s, %s, %s, %s, %d, %d, %d, %d, %d, FROM_UNIXTIME(%d), %s, %s
				)",
					$this->creator_id, 
					$this->name, 
					$this->slug, 
					$this->description, 
					$this->news, 
					$this->status, 
					$this->is_invitation_only, 
					$this->enable_wire, 
					$this->enable_forum, 
					$this->enable_photos, 
					$this->photos_admin_only, 
					$this->date_created, 
					$this->avatar_thumb, 
					$this->avatar_full 
			);
		}

		$result = $wpdb->query($sql);
		
		if ( $wpdb->insert_id )
			$this->id = $wpdb->insert_id;
		
		return $result;
	}
	
	function delete() {
		
	}
	
	function make_private() {
		
	}
	
	function make_public() {
		
	}
	
	function get_user_dataset() {
		global $wpdb, $bp;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, is_admin, inviter_id, user_title FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 1 ORDER BY rand()", $this->id ) );
	}
		
	function get_administrators() {
		for ( $i = 0; $i < count($this->user_dataset); $i++ ) {
			if ( $this->user_dataset[$i]->is_admin ) {
				$admins[] = new BP_Groups_Member( $this->user_dataset[$i]->user_id, $this->id );
			}
		}	
		
		return $admins;
	}

	function get_random_members() {
		$total_randoms = ( $this->total_member_count > 5 ) ? 5 : $this->total_member_count;
		
		for ( $i = 0; $i < $total_randoms; $i++ ) {
			$users[] = new BP_Groups_Member( $this->user_dataset[$i]->user_id, $this->id );
		}
		return $users;
	}
	
	function get_latest_wire_posts() {
		global $wpdb, $bp;
		
		
	}
	
	function get_random_photos() {
		global $wpdb, $bp;
		
		
	}

	/* Static Functions */
	
	function group_exists( $slug ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name'] . " WHERE slug = %s", $slug ) );
	}

	function get_id_from_slug( $slug ) {
		return BP_Groups_Group::group_exists( $slug );
	}

	function get_invites( $group_id ) {
		global $wpdb, $bp;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d and is_confirmed = 0", $group_id ) );
	}
	
	function search_user_groups( $filter, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// Get all the user ids for the current user's friends.
		$gids = BP_Groups_Member::get_group_ids( $user_id );
		$gids = implode( ',', $gids['ids'] );

		$sql = $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name'] . " WHERE id IN ($gids) AND name LIKE '$filter%%' OR description LIKE '$filter%%'$pag_sql" );
		$count_sql = $wpdb->prepare( "SELECT count(id) FROM " . $bp['groups']['table_name'] . " WHERE id IN ($gids) AND name LIKE '$filter%%' OR description LIKE '$filter%%'" );
		
		$group_ids = $wpdb->get_col($sql);
		$total_groups = $wpdb->get_var($count_sql);

		for ( $i = 0; $i < count($group_ids); $i++ ) {
			$groups[] = new BP_Groups_Group( (int)$group_ids[$i] );
		}

		return array( 'groups' => $groups, 'count' => $total_groups );
	}
	
	function search_groups( $filter, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		$sql = $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name'] . " WHERE name LIKE '$filter%%' OR description LIKE '$filter%%'$pag_sql" );
		$count_sql = $wpdb->prepare( "SELECT count(id) FROM " . $bp['groups']['table_name'] . " WHERE name LIKE '$filter%%' OR description LIKE '$filter%%'" );
		
		$group_ids = $wpdb->get_col($sql);
		$total_groups = $wpdb->get_var($count_sql);

		for ( $i = 0; $i < count($group_ids); $i++ ) {
			$groups[] = new BP_Groups_Group( (int)$group_ids[$i] );
		}

		return array( 'groups' => $groups, 'count' => $total_groups );
	}
	
	function check_slug( $slug ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM " . $bp['groups']['table_name'] . " WHERE slug = %s", $slug ) );		
	}
	
}

Class BP_Groups_Member {
	var $id;
	var $group_id;
	var $user_id;
	var $inviter_id;
	var $is_admin;
	var $user_title;
	var $date_modified;
	var $is_confirmed;
	
	var $user;
	
	function bp_groups_member( $user_id = null, $group_id = null, $populate = true ) {
		if ( $user_id && $group_id ) {
			$this->user_id = $user_id;
			$this->group_id = $group_id;
			
			if ( $populate )
				$this->populate();
		}
	}
	
	function populate() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );
		$member = $wpdb->get_row($sql);
		
		if ( $member ) {
			$this->id = $member->id;
			$this->inviter_id = $member->inviter_id;
			$this->is_admin = $member->is_admin;
			$this->user_title = $member->user_title;
			$this->date_modified = $member->date_modified;
			$this->is_confirmed = $member->is_confirmed;
			
			$this->user = new BP_Core_User( $this->user_id );
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( "UPDATE " . $bp['groups']['table_name_members'] . " SET inviter_id = %d, is_admin = %d, user_title = %s, date_modified = FROM_UNIXTIME(%d), is_confirmed = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->user_title, $this->date_modified, $this->is_confirmed, $this->id );
		
		} else {
			$sql = $wpdb->prepare( "INSERT INTO " . $bp['groups']['table_name_members'] . " ( user_id, group_id, inviter_id, is_admin, user_title, date_modified, is_confirmed ) VALUES ( %d, %d, %d, %d, %s, FROM_UNIXTIME(%d), %d )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->user_title, $this->date_modified, $this->is_confirmed );
		}

		if ( !$result = $wpdb->query($sql) )
			return false;
		
		return true;
	}
	
	function promote() {
		
	}
	
	function demote() {
		
	}
	
	function accept_invite() {
		$this->is_confirmed = 1;
		$this->date_modified = time();
	}
		
	/* Static Functions */
	
	function get_group_ids( $user_id, $page = false, $limit = false ) {
		global $wpdb, $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND is_confirmed = 1$pag_sql", $user_id ) );
		$group_count = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(group_id) FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND is_confirmed = 1", $user_id ) );
		
		
		return array( 'ids' => $group_ids, 'count' => $group_count );
		
	}
	
	function get_invites( $user_id ) {
		global $wpdb, $bp;
		
		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d and is_confirmed = 0", $user_id ) );
		
		for ( $i = 0; $i < count($group_ids); $i++ ) {
			$groups[] = new BP_Groups_Group($group_ids[$i]);
		}
		
		return $groups;
	}
	
	function delete( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}
	
	function check_is_admin( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d AND is_admin = 1", $user_id, $group_id ) );
	}
	
	function check_is_member( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );	
	}
}

?>