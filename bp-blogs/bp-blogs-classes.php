<?php

Class BP_Blogs_Blog {
	var $id;
	var $user_id;
	var $blog_id;
	
	function bp_blogs_blog( $id = null ) {
		global $bp, $wpdb;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		if ( $id ) {
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
		
		if ( $this->id )
			return $this->id;
		else
			return $wpdb->insert_id;
	}
	
	function exists() {
		global $bp, $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$bp->blogs->table_name} WHERE user_id = %d AND blog_id = %d", $this->user_id, $this->blog_id ) );
	}
	
	/* Static Functions */
	
	function delete_blog_for_all( $blog_id ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		bp_blogs_delete_blogmeta( $blog_id );

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE blog_id = %d", $blog_id ) );
	}
	
	function delete_blog_for_user( $blog_id, $user_id = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $bp->loggedin_user->id;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE user_id = %d AND blog_id = %d", $user_id, $blog_id ) );
	}
	
	function delete_blogs_for_user( $user_id = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();

		if ( !$user_id )
			$user_id = $bp->loggedin_user->id;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE user_id = %d", $user_id ) );
	}
	
	function get_blogs_for_user( $user_id = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		// Show logged in users their hidden blogs.
		if ( !bp_is_home() )
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT b.blog_id FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND b.user_id = %d ", $user_id) );
		else
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT b.blog_id FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND b.user_id = %d ", $user_id) );
			
		$total_blog_count = BP_Blogs_Blog::total_blog_count( $user_id );
		
		for ( $i = 0; $i < count($blog_ids); $i++ ) {
			$blogs[] = array(
				'id' => $blog_ids[$i],
				'siteurl' => get_blog_option($blog_ids[$i], 'siteurl'),
				'title' => get_blog_option($blog_ids[$i], 'blogname'),
				'description' => get_blog_option($blog_ids[$i], 'blogdescription')
			);
		}

		return array( 'blogs' => $blogs, 'count' => $total_blog_count );
	}
	
	function is_recorded( $blog_id ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->blogs->table_name} WHERE blog_id = %d", $blog_id ) );
	}
	
	function total_blog_count( $user_id = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		// If the user is logged in return the blog count including their hidden blogs.
		if ( !bp_is_home() )
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND user_id = %d", $user_id) );
		else
			return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.deleted = 0 AND wb.spam = 0 AND wb.mature = 0 AND wb.archived = '0' AND user_id = %d", $user_id) );			
	}
	
	function get_all( $limit = null, $page = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(bm.blog_id) FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 AND bm.meta_key = 'last_activity' ORDER BY CONVERT(bm.meta_value, SIGNED) DESC" ) );
		}
			
		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT bm.blog_id FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 AND bm.meta_key = 'last_activity' ORDER BY CONVERT(bm.meta_value, SIGNED) DESC {$pag_sql}" ) );

		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}
	
	function get_by_letter( $letter, $limit = null, $page = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		like_escape($letter);
				
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(bm.blog_id) FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE bm.meta_key = 'name' AND bm.meta_value LIKE '$letter%%' AND wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY bm.meta_value ASC" ) );
		}
	
		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT bm.blog_id FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE bm.meta_key = 'name' AND bm.meta_value LIKE '$letter%%' AND wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY bm.meta_value ASC{$pag_sql}" ) );
		
		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );		
	}
	
	function search_blogs( $filter, $limit = null, $page = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		like_escape($filter);
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(bm.blog_id) FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE ( ( bm.meta_key = 'name' OR bm.meta_key = 'description' ) AND bm.meta_value LIKE '%%$filter%%' ) AND wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY meta_value ASC" ) );
		}

		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT bm.blog_id FROM {$bp->blogs->table_name_blogmeta} bm LEFT JOIN {$wpdb->base_prefix}blogs wb ON bm.blog_id = wb.blog_id WHERE ( ( bm.meta_key = 'name' OR bm.meta_key = 'description' ) AND bm.meta_value LIKE '%%$filter%%' ) AND wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY meta_value ASC{$pag_sql}" ) );
		
		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );		
	}
	
	function get_random( $limit = null, $page = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_blogs = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT count(b.blog_id) FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0 ORDER BY rand()" ) );
		}
		
		$paged_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT b.blog_id FROM {$bp->blogs->table_name} b LEFT JOIN {$wpdb->base_prefix}blogs wb ON b.blog_id = wb.blog_id WHERE wb.public = 1 AND wb.mature = 0 AND wb.spam = 0 AND wb.archived = '0' AND wb.deleted = 0  ORDER BY rand() {$pag_sql}" ) ); 		
		
		return array( 'blogs' => $paged_blogs, 'total' => $total_blogs );
	}
	
	function is_hidden( $blog_id ) {
		global $wpdb;

		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !(int)$wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT public FROM {$wpdb->base_prefix}blogs WHERE blog_id = %d", $blog_id ) ) )
			return true;
		
		return false;
	}
}

