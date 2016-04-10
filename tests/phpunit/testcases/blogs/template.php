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
			return;
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

		$at = new BP_Blogs_Template(
			$r['type'],
			$r['page'],
			$r['per_page'],
			$r['max'],
			$r['user_id'],
			$r['search_terms'],
			$r['page_arg'],
			$r['update_meta_cache'],
			$r['include_blog_ids']
		);

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		if ( ! is_multisite() ) {
			return;
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

		$at = new BP_Blogs_Template(
			$r['type'],
			$r['page'],
			$r['per_page'],
			$r['max'],
			$r['user_id'],
			$r['search_terms'],
			$r['page_arg'],
			$r['update_meta_cache'],
			$r['include_blog_ids']
		);

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_give_precedence_to_num_URL_param() {
		if ( ! is_multisite() ) {
			return;
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

		$at = new BP_Blogs_Template(
			$r['type'],
			$r['page'],
			$r['per_page'],
			$r['max'],
			$r['user_id'],
			$r['search_terms'],
			$r['page_arg'],
			$r['update_meta_cache'],
			$r['include_blog_ids']
		);

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Blogs_Template
	 */
	public function test_bp_blogs_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		if ( ! is_multisite() ) {
			return;
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

		$at = new BP_Blogs_Template(
			$r['type'],
			$r['page'],
			$r['per_page'],
			$r['max'],
			$r['user_id'],
			$r['search_terms'],
			$r['page_arg'],
			$r['update_meta_cache'],
			$r['include_blog_ids']
		);

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @ticket BP6519
	 */
	public function test_bp_blog_get_avatar_should_respect_title_parameter() {
		global $blogs_template;

		if ( isset( $blogs_template ) ) {
			$_blogs_template = $blogs_template;
		}

		$user = $this->factory->user->create_and_get();

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->blog_id = get_current_blog_id();
		$blogs_template->blog->admin_user_id = $user->ID;
		$blogs_template->blog->admin_user_email = $user->user_email;

		$actual = bp_get_blog_avatar( array(
			'title' => 'Foo',
		) );

		if ( isset( $_blogs_template ) ) {
			$blogs_template = $_blogs_template;
		} else {
			unset( $blogs_template );
		}

		$this->assertContains( 'title="Foo"', $actual );
	}

	/**
	 * @ticket BP6519
	 */
	public function test_bp_blog_get_avatar_title_attribute_should_default_to_user_displayname() {
		global $blogs_template;

		if ( isset( $blogs_template ) ) {
			$_blogs_template = $blogs_template;
		}

		$user = $this->factory->user->create_and_get();

		$blogs_template = new stdClass;
		$blogs_template->blog = new stdClass;
		$blogs_template->blog->blog_id = get_current_blog_id();
		$blogs_template->blog->admin_user_id = $user->ID;
		$blogs_template->blog->admin_user_email = $user->user_email;

		$actual = bp_get_blog_avatar();

		if ( isset( $_blogs_template ) ) {
			$blogs_template = $_blogs_template;
		} else {
			unset( $blogs_template );
		}

		$this->assertContains( 'title="Profile picture of site author ' . bp_core_get_user_displayname( $user->ID ) . '"', $actual );
	}
}
