<?php

/**
 * @group core
 * @group core-template-loader
 */
class BP_Tests_Template_Loader_Functions extends BP_UnitTestCase {

	public function setUp() {
		if ( version_compare( bp_get_major_wp_version(), '5.5', '<' ) ) {
			$this->markTestSkipped(
				'Passing variables in template parts was introduced in WordPress 5.5'
			);
		}

		add_filter( 'bp_get_template_stack', array( $this, 'template_stack'), 10, 1 );

		parent::setUp();
	}

	public function tearDown() {
		remove_filter( 'bp_get_template_stack', array( $this, 'template_stack'), 10, 1 );

		parent::tearDown();
	}

	public function template_stack( $stack = array() ) {
		return array_merge(
			array(
				dirname( dirname( dirname( __FILE__ ) ) ) . '/assets/templates/',
			)
		);
	}

	/**
	 * @group bp_buffer_template_part
	 */
	public function test_bp_buffer_template_part() {
		$buffer = bp_buffer_template_part( 'index', null, false, array( 'test' => 1 ) );
		$this->assertTrue( 2 === (int) $buffer );
	}
}