Class BP_Blogs_Post {
	var $id;
	var $user_id;
	var $blog_id;
	var $post_id;
	var $date_created;
	
	function bp_blogs_post( $id = null, $blog_id = null, $post_id = null ) {
		global $bp, $wpdb;

		if ( $id || ( !$id && $blog_id && $post_id ) ) {
			$this->id = $id;
			$this->blog_id = $blog_id;
			$this->post_id = $post_id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $this->id )
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name_blog_posts} WHERE id = %d", $this->id ) );
		else
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name_blog_posts} WHERE blog_id = %d AND post_id = %d", $this->blog_id, $this->post_id ) );
		
		$this->id = $post->id;	
		$this->user_id = $post->user_id;
		$this->blog_id = $post->blog_id;
		$this->post_id = $post->post_id;
		$this->date_created = $post->date_created;
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare( "UPDATE {$bp->blogs->table_name_blog_posts} SET post_id = %d, blog_id = %d, user_id = %d, date_created = FROM_UNIXTIME(%d) WHERE id = %d", $this->post_id, $this->blog_id, $this->user_id, $this->date_created, $this->id );
		} else {
			// Save
			$sql = $wpdb->prepare( "INSERT INTO {$bp->blogs->table_name_blog_posts} ( post_id, blog_id, user_id, date_created ) VALUES ( %d, %d, %d, FROM_UNIXTIME(%d) )", $this->post_id, $this->blog_id, $this->user_id, $this->date_created );
		}
		
		if ( !$wpdb->query($sql) )
			return false;
		
		if ( $this->id )
			return $this->id;
		else
			return $wpdb->insert_id;	
	}
	
	/* Static Functions */
	
	function delete( $post_id, $blog_id ) {
		global $wpdb, $bp, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_posts} WHERE blog_id = %d AND post_id = %d", $blog_id, $post_id ) );
	}
	
	function delete_oldest( $user_id = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $current_user->ID;
			
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_posts} WHERE user_id = %d ORDER BY date_created ASC LIMIT 1", $user_id ) ); 		
	}
	
	function delete_posts_for_user( $user_id = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();

		if ( !$user_id )
			$user_id = $bp->loggedin_user->id;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_posts} WHERE user_id = %d", $user_id ) );
	}
	
	function delete_posts_for_blog( $blog_id ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_posts} WHERE blog_id = %d", $blog_id ) );
	}
	
	function get_latest_posts( $blog_id = null, $limit = 5 ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( $blog_id )
			$blog_sql = $wpdb->prepare( " AND p.blog_id = %d", $blog_id );
		
		$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT p.post_id, p.blog_id FROM {$bp->blogs->table_name_blog_posts} p LEFT JOIN {$wpdb->base_prefix}blogs b ON p.blog_id = b.blog_id WHERE b.public = 1 AND b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 $blog_sql ORDER BY p.date_created DESC LIMIT $limit" ) );

		for ( $i = 0; $i < count($post_ids); $i++ ) {
			$posts[$i] = BP_Blogs_Post::fetch_post_content($post_ids[$i]);
		}
		
		return $posts;
	}
	
	function get_posts_for_user( $user_id = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		// Show a logged in user their posts on private blogs, but not anyone else.
		if ( !bp_is_home() ) {
			$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT p.post_id, p.blog_id FROM {$bp->blogs->table_name_blog_posts} p LEFT JOIN {$wpdb->base_prefix}blogs b ON p.blog_id = b.blog_id WHERE b.public = 1 AND b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND p.user_id = %d ORDER BY p.date_created DESC", $user_id) );
			$total_post_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(p.post_id) FROM {$bp->blogs->table_name_blog_posts} p LEFT JOIN {$wpdb->base_prefix}blogs b ON p.blog_id = b.blog_id WHERE b.public = 1 AND b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND p.user_id = %d", $user_id) );
		} else {
			$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT p.post_id, p.blog_id FROM {$bp->blogs->table_name_blog_posts} p LEFT JOIN {$wpdb->base_prefix}blogs b ON p.blog_id = b.blog_id WHERE b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND p.user_id = %d ORDER BY p.date_created DESC", $user_id) );
			$total_post_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(p.post_id) FROM {$bp->blogs->table_name_blog_posts} p LEFT JOIN {$wpdb->base_prefix}blogs b ON p.blog_id = b.blog_id WHERE b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND p.user_id = %d", $user_id) );			
		}
			
			
		for ( $i = 0; $i < count($post_ids); $i++ ) {
			$posts[$i] = BP_Blogs_Post::fetch_post_content($post_ids[$i]);
		}

		return array( 'posts' => $posts, 'count' => $total_post_count );
	}
	
	function fetch_post_content( $post_object ) {
		// TODO: switch_to_blog() calls are expensive and this needs to be changed.
		switch_to_blog( $post_object->blog_id );
		$post = get_post($post_object->post_id);
		$post->blog_id = $post_object->blog_id;
		restore_current_blog();

		return $post;
	}
	
	function get_total_recorded_for_user( $user_id = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $current_user->ID;

		return $wpdb->get_var( $wpdb->prepare( "SELECT count(post_id) FROM {$bp->blogs->table_name_blog_posts} WHERE user_id = %d", $user_id ) );
	}
	
	function is_recorded( $post_id, $blog_id, $user_id = null ) {
		global $bp, $wpdb, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $current_user->ID;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$bp->blogs->table_name_blog_posts} WHERE post_id = %d AND blog_id = %d AND user_id = %d", $post_id, $blog_id, $user_id ) );
	}
	
	function total_post_count( $blog_id ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$blog_id )
			return false;
			
		return $wpdb->get_var( $wpdb->prepare( "SELECT count(post_id) FROM {$bp->blogs->table_name_blog_posts} WHERE blog_id = %d", $blog_id ) );		
	}
	
	function get_all() {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		return $wpdb->get_col( $wpdb->prepare( "SELECT post_id, blog_id FROM " . $bp->blogs->table_name_blog_posts ) );
	}
	
}

