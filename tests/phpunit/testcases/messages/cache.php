<?php

/**
 * @group messages
 * @group cache
 */
class BP_Tests_Message_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_messages_update_meta_cache
	 */
	public function test_bp_messages_update_meta_cache() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create the thread
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );

		// create a reply
		$message_2 = $this->factory->message->create_and_get( array(
			'thread_id'  => $message_1->thread_id,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );

		$m1 = $message_1->id;
		$m2 = $message_2->id;

		// add cache for each message
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		// prime cache
		bp_messages_get_meta( $m1, 'utensil' );

		// Ensure an empty cache for second message
		wp_cache_delete( $m2, 'message_meta' );

		// update message meta cache
		bp_messages_update_meta_cache( array( $m1, $m2 ) );

		$expected = array(
			$m1 => array(
				'utensil' => array(
					'knive',
				),
				'is_knive' => array(
					'yes',
				),
			),
			$m2 => array(
				'utensil' => array(
					'spoon',
				),
				'is_knive' => array(
					'no',
				),
				'is_spoon' => array(
					'yes',
				),
			),
		);

		$found = array(
			$m1 => wp_cache_get( $m1, 'message_meta' ),
			$m2 => wp_cache_get( $m2, 'message_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_messages_update_meta_cache
	 * @group bp_thread_has_messages
	 */
	public function test_bp_messages_metadata_cache_in_message_loop() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create the message and thread
		$m = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// add message cache
		bp_messages_update_meta( $m->id, 'yolo', 'gah' );

		// prime meta cache in message loop
		bp_thread_has_messages( array(
			'thread_id' => $m->thread_id,
			'update_meta_cache' => true
		) );

		$this->assertNotEmpty( wp_cache_get( $m->id, 'message_meta' ) );
	}

	/**
	 * @group bp_messages_delete_meta
	 * @group messages_delete_thread
	 */
	public function test_bp_messages_delete_metadata_cache_on_thread_delete() {
		$this->old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create the thread
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// create a reply
		$message_2 = $this->factory->message->create_and_get( array(
			'thread_id'  => $message_1->thread_id,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => 'Yo',
		) );

		$m1 = $message_1->id;
		$m2 = $message_2->id;
		$t1 = $message_1->thread_id;

		// add message meta
		bp_messages_update_meta( $m1, 'yolo', 'gah' );
		bp_messages_update_meta( $m2, 'yolo', 'bah' );

		// prime message meta cache
		bp_messages_get_meta( $m1, 'yolo' );
		bp_messages_get_meta( $m2, 'yolo' );

		// delete thread
		// to outright delete a thread, both recipients must delete it
		$this->set_current_user( $u1 );
		messages_delete_thread( $t1 );
		$this->set_current_user( $u2 );
		messages_delete_thread( $t1 );

		// assert empty meta cache
		$this->assertEmpty( wp_cache_get( $m1, 'message_meta' ) );
		$this->assertEmpty( wp_cache_get( $m2, 'message_meta' ) );

		// cleanup
		$this->set_current_user( $this->old_current_user );
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
