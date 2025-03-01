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

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'Active %s', 'buddypress' ) ), bp_get_blog_last_active() );

		$blogs_template = null;
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

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'Active %s', 'buddypress' ) ), bp_get_blog_last_active( array( 'active_format' => true, ) ) );

		$blogs_template = null;
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

		$blogs_template = null;
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

		$blogs_template = null;
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

		$blogs_template = null;
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

		$blogs_template = null;
	}

	/**
	 * @group bp_blog_signup_enabled
	 */
	public function test_bp_signup_enabled_when_registration_setting_does_not_exist_should_default_to_true() {
		$old_settings = $settings = buddypress()->site_options;
		if ( is_array( $settings ) && isset( $settings['registration'] ) ) {
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
		$old_settings = $settings = buddypress()->site_options;

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
		$old_settings = $settings = buddypress()->site_options;

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
		$old_settings = $settings = buddypress()->site_options;

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
		$old_settings = $settings = buddypress()->site_options;

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration'] = 'none';
		buddypress()->site_options = $settings;

		$this->assertFalse( bp_blog_signup_enabled() );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_give_precedence_to_bpage_URL_param() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$request = $_REQUEST;
		$_REQUEST['bpage'] = '5';

		$r = array(
			'type'              => 'active',
			'page_arg'          => 'bpage',
			'page'              => 8,
			'per_page'          => 20,
			'max'               => false,
			'user_id'           => 0,
			'include_blog_ids'  => false,
			'search_terms'      => '',
			'update_meta_cache' => true
		);

		$at = new BP_Blogs_Template( $r );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$request = $_REQUEST;
		$_REQUEST['bpage'] = '0';

		$r = array(
			'type'              => 'active',
			'page_arg'          => 'bpage',
			'page'              => 8,
			'per_page'          => 20,
			'max'               => false,
			'user_id'           => 0,
			'include_blog_ids'  => false,
			'search_terms'      => '',
			'update_meta_cache' => true
		);

		$at = new BP_Blogs_Template( $r );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_give_precedence_to_num_URL_param() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$r = array(
			'type'              => 'active',
			'page_arg'          => 'bpage',
			'page'              => 1,
			'per_page'          => 13,
			'max'               => false,
			'user_id'           => 0,
			'include_blog_ids'  => false,
			'search_terms'      => '',
			'update_meta_cache' => true
		);

		$at = new BP_Blogs_Template( $r );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$r = array(
			'type'              => 'active',
			'page_arg'          => 'bpage',
			'page'              => 1,
			'per_page'          => 13,
			'max'               => false,
			'user_id'           => 0,
			'include_blog_ids'  => false,
			'search_terms'      => '',
			'update_meta_cache' => true
		);

		$at = new BP_Blogs_Template( $r );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group avatar
	 * @group BP_Blogs_Template
	 * @group bp_get_blog_avatar
	 */
	public function test_bp_get_blog_avatar_ids_provided() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		global $blogs_template;
		$reset_blogs_template = $blogs_template;
		$blogs_template = null;

		$u = self::factory()->user->create();
		$b = self::factory()->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
		) );

		$avatar = bp_get_blog_avatar( array(
			'type'          => 'full',
			'admin_user_id' => $u,
			'blog_id'       => $b,
			'alt'           => 'test',
			'no_grav'       => true,
			'class'         => 'avatar',
		) );

		$blogs_template = $reset_blogs_template;
		$expected       = bp_core_fetch_avatar(
			array(
				'type'    => 'full',
				'item_id' => $u,
				'alt'     => 'test',
				'no_grav' => true,
				'class'   => 'avatar',
			)
		);

		$this->assertTrue( $avatar === $expected );
	}

	/**
	 * @group avatar
	 * @group BP_Blogs_Template
	 * @group bp_get_blog_avatar
	 */
	public function test_bp_get_blog_avatar_has_site_icon() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		global $blogs_template;
		$reset_blogs_template = $blogs_template;
		$blogs_template = null;

		$u = self::factory()->user->create();
		$b = self::factory()->blog->create( array(
			'title' => 'The Bar Foo Blog',
			'user_id' => $u,
		) );

		add_filter( 'get_site_icon_url', array( $this, 'filter_blog_avatar' ) );
		add_filter( 'bp_is_network_activated', '__return_true' );

		$avatar = bp_get_blog_avatar( array(
			'type'          => 'full',
			'admin_user_id' => $u,
			'blog_id'       => $b,
			'alt'           => 'test',
			'no_grav'       => true,
			'class'         => 'avatar',
		) );

		remove_filter( 'bp_is_network_activated', '__return_true' );
		remove_filter( 'get_site_icon_url', array( $this, 'filter_blog_avatar' ) );
		$blogs_template = $reset_blogs_template;

		$this->assertTrue( false !== strpos( $avatar, BP_TESTS_DIR . 'assets/upside-down.jpg' ) );
	}

	/**
	 * @group avatar
	 * @group BP_Blogs_Template
	 * @group bp_get_blog_avatar
	 */
	public function test_bp_get_blog_default_avatar() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		global $blogs_template;
		$reset_blogs_template = $blogs_template;
		$blogs_template = null;

		$u = self::factory()->user->create();
		$b = self::factory()->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
		) );

		$avatar = bp_get_blog_avatar(
			array(
				'type'    => 'thumb',
				'blog_id' => $b,
				'html'    => false,
			)
		);

		$blogs_template = $reset_blogs_template;
		$expected       = buddypress()->plugin_url . "bp-core/images/mystery-blog-50.png";

		$this->assertTrue( $avatar === $expected );
	}

	/**
	 * @BP9228
	 * @group avatar
	 * @group BP_Blogs_Template
	 * @group bp_get_blog_avatar
	 */
	public function test_bp_get_blog_default_avatar_inside_blogs_template() {
		$this->skipWithoutMultisite();

		$b1 = self::factory()->blog->create();
		$b2 = self::factory()->blog->create();

		// Fake the global
		global $blogs_template;

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->blog_id = $b1;

		$expected = buddypress()->plugin_url . "bp-core/images/mystery-blog.png";

		// Get from global blog_id.
		$avatar = bp_get_blog_avatar();
		$this->assertTrue( str_contains( $avatar, "blog-{$b1}-avatar" ) );
		$this->assertTrue( str_contains( $avatar, $expected ) );

		// Get from the blog_id passed in, instead of global.
		$avatar = bp_get_blog_avatar( array( 'blog_id' => $b2 ) );
		$this->assertTrue( str_contains( $avatar, "blog-{$b2}-avatar" ) );
		$this->assertTrue( str_contains( $avatar, $expected ) );

		$blogs_template = null;
	}

	public function filter_blog_avatar() {
		return BP_TESTS_DIR . 'assets/upside-down.jpg';
	}
}
