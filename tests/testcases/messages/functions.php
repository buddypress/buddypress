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
		$u1 = $this->create_user();
		$u2 = $this->create_user();

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

}
