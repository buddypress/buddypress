<?php

/**
 * @group core
 * @group BP_Core_User
 */
class BP_Tests_BP_Core_User_TestCases extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_querystring() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		$exclude_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_qs );
		$user_ids = wp_parse_id_list( wp_list_pluck( $users['users'], 'id' ) );

		$this->assertEquals( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_array() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		$exclude_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_array );
		$user_ids = wp_parse_id_list( wp_list_pluck( $users['users'], 'id' ) );

		$this->assertEquals( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_querystring() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );

		$include_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_qs );
		$user_ids = wp_parse_id_list( wp_list_pluck( $users['users'], 'id' ) );

		$this->assertEquals( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_array() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );


		$include_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		// typecast...ugh
		$user_ids = array_map( 'intval', $user_ids );

		$this->assertEquals( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 * @group get_users
	 * @group type
	 */
	public function test_type_alphabetical() {
		$u1 = $this->create_user( array(
			'display_name' => 'foo',
		) );
		$u2 = $this->create_user( array(
			'display_name' => 'bar',
		) );

		global $wpdb;

		$q = BP_Core_User::get_users( 'alphabetical' );
		$found = array_map( 'intval', wp_list_pluck( $q['users'], 'id' ) );

		$this->assertEquals( array( $u2, $u1 ), $found );
	}

	/**
	 * @group get_users_by_letter
	 */
	public function test_get_users_by_letter() {
		$u1 = $this->create_user( array(
			'display_name' => 'foo',
		) );
		$u2 = $this->create_user( array(
			'display_name' => 'bar',
		) );

		$q = BP_Core_User::get_users_by_letter( 'b' );
		$found = array_map( 'intval', wp_list_pluck( $q['users'], 'id' ) );

		$this->assertEquals( array( $u2 ), $found );
	}

	/**
	 * @group search_users
	 */
	public function test_search_users() {
		$u1 = $this->create_user( array(
			'display_name' => 'foo',
		) );
		$u2 = $this->create_user( array(
			'display_name' => 'bar',
		) );

		$q = BP_Core_User::search_users( 'ar' );
		$found = array_map( 'intval', wp_list_pluck( $q['users'], 'id' ) );

		$this->assertEquals( array( $u2 ), $found );
	}

	public function test_get_specific_users() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		$include_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_specific_users( $include_array );
		$user_ids = wp_parse_id_list( wp_list_pluck( $users['users'], 'id' ) );

		$this->assertEquals( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @group last_activity
	 */
	public function test_get_last_activity() {
		$u = $this->create_user();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );

		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';

		$this->assertEquals( $time, $found );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_store_in_cache() {
		$u = $this->create_user();
		$time = bp_core_current_time();

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u, 'bp_last_activity' );

		// prime cache
		$a = BP_Core_User::get_last_activity( $u );

		$this->assertSame( $a[ $u ], wp_cache_get( $u, 'bp_last_activity' ) );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_store_in_cache_multiple_users() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = bp_core_current_time();

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u1, 'bp_last_activity' );
		wp_cache_delete( $u2, 'bp_last_activity' );

		// prime cache
		$a = BP_Core_User::get_last_activity( array( $u1, $u2 ) );

		$this->assertSame( $a[ $u1 ], wp_cache_get( $u1, 'bp_last_activity' ) );
		$this->assertSame( $a[ $u2 ], wp_cache_get( $u2, 'bp_last_activity' ) );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_from_cache_single_user() {
		$u    = $this->create_user();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u, 'bp_last_activity' );

		// Prime cache
		$uncached = BP_Core_User::get_last_activity( $u );

		// Fetch again to get from the cache
		$cached = BP_Core_User::get_last_activity( $u );

		$this->assertSame( $uncached, $cached );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_in_cache_multiple_users() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u1, $time );
		BP_Core_User::update_last_activity( $u2, $time );

		// Cache is set during user creation. Clear to reflect actual pageload
		wp_cache_delete( $u1, 'bp_last_activity' );
		wp_cache_delete( $u2, 'bp_last_activity' );

		// Prime cache
		$uncached = BP_Core_User::get_last_activity( array( $u1, $u2 ) );

		// Second grab will be from the cache
		$cached = BP_Core_User::get_last_activity( array( $u1, $u2 ) );
		$cached_u1 = wp_cache_get( $u1, 'bp_last_activity' );

		$this->assertSame( $cached, $uncached );
	}

	/**
	 * @group last_activity
	 */
	public function test_update_last_activity() {
		$u = $this->create_user();
		$time = bp_core_current_time();
		$time2 = '1968-12-25 01:23:45';

		BP_Core_User::update_last_activity( $u, $time );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertEquals( $time, $found );

		BP_Core_User::update_last_activity( $u, $time2 );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertEquals( $time2, $found );
	}

	/**
	 * @group last_activity
	 */
	public function test_delete_last_activity() {
		$u = $this->create_user();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertEquals( $time, $found );

		BP_Core_User::delete_last_activity( $u );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertEquals( '', $found );
	}
}
