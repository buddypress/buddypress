<?php

/**
 * @group BP_Messages_Thread
 * @group messages
 */
class BP_Tests_BP_Messages_Thread extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function construct_cache() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		// prime cache
		new BP_Messages_Thread( $message->thread_id );

		// Cache should exist
		$this->assertThat(
			wp_cache_get( $message->thread_id, 'bp_messages_threads' ),
			$this->logicalNot( $this->equalTo( false ) ),
			'Message thread cache should exist.'
		);
	}

	public function test_get_messages_with_invalid_thread_id() {
		$this->assertTrue( empty( BP_Messages_Thread::get_messages( 0 ) ) );
	}

	public function test_get_messages_using_arguments() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$m1 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		self::factory()->message->create_many(
			98,
			array(
				'thread_id' => $m1->thread_id,
				'sender_id' => $u2,
				'recipients' => array( $u1 ),
				'subject' => 'Bar',
			)
		);

		// Last message
		self::factory()->message->create(
			array(
				'thread_id' => $m1->thread_id,
				'sender_id' => $u1,
				'recipients' => array( $u2 ),
				'subject' => 'Last Message',
			)
		);

		// Default results.
		$messages = BP_Messages_Thread::get_messages( $m1->thread_id );
		$this->assertTrue( 100 === count( $messages ) );

		// Get first 10 messages.
		$messages = BP_Messages_Thread::get_messages( $m1->thread_id, array( 'page' => 1, 'per_page' => 10 ) );
		$this->assertTrue( 10 === count( $messages ) );

		// Get first 10 messages differently.
		$thread = new BP_Messages_Thread( $m1->thread_id, 'ASC', array( 'page' => 1, 'per_page' => 10 ) );
		$this->assertTrue( 10 === count( $thread->messages ) );

		// Get all messages.
		$messages = BP_Messages_Thread::get_messages( $m1->thread_id, array( 'page' => null, 'per_page' => null ) );
		$this->assertTrue( 100 === count( $messages ) );

		// Get all mesages differently.
		$thread = new BP_Messages_Thread( $m1->thread_id, 'ASC', array( 'page' => null, 'per_page' => null ) );
		$this->assertTrue( 100 === count( $thread->messages ) );

		// Get last message.
		$messages = BP_Messages_Thread::get_messages( $m1->thread_id, array( 'page' => 100, 'per_page' => 1 ) );
		$this->assertTrue( 1 === count( $messages ) );
		$this->assertEquals( $u1, $messages[0]->sender_id );
		$this->assertEquals( 'Last Message', $messages[0]->subject );
	}

	/**
	 * @group order
	 */
	public function test_construct_order_desc() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create thread
		$message_1 = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Foo',
		) );

		// create reply
		$message_2 = self::factory()->message->create_and_get( array(
			'thread_id'  => $message_1->thread_id,
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'content'    => 'Bar'
		) );

		// Default sort from constructor.
		$thread = new BP_Messages_Thread( $message_1->thread_id );
		$this->assertEquals(
			array( $message_1->id, $message_2->id ),
			wp_list_pluck( $thread->messages, 'id' )
		);

		// Default via the helper method.
		$messages = BP_Messages_Thread::get_messages( $message_1->thread_id );
		$this->assertEquals(
			array( $message_1->id, $message_2->id ),
			wp_list_pluck( $messages, 'id' )
		);

		// Now get thread by DESC via the constructor.
		$thread = new BP_Messages_Thread( $message_1->thread_id, 'DESC' );
		$this->assertEquals(
			array( $message_2->id, $message_1->id ),
			wp_list_pluck( $thread->messages, 'id' )
		);

		// Testing sort with lowercase.
		$thread = new BP_Messages_Thread( $message_1->thread_id, 'desc' );
		$this->assertEquals(
			array( $message_2->id, $message_1->id ),
			wp_list_pluck( $thread->messages, 'id' )
		);

		// Testing sort with lowercase and space.
		$thread = new BP_Messages_Thread( $message_1->thread_id, '    desc' );
		$this->assertEquals(
			array( $message_2->id, $message_1->id ),
			wp_list_pluck( $thread->messages, 'id' )
		);

		// Now sorting via the helper method.
		$messages = BP_Messages_Thread::get_messages( $message_1->thread_id, array( 'order' => 'desc' ) );
		$this->assertEquals(
			array( $message_2->id, $message_1->id ),
			wp_list_pluck( $messages, 'id' )
		);
	}

	/**
	 * @group get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_search_terms_inbox() {
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

		$threads = BP_Messages_Thread::get_current_threads_for_user( array(
			'user_id' => $u2,
			'search_terms' => 'ar',
		) );

		$expected = array( $message_2->thread_id );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_search_terms_sentbox() {
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

		$threads = BP_Messages_Thread::get_current_threads_for_user( array(
			'user_id' => $u1,
			'box' => 'sentbox',
			'search_terms' => 'ar',
		) );

		$expected = array( $message_2->thread_id );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_current_threads_for_user
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
	 * @group get_current_threads_for_user
	 */
	public function test_get_current_threads_setting_per_page_messages_and_recipients() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create initial thread
		$message_1 = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
			)
		);

		// create some replies to thread
		self::factory()->message->create_and_get(
			array(
				'thread_id'  => $message_1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
			)
		);

		self::factory()->message->create_and_get(
			array(
				'thread_id'  => $message_1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
			)
		);

		self::factory()->message->create_and_get(
			array(
				'thread_id'  => $message_1->thread_id,
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
			)
		);

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array( 'user_id' => $u1 )
		)['threads'];

		$this->assertCount( 4, $threads[0]->messages );
		$this->assertCount( 2, $threads[0]->recipients );

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id'             => $u1,
				'messages_page'       => 1,
				'messages_per_page'   => 2,
				'recipients_page'     => 1,
				'recipients_per_page' => 1,
			)
		)['threads'];

		$this->assertCount( 2, $threads[0]->messages );
		$this->assertNotCount( 2, $threads[0]->recipients );
		$this->assertCount( 1, $threads[0]->recipients );
	}

	/**
	 * @group get_recipients
	 */
	public function test_get_recipients_paginated() {
		$u1       = self::factory()->user->create();
		$user_ids = self::factory()->user->create_many( 9 );
		$m        = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => $user_ids,
			'subject'    => 'Foo',
		) );

		$thread_1 = new BP_Messages_Thread( $m->thread_id );
		$this->assertTrue( 10 === count( $thread_1->get_recipients() ) );

		$thread_2 = new BP_Messages_Thread( $m->thread_id, 'ASC', array( 'recipients_page' => 1, 'recipients_per_page' => 5 ) );
		$this->assertTrue( 5 === count( $thread_2->recipients ) );

		$thread_3 = new BP_Messages_Thread( $m->thread_id );
		$this->assertTrue( 8 === count( $thread_3->get_recipients( $m->thread_id, array( 'recipients_page' => 1, 'recipients_per_page' => 8 ) ) ) );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_should_cache_its_values() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $message->thread_id );
		$recipients = $thread->get_recipients();

		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();

		$this->assertEquals( $recipients, $recipients_cached );
		$this->assertEquals( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_message_is_sent() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $message->thread_id );
		$thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_new_message( array(
			'sender_id' => $u2,
			'thread_id' => $message->thread_id,
			'recipients' => array( $u1 ),
			'subject' => 'Bar',
			'content' => 'Baz',
		) );

		// Cache should be empty.
		$num_queries = $wpdb->num_queries;
		$thread->get_recipients();
		$this->assertEquals( $num_queries + 1, $wpdb->num_queries );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_single_thread_is_deleted() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_delete_thread( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_array_of_threads_is_deleted() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_delete_thread( array( $t1 ) );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_is_read() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		// Mark thread as read
		$current_user = get_current_user_id();
		$this->set_current_user( $u2 );
		messages_mark_thread_read( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group cache
	 */
	public function test_marking_a_thread_as_read_with_specific_user_id() {
		$u1      = self::factory()->user->create();
		$u2      = self::factory()->user->create();
		$message = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Foo',
		) );

		$thread_id = $message->thread_id;

		// Cache should be populated.
		$this->assertTrue( (bool) wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' ) );

		// Mark thread as read.
		messages_mark_thread_read( $thread_id, $u2 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' ) );

		$thread = new BP_Messages_Thread( $thread_id );

		$this->assertFalse( (bool) $thread->unread_count );
		$this->assertFalse( (bool) $thread->recipients[ $u1 ]->unread_count );
		$this->assertFalse( (bool) $thread->recipients[ $u2 ]->unread_count );
	}

	/**
	 * @group cache
	 */
	public function test_marking_a_thread_as_unread_with_specific_user_id() {
		$u1      = self::factory()->user->create();
		$u2      = self::factory()->user->create();
		$message = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Foo',
		) );

		$thread_id = $message->thread_id;

		// Cache should be populated.
		$this->assertTrue( (bool) wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' ) );

		// Mark thread as unread.
		messages_mark_thread_unread( $thread_id, $u2 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' ) );

		$thread = new BP_Messages_Thread( $thread_id );

		$this->assertFalse( (bool) $thread->recipients[ $u1 ]->unread_count );
		$this->assertTrue( (bool) $thread->recipients[ $u2 ]->unread_count );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_is_unread() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		// Mark thread as unread
		$current_user = get_current_user_id();
		$this->set_current_user( $u2 );
		messages_mark_thread_unread( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group check_access
	 */
	public function test_check_access_valid_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		// save recipient ID
		$thread = new BP_Messages_Thread( $t1 );
		$r1 = wp_list_pluck( $thread->recipients, 'id' );
		$r1 = array_pop( $r1 );

		$this->assertEquals( $r1, BP_Messages_Thread::check_access( $t1, $u1 ) );
	}

	/**
	 * @group check_access
	 */
	public function test_check_access_invalid_thread() {
		$this->assertEquals( null, BP_Messages_Thread::check_access( 999, 1 ) );
	}

	/**
	 * @group is_valid
	 */
	public function test_is_valid_valid_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$this->assertEquals( $t1, BP_Messages_Thread::is_valid( $t1 ) );
	}

	/**
	 * @group is_valid
	 */
	public function test_is_valid_invalid_thread() {
		$this->assertEquals( null, BP_Messages_Thread::is_valid( 999 ) );
	}

	/**
	 * @group last_message
	 */
	public function test_last_message_populated() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$date = bp_core_current_time();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
			'date_sent' => $date,
			'content' => 'Bar and baz.',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );

		$this->assertNotNull( $thread->last_message_id );
		$this->assertEquals( 'Foo', $thread->last_message_subject );
		$this->assertEquals( $u1, $thread->last_sender_id );
		$this->assertEquals( $date, $thread->last_message_date );
		$this->assertEquals( 'Bar and baz.', $thread->last_message_content );
	}
}
