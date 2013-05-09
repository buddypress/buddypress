<?php
/**
 * @group groups
 * @group routing
 */
class BP_Tests_Routing_Groups extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	function test_member_groups() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_groups_slug() );
		$this->assertTrue( bp_is_user_groups() );
	}

	function test_member_groups_invitations() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_groups_slug() . '/invites' );
		$this->assertTrue( bp_is_user_groups() && bp_is_current_action( 'invites' ) );
	}
}
