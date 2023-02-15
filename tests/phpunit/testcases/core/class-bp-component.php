<?php

include_once BP_TESTS_DIR . '/assets/bp-rest-api-controllers.php';
include_once BP_TESTS_DIR . '/assets/class-bptest-component.php';

/**
 * @group core
 * @group BP_Component
 */
class BP_Tests_BP_Component_TestCases extends BP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$bp = buddypress();
		$bp->unit_test_rest = new stdClass;
		$bp->unit_test_rest->controllers = array();
	}

	public function remove_controller( $controllers ) {
		return array_diff( $controllers, array( 'BP_REST_Members_Endpoint' ) );
	}

	public function add_controller( $controllers ) {
		return array_merge( $controllers, array( 'Foo_Bar' ) );
	}

	public function test_rest_api_init_for_members_component() {
		$bp_members = new BP_Members_Component();
		$bp         = buddypress();

		$bp_members->rest_api_init();

		$this->assertSame( $bp->unit_test_rest->controllers, array(
			'BP_REST_Members_Endpoint',
			'BP_REST_Attachments_Member_Avatar_Endpoint',
			'BP_REST_Attachments_Member_Cover_Endpoint',
		) );
	}

	public function test_rest_api_init_for_members_component_can_remove_controller() {
		$bp_members = new BP_Members_Component();
		$bp         = buddypress();

		add_filter( 'bp_members_rest_api_controllers', array( $this, 'remove_controller' ) );

		$bp_members->rest_api_init();

		remove_filter( 'bp_members_rest_api_controllers', array( $this, 'remove_controller' ) );

		$this->assertSame( $bp->unit_test_rest->controllers, array(
			'BP_REST_Attachments_Member_Avatar_Endpoint',
			'BP_REST_Attachments_Member_Cover_Endpoint',
		) );
	}

	public function test_rest_api_init_for_members_component_cannot_add_controller() {
		$bp_members = new BP_Members_Component();
		$bp         = buddypress();

		add_filter( 'bp_members_rest_api_controllers', array( $this, 'add_controller' ) );

		$bp_members->rest_api_init();

		remove_filter( 'bp_members_rest_api_controllers', array( $this, 'add_controller' ) );

		$this->assertSame( $bp->unit_test_rest->controllers, array(
			'BP_REST_Members_Endpoint',
			'BP_REST_Attachments_Member_Avatar_Endpoint',
			'BP_REST_Attachments_Member_Cover_Endpoint',
		) );
	}

	/**
	 * @group bp_blocks
	 */
	public function test_component_block_globals() {
		$expected = array(
			'dynamic_widget_classname' => 'widget_example_classname',
		);

		$example = new BPTest_Component(
			array(
				'globals' => array(
					'block_globals' => array(
						'bp/example-block' => $expected,
					)
				),
			)
		);

		do_action( 'bp_setup_globals' );

		$this->assertEquals( $expected, $example->block_globals['bp/example-block']->props );
	}

	/**
	 * @group bp_rewrites
	 */
	public function test_component_rewrite_globals() {
		$expected = array(
			'directory'          => 'bp_examples',
			'single_item'        => 'bp_example',
			'single_item_action' => 'bp_example_action',
		);

		$example = new BPTest_Component(
			array(
				'globals' => array(
					'rewrite_ids' => array(
						'directory'          => 'examples ',
						'single_item'        => 'Exam?ple',
						'single_item_action' => 'bp_example_action',
					)
				),
			)
		);

		do_action( 'bp_setup_globals' );

		$this->assertEquals( $expected, $example->rewrite_ids );
	}

	/**
	 * @group bp_rewrites
	 */
	public function test_component_add_rewrite_tags() {
		$example = new BPTest_Component(
			array(
				'globals' => array(
					'rewrite_ids' => array(
						'directory'      => 'examples',
						'directory_type' => 'example_type',
					)
				),
			)
		);

		do_action( 'bp_setup_globals' );

		$expected_directory_regex = '([1]{1,})';
		$rewrite_tags             = array(
			'directory_type' => '([^/]+)',
		);

		$example->add_rewrite_tags( $rewrite_tags );

		global $wp_rewrite;

		$position = array_search( '%' . $example->rewrite_ids['directory'] . '%', $wp_rewrite->rewritecode, true );
		$this->assertEquals( $wp_rewrite->rewritereplace[ $position ], $expected_directory_regex );

		$position = array_search( '%' . $example->rewrite_ids['directory_type'] . '%', $wp_rewrite->rewritecode, true );
		$this->assertEquals( $wp_rewrite->rewritereplace[ $position ], $rewrite_tags['directory_type'] );
	}

	/**
	 * @group bp_rewrites
	 */
	public function test_component_add_rewrite_rules() {
		$example = new BPTest_Component(
			array(
				'globals' => array(
					'root_slug'   => 'examples',
					'rewrite_ids' => array(
						'directory'             => 'examples',
						'directory_type'        => 'example_type',
						'single_item'           => 'example',
						'single_item_component' => 'example_component',
					)
				),
			)
		);

		do_action( 'bp_setup_globals' );

		$rewrite_tags = array(
			'directory_type' => '([^/]+)',
		);

		$example->add_rewrite_tags( $rewrite_tags );

		$rewrite_rules = array(
			'directory_type' => array(
				'order' => 95,
				'regex' => $example->root_slug . '/type/([^/]+)/?$',
				'query' => 'index.php?' . $example->rewrite_ids['directory'] . '=1&' . $example->rewrite_ids['directory_type'] . '=$matches[1]',
			),
		);

		$example->add_rewrite_rules( $rewrite_rules );

		global $wp_rewrite;
		$this->assertEquals( $wp_rewrite->extra_rules_top[ $rewrite_rules['directory_type']['regex'] ], $rewrite_rules['directory_type']['query'] );
	}

	/**
	 * @group bp_rewrites
	 */
	public function test_component_add_permastructs() {
		$example = new BPTest_Component(
			array(
				'globals' => array(
					'has_directory' => true,
					'root_slug'     => 'examples',
					'rewrite_ids'   => array(
						'directory'      => 'examples',
						'example_signup' => 'signup',
					)
				),
			)
		);

		do_action( 'bp_setup_globals' );

		$expected     = 'example-signup/%' . $example->rewrite_ids['example_signup'] . '%';
		$permastructs = array(
			$example->rewrite_ids['example_signup'] => array(
				'permastruct' => $expected,
				'args'        => array(),
			),
		);

		$example->add_permastructs( $permastructs );

		global $wp_rewrite;

		// The directory permastruct should be created automatically.
		$this->assertTrue( isset( $wp_rewrite->extra_permastructs['bp_examples'] ) );

		// The custom permastruct should be created as requested.
		$this->assertEquals( $wp_rewrite->extra_permastructs[ $example->rewrite_ids['example_signup'] ]['struct'], $expected );
	}
}
