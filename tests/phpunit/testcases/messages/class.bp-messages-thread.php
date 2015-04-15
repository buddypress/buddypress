<?php

/**
 * @group BP_Messages_Thread
 */
class BP_Tests_BP_Messages_Thread extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function test_construct_cache() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		// prime cache
		new BP_Messages_Thread( $t1 );

		// Cache should exist
		$this->assertThat(
			wp_cache_get( $t1, 'bp_messages_threads' ),
			$this->logicalNot( $this->equalTo( false ) ),
			'Message thread cache should exist.'
		);
	}

	/**
	 * @group order
	 */
	public function test_construct_order_desc() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create thread
		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );
		// save message ID
		$thread = new BP_Messages_Thread( $t1 );
		$m1 = wp_list_pluck( $thread->messages, 'id' );
		$m1 = array_pop( $m1 );

		// create reply
		$t2 = $this->factory->message->create( array(
			'thread_id' => $t1,
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'content' => 'Bar'
		) );
		// save message ID
		$thread = new BP_Messages_Thread( $t1 );
		$m2 = wp_list_pluck( $thread->messages, 'id' );
		$m2 = array_pop( $m2 );

		// now get thread by DESC
		$thread = new BP_Messages_Thread( $t1, 'DESC' );

		// assert!
		$this->assertEquals(
			array( $m2, $m1 ),
			wp_list_pluck( $thread->messages, 'id' )
		);
	}

	/**
	 * @group get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_search_terms_inbox() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t2 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Bar',
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user( array(
			'user_id' => $u2,
			'search_terms' => 'ar',
		) );

		$expected = array( $t2 );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_search_terms_sentbox() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t2 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Bar',
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user( array(
			'user_id' => $u1,
			'box' => 'sentbox',
			'search_terms' => 'ar',
		) );

		$expected = array( $t2 );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_current_threads_for_user
	 * @expectedDeprecated BP_Messages_Thread::get_current_threads_for_user
	 */
	public function test_get_current_threads_for_user_with_old_args() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t2 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Bar',
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user( $u1, 'sentbox', 'all', null, null, 'ar' );

		$expected = array( $t2 );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_should_cache_its_values() {
		global $wpdb;

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $t1 );
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

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_new_message( array(
			'sender_id' => $u2,
			'thread_id' => $t1,
			'recipients' => array( $u1 ),
			'subject' => 'Bar',
			'content' => 'Baz',
		) );

		// Cache should be empty.
		$num_queries = $wpdb->num_queries;
		$recipients_uncached = $thread->get_recipients();
		$this->assertEquals( $num_queries + 1, $wpdb->num_queries );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_single_thread_is_deleted() {
		global $wpdb;

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
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

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
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

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

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
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_is_unread() {
		global $wpdb;

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$t1 = $this->factory->message->create( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$this->assertEquals( $t1, BP_Messages_Thread::is_valid( $t1 ) );
	}

	/**
	 * @group is_valid
	 */
	public function test_is_valid_invalid_thread() {
		$this->assertEquals( null, BP_Messages_Thread::is_valid( 999 ) );
	}
}
