<?php

/**
 * @group blogs
 * @group activity
 */
class BP_Tests_Blogs_Activity extends BP_UnitTestCase {
	/**
	 * @group bp_blogs_register_activity_actions
	 * @group activity_tracking
	 */
	public function test_bp_blogs_loader_post_tracking_args_filter() {
		$bp = buddypress();

		$expected = array( 'new_blog_post', 'new_blog_comment' );

		if ( is_multisite() ) {
			$expected = array_merge( array( 'new_blog' ), $expected );
		}

		$actions = bp_activity_get_actions();
		$actions = array_keys( (array) $actions->blogs );

		$this->assertEquals( $expected, $actions );
	}

	/**
	 * @group activity_action
	 * @group bp_blogs_format_activity_action_new_blog
	 */
	public function test_bp_blogs_format_activity_action_new_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->blogs->id,
			'type' => 'new_blog',
			'user_id' => $u,
			'item_id' => $b,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$expected = sprintf( '%s created the site %s', bp_core_get_userlink( $u ), '<a href="' . get_blog_option( $b, 'home' ) . '">' . get_blog_option( $b, 'blogname' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_blogs_format_activity_action_new_blog_post
	 */
	public function test_bp_blogs_format_activity_action_new_blog_post_nonms() {
		if ( is_multisite() ) {
			$this->markTestSkipped();
		}

		$u = self::factory()->user->create();
		$p = self::factory()->post->create( array(
			'post_author' => $u,
		) );
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->blogs->id,
			'type' => 'new_blog_post',
			'user_id' => $u,
			'item_id' => 1,
			'secondary_item_id' => $p,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$expected = sprintf( '%s wrote a new post', $user_link );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_blogs_format_activity_action_new_blog_post
	 */
	public function test_bp_blogs_format_activity_action_new_blog_post_ms_rootblog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$u = self::factory()->user->create();
		$p = self::factory()->post->create( array(
			'post_author' => $u,
		) );
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->blogs->id,
			'type' => 'new_blog_post',
			'user_id' => $u,
			'item_id' => 1,
			'secondary_item_id' => $p,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_home_url();

		$expected = sprintf( '%s wrote a new post on the site %s', $user_link, '<a href="' . $blog_url . '">' . bp_blogs_get_blogmeta( $a_obj->item_id, 'name' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_blogs_format_activity_action_new_blog_post
	 */
	public function test_bp_blogs_format_activity_action_new_blog_post_ms_nonrootblog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		switch_to_blog( $b );
		$p = self::factory()->post->create( array(
			'post_author' => $u,
		) );
		$p_obj = get_post( $p );
		restore_current_blog();

		$a = self::factory()->activity->create( array(
			'component' => buddypress()->blogs->id,
			'type' => 'new_blog_post',
			'user_id' => $u,
			'item_id' => $b,
			'secondary_item_id' => $p,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_blog_option( $a_obj->item_id, 'home' );

		$expected = sprintf( '%s wrote a new post on the site %s', $user_link, '<a href="' . $blog_url . '">' . get_blog_option( $a_obj->item_id, 'blogname' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_blogs_format_activity_action_new_blog_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_format_activity_action_new_blog_comment_ms_nonrootblog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		switch_to_blog( $b );
		$p = self::factory()->post->create( array(
			'post_author' => $u,
		) );
		$p_obj = get_post( $p );
		$c = self::factory()->comment->create( array(
			'comment_post_ID' => $p,
		) );
		$c_obj = get_comment( $c );
		restore_current_blog();

		$a = self::factory()->activity->create( array(
			'component' => buddypress()->blogs->id,
			'type' => 'new_blog_comment',
			'user_id' => $u,
			'item_id' => $b,
			'secondary_item_id' => $c,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_blog_option( $a_obj->item_id, 'home' );
		$post_url = add_query_arg( 'p', $p, trailingslashit( $blog_url ) );
		$post_title = $p_obj->post_title;
		$post_link = '<a href="' . $post_url . '">' . $post_title . '</a>';

		$expected = sprintf( '%s commented on the post, %s, on the site %s', $user_link, $post_link, '<a href="' . $blog_url . '">' . get_blog_option( $a_obj->item_id, 'blogname' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_update_post_title_activity_meta_should_not_be_the_same_for_same_comment_id() {
		global $wpdb;

		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$b1 = self::factory()->blog->create();
		$b2 = self::factory()->blog->create();
		$b3 = self::factory()->blog->create();
		$u = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$commenter = self::factory()->user->get_object_by_id( $u2 );

		$bids = array( $b1, $b2, $b3 );
		$pids = array();

		foreach ( $bids as $bid ) {
			switch_to_blog( $bid );

			// Ensure blog privacy is public so post activities are recorded.
			update_option( 'blog_public', 1 );

			// Create the post.
			$pids[ $bid ] = self::factory()->post->create( array(
				'post_author' => $u,
			) );

			/*
			 * Create the post comment.
			 *
			 * Both user_id and comment_author_email are required for unit tests.
			 */
			$c = self::factory()->comment->create( array(
				'user_id'         => $u2,
				'comment_author_email' => $commenter->user_email,
				'comment_post_ID' => $pids[ $bid ],
			) );

			// Approve the comment so the activity item is generated.
			self::factory()->comment->update_object( $c, array( 'comment_approved' => 1 ) );

			restore_current_blog();
		}

		// Now update the post title on one blog only.
		switch_to_blog( $b1 );
		wp_update_post( array(
			'ID' => $pids[ $b1 ],
			'post_title' => 'Updated'
		) );
		restore_current_blog();

		// Check our activity meta to see if the post title is different.
		$aids = bp_activity_get( array(
			'fields' => 'ids',
			'filter' => array(
				'action' => 'new_blog_comment'
			)
		) );
		$aids= $aids['activities'];
		foreach ( $aids as $aid ) {
			// Skip the check for the correct blog.
			$a = new BP_Activity_Activity( $aid );
			if ( $a->item_id == $b1 ) {
				continue;
			}

			// Assert that post title is different.
			$post_title = bp_activity_get_meta( $aid, 'post_title' );
			$this->assertFalse( 'Updated' === $post_title, 'Post title should not be the same across all sites with the same post comment ID' );
		}
	}

	/**
	 * @ticket BP6126
	 */
	public function test_check_activity_actions_are_set_when_creating_activity_object() {
		$bp = buddypress();

		if ( isset( $bp->activity->actions ) ) {
			unset( $bp->activity->actions );
		}

		$u = self::factory()->user->create();
		$p = self::factory()->post->create( array( 'post_author' => $u, ) );
		$a = self::factory()->activity->create( array(
			'component'         => buddypress()->blogs->id,
			'item_id'           => 1,
			'secondary_item_id' => $p,
			'type'              => 'new_blog_post',
			'user_id'           => $u,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$this->assertTrue( ! empty( $a_obj->action ) );
	}

	/**
	 * @group bp_blogs_sync_add_from_activity_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_sync_add_from_activity_comment() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'this content shoud be in a new post comment',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$approved_comments = get_approved_comments( $post_id );
		$comment = reset( $approved_comments );

		$this->assertTrue( (int) $comment->comment_ID === (int) bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' ), 'The comment ID should be in the activity meta' );
		$this->assertTrue( (int) $a2 === (int) get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true ), 'The activity ID should be in the comment meta' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_sync_delete_from_activity_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_sync_delete_from_activity_comment() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'the generated comment should be deleted once the activity comment is removed',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		bp_activity_delete_comment( $a1, $a2 );

		$post_comments = get_comments( array( 'post_id' => $post_id ) );

		$this->assertEmpty( $post_comments, 'A post comment should be deleted when the corresponding activity is' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_sync_activity_edit_to_post_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_sync_activity_edit_to_post_comment_spam_unspam_activity_comment() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'the generated comment should be spamed/unspamed once the activity comment is spamed/unspamed',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$activity = new BP_Activity_Activity( $a2 );

		bp_activity_mark_as_spam( $activity );
		$activity->save();

		$post_comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );

		$this->assertEmpty( $post_comments, 'A post comment should be spammed when the corresponding activity is spammed' );

		bp_activity_mark_as_ham( $activity );
		$activity->save();

		$post_comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		$comment = reset( $post_comments );

		$this->assertTrue( (int) $comment->comment_ID === (int) bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' ), 'The comment ID should be in the activity meta' );
		$this->assertTrue( (int) $a2 === (int) get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true ), 'The activity ID should be in the comment meta' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_sync_activity_edit_to_post_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_sync_activity_edit_to_post_comment_spam_activity_comment_unspam_post_comment() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'the generated comment should be spamed/unspamed once the activity comment is spamed/unspamed',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$c = bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' );

		$activity = new BP_Activity_Activity( $a2 );

		bp_activity_mark_as_spam( $activity );
		$activity->save();

		wp_unspam_comment( $c );

		$post_comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		$comment = reset( $post_comments );

		$this->assertTrue( (int) $comment->comment_ID === (int) bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' ), 'The comment ID should be in the activity meta' );
		$this->assertTrue( (int) $a2 === (int) get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true ), 'The activity ID should be in the comment meta' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_sync_activity_edit_to_post_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_sync_activity_edit_to_post_comment_trash_comment_ham_activity() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'the generated comment should be spamed/unspamed once the activity comment is spamed/unspamed',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$c = bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' );

		wp_trash_comment( $c );

		$activity = new BP_Activity_Activity( $a2 );

		bp_activity_mark_as_ham( $activity );
		$activity->save();

		$post_comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		$comment = reset( $post_comments );

		$this->assertTrue( (int) $comment->comment_ID === (int) bp_activity_get_meta( $a2, 'bp_blogs_post_comment_id' ), 'The comment ID should be in the activity meta' );
		$this->assertTrue( (int) $a2 === (int) get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true ), 'The activity ID should be in the comment meta' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_sync_activity_edit_to_post_comment
	 * @group post_type_comment_activities
	 */
	public function test_spammed_activity_comment_should_not_create_post_comment() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// let's use activity comments instead of single "new_blog_comment" activity items.
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// create the blog post.
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test activity comment to post comment',
		) );

		// Grab the activity ID for the activity comment.
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		// Set activity item to spam.
		add_action( 'bp_activity_before_save', array( $this, 'set_activity_to_spam' ) );

		// Create spammed activity comment.
		$a2 = bp_activity_new_comment( array(
			'content'     => 'this activity comment shoud not be created as a new post comment. yolo.',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		// Grab post comments.
		$approved_comments = get_approved_comments( $post_id );
		$comment = reset( $approved_comments );

		// Assert that post comment wasn't created.
		$this->assertEmpty( $comment );

		// Reset.
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
		remove_action( 'bp_activity_before_save', array( $this, 'set_activity_to_spam' ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * Dopey passthrough method so we can check that the correct values
	 * are being passed to the filter
	 */
	public function created_blog_passthrough( $a, $b ) {
		$this->userblog_id = isset( $b->id ) ? $b->id : '';
		return $a;
	}

	/**
	 * Dopey passthrough method so we can check that the correct values
	 * are being passed to the filter
	 */
	public function new_post_passthrough( $a, $b ) {
		$this->post_id = isset( $b->ID ) ? $b->ID : '';
		return $a;
	}

	/**
	 * Dopey passthrough method so we can check that the correct values
	 * are being passed to the filter
	 */
	public function new_comment_passthrough( $a, $b ) {
		$this->comment_post_id = isset( $b->comment_post_ID ) ? $b->comment_post_ID : '';
		return $a;
	}

	/**
	 * Explicitly set activity to spam.
	 */
	public function set_activity_to_spam( $activity ) {
		$activity->is_spam = 1;
	}
}
