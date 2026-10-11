<?php
/**
 * @group routing
 */
class BP_Tests_Routing_Anonymous extends BP_UnitTestCase {
	public function test_wordpress_page() {
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}

	public function test_nav_menu() {
		$this->go_to( '/' );
		$nav = buddypress()->members->nav->get_item_nav();
		$this->assertEmpty( $nav );
	}
}
