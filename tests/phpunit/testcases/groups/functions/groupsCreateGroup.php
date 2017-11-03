<?php

/**
 * Tests for the `groups_create_group()` function.
 *
 * @group groups
 */
class BP_Tests_Groups_Functions_GroupsCreateGroup extends BP_UnitTestCase {
	static $user_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create();
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$user_id );
	}

	/**
	 * @ticket BP7619
	 */
	public function test_should_respect_creator_id() {
		$old_user_id = bp_loggedin_user_id();
		$this->set_current_user( self::$user_id );

		$group_id = groups_create_group( array(
			'name' => 'Foo',
			'creator_id' => self::$user_id + 1,
		) );

		$group = groups_get_group( $group_id );

		$this->set_current_user( $old_user_id );

		$this->assertSame( self::$user_id + 1, $group->creator_id );
	}

	/**
	 * @ticket BP7619
	 */
	public function test_creator_id_should_be_fall_back_to_loggedin_user_for_new_group() {
		$old_user_id = bp_loggedin_user_id();
		$this->set_current_user( self::$user_id );

		$group_id = groups_create_group( array(
			'name' => 'Foo',
		) );

		$group = groups_get_group( $group_id );

		$this->set_current_user( $old_user_id );

		$this->assertSame( self::$user_id, $group->creator_id );
	}

	/**
	 * @ticket BP7619
	 */
	public function test_creator_id_should_be_fall_back_to_existing_creator_id_for_existing_group() {
		$group_id = self::factory()->group->create( array(
			'creator_id' => self::$user_id + 1,
		) );

		$old_user_id = bp_loggedin_user_id();
		$this->set_current_user( self::$user_id );

		$group_id = groups_create_group( array(
			'group_id' => $group_id,
		) );

		$group = groups_get_group( $group_id );

		$this->set_current_user( $old_user_id );

		$this->assertSame( self::$user_id + 1, $group->creator_id );
	}
}
