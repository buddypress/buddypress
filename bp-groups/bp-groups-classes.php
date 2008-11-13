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

		$sql = $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name'] . " WHERE id = %d", $this->id );
		$group = $wpdb->get_row($sql);

		if ( $group ) {
			$this->creator_id = $group->creator_id;
			$this->name = stripslashes($group->name);
			$this->slug = $group->slug;
			$this->description = stripslashes($group->description);
			$this->news = stripslashes($group->news);
			$this->status = $group->status;
			$this->is_invitation_only = $group->is_invitation_only;
			$this->enable_wire = $group->enable_wire;
			$this->enable_forum = $group->enable_forum;
			$this->enable_photos = $group->enable_photos;
			$this->photos_admin_only = $group->photos_admin_only;
			$this->date_created = strtotime($group->date_created);
			$this->total_member_count = groups_get_groupmeta( $this->id, 'total_member_count' );
			
			if ( !$group->avatar_thumb || strpos( $group->avatar_thumb, 'none-thumbnail' ) )
				$this->avatar_thumb = 'http://www.gravatar.com/avatar/' . md5( $this->id . '@buddypress.org') . '?d=identicon&amp;s=50';
			else
				$this->avatar_thumb = $group->avatar_thumb;
			
			if ( !$group->avatar_full || strpos( $group->avatar_thumb, 'none-' ) )
				$this->avatar_full = 'http://www.gravatar.com/avatar/' . md5( $this->id . '@buddypress.org') . '?d=identicon&amp;s=150';
			else
				$this->avatar_full = $group->avatar_full;
			
			if ( $get_user_dataset ) {
				$this->user_dataset = $this->get_user_dataset();
				
				if ( !$this->total_member_count ) {
					$this->total_member_count = count( $this->user_dataset );
					groups_update_groupmeta( $this->id, 'total_member_count', $this->total_member_count );
				}
			}
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
		
		if ( $wpdb->query($sql) === false )
			return false;
		
		if ( !$this->id ) {
			$this->id = $wpdb->insert_id;
		}
		
		return true;
	}
	
	function make_private() {
		
	}
	
	function make_public() {
		
	}
	
	function get_user_dataset() {
		global $wpdb, $bp;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, is_admin, inviter_id, user_title, is_mod FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand()", $this->id ) );
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
	
	function get_latest_wire_posts() {
		global $wpdb, $bp;
		
		
	}
	
	function get_random_photos() {
		global $wpdb, $bp;
		
		
	}
	
	function is_member() {
		global $bp;
		
		for ( $i = 0; $i < count($this->user_dataset); $i++ ) {
			if ( $this->user_dataset[$i]->user_id == $bp['loggedin_userid'] ) {
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

			if ( $total_count = get_usermeta( $user->user_id, 'total_group_count' ) != '' ) {
				update_usermeta( $user->user_id, 'total_group_count', (int)$total_count - 1 );
			}
			
			// Now delete the group member record
			BP_Groups_Member::delete( $user->user_id, $this->id, false );
		}
		
		// Delete the wire posts for this group if the wire is installed
		if ( function_exists('bp_wire_install') ) {
			BP_Wire_Post::delete_all_for_item( $this->id, $bp['groups']['table_name_wire'] );
		}
		
		do_action( 'bp_groups_delete_group_content', $this->id );
		
		// Finally remove the group entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['groups']['table_name'] . " WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}
	

	/* Static Functions */
		
	function group_exists( $slug, $table_name = false ) {
		global $wpdb, $bp;
		
		if ( !$table_name )
			$table_name = $bp['groups']['table_name'];
		
		if ( !$slug )
			return false;
			
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE slug = %s", $slug ) );
	}

	function get_id_from_slug( $slug ) {
		return BP_Groups_Group::group_exists( $slug );
	}

	function get_invites( $group_id ) {
		global $wpdb, $bp;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d and is_confirmed = 0", $group_id ) );
	}
	
	function filter_user_groups( $filter, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		like_escape($filter);
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// Get all the group ids for the current user's groups.
		$gids = BP_Groups_Member::get_group_ids( $bp['current_userid'], false, false, false );
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
		
		$sql = $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name'] . " WHERE status != 'hidden' AND name LIKE '%%$filter%%' OR description LIKE '%%$filter%%'{$order_sql}{$pag_sql}" );
		$count_sql = $wpdb->prepare( "SELECT count(id) FROM " . $bp['groups']['table_name'] . " WHERE status != 'hidden' AND name LIKE '%%$filter%%' OR description LIKE '%%$filter%%'" );
		
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
	
	function get_slug( $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM " . $bp['groups']['table_name'] . " WHERE id = %d", $group_id ) );		
	}
	
	function has_members( $group_id ) {
		global $wpdb, $bp;
		
		$members = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d", $group_id ) );						

		if ( !$members )
			return false;
		
		return true;
	}
	
	function has_membership_requests( $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 0", $group_id ) );						
	}
	
	function get_membership_requests( $group_id, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 0{$pag_sql}", $group_id ) );			
	}
	
	function get_newest( $limit = 5 ) {
		global $wpdb, $bp;
		
		if ( !$limit )
			$limit = 5;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM " . $bp['groups']['table_name'] . " WHERE status != 'hidden' ORDER BY date_created DESC LIMIT %d", $limit ) ); 
	}
	
	function get_active( $limit = 5 ) {
		global $wpdb, $bp;
		
		if ( !$limit )
			$limit = 5;

		return $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM " . $bp['groups']['table_name_groupmeta'] . " gm, " . $bp['groups']['table_name'] . " g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'last_activity' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC LIMIT %d", $limit ) ); 
	}
	
	function get_popular( $limit = 5 ) {
		global $wpdb, $bp;
		
		if ( !$limit )
			$limit = 5;

		return $wpdb->get_results( $wpdb->prepare( "SELECT gm.group_id FROM " . $bp['groups']['table_name_groupmeta'] . " gm, " . $bp['groups']['table_name'] . " g WHERE g.id = gm.group_id AND g.status != 'hidden' AND gm.meta_key = 'total_member_count' ORDER BY CONVERT(gm.meta_value, SIGNED) DESC LIMIT %d", $limit ) ); 
	}
	
	function get_all( $only_public = true, $limit = null, $page = null, $sort_by = false, $order = false, $instantiate = false ) {
		global $wpdb, $bp;
		
		if ( $only_public )
			$public_sql = $wpdb->prepare( " WHERE status = 'public'" );
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $sort_by && $order ) {
			$sort_by = $wpdb->escape( $sort_by );
			$order = $wpdb->escape( $order );
			$order_sql = "ORDER BY $sort_by $order";
			
			switch ( $sort_by ) {
				default:
					$sql = $wpdb->prepare( "SELECT id, slug FROM " . $bp['groups']['table_name'] . " {$public_sql} {$order_sql} {$pag_sql}" ); 	
					break;
				case 'members':
					$sql = $wpdb->prepare( "SELECT g.id, g.slug FROM " . $bp['groups']['table_name'] . " g, " . $bp['groups']['table_name_groupmeta'] . " gm WHERE g.id = gm.group_id AND gm.meta_key = 'total_member_count' ORDER BY CONVERT(gm.meta_value, SIGNED) {$order} {$pag_sql}" ); 
					break;
				case 'last_active':
					$sql = $wpdb->prepare( "SELECT g.id, g.slug FROM " . $bp['groups']['table_name'] . " g, " . $bp['groups']['table_name_groupmeta'] . " gm WHERE g.id = gm.group_id AND gm.meta_key = 'last_activity' ORDER BY CONVERT(gm.meta_value, SIGNED) {$order} {$pag_sql}" ); 
					break;
			}
		} else {
			$sql = $wpdb->prepare( "SELECT id, slug FROM " . $bp['groups']['table_name'] . " {$public_sql} {$order_sql} {$pag_sql}" ); 	
		}
		
		$groups = $wpdb->get_results($sql);
		
		if ( !$instantiate )
			return $groups;
		
		for ( $i = 0; $i < count($groups); $i++ ) {
			$group_objs[] = new BP_Groups_Group( $groups[$i]->id ); 
		}
		
		return $group_objs;
	}
	
	function get_random() {
		global $wpdb, $bp;
		
		return $wpdb->get_row( $wpdb->prepare( "SELECT id, slug FROM " . $bp['groups']['table_name'] . " WHERE status = 'public' ORDER BY rand() LIMIT 1" ) ); 		
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
			$sql = $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );
		
		if ( $this->id )
			$sql = $wpdb->prepare( "SELECT * FROM " . $bp['groups']['table_name_members'] . " WHERE id = %d", $this->id );
			
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
			
			$this->user = new BP_Core_User( $this->user_id );
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( "UPDATE " . $bp['groups']['table_name_members'] . " SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = FROM_UNIXTIME(%d), is_confirmed = %d, comments = %s WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO " . $bp['groups']['table_name_members'] . " ( user_id, group_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments ) VALUES ( %d, %d, %d, %d, %d, %d, %s, FROM_UNIXTIME(%d), %d, %s )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments );
		}

		if ( !$wpdb->query($sql) )
			return false;
		
		$this->id = $wpdb->insert_id;
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
		
		$delete_result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
		
		return $delete_result;
	}
	
	function get_group_ids( $user_id, $page = false, $limit = false, $get_total = true ) {
		global $wpdb, $bp;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		// If the user is logged in and viewing their own groups, we can show hidden and closed groups
		if ( bp_is_home() ) {
			$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND inviter_id = 0 AND is_banned = 0{$pag_sql}", $user_id ) );	
		} else {
			$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM " . $bp['groups']['table_name_members'] . " m, " . $bp['groups']['table_name'] . " g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.inviter_id = 0 AND m.is_banned = 0{$pag_sql}", $user_id ) );	
		}
		
		if ( $get_total )
			$group_count = BP_Groups_Member::total_group_count( $user_id );
	
		return array( 'ids' => $group_ids, 'count' => $group_count );
	}
	
	function total_group_count( $user_id = false ) {
		global $bp, $wpdb;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];
			
		if ( bp_is_home() ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(group_id) FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND inviter_id = 0 AND is_banned = 0", $user_id ) );			
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(m.group_id) FROM " . $bp['groups']['table_name_members'] . " m, " . $bp['groups']['table_name'] . " g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.inviter_id = 0 m.is_banned = 0", $user_id ) );			
		}
	}
	
	function get_invites( $user_id ) {
		global $wpdb, $bp;
		
		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d and is_confirmed = 0 AND inviter_id != 0", $user_id ) );
		
		for ( $i = 0; $i < count($group_ids); $i++ ) {
			$groups[] = new BP_Groups_Group($group_ids[$i]);
		}
		
		return $groups;
	}
	
	function check_is_admin( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d AND is_admin = 1 AND is_banned = 0", $user_id, $group_id ) );
	}
	
	function check_is_mod( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d AND is_mod = 1 AND is_banned = 0", $user_id, $group_id ) );
	}
	
	function check_is_member( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id, $group_id ) );	
	}
	
	function check_is_banned( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT is_banned FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}
	
	function check_for_membership_request( $user_id, $group_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "SELECT id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND is_banned = 0", $user_id, $group_id ) );	
	}
	
	function get_random_groups( $user_id, $total_groups = 5 ) {
		global $wpdb, $bp;
		
		// If the user is logged in and viewing their random groups, we can show hidden and closed groups
		if ( bp_is_home() ) {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM " . $bp['groups']['table_name_members'] . " m, " . $bp['groups']['table_name'] . " g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );			
		}
	}
	
	function get_group_administrator_ids( $group_id ) {
		global $bp, $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_admin = 1 AND is_banned = 0", $group_id ) );
	}
	
	function get_group_moderator_ids( $group_id ) {
		global $bp, $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_mod = 1 AND is_banned = 0", $group_id ) );
	}
	
	function get_all_for_group( $group_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true ) {
		global $bp, $wpdb;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		if ( $exclude_admins_mods )
			$exclude_sql = $wpdb->prepare( "AND is_admin = 0 AND is_mod = 0" );
		
		if ( $exclude_banned )
			$banned_sql = $wpdb->prepare( " AND is_banned = 0" );
		
		$members = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql} {$pag_sql}", $group_id ) );
		
		if ( !$members )
			return false;
		
		if ( !isset($pag_sql) ) 
			$total_member_count = count($members);
		else
			$total_member_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(user_id) FROM " . $bp['groups']['table_name_members'] . " WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql}", $group_id ) );
	
		return array( 'members' => $members, 'count' => $total_member_count );
	}
	
	function delete_all_for_user( $user_id ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['groups']['table_name_members'] . " WHERE user_id = %d", $user_id ) ); 		
	}
}

?>