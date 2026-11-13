<?php

include_once BP_TESTS_DIR . '/assets/class-bptest-component.php';

/**
 * @group core
 */
class BP_Tests_BP_Component_TestCases extends BP_UnitTestCase {

	public function test_register_global_tables_uses_filtered_table_names() {
		$component     = new BP_Component();
		$component->id = 'test_component';
		$filter        = function ( $tables ) {
			$tables['table_name'] = 'filtered_table';
			return $tables;
		};

		add_filter( 'bp_test_component_global_tables', $filter );
		try {
			$component->register_global_tables( array( 'table_name' => 'original_table' ) );
		} finally {
			remove_filter( 'bp_test_component_global_tables', $filter );
		}

		$this->assertSame( 'filtered_table', $component->table_name );
		$this->assertSame( array( 'table_name' => 'filtered_table' ), $component->global_tables );
	}

	public function test_register_meta_tables_uses_filtered_table_names() {
		global $wpdb;

		$component     = new BP_Component();
		$component->id = 'test_component';
		$tables        = array( 'bp_test_component' => $wpdb->prefix . 'filtered_meta' );
		$filter        = function () use ( $tables ) {
			return $tables;
		};

		$property = 'bp_test_componentmeta';
		$existed  = isset( $wpdb->$property );
		$previous = $existed ? $wpdb->$property : null;

		add_filter( 'bp_test_component_meta_tables', $filter );
		try {
			$component->register_meta_tables( array( 'bp_test_component' => 'original_meta' ) );

			$this->assertSame( $tables, $component->meta_tables );
			$this->assertSame( $tables['bp_test_component'], $wpdb->$property );
		} finally {
			remove_filter( 'bp_test_component_meta_tables', $filter );
			if ( $existed ) {
				$wpdb->$property = $previous;
			} else {
				unset( $wpdb->$property );
			}
		}
	}

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

		$this->assertSame( $expected, $example->block_globals['bp/example-block']->props );
	}

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

		$this->assertSame( $expected, $example->rewrite_ids );
	}

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
		$this->assertSame( $wp_rewrite->rewritereplace[ $position ], $expected_directory_regex );

		$position = array_search( '%' . $example->rewrite_ids['directory_type'] . '%', $wp_rewrite->rewritecode, true );
		$this->assertSame( $wp_rewrite->rewritereplace[ $position ], $rewrite_tags['directory_type'] );
	}

	/**
	 * @dataProvider rest_controller_filters
	 */
	public function test_rest_api_init_respects_controller_filter( $filtered, $expected ) {
		global $wp_rest_server;

		$previous       = $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$component      = new BP_Component();
		$component->id  = 'test_component';
		$filter         = function () use ( $filtered ) {
			return $filtered;
		};

		add_filter( 'bp_test_component_rest_api_controllers', $filter );
		try {
			// Initialize REST before testing registration on an empty server.
			do_action( 'rest_api_init' );
			$wp_rest_server = new WP_REST_Server();
			$component->rest_api_init( array( 'BP_Core_Components_REST_Controller' ) );
			$routes = $wp_rest_server->get_routes();

			$this->assertSame( $expected, isset( $routes['/buddypress/v2/components'] ) );
		} finally {
			remove_filter( 'bp_test_component_rest_api_controllers', $filter );
			$wp_rest_server = $previous;
		}
	}

	public function rest_controller_filters() {
		return array(
			'allowed controller' => array( array( 'BP_Core_Components_REST_Controller' ), true ),
			'disabled controller' => array( array(), false ),
			'unlisted controller' => array( array( 'BP_Unlisted_Test_REST_Controller' ), false ),
		);
	}

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
		$this->assertSame( $wp_rewrite->extra_rules_top[ $rewrite_rules['directory_type']['regex'] ], $rewrite_rules['directory_type']['query'] );
	}

	public function test_register_nav_generates_rewrite_ids_and_ignores_incomplete_subnav() {
		$component     = new BP_Component();
		$component->id = 'test_component';
		$component->register_nav(
			array( 'slug' => 'test-component' ),
			array(
				array( 'slug' => 'missing-parent' ),
				array( 'parent_slug' => 'missing-child' ),
				array( 'slug' => 'test-child', 'parent_slug' => 'test-component' ),
			)
		);

		$this->assertSame( 'test_component', $component->main_nav['component_id'] );
		$this->assertSame( 'bp_member_test_component', $component->main_nav['rewrite_id'] );
		$this->assertCount( 1, $component->sub_nav );
		$this->assertSame( 'bp_member_test_component_test_child', $component->sub_nav[0]['rewrite_id'] );
	}

	public function test_register_nav_preserves_explicit_rewrite_ids() {
		$component = new BP_Component();
		$component->register_nav(
			array( 'slug' => 'test-component', 'rewrite_id' => 'custom_main' ),
			array( array( 'slug' => 'test-child', 'parent_slug' => 'test-component', 'rewrite_id' => 'custom_child' ) )
		);

		$this->assertSame( 'custom_main', $component->main_nav['rewrite_id'] );
		$this->assertSame( 'custom_child', $component->sub_nav[0]['rewrite_id'] );
	}

	public function test_register_nav_can_disable_rewrite_ids() {
		$component = new BP_Component();
		$component->register_nav(
			array( 'slug' => 'test-component', 'rewrite_id' => false ),
			array( array( 'slug' => 'test-child', 'parent_slug' => 'test-component', 'rewrite_id' => false ) )
		);

		$this->assertArrayNotHasKey( 'rewrite_id', $component->main_nav );
		$this->assertArrayNotHasKey( 'rewrite_id', $component->sub_nav[0] );
	}

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
		$this->assertSame( $wp_rewrite->extra_permastructs[ $example->rewrite_ids['example_signup'] ]['struct'], $expected );
	}
}
