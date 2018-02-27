<?php

/**
 * @group friends
 * @group functions
 */
class BP_Tests_Friends_Functions extends BP_UnitTestCase {

	protected $filter_fired;

	public function setUp() {
		parent::setUp();
		$this->filter_fired = '';
	}

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 * @group friends_accept_friendship
	 */
	public function test_requests_on_accept() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		// request friendship
		friends_add_friend( $u2, $u1 );
		friends_add_friend( $u3, $u1 );

		// Set the time of the earlier friendship for reliable ordering of the results.
		$fid = friends_get_friendship_id( $u2, $u1 );
		$friendship = new BP_Friends_Friendship( $fid, false, false );
		$friendship->date_created = date( 'Y-m-d H:i:s', time() - 60 );
		$friendship->save();

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u3, $u2 ), $requests );

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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		// request friendship
		friends_add_friend( $u2, $u1 );
		// Set the time of the earlier friendship for reliable ordering of the results.
		$fid = friends_get_friendship_id( $u2, $u1 );
		$friendship = new BP_Friends_Friendship( $fid, false, false );
		$friendship->date_created = date( 'Y-m-d H:i:s', time() - 60 );
		$friendship->save();

		// get request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u2 ), $requests );

		// request another friendship
		friends_add_friend( $u3, $u1 );

		// refetch request count for user 1 and assert
		$requests = friends_get_friendship_request_user_ids( $u1 );
		$this->assertEquals( array( $u3, $u2 ), $requests );
	}

	/**
	 * @group friends_get_friendship_request_user_ids
	 * @group friends_add_friend
	 * @group friends_withdraw_friendship
	 */
	public function test_requests_on_withdraw() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

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
		$u1 = self::factory()->user->create();
		$this->assertFalse( friends_add_friend( $u1, $u1 ) );
	}

	/**
	 * @group friends_add_friend
	 */
	public function test_friends_add_friend_already_friends() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		friends_add_friend( $u1, $u2, true );

		$this->assertTrue( friends_add_friend( $u1, $u2 ) );
	}

	/**
	 * @group friends_check_friendship_status
	 */
	public function test_friends_check_friendship_status_in_members_loop() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 200 ),
		) );
		$u4 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );
		$u5 = self::factory()->user->create( array(
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
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 200 ),
		) );
		$u4 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );
		$u5 = self::factory()->user->create( array(
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

	/**
	 * @group friends_add_friend
	 */
	public function test_friends_add_friend_friends_friendship_requested() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		add_filter( 'friends_friendship_requested', array( $this, 'friends_friendship_filter_callback' ) );
		$n = friends_add_friend( $u1, $u2, false );
		remove_filter( 'friends_friendship_requested', array( $this, 'friends_friendship_filter_callback' ) );

		$this->assertSame( 'friends_friendship_requested', $this->filter_fired );
	}

	/**
	 * @group friends_add_friend
	 */
	public function test_friends_add_friend_friends_friendship_accepted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		add_filter( 'friends_friendship_accepted', array( $this, 'friends_friendship_filter_callback' ) );
		$n = friends_add_friend( $u1, $u2, true );
		remove_filter( 'friends_friendship_accepted', array( $this, 'friends_friendship_filter_callback' ) );

		$this->assertSame( 'friends_friendship_accepted', $this->filter_fired );
	}

	public function friends_friendship_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}

	/**
	 * @group friendship_caching
	 */
	public function test_friends_check_friendship_should_hit_friendship_object_cache() {
		global $wpdb;
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		friends_add_friend( $u1, $u2, true );

		friends_check_friendship_status( $u1, $u2 );
		$first_query_count = $wpdb->num_queries;

		/*
		 * This should access the previous friendship check's cached items.
		 */
		friends_check_friendship_status( $u2, $u1 );

		$this->assertEquals( $first_query_count, $wpdb->num_queries );
	}

	public function test_friends_get_recently_active() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$u4 = self::factory()->user->create();

		// request friendship
		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );
		friends_add_friend( $u1, $u4, true );

		bp_update_user_last_activity( $u2, date( 'Y-m-d H:i:s', time() - ( 2 * DAY_IN_SECONDS ) ) );
		bp_update_user_last_activity( $u3, date( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) ) );
		bp_update_user_last_activity( $u4, date( 'Y-m-d H:i:s', time() - ( 3 * DAY_IN_SECONDS ) ) );

		$recent = friends_get_recently_active( $u1 );

		$this->assertEquals( $recent['users'][0]->id, $u2 );
		$this->assertEquals( $recent['users'][1]->id, $u4 );
		$this->assertEquals( $recent['users'][2]->id, $u3 );
	}

	public function test_friends_get_alphabetically() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		// request friendship
		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );

		$field_id = bp_xprofile_fullname_field_id();
		xprofile_set_field_data( $field_id, $u2, 'Dave Lister' );
		xprofile_set_field_data( $field_id, $u3, 'Arnold Rimmer' );
		xprofile_sync_wp_profile( $u2 );
		xprofile_sync_wp_profile( $u3 );

		$alpha = friends_get_alphabetically( $u1 );

		$this->assertEquals( $alpha['users'][0]->id, $u3 );
		$this->assertEquals( $alpha['users'][1]->id, $u2 );
	}

	public function test_friends_get_newest() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$u4 = self::factory()->user->create();

		// request friendship
		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );
		friends_add_friend( $u1, $u4, true );

		bp_update_user_last_activity( $u2, date( 'Y-m-d H:i:s', time() - ( 2 * DAY_IN_SECONDS ) ) );
		bp_update_user_last_activity( $u3, date( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) ) );
		bp_update_user_last_activity( $u4, date( 'Y-m-d H:i:s', time() - ( 3 * DAY_IN_SECONDS ) ) );

		$newest = friends_get_newest( $u1 );

		$this->assertEquals( $newest['users'][0]->id, $u4 );
		$this->assertEquals( $newest['users'][1]->id, $u3 );
		$this->assertEquals( $newest['users'][2]->id, $u2 );
	}
}
