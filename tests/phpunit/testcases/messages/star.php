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