<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreRemoveSubnavItem extends BP_UnitTestCase {

	public function test_backcompat_remove_group_nav_items() {
		$g1 = self::factory()->group->create();

		// In group context
		$g_obj = groups_get_group( $g1 );
		$this->go_to( bp_get_group_url( $g_obj ) );

		bp_core_new_subnav_item( array(
			'name' => 'Clam',
			'slug' => 'clam',
			'parent_slug' => bp_get_current_group_slug(),
			'parent_url' => bp_get_group_url( $g_obj ),
			'screen_function' => 'clam_subnav',
		) );

		bp_core_remove_subnav_item( $g_obj->slug, 'clam' );

		$nav = bp_get_nav_menu_items( 'groups' );
		$found = false;
		foreach ( $nav as $_nav ) {
			if ( 'clam' === $_nav->css_id ) {
				$found = true;
				break;
			}
		}

		$this->assertFalse( $found );
	}
}
