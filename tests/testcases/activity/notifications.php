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
		$this->u1 = $this->create_user();
		$this->u2 = $this->create_user();
		$this->set_current_user( $this->u1 );
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
		parent::tearDown();
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
		$this->go_to( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $this->a1 . '/' );
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
		$this->go_to( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $this->a1 . '/' );
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
		$this->go_to( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $this->a1 . '/' );
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
