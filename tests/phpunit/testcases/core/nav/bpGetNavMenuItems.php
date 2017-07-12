<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpGetNavMenuItems extends BP_UnitTestCase {
	/**
	 * @ticket BP7110
	 */
	public function test_top_level_link_should_point_to_displayed_user_for_loggedin_user() {
		$users = $this->factory->user->create_many( 2 );

		$this->set_current_user( $users[0] );
		$user_1_domain = bp_core_get_user_domain( $users[1] );
		$this->go_to( $user_1_domain );

		$found = bp_get_nav_menu_items();

		// Find the Activity top-level item.
		$activity_item = null;
		foreach ( $found as $f ) {
			if ( 'activity' === $f->css_id ) {
				$activity_item = $f;
				break;
			}
		}

		$this->assertSame( trailingslashit( $user_1_domain ) . 'activity/', $activity_item->link );
	}

	/**
	 * @ticket BP7110
	 */
	public function test_top_level_link_should_point_to_displayed_user_for_loggedout_user() {
		$user = $this->factory->user->create();

		$this->set_current_user( 0 );
		$user_domain = bp_core_get_user_domain( $user );
		$this->go_to( $user_domain );

		$found = bp_get_nav_menu_items();

		// Find the Activity top-level item.
		$activity_item = null;
		foreach ( $found as $f ) {
			if ( 'activity' === $f->css_id ) {
				$activity_item = $f;
				break;
			}
		}

		$this->assertSame( trailingslashit( $user_domain ) . 'activity/', $activity_item->link );
	}
}
