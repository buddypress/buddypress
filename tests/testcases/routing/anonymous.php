<?php
/**
 * @group routing
 */
class BP_Tests_Routing_Anonymous extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( 0 );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	function test_wordpress_page() {
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}

	function test_nav_menu() {
		$this->go_to( '/' );
		$this->assertEmpty( buddypress()->bp_nav );
	}
}
