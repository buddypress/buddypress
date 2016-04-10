<?php

/**
 * @group activity
 * @group notifications
 */
class BP_Tests_Activity_Notifications extends BP_UnitTestCase {
	protected $current_user;
	protected $u1;
	protected $u2;
	protected $a1;
	protected $a2;

	public function setUp() {
		parent::setUp();
		$this->current_user = get_current_user_id();
		$this->u1 = $this->factory->user->create();
		$this->u2 = $this->factory->user->create();
		$this->set_current_user( $this->u1 );

		/**
		 * Tests suite in WP < 4.0 does not include the WP_UnitTestCase->_restore_hooks() function
		 * When updating an activity, the following filter is fired to prevent sending more than one
		 * notification. Once we've reached this filter all at_mentions tests fails so we need to
		 * temporarly remove it and restore it in $this->tearDown()
		 */
		remove_filter( 'bp_activity_at_name_do_notifications', '__return_false' );
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
		parent::tearDown();

		// Restore the filter
		add_filter( 'bp_activity_at_name_do_notifications', '__return_false' );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be empty
		$this->assertEquals( array(), $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink_logged_out() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( 0 );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink_wrong_user() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Switch user
		$this->set_current_user( $this->u2 );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be empty
		$this->assertEquals( array(), $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions_logged_out() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( 0 );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// clean up
		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions_wrong_user() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( $this->u2 );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// clean up
		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_notifications_delete_all_notifications_by_type
	 * @group bp_activity_at_mention_delete_notification
	 */
	public function test_bp_activity_at_mention_delete_notification() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		bp_activity_delete( array(
			'id' => $this->a1,
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		$this->assertEmpty( $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 * @ticket BP6687
	 */
	public function test_bp_activity_remove_screen_notifications_on_new_mentions_cleared() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );
		$this->assertEquals( 1, bp_get_total_mention_count_for_user( $this->u1 ) );

		// Clear notifications for $this->u1
		bp_activity_clear_new_mentions( $this->u1 );

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		$this->assertEmpty( $notifications, 'Notifications should be cleared when new mention metas are removed' );
		$this->assertEmpty( bp_get_total_mention_count_for_user( $this->u1 ) );
	}

	/**
	 * Creates two notifications for $u1, one of which is for mentions
	 */
	protected function create_notifications() {
		$u1_mentionname = bp_activity_get_user_mentionname( $this->u1 );
		$this->a1 = $this->factory->activity->create( array(
			'user_id' => $this->u2,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => sprintf( 'Hello! @%s', $u1_mentionname ),
		) );
		$u2_mentionname = bp_activity_get_user_mentionname( $this->u2 );
		$this->a2 = $this->factory->activity->create( array(
			'user_id' => $this->u1,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => sprintf( 'Hello! @%s', $u2_mentionname ),
		) );
	}
}
