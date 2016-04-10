<?php
/**
 * @group messages
 * @group star
 */
class BP_Tests_Messages_Star_ extends BP_UnitTestCase {

	/**
	 * @group bp_messages_is_message_starred
	 * @group bp_messages_star_set_action
	 */
	public function test_is_message_starred() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create the thread
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );

		// create a reply
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );

		// grab the message ids as individual variables
		list( $m1, $m2 ) = $this->get_message_ids( $t1 );

		// star the second message
		$star = bp_messages_star_set_action( array(
			'user_id'    => $u1,
			'message_id' => $m2,
		) );

		// assert that star is set
		$this->assertTrue( $star );
		$this->assertTrue( bp_messages_is_message_starred( $m2, $u1 ) );

		// unstar the second message
		$unstar = bp_messages_star_set_action( array(
			'user_id'    => $u1,
			'message_id' => $m2,
			'action'     => 'unstar'
		) );

		// assert that star is removed
		$this->assertTrue( $unstar );
		$this->assertFalse( bp_messages_is_message_starred( $m2, $u1 ) );
	}

	/**
	 * @group bp_messages_star_set_action
	 * @group bulk
	 */
	public function test_star_set_action_bulk_unstar() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create the thread
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );

		// create a reply
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );

		// grab the message ids as individual variables
		list( $m1, $m2 ) = $this->get_message_ids( $t1 );

		// star all messages
		bp_messages_star_set_action( array(
			'user_id'    => $u1,
			'message_id' => $m1,
		) );
		bp_messages_star_set_action( array(
			'user_id'    => $u1,
			'message_id' => $m2,
		) );

		// assert that stars are set
		$this->assertTrue( bp_messages_is_message_starred( $m1, $u1 ) );
		$this->assertTrue( bp_messages_is_message_starred( $m2, $u1 ) );

		// unstar all messages
		bp_messages_star_set_action( array(
			'user_id'    => $u1,
			'thread_id'  => $t1,
			'action'     => 'unstar',
			'bulk'       => true
		) );

		// assert that star is removed
		$this->assertFalse( bp_messages_is_message_starred( $m1, $u1 ) );
		$this->assertFalse( bp_messages_is_message_starred( $m2, $u1 ) );
	}

	/**
	 * @group bp_messages_filter_starred_message_threads
	 */
	public function test_get_starred_threads_should_not_include_deleted_thread() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create three threads
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'A',
		) );
		$t2 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'B',
		) );
		$t3 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'C',
		) );

		// grab the message ids as individual variables
		list( $m1 ) = $this->get_message_ids( $t1 );
		list( $m2 ) = $this->get_message_ids( $t2 );
		list( $m3 ) = $this->get_message_ids( $t3 );

		// star all threads
		bp_messages_star_set_action( array(
			'user_id'    => $u2,
			'message_id' => $m1,
		) );
		bp_messages_star_set_action( array(
			'user_id'    => $u2,
			'message_id' => $m2,
		) );
		bp_messages_star_set_action( array(
			'user_id'    => $u2,
			'message_id' => $m3,
		) );

		// delete the second thread
		$this->set_current_user( $u2 );
		messages_delete_thread( $t2 );

		// load the starred threads loop
		global $messages_template;
		add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
		bp_has_message_threads();
		remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );

		// assert that second thread isn't in starred thread loop
		$thread_ids = wp_list_pluck( $messages_template->threads, 'thread_id' );
		$this->assertFalse( in_array( $t2, $thread_ids ) );

		// reset
		$this->set_current_user( $old_current_user );
	}

	/**
	 * Helper method to grab the message IDs from a message thread.
	 *
	 * @param int $thread_id The message thread ID
	 * @return array
	 */
	protected function get_message_ids( $thread_id = 0 ) {
		$thread = new BP_Messages_Thread( $thread_id );
		return wp_list_pluck( $thread->messages, 'id' );
	}
}
