<?php

/**
 * @group friends
 * @group functions
 */
class BP_Tests_Friends_Functions extends BP_UnitTestCase {

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 * @group friends_accept_friendship
	 */
	public function test_requests_on_accept() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		// request friendship
		friends_add_friend( $u2, $u1 );
		friends_add_friend( $u3, $u1 );

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2, $u3 ), $requests );

		// accept friendship
		$old_user = get_current_user_id();
		$this->set_current_user( $u1 );
		friends_accept_friendship( friends_get_friendship_id( $u2, $u1 ) );

		// refetch request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u3 ), $requests );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 */
	public function test_requests_on_request() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		// request friendship
		friends_add_friend( $u2, $u1 );

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2 ), $requests );

		// request another friendship
		friends_add_friend( $u3, $u1 );

		// refetch request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2, $u3 ), $requests );
	}

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 * @group friends_withdraw_friendship
	 */
	public function test_requests_on_withdraw() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		// request friendship
		friends_add_friend( $u2, $u1 );

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2 ), $requests );

		// user 2 withdraws friendship
		$old_user = get_current_user_id();
		$this->set_current_user( $u2 );
		friends_withdraw_friendship( $u2, $u1 );

		// refetch request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array(), $requests );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 * @group friends_reject_friendship
	 */
	public function test_requests_on_reject() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		// request friendship
		friends_add_friend( $u2, $u1 );

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2 ), $requests );

		// user 1 rejects friendship
		$old_user = get_current_user_id();
		$this->set_current_user( $u1 );
		friends_reject_friendship( friends_get_friendship_id( $u2, $u1 ) );

		// refetch request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array(), $requests );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group friends_add_friend
	 */
	public function test_friends_add_friend_fail_on_self() {
		$u1 = $this->create_user();
		$this->assertFalse( friends_add_friend( $u1, $u1 ) );
	}

	/**
	 * @group friends_add_friend
	 */
	public function test_friends_add_friend_already_friends() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		friends_add_friend( $u1, $u2, true );

		$this->assertTrue( friends_add_friend( $u1, $u2 ) );
	}

	/**
	 * @group friends_check_friendship_status
	 */
	public function test_friends_check_friendship_status_in_members_loop() {
		$now = time();
		$u1 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 200 ),
		) );
		$u4 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );
		$u5 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 400 ),
		) );

		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, false );
		friends_add_friend( $u4, $u1, false );

		$old_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$found = array();
		if ( bp_has_members() ) : while ( bp_members() ) : bp_the_member();
			$found[ bp_get_member_user_id() ] = friends_check_friendship_status( $u1, bp_get_member_user_id() );
		endwhile; endif;

		$expected = array(
			$u1 => 'not_friends',
			$u2 => 'is_friend',
			$u3 => 'pending',
			$u4 => 'awaiting_response',
			$u5 => 'not_friends',
		);

		$this->assertSame( $expected, $found );

		// clean up
		$GLOBALS['members_template'] = null;
		$this->set_current_user( $old_user );
	}

	/**
	 * @group friends_check_friendship_status
	 */
	public function test_friends_check_friendship_status_not_in_members_loop() {
		$now = time();
		$u1 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 200 ),
		) );
		$u4 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );
		$u5 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 400 ),
		) );

		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, false );
		friends_add_friend( $u4, $u1, false );

		$found = array(
			$u1 => friends_check_friendship_status( $u1, $u1 ),
			$u2 => friends_check_friendship_status( $u1, $u2 ),
			$u3 => friends_check_friendship_status( $u1, $u3 ),
			$u4 => friends_check_friendship_status( $u1, $u4 ),
			$u5 => friends_check_friendship_status( $u1, $u5 ),
		);

		$expected = array(
			$u1 => 'not_friends',
			$u2 => 'is_friend',
			$u3 => 'pending',
			$u4 => 'awaiting_response',
			$u5 => 'not_friends',
		);

		$this->assertSame( $expected, $found );
	}
}
