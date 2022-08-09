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
}
