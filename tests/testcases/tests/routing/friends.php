<?php
/**
 * @group friends
 * @group routing
 */
class BP_Tests_Routing_Friends extends BP_UnitTestCase {
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

	function test_member_friends() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_friends_slug() );
		$this->assertTrue( bp_is_user_friends() );
	}

	function test_member_friends_requests() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_friends_slug()  . '/requests' );
		$this->assertTrue( bp_is_user_friend_requests() );
	}
}
