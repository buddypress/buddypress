<?php

/**
 * @group core
 */

class BP_Tests_Core_Filters extends BP_UnitTestCase {
	/**
	 * @group bp_core_components_subdirectory_reserved_names
	 * @ticket 8187
	 */
	public function test_bp_core_components_subdirectory_reserved_names() {
		if ( ! is_multisite() || is_subdomain_install() ) {
			$this->markTestSkipped();
		}

		$u = self::factory()->user->create();

		$site_data = wpmu_validate_blog_signup( 'members', 'Members', $u );

		$this->assertTrue( is_wp_error( $site_data['errors'] ), 'On MS subdomain installs, a new site should not be able to use a component slug' );
	}
}
