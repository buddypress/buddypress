<?php

/**
 * @group blogs
 */
class BP_Tests_Blogs_Template extends BP_UnitTestCase {
	/**
	 * @group bp_get_blog_last_active
	 */
	public function test_bp_get_blog_last_active_default_params() {
		// Fake the global
		global $blogs_template;

		$time = date( 'Y-m-d h:i:s', time() - 24 * 60 * 60 );
		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'active %s', 'buddypress' ) ), bp_get_blog_last_active() );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_get_blog_last_active
	 */
	public function test_bp_get_blog_last_active_active_format_true() {
		// Fake the global
		global $blogs_template;

		$time = date( 'Y-m-d h:i:s', time() - 24 * 60 * 60 );
		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'active %s', 'buddypress' ) ), bp_get_blog_last_active( array( 'active_format' => true, ) ) );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_get_blog_last_active
	 */
	public function test_bp_get_blog_last_active_active_format_false() {
		// Fake the global
		global $blogs_template;

		$time = date( 'Y-m-d h:i:s', time() - 24 * 60 * 60 );
		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->last_activity = $time;

		$this->assertEquals( bp_core_time_since( $time ), bp_get_blog_last_active( array( 'active_format' => false, ) ) );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_get_blog_last_active
	 */
	public function test_bp_get_blog_last_active_active_no_last_activity() {
		$this->assertEquals( __( 'Never active', 'buddypress' ), bp_get_blog_last_active() );
	}

	/**
	 * @group bp_get_blog_latest_post
	 */
	public function test_bp_get_blog_latest_post_default_params() {
		// Fake the global
		global $blogs_template;

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->latest_post = new stdClass;
		$blogs_template->blog->latest_post->guid = 'foo';
		$blogs_template->blog->latest_post->post_title = 'bar';

		$this->assertSame( sprintf( __( 'Latest Post: %s', 'buddypress' ), '<a href="foo">bar</a>' ), bp_get_blog_latest_post() );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_get_blog_latest_post
	 */
	public function test_bp_get_blog_latest_post_latest_format_true() {
		// Fake the global
		global $blogs_template;

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->latest_post = new stdClass;
		$blogs_template->blog->latest_post->guid = 'foo';
		$blogs_template->blog->latest_post->post_title = 'bar';

		$this->assertSame( sprintf( __( 'Latest Post: %s', 'buddypress' ), '<a href="foo">bar</a>' ), bp_get_blog_latest_post( array( 'latest_format' => true, ) ) );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_get_blog_latest_post
	 */
	public function test_bp_get_blog_latest_post_latest_format_false() {
		// Fake the global
		global $blogs_template;

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->latest_post = new stdClass;
		$blogs_template->blog->latest_post->guid = 'foo';
		$blogs_template->blog->latest_post->post_title = 'bar';

		$this->assertSame( '<a href="foo">bar</a>', bp_get_blog_latest_post( array( 'latest_format' => false, ) ) );

		$blogs_template->blog = null;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_does_not_exist_should_default_to_true() {
		$old_settings = $settings = buddypress()->site_options;
		if ( is_array( $settings['registration'] ) && isset( $settings['registration'] ) ) {
			unset( $settings['registration'] );
		}
		buddypress()->site_options = $settings;

		$this->assertTrue( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_is_all_should_return_true() {
		$old_settings = $settings = buddypress()->site_options['registration'];

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration'] = 'all';
		buddypress()->site_options = $settings;

		$this->assertTrue( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_is_blog_should_return_true() {
		$old_settings = $settings = buddypress()->site_options['registration'];

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration'] = 'blog';
		buddypress()->site_options = $settings;

		$this->assertTrue( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_is_user_should_return_false() {
		$old_settings = $settings = buddypress()->site_options['registration'];

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration'] = 'user';
		buddypress()->site_options = $settings;

		$this->assertFalse( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_is_none_should_return_false() {
		$old_settings = $settings = buddypress()->site_options['registration'];

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration'] = 'none';
		buddypress()->site_options = $settings;

		$this->assertFalse( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}
}
