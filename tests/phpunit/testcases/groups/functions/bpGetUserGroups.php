<?php

/**
 * @group groups
 */
class BP_Tests_Groups_Functions_BpGetUserGroups extends BP_UnitTestCase {
	static $user;
	static $admin_user;
	static $groups;

	public function setUp() {
		parent::setUp();
		groups_remove_member( self::$user, self::$groups[2] );
	}

	public static function wpSetUpBeforeClass( $f ) {
		$f = new BP_UnitTest_Factory();

		self::$user = $f->user->create( array(
			'user_login' => 'bp_get_user_groups_user',
			'user_email' => 'bp_get_user_groups_user@example.com',
		) );
		self::$admin_user = $f->user->create( array(
			'user_login' => 'bp_get_user_groups_admin_user',
			'user_email' => 'bp_get_user_groups_admin_user@example.com',
		) );
		self::$groups = $f->group->create_many( 4, array(
			'creator_id' => self::$admin_user,
		) );

		$now = time();
		self::add_user_to_group( self::$user, self::$groups[1], array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 10 ),
		) );
		self::add_user_to_group( self::$user, self::$groups[0], array(
			'date_modified' => date( 'Y-m-d H:i:s', $now ),
		) );

		self::commit_transaction();
	}

	public static function tearDownAfterClass() {
		foreach ( self::$groups as $group ) {
			groups_delete_group( $group );
		}

		if ( is_multisite() ) {
			wpmu_delete_user( self::$user );
			wpmu_delete_user( self::$admin_user );
		} else {
			wp_delete_user( self::$user );
			wp_delete_user( self::$admin_user );
		}

		self::commit_transaction();
	}

	public function test_default_params() {
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( array( self::$groups[0], self::$groups[1] ), wp_list_pluck( $found, 'group_id' ) );

		foreach ( $found as $index => $f ) {
			$this->assertInternalType( 'int', $index );
			$this->assertInternalType( 'object', $f );
			$this->assertInternalType( 'int', $f->group_id );
			$this->assertSame( $index, $f->group_id );
		}
	}

	public function test_is_confirmed_true() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_confirmed' => false,
		) );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => true,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_confirmed_false() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_confirmed' => false,
		) );

		$expected = array( self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => false,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_confirmed_null() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_confirmed' => false,
		) );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_banned_true() {
		$this->add_user_to_group( self::$user, self::$groups[2] );
		$member = new BP_Groups_Member( self::$user, self::$groups[2] );
		$member->ban();

		$expected = array( self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_banned' => true,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_banned_false() {
		$this->add_user_to_group( self::$user, self::$groups[2] );
		$member = new BP_Groups_Member( self::$user, self::$groups[2] );
		$member->ban();

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_banned' => false,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_banned_null() {
		$this->add_user_to_group( self::$user, self::$groups[2] );
		$member = new BP_Groups_Member( self::$user, self::$groups[2] );
		$member->ban();

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_banned' => null,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_admin_true() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_admin' => true,
		) );

		$expected = array( self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_admin' => true,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_admin_false() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_admin' => true,
		) );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_admin' => false,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_admin_null() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_admin' => false,
		) );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_admin' => null,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_mod_true() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_mod' => true,
		) );

		$expected = array( self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_mod' => true,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_mod_false() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_mod' => true,
		) );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_mod' => false,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_is_mod_null() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'is_mod' => false,
		) );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_mod' => null,
		) );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'group_id' ) );
	}

	public function test_orderby_should_default_to_group_id() {
		$expected = bp_get_user_groups( self::$user );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'group_id',
		) );

		$this->assertEquals( $expected, $found );
	}

	public function test_orderby_group_id() {
		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'group_id',
		) );

		$this->assertSame( $expected, array_keys( $found ) );
	}

	public function test_orderby_id() {
		$expected = array( self::$groups[1], self::$groups[0] );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'id',
		) );

		$this->assertSame( $expected, array_values( wp_list_pluck( $found, 'group_id' ) ) );
	}

	public function test_orderby_date_modified() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'date_modified' => '2015-03-14 09:26:53',
		) );

		$expected = array( self::$groups[2], self::$groups[1], self::$groups[0] );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'date_modified',
		) );

		$this->assertSame( $expected, array_values( wp_list_pluck( $found, 'group_id' ) ) );
	}

	public function test_orderby_group_id_with_order_desc() {
		$expected = array( self::$groups[1], self::$groups[0] );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'group_id',
			'order' => 'DESC',
		) );

		$this->assertSame( $expected, array_keys( $found ) );
	}

	public function test_orderby_date_modified_with_order_desc() {
		$this->add_user_to_group( self::$user, self::$groups[2], array(
			'date_modified' => '2015-03-14 09:26:53',
		) );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'orderby' => 'date_modified',
			'order' => 'DESC',
		) );

		$this->assertSame( $expected, array_values( wp_list_pluck( $found, 'group_id' ) ) );
	}

	/**
	 * @group cache
	 */
	public function test_results_should_be_cached() {
		global $wpdb;

		$g1 = bp_get_user_groups( self::$user );

		$num_queries = $wpdb->num_queries;
		$g2 = bp_get_user_groups( self::$user );

		$this->assertSame( $num_queries, $wpdb->num_queries );
		$this->assertEquals( $g1, $g2 );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_join() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		groups_join_group( self::$groups[2], self::$user );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_leave() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		groups_leave_group( self::$groups[1], self::$user );

		$expected = array( self::$groups[0] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_remove() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		$m = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m->remove();

		$expected = array( self::$groups[0] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_ban() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		$m = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m->ban();

		$expected = array( self::$groups[0] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_unban() {
		$m1 = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m1->ban();

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		$m2 = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m2->unban();

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_invite() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		groups_invite_user( array(
			'user_id' => self::$user,
			'group_id' => self::$groups[2],
			'inviter_id' => self::$admin_user,
		) );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_uninvite() {
		groups_invite_user( array(
			'user_id' => self::$user,
			'group_id' => self::$groups[2],
			'inviter_id' => self::$admin_user,
		) );

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		groups_uninvite_user( self::$user, self::$groups[2] );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_invite_acceptance() {
		groups_invite_user( array(
			'user_id' => self::$user,
			'group_id' => self::$groups[2],
			'inviter_id' => self::$admin_user,
		) );

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		groups_accept_invite( self::$user, self::$groups[2] );

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_invite_reject() {
		groups_invite_user( array(
			'user_id' => self::$user,
			'group_id' => self::$groups[2],
			'inviter_id' => self::$admin_user,
		) );

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		groups_reject_invite( self::$user, self::$groups[2] );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_invite_delete() {
		groups_invite_user( array(
			'user_id' => self::$user,
			'group_id' => self::$groups[2],
			'inviter_id' => self::$admin_user,
		) );

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		groups_delete_invite( self::$user, self::$groups[2] );

		$expected = array( self::$groups[0], self::$groups[1] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_request() {
		// Populate cache.
		$g1 = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		// For `wp_mail()`.
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : null;
		$_SERVER['SERVER_NAME'] = '';

		groups_send_membership_request( self::$user, self::$groups[2] );

		// For `wp_mail()`.
		if ( is_null( $server_name ) ) {
			unset( $_SERVER['SERVER_NAME'] );
		} else {
			$_SERVER['SERVER_NAME'] = $server_name;
		}

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user, array(
			'is_confirmed' => null,
		) );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_should_be_invalidated_on_group_request_acceptance() {
		// For `wp_mail()`.
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : null;
		$_SERVER['SERVER_NAME'] = '';

		groups_send_membership_request( self::$user, self::$groups[2] );

		// Populate cache.
		$g1 = bp_get_user_groups( self::$user );

		$m = new BP_Groups_Member( self::$user, self::$groups[2] );

		groups_accept_membership_request( $m->id, self::$user, self::$groups[2] );

		// For `wp_mail()`.
		if ( is_null( $server_name ) ) {
			unset( $_SERVER['SERVER_NAME'] );
		} else {
			$_SERVER['SERVER_NAME'] = $server_name;
		}

		$expected = array( self::$groups[0], self::$groups[1], self::$groups[2] );
		$found = bp_get_user_groups( self::$user );

		$this->assertEqualSets( $expected, array_keys( $found ) );
	}
}
