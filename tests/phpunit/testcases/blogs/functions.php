<?php

/**
 * @group blogs
 */
class BP_Tests_Blogs_Functions extends BP_UnitTestCase {
	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 */
	public function test_bp_blogs_delete_blogmeta_non_numeric_blog_id() {
		$this->assertFalse( bp_blogs_delete_blogmeta( 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 * @ticket BP5399
	 */
	public function test_bp_blogs_delete_blogmeta_illegal_characters() {
		$this->assertNotEmpty( bp_blogs_update_blogmeta( 1, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertFalse( bp_blogs_delete_blogmeta( 1, $krazy_key ) );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 * @ticket BP5399
	 */
	public function test_bp_blogs_delete_blogmeta_trim_meta_value() {
		$this->assertNotEmpty( bp_blogs_update_blogmeta( 1, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		bp_blogs_delete_blogmeta( 1, 'foo', '   bar  ' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 */
	public function test_bp_blogs_delete_blogmeta_no_meta_key() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_update_blogmeta( 1, 'foo2', 'bar2' );
		$this->assertNotEmpty( bp_blogs_get_blogmeta( 1 ) );
		$this->assertTrue( bp_blogs_delete_blogmeta( 1 ) );
		$this->assertSame( array(), bp_blogs_get_blogmeta( 1 ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 */
	public function test_bp_blogs_delete_blogmeta_with_meta_value() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		bp_blogs_delete_blogmeta( 1, 'foo', 'baz' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		$this->assertTrue( bp_blogs_delete_blogmeta( 1, 'foo', 'bar' ) );
		$this->assertSame( '', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 */
	public function test_bp_blogs_delete_blogmeta_with_delete_all_but_no_meta_key() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 1, 'foo1', 'bar1' );
		bp_blogs_add_blogmeta( 2, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 2, 'foo1', 'bar1' );

		$this->assertTrue( bp_blogs_delete_blogmeta( 1, '', '', true ) );
		$this->assertEmpty( bp_blogs_get_blogmeta( 1 ) );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 2, 'foo' ) );
		$this->assertSame( 'bar1', bp_blogs_get_blogmeta( 2, 'foo1' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_delete_blogmeta
	 */
	public function test_bp_blogs_delete_blogmeta_with_delete_all() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 1, 'foo1', 'bar1' );
		bp_blogs_add_blogmeta( 2, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 2, 'foo1', 'bar1' );

		$this->assertTrue( bp_blogs_delete_blogmeta( 1, 'foo', '', true ) );
		$this->assertSame( '', bp_blogs_get_blogmeta( 1, 'foo' ) );
		$this->assertSame( '', bp_blogs_get_blogmeta( 2, 'foo' ) );
		$this->assertSame( 'bar1', bp_blogs_get_blogmeta( 1, 'foo1' ) );
		$this->assertSame( 'bar1', bp_blogs_get_blogmeta( 2, 'foo1' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 */
	public function test_bp_blogs_get_blogmeta_empty_blog_id() {
		$this->assertFalse( bp_blogs_get_blogmeta( 0 ) );
		$this->assertFalse( bp_blogs_get_blogmeta( '' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 * @ticket BP5399
	 */
	public function test_bp_blogs_get_blogmeta_illegal_characters() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( '', bp_blogs_get_blogmeta( 1, $krazy_key ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 */
	public function test_bp_blogs_get_blogmeta_no_meta_key() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_update_blogmeta( 1, 'foo2', 'bar2' );

		$expected = array(
			'foo' => array(
				'bar',
			),
			'foo2' => array(
				'bar2',
			),
		);

		$this->assertSame( $expected, bp_blogs_get_blogmeta( 1 ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 */
	public function test_bp_blogs_get_blogmeta_no_meta_key_empty() {
		$this->assertSame( array(), bp_blogs_get_blogmeta( 1 ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 */
	public function test_bp_blogs_get_blogmeta_single_true() {
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 1, 'foo', 'baz' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) ); // default is true
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo', true ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_get_blogmeta
	 */
	public function test_bp_blogs_get_blogmeta_single_false() {
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( 1, 'foo', 'baz' );
		$this->assertSame( array( 'bar', 'baz' ), bp_blogs_get_blogmeta( 1, 'foo', false ) );
	}
	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_blogmeta_non_numeric_blog_id() {
		$this->assertFalse( bp_blogs_update_blogmeta( 'foo', 'foo', 'bar' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 * @ticket BP5399
	 */
	public function test_bp_blogs_update_blogmeta_illegal_characters() {
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_blogs_update_blogmeta( 1, $krazy_key, 'bar' );
		$this->assertSame( '', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_blogmeta_stripslashes() {
		$slashed = 'This \"string\" is cool';
		bp_blogs_update_blogmeta( 1, 'foo', $slashed );
		$this->assertSame( 'This "string" is cool', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_blogmeta_new() {
		$this->assertNotEmpty( bp_blogs_update_blogmeta( 1, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_blogmeta_existing() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		$this->assertTrue( bp_blogs_update_blogmeta( 1, 'foo', 'baz' ) );
		$this->assertSame( 'baz', bp_blogs_get_blogmeta( 1, 'foo' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_blogmeta_existing_no_change() {
		bp_blogs_update_blogmeta( 1, 'foo', 'bar' );
		$this->assertSame( 'bar', bp_blogs_get_blogmeta( 1, 'foo' ) );
		$this->assertFalse( bp_blogs_update_blogmeta( 1, 'foo', 'bar' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_update_blogmeta
	 */
	public function test_bp_blogs_update_meta_prev_value() {
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );

		// In earlier versions of WordPress, bp_activity_update_meta()
		// returns true even on failure. However, we know that in these
		// cases the update is failing as expected, so we skip this
		// assertion just to keep our tests passing
		// See https://core.trac.wordpress.org/ticket/24933
		if ( version_compare( $GLOBALS['wp_version'], '3.7', '>=' ) ) {
			$this->assertFalse( bp_blogs_update_blogmeta( 1, 'foo', 'bar2', 'baz' ) );
		}

		$this->assertTrue( bp_blogs_update_blogmeta( 1, 'foo', 'bar2', 'bar' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_add_blogmeta
	 */
	public function test_bp_blogs_add_blogmeta_no_meta_key() {
		$this->assertFalse( bp_blogs_add_blogmeta( 1, '', 'bar' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_add_blogmeta
	 */
	public function test_bp_blogs_add_blogmeta_empty_object_id() {
		$this->assertFalse( bp_blogs_add_blogmeta( 0, 'foo', 'bar' ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_add_blogmeta
	 */
	public function test_bp_blogs_add_blogmeta_existing_unique() {
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		$this->assertFalse( bp_blogs_add_blogmeta( 1, 'foo', 'baz', true ) );
	}

	/**
	 * @group blogmeta
	 * @group bp_blogs_add_blogmeta
	 */
	public function test_bp_blogs_add_blogmeta_existing_not_unique() {
		bp_blogs_add_blogmeta( 1, 'foo', 'bar' );
		$this->assertNotEmpty( bp_blogs_add_blogmeta( 1, 'foo', 'baz' ) );
	}

	/**
	 * @group bp_blogs_restore_data
	 */
	public function test_bp_blogs_restore_data() {
		if ( ! is_multisite() ) {
			return;
		}

		// Create a regular member
		$u = $this->factory->user->create();

		// Create blogs
		$b1 = $this->factory->blog->create( array( 'user_id' => $u ) );
		$b2 = $this->factory->blog->create( array( 'user_id' => $u ) );

		$expected = array(
			$b1 => $b1,
			$b2 => $b2
		);

		// Mark the user as spam
		bp_core_process_spammer_status( $u, 'spam' );

		// get all blogs for user
		$blogs = bp_blogs_get_blogs_for_user( $u, true );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertNotEquals( $expected, array_map( 'intval', $blog_ids ), 'User marked as spam should not have any blog registered' );

		// Ham the user
		bp_core_process_spammer_status( $u, 'ham' );

		// get all blogs for user
		$blogs = bp_blogs_get_blogs_for_user( $u, true );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( $expected, array_map( 'intval', $blog_ids ) );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_publish_to_publish() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		// 'publish' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		$post->post_status = 'publish';
		$post->post_content .= ' foo';

		wp_update_post( $post );

		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity (no change)' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_password_publish() {
		$post_id = $this->factory->post->create( array(
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_password' => 'pass',
		) );
		$post = get_post( $post_id );

		// 'new' => 'publish with password'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Published with password post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_publish_update_password() {
		$post_id = $this->factory->post->create( array(
			'post_status'   => 'publish',
			'post_type'     => 'post',
		) );
		$post = get_post( $post_id );

		// 'publish' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		$post->post_content .= ' foo';
		$post->post_password = 'pass';

		wp_update_post( $post );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Updated with password post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_private_publish() {
		$post_id = $this->factory->post->create( array(
			'post_status'   => 'private',
			'post_type'     => 'post',
		) );
		$post = get_post( $post_id );

		// 'new' => 'private'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Private post should not have activity' );

		$post->post_status = 'publish';

		wp_update_post( $post );

		// 'private' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_publish_private() {
		$post_id = $this->factory->post->create( array(
			'post_status'   => 'publish',
			'post_type'     => 'post',
		) );
		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		$post->post_status = 'private';

		wp_update_post( $post );

		// 'publish' => 'private'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Private post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_draft_to_draft() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'draft',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity' );

		$post->post_status = 'draft';
		$post->post_content .= ' foo';

		wp_update_post( $post );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity (no change)' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_draft_to_publish() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'draft',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity' );

		$post->post_status = 'publish';
		$post->post_content .= ' foo';

		wp_update_post( $post );

		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_publish_to_draft() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		$post->post_status = 'draft';
		$post->post_content .= ' foo';

		wp_update_post( $post );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_wp_delete_post() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		wp_delete_post( $post->ID );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_transition_post_status_wp_trash_post() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
		) );
		$post = get_post( $post_id );

		$this->assertTrue( $this->activity_exists_for_post( $post_id ), 'Published post should have activity' );

		wp_trash_post( $post->ID );

		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Unpublished post should not have activity' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 * @group post_type_comment_activities
	 */
	public function test_update_blog_post_and_new_blog_comment_and_activity_comment_meta() {
		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// create the blog post
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_title' => 'First title',
		) );

		// remove comment flood protection temporarily
		add_filter( 'comment_flood_filter', '__return_false' );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );
		$c1 = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a blog comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// let's also add a "new_blog_comment" activity entry
		$c2 = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is another blog comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );

		// bring back flood protection
		remove_filter( 'comment_flood_filter', '__return_false' );

		// update the initial blog post
		wp_update_post( array(
			'ID'        => $post_id,
			'post_title' => 'Second title',
		) );

		// grab the activity ID for the activity comment
		$a1 = bp_activity_get_activity_id( array(
			'type'              => 'activity_comment',
			'display_comments'  => 'stream',
			'meta_query'        => array( array(
				'key'     => 'bp_blogs_post_comment_id',
				'value'   => $c1,
			) )
		) );

		// grab the activity ID for the blog comment
		$a2 = bp_activity_get_activity_id( array(
			'component'         => buddypress()->blogs->id,
			'type'              => 'new_blog_comment',
			'secondary_item_id' => $c2,
		) );

		// see if blog comment activity meta matches the post items
		$this->assertEquals( 'Second title', bp_activity_get_meta( $a1, 'post_title' ) );
		$this->assertEquals( add_query_arg( 'p', $post_id, home_url( '/' ) ), bp_activity_get_meta( $a1, 'post_url' ) );

		$this->assertEquals( 'Second title', bp_activity_get_meta( $a2, 'post_title' ) );
		$this->assertEquals( add_query_arg( 'p', $post_id, home_url( '/' ) ), bp_activity_get_meta( $a2, 'post_url' ) );

		// reset
		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_transition_activity_status
	 * @group bp_blogs_post_type_remove_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_remove_comment_should_remove_spammed_activity_comment() {
		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// create the blog post
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_title' => 'First title',
		) );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );
		$c1 = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a blog comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );

		// save the corresponding activity comment ID
		$a1 = bp_activity_get_activity_id( array(
			'type'              => 'activity_comment',
			'display_comments'  => 'stream',
			'meta_query'        => array( array(
				'key'     => 'bp_blogs_post_comment_id',
				'value'   => $c1,
			) )
		) );

		// trash the parent comment.
		// corresponding activity comment should now be marked as spam
		// @see bp_blogs_transition_activity_status()
		wp_trash_comment( $c1 );

		// now permanently delete the comment
		wp_delete_comment( $c1, true );

		// activity comment should no longer exist
		$a = bp_activity_get( array(
			'in'               => $a1,
			'display_comments' => 'stream',
			'spam'             => 'all'
		) );
		// this is a convoluted way of testing if the activity comment still exists
		$this->assertTrue( empty( $a['activities'][0] ) );

		// reset
		$this->set_current_user( $old_user );
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
	}

	/**
	 * @group bp_blogs_post_type_remove_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_post_type_remove_comment() {
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );

		// create the blog post
		$p = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_title' => 'First title',
		) );

		$c = wp_new_comment( array(
			'comment_post_ID'      => $p,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this comment will be removed',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );

		// An activity should exist
		$a = bp_activity_get_activity_id( array(
			'user_id' => $u,
			'type'    => 'new_blog_comment'
		) );

		// now permanently delete the comment
		wp_delete_comment( $c, true );

		// The activity comment should no longer exist
		$ac = bp_activity_get( array( 'in' => $a ) );
		$this->assertTrue( empty( $ac['activities'] ) );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_bp_blogs_is_blog_trackable_false_publish_post() {
		add_filter( 'bp_blogs_is_blog_trackable', '__return_false' );

		$post_id = $this->factory->post->create( array(
			'post_status'   => 'publish',
			'post_type'     => 'post',
		) );
		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Not trackable blog post should not have activity' );

		$post->post_content .= ' foo';

		wp_update_post( $post );

		// 'publish' => 'publish'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Not trackable blog post should not have activity' );

		remove_filter( 'bp_blogs_is_blog_trackable', '__return_false' );
	}

	/**
	 * @group bp_blogs_catch_transition_post_status
	 */
	public function test_bp_is_blog_public_zero_publish_post() {
		if ( ! is_multisite() ) {
			return;
		}

		add_filter( 'bp_is_blog_public', '__return_zero' );

		$post_id = $this->factory->post->create( array(
			'post_status'   => 'publish',
			'post_type'     => 'post',
		) );
		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Not public blog post should not have activity' );

		$post->post_content .= ' foo';

		wp_update_post( $post );

		// 'publish' => 'publish'
		$this->assertFalse( $this->activity_exists_for_post( $post_id ), 'Not public blog post should not have activity' );

		remove_filter( 'bp_is_blog_public', '__return_zero' );
	}

	/**
	 * @group bp_blogs_record_comment
	 * @group unique
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_record_comment_no_duplicate_activity_comments() {
		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$userdata = get_userdata( $u );
		$this->activity_saved_comment_count = 0;
		$this->comment_saved_count = 0;

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );
		add_action( 'bp_activity_add', array( $this, 'count_activity_comment_saved' ) );
		add_action( 'wp_insert_comment', array( $this, 'count_post_comment_saved' ) );
		add_action( 'edit_comment', array( $this, 'count_post_comment_saved' ) );

		// create the blog post
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test Duplicate activity comments',
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
			'content'     => 'activity comment should be unique',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$activities = bp_activity_get( array(
			'type'             => 'activity_comment',
			'display_comments' => 'stream',
			'search_terms'     => 'activity comment should be unique',
		) );

		$this->assertTrue( count( $activities['activities'] ) === 1, 'An activity comment should be unique' );

		$this->assertTrue( 2 === $this->activity_saved_comment_count, 'An activity comment should be saved only twice' );
		$this->assertTrue( 1 === $this->comment_saved_count, 'A comment should be saved only once' );

		// reset
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
		remove_action( 'bp_activity_add', array( $this, 'count_activity_comment_saved' ) );
		remove_action( 'wp_insert_comment', array( $this, 'count_post_comment_saved' ) );
		remove_action( 'edit_comment', array( $this, 'count_post_comment_saved' ) );

		$this->activity_saved_comment_count = 0;
		$this->comment_saved_count = 0;
		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_record_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_record_comment_should_record_parent_blog_post_activity_if_not_found() {
		// Save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Get user details
		$user = get_userdata( $u );

		// Let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// Create the blog post
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
		) );

		// Now, delete the activity item for the blog post
		bp_activity_delete( array(
			'component'         => buddypress()->blogs->id,
			'type'              => 'new_blog_post',
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		// Add a comment to blog post
		wp_new_comment( array(
			'comment_post_ID' => $post_id,
			'user_id' => $u,
			'comment_content' => 'Dummy comment',
			'comment_author' => 'Dumbo',
			'comment_author_url' => 'http://buddypress.org',

			// Important to pass check in bp_blogs_record_comment()
			'comment_author_email' => $user->user_email
		) );

		// Fetch the activity ID for the blog post to see if it exists
		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_blog_post',
			'component' => buddypress()->blogs->id,
			'filter'    => array(
				'item_id' => get_current_blog_id(),
				'secondary_item_id' => $post_id
			),
		) );

		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// Assert that activity item for blog post was created after adding a comment
		$this->assertNotNull( $a1, 'Activity item was not created for existing blog post when recording post comment.' );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_comment_sync_activity_comment
	 * @group post_type_comment_activities
	 */
	public function test_bp_blogs_comment_sync_activity_comment_for_custom_post_type() {
		if ( is_multisite() ) {
			$b = $this->factory->blog->create();
			switch_to_blog( $b );
			add_filter( 'comment_flood_filter', '__return_false' );
		} else {
			$b = get_current_blog_id();
		}

		$u = $this->factory->user->create();
		$userdata = get_userdata( $u );

		$labels = array(
			'name'                       => 'bars',
			'singular_name'              => 'bar',
		);

		register_post_type( 'foo', array(
			'labels'   => $labels,
			'public'   => true,
			'supports' => array( 'comments' ),
		) );

		add_post_type_support( 'foo', 'buddypress-activity' );

		bp_activity_set_post_type_tracking_args( 'foo', array(
			'comment_action_id' => 'new_foo_comment',
		) );

		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$p = $this->factory->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$a1 = bp_activity_get_activity_id( array(
			'type'      => 'new_foo',
			'filter'    => array(
				'item_id' => $b,
				'secondary_item_id' => $p
			),
		) );

		$c = wp_new_comment( array(
			'comment_post_ID'      => $p,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a foo comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );

		$a2 = bp_activity_new_comment( array(
			'content'     => 'this should generate a new foo comment',
			'user_id'     => $u,
			'activity_id' => $a1,
		) );

		$activity_args = array(
			'type'              => 'activity_comment',
			'display_comments'  => 'stream',
			'meta_query'        => array( array(
				'key'       => 'bp_blogs_foo_comment_id',
				'compare'   => 'exists',
			) )
		);

		$a = bp_activity_get( $activity_args );
		$aids = wp_list_pluck( $a['activities'], 'id' );
		$cids = wp_list_pluck( get_approved_comments( $p ), 'comment_ID' );

		foreach ( $aids as $aid ) {
			$this->assertTrue( in_array( bp_activity_get_meta( $aid, 'bp_blogs_foo_comment_id' ), $cids ), 'The comment ID should be in the activity meta' );
		}

		foreach ( $cids as $cid ) {
			$this->assertTrue( in_array( get_comment_meta( $cid, 'bp_activity_comment_id', true ), $aids ), 'The activity ID should be in the comment meta' );
		}

		_unregister_post_type( 'foo' );

		if ( is_multisite() ) {
			restore_current_blog();
			remove_filter( 'comment_flood_filter', '__return_false' );
		}

		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
	}

	public function count_activity_comment_saved() {
		$this->activity_saved_comment_count += 1;
	}

	public function count_post_comment_saved() {
		$this->comment_saved_count += 1;
	}

	/**
	 * @group bp_blogs_record_existing_blogs
	 */
	public function test_bp_blogs_record_existing_blogs_limit() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Create three sites.
		$this->factory->blog->create_many( 3, array(
			'user_id' => $u
		) );

		// Record each site one at a time
		bp_blogs_record_existing_blogs( array(
			'limit' => 1
		) );

		// Assert!
		$blogs = bp_blogs_get_blogs( array(
			'user_id' => $u
		) );
		$this->assertSame( 3, (int) $blogs['total'] );

		$this->set_current_user( $old_user );
	}

	protected function activity_exists_for_post( $post_id ) {
		$a = bp_activity_get( array(
			'component' => buddypress()->blogs->id,
			'action' => 'new_blog_post',
			'item_id' => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		return ! empty( $a['activities'] );
	}
}
