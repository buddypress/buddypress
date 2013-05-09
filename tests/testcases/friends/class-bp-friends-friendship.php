<?php

/**
 * @group friends
 */
class BP_Tests_BP_Friends_Friendship_TestCases extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	public function test_search_friends() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );

		$friends = BP_Friends_Friendship::search_friends( 'Coo', $u1 );
		$this->assertEquals( array( $u2 ), $friends['friends'] );
	}

	public function test_get_bulk_last_active() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );

		$friends = BP_Friends_Friendship::get_bulk_last_active( array( $u1, $u2, $u3, 'junk' ) );
		$friend_ids = wp_list_pluck( $friends, 'user_id' );
		$this->assertEquals( array( $u1, $u3, $u2 ), $friend_ids );
	}

	public function test_search_users() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		xprofile_set_field_data( 1, $u1, 'Freedom Isn\'t Free' );
		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		// Needs a user_id param though it does nothing
		$friends = BP_Friends_Friendship::search_users( 'Coo', 1 );
		$this->assertEquals( array( $u2 ), $friends );
	}

	public function test_search_users_count() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		xprofile_set_field_data( 1, $u1, 'Freedom Isn\'t Free' );
		xprofile_set_field_data( 1, $u2, 'Cool Dude' );
		xprofile_set_field_data( 1, $u3, 'Rock And Roll America Yeah' );

		// Needs a user_id param though it does nothing
		$friends = BP_Friends_Friendship::search_users_count( 'Coo' );
		$this->assertEquals( 1, $friends );
	}



}
