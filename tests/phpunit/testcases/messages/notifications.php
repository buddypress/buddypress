<?php

/**
 * @group notifications
 * @group messages
 */
class BP_Tests_Messages_Notifications extends BP_UnitTestCase {

	protected $filter_fired;

	public function setUp() {
		parent::setUp();

		$this->reset_user_id = get_current_user_id();

		$this->filter_fired = '';
	}

	public function tearDown() {
		parent::tearDown();

		$this->set_current_user( $this->reset_user_id );
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

		add_filter( 'bp_messages_multiple_new_message_array_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 2, 'array' );
		remove_filter( 'bp_messages_multiple_new_message_array_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_multiple_new_message_array_notification', $this->filter_fired );
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

		add_filter( 'bp_messages_single_new_message_array_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 1, 'array' );
		remove_filter( 'bp_messages_single_new_message_array_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_single_new_message_array_notification', $this->filter_fired );
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

		add_filter( 'bp_messages_multiple_new_message_string_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 2 );
		remove_filter( 'bp_messages_multiple_new_message_string_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_multiple_new_message_string_notification', $this->filter_fired );
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

		add_filter( 'bp_messages_single_new_message_string_notification', array( $this, 'notification_filter_callback' ) );
		$n = messages_format_notifications( 'new_message', $n, '', 1 );
		remove_filter( 'bp_messages_single_new_message_string_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_messages_single_new_message_string_notification', $this->filter_fired );
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

	/**
	 * @ticket BP8426
	 */
	public function test_bp_messages_mark_notification_on_mark_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$m1 = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Foo',
		) );

		self::factory()->message->create_many(
			9,
			array(
				'thread_id' => $m1->thread_id,
				'sender_id' => $u2,
				'recipients' => array( $u1 ),
				'subject' => 'Bar',
			)
		);

		$unreadn = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u1,
					'component_name'    => buddypress()->messages->id,
					'component_action'  => 'new_message',
					'is_new'            => 1,
				)
			),
			'user_id',
			'id'
		);

		$this->set_current_user( $u1 );

		// Mark a thread read.
		bp_messages_mark_notification_on_mark_thread( $m1->thread_id, $u1, count( $unreadn ) );

		$readn = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u1,
					'component_name'    => buddypress()->messages->id,
					'component_action'  => 'new_message',
					'is_new'            => 0,
				)
			),
			'user_id',
			'id'
		);

		$this->assertSame( $unreadn, $readn );
	}

	/**
	 * @ticket BP8426
	 * @group message_delete_notifications
	 */
	public function test_bp_messages_message_delete_notifications() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$m1 = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Foo',
		) );

		$message_ids = self::factory()->message->create_many(
			9,
			array(
				'thread_id' => $m1->thread_id,
				'sender_id' => $u2,
				'recipients' => array( $u1 ),
				'subject' => 'Bar',
			)
		);

		$message_ids = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u1,
					'component_name'    => buddypress()->messages->id,
					'component_action'  => 'new_message',
					'is_new'            => 1,
				)
			),
			'item_id'
		);

		$test = bp_messages_message_delete_notifications( $m1->thread_id, $message_ids );

		$deleted = BP_Notifications_Notification::get(
			array(
				'user_id'           => $u1,
				'component_name'    => buddypress()->messages->id,
				'component_action'  => 'new_message',
				'is_new'            => 1,
			)
		);

		$this->assertEmpty( $deleted );
	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}
}
