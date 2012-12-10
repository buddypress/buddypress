<?php

/**
 * BuddyPress Groups Classes
 *
 * @package BuddyPress
 * @subpackage GroupsClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Groups_Group {
	var $id;
	var $creator_id;
	var $name;
	var $slug;
	var $description;
	var $status;
	var $enable_forum;
	var $date_created;

	var $admins;
	var $mods;
	var $total_member_count;

	/**
	 * Is the current user a member of this group?
	 *
	 * @since BuddyPress (1.2)
	 * @var bool
	 */
	public $is_member;

	/**
	 * Timestamp of the last activity that happened in this group.
	 *
	 * @since BuddyPress (1.2)
	 * @var string
	 */
	public $last_activity;

	/**
	 * If this is a private or hidden group, does the current user have access?
	 *
	 * @since BuddyPress (1.6)
	 * @var bool
	 */
	public $user_has_access;

	function __construct( $id = null ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $group = $wpdb->get_row( $wpdb->prepare( "SELECT g.* FROM {$bp->groups->table_name} g WHERE g.id = %d", $this->id ) ) ) {			
			bp_groups_update_meta_cache( $this->id );
						
			$this->id                 = $group->id;
			$this->creator_id         = $group->creator_id;
			$this->name               = stripslashes($group->name);
			$this->slug               = $group->slug;
			$this->description        = stripslashes($group->description);
			$this->status             = $group->status;
			$this->enable_forum       = $group->enable_forum;
			$this->date_created       = $group->date_created;
			$this->last_activity      = groups_get_groupmeta( $this->id, 'last_activity' );
			$this->total_member_count = groups_get_groupmeta( $this->id, 'total_member_count' );
			$this->is_member          = BP_Groups_Member::check_is_member( bp_loggedin_user_id(), $this->id );
			
			// If this is a private or hidden group, does the current user have access?
			if ( 'private' == $this->status || 'hidden' == $this->status ) {
				if ( $this->is_member && is_user_logged_in() || bp_current_user_can( 'bp_moderate' ) )
					$this->user_has_access = true;
				else
					$this->user_has_access = false;
			} else {
				$this->user_has_access = true;
			}

			// Get group admins and mods
			$admin_mods = $wpdb->get_results( apply_filters( 'bp_group_admin_mods_user_join_filter', $wpdb->prepare( "SELECT u.ID as user_id, u.user_login, u.user_email, u.user_nicename, m.is_admin, m.is_mod FROM {$wpdb->users} u, {$bp->groups->table_name_members} m WHERE u.ID = m.user_id AND m.group_id = %d AND ( m.is_admin = 1 OR m.is_mod = 1 )", $this->id ) ) );
			foreach( (array) $admin_mods as $user ) {
				if ( (int) $user->is_admin )
					$this->admins[] = $user;
				else
					$this->mods[] = $user;
			}
		}
	}

	function save() {
		global $wpdb, $bp;

		$this->creator_id   = apply_filters( 'groups_group_creator_id_before_save',   $this->creator_id,   $this->id );
		$this->name         = apply_filters( 'groups_group_name_before_save',         $this->name,         $this->id );
 		$this->slug         = apply_filters( 'groups_group_slug_before_save',         $this->slug,         $this->id );
		$this->description  = apply_filters( 'groups_group_description_before_save',  $this->description,  $this->id );
 		$this->status       = apply_filters( 'groups_group_status_before_save',       $this->status,       $this->id );
		$this->enable_forum = apply_filters( 'groups_group_enable_forum_before_save', $this->enable_forum, $this->id );
		$this->date_created = apply_filters( 'groups_group_date_created_before_save', $this->date_created, $this->id );

		do_action_ref_array( 'groups_group_before_save', array( &$this ) );

		if ( !empty( $this->id ) ) {
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

		if ( empty( $this->id ) )
			$this->id = $wpdb->insert_id;

		do_action_ref_array( 'groups_group_after_save', array( &$this ) );
		
		wp_cache_delete( 'bp_groups_group_' . $this->id, 'bp' );

		return true;
	}

	function delete() {
		global $wpdb, $bp;

		// Delete groupmeta for the group
		groups_delete_groupmeta( $this->id );

		// Fetch the user IDs of all the members of the group
		$user_ids    = BP_Groups_Member::get_group_member_ids( $this->id );
		$user_id_str = implode( ',', (array) $user_ids );

		// Modify group count usermeta for members
		$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_value = meta_value - 1 WHERE meta_key = 'total_group_count' AND user_id IN ( {$user_id_str} )" );

		// Now delete all group member entries
		BP_Groups_Member::delete_all( $this->id );

		do_action_ref_array( 'bp_groups_delete_group', array( &$this, $user_ids ) );

		wp_cache_delete( 'bp_groups_group_' . $this->id, 'bp' );

		// Finally remove the group entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name} WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}

	/** Static Methods ********************************************************/

	function group_exists( $slug, $table_name = false ) {
		global $wpdb, $bp;

		if ( empty( $table_name ) )
			$table_name = $bp->groups->table_name;

		if ( empty( $slug ) )
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

	function filter_user_groups( $filter, $user_id = 0, $order = false, $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// Get all the group ids for the current user's groups.
		$gids = BP_Groups_Member::get_group_ids( $user_id );

		if ( empty( $gids['groups'] ) )
			return false;

		$gids = implode( ',', $gids['groups'] );

		$paged_groups = $wpdb->get_results( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids}) {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->groups->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids})" );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function search_groups( $filter, $limit = null, $page = null, $sort_by = false, $order = false ) {
		global $wpdb, $bp;

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $sort_by ) && !empty( $order ) ) {
			$sort_by   = $wpdb->escape( $sort_by );
			$order     = $wpdb->escape( $order );
			$order_sql = "ORDER BY $sort_by $order";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
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

		if ( empty( $members ) )
			return false;

		return true;
	}

	function has_membership_requests( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0", $group_id ) );
	}

	function get_membership_requests( $group_id, $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$paged_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0{$pag_sql}", $group_id ) );
		$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0", $group_id ) );

		return array( 'requests' => $paged_requests, 'total' => $total_requests );
	}

	function get( $type = 'newest', $per_page = null, $page = null, $user_id = 0, $search_terms = false, $include = false, $populate_extras = true, $exclude = false, $show_hidden = false ) {
		global $wpdb, $bp;

		$sql       = array();
		$total_sql = array();

		$sql['select'] = "SELECT g.*, gm1.meta_value AS total_member_count, gm2.meta_value AS last_activity";
		$sql['from']   = " FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2,";

		if ( !empty( $user_id ) )
			$sql['members_from'] = " {$bp->groups->table_name_members} m,";

		$sql['group_from'] = " {$bp->groups->table_name} g WHERE";

		if ( !empty( $user_id ) )
			$sql['user_where'] = " g.id = m.group_id AND";

		$sql['where'] = " g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'";

		if ( empty( $show_hidden ) )
			$sql['hidden'] = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$sql['search'] = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $user_id ) )
			$sql['user'] = $wpdb->prepare( " AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id );

		if ( !empty( $include ) ) {
			if ( is_array( $include ) )
				$include = implode( ',', $include );

			$include = $wpdb->escape( $include );
			$sql['include'] = " AND g.id IN ({$include})";
		}

		if ( !empty( $exclude ) ) {
			if ( is_array( $exclude ) )
				$exclude = implode( ',', $exclude );

			$exclude = $wpdb->escape( $exclude );
			$sql['exclude'] = " AND g.id NOT IN ({$exclude})";
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

		if ( !empty( $per_page ) && !empty( $page ) )
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page), intval( $per_page ) );

		// Get paginated results
		$paged_groups_sql = apply_filters( 'bp_groups_get_paged_groups_sql', join( ' ', (array) $sql ), $sql );
		$paged_groups     = $wpdb->get_results( $paged_groups_sql );

		$total_sql['select'] = "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name} g, {$bp->groups->table_name_members} gm1, {$bp->groups->table_name_groupmeta} gm2";

		if ( !empty( $user_id ) )
			$total_sql['select'] .= ", {$bp->groups->table_name_members} m";

		if ( !empty( $sql['hidden'] ) )
			$total_sql['where'][] = "g.status != 'hidden'";

		if ( !empty( $sql['search'] ) )
			$total_sql['where'][] = "( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";

		if ( !empty( $user_id ) )
			$total_sql['where'][] = "m.group_id = g.id AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0";

		// Already escaped in the paginated results block
		if ( ! empty( $include ) )
			$total_sql['where'][] = "g.id IN ({$include})";

		// Already escaped in the paginated results block
		if ( ! empty( $exclude ) )
			$total_sql['where'][] = "g.id NOT IN ({$exclude})";

		$total_sql['where'][] = "g.id = gm1.group_id";
		$total_sql['where'][] = "g.id = gm2.group_id";
		$total_sql['where'][] = "gm2.meta_key = 'last_activity'";

		$t_sql = $total_sql['select'];

		if ( !empty( $total_sql['where'] ) )
			$t_sql .= " WHERE " . join( ' AND ', (array) $total_sql['where'] );

		// Get total group results
		$total_groups_sql = apply_filters( 'bp_groups_get_total_groups_sql', $t_sql, $total_sql );
		$total_groups     = $wpdb->get_var( $total_groups_sql );

		$group_ids = array();
		foreach ( (array) $paged_groups as $group ) {
			$group_ids[] = $group->id;
		}
		
		// Populate some extra information instead of querying each time in the loop
		if ( !empty( $populate_extras ) ) {
			$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, $type );
		}
		
		// Grab all groupmeta
		bp_groups_update_meta_cache( $group_ids );

		unset( $sql, $total_sql );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_most_forum_topics( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp, $bbdb;

		if ( empty( $bbdb ) )
			do_action( 'bbpress_init' );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude = $wpdb->escape( $exclude );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id = $wpdb->escape( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_most_forum_posts( $limit = null, $page = null, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp, $bbdb;

		if ( empty( $bbdb ) )
			do_action( 'bbpress_init' );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude = $wpdb->escape( $exclude );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id = $wpdb->escape( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_results( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} " );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_by_letter( $letter, $limit = null, $page = null, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp;

		// Multibyte compliance
		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $letter, 'UTF-8' ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		} else {
			if ( strlen( $letter ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		}

		if ( !empty( $exclude ) ) {
			$exclude = $wpdb->escape( $exclude );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = " AND status != 'hidden'";

		$letter = like_escape( $wpdb->escape( $letter ) );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '$letter%%' {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '$letter%%' {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY g.name ASC {$pag_sql}" );

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_random( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $search_sql = $exclude_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = "AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = like_escape( $wpdb->escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude = $wpdb->escape( $exclude );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id = $wpdb->escape( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm INNER JOIN {$bp->groups->table_name} g ON gm.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_group_extras( &$paged_groups, &$group_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $group_ids ) )
			return $paged_groups;

		// Fetch the logged in users status within each group
		$user_status = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id IN ( {$group_ids} ) AND is_confirmed = 1 AND is_banned = 0", bp_loggedin_user_id() ) );
		for ( $i = 0, $count = count( $paged_groups ); $i < $count; ++$i ) {
			$paged_groups[$i]->is_member = false;

			foreach ( (array) $user_status as $group_id ) {
				if ( $group_id == $paged_groups[$i]->id ) {
					$paged_groups[$i]->is_member = true;
				}
			}
		}

		$user_banned = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_members} WHERE is_banned = 1 AND user_id = %d AND group_id IN ( {$group_ids} )", bp_loggedin_user_id() ) );
		for ( $i = 0, $count = count( $paged_groups ); $i < $count; ++$i ) {
			$paged_groups[$i]->is_banned = false;

			foreach ( (array) $user_banned as $group_id ) {
				if ( $group_id == $paged_groups[$i]->id ) {
					$paged_groups[$i]->is_banned = true;
				}
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

		$hidden_sql = '';
		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "WHERE status != 'hidden'";

		return $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->groups->table_name} {$hidden_sql}" );
	}

	function get_global_forum_topic_count( $type ) {
		global $bbdb, $wpdb, $bp;

		if ( 'unreplied' == $type )
			$bp->groups->filter_sql = ' AND t.topic_posts = 1';

		/**
		 * Provide backward-compatibility for the groups_total_public_forum_topic_count SQL filter. 
		 * Developers: DO NOT use this filter. It will be removed in BP 1.7. Instead, use
		 * get_global_forum_topic_count_extra_sql. See https://buddypress.trac.wordpress.org/ticket/4306
		 */
		$maybe_extra_sql = apply_filters( 'groups_total_public_forum_topic_count', $bp->groups->filter_sql, $type );

		if ( is_int( $maybe_extra_sql ) )
			$extra_sql = $bp->groups->filter_sql;
		else
			$extra_sql = $maybe_extra_sql;

		// Developers: use this filter instead
		$extra_sql = apply_filters( 'get_global_forum_topic_count_extra_sql', $bp->groups->filter_sql, $type );

		// Make sure the $extra_sql begins with an AND
		if ( 'AND' != substr( trim( strtoupper( $extra_sql ) ), 0, 3 ) )
			$extra_sql = ' AND ' . $extra_sql;

		return $wpdb->get_var( "SELECT COUNT(t.topic_id) FROM {$bbdb->topics} AS t, {$bp->groups->table_name} AS g LEFT JOIN {$bp->groups->table_name_groupmeta} AS gm ON g.id = gm.group_id WHERE (gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id) AND g.status = 'public' AND t.topic_status = '0' AND t.topic_sticky != '2' {$extra_sql} " );
	}

	function get_total_member_count( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0", $group_id ) );
	}

	/**
	 * Get a total count of all topics of a given status, across groups/forums
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 *
	 * @param str $status 'public', 'private', 'hidden', 'all' Which group types to count
	 * @return int The topic count
	 */
	function get_global_topic_count( $status = 'public', $search_terms = false ) {
		global $bbdb, $wpdb, $bp;

		switch ( $status ) {
			case 'all' :
				$status_sql = '';
				break;

			case 'hidden' :
				$status_sql = "AND g.status = 'hidden'";
				break;

			case 'private' :
				$status_sql = "AND g.status = 'private'";
				break;

			case 'public' :
			default :
				$status_sql = "AND g.status = 'public'";
				break;
		}

		$sql = array();

		$sql['select'] = "SELECT COUNT(t.topic_id)";

		$sql['from'] = "FROM {$bbdb->topics} AS t INNER JOIN {$bp->groups->table_name_groupmeta} AS gm ON t.forum_id = gm.meta_value INNER JOIN {$bp->groups->table_name} AS g ON gm.group_id = g.id";

		$sql['where'] = "WHERE gm.meta_key = 'forum_id' {$status_sql} AND t.topic_status = '0' AND t.topic_sticky != '2'";

		if ( $search_terms ) {
			$st = like_escape( $search_terms );
			$sql['where'] .= " AND (  t.topic_title LIKE '%{$st}%' )";
		}

		return $wpdb->get_var( implode( ' ', $sql ) );
	}
}

class BP_Groups_Member {
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

	function __construct( $user_id = 0, $group_id = 0, $id = false, $populate = true ) {

		// User and group are not empty, and ID is
		if ( !empty( $user_id ) && !empty( $group_id ) && empty( $id ) ) {
			$this->user_id  = $user_id;
			$this->group_id = $group_id;

			if ( !empty( $populate ) ) {
				$this->populate();
			}
		}

		// ID is not empty
		if ( !empty( $id ) ) {
			$this->id = $id;

			if ( !empty( $populate ) ) {
				$this->populate();
			}
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $this->user_id && $this->group_id && !$this->id )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );

		if ( !empty( $this->id ) )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE id = %d", $this->id );

		$member = $wpdb->get_row($sql);

		if ( !empty( $member ) ) {
			$this->id            = $member->id;
			$this->group_id      = $member->group_id;
			$this->user_id       = $member->user_id;
			$this->inviter_id    = $member->inviter_id;
			$this->is_admin      = $member->is_admin;
			$this->is_mod        = $member->is_mod;
			$this->is_banned     = $member->is_banned;
			$this->user_title    = $member->user_title;
			$this->date_modified = $member->date_modified;
			$this->is_confirmed  = $member->is_confirmed;
			$this->comments      = $member->comments;
			$this->invite_sent   = $member->invite_sent;

			$this->user = new BP_Core_User( $this->user_id );
		}
	}

	function save() {
		global $wpdb, $bp;

		$this->user_id       = apply_filters( 'groups_member_user_id_before_save',       $this->user_id,       $this->id );
		$this->group_id      = apply_filters( 'groups_member_group_id_before_save',      $this->group_id,      $this->id );
		$this->inviter_id    = apply_filters( 'groups_member_inviter_id_before_save',    $this->inviter_id,    $this->id );
		$this->is_admin      = apply_filters( 'groups_member_is_admin_before_save',      $this->is_admin,      $this->id );
		$this->is_mod        = apply_filters( 'groups_member_is_mod_before_save',        $this->is_mod,        $this->id );
		$this->is_banned     = apply_filters( 'groups_member_is_banned_before_save',     $this->is_banned,     $this->id );
		$this->user_title    = apply_filters( 'groups_member_user_title_before_save',    $this->user_title,    $this->id );
		$this->date_modified = apply_filters( 'groups_member_date_modified_before_save', $this->date_modified, $this->id );
		$this->is_confirmed  = apply_filters( 'groups_member_is_confirmed_before_save',  $this->is_confirmed,  $this->id );
		$this->comments      = apply_filters( 'groups_member_comments_before_save',      $this->comments,      $this->id );
		$this->invite_sent   = apply_filters( 'groups_member_invite_sent_before_save',   $this->invite_sent,   $this->id );

		do_action_ref_array( 'groups_member_before_save', array( &$this ) );

		if ( !empty( $this->id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name_members} SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = %s, is_confirmed = %d, comments = %s, invite_sent = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent, $this->id );
		} else {
			// Ensure that user is not already a member of the group before inserting
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 LIMIT 1", $this->user_id, $this->group_id ) ) ) {
				return false;
			}
			
			$sql = $wpdb->prepare( "INSERT INTO {$bp->groups->table_name_members} ( user_id, group_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments, invite_sent ) VALUES ( %d, %d, %d, %d, %d, %d, %s, %s, %d, %s, %d )", $this->user_id, $this->group_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent );
		}

		if ( !$wpdb->query( $sql ) )
			return false;

		$this->id = $wpdb->insert_id;

		do_action_ref_array( 'groups_member_after_save', array( &$this ) );

		return true;
	}

	function promote( $status = 'mod' ) {
		if ( 'mod' == $status ) {
			$this->is_admin   = 0;
			$this->is_mod     = 1;
			$this->user_title = __( 'Group Mod', 'buddypress' );
		}

		if ( 'admin' == $status ) {
			$this->is_admin   = 1;
			$this->is_mod     = 0;
			$this->user_title = __( 'Group Admin', 'buddypress' );
		}

		return $this->save();
	}

	function demote() {
		$this->is_mod     = 0;
		$this->is_admin   = 0;
		$this->user_title = false;

		return $this->save();
	}

	function ban() {

		if ( !empty( $this->is_admin ) )
			return false;

		$this->is_mod = 0;
		$this->is_banned = 1;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) - 1 ) );

		$group_count = bp_get_user_meta( $this->user_id, 'total_group_count', true );
		if ( !empty( $group_count ) )
			bp_update_user_meta( $this->user_id, 'total_group_count', (int) $group_count - 1 );

		return $this->save();
	}

	function unban() {

		if ( !empty( $this->is_admin ) )
			return false;

		$this->is_banned = 0;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) + 1 ) );
		bp_update_user_meta( $this->user_id, 'total_group_count', (int) bp_get_user_meta( $this->user_id, 'total_group_count', true ) + 1 );

		return $this->save();
	}

	function accept_invite() {

		$this->inviter_id    = 0;
		$this->is_confirmed  = 1;
		$this->date_modified = bp_core_current_time();

		bp_update_user_meta( $this->user_id, 'total_group_count', (int) bp_get_user_meta( $this->user_id, 'total_group_count', true ) + 1 );
	}

	function accept_request() {

		$this->is_confirmed = 1;
		$this->date_modified = bp_core_current_time();

		bp_update_user_meta( $this->user_id, 'total_group_count', (int) bp_get_user_meta( $this->user_id, 'total_group_count', true ) + 1 );
	}

	function remove() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $this->user_id, $this->group_id );

		if ( !$result = $wpdb->query( $sql ) )
			return false;

		groups_update_groupmeta( $this->group_id, 'total_member_count', ( (int) groups_get_groupmeta( $this->group_id, 'total_member_count' ) - 1 ) );

		$group_count = bp_get_user_meta( $this->user_id, 'total_group_count', true );
		if ( !empty( $group_count ) )
			bp_update_user_meta( $this->user_id, 'total_group_count', (int) $group_count - 1 );

		return $result;
	}

	/** Static Methods ********************************************************/

	function delete( $user_id, $group_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}

	function get_group_ids( $user_id, $limit = false, $page = false ) {
		global $wpdb, $bp;

		$pag_sql = '';
		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// If the user is logged in and viewing their own groups, we can show hidden and private groups
		if ( $user_id != bp_loggedin_user_id() ) {
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

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY m.date_modified DESC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_is_admin_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function get_is_mod_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter = like_escape( $wpdb->escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function total_group_count( $user_id = 0 ) {
		global $bp, $wpdb;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		if ( $user_id != bp_loggedin_user_id() && !bp_current_user_can( 'bp_moderate' ) ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		}
	}

	function get_invites( $user_id, $limit = false, $page = false, $exclude = false ) {
		global $wpdb, $bp;

		$pag_sql = ( !empty( $limit ) && !empty( $page ) ) ? $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) ) : '';

		$exclude_sql = !empty( $exclude ) ? $wpdb->prepare( " AND g.id NOT IN (%s)", $exclude ) : '';

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d {$exclude_sql} ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d {$exclude_sql} ORDER BY date_modified ASC", $user_id ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	function check_has_invite( $user_id, $group_id, $type = 'sent' ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		$sql = "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND inviter_id != 0";

		if ( 'sent' == $type )
			$sql .= " AND invite_sent = 1";

		return $wpdb->get_var( $wpdb->prepare( $sql, $user_id, $group_id ) );
	}

	function delete_invite( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND inviter_id != 0 AND invite_sent = 1", $user_id, $group_id ) );
	}

	function delete_request( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

 		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND inviter_id = 0 AND invite_sent = 0", $user_id, $group_id ) );
	}

	function check_is_admin( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_admin = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	function check_is_mod( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_mod = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	function check_is_member( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id, $group_id ) );
	}

	function check_is_banned( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT is_banned FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d", $user_id, $group_id ) );
	}

	/**
	 * Is the specified user the creator of the group?
	 *
	 * @global object $bp BuddyPress global settings
	 * @global wpdb $wpdb WordPress database object
	 * @param int $user_id
	 * @param int $group_id
	 * @since 1.2.6
	 */
	function check_is_creator( $user_id, $group_id ) {
		global $bp, $wpdb;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name} WHERE creator_id = %d AND id = %d", $user_id, $group_id ) );
	}

	function check_for_membership_request( $user_id, $group_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id = %d AND is_confirmed = 0 AND is_banned = 0 AND inviter_id = 0", $user_id, $group_id ) );
	}

	function get_random_groups( $user_id, $total_groups = 5 ) {
		global $wpdb, $bp;

		// If the user is logged in and viewing their random groups, we can show hidden and private groups
		if ( bp_is_my_profile() ) {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT {$total_groups}", $user_id ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE m.group_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT {$total_groups}", $user_id ) );
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

	function get_all_for_group( $group_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true, $exclude = false ) {
		global $bp, $wpdb;

		$pag_sql = '';
		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$exclude_admins_sql = '';
		if ( !empty( $exclude_admins_mods ) )
			$exclude_admins_sql = "AND is_admin = 0 AND is_mod = 0";

		$banned_sql = '';
		if ( !empty( $exclude_banned ) )
			$banned_sql = " AND is_banned = 0";

		$exclude_sql = '';
		if ( !empty( $exclude ) )
			$exclude_sql = " AND m.user_id NOT IN ({$exclude})";

		if ( bp_is_active( 'xprofile' ) )
			$members = $wpdb->get_results( apply_filters( 'bp_group_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, pd.value as display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u, {$bp->profile->table_name_data} pd WHERE u.ID = m.user_id AND u.ID = pd.user_id AND pd.field_id = 1 AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) ) );
		else
			$members = $wpdb->get_results( apply_filters( 'bp_group_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$bp->groups->table_name_members} m, {$wpdb->users} u WHERE u.ID = m.user_id AND group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $group_id ) ) );

		if ( empty( $members ) )
			return false;

		if ( empty( $pag_sql ) )
			$total_member_count = count( $members );
		else
			$total_member_count = $wpdb->get_var( apply_filters( 'bp_group_members_count_user_join_filter', $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql}", $group_id ) ) );

		// Fetch whether or not the user is a friend
		foreach ( (array) $members as $user )
			$user_ids[] = $user->user_id;

		$user_ids = $wpdb->escape( join( ',', (array) $user_ids ) );

		if ( bp_is_active( 'friends' ) ) {
			$friend_status = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ( {$user_ids} ) ) OR (initiator_user_id IN ( {$user_ids} ) AND friend_user_id = %d )", bp_loggedin_user_id(), bp_loggedin_user_id() ) );
			for ( $i = 0, $count = count( $members ); $i < $count; ++$i ) {
				foreach ( (array) $friend_status as $status ) {
					if ( $status->initiator_user_id == $members[$i]->user_id || $status->friend_user_id == $members[$i]->user_id ) {
						$members[$i]->is_friend = $status->is_confirmed;
					}
				}
			}
		}

		return array( 'members' => $members, 'count' => $total_member_count );
	}

	function delete_all( $group_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );
	}

	/**
	 * Delete all group membership information for the specified user
	 *
	 * @global object $bp BuddyPress global settings
	 * @global wpdb $wpdb WordPress database object
	 * @param int $user_id
	 * @since 1.0
	 * @uses BP_Groups_Member
	 */
	function delete_all_for_user( $user_id ) {
		global $bp, $wpdb;

		// Get all the group ids for the current user's groups and update counts
		$group_ids = BP_Groups_Member::get_group_ids( $user_id );
		foreach ( $group_ids['groups'] as $group_id ) {
			groups_update_groupmeta( $group_id, 'total_member_count', groups_get_total_member_count( $group_id ) - 1 );

			// If current user is the creator of a group and is the sole admin, delete that group to avoid counts going out-of-sync
			if ( groups_is_user_admin( $user_id, $group_id ) && count( groups_get_group_admins( $group_id ) ) < 2 && groups_is_user_creator( $user_id, $group_id ) )
				groups_delete_group( $group_id );
		}

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
	
	// The name/slug of the Group Admin tab for this extension
	var $admin_name = '';
	var $admin_slug = '';

	// The name/slug of the Group Creation tab for this extension
	var $create_name = '';
	var $create_slug = '';

	// Will this extension be visible to non-members of a group? Options: public/private
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

	function display() {}

	function widget_display() {}

	function edit_screen() {}

	function edit_screen_save() {}

	function create_screen() {}

	function create_screen_save() {}

	// Private Methods

	function _register() {
		global $bp;
		
		// If admin/create names and slugs are not provided, they fall back on the main
		// name and slug for the extension
		if ( !$this->admin_name ) {
			$this->admin_name = $this->name;
		}
		
		if ( !$this->admin_slug ) {
			$this->admin_slug = $this->slug;
		}
		
		if ( !$this->create_name ) {
			$this->create_name = $this->name;
		}
		
		if ( !$this->create_slug ) {
			$this->create_slug = $this->slug;
		}

		if ( !empty( $this->enable_create_step ) ) {
			// Insert the group creation step for the new group extension
			$bp->groups->group_creation_steps[$this->create_slug] = array( 'name' => $this->create_name, 'slug' => $this->create_slug, 'position' => $this->create_step_position );

			// Attach the group creation step display content action
			add_action( 'groups_custom_create_steps', array( &$this, 'create_screen' ) );

			// Attach the group creation step save content action
			add_action( 'groups_create_group_step_save_' . $this->create_slug, array( &$this, 'create_screen_save' ) );
		}

		// When we are viewing a single group, add the group extension nav item
		if ( bp_is_group() ) {
			if ( $this->visibility == 'public' || ( $this->visibility != 'public' && $bp->groups->current_group->user_has_access ) ) {
				if ( $this->enable_nav_item ) {
					bp_core_new_subnav_item( array( 'name' => ( !$this->nav_item_name ) ? $this->name : $this->nav_item_name, 'slug' => $this->slug, 'parent_slug' => $bp->groups->current_group->slug, 'parent_url' => bp_get_group_permalink( $bp->groups->current_group ), 'position' => $this->nav_item_position, 'item_css_id' => 'nav-' . $this->slug, 'screen_function' => array( &$this, '_display_hook' ), 'user_has_access' => $this->enable_nav_item ) );

					// When we are viewing the extension display page, set the title and options title
					if ( bp_is_current_action( $this->slug ) ) {
						add_action( 'bp_template_content_header', create_function( '', 'echo "' . esc_attr( $this->name ) . '";' ) );
						add_action( 'bp_template_title', create_function( '', 'echo "' . esc_attr( $this->name ) . '";' ) );
					}
				}

				// Hook the group home widget
				if ( !bp_current_action() && bp_is_current_action( 'home' ) )
					add_action( $this->display_hook, array( &$this, 'widget_display' ) );
			}
		}

		// Construct the admin edit tab for the new group extension
		if ( !empty( $this->enable_edit_item ) && bp_is_item_admin() ) {
			add_action( 'groups_admin_tabs', create_function( '$current, $group_slug', '$selected = ""; if ( "' . esc_attr( $this->admin_slug ) . '" == $current ) $selected = " class=\"current\""; echo "<li{$selected}><a href=\"' . trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/{$group_slug}/admin/' . esc_attr( $this->admin_slug ) ) . '\">' . esc_attr( $this->admin_name ) . '</a></li>";' ), 10, 2 );

			// Catch the edit screen and forward it to the plugin template
			if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( $this->admin_slug, 0 ) ) {
				// Check whether the user is saving changes
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

	if ( !class_exists( $group_extension_class ) )
		return false;

	// Register the group extension on the bp_init action so we have access
	// to all plugins.
	add_action( 'bp_init', create_function( '', '$extension = new ' . $group_extension_class . '; add_action( "bp_actions", array( &$extension, "_register" ), 8 );' ), 11 );
}

?>