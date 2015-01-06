<?php

/**
 * @group messages
 * @group template
 */
class BP_Tests_Messages_Template extends BP_UnitTestCase {
	/**
	 * @group bp_has_message_threads
	 */
	public function test_has_message_threads() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create initial thread
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies to thread
		$this->factory->message->create( array(
			'thread_id' => $t1,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );
		$this->factory->message->create( array(
			'thread_id' => $t1,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );

		$messages_template = new BP_Messages_Box_Template( array( 'user_id' => $u1 ) );

		$this->assertEquals( 1, $messages_template->thread_count );
		$this->assertSame( array( $t1 ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}

	/**
	 * @group bp_has_message_threads
	 *
	 * @expectedDeprecated BP_Messages_Box_Template::__construct
	 */
	public function test_has_message_threads_old_args() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create initial thread
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies to thread
		$this->factory->message->create( array(
			'thread_id' => $t1,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );
		$this->factory->message->create( array(
			'thread_id' => $t1,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );

		$messages_template = new BP_Messages_Box_Template( $u1 );

		$this->assertEquals( 1, $messages_template->thread_count );
		$this->assertSame( array( $t1 ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}
}