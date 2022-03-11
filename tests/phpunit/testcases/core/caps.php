<?php

/**
 * @group core
 * @group caps
 */
class BP_Tests_Core_Caps extends BP_UnitTestCase {
	protected $reset_user_id;
	protected $blog_id;

	public function set_up() {
		parent::set_up();

		$this->reset_user_id = get_current_user_id();

		if ( is_multisite() ) {
			$this->blog_id = self::factory()->blog->create();
		}
	}

	public function tear_down() {
		parent::tear_down();

		$this->set_current_user( $this->reset_user_id );
	}

	public function test_bp_current_user_can_should_interpret_integer_second_param_as_a_blog_id() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		$this->set_current_user( $u );

		add_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );
		$can  = bp_current_user_can( 'foo', bp_get_root_blog_id() );
		$cant = bp_current_user_can( 'foo', $b );
		remove_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10 );

		$this->assertTrue( $can );
		$this->assertFalse( $cant );
	}

	/**
	 * @ticket BP6501
	 */
	public function test_bp_current_user_can_should_respect_blog_id_passed_in_args_array() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		$this->set_current_user( $u );

		add_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );
		$can  = bp_current_user_can( 'foo', array( 'blog_id' => bp_get_root_blog_id() ) );
		$cant = bp_current_user_can( 'foo', array( 'blog_id' => $b ) );
		remove_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10 );

		$this->assertTrue( $can );
		$this->assertFalse( $cant );
	}

	public function grant_cap_foo( $allcaps, $caps ) {
		if ( bp_is_root_blog() ) {
			$allcaps['foo'] = 1;
		}

		return $allcaps;
	}

	public function check_cap_args( $caps, $cap, $user_id, $args ) {
		$this->test_args = $args;
		return $caps;
	}

	/**
	 * @group bp_moderate
	 */
	public function test_administrator_can_bp_moderate() {
		$u = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->set_current_user( $u );

		$this->assertTrue( bp_current_user_can( 'bp_moderate' ), 'Administrator can `bp_moderate` on default WordPress config' );
	}

	/**
	 * @group bp_moderate
	 */
	public function test_role_with_manage_options_cap_can_bp_moderate() {
		add_role( 'random_role', 'Random Role', array( 'manage_options' => true ) );

		// Reset roles.
		wp_roles()->init_roles();

		$u = self::factory()->user->create(
			array(
				'role' => 'random_role',
			)
		);

		$this->set_current_user( $u );

		$this->assertTrue( bp_current_user_can( 'bp_moderate' ), 'Users having a `manage_options` cap into their role can `bp_moderate`' );

		remove_role( 'random_role' );
	}

	/**
	 * @group bp_moderate
	 */
	public function test_administrator_can_bp_moderate_emails() {
		$u1 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$u2 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->set_current_user( $u1 );

		$email = self::factory()->post->create(
			array(
				'post_type'   => 'bp-email',
			)
		);

		$this->assertTrue( current_user_can( 'edit_post', $email ), 'Administrator should be able to edit emails they created' );

		$this->set_current_user( $u2 );

		$this->assertTrue( current_user_can( 'edit_post', $email ), 'Administrator should be able to edit emails others created when BuddyPress is not network activated' );
	}

	/**
	 * @group bp_moderate
	 */
	public function test_administrator_can_bp_moderate_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u1 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		grant_super_admin( $u1 );

		$u2 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		add_filter( 'bp_is_network_activated', '__return_true' );

		// Swith & restore to reset the roles.
		switch_to_blog( $this->blog_id );

		$this->set_current_user( $u1 );
		$this->assertTrue( bp_current_user_can( 'bp_moderate' ), 'Only Super Admins can `bp_moderate` when BuddyPress is network activated' );

		$this->set_current_user( $u2 );

		$this->assertFalse( bp_current_user_can( 'bp_moderate' ), 'Regular Admins cannot `bp_moderate` when BuddyPress is network activated' );

		grant_super_admin( $u2 );
		$this->assertTrue( bp_current_user_can( 'bp_moderate' ), 'Only Super Admins can `bp_moderate` when BuddyPress is network activated' );

		restore_current_blog();

		remove_filter( 'bp_is_network_activated', '__return_true' );
	}

	/**
	 * @group bp_moderate
	 */
	public function test_administrator_can_bp_moderate_emails_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u1 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		grant_super_admin( $u1 );

		$u2 = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$email = self::factory()->post->create(
			array(
				'post_type'   => 'bp-email',
			)
		);

		add_filter( 'bp_is_network_activated', '__return_true' );

		// Swith & restore to reset the roles.
		switch_to_blog( $this->blog_id );
		restore_current_blog();

		$this->set_current_user( $u1 );
		$this->assertTrue( current_user_can( 'edit_post', $email ), 'Super Admins should be able to edit emails they created' );

		$this->set_current_user( $u2 );
		$this->assertFalse( current_user_can( 'edit_post', $email ), 'Administrator should not be able to edit emails others created when BuddyPress is network activated' );

		grant_super_admin( $u2 );
		$this->assertTrue( current_user_can( 'edit_post', $email ), 'Super Admins should be able to edit emails others created' );

		remove_filter( 'bp_is_network_activated', '__return_true' );
	}
}
