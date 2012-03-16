<?php

/**
 * BuddyPress Blogs Classes
 *
 * @package BuddyPress
 * @subpackage BlogsClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * The main BuddyPress blog class
 *
 * @since BuddyPress (1.0)
 * @package BuddyPress
 * @subpackage BlogsClasses
 */
class BP_Blogs_Blog {
	var $id;
	var $user_id;
	var $blog_id;

	function __construct( $id = null ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		$blog = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name} WHERE id = %d", $this->id ) );

		$this->user_id = $blog->user_id;
		$this->blog_id = $blog->blog_id;
	}

	function save() {
		global $wpdb, $bp;

		$this->user_id = apply_filters( 'bp_blogs_blog_user_id_before_save', $this->user_id, $this->id );
		$this->blog_id = apply_filters( 'bp_blogs_blog_id_before_save', $this->blog_id, $this->id );

		do_action_ref_array( 'bp_blogs_blog_before_save', array( &$this ) );

		// Don't try and save if there is no user ID or blog ID set.
		if ( !$this->user_id || !$this->blog_id )
			return false;

		// Don't save if this blog has already been recorded for the user.
		if ( !$this->id && $this->exists() )
			return false;

		if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare( "UPDATE {$bp->blogs->table_name} SET user_id = %d, blog_id = %d WHERE id = %d", $this->user_id, $this->blog_id, $this->id );
		} else {
			// Save
			$sql = $wpdb->prepare( "INSERT INTO {$bp->blogs->table_name} ( user_id, blog_id ) VALUES ( %d, %d )", $this->user_id, $this->blog_id );
		}

		if ( !$wpdb->query($sql) )
			return false;

		do_action_ref_array( 'bp_blogs_blog_after_save', array( &$this ) );

		if ( $this->id )
			return $this->id;
		else
			return $wpdb->insert_id;
	}

	function exists() {
		global $bp, $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->blogs->table_name} WHERE user_id = %d AND blog_id = %d", $this->user_id, $this->blog_id ) );
	}

	/* Static Functions */

	function get( $type, $limit = false, $page = false, $user_id = 0, $search_terms = false ) {
		global $bp, $wpdb;

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = "AND wb.public = 1";
		else
			$hidden_sql = '';

		$pag_sql = ( $limit && $page ) ? $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) ) : '';

		$user_sql = !empty( $user_id ) ? $wpdb->prepare( " AND b.user_id = %d", $user_id ) : '';

		switch ( $type ) {
			case 'active': default:
				$order_sql = "ORDER BY bm.meta_value DESC";
				break;
			case 'alphabetical':
				$order_sql = "ORDER BY bm2.meta_value ASC";
				break;
			case 'newest':
				$order_sql = "ORDER BY wb.registered DESC";
				break;
			case 'random':
				$order_sql = "ORDER BY RAND()";
				break;
		}

		if ( !empty( $search_terms ) ) {
			$filter = like_escape( $wpdb->escape( $search_terms ) );
			$paged_blogs = $wpdb->get_results( "SELECT b.blog_id, b.user_id as admin_user_id, u.user_email as admin_user_email, wb.domain, wb.path, bm.meta_value as last_activity, bm2.meta_value as name FROM {$bp->blogs->table_name} b, {$bp->blogs->table_name_blogmeta} bm, {$bp->blogs->table_name_blogmeta} bm2, {$wpdb->base_prefix}blogs wb, {$wpdb->users} u WHERE b.blog_id = wb.blog_id AND b.user_id = u.ID AND b.blog_id = bm.blog_id AND b.blog_id = bm2.blog_id AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql} AND bm.meta_key = 'last_activity' AND bm2.meta_key = 'name' AND bm2.meta_value LIKE '%%$filter%%' {$user_sql} GROUP BY b.blog_id {$order_sql} {$pag_sql}" );
			$total_blogs = $wpdb->get_var( "SELECT COUNT(DISTINCT b.blog_id) FROM {$bp->blogs->table_name} b, {$wpdb->base_prefix}blogs wb, {$bp->blogs->table_name_blogmeta} bm, {$bp->blogs->table_name_blogmeta} bm2 WHERE b.blog_id = wb.blog_id AND bm.blog_id = b.blog_id AND bm2.blog_id = b.blog_id AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql} AND bm.meta_key = 'name' AND bm2.meta_key = 'description' AND ( bm.meta_value LIKE '%%$filter%%' || bm2.meta_value LIKE '%%$filter%%' ) {$user_sql}" );
		} else {
			$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT b.blog_id, b.user_id as admin_user_id, u.user_email as admin_user_email, wb.domain, wb.path, bm.meta_value as last_activity, bm2.meta_value as name FROM {$bp->blogs->table_name} b, {$bp->blogs->table_name_blogmeta} bm, {$bp->blogs->table_name_blogmeta} bm2, {$wpdb->base_prefix}blogs wb, {$wpdb->users} u WHERE b.blog_id = wb.blog_id AND b.user_id = u.ID AND b.blog_id = bm.blog_id AND b.blog_id = bm2.blog_id {$user_sql} AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql} AND bm.meta_key = 'last_activity' AND bm2.meta_key = 'name' GROUP BY b.blog_id {$order_sql} {$pag_sql}" ) );
			$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT b.blog_id) FROM {$bp->blogs->table_name} b, {$wpdb->base_prefix}blogs wb WHERE b.blog_id = wb.blog_id {$user_sql} AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql}" ) );
		}

		$blog_ids = array();
		foreach ( (array) $paged_blogs as $blog ) {
			$blog_ids[] = $blog->blog_id;
		}

		$blog_ids = $wpdb->escape( join( ',', (array) $blog_ids ) );
		$paged_blogs = BP_Blogs_Blog::get_blog_extras( $paged_blogs, $blog_ids, $type );

		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}

	function delete_blog_for_all( $blog_id ) {
		global $wpdb, $bp;

		bp_blogs_delete_blogmeta( $blog_id );
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE blog_id = %d", $blog_id ) );
	}

	function delete_blog_for_user( $blog_id, $user_id = null ) {
		global $wpdb, $bp;

		if ( !$user_id )
			$user_id = bp_loggedin_user_id();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE user_id = %d AND blog_id = %d", $user_id, $blog_id ) );
	}

	function delete_blogs_for_user( $user_id = null ) {
		global $wpdb, $bp;

		if ( !$user_id )
			$user_id = bp_loggedin_user_id();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE user_id = %d", $user_id ) );
	}

	function get_blogs_for_user( $user_id = 0, $show_hidden = false ) {
		global $bp, $wpdb;

		if ( !$user_id )
			$user_id = bp_displayed_user_id();

		// Show logged in users their hidden blogs.
		if ( !bp_is_my_profile() && !$show_hidden )
			$blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT b.blog_id, b.id, bm1.meta_value as name, wb.domain, wb.path FROM {$bp->blogs->table_name} b, {$wpdb->base_prefix}blogs wb, {$bp->blogs->table_name_blogmeta} bm1 WHERE b.blog_id = wb.blog_id AND b.blog_id = bm1.blog_id AND bm1.meta_key = 'name' AND wb.public = 1 AND wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND b.user_id = %d ORDER BY b.blog_id", $user_id ) );
		else
			$blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT b.blog_id, b.id, bm1.meta_value as name, wb.domain, wb.path FROM {$bp->blogs->table_name} b, {$wpdb->base_prefix}blogs wb, {$bp->blogs->table_name_blogmeta} bm1 WHERE b.blog_id = wb.blog_id AND b.blog_id = bm1.blog_id AND bm1.meta_key = 'name' AND wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND b.user_id = %d ORDER BY b.blog_id", $user_id ) );

		$total_blog_count = BP_Blogs_Blog::total_blog_count_for_user( $user_id );

		$user_blogs = array();
		foreach ( (array) $blogs as $blog ) {
			$user_blogs[$blog->blog_id] = new stdClass;
			$user_blogs[$blog->blog_id]->id = $blog->id;
			$user_blogs[$blog->blog_id]->blog_id = $blog->blog_id;
			$user_blogs[$blog->blog_id]->siteurl = ( is_ssl() ) ? 'https://' . $blog->domain . $blog->path : 'http://' . $blog->domain . $blog->path;
			$user_blogs[$blog->blog_id]->name = $blog->name;
		}

		return array( 'blogs' => $user_blogs, 'count' => $total_blog_count );
	}

	function get_blog_ids_for_user( $user_id = 0 ) {
		global $bp, $wpdb;

		if ( !$user_id )
			$user_id = bp_displayed_user_id();

		return $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$bp->blogs->table_name} WHERE user_id = %d", $user_id ) );
	}

	function is_recorded( $blog_id ) {
		global $bp, $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->blogs->table_name} WHERE blog_id = %d", $blog_id ) );
	}

	function total_blog_count_for_user( $user_id = null ) {
		global $bp, $wpdb;

		if ( !$user_id )
			$user_id = bp_displayed_user_id();

		// If the user is logged in return the blog count including their hidden blogs.
		if ( ( is_user_logged_in() && $user_id == bp_loggedin_user_id() ) || bp_current_user_can( 'bp_moderate' ) )
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND user_id = %d", $user_id) );
		else
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND user_id = %d", $user_id) );
	}

	function search_blogs( $filter, $limit = null, $page = null ) {
		global $wpdb, $bp;

		$filter = like_escape( $wpdb->escape( $filter ) );

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "AND wb.public = 1";

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$paged_blogs = $wpdb->get_results( "SELECT DISTINCT bm.blog_id FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE ( ( bm.meta_key = 'name' OR bm.meta_key = 'description' ) AND bm.meta_value LIKE '%%$filter%%' ) {$hidden_sql} AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY meta_value ASC{$pag_sql}" );
		$total_blogs = $wpdb->get_var( "SELECT COUNT(DISTINCT bm.blog_id) FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE ( ( bm.meta_key = 'name' OR bm.meta_key = 'description' ) AND bm.meta_value LIKE '%%$filter%%' ) {$hidden_sql} AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY meta_value ASC" );

		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}

	function get_all( $limit = null, $page = null ) {
		global $bp, $wpdb;

		$hidden_sql = !bp_current_user_can( 'bp_moderate' ) ? "AND wb.public = 1" : '';
		$pag_sql = ( $limit && $page ) ? $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) ) : '';

		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT b.blog_id FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 {$hidden_sql} {$pag_sql}" ) );
		$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 {$hidden_sql}" ) );

		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}

	function get_by_letter( $letter, $limit = null, $page = null ) {
		global $bp, $wpdb;

		$letter = like_escape( $wpdb->escape( $letter ) );

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "AND wb.public = 1";

		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT bm.blog_id FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE bm.meta_key = 'name' AND bm.meta_value LIKE '$letter%%' {$hidden_sql} AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY bm.meta_value ASC{$pag_sql}" ) );
		$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT bm.blog_id) FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE bm.meta_key = 'name' AND bm.meta_value LIKE '$letter%%' {$hidden_sql} AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY bm.meta_value ASC" ) );

		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}

	function get_blog_extras( &$paged_blogs, &$blog_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $blog_ids ) )
			return $paged_blogs;

		for ( $i = 0, $count = count( $paged_blogs ); $i < $count; ++$i ) {
			$blog_prefix = $wpdb->get_blog_prefix( $paged_blogs[$i]->blog_id );
			$paged_blogs[$i]->latest_post = $wpdb->get_row( "SELECT post_title, guid FROM {$blog_prefix}posts WHERE post_status = 'publish' AND post_type = 'post' AND id != 1 ORDER BY id DESC LIMIT 1" );
		}

		/* Fetch the blog description for each blog (as it may be empty we can't fetch it in the main query). */
		$blog_descs = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id, meta_value as description FROM {$bp->blogs->table_name_blogmeta} WHERE meta_key = 'description' AND blog_id IN ( {$blog_ids} )" ) );

		for ( $i = 0, $count = count( $paged_blogs ); $i < $count; ++$i ) {
			foreach ( (array) $blog_descs as $desc ) {
				if ( $desc->blog_id == $paged_blogs[$i]->blog_id )
					$paged_blogs[$i]->description = $desc->description;
			}
		}

		return $paged_blogs;
	}

	function is_hidden( $blog_id ) {
		global $wpdb;

		if ( !(int) $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT public FROM {$wpdb->base_prefix}blogs WHERE blog_id = %d", $blog_id ) ) )
			return true;

		return false;
	}
}

?>
