<?php

include_once BP_TESTS_DIR . '/assets/group-extensions.php';

/**
 * @group groups
 * @group BP_Group_Extension
 */
class BP_Tests_Group_Extension_TestCases extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		parent::tear_down();
		$this->set_permalink_structure( $this->permalink_structure );
	}

	public function test_parse_legacy_properties() {
		$class_name = 'BPTest_Group_Extension_Parse_Legacy_Properties';
		$class_slug = sanitize_title( $class_name );
		$e = new $class_name();
		$e->_register();

		// Test most items separately so we can ignore irrelevant props
		$l = $e->_get_legacy_properties_converted();
		$this->assertEquals( $l['name'], $class_name );
		$this->assertEquals( $l['slug'], $class_slug );
		$this->assertEquals( $l['visibility'], 'private' );
		$this->assertEquals( $l['nav_item_position'], 63 );
		$this->assertEquals( $l['enable_nav_item'], true );
		$this->assertEquals( $l['nav_item_name'], $class_name . ' Nav' );
		$this->assertEquals( $l['display_hook'], 'foo_hook' );
		$this->assertEquals( $l['template_file'], 'foo_template' );

		// Build the screens array manually
		$expected = array(
			'create' => array(
				'name' => $class_name . ' Create',
				'slug' => $class_slug . '-create',
				'position' => 58,
				'enabled' => false,
			),
			'edit' => array(
				'name' => $class_name . ' Edit',
				'slug' => $class_slug . '-edit',
				'enabled' => false,
			),
			'admin' => array(
				'enabled' => true,
				'metabox_context' => 'high',
				'metabox_priority' => 'side',
			),
		);

		$this->assertEquals( $expected, $l['screens'] );
	}

	public function test_setup_screens_use_global_fallbacks() {
		$class_name = 'BPTest_Group_Extension_Setup_Screens_Use_Global_Fallbacks';
		$e = new $class_name();
		$e->_setup_class_info();
		$screens = $e->_get_default_screens();

		$fallback = array(
			'screen_callback' => array( $e, 'settings_screen' ),
			'screen_save_callback' => array( $e, 'settings_screen_save' ),
		);
		$fallbacks = array(
			'create' => $fallback,
			'edit' => $fallback,
			'admin' => $fallback,
		);

		// strip everything from the screens array but what we we're
		// testing
		foreach ( $screens as &$screen ) {
			foreach ( $screen as $k => $v ) {
				if ( ! in_array( $k, array( 'screen_callback', 'screen_save_callback' ) ) ) {
					unset( $screen[ $k ] );
				}
			}
		}

		$this->assertEquals( $fallbacks, $screens );
	}

	public function test_setup_screens_define_edit_screens_locally() {
		$class_name = 'BPTest_Group_Extension_Setup_Screens_Define_Edit_Screens_Locally';
		$e = new $class_name();
		$e->_setup_class_info();
		$screens = $e->_get_default_screens();

		$fallback = array(
			'screen_callback' => array( $e, 'settings_screen' ),
			'screen_save_callback' => array( $e, 'settings_screen_save' ),
		);
		$expected = array(
			'create' => $fallback,
			'edit' => array(
				'screen_callback' => array( $e, 'edit_screen' ),
				'screen_save_callback' => array( $e, 'edit_screen_save' ),
			),
			'admin' => $fallback,
		);

		// strip everything from the screens array but what we we're
		// testing
		foreach ( $screens as &$screen ) {
			foreach ( $screen as $k => $v ) {
				if ( ! in_array( $k, array( 'screen_callback', 'screen_save_callback' ) ) ) {
					unset( $screen[ $k ] );
				}
			}
		}

		$this->assertEquals( $screens, $expected );
	}

	public function test_parse_args_r() {
		$a = array(
			'veggies' => 'yes',
			'ice_cream' => 'dope',
			'fruit' => array(
				'apple' => 'gross',
				'berries' => array(
					'blueberries' => array(
						'in_season' => 'never',
						'oh' => 'boy',
					),
					'cherries' => 'sometimes',
				),
			),
		);

		$b = array(
			'veggies' => 'no',
			'cheese' => array(
				'cheddar' => 'good',
			),
			'fruit' => array(
				'apple' => 'yum',
				'berries' => array(
					'strawberries' => 'awesome',
					'blueberries' => array(
						'in_season' => 'yes',
						'out_of_season' => 'no',
					),
				),
			),
		);

		$expected = array(
			'veggies' => 'yes',
			'ice_cream' => 'dope',
			'cheese' => array(
				'cheddar' => 'good',
			),
			'fruit' => array(
				'apple' => 'gross',
				'berries' => array(
					'strawberries' => 'awesome',
					'blueberries' => array(
						'in_season' => 'never',
						'out_of_season' => 'no',
						'oh' => 'boy',
					),
					'cherries' => 'sometimes',
				),
			),
		);

		$this->assertEquals( $expected, BP_Group_Extension::parse_args_r( $a, $b ) );
	}

	/**
	 * Config that gets intentionally stored as a direct property of object
	 */
	public function test_access_root_property() {
		$class_name = 'BPTest_Group_Extension_Access_Root_Property';
		$e = new $class_name();
		$e->_register();

		$this->assertEquals( 39, $e->nav_item_position );
	}

	/**
	 * Config that gets registered using init(), but is then accessed via
	 * the legacy location
	 */
	public function test_access_init_property_using_legacy_location() {
		$class_name = 'BPTest_Group_Extension_Access_Init_Property_Using_Legacy_Location';
		$e = new $class_name();
		$e->_register();

		$this->assertEquals( 18, $e->create_step_position );
	}

	/**
	 * Provides settings_screen* and edit_screen*
	 */
	public function test_get_screen_callback_fallbacks() {
		$class_name = 'BPTest_Group_Extension_Get_Screen_Callback_Fallbacks';
		$e = new $class_name();
		$e->_register();

		$this->assertEquals( array( $e, 'settings_screen' ), $e->screens['create']['screen_callback'] );
		$this->assertEquals( array( $e, 'settings_screen_save' ), $e->screens['create']['screen_save_callback'] );
		$this->assertEquals( array( $e, 'settings_screen' ), $e->screens['admin']['screen_callback'] );
		$this->assertEquals( array( $e, 'settings_screen_save' ), $e->screens['admin']['screen_save_callback'] );
		$this->assertEquals( array( $e, 'edit_screen' ), $e->screens['edit']['screen_callback'] );
		$this->assertEquals( array( $e, 'edit_screen_save' ), $e->screens['edit']['screen_save_callback'] );
	}

	public function test_has_submit_button() {
		$a = '<p>Foo bar</p><input type="text" name="awesome" /><input name="save" type="submit" id="saverrrr" />sweet';
		$this->assertTrue( BP_Group_Extension::has_submit_button( $a ) );

		$b = '<p>Foo bar</p><input type="text" name="awesome" />sweet';
		$this->assertFalse( BP_Group_Extension::has_submit_button( $b ) );

		// switch the quotation marks
		$c = "<p>Foo bar</p><input type='text' name='awesome' /><input name='save' type='submit' id='saverrrr' />sweet";
		$this->assertTrue( BP_Group_Extension::has_submit_button( $c ) );
	}

	/**
	 * @group enable_nav_item
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_enable_nav_item_true() {
		$old_options_nav = buddypress()->bp_options_nav;
		$this->set_permalink_structure( '/%postname%/' );

		$g = self::factory()->group->create();
		$g_obj = groups_get_group( $g );

		$class_name = 'BPTest_Group_Extension_Enable_Nav_Item_True';
		$e = new $class_name();

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e->_register();

		$this->assertTrue( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;
	}

	/**
	 * @group enable_nav_item
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_enable_nav_item_false() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_options_nav = buddypress()->bp_options_nav;

		$g = self::factory()->group->create();
		$g_obj = groups_get_group( $g );

		$class_name = 'BPTest_Group_Extension_Enable_Nav_Item_False';
		$e = new $class_name();

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e->_register();

		$this->assertFalse( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;
	}

	/**
	 * @group visibility
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_visibility_private() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_options_nav = buddypress()->bp_options_nav;
		$old_current_user = get_current_user_id();

		$g = self::factory()->group->create( array(
			'status' => 'private',
		) );
		$g_obj = groups_get_group( $g );

		$class_name = 'BPTest_Group_Extension_Visibility_Private';
		$e = new $class_name();

		// Test as non-logged-in user
		$this->set_current_user( 0 );
		$this->go_to( bp_get_group_url( $g_obj ) );
		$e->_register();
		$this->assertFalse( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;

		// Test as group member
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$this->add_user_to_group( $u, $g );
		$this->go_to( bp_get_group_url( $g_obj ) );
		$e->_register();
		$this->assertTrue( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group visibility
	 * @expectedIncorrectUsage bp_nav
	 *
	 * visibility=public + status=private results in adding the item to
	 * the nav. However, BP_Groups_Component::setup_globals() bounces the
	 * user away from this page on a regular pageload (BP 2.0 and under)
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/4785
	 */
	public function test_visibility_public() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_options_nav = buddypress()->bp_options_nav;
		$old_current_user = get_current_user_id();

		$g = self::factory()->group->create( array(
			'status' => 'private',
		) );
		$g_obj = groups_get_group( $g );

		$class_name = 'BPTest_Group_Extension_Visibility_Public';
		$e = new $class_name();

		// Test as non-logged-in user
		$this->set_current_user( 0 );
		$this->go_to( bp_get_group_url( $g_obj ) );
		$e->_register();
		$this->assertTrue( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;

		// Test as group member
		$u = self::factory()->user->create();
		$this->set_current_user( $u );
		$this->add_user_to_group( $u, $g );
		$this->go_to( bp_get_group_url( $g_obj ) );
		$e->_register();
		$this->assertTrue( isset( buddypress()->bp_options_nav[ $g_obj->slug ][ $e->slug ] ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_inferred_from_enable_nav_item() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_current_user = get_current_user_id();

		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );
		$this->go_to( bp_get_group_url( $g_obj ) );

		$this->set_current_user( 0 );

		$e = new BPTest_Group_Extension_Inferred_Access_Settings_EnableNavItem_True();
		$e->_register();
		$this->assertTrue( $e->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Inferred_Access_Settings_EnableNavItem_False();
		$e2->_register();
		$this->assertFalse( $e2->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_explicit_for_logged_out_user() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );
		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertFalse( $e2->user_can_visit() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertFalse( $e3->user_can_visit() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_visit() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_visit() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_explicit_for_logged_in_user() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_visit() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertFalse( $e3->user_can_visit() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_visit() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_visit() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_explicit_for_group_member() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->add_user_to_group( $u, $g );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_visit() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_visit() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_visit() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_visit() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_explicit_for_group_mod() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$m = $this->add_user_to_group( $u, $g );
		$gm = new BP_Groups_Member( $u, $g );
		$gm->promote( 'mod' );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_visit() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_visit() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertTrue( $e4->user_can_visit() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_visit() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_visit
	 */
	public function test_user_can_visit_explicit_for_group_admin() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$m = $this->add_user_to_group( $u, $g );
		$gm = new BP_Groups_Member( $u, $g );
		$gm->promote( 'admin' );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_visit() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_visit() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_visit() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertTrue( $e4->user_can_visit() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertTrue( $e5->user_can_visit() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_implied() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Access_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_Access_Loggedin();
		$e2->_register();
		$this->assertFalse( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_Access_Member();
		$e3->_register();
		$this->assertFalse( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_Access_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_Access_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_Access_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_visit() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_explicit_for_logged_out_user() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_ShowTab_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_ShowTab_Loggedin();
		$e2->_register();
		$this->assertFalse( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_ShowTab_Member();
		$e3->_register();
		$this->assertFalse( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_ShowTab_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_ShowTab_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_ShowTab_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_see_nav_item() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_explicit_for_logged_in_user() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_ShowTab_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_ShowTab_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_ShowTab_Member();
		$e3->_register();
		$this->assertFalse( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_ShowTab_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_ShowTab_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_ShowTab_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_see_nav_item() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_explicit_for_group_member() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->add_user_to_group( $u, $g );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_ShowTab_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_ShowTab_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_ShowTab_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_ShowTab_AdminMod();
		$e4->_register();
		$this->assertFalse( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_ShowTab_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_ShowTab_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_see_nav_item() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_explicit_for_group_mod() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->add_user_to_group( $u, $g );
		$gm = new BP_Groups_Member( $u, $g );
		$gm->promote( 'mod' );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_ShowTab_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_ShowTab_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_ShowTab_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_ShowTab_AdminMod();
		$e4->_register();
		$this->assertTrue( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_ShowTab_Admin();
		$e5->_register();
		$this->assertFalse( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_ShowTab_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_see_nav_item() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group user_can_see_nav_item
	 */
	public function test_user_can_see_nav_item_explicit_for_group_admin() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->add_user_to_group( $u, $g );
		$gm = new BP_Groups_Member( $u, $g );
		$gm->promote( 'admin' );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_ShowTab_Anyone();
		$e1->_register();
		$this->assertTrue( $e1->user_can_see_nav_item() );

		$e2 = new BPTest_Group_Extension_ShowTab_Loggedin();
		$e2->_register();
		$this->assertTrue( $e2->user_can_see_nav_item() );

		$e3 = new BPTest_Group_Extension_ShowTab_Member();
		$e3->_register();
		$this->assertTrue( $e3->user_can_see_nav_item() );

		$e4 = new BPTest_Group_Extension_ShowTab_AdminMod();
		$e4->_register();
		$this->assertTrue( $e4->user_can_see_nav_item() );

		$e5 = new BPTest_Group_Extension_ShowTab_Admin();
		$e5->_register();
		$this->assertTrue( $e5->user_can_see_nav_item() );

		$e6 = new BPTest_Group_Extension_ShowTab_Noone();
		$e6->_register();
		$this->assertFalse( $e6->user_can_see_nav_item() );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @ticket BP7131
	 */
	public function test_widget_on_group_home_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$this->go_to( bp_get_group_url( $g_obj ) );

		$e1 = new BPTest_Group_Extension_Widget_Method();
		$e1->_register();

		ob_start();
		bp_custom_group_boxes();
		$content = ob_get_clean();

		$this->assertTrue( $content === 'Widget Displayed' );
	}

	/**
	 * @ticket BP7131
	 */
	public function test_widget_on_group_members_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$g = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g_obj = groups_get_group( $g );

		$this->go_to(
			bp_get_group_url(
				$g_obj,
				array(
					'single_item_component' => 'members',
				)
			)
		);

		$e1 = new BPTest_Group_Extension_Widget_Method();
		$e1->_register();

		ob_start();
		bp_custom_group_boxes();
		$content = ob_get_clean();

		$this->assertFalse( $content === 'Widget Displayed' );
	}

	/**
	 * @ticket BP8558
	 */
	public function test_adding_multiple_extension_classes() {
		$this->set_permalink_structure( '/%postname%/' );
		$old_options_nav = buddypress()->bp_options_nav;

		$g = self::factory()->group->create();
		$g_obj = groups_get_group( $g );

		$e = new BPTest_Group_Extension_Enable_Nav_Item_True();
		$e_slug = 'bptest_group_extension_enable_nav_item_true';
		$e_class_name = get_class( $e );
		$f = new BPTest_Group_Extension_Access_Anyone();
		$f_slug = 'bptest_group_extension_access_anyone';
		$f_class_name = get_class( $f );

		bp_register_group_extension( $e_class_name );
		bp_register_group_extension( $f_class_name );

		$this->go_to( bp_get_group_url( $g_obj ) );
		$nav = buddypress()->groups->nav->get_secondary( array(
			'parent_slug' => $g_obj->slug ,
		) );

		$slugs = array();
		foreach ( $nav as $priority => $nav_obj ) {
			$slugs[] = $nav_obj->slug;
		}

		$this->assertTrue( in_array( $e_slug, $slugs, true ) && in_array( $f_slug, $slugs, true ) );

		// Clean up
		buddypress()->bp_options_nav = $old_options_nav;
	}
}
