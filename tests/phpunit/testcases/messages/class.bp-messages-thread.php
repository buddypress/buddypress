<?php

/**
 * @group BP_Messages_Thread
 */
class BP_Tests_BP_Messages_Thread extends BP_UnitTestCase {
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

		$threads = BP_Messages_Thread::get_current_threads_for_user( $u2, 'inbox', 'all', null, null, 'ar' );

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

		$threads = BP_Messages_Thread::get_current_threads_for_user( $u1, 'sentbox', 'all', null, null, 'ar' );

		$expected = array( $t2 );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}
}
