<?php
/**
 * @group messages
 * @group routing
 */
class BP_Tests_Routing_Messages extends BP_UnitTestCase {
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

	function test_member_messages() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() );
		$this->assertTrue( bp_is_messages_inbox() );
	}

	function test_member_messages_sentbox() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() . '/sentbox' );
		$this->assertTrue( bp_is_messages_sentbox() );
	}

	function test_member_messages_compose() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() . '/compose' );
		$this->assertTrue( bp_is_messages_compose_screen() );
	}

	function test_member_messages_notices() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() . '/notices' );
		$this->assertTrue( bp_is_notices() );
	}
}
