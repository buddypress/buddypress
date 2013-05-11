<?php

/**
 * @group core
 */

class BP_Tests_Core_Functions extends BP_UnitTestCase {
	/**
	 * @group bp_esc_sql_order
	 */
	public function test_bp_esc_sql_order_ASC() {
		$this->assertEquals( 'ASC', bp_esc_sql_order( 'ASC' ) );
	}

	/**
	 * @group bp_esc_sql_order
	 */
	public function test_bp_esc_sql_order_DESC() {
		$this->assertEquals( 'DESC', bp_esc_sql_order( 'DESC' ) );
	}

	/**
	 * @group bp_esc_sql_order
	 */
	public function test_bp_esc_sql_order_desc_lowercase() {
		$this->assertEquals( 'DESC', bp_esc_sql_order( 'desc' ) );
	}

	/**
	 * @group bp_esc_sql_order
	 */
	public function test_bp_esc_sql_order_desc_whitespace() {
		$this->assertEquals( 'DESC', bp_esc_sql_order( ' desc ' ) );
	}

	/**
	 * @group bp_esc_sql_order
	 */
	public function test_bp_esc_sql_order_invalid() {
		$this->assertEquals( 'ASC', bp_esc_sql_order( 'In ur base killin ur d00dz' ) );
	}

}
