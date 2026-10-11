<?php
/**
 * @group bp_community_visibility
 */
class BP_Tests_BP_Community_Visibility_TestCases extends BP_UnitTestCase {
	protected $old_user;
	protected $logged_in_user;

	public function set_up() {
		parent::set_up();
		$this->old_user = get_current_user_id();
		$this->logged_in_user = self::factory()->user->create();
		wp_set_current_user( $this->logged_in_user );

		// Save a typical setting.
		$setting = array(
			'global' => 'members',
		);
		update_option( '_bp_community_visibility', $setting );
	}

	public function tear_down() {
		wp_set_current_user( $this->old_user );
		// Reset site to totally open.
		delete_option( '_bp_community_visibility' );
		parent::tear_down();
	}

	// Test that logged-in user has access to component marked anyone and component marked members
	public function test_bp_community_visibility_allow_visibility_for_logged_in_user() {
		$this->assertTrue( bp_user_can( $this->logged_in_user, 'bp_view' ) );
	}

	// Test that anonymous user does not have access
	public function test_bp_community_visibility_enforce_visibility_for_anon_user() {
		$this->assertFalse( bp_user_can( 0, 'bp_view' ) );
	}

	// Bad component ID should use global setting.
	public function test_bp_community_visibility_bad_component_id() {
		$this->assertFalse( bp_user_can( 0, 'bp_view', array( 'bp_component' => 'blerg' ) ) );
		$this->assertTrue( bp_user_can( $this->logged_in_user, 'bp_view', array( 'bp_component' => 'blerg' ) ) );
	}

	// No saved setting should be open access for anonymous users and logged in users.
	public function test_bp_community_visibility_no_saved_setting() {
		delete_option( '_bp_community_visibility' );
		// No saved setting should result in the site being open to anyone.
		$this->assertTrue( bp_user_can( 0, 'bp_view' ) );
		$this->assertTrue( bp_user_can( $this->logged_in_user, 'bp_view' ) );
	}

	// Ensure that "anyone" setting allows access to everyone.
	public function test_bp_community_visibility_access_allowed() {
		$setting = array(
			'global'      => 'anyone',
		);
		update_option( '_bp_community_visibility', $setting );
		$this->assertTrue( bp_user_can( 0, 'bp_view' ) );
		$this->assertTrue( bp_user_can( $this->logged_in_user, 'bp_view' ) );
	}

	// Make sure fallback logic works for mixed-up setting values.
	public function test_bp_community_visibility_fallback_setting() {
		// Save a partial setting.
		$setting = array(
			'global'      => 'members',
			'members'     => 'anyone',
		);
		update_option( '_bp_community_visibility', $setting );
		$this->assertTrue( 'members' === bp_get_community_visibility( 'groups' ) );
	}
}
