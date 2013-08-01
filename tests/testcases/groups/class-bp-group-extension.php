<?php

include_once BP_TESTS_DIR . '/assets/group-extensions.php';

/**
 * @group groups
 */
class BP_Tests_Group_Extension_TestCases extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
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
}
