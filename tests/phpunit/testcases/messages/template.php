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
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies to thread
		$message_2 = $this->factory->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );
		$message_3 = $this->factory->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );

		$messages_template = new BP_Messages_Box_Template( array( 'user_id' => $u1 ) );

		$this->assertEquals( 1, $messages_template->thread_count );
		$this->assertSame( array( $message_1->thread_id ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
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
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// create some replies to thread
		$message_2 = $this->factory->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );
		$message_3 = $this->factory->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
		) );

		$messages_template = new BP_Messages_Box_Template( $u1 );

		$this->assertEquals( 1, $messages_template->thread_count );
		$this->assertSame( array( $message_1->thread_id ), wp_list_pluck( $messages_template->threads, 'thread_id' ) );
	}

	/**
	 * @group bp_has_message_threads
	 * @group meta_query
	 */
	public function test_thread_has_messages_meta_query() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create some threads
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );
		$message_2 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// misc threads
		$this->factory->message->create_many( 3, array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		$t1 = $message_1->thread_id;
		$t2 = $message_2->thread_id;

		// create some replies for thread 1
		$message_3 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );
		$message_4 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'content'    => "I see you've played knivey-spooney before.",
		) );
		$message_5 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => '*crickets*',
		) );

		// create some replies for thread 2
		$message_6 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t2,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "Oy yourself.",
		) );

		// belong to $t1
		$m1 = $message_1->id;
		$m3 = $message_3->id;
		$m4 = $message_4->id;
		$m5 = $message_5->id;

		// belong to $t2
		$m2 = $message_2->id;
		$m6 = $message_6->id;

		// add meta for some of the messages
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );
		bp_messages_update_meta( $m1, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		bp_messages_update_meta( $m3, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m6, "starred_by_user_{$u2}", true );

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
		$message_1 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );
		$message_2 = $this->factory->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// misc threads
		$this->factory->message->create_many( 3, array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		$t1 = $message_1->thread_id;
		$t2 = $message_2->thread_id;

		// create some replies for thread 1
		$message_3 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );
		$message_4 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'content'    => "I see you've played knivey-spooney before.",
		) );
		$message_5 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t1,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => '*crickets*',
		) );

		// create some replies for thread 2
		$message_6 = $this->factory->message->create_and_get( array(
			'thread_id'  => $t2,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "Oy yourself.",
		) );

		// belong to $t1
		$m1 = $message_1->id;
		$m3 = $message_3->id;
		$m4 = $message_4->id;
		$m5 = $message_5->id;

		// belong to $t2
		$m2 = $message_2->id;
		$m6 = $message_6->id;

		// add meta for some of the messages
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );
		bp_messages_update_meta( $m1, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		bp_messages_update_meta( $m3, "starred_by_user_{$u2}", true );

		bp_messages_update_meta( $m6, "starred_by_user_{$u2}", true );

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

	/**
	 * @group bp_has_message_threads
	 */
	public function test_has_message_threads_anonymous_user_should_not_see_threads() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create initial thread
		$this->factory->message->create( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
		) );

		// set user to anonymous
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		// now, do the message thread query
		global $messages_template;
		bp_has_message_threads();

		// assert!
		$this->assertEquals( 0, $messages_template->thread_count );
		$this->assertEmpty( $messages_template->threads );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group pagination
	 * @group BP_Messages_Box_Template
	 */
	public function test_bp_messages_template_should_give_precedence_to_mpage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['mpage'] = '5';

		$at = new BP_Messages_Box_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Messages_Box_Template
	 */
	public function test_bp_messages_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['mpage'] = '0';

		$at = new BP_Messages_Box_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Messages_Box_Template
	 */
	public function test_bp_messages_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Messages_Box_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Messages_Box_Template
	 */
	public function test_bp_messages_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Messages_Box_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}
}
