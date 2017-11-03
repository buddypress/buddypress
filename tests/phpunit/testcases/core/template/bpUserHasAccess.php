<?php
/**
 * @group core
 * @group template
 */
class BP_Tests_Core_Template_BpUserHasAccess extends BP_UnitTestCase {
	public function test_should_return_true_for_bp_moderate_user() {
		$users = self::factory()->user->create_many( 2 );

		$this->grant_bp_moderate( $users[0] );
		$this->set_current_user( $users[0] );

		$this->go_to( bp_core_get_user_domain( $users[1] ) );

		$this->assertTrue( bp_user_has_access( $users[0] ) );
	}

	public function test_should_return_false_on_anothers_profile_for_user_without_bp_moderate() {
		$users = self::factory()->user->create_many( 2 );

		$this->set_current_user( $users[0] );

		$this->go_to( bp_core_get_user_domain( $users[1] ) );

		$this->assertFalse( bp_user_has_access( $users[0] ) );
	}

	public function test_should_return_true_on_own_profile() {
		$users = self::factory()->user->create_many( 2 );

		$this->set_current_user( $users[0] );

		$this->go_to( bp_core_get_user_domain( $users[0] ) );

		$this->assertTrue( bp_user_has_access( $users[0] ) );
	}
}
