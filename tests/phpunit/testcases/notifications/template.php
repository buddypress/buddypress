<?php
/**
 * @group notifications
 * @group template
 */
class BP_Tests_Notifications_Template extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @group BP_Notifications_Template
	 */
	public function test_bp_notifications_template_should_give_precedence_to_npage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['npage'] = '5';

		$at = new BP_Notifications_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group BP_Notifications_Template
	 */
	public function test_bp_notifications_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['npage'] = '0';

		$at = new BP_Notifications_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group BP_Notifications_Template
	 */
	public function test_bp_notifications_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Notifications_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group BP_Notifications_Template
	 */
	public function test_bp_notifications_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Notifications_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}
}
