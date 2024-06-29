<?php
/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreNewSubnavItem extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_required_params() {
		// 'name'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'slug'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'parent_slug' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'parent_slug'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'screen_function'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_slug' => 'foo',
		) ) );
	}

	public function test_site_admin_only() {
		$old_current_user = get_current_user_id();
		self::set_current_user( 0 );

		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
			'site_admin_only' => true,
		) ) );

		self::set_current_user( $old_current_user );
	}

	public function test_should_return_false_if_site_admin_only_and_current_user_cannot_bp_moderate() {
		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		// Should already be set to a 0 user.
		$this->assertFalse( bp_current_user_can( 'bp_moderate' ) );
		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'parent_slug' => 'parent',
			'parent_url' => bp_get_root_url() . '/parent/',
			'screen_function' => 'foo',
			'site_admin_only' => true,
		);

		$this->assertFalse( bp_core_new_subnav_item( $args ) );
	}

	public function screen_callback() {
		bp_core_load_template( 'members/single/plugins' );
	}

	public function new_nav_hook() {
		bp_core_new_subnav_item(
			array(
				'name'            => 'Testing',
				'slug'            => 'testing',
				'parent_slug'     => bp_get_profile_slug(),
				'screen_function' => array( $this, 'screen_callback' ),
				'position'        => 20
			)
		);
	}

	/**
	 * @ticket BP7931
	 */
	public function test_subnav_should_not_404_on_early_bp_setup_nav_priority() {
		// Register a subnav on 'bp_setup_nav' hook early (at priority zero).
		add_action( 'bp_setup_nav', array( $this, 'new_nav_hook' ), 0 );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		self::set_current_user( $u );

		$url = bp_members_get_user_url(
			$u,
			array(
				'single_item_component' => bp_get_profile_slug(),
				'single_item_action'    => 'testing',
			)
		);

		// Emulate visit to our new subnav page.
		$this->go_to( $url );

		// Assert that subnav page does not 404.
		$this->assertFalse( is_404() );

		remove_action( 'bp_setup_nav', array( $this, 'new_nav_hook' ), 0 );

		self::set_current_user( $old_current_user );
	}
}
