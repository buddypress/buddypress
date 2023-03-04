<?php
/**
 * @group core
 * @group template
 */
class BP_Tests_Core_Template_BpUserHasAccess extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_should_return_true_for_bp_moderate_user() {
		$users = self::factory()->user->create_many( 2 );

		$this->grant_bp_moderate( $users[0] );
		$this->set_current_user( $users[0] );
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( bp_members_get_user_url( $users[1] ) );

		$this->assertTrue( bp_user_has_access( $users[0] ) );
	}

	public function test_should_return_false_on_anothers_profile_for_user_without_bp_moderate() {
		$users = self::factory()->user->create_many( 2 );

		$this->set_current_user( $users[0] );
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( bp_members_get_user_url( $users[1] ) );

		$this->assertFalse( bp_user_has_access( $users[0] ) );
	}

	public function test_should_return_true_on_own_profile() {
		$users = self::factory()->user->create_many( 2 );

		$this->set_current_user( $users[0] );
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( bp_members_get_user_url( $users[0] ) );

		$this->assertTrue( bp_user_has_access( $users[0] ) );
	}
}
