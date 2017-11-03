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
	 * @group messages_format_notifications
	 */
	public function test_friends_format_notifications_bp_messages_multiple_new_message_notification_nonstring_filter() {
		// Dummy thread ID
		$t = 12;
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = self::factory()->notification->create( array(
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
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = self::factory()->notification->create( array(
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
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = self::factory()->notification->create( array(
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
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		// Admin
		$n = self::factory()->notification->create( array(
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

	/**
	 * @ticket BP6329
	 */
	public function test_messages_notifications_should_be_deleted_when_corresponding_message_is_deleted() {
		if ( ! bp_is_active( 'messages' ) ) {
			$this->markTestSkipped( __METHOD__ . ' requires the Messages component.' );
		}

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
		) );

		// Verify that a notification has been created for the message.
		$n1 = BP_Notifications_Notification::get( array(
			'component' => 'messages',
			'user_id' => $u2,
		) );
		$this->assertNotEmpty( $n1 );

		$this->assertTrue( messages_delete_thread( $t1 ) );

		$n2 = BP_Notifications_Notification::get( array(
			'component' => 'messages',
			'user_id' => $u2,
		) );
		$this->assertSame( array(), $n2 );
	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}
}
