<?php
/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreNewNavItem extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_should_return_false_if_name_is_not_provided() {
		$args = array(
			'slug' => 'foo',
		);

		$this->assertFalse( bp_core_new_nav_item( $args ) );
	}

	public function test_should_return_false_if_slug_is_not_provided() {
		$args = array(
			'name' => 'foo',
		);

		$this->assertFalse( bp_core_new_nav_item( $args ) );
	}

	public function test_should_return_false_if_site_admin_only_and_current_user_cannot_bp_moderate() {
		// Should already be set to a 0 user.
		$this->assertFalse( bp_current_user_can( 'bp_moderate' ) );
		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'site_admin_only' => true,
		);

		$this->assertFalse( bp_core_new_nav_item( $args ) );
	}

	public function test_show_for_displayed_user_false_should_force_function_to_return_false_when_bp_user_has_access_is_also_false() {
		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'show_for_displayed_user' => false,
		);

		add_filter( 'bp_user_has_access', '__return_false' );
		$retval = bp_core_new_nav_item( $args );
		remove_filter( 'bp_user_has_access', '__return_false' );

		$this->assertFalse( $retval );
	}
}
