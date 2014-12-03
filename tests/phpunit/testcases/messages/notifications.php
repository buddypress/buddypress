<?php

/**
 * @group notifications
 * @group messages
 */
class BP_Tests_Messages_Notifications extends BP_UnitTestCase {

	protected $filter_fired;

	public function setUp() {
		parent::setUp();

		$this->filter_fired = '';
	}
	/**
	 * @group bp_messages_message_delete_notifications
	 */
	public function test_bp_messages_message_delete_notifications() {
		$current_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Dummy thread ID
		$t = 12;

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Double check it's there
		$this->assertEquals( array( $n ), wp_list_pluck( $notifications, 'id' ) );

		// fire the hook
		do_action( 'messages_thread_deleted_thread', $t );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		$this->assertEmpty( $notifications );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group messages_format_notifications
	 */
	public function test_friends_format_notifications_bp_messages_multiple_new_message_notification_nonstring_filter() {
		// Dummy thread ID
		$t = 12;
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		add_filter( 'bp_messages_multiple_new_message_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 2, 'array' );
		remove_filter( 'bp_messages_multiple_new_message_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_multiple_new_message_notification', $this->filter_fired );
	}

	/**
	 * @group messages_format_notifications
	 */
	public function test_friends_format_notifications_bp_messages_single_new_message_notification_nonstring_filter() {
		// Dummy thread ID
		$t = 12;
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		add_filter( 'bp_messages_single_new_message_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 1, 'array' );
		remove_filter( 'bp_messages_single_new_message_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_single_new_message_notification', $this->filter_fired );
	}

	/**
	 * @group messages_format_notifications
	 */
	public function test_friends_format_notifications_bp_messages_multiple_new_message_notification_string_filter() {
		// Dummy thread ID
		$t = 12;
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		add_filter( 'bp_messages_multiple_new_message_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 2 );
		remove_filter( 'bp_messages_multiple_new_message_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_multiple_new_message_notification', $this->filter_fired );
	}

	/**
	 * @group messages_format_notifications
	 */
	public function test_friends_format_notifications_bp_messages_single_new_message_notification_string_filter() {
		// Dummy thread ID
		$t = 12;
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		add_filter( 'bp_messages_single_new_message_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 1 );
		remove_filter( 'bp_messages_single_new_message_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_single_new_message_notification', $this->filter_fired );
	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}
}
