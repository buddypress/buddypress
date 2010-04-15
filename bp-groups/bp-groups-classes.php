<?php

Class BP_Groups_Group {
	var $id;
	var $creator_id;
	var $name;
	var $slug;
	var $description;
	var $status;
	var $enable_forum;
	var $date_created;

	var $admins;
	var $total_member_count;

	function bp_groups_group( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $group = $wpdb->get_row( $wpdb->prepare( "SELECT g.*, gm.meta_value as last_activity, gm2.meta_value as total_member_count FROM {$bp->groups->table_name} g, {$bp->groups->table_name_groupmeta} gm, {$bp->groups->table_name_groupmeta} gm2 WHERE g.id = gm.group_id AND g.id = gm2.group_id AND gm.meta_key = 'last_activity' AND gm2.meta_key = 'total_member_count' AND g.id = %d", $this->id ) ) ) {
			$this->id = $group->id;
			$this->creator_id = $group->creator_id;
			$this->name = stripslashes($group->name);
			$this->slug = $group->slug;
			$this->description = stripslashes($group->description);
			$this->status = $group->status;
			$this->enable_forum = $group->enable_forum;
			$this->date_created = $group->date_created;
			$this->last_activity = $group->last_activity;
			$this->total_member_count = $group->total_member_count;
			$this->is_member = BP_Groups_Member::check_is_member( $bp->loggedin_user->id, $this->id );

			/* Get group admins and mods */
			$admin_mods = $wpdb->get_results( $wpdb->prepare( "SELECT u.ID as user_id, u.user_login, u.user_email, u.user_nicename, m.is_admin, m.is_mod FROM {$wpdb->users} u, {$bp->groups->table_name_members} m WHERE u.ID = m.user_id AND m.group_id = %d AND ( m.is_admin = 1 OR m.is_mod = 1 )", $this->id ) );
			foreach( (array)$admin_mods as $user ) {
				if ( (int)$user->is_admin )
					$this->admins[] = $user;
				else
					$this->mods[] = $user;
			}
		}
	}

	function save() {
		global $wpdb, $bp;

		$this->creator_id = apply_filters( 'groups_group_creator_id_before_save', $this->creator_id, $this->id );
		$this->name = apply_filters( 'groups_group_name_before_save', $this->name, $this->id );
 		$this->slug = apply_filters( 'groups_group_slug_before_save', $this->slug, $this->id );
		$this->description = apply_filters( 'groups_group_description_before_save', $this->description, $this->id );
 		$this->status = apply_filters( 'groups_group_status_before_save', $this->status, $this->id );
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
					status = %s,
					enable_forum = %d,
					date_created = %s
				WHERE
					id = %d
				",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
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
					status,
					enable_forum,
					date_created
				) VALUES (
					%d, %s, %s, %s, %s, %d, %s
				)",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
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

	function delete() {
		global $wpdb, $bp;

		/* Delete groupmeta for the group */
		groups_delete_groupmeta( $this->id );

		/* Fetch the user IDs of all the members of the group */
		$user_ids = BP_Groups_Member::get_group_member_ids( $this->id );
		$user_ids = implode( ',', (array)$user_ids );

		/* Modify group count usermeta for members */
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->groups->table_name_groupmeta} SET meta_value = meta_value - 1 WHERE meta_key = 'total_group_count' AND user_id IN ( {$user_ids} )" ) );

		/* Now delete all group member entries */
		BP_Groups_Member::delete_all( $this->id );

		do_action( 'bp_groups_delete_group', $this );

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

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// Get all the group ids for the current user's groups.
		$gids = BP_Groups_Member::get_group_ids( $user_id );

		if ( !$gids['groups'] )
			return false;

		$gids = implode( ',', $gids['groups'] );

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids}) {$pag_sql}" ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids})" ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function search_groups( $filter, $limit = null, $page = null, $sort_by = false, $order = false ) {
		global $wpdb, $bp;

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $sort_by && $order ) {
			$sort_by = $wpdb->escape( $sort_by );
			$order = $wpdb->escape( $order );
			$order_sql = "ORDER BY $sort_by $order";
		}

		if ( !is_site_admin() )
			$hidden_sql = "AND status != 'hidden'";

		$paged_groups = $wpdb->get_results( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE '%%$filter%%' OR description LIKE '%%$filter%%' ) {$hidden_sql} {$order_sql} {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->groups->table_name} WHERE ( name LIKE '%%$filter%%' OR description LIKE '%%$filter%%' ) {$hidden_sq}" );

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

		$members = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );

		if ( !$members )
			return false;

		return true;
	}

	function has_membership_requests( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0", $group_id ) );
	}

	function get_membership_requests( $group_id, $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$paged_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0{$pag_sql}", $group_id ) );
		$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0", $group_id ) );

		return array( 'requests' => $paged_requests, 'total' => $total_requests );
	}

	function get( $type = 'newest', $per_page = null, $page = null, $user_id = false, $search_terms = false, $include = false, $populate_extras = true ) {
		global $wpdb, $bp;

		$sql = array();

		$sql['select'] = "SELECT g.*, gm1.meta_value AS total_member_count, gm2.meta_value AS last_activity";
		$sql['from']   = " FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2,";

		if ( !empty( $user_id ) )
			$sql['members_from'] = " {$bp->groups->table_name_members} m,";

		$sql['group_from'] = " {$bp->groups->table_name} g WHERE";

		if ( !empty( $user_id ) )
			$sql['user_where'] = " g.id = m.group_id AND";

		$sql['where'] = " g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'";

		if ( !is_user_logged_in() || ( !is_site_admin() && ( $user_id != $bp->loggedin_user->id ) ) )
			$sql['hidden'] = " AND g.status != 'hidden'";

		if ( $search_terms ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$sql['search'] = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $user_id ) )
			$sql['user'] = $wpdb->prepare( " AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id );

		if ( !empty( $include ) ) {
			$include = $wpdb->escape( $include );
			$sql['include'] = " AND g.id IN ({$include})";
		}

		switch ( $type ) {
			case 'newest': default:
				$sql['order'] = " ORDER BY g.date_created DESC";
				break;
			case 'active':
				$sql[] = "ORDER BY last_activity DESC";
				break;
			case 'popular':
				$sql[] = "ORDER BY CONVERT(gm1.meta_value, SIGNED) DESC";
				break;
			case 'alphabetical':
				$sql[] = "ORDER BY g.name ASC";
				break;
			case 'random':
				$sql[] = "ORDER BY rand()";
				break;
		}

		if ( $per_page && $page )
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page), intval( $per_page ) );

		/* Get paginated results */
		$paged_groups = $wpdb->get_results( join( ' ', (array)$sql ) );

		$total_sql['select'] = "SELECT COUNT(g.id) FROM {$bp->groups->table_name} g";

		if ( !empty( $user_id ) )
			$total_sql['select'] .= ", {$bp->groups->table_name_members} m";

		if ( !empty( $sql['hidden'] ) )
			$total_sql['where'][] = "g.status != 'hidden'";

		if ( !empty( $sql['search'] ) )
			$total_sql['where'][] = "( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";

		if ( !empty( $user_id ) )
			$total_sql['where'][] = "m.group_id = g.id AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0";

		$t_sql = $total_sql['select'];

		if ( !empty( $total_sql['where'] ) )
			$t_sql .= " WHERE " . join( ' AND ', (array)$total_sql['where'] );

		/* Get total group results */
		$total_groups = $wpdb->get_var( join( ' ', (array)$t_sql ) );

		/* Populate some extra information instead of querying each time in the loop */
		if ( !empty( $populate_extras ) ) {
			foreach ( (array)$paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array)$group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( &$paged_groups, $group_ids, $type );
		}

		unset( $sql, $total_sql );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_most_forum_topics( $limit = null, $page = null, $user_id = false, $search_terms = false, $populate_extras = true ) {
		global $wpdb, $bp, $bbdb;

		if ( !$bbdb )
			do_action( 'bbpress_init' );

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !is_site_admin() && ( $user_id != $bp->loggedin_user->id ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( $search_terms ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( $user_id ) {
			$user_id = $wpdb->escape( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array)$paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array)$group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( &$paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_most_forum_posts( $limit = null, $page = null, $search_terms = false, $populate_extras = true ) {
		global $wpdb, $bp, $bbdb;

		if ( !$bbdb )
			do_action( 'bbpress_init' );

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !is_site_admin() && ( $user_id != $bp->loggedin_user->id ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( $search_terms ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( $user_id ) {
			$user_id = $wpdb->escape( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_results( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array)$paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array)$group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( &$paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_letter( $letter, $limit = null, $page = null, $populate_extras = true ) {
		global $wpdb, $bp;

		if ( strlen($letter) > 1 || is_numeric($letter) || !$letter )
			return false;

		if ( !is_site_admin() )
			$hidden_sql = $wpdb->prepare( " AND status != 'hidden'");

		$letter = like_escape( $wpdb->escape( $letter ) );

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '$letter%%' {$hidden_sql} {$search_sql}" ) );
		}

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '$letter%%' {$hidden_sql} {$search_sql} ORDER BY g.name ASC {$pag_sql}"  ) );

		if ( !empty( $populate_extras ) ) {
			foreach ( (array)$paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array)$group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( &$paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_group_extras( $paged_groups, $group_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $group_ids ) )
			return $paged_groups;

		/* Fetch the logged in users status within each group */
		$user_status = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id IN ( {$group_ids} ) AND is_confirmed = 1 AND is_banned = 0", $bp->loggedin_user->id ) );
		for ( $i = 0; $i < count( $paged_groups ); $i++ ) {
			foreach ( (array)$user_status as $group_id ) {
				if ( $group_id == $paged_groups[$i]->id )
					$paged_groups[$i]->is_member = true;
			}
		}

		$user_banned = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_members} WHERE is_banned = 1 AND user_id = %d AND group_id IN ( {$group_ids} )", $bp->loggedin_user->id ) );
		for ( $i = 0; $i < count( $paged_groups ); $i++ ) {
			foreach ( (array)$user_banned as $group_id ) {
				if ( $group_id == $paged_groups[$i]->id )
					$paged_groups[$i]->is_banned = true;
			}
		}

		return $paged_groups;
	}

	function delete_all_invites( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d AND invite_sent = 1", $group_id ) );
	}

	function get_total_group_count() {
		global $wpdb, $bp;

		if ( !is_site_admin() )
			$hidden_sql = "WHERE status != 'hidden'";

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name} {$hidden_sql}" ) );
	}

	function get_global_forum_topic_count( $type ) {
		global $bbdb, $wpdb, $bp;

		if ( 'unreplied' == $type )
			$bp->groups->filter_sql = ' AND t.topic_posts = 1';

		$extra_sql = apply_filters( 'groups_total_public_forum_topic_count', $bp->groups->filter_sql, $type );

		return $wpdb->get_var( "SELECT COUNT(t.topic_id) FROM {$bbdb->topics} AS t, {$bp->groups->table_name} AS g LEFT JOIN {$bp->groups->table_name_groupmeta} AS gm ON g.id = gm.group_id WHERE (gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id) AND g.status = 'public' AND t.topic_status = '0' AND t.topic_sticky != '2' {$extra_sql} " );
	}

	function get_total_member_count( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0", $group_id ) );
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
			$this->date_modified = $member->date_modified;
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
			$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name_members} SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = %s, is_confirmed = %d, comments = %s, invite_sent = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->groups->table_name_members} ( user_id, group_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments, invite_sent ) VALUES ( %d, %d, %d, %d, %d, %d, %s, %s, %d, %s, %d )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent );
		}

		if ( !$wpdb->query($sql) )
			return false;

		$this->id = $wpdb->insert_id;

		do_action( 'groups_member_after_save', $this );

		return true;
	}

	function promote( $status = 'mod' ) {
		if ( 'mod' == $status ) {
			$this->is_admin = 0;
			$this->is_mod = 1;
			$this->user_title = __( 'Group Mod', 'buddypress' );
		}

		if ( 'admin' == $status ) {
			$this->is_admin = 1;
			$this->is_mod = 0;
			$this->user_title = __( 'Group Admin', 'buddypress' );
		}

		return $this->save();
	}

	function demote() {
		$this->is_mod = 0;
		$this->is_admin = 0;
		$this->user_title = false;

		return $this->save();
	}

	function ban() {
		if ( $this->is_admin )
			return false;

		$this->is_mod = 0;
		$this->is_banned = 1;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) - 1 ) );

		$group_count = get_usermeta( $this->user_id, 'total_group_count' );
		if ( !empty( $group_count ) )
			update_usermeta( $this->user_id, 'total_group_count', (int)$group_count - 1 );

		return $this->save();
	}

	function unban() {
		if ( $this->is_admin )
			return false;

		$this->is_banned = 0;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) + 1 ) );
		update_usermeta( $this->user_id, 'total_group_count', (int)get_usermeta( $this->user_id, 'total_group_count' ) + 1 );

		return $this->save();
	}

	function accept_invite() {
		$this->inviter_id = 0;
		$this->is_confirmed = 1;
		$this->date_modified = gmdate( "Y-m-d H:i:s" );
	}

	function accept_request() {
		$this->is_confirmed = 1;
		$this->date_modified = gmdate( "Y-m-d H:i:s" );
	}

	/* Static Functions */

	function delete( $user_id, $group_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}

	function get_group_ids( $user_id, $limit = false, $page = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// If the user is logged in and viewing their own groups, we can show hidden and private groups
		if ( $user_id != $bp->loggedin_user->id ) {
			$group_sql = $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0{$pag_sql}", $user_id );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			$group_sql = $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0{$pag_sql}", $user_id );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT group_id) FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id ) );
		}

		$groups = $wpdb->get_col( $group_sql );

		return array( 'groups' => $groups, 'total' => (int) $total_groups );
	}

	function get_recently_joined( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != $bp->loggedin_user->id )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY m.date_modified DESC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_is_admin_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != $bp->loggedin_user->id )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_is_mod_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $filter ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != $bp->loggedin_user->id )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function total_group_count( $user_id = false ) {
		global $bp, $wpdb;

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		if ( $user_id != $bp->loggedin_user->id && !is_site_admin() ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		}
	}

	function get_invites( $user_id, $limit = false, $page = false ) {
		global $wpdb, $bp;

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
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
		if ( bp_is_my_profile() ) {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT $total_groups", $user_id ) );
		}
	}

	function get_group_member_ids( $group_id ) {
		global $bp, $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0", $group_id ) );
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

		if ( bp_is_active( 'xprofile' ) )
			$members = $wpdb->get_results( $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, pd.value as display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u, {$bp->profile->table_name_data} pd WHERE u.ID = m.user_id AND u.ID = pd.user_id AND pd.field_id = 1 AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) );
		else
			$members = $wpdb->get_results( $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u WHERE u.ID = m.user_id AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) );

		if ( !$members )
			return false;

		if ( !isset($pag_sql) )
			$total_member_count = count($members);
		else
			$total_member_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_sql}", $group_id ) );

		/* Fetch whether or not the user is a friend */
		foreach ( (array)$members as $user ) $user_ids[] = $user->user_id;
		$user_ids = $wpdb->escape( join( ',', (array)$user_ids ) );

		if ( bp_is_active( 'friends' ) ) {
			$friend_status = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ( {$user_ids} ) ) OR (initiator_user_id IN ( {$user_ids} ) AND friend_user_id = %d )", $bp->loggedin_user->id, $bp->loggedin_user->id ) );
			for ( $i = 0; $i < count( $members ); $i++ ) {
				foreach ( (array)$friend_status as $status ) {
					if ( $status->initiator_user_id == $members[$i]->user_id || $status->friend_user_id == $members[$i]->user_id )
						$members[$i]->is_friend = $status->is_confirmed;
				}
			}
		}

		return array( 'members' => $members, 'count' => $total_member_count );
	}

	function delete_all( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );
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

	var $display_hook = 'groups_custom_group_boxes';
	var $template_file = 'groups/single/plugins';

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

		/* When we are viewing a single group, add the group extension nav item */
		if ( $this->visbility == 'public' || ( $this->visbility != 'public' && $bp->groups->current_group->user_has_access ) ) {
			if ( $this->enable_nav_item ) {
				if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item )
					bp_core_new_subnav_item( array( 'name' => ( !$this->nav_item_name ) ? $this->name : $this->nav_item_name, 'slug' => $this->slug, 'parent_slug' => BP_GROUPS_SLUG, 'parent_url' => bp_get_group_permalink( $bp->groups->current_group ), 'position' => $this->nav_item_position, 'item_css_id' => 'nav-' . $this->slug, 'screen_function' => array( &$this, '_display_hook' ), 'user_has_access' => $this->enable_nav_item ) );

				/* When we are viewing the extension display page, set the title and options title */
				if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item && $bp->current_action == $this->slug ) {
					add_action( 'bp_template_content_header', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
			 		add_action( 'bp_template_title', create_function( '', 'echo "' . attribute_escape( $this->name ) . '";' ) );
				}
			}

			/* Hook the group home widget */
			if ( $bp->current_component == $bp->groups->slug && $bp->is_single_item && ( !$bp->current_action || 'home' == $bp->current_action ) )
				add_action( $this->display_hook, array( &$this, 'widget_display' ) );
		}

		if ( $this->enable_create_step ) {
			/* Insert the group creation step for the new group extension */
			$bp->groups->group_creation_steps[$this->slug] = array( 'name' => $this->name, 'slug' => $this->slug, 'position' => $this->create_step_position );

			/* Attach the group creation step display content action */
			add_action( 'groups_custom_create_steps', array( &$this, 'create_screen' ) );

			/* Attach the group creation step save content action */
			add_action( 'groups_create_group_step_save_' . $this->slug, array( &$this, 'create_screen_save' ) );
		}

		/* Construct the admin edit tab for the new group extension */
		if ( $this->enable_edit_item ) {
			add_action( 'groups_admin_tabs', create_function( '$current, $group_slug', 'if ( "' . attribute_escape( $this->slug ) . '" == $current ) $selected = " class=\"current\""; echo "<li{$selected}><a href=\"' . $bp->root_domain . '/' . $bp->groups->slug . '/{$group_slug}/admin/' . attribute_escape( $this->slug ) . '\">' . attribute_escape( $this->name ) . '</a></li>";' ), 10, 2 );

			/* Catch the edit screen and forward it to the plugin template */
			if ( $bp->current_component == $bp->groups->slug && 'admin' == $bp->current_action && $this->slug == $bp->action_variables[0] ) {
				$this->edit_screen_save();
				add_action( 'groups_custom_edit_steps', array( &$this, 'edit_screen' ) );

				if ( '' != locate_template( array( 'groups/single/home.php' ), false ) ) {
					bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
				} else {
					add_action( 'bp_template_content_header', create_function( '', 'echo "<ul class=\"content-header-nav\">"; bp_group_admin_tabs(); echo "</ul>";' ) );
					add_action( 'bp_template_content', array( &$this, 'edit_screen' ) );
					bp_core_load_template( apply_filters( 'bp_core_template_plugin', '/groups/single/plugins' ) );
				}
			}
		}
	}

	function _display_hook() {
		add_action( 'bp_template_content', array( &$this, 'display' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', $this->template_file ) );
	}
}

function bp_register_group_extension( $group_extension_class ) {
	global $bp;

	if ( !class_exists( $group_extension_class ) )
		return false;

	/* Register the group extension on the plugins_loaded action so we have access to all plugins */
	add_action( 'init', create_function( '', '$extension = new ' . $group_extension_class . '; add_action( "pre_get_posts", array( &$extension, "_register" ) );' ) );
}


?>