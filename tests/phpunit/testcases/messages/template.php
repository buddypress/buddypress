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

	/**
	 * @group bp_has_message_threads
	 * @group meta_query
	 */
	public function test_thread_has_messages_meta_query() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create some threads
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );
		$t2 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// misc threads
		$this->factory->message->create_many( 3, array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies for thread 1
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'content'    => "I see you've played knivey-spooney before.",
		) );
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => '*crickets*',
		) );

		// create some replies for thread 2
		$this->factory->message->create( array(
			'thread_id'  => $t2,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "Oy yourself.",
		) );

		// grab the message ids as individual variables for thread 1
		$thread = new BP_Messages_Thread( $t1 );
		$mids = wp_list_pluck( $thread->messages, 'id' );
		list( $m1, $m2, $m3, $m4 ) = $mids;

		// grab the message ids as individual variables for thread 2
		$thread = new BP_Messages_Thread( $t2 );
		$mids = wp_list_pluck( $thread->messages, 'id' );
		list( $m5, $m6 ) = $mids;

		// add meta for some of the messages
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );
		bp_messages_update_meta( $m1, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		bp_messages_update_meta( $m3, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m5, "starred_by_user_{$u2}", true );

		// now, do the message thread loop query
		global $messages_template;
		bp_has_message_threads( array(
			'user_id' => $u2,
			'meta_query' => array(
				array(
					'key' => "starred_by_user_{$u2}"
				),
			)
		) );

		$this->assertEquals( 2, $messages_template->thread_count );
		$this->assertEqualSets( array( $t1, $t2 ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}

	/**
	 * @group bp_has_message_threads
	 * @group meta_query
	 */
	public function test_thread_has_messages_meta_query_multiple_clauses_relation_and() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create some threads
		$t1 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );
		$t2 = $this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// misc threads
		$this->factory->message->create_many( 3, array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies for thread 1
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'content'    => "I see you've played knivey-spooney before.",
		) );
		$this->factory->message->create( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => '*crickets*',
		) );

		// create some replies for thread 2
		$this->factory->message->create( array(
			'thread_id'  => $t2,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "Oy yourself.",
		) );

		// grab the message ids as individual variables for thread 1
		$thread = new BP_Messages_Thread( $t1 );
		$mids = wp_list_pluck( $thread->messages, 'id' );
		list( $m1, $m2, $m3, $m4 ) = $mids;

		// grab the message ids as individual variables for thread 2
		$thread = new BP_Messages_Thread( $t2 );
		$mids = wp_list_pluck( $thread->messages, 'id' );
		list( $m5, $m6 ) = $mids;

		// add meta for some of the messages
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );
		bp_messages_update_meta( $m1, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		bp_messages_update_meta( $m3, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m5, "starred_by_user_{$u2}", true );

		// now, do the message thread loop query
		global $messages_template;
		bp_has_message_threads( array(
			'user_id' => $u2,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => "starred_by_user_{$u2}"
				),
				array(
					'key' => 'utensil',
					'value' => 'knive',
				),
			)
		) );

		$this->assertEquals( 1, $messages_template->thread_count );
		$this->assertEqualSets( array( $t1 ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}
}