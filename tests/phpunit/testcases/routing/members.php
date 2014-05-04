<?php
/**
 * @group members
 * @group routing
 */
class BP_Tests_Routing_Members extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'user_login' => 'paulgibbs', 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	function test_members_directory() {
		$this->go_to( bp_get_members_directory_permalink() );
		$this->assertEquals( bp_get_members_root_slug(), bp_current_component() );
	}

	function test_member_permalink() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) );
		$this->assertTrue( bp_is_my_profile() );
	}
}
