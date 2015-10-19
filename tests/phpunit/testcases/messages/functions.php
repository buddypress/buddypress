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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

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
		$u1 = $this->factory->user->create();

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
}
