<?php
/**
 * @group xprofile
 * @group routing
 */
class BP_Tests_Routing_XProfile extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		parent::tear_down();
		$this->set_current_user( $this->old_current_user );
	}

	function test_member_profile() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_profile_slug() );
		$this->assertTrue( bp_is_user_profile() );
	}

	function test_member_profile_edit() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_profile_slug() . '/edit' );
		$this->assertTrue( bp_is_user_profile_edit() );
	}

	function test_member_profile_change_avatar() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_profile_slug() . '/change-avatar' );
		$this->assertTrue( bp_is_user_change_avatar() );
	}
}
