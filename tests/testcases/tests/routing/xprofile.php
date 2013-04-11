<?php
/**
 * @group xprofile
 * @group routing
 */
class BP_Tests_Routing_XProfile extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( $this->old_current_user );
	}

	function test_member_profile() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . buddypress()->profile->slug );
		$this->assertTrue( bp_is_user_profile() );
	}

	function test_member_profile_edit() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . buddypress()->profile->slug . '/edit' );
		$this->assertTrue( bp_is_user_profile_edit() );
	}

	function test_member_profile_change_avatar() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . buddypress()->profile->slug . '/change-avatar' );
		$this->assertTrue( bp_is_user_change_avatar() );	
	}
}
