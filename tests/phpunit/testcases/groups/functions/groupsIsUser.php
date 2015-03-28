<?php

/**
 * Tests for the `groups_is_user_*()` functions.
 *
 * @group groups
 */
class BP_Tests_Groups_Functions_GroupsIsUser extends BP_UnitTestCase {
	static $user;
	static $admin_user;
	static $groups;

	public static function setUpBeforeClass() {
		$f = new BP_UnitTest_Factory();

		self::$user = $f->user->create( array(
			'user_login' => 'groups_is_user',
			'user_email' => 'groups_is_user@example.com',
		) );
		self::$admin_user = $f->user->create( array(
			'user_login' => 'groups_is_user_admin',
			'user_email' => 'groups_is_user_admin@example.com',
		) );
		self::$groups = $f->group->create_many( 3, array(
			'creator_id' => self::$admin_user,
		) );

		$now = time();

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

	public function test_groups_is_user_admin_expected_true() {
		$this->add_user_to_group( self::$user, self::$groups[0], array(
			'is_admin' => false,
		) );
		$this->add_user_to_group( self::$user, self::$groups[1], array(
			'is_admin' => true,
		) );

		$this->assertEquals( true, groups_is_user_admin( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_admin_expected_false() {
		$this->add_user_to_group( self::$user, self::$groups[0], array(
			'is_admin' => false,
		) );
		$this->add_user_to_group( self::$user, self::$groups[1], array(
			'is_admin' => true,
		) );

		$this->assertEquals( false, groups_is_user_admin( self::$user, self::$groups[0] ) );
	}

	public function test_groups_is_user_mod_expected_true() {
		$this->add_user_to_group( self::$user, self::$groups[0], array(
			'is_mod' => false,
		) );
		$this->add_user_to_group( self::$user, self::$groups[1], array(
			'is_mod' => true,
		) );

		$this->assertEquals( true, groups_is_user_mod( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_mod_expected_false() {
		$this->add_user_to_group( self::$user, self::$groups[0], array(
			'is_mod' => false,
		) );
		$this->add_user_to_group( self::$user, self::$groups[1], array(
			'is_mod' => true,
		) );

		$this->assertEquals( false, groups_is_user_mod( self::$user, self::$groups[0] ) );
	}

	public function test_groups_is_user_mod_should_return_false_when_user_is_also_banned() {
		$this->add_user_to_group( self::$user, self::$groups[0], array(
			'is_mod' => false,
		) );
		$this->add_user_to_group( self::$user, self::$groups[1], array(
			'is_mod' => true,
		) );

		$m = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m->ban();

		$this->assertEquals( false, groups_is_user_mod( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_member_expected_true() {
		$this->add_user_to_group( self::$user, self::$groups[1] );

		$this->assertEquals( true, groups_is_user_member( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_member_expected_false() {
		$this->add_user_to_group( self::$user, self::$groups[1] );

		$this->assertEquals( false, groups_is_user_member( self::$user, self::$groups[0] ) );
	}

	public function test_groups_is_user_member_should_return_false_when_user_is_also_banned() {
		$this->add_user_to_group( self::$user, self::$groups[1] );

		$m = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m->ban();

		$this->assertEquals( false, groups_is_user_member( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_banned_should_return_false_for_non_member() {
		$this->assertEquals( false, groups_is_user_banned( self::$user, self::$groups[1] ) );
	}

	/**
	 * Return values for these functions are terrible.
	 */
	public function test_groups_is_user_banned_should_return_false_for_non_banned_member() {
		$this->add_user_to_group( self::$user, self::$groups[1] );
		$this->assertEquals( 0, groups_is_user_banned( self::$user, self::$groups[1] ) );
	}

	public function test_groups_is_user_banned_should_return_true_for_banned_member() {
		$this->add_user_to_group( self::$user, self::$groups[1] );

		$m = new BP_Groups_Member( self::$user, self::$groups[1] );
		$m->ban();

		$this->assertEquals( true, groups_is_user_banned( self::$user, self::$groups[1] ) );
	}
}
