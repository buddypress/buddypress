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
}
