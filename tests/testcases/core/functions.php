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

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_years_months() {
		$now = time();
		$then = $now - ( 3 * YEAR_IN_SECONDS ) - ( 3 * 30 * DAY_IN_SECONDS );
		$this->assertEquals( '3 years, 3 months ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_years_nomonths() {
		$now = time();
		$then = $now - ( 3 * YEAR_IN_SECONDS );
		$this->assertEquals( '3 years ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_months_weeks() {
		$now = time();
		$then = $now - ( 3 * 30 * DAY_IN_SECONDS ) - ( 3 * WEEK_IN_SECONDS );
		$this->assertEquals( '3 months, 3 weeks ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_months_noweeks() {
		$now = time();
		$then = $now - ( 3 * 30 * DAY_IN_SECONDS );
		$this->assertEquals( '3 months ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_weeks_days() {
		$now = time();
		$then = $now - ( 3 * WEEK_IN_SECONDS ) - ( 3 * DAY_IN_SECONDS );
		$this->assertEquals( '3 weeks, 3 days ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_weeks_nodays() {
		$now = time();
		$then = $now - ( 3 * WEEK_IN_SECONDS );
		$this->assertEquals( '3 weeks ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_days_hours() {
		$now = time();
		$then = $now - ( 3 * DAY_IN_SECONDS ) - ( 3 * HOUR_IN_SECONDS );
		$this->assertEquals( '3 days, 3 hours ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_days_nohours() {
		$now = time();
		$then = $now - ( 3 * DAY_IN_SECONDS );
		$this->assertEquals( '3 days ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_hours_minutes() {
		$now = time();
		$then = $now - ( 3 * HOUR_IN_SECONDS ) - ( 3 * MINUTE_IN_SECONDS );
		$this->assertEquals( '3 hours, 3 minutes ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_hours_nominutes() {
		$now = time();
		$then = $now - ( 3 * HOUR_IN_SECONDS );
		$this->assertEquals( '3 hours ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 * @ticket BP5017
	 */
	public function test_bp_core_time_since_minutes_seconds() {
		$now = time();
		$then = $now - ( 3 * MINUTE_IN_SECONDS ) - 3;
		$this->assertEquals( '3 minutes, 3 seconds ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_minutes_noseconds() {
		$now = time();
		$then = $now - ( 3 * MINUTE_IN_SECONDS );
		$this->assertEquals( '3 minutes ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_seconds() {
		$now = time();
		$then = $now - 3;
		$this->assertEquals( '3 seconds ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * Sanity check for the singular version of 'year'
	 *
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_year() {
		$now = time();
		$then = $now - YEAR_IN_SECONDS;
		$this->assertEquals( '1 year ago', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_rightnow() {
		$now = time();
		$then = $now;
		$this->assertEquals( 'right now', bp_core_time_since( $then, $now ) );
	}

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_future() {
		$now = time();
		$then = $now + 100;
		$this->assertEquals( 'sometime ago', bp_core_time_since( $then, $now ) );
	}

}
