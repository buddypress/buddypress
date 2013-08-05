<?php

/**
 * @group core
 * @group BP_User_Query
 */
class BP_Tests_BP_User_Query_TestCases extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
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

	public function test_bp_user_query_sort_by_popular() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();

		bp_update_user_meta( $u1, bp_get_user_meta_key( 'total_friend_count' ), '5' );
		bp_update_user_meta( $u2, bp_get_user_meta_key( 'total_friend_count' ), '90' );
		bp_update_user_meta( $u3, bp_get_user_meta_key( 'total_friend_count' ), '101' );
		bp_update_user_meta( $u4, bp_get_user_meta_key( 'total_friend_count' ), '3002' );

		$q = new BP_User_Query( array(
			'type' => 'popular',
		) );

		$users = is_array( $q->results ) ? array_values( $q->results ) : array();
		$user_ids = wp_parse_id_list( wp_list_pluck( $users, 'ID' ) );

		$expected = array( $u4, $u3, $u2, $u1 );
		$this->assertEquals( $expected, $user_ids );
	}

	/**
	 * @group online
	 */
	public function test_bp_user_query_type_online() {
		$now = time();
		$u1 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*13 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*16 ),
		) );

		$q = new BP_User_Query( array(
			'type' => 'online',
		) );

		$users = is_array( $q->results ) ? array_values( $q->results ) : array();
		$user_ids = wp_parse_id_list( wp_list_pluck( $users, 'ID' ) );
		$this->assertEquals( array( $u1, $u2 ), $user_ids );
	}

	/**
	 * @group online
	 */
	public function test_bp_user_query_type_online_five_minute_interval() {
		$now = time();
		$u1 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*4 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*6 ),
		) );

		add_filter( 'bp_user_query_online_interval', create_function( '', 'return 5;' ) );

		$q = new BP_User_Query( array(
			'type' => 'online',
		) );

		$users = is_array( $q->results ) ? array_values( $q->results ) : array();
		$user_ids = wp_parse_id_list( wp_list_pluck( $users, 'ID' ) );
		$this->assertEquals( array( $u1, $u2 ), $user_ids );
	}


	public function test_bp_user_query_search_with_apostrophe() {
		// Apostrophe. Search_terms must escaped to mimic POST payload
		$user_id = $this->create_user();
		xprofile_set_field_data( 1, $user_id, "Foo'Bar" );
		$q = new BP_User_Query( array( 'search_terms' => "oo\'Ba", ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );
	}

	public function test_bp_user_query_search_with_percent_sign() {

		// LIKE special character: %
		$user_id = $this->create_user();
		xprofile_set_field_data( 1, $user_id, "Foo%Bar" );
		$q = new BP_User_Query( array( 'search_terms' => "oo%Bar", ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );

	}

	public function test_bp_user_query_search_with_underscore() {

		// LIKE special character: _
		$user_id = $this->create_user();
		xprofile_set_field_data( 1, $user_id, "Foo_Bar" );
		$q = new BP_User_Query( array( 'search_terms' => "oo_Bar", ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );
	}

	/**
	 * @group exclude
	 */
	public function test_bp_user_query_with_exclude() {
		// Grab list of existing users who should also be excluded
		global $wpdb;
		$existing_users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );

		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$exclude = array_merge( array( $u1 ), $existing_users );
		$q = new BP_User_Query( array( 'exclude' => $exclude, ) );

		$found_user_ids = null;
		if ( ! empty( $q->results ) ) {
			$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );
		}

		$this->assertEquals( array( $u2 ), $found_user_ids );
	}
}
