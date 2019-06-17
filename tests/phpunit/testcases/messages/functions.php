<?php

/**
 * @group messages
 * @group functions
 */
class BP_Tests_Messages_Functions extends BP_UnitTestCase {

	/**
	 * @group counts
	 */
	public function test_get_unread_count() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// send a private message
		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
		) );

		// get unread count for $u2
		$this->set_current_user( $u2 );
		$this->assertEquals( 1, messages_get_unread_count( $u2 ) );

		// send another message and get recheck unread count
		$t2 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
		) );
		$this->assertEquals( 2, messages_get_unread_count( $u2 ) );

		// mark one message as read
		messages_mark_thread_read( $t1 );

		// recheck unread count
		$this->assertEquals( 1, messages_get_unread_count( $u2 ) );
	}

	/**
	 * @group messages_new_message
	 */
	public function test_messages_new_message_invalid_recipient_error_message() {
		$u1 = self::factory()->user->create();

		// attempt to send a private message to an invalid username
		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( 'homerglumpkin' ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
			'error_type' => 'wp_error'
		) );

		$this->assertSame( 'Message could not be sent because you have entered an invalid username. Please try again.', $t1->get_error_message() );
	}

	/**
	 * @group messages_new_message
	 */
	public function test_messages_new_message_wp_error_generic() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// Emulate a plugin disabling messages.
		add_action( 'messages_message_before_save', array( $this, 'remove_recipients_before_save' ) );

		// send a private message
		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
			'error_type' => 'wp_error'
		) );

		$this->assertNotEmpty( $t1->get_error_code() );

		remove_action( 'messages_message_before_save', array( $this, 'remove_recipients_before_save' ) );
	}

	/**
	 * Helper method for test_messages_new_message_wp_error_generic().
	 */
	public function remove_recipients_before_save( $message ) {
		$message->recipients = array();
	}

	/**
	 * @ticket BP7819
	 * @ticket BP7698
	 */
	public function test_bp_messages_personal_data_exporter() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$time = time();

		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 3 * HOUR_IN_SECONDS ) ),
		) );

		$t1m1 = messages_new_message( array(
			'sender_id'  => $u2,
			'thread_id'  => $t1,
			'recipients' => array( $u1 ),
			'subject'    => 'Reply to ' . $t1,
			'content'    => 'Hey there!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 2 * HOUR_IN_SECONDS ) ),
		) );

		$t1m2 = messages_new_message( array(
			'sender_id'  => $u1,
			'thread_id'  => $t1,
			'recipients' => array( $u2 ),
			'subject'    => 'Reply to ' . $t1,
			'content'    => 'Hey there!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 2 * HOUR_IN_SECONDS ) ),
		) );

		$t2 = messages_new_message( array(
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'subject'    => 'A new message',
			'content'    => 'Hey there!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 5 * HOUR_IN_SECONDS ) ),
		) );

		$t2m1 = messages_new_message( array(
			'sender_id'  => $u1,
			'thread_id'  => $t2,
			'recipients' => array( $u2 ),
			'subject'    => 'Reply to ' . $t2,
			'content'    => 'Hey there!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 4 * HOUR_IN_SECONDS ) ),
		) );

		$test_user = new WP_User( $u1 );

		$actual = bp_messages_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );

		// Number of exported messages.
		$this->assertSame( 3, count( $actual['data'] ) );
	}

	/**
	 * @ticket BP8080
	 */
	public function test_bp_messages_personal_data_exporter_check_sender() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$expected = array(
			'Hey u2!',
			'You could have replied to my first message u2!',
		);

		$time = time();

		$t1 = messages_new_message( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A new message',
			'content'    => $expected[0],
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 3 * HOUR_IN_SECONDS ) ),
		) );

		$t2 = messages_new_message( array(
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'subject'    => 'A new message',
			'content'    => 'Hey u1!',
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 5 * HOUR_IN_SECONDS ) ),
		) );

		$t3 = messages_new_message( array(
			'sender_id'  => $u1,
			'thread_id'  => $t2,
			'recipients' => array( $u2 ),
			'subject'    => 'Reply to ' . $t2,
			'content'    => $expected[1],
			'date_sent'  => date( 'Y-m-d H:i:s', $time - ( 4 * HOUR_IN_SECONDS ) ),
		) );

		$test_user = new WP_User( $u1 );

		$threads      = bp_messages_personal_data_exporter( $test_user->user_email, 1 );
		$threads_data = wp_list_pluck( $threads['data'], 'data' );
		$actual       = array();

		foreach ( $threads_data as $thread ) {
			foreach ( $thread as $data ) {
				if ( 'Message Content' !== $data['name'] ) {
					continue;
				}

				$actual[] = $data['value'];
			}
		}

		// Only messages sent by u1 should be exported.
		$this->assertEquals( $expected, $actual );
	}
}
