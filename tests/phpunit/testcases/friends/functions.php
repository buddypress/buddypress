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
}
