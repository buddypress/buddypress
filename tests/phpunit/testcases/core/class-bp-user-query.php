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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$u4 = $this->factory->user->create();
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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$u4 = $this->factory->user->create();

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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$u4 = $this->factory->user->create();

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
		$u1 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*13 ),
		) );
		$u3 = $this->factory->user->create( array(
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
		$u1 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*4 ),
		) );
		$u3 = $this->factory->user->create( array(
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
		$user_id = $this->factory->user->create();
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
		$user_id = $this->factory->user->create();
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
		$user_id = $this->factory->user->create();
		xprofile_set_field_data( 1, $user_id, "Foo_Bar" );
		$q = new BP_User_Query( array( 'search_terms' => "oo_Bar", ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );
	}

	public function test_bp_user_query_search_with_ampersand_sign() {

		// LIKE special character: &
		$user_id = $this->factory->user->create();
		xprofile_set_field_data( 1, $user_id, "a&mpersand" );
		$q = new BP_User_Query( array( 'search_terms' => "a&m", ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );

	}

	/**
	 * @group search_terms
	 */
	public function test_bp_user_query_search_core_fields() {
		$user_id = $this->factory->user->create( array(
			'user_login' => 'foo',
		) );
		xprofile_set_field_data( 1, $user_id, "Bar" );
		$q = new BP_User_Query( array( 'search_terms' => 'foo', ) );

		$found_user_id = null;
		if ( ! empty( $q->results ) ) {
			$found_user = array_pop( $q->results );
			$found_user_id = $found_user->ID;
		}

		$this->assertEquals( $user_id, $found_user_id );
	}

	public function test_bp_user_query_search_wildcards() {
		$u1 = $this->factory->user->create( array(
			'user_login' => 'xfoo',
		) );
		xprofile_set_field_data( 1, $u1, "Bar" );
		$q1 = new BP_User_Query( array( 'search_terms' => 'foo', 'search_wildcard' => 'left' ) );

		$u2 = $this->factory->user->create( array(
			'user_login' => 'foox',
		) );
		xprofile_set_field_data( 1, $u2, "Bar" );
		$q2 = new BP_User_Query( array( 'search_terms' => 'foo', 'search_wildcard' => 'right' ) );

		$u3 = $this->factory->user->create( array(
			'user_login' => 'xfoox',
		) );
		xprofile_set_field_data( 1, $u3, "Bar" );
		$q3 = new BP_User_Query( array( 'search_terms' => 'foo', 'search_wildcard' => 'both' ) );

		$this->assertNotEmpty( $q1->results );
		$q1 = array_pop( $q1->results );
		$this->assertEquals( $u1, $q1->ID );

		$this->assertNotEmpty( $q2->results );
		$q2 = array_pop( $q2->results );
		$this->assertEquals( $u2, $q2->ID );

		$this->assertNotEmpty( $q3->results );
		foreach ( $q3->results as $user ) {
			$this->assertTrue( in_array( $user->ID, array( $u1, $u2, $u3 ) ) );
		}
	}

	/**
	 * @group exclude
	 */
	public function test_bp_user_query_with_exclude() {
		// Grab list of existing users who should also be excluded
		global $wpdb;
		$existing_users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$exclude = array_merge( array( $u1 ), $existing_users );
		$q = new BP_User_Query( array( 'exclude' => $exclude, ) );

		$found_user_ids = null;
		if ( ! empty( $q->results ) ) {
			$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );
		}

		$this->assertEquals( array( $u2 ), $found_user_ids );
	}

	/**
	 * @group type
	 * @group spam
	 */
	public function test_bp_user_query_type_alphabetical_spam_xprofileon() {
		if ( is_multisite() ) {
			return;
		}

		// Make sure xprofile is on
		$xprofile_toggle = isset( buddypress()->active_components['xprofile'] );
		buddypress()->active_components['xprofile'] = 1;
		add_filter( 'bp_disable_profile_sync', '__return_false' );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		global $wpdb;
		bp_core_process_spammer_status( $u1, 'spam' );

		$q = new BP_User_Query( array( 'type' => 'alphabetical', ) );

		// Restore xprofile setting
		if ( $xprofile_toggle ) {
			buddypress()->active_components['xprofile'] = 1;
		} else {
			unset( buddypress()->active_components['xprofile'] );
		}
		remove_filter( 'bp_disable_profile_sync', '__return_false' );

		$found_user_ids = null;

		if ( ! empty( $q->results ) ) {
			$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );
		}

		// Do a assertNotContains because there are weird issues with user #1 as created by WP
		$this->assertNotContains( $u1, $found_user_ids );
	}

	/**
	 * @group type
	 * @group spam
	 */
	public function test_bp_user_query_type_alphabetical_spam_xprofileoff() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// Make sure xprofile and profile sync are off
		$xprofile_toggle = isset( buddypress()->active_components['xprofile'] );
		buddypress()->active_components['xprofile'] = 0;
		add_filter( 'bp_disable_profile_sync', '__return_false' );

		bp_core_process_spammer_status( $u1, 'spam' );

		$q = new BP_User_Query( array( 'type' => 'alphabetical', ) );

		// Restore xprofile setting
		if ( $xprofile_toggle ) {
			buddypress()->active_components['xprofile'] = 1;
		} else {
			unset( buddypress()->active_components['xprofile'] );
		}
		remove_filter( 'bp_disable_profile_sync', '__return_false' );

		$found_user_ids = null;

		if ( ! empty( $q->results ) ) {
			$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );
		}

		// Do a assertNotContains because there are weird issues with user #1 as created by WP
		$this->assertNotContains( $u1, $found_user_ids );
	}

	/**
	 * @group meta
	 * @group BP5904
	 */
	public function test_bp_user_query_with_user_meta_argument() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		bp_update_user_meta( $u2, 'foo', 'bar' );

		$q = new BP_User_Query( array(
			'meta_key'        => 'foo',
			'meta_value'      => 'bar',
		) );

		$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );

		// Do a assertNotContains because there are weird issues with user #1 as created by WP
		$this->assertNotContains( $u1, $found_user_ids );
		$this->assertEquals( array( $u2 ), $found_user_ids );
	}

	/**
	 * @group meta
	 * @group BP5904
	 */
	public function test_bp_user_query_with_user_meta_argument_no_user() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$q = new BP_User_Query( array(
			'meta_key'        => 'foo',
			'meta_value'      => 'bar',
		) );

		$found_user_ids = array_values( wp_parse_id_list( wp_list_pluck( $q->results, 'ID' ) ) );

		$this->assertEmpty( $found_user_ids );
	}

	/**
	 * @group member_types
	 */
	public function test_member_type_single_value() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		$q = new BP_User_Query( array(
			'member_type' => 'bar',
		) );

		$found = array_values( wp_list_pluck( $q->results, 'ID' ) );
		$this->assertEquals( array( $users[1] ), $found );
	}

	/**
	 * @group member_types
	 */
	public function test_member_type_array_with_single_value() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		$q = new BP_User_Query( array(
			'member_type' => array( 'bar' ),
		) );

		$found = array_values( wp_list_pluck( $q->results, 'ID' ) );
		$this->assertEquals( array( $users[1] ), $found );
	}

	/**
	 * @group member_types
	 */
	public function test_member_type_comma_separated_values() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		$q = new BP_User_Query( array(
			'member_type' => 'foo, bar',
		) );

		$found = array_values( wp_list_pluck( $q->results, 'ID' ) );
		$this->assertEqualSets( array( $users[0], $users[1] ), $found );
	}

	/**
	 * @group member_types
	 */
	public function test_member_type_array_with_multiple_values() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		$q = new BP_User_Query( array(
			'member_type' => array( 'foo', 'bar' ),
		) );

		$found = array_values( wp_list_pluck( $q->results, 'ID' ) );
		$this->assertEqualSets( array( $users[0], $users[1] ), $found );
	}

	/**
	 * @group member_types
	 */
	public function test_member_type_comma_separated_values_should_discard_non_existent_taxonomies() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		$q = new BP_User_Query( array(
			'member_type' => 'foo, baz',
		) );

		$found = array_values( wp_list_pluck( $q->results, 'ID' ) );
		$this->assertEqualSets( array( $users[0] ), $found );
	}


	/**
	 * @group cache
	 * @group member_types
	 */
	public function test_member_type_should_be_prefetched_into_cache_during_user_query() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = $this->factory->user->create_many( 4 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );
		bp_set_member_type( $users[2], 'foo' );

		$q = new BP_User_Query( array(
			'include' => $users,
		) );

		$this->assertSame( array( 'foo' ), wp_cache_get( $users[0], 'bp_member_type' ) );
		$this->assertSame( array( 'bar' ), wp_cache_get( $users[1], 'bp_member_type' ) );
		$this->assertSame( array( 'foo' ), wp_cache_get( $users[2], 'bp_member_type' ) );
		$this->assertSame( '', wp_cache_get( $users[3], 'bp_member_type' ) );
	}
}
