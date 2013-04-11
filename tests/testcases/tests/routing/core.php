<?php
/**
 * @group core
 * @group routing
 */
class BP_Tests_Routing_Core extends BP_UnitTestCase {
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

	function test_wordpress_page() {
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}

	function test_nav_menu() {
		$this->go_to( '/' );
		$this->assertArrayHasKey( 'activity', buddypress()->bp_nav );
		$this->assertArrayHasKey( 'profile',  buddypress()->bp_nav );
	}
}
