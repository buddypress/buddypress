<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Template extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	/**
	 * @ticket BP4735
	 */
	public function test_user_can_delete() {
		global $bp;

		$a = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		// User can delete his own items
		$activity = $this->factory->activity->get_object_by_id( $a );
		$this->assertTrue( bp_activity_user_can_delete( $activity ) );

		// Stash original user
		$original_user = get_current_user_id();

		// Logged-out user can't delete
		$this->set_current_user( 0 );
		$this->assertFalse( bp_activity_user_can_delete( $activity ) );

		// Miscellaneous user can't delete
		$misc_user = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->set_current_user( $misc_user );
		$this->assertFalse( bp_activity_user_can_delete( $activity ) );

		// Item admin can delete
		$is_single_item = $bp->is_single_item;
		$bp->is_single_item = true;

		$is_item_admin = $bp->is_item_admin;
		$bp->is_item_admin = true;

		$this->assertTrue( bp_activity_user_can_delete( $activity ) );

		$bp->is_single_item = $is_single_item;
		$bp->is_item_admin = $is_item_admin;
		$this->set_current_user( $original_user );
	}

	/**
	 * Test if a non-admin can delete their own activity.
	 */
	public function test_user_can_delete_for_nonadmin() {
		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$u = $this->create_user();
		$this->set_current_user( $u );

		// create an activity update for the user
		$this->factory->activity->create( array(
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'user_id' => $u,
		) );

		// start the activity loop
		bp_has_activities( array( 'user_id' => $u ) );
		while ( bp_activities() ) : bp_the_activity();
			// assert!
			$this->assertTrue( bp_activity_user_can_delete() );
		endwhile;

		// reset
		$this->set_current_user( $old_user );
	}

	/**
	 * Make sure that action filters ('activity_update', etc) work when
	 * limiting query to user favorites
	 *
	 * @ticket BP4872
	 */
	public function test_bp_has_activities_favorites_action_filter() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$now = time();

		$a1 = $this->factory->activity->create( array(
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		$a2 = $this->factory->activity->create( array(
			'type' => 'joined_group',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		bp_activity_add_user_favorite( $a1, $user_id );
		bp_activity_add_user_favorite( $a2, $user_id );

		// groan. It sucks that you have to invoke the global
		global $activities_template;

		// Case 1: no action filter
		bp_has_activities( array(
			'user_id' => $user_id,
			'scope' => 'favorites',
		) );

		// The formatting of $activities_template->activities is messed
		// up, so we're just going to look at the IDs. This should be
		// fixed in BP at some point
		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1, $a2 ), $ids );

		$activities_template = null;

		// Case 2: action filter
		bp_has_activities( array(
			'user_id' => $user_id,
			'scope' => 'favorites',
			'action' => 'activity_update',
		) );

		global $wpdb, $bp;

		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1 ), $ids );

		$activities_template = null;
	}

	/**
	 * Integration test for 'meta_query' param
	 */
	function test_bp_has_activities_with_meta_query() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		bp_activity_update_meta( $a1, 'foo', 'bar' );

		global $activities_template;
		bp_has_activities( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$ids = wp_list_pluck( $activities_template->activities, 'id' );
		$this->assertEquals( $ids, array( $a1 ) );
	}

	/**
	 * @ticket BP5029
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_with_display_comments_false() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		global $activities_template;
		bp_has_activities( array(
			'display_comments' => false,
		) );
		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1, $a2 ), wp_parse_id_list( $ids ) );

	}

	/**
	 * @ticket BP5029
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_with_display_comments_0() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		global $activities_template;
		bp_has_activities( array(
			'display_comments' => 0,
		) );
		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1, $a2 ), wp_parse_id_list( $ids ) );

	}

	/**
	 * @ticket BP5029
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_with_display_comments_0_querystring() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		global $activities_template;
		bp_has_activities( 'display_comments=0' );
		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1, $a2 ), $ids );

	}

	/**
	 * @ticket BP5029
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_with_display_comments_none_querystring() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		global $activities_template;
		bp_has_activities( 'display_comments=none' );
		$ids = wp_list_pluck( $activities_template->activities, 'id' );

		$this->assertEquals( array( $a1, $a2 ), $ids );

	}

	/**
	 * @group bp_has_activities
	 * @group cache
	 */
	public function test_bp_has_activities_with_update_meta_cache_false() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		bp_activity_add_meta( $a1, 'foo', 'bar' );
		bp_activity_add_meta( $a2, 'foo1', 'bar2' );

		// prime
		bp_has_activities( array(
			'update_meta_cache' => false,
		) );

		$this->assertFalse( wp_cache_get( $a1, 'activity_meta' ) );
		$this->assertFalse( wp_cache_get( $a2, 'activity_meta' ) );
	}

	/**
	 * @group bp_has_activities
	 * @group cache
	 */
	public function test_bp_has_activities_with_update_meta_cache_true() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		bp_activity_add_meta( $a1, 'foo', 'bar' );
		bp_activity_add_meta( $a2, 'foo1', 'bar2' );

		// prime
		bp_has_activities( array(
			'update_meta_cache' => true,
		) );

		$this->assertNotEmpty( wp_cache_get( $a1, 'activity_meta' ) );
		$this->assertNotEmpty( wp_cache_get( $a2, 'activity_meta' ) );
	}

	/**
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_with_type_new_blog_comments() {
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'component' => 'blogs',
			'type' => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'component' => 'blogs',
			'type' => 'new_blog_comment',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// This one will show up in the stream because it's a comment
		// on a blog post
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 200 ),
		) );

		$a4 = $this->factory->activity->create( array(
			'content' => 'Life Rulez',
			'component' => 'activity',
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );

		// This one should not show up in the stream because it's a
		// comment on an activity item
		$a5 = bp_activity_new_comment( array(
			'activity_id' => $a4,
			'content' => 'Candy is great',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 400 ),
		) );
		global $activities_template;

		// prime
		bp_has_activities( array(
			'component' => 'blogs',
			'action' => 'new_blog_comment',
		) );

		$this->assertEquals( array( $a3, $a2 ), wp_parse_id_list( wp_list_pluck( $activities_template->activities, 'id' ) ) );

		// Clean up
		$activities_template = null;
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
	}

	/**
	 * @group bp_activity_can_comment_reply
	 */
	public function test_bp_activity_can_comment_reply_thread_comments_on() {
		$tc = get_option( 'thread_comments' );
		update_option( 'thread_comments', '1' );

		$tcd = get_option( 'thread_comments_depth' );
		update_option( 'thread_comments_depth', '4' );

		// Fake the global
		global $activities_template;
		$activities_template = new stdClass;
		$activities_template->activity = new stdClass;
		$activities_template->activity->current_comment = new stdClass;

		$comment = new stdClass;
		$comment->item_id = 4;

		$activities_template->activity->current_comment->depth = 1;
		$this->assertTrue( bp_activity_can_comment_reply( $comment ) );

		$activities_template->activity->current_comment->depth = 3;
		$this->assertTrue( bp_activity_can_comment_reply( $comment ) );

		$activities_template->activity->current_comment->depth = 4;
		$this->assertFalse( bp_activity_can_comment_reply( $comment ) );

		$activities_template->activity->current_comment->depth = 5;
		$this->assertFalse( bp_activity_can_comment_reply( $comment ) );

		// Set right what once went wrong
		update_option( 'thread_comments', $tc );
		update_option( 'thread_comments_depth', $tcd );
		$activities_template = null;
	}

	/**
	 * @group bp_activity_can_comment_reply
	 */
	public function test_bp_activity_can_comment_reply_thread_comments_off() {
		$tc = get_option( 'thread_comments' );
		update_option( 'thread_comments', '0' );

		$tcd = get_option( 'thread_comments_depth' );
		update_option( 'thread_comments_depth', '4' );

		// Fake the global
		global $activities_template;
		$activities_template = new stdClass;
		$activities_template->activity = new stdClass;
		$activities_template->activity->current_comment = new stdClass;

		$comment = new stdClass;
		$comment->item_id = 4;

		$activities_template->activity->current_comment->depth = 1;
		$this->assertFalse( bp_activity_can_comment_reply( $comment ) );

		$activities_template->activity->current_comment->depth = 2;
		$this->assertFalse( bp_activity_can_comment_reply( $comment ) );

		// Set right what once went wrong
		update_option( 'thread_comments', $tc );
		update_option( 'thread_comments_depth', $tcd );
		$activities_template = null;
	}

	/**
	 * @group bp_activity_has_more_items
	 */
	public function test_bp_activity_has_more_items_no_count_total_false() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		$args = array(
			'count_total' => false,
		);

		if ( bp_has_activities( $args ) ) {
			global $activities_template;
			$this->assertFalse( bp_activity_has_more_items() );
			$activities_template = null;
		}
	}

	/**
	 * @group bp_activity_has_more_items
	 */
	public function test_bp_activity_has_more_items_no_count_total_true() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		$a3 = $this->factory->activity->create();
		$a4 = $this->factory->activity->create();

		$args = array(
			'count_total' => false,
			'per_page' => 2,
		);

		if ( bp_has_activities( $args ) ) {
			global $activities_template;
			$this->assertTrue( bp_activity_has_more_items() );
			$activities_template = null;
		}
	}

	/**
	 * @group bp_activity_has_more_items
	 */
	public function test_bp_activity_has_more_items_count_total_false() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		$a3 = $this->factory->activity->create();
		$a4 = $this->factory->activity->create();

		$args = array(
			'count_total' => 'count_query',
		);

		if ( bp_has_activities( $args ) ) {
			global $activities_template;
			$this->assertFalse( bp_activity_has_more_items() );
			$activities_template = null;
		}
	}

	/**
	 * @group bp_activity_has_more_items
	 */
	public function test_bp_activity_has_more_items_count_total_true() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		$a3 = $this->factory->activity->create();
		$a4 = $this->factory->activity->create();

		$args = array(
			'count_total' => 'count_query',
			'per_page' => 2,
		);

		if ( bp_has_activities( $args ) ) {
			global $activities_template;
			$this->assertTrue( bp_activity_has_more_items() );
			$activities_template = null;
		}
	}

	/**
	 * Integration test for 'date_query' param
	 *
	 * @group date_query
	 * @requires PHP 5.3
	 */
	function test_bp_has_activities_with_date_query() {
		if ( ! class_exists( 'WP_Date_Query' ) ) {
			return;
		}

		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => '2001-01-01 12:00'
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => '2005-01-01 12:00'
		) );

		global $activities_template;
		bp_has_activities( array(
			'date_query' => array( array(
				'after' => '1 day ago'
			) )
		) );

		$ids = wp_list_pluck( $activities_template->activities, 'id' );
		$this->assertEquals( $ids, array( $a1 ) );
	}
}
