<?php

/**
 * @group friends
 */
class BP_Tests_BP_Friends_Friendship_TestCases extends BP_UnitTestCase {
	public function test_search_friends() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );

		$friends = BP_Friends_Friendship::search_friends( 'Coo', $u1 );
		$this->assertEquals( array( $u2 ), $friends['friends'] );
	}

	/**
	 * @ticket BP6546
	 */
	public function test_search_friends_with_xprofile_inactive() {
		$this->deactivate_component( 'xprofile' );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		add_user_meta( $u2, 'nickname', 'Cool Dude' );
		add_user_meta( $u3, 'nickname', 'Rock And Roll America Yeah' );

		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );

		$friends = BP_Friends_Friendship::search_friends( 'Coo', $u1 );
		$this->assertEquals( array( $u2 ), $friends['friends'] );
	}

	public function test_get_bulk_last_active() {
		$u1 = $this->factory->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = $this->factory->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );

		$friends = BP_Friends_Friendship::get_bulk_last_active( array( $u1, $u2, $u3, 'junk' ) );
		$friend_ids = wp_list_pluck( $friends, 'user_id' );
		$this->assertEquals( array( $u1, $u3, $u2 ), $friend_ids );
	}

	public function test_search_users() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		xprofile_set_field_data( 1, $u1, 'Freedom Isn\'t Free' );
		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		// Needs a user_id param though it does nothing
		$friends = BP_Friends_Friendship::search_users( 'Coo', 1 );
		$this->assertEquals( array( $u2 ), $friends );
	}

	public function test_search_users_count() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();

		xprofile_set_field_data( 1, $u1, 'Freedom Isn\'t Free' );
		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		// Needs a user_id param though it does nothing
		$friends = BP_Friends_Friendship::search_users_count( 'Coo' );
		$this->assertEquals( 1, $friends );
	}

	/**
	 * @group check_is_friend
	 */
	public function test_check_is_friend_not_friends() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->assertEquals( 'not_friends', BP_Friends_Friendship::check_is_friend( $u1, $u2 ) );
	}

	/**
	 * @group check_is_friend
	 */
	public function test_check_is_friend_pending() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		friends_add_friend( $u1, $u2, false );
		$this->assertEquals( 'pending', BP_Friends_Friendship::check_is_friend( $u1, $u2 ) );
	}

	/**
	 * @group check_is_friend
	 */
	public function test_check_is_friend_awaiting_response() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		friends_add_friend( $u1, $u2, false );
		$this->assertEquals( 'awaiting_response', BP_Friends_Friendship::check_is_friend( $u2, $u1 ) );
	}

	/**
	 * @group check_is_friend
	 */
	public function test_check_is_friend_is_friend() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		friends_add_friend( $u1, $u2, true );
		$this->assertEquals( 'is_friend', BP_Friends_Friendship::check_is_friend( $u1, $u2 ) );
	}

	/**
	 * @group BP6247
	 */
	public function test_save_method_should_update_existing_row() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$friendship = new BP_Friends_Friendship();
		$friendship->initiator_user_id = $u1;
		$friendship->friend_user_id = $u2;
		$friendship->is_confirmed = 0;
		$friendship->is_limited = 0;
		$friendship->date_created = bp_core_current_time();
		$friendship->is_confirmed = 1;
		$friendship->save();

		$fid = $friendship->id;

		$f = new BP_Friends_Friendship( $fid );
		$f->is_confirmed = 1;
		$f->save();

		$f2 = new BP_Friends_Friendship( $fid );
		$this->assertEquals( 1, $f2->is_confirmed );
	}
}
