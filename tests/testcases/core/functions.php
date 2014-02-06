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
		$this->assertEquals( '3 minutes ago', bp_core_time_since( $then, $now ) );
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

	/**
	 * @group bp_alpha_sort_by_key
	 */
	public function test_bp_alpha_sort_by_key_arrays() {
		$items = array(
			array(
				'foo' => 'bar',
				'name' => 'alpha',
			),
			array(
				'foo' => 'bar',
				'name' => 'charlie',
			),
			array(
				'foo' => 'bar',
				'name' => 'beta',
			),
		);

		$expected = array(
			array(
				'foo' => 'bar',
				'name' => 'alpha',
			),
			array(
				'foo' => 'bar',
				'name' => 'beta',
			),
			array(
				'foo' => 'bar',
				'name' => 'charlie',
			),
		);

		$this->assertEquals( $expected, bp_alpha_sort_by_key( $items, 'name' ) );
	}

	/**
	 * @group bp_alpha_sort_by_key
	 */
	public function test_bp_alpha_sort_by_key_objects() {
		$items = array(
			new stdClass,
			new stdClass,
			new stdClass,
		);
		$items[0]->foo = 'bar';
		$items[0]->name = 'alpha';
		$items[1]->foo = 'bar';
		$items[1]->name = 'charlie';
		$items[2]->foo = 'bar';
		$items[2]->name = 'beta';

		$expected = array(
			new stdClass,
			new stdClass,
			new stdClass,
		);
		$expected[0]->foo = 'bar';
		$expected[0]->name = 'alpha';
		$expected[1]->foo = 'bar';
		$expected[1]->name = 'beta';
		$expected[2]->foo = 'bar';
		$expected[2]->name = 'charlie';

		$this->assertEquals( $expected, bp_alpha_sort_by_key( $items, 'name' ) );
	}

	/**
	 * @group bp_core_get_directory_pages
	 */
	public function test_bp_core_get_directory_pages_after_page_edit() {
		// Set the cache
		$pages = bp_core_get_directory_pages();

		// Update one of the posts
		switch_to_blog( bp_get_root_blog_id() );

		// grab the first one
		foreach ( $pages as $page ) {
			$p = $page;
			break;
		}

		$post = get_post( $p->id );
		$post->post_title .= ' Foo';
		wp_update_post( $post );

		restore_current_blog();

		$this->assertFalse( wp_cache_get( 'directory_pages', 'bp' ) );
	}

	/**
	 * @group bp_core_get_directory_pages
	 */
	public function test_bp_core_get_directory_pages_pages_settings_update() {
		// Set the cache
		$pages = bp_core_get_directory_pages();

		// Mess with it but put it back
		$v = bp_get_option( 'bp-pages' );
		bp_update_option( 'bp-pages', 'foo' );

		$this->assertFalse( wp_cache_get( 'directory_pages', 'bp' ) );

		bp_update_option( 'bp-pages', $v );
	}

}
