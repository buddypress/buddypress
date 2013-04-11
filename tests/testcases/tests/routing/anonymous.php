<?php
/**
 * @group routing
 */
class BP_Tests_Routing_Anonymous extends BP_UnitTestCase {

	function test_wordpress_page() {
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}

	function test_nav_menu() {
		$this->go_to( '/' );
		$this->assertEmpty( buddypress()->bp_nav );
	}
}
