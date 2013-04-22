<?php

/**
 * @group core
 */
class BP_Tests_Core_Classes extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( $this->old_current_user );
	}

	/**
	 * Checks that user_id returns friends
	 */
	public function test_bp_user_query_friends() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		friends_add_friend( $u1, $u2, true );

		$q = new BP_User_Query( array(
			'user_id' => $u2,
		) );

		$friends = is_array( $q->results ) ? array_values( $q->results ) : array();
		$friend_ids = wp_list_pluck( $friends, 'ID' );
		$this->assertEquals( $friend_ids, array( $u1 ) );
	}

	/**
	 * @ticket 4938
	 */
	public function test_bp_user_query_friends_with_include() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();
		friends_add_friend( $u1, $u2, true );
		friends_add_friend( $u1, $u3, true );

		$q = new BP_User_Query( array(
			'user_id' => $u1,

			// Represents an independent filter passed by a plugin
			// u4 is not a friend of u1 and should not be returned
			'include' => array( $u2, $u4 ),
		) );

		$friends = is_array( $q->results ) ? array_values( $q->results ) : array();
		$friend_ids = wp_list_pluck( $friends, 'ID' );
		$this->assertEquals( $friend_ids, array( $u2 ) );
	}

	public function test_bp_user_query_friends_with_include_but_zero_friends() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();

		$q = new BP_User_Query( array(
			'user_id' => $u1,

			// Represents an independent filter passed by a plugin
			// u4 is not a friend of u1 and should not be returned
			'include' => array( $u2, $u4 ),
		) );

		$friends = is_array( $q->results ) ? array_values( $q->results ) : array();
		$friend_ids = wp_list_pluck( $friends, 'ID' );
		$this->assertEquals( $friend_ids, array() );
	}

	public function test_bp_user_query_include_on_user_page() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$q = new BP_User_Query( array(
			// 'user_id' parameter is passed to emulate a displayed user page
			// @see https://buddypress.trac.wordpress.org/browser/tags/1.7/bp-members/bp-members-template.php#L277
			'user_id' => $u1,

			// Get all user IDs; we want to return just these IDs
			'include' => array( $u1, $u2 ),
		) );

		$users = is_array( $q->results ) ? array_values( $q->results ) : array();
		$user_ids = wp_list_pluck( $users, 'ID' );
		$this->assertEquals( $user_ids, array( $u2, $u1 ) );
	}

	public function test_bp_user_query_friendship_requests() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		friends_add_friend( $u1, $u2 );

		// emulate bp_has_members( 'type=alphabetical&include=' . bp_get_friendship_requests() )
		$q = new BP_User_Query( array(
			// 'user_id' parameter is passed to emulate a displayed user page
			// @see https://buddypress.trac.wordpress.org/browser/tags/1.7/bp-members/bp-members-template.php#L277
			'user_id' => $u2,

			// get friendship requests for $u2
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $q->results ) ? array_values( $q->results ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( $request_ids, array( $u1 ) );
	}
}
