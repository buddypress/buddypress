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
		$u = $this->factory->user->create();
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
	 * @group scope
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
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_just_me_scope_with_no_user_id() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// save the current user and override logged-in user
		$old_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time();

		// activity item
		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// misc activity items

		$this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// grab just-me scope with no user ID
		// user ID should fallback to logged-in user ID
		bp_has_activities( array(
			'user_id' => false,
			'scope' => 'just-me',
		) );

		// assert!
		$this->assertEqualSets( array( $a1 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
		$this->set_current_user( $old_user );
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_mentions_scope() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$now = time();

		// mentioned activity item
		$mention_username = '@' . bp_activity_get_user_mentionname( $u1 );
		$a1 = $this->factory->activity->create( array(
			'user_id' => $u2,
			'type'    => 'activity_update',
			'content' => "{$mention_username} - You rule, dude!",
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		// misc activity items
		$this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// grab activities from multiple scopes
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'mentions',
		) );

		// assert!
		$this->assertEqualSets( array( $a1 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_friends_and_mentions_scope() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		// user 1 becomes friends with user 2
		friends_add_friend( $u1, $u2, true );

		$now = time();

		// friend status update
		$a1 = $this->factory->activity->create( array(
			'user_id' => $u2,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		// mentioned item by non-friend
		$mention_username = '@' . bp_activity_get_user_mentionname( $u1 );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'activity',
			'type'      => 'activity_update',
			'content'   => "{$mention_username} - Oy!",
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// misc activity items
		$this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// grab activities from multiple scopes
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'mentions,friends',
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_groups_and_friends_scope() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		// user 1 becomes friends with user 2
		friends_add_friend( $u1, $u2, true );

		// user 1 joins a group
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		$now = time();

		// friend status update
		$a1 = $this->factory->activity->create( array(
			'user_id' => $u2,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		// group activity
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'groups',
			'item_id'   => $g1,
			'type'      => 'joined_group',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// misc activity items
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// grab activities from multiple scopes
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'groups,friends',
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_scope_friends_no_items() {
		$u1 = $this->factory->user->create();

		$now = time();

		// Create a random activity
		$this->factory->activity->create( array(
			'user_id' => $u1,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		global $activities_template;
		$reset_activities_template = $activities_template;

		// grab activities from friends scope
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'friends',
		) );

		// assert!
		$this->assertEmpty( $activities_template->activities, 'When a user does not have any friendship, no activities should be fetched when on friends scope' );

		// clean up!
		$activities_template = $reset_activities_template;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_scope_favorites_no_items() {
		$u1 = $this->factory->user->create();

		$now = time();

		// Create a random activity
		$this->factory->activity->create( array(
			'user_id' => $u1,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		global $activities_template;
		$reset_activities_template = $activities_template;

		// grab activities from favorites scope
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'favorites',
		) );

		// assert!
		$this->assertEmpty( $activities_template->activities, 'When a user has not favorited any activity, no activities should be fetched when on favorites scope' );

		// clean up!
		$activities_template = $reset_activities_template;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_scope_groups_no_items() {
		$u1 = $this->factory->user->create();

		$now = time();

		// Create a random activity
		$this->factory->activity->create( array(
			'user_id' => $u1,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		global $activities_template;
		$reset_activities_template = $activities_template;

		// grab activities from groups scope
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'groups',
		) );

		// assert!
		$this->assertEmpty( $activities_template->activities, 'When a user is not a member of any group, no activities should be fetched when on groups scope' );

		// clean up!
		$activities_template = $reset_activities_template;
	}

	/**
	 * @group scope
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_scope_mentions_no_items() {
		$u1 = $this->factory->user->create();

		$now = time();

		// Create a random activity
		$this->factory->activity->create( array(
			'user_id' => $u1,
			'type' => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );

		global $activities_template;
		$reset_activities_template = $activities_template;

		// grab activities from mentions scope
		bp_has_activities( array(
			'user_id' => $u1,
			'scope' => 'mentions',
		) );

		// assert!
		$this->assertEmpty( $activities_template->activities, 'When a user has no mention, no activities should be fetched when on the mentions scope' );

		// clean up!
		$activities_template = $reset_activities_template;
	}

	/**
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_with_filter_query_nested_conditions() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		$now = time();

		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// misc activity items
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		bp_has_activities( array(
			'filter_query' => array(
				'relation' => 'OR',
				array(
					'column' => 'component',
					'value'  => 'blogs',
				),
				array(
					'relation' => 'AND',
					array(
						'column' => 'type',
						'value'  => 'activity_update',
					),
					array(
						'column' => 'user_id',
						'value'  => $u2,
					),
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_with_filter_query_compare_not_in_operator() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		$now = time();

		// misc activity items
		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u2,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a4 = $this->factory->activity->create( array(
			'user_id'   => $u3,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'id',
					'compare' => 'NOT IN',
					'value'   => array( $a1, $a4 ),
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a2, $a3 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_with_filter_query_compare_between_operator() {
		$u1 = $this->factory->user->create();

		$now = time();

		// misc activity items
		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a4 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'id',
					'compare' => 'BETWEEN',
					'value'   => array( $a3, $a4 ),
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a3, $a4 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_with_filter_query_compare_arithmetic_comparisons() {
		$u1 = $this->factory->user->create();

		$now = time();

		// misc activity items
		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'item_id'   => 1,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'item_id'   => 10,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'item_id'   => 25,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a4 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'item_id'   => 100,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// greater than
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'item_id',
					'compare' => '>',
					'value'   => 10,
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a3, $a4 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// greater or equal than
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'item_id',
					'compare' => '>=',
					'value'   => 10,
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a2, $a3, $a4 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// less than
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'item_id',
					'compare' => '<',
					'value'   => 10,
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// less or equal than
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'item_id',
					'compare' => '<=',
					'value'   => 10,
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// not equal to
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'item_id',
					'compare' => '!=',
					'value'   => 10,
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a3, $a4 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @group filter_query
	 * @group BP_Activity_Query
	 */
	function test_bp_has_activities_with_filter_query_compare_regex() {
		$u1 = $this->factory->user->create();

		$now = time();

		// misc activity items
		$a1 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'blogs',
			'item_id'   => 1,
			'type'      => 'new_blog_post',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'blogs',
			'type'      => 'new_blog_comment',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'activity',
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a4 = $this->factory->activity->create( array(
			'user_id'   => $u1,
			'component' => 'groups',
			'item_id'   => 324,
			'type'      => 'activity_update',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		global $activities_template;

		// REGEXP
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'type',
					'compare' => 'REGEXP',
					'value'   => '^new_blog_',
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// RLIKE is a synonym for REGEXP
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'type',
					'compare' => 'RLIKE',
					'value'   => '^new_blog_',
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// NOT REGEXP
		bp_has_activities( array(
			'filter_query' => array(
				array(
					'column'  => 'type',
					'compare' => 'NOT REGEXP',
					'value'   => '^new_blog_',
				),
			)
		) );

		// assert!
		$this->assertEqualSets( array( $a3, $a4 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
	}

	/**
	 * @ticket BP6169
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_private_group_home_scope() {
		global $activities_template;
		$bp = buddypress();
		$reset_current_group = $bp->groups->current_group;
		$reset_current_action = $bp->current_action;

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		groups_join_group( $g, $u2 );
		groups_join_group( $g, $u3 );

		$a1 = $this->factory->activity->create( array(
			'component' => $bp->groups->id,
			'item_id'   => $g,
			'type'      => 'activity_update',
			'user_id'   => $u2,
			'content'   => 'foo bar',
		) );

		$a2 = $this->factory->activity->create( array(
			'component' => $bp->groups->id,
			'item_id'   => $g,
			'type'      => 'activity_update',
			'user_id'   => $u3,
			'content'   => 'bar foo',
		) );

		$bp->groups->current_group = groups_get_group( array(
			'group_id'        => $g,
			'populate_extras' => true,
		) );

		// On group's home the scope is set to 'home'
		$bp->current_action = 'home';

		bp_has_activities( array( 'action' => 'activity_update' ) );

		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
		$bp->groups->current_group = $reset_current_group;
		$bp->current_action = $reset_current_action;
	}

	/**
	 * @ticket BP6169
	 * @group bp_has_activities
	 */
	public function test_bp_has_activities_hidden_group_home_scope() {
		global $activities_template;
		$bp = buddypress();
		$reset_current_group = $bp->groups->current_group;
		$reset_current_action = $bp->current_action;

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'hidden',
		) );

		groups_join_group( $g, $u2 );
		groups_join_group( $g, $u3 );

		$a1 = $this->factory->activity->create( array(
			'component' => $bp->groups->id,
			'item_id'   => $g,
			'type'      => 'activity_update',
			'user_id'   => $u2,
			'content'   => 'foo bar',
		) );

		$a2 = $this->factory->activity->create( array(
			'component' => $bp->groups->id,
			'item_id'   => $g,
			'type'      => 'activity_update',
			'user_id'   => $u3,
			'content'   => 'bar foo',
		) );

		$bp->groups->current_group = groups_get_group( array(
			'group_id'        => $g,
			'populate_extras' => true,
		) );

		// On group's home the scope is set to 'home'
		$bp->current_action = 'home';

		bp_has_activities( array( 'action' => 'activity_update' ) );

		$this->assertEqualSets( array( $a1, $a2 ), wp_list_pluck( $activities_template->activities, 'id' ) );

		// clean up!
		$activities_template = null;
		$bp->groups->current_group = $reset_current_group;
		$bp->current_action = $reset_current_action;
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

	/**
	 * @group pagination
	 * @group BP_Activity_Template
	 */
	public function test_bp_activity_template_should_give_precedence_to_acpage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['acpage'] = '5';

		$at = new BP_Activity_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Activity_Template
	 */
	public function test_bp_activity_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['acpage'] = '0';

		$at = new BP_Activity_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Activity_Template
	 */
	public function test_bp_activity_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Activity_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Activity_Template
	 */
	public function test_bp_activity_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Activity_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}
}