Class BP_Blogs_Comment {
	var $id;
	var $user_id;
	var $blog_id;
	var $comment_id;
	var $comment_post_id;
	var $date_created;
	
	function bp_blogs_comment( $id = null ) {
		global $bp, $wpdb;

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
			
		if ( $id ) {
			$this->id = $id;
			$this->populate( $id );
		}
	}

	function populate( $id ) {
		global $wpdb, $bp;
		
		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name_blog_comments} WHERE id = %d", $this->id ) );

		$this->comment_id = $comment->comment_id;
		$this->user_id = $comment->user_id;
		$this->blog_id = $comment->blog_id;
		$this->comment_post_id = $comment->comment_post_id;
		$this->date_created = $comment->date_created;
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare( "UPDATE {$bp->blogs->table_name_blog_comments} SET comment_id = %d, comment_post_id = %d, blog_id = %d, user_id = %d, date_created = FROM_UNIXTIME(%d) WHERE id = %d", $this->comment_id, $this->comment_post_id, $this->blog_id, $this->user_id, $this->date_created, $this->id );
		} else {
			// Save
			$sql = $wpdb->prepare( "INSERT INTO {$bp->blogs->table_name_blog_comments} ( comment_id, comment_post_id, blog_id, user_id, date_created ) VALUES ( %d, %d, %d, %d, FROM_UNIXTIME(%d) )", $this->comment_id, $this->comment_post_id, $this->blog_id, $this->user_id, $this->date_created );
		}

		if ( !$wpdb->query($sql) )
			return false;
		
		if ( $this->id )
			return $this->id;
		else
			return $wpdb->insert_id;	
	}

	/* Static Functions */
	
	function delete( $comment_id, $blog_id ) {
		global $wpdb, $bp, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
			
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_comments} WHERE comment_id = %d AND blog_id = %d", $comment_id, $blog_id ) );
	}
	
	function delete_oldest( $user_id = null ) {
		global $wpdb, $bp, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $current_user->ID;
			
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_comments} WHERE user_id = %d ORDER BY date_created ASC LIMIT 1", $user_id ) ); 		
	}
	
	function delete_comments_for_user( $user_id = null ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();

		if ( !$user_id )
			$user_id = $bp->loggedin_user->id;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_comments} WHERE user_id = %d", $user_id ) );
	}
	
	function delete_comments_for_blog( $blog_id ) {
		global $wpdb, $bp;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blog_comments} WHERE blog_id = %d", $blog_id ) );
	}
	
	function get_comments_for_user( $user_id = null ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
			
		// Show the logged in user their comments on hidden blogs, but not to anyone else.
		if ( !bp_is_home() ) {
			$comment_ids = $wpdb->get_results( $wpdb->prepare( "SELECT c.comment_id, c.blog_id FROM {$bp->blogs->table_name_blog_comments} c LEFT JOIN {$wpdb->base_prefix}blogs b ON c.blog_id = b.blog_id WHERE b.public = 1 AND b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND c.user_id = %d ORDER BY c.date_created ASC", $user_id) );
			$total_comment_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(c.comment_id) FROM {$bp->blogs->table_name_blog_comments} c LEFT JOIN {$wpdb->base_prefix}blogs b ON c.blog_id = b.blog_id WHERE b.public = 1 AND b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND c.user_id = %d", $user_id) );
		} else {
			$comment_ids = $wpdb->get_results( $wpdb->prepare( "SELECT c.comment_id, c.blog_id FROM {$bp->blogs->table_name_blog_comments} c LEFT JOIN {$wpdb->base_prefix}blogs b ON c.blog_id = b.blog_id WHERE b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND c.user_id = %d ORDER BY c.date_created ASC", $user_id) );
			$total_comment_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(c.comment_id) FROM {$bp->blogs->table_name_blog_comments} c LEFT JOIN {$wpdb->base_prefix}blogs b ON c.blog_id = b.blog_id WHERE b.deleted = 0 AND b.archived = '0' AND b.spam = 0 AND b.mature = 0 AND c.user_id = %d", $user_id) );			
		}
		
		for ( $i = 0; $i < count($comment_ids); $i++ ) {
			$comments[$i] = BP_Blogs_Comment::fetch_comment_content($comment_ids[$i]);
		}

		return array( 'comments' => $comments, 'count' => $total_comment_count );
	}
	
	function fetch_comment_content( $comment_object ) {	
		switch_to_blog($comment_object->blog_id);
		$comment = get_comment($comment_object->comment_id);
		$comment->blog_id = $comment_object->blog_id;
		$comment->post = &get_post( $comment->comment_post_ID );
		restore_current_blog();
		
		return $comment;
	}
	
	function get_total_recorded_for_user( $user_id = null ) {
		global $bp, $wpdb, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
		
		if ( !$user_id )
			$user_id = $current_user->ID;

		return $wpdb->get_var( $wpdb->prepare( "SELECT count(comment_id) FROM {$bp->blogs->table_name_blog_comments} WHERE user_id = %d", $user_id ) );
	}
	
	function total_comment_count( $blog_id, $post_id ) {
		global $bp, $wpdb;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
			
		if ( $post_id )
			$post_sql = $wpdb->prepare( " AND comment_post_id = %d", $post_id );

		return $wpdb->get_var( $wpdb->prepare( "SELECT count(comment_id) WHERE blog_id = %d{$post_sql}", $blog_id ) );
	}
	
	
	function is_recorded( $comment_id, $comment_post_id, $blog_id, $user_id = null ) {
		global $bp, $wpdb, $current_user;
		
		if ( !$bp->blogs )
			bp_blogs_setup_globals();
			
		if ( !$user_id )
			$user_id = $current_user->ID;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT comment_id FROM {$bp->blogs->table_name_blog_comments} WHERE comment_id = %d AND blog_id = %d AND comment_post_id = %d AND user_id = %d", $comment_id, $blog_id, $comment_post_id, $user_id ) );
	}
	
}

?>