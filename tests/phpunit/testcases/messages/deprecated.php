<?php
/**
 * Tests for deprecated messages functionality.
 *
 * @group messages
 * @group deprecated
 */
class BP_Tests_Messages_Deprecated extends BP_UnitTestCase {
	/**
	 * @expectedDeprecated BP_Messages_Thread::get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_old_args() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$message_2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Bar',
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user( $u1, 'sentbox', 'all', null, null, 'ar' );

		$expected = array( $message_2->thread_id );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 *
	 * @expectedDeprecated BP_Messages_Box_Template::__construct
	 */
	public function test_has_message_threads_old_args() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create initial thread
		$message_1 = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies to thread
		$message_2 = self::factory()->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );
		$message_3 = self::factory()->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );

		$messages_template = new BP_Messages_Box_Template( $u1 );

		$this->assertSame( 1, $messages_template->thread_count );
		$this->assertSame( array( $message_1->thread_id ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}
}
