<?php

/**
 * @group friends
 */
class BP_Tests_Friends_Notifications extends BP_UnitTestcase {
	protected $filter_fired;
	protected $current_user;
	protected $friend;

	public function setUp() {
		parent::setUp();
		$this->current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create() );

		$this->friend = $this->factory->user->create();
		$this->filter_fired = '';
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->current_user );
	}

	/**
	 * @group friends_format_notifications
	 */
	public function test_friends_format_notifications_bp_friends_multiple_friendship_accepted_notification_filter() {
		add_filter( 'bp_friends_multiple_friendship_accepted_notification', array( $this, 'notification_filter_callback' ) );
		$n = friends_format_notifications( 'friendship_accepted', $this->friend, 0, 5 );
		remove_filter( 'bp_friends_multiple_friendship_accepted_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_friends_multiple_friendship_accepted_notification', $this->filter_fired );
	}

	/**
	 * @group friends_format_notifications
	 */
	public function test_friends_format_notifications_bp_friends_single_friendship_accepted_notification_filter() {
		add_filter( 'bp_friends_single_friendship_accepted_notification', array( $this, 'notification_filter_callback' ) );
		$n = friends_format_notifications( 'friendship_accepted', $this->friend, 0, 1 );
		remove_filter( 'bp_friends_single_friendship_accepted_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_friends_single_friendship_accepted_notification', $this->filter_fired );
	}

	/**
	 * @group friends_format_notifications
	 */
	public function test_friends_format_notifications_bp_friends_multiple_friendship_request_notification_filter() {
		add_filter( 'bp_friends_multiple_friendship_request_notification', array( $this, 'notification_filter_callback' ) );
		$n = friends_format_notifications( 'friendship_request', $this->friend, 0, 5 );
		remove_filter( 'bp_friends_multiple_friendship_request_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_friends_multiple_friendship_request_notification', $this->filter_fired );
	}

	/**
	 * @group friends_format_notifications
	 */
	public function test_friends_format_notifications_bp_friends_single_friendship_request_notification_filter() {
		add_filter( 'bp_friends_single_friendship_request_notification', array( $this, 'notification_filter_callback' ) );
		$n = friends_format_notifications( 'friendship_request', $this->friend, 0, 1 );
		remove_filter( 'bp_friends_single_friendship_request_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_friends_single_friendship_request_notification', $this->filter_fired );
	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}
}
