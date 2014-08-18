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
	 */
	public function test_update_blog_post_and_new_blog_comment_and_activity_comment_meta() {
		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->create_user();
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
