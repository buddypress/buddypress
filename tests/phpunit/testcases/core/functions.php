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
	 * @group bp_format_time
	 */
	public function test_bp_format_time_gmt_offset() {
		$time          = 1395169200;
		$gmt_offset    = '-6';
		$just_date     = false;
		$localize_time = true;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'gmt_offset', $gmt_offset );
		delete_option( 'timezone_string' );

		$this->assertEquals( 'March 18, 2014 at 1:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
	}

	/**
	 * @group bp_format_time
	 */
	public function test_bp_format_time_timezone_string() {
		$time           = 1395169200;
		$timzone_string = 'America/Chicago';
		$just_date      = false;
		$localize_time  = true;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'timezone_string', $timzone_string );
		delete_option( 'gmt_offset' );

		$this->assertEquals( 'March 18, 2014 at 1:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
	}

	/**
	 * @group bp_sort_by_key
	 */
	public function test_bp_sort_by_key_arrays_num() {
		$items = array(
			array(
				'foo' => 'bar',
				'value' => 5,
			),
			array(
				'foo' => 'bar',
				'value' => 10,
			),
			array(
				'foo' => 'bar',
				'value' => 1,
			),
		);

		$expected = array(
			array(
				'foo' => 'bar',
				'value' => 1,
			),
			array(
				'foo' => 'bar',
				'value' => 5,
			),
			array(
				'foo' => 'bar',
				'value' => 10,
			),
		);

		$this->assertEquals( $expected, bp_sort_by_key( $items, 'value', 'num' ) );
	}

	/**
	 * @group bp_sort_by_key
	 */
	public function test_bp_sort_by_key_objects_num() {
		$items = array(
			new stdClass,
			new stdClass,
			new stdClass,
		);
		$items[0]->foo = 'bar';
		$items[0]->value = 5;
		$items[1]->foo = 'bar';
		$items[1]->value = 10;
		$items[2]->foo = 'bar';
		$items[2]->value = 1;

		$expected = array(
			new stdClass,
			new stdClass,
			new stdClass,
		);
		$expected[0]->foo = 'bar';
		$expected[0]->value = 1;
		$expected[1]->foo = 'bar';
		$expected[1]->value = 5;
		$expected[2]->foo = 'bar';
		$expected[2]->value = 10;

		$this->assertEquals( $expected, bp_sort_by_key( $items, 'value', 'num' ) );
	}

	/**
	 * @group bp_sort_by_key
	 */
	public function test_bp_sort_by_key_num_should_respect_0() {
		$items = array(
			array(
				'foo' => 'bar',
				'value' => 2,
			),
			array(
				'foo' => 'bar',
				'value' => 0,
			),
			array(
				'foo' => 'bar',
				'value' => 4,
			),
		);

		$expected = array(
			array(
				'foo' => 'bar',
				'value' => 0,
			),
			array(
				'foo' => 'bar',
				'value' => 2,
			),
			array(
				'foo' => 'bar',
				'value' => 4,
			),
		);

		$this->assertEquals( $expected, bp_sort_by_key( $items, 'value', 'num' ) );
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
	 * @group pagination
	 * @group bp_sanitize_pagination_arg
	 */
	public function test_bp_sanitize_pagination_arg_zero() {
		$request          = $_REQUEST;
		$arg              = 'bp_pagination_test';
		$page             = 1;
		$_REQUEST[ $arg ] = '0';
		$value            = bp_sanitize_pagination_arg( $arg, $page );

		$this->assertEquals( $value, $page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group bp_sanitize_pagination_arg
	 */
	public function test_bp_sanitize_pagination_arg_negative() {
		$request          = $_REQUEST;
		$arg              = 'bp_pagination_test';
		$page             = 25;
		$_REQUEST[ $arg ] = '-25';
		$value            = bp_sanitize_pagination_arg( $arg, $page );

		$this->assertEquals( $value, $page );

		$_REQUEST = $request;
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
	 * @group bp_core_get_root_options
	 */
	public function test_bp_core_get_root_options_cache_invalidate() {
		$keys = array_keys( bp_get_default_options() );
		$keys[] = 'registration';
		$keys[] = 'avatar_default';

		foreach ( $keys as $key ) {
			// prime cache
			$root_options = bp_core_get_root_options();

			bp_update_option( $key, 'foo' );

			$this->assertFalse( wp_cache_get( 'root_blog_options', 'bp' ), 'Cache not invalidated after updating "' . $key . '"' );
		}

		if ( is_multisite() ) {
			$ms_keys = array(
				'tags_blog_id',
				'sitewide_tags_blog',
				'registration',
				'fileupload_mask',
			);

			foreach ( $ms_keys as $ms_key ) {
				$root_options = bp_core_get_root_options();

				update_site_option( $ms_key, 'foooooooo' );

				$this->assertFalse( wp_cache_get( 'root_blog_options', 'bp' ), 'Cache not invalidated after updating "' . $ms_key . '"' );
			}
		}
	}

	/**
	 * @group bp_core_add_root_component
	 */
	public function test_add_root_component_not_in_bp_pages() {
		buddypress()->foo = new stdClass;
		buddypress()->foo->id = 'foo';
		buddypress()->foo->slug = 'foo';

		bp_core_add_root_component( 'foo' );

		$this->assertTrue( in_array( 'foo', buddypress()->add_root ) );
		$this->assertTrue( buddypress()->foo->has_directory );
		$this->assertNotEmpty( buddypress()->loaded_components['foo'] );
	}

	/**
	 * @group bp_core_time_since
	 * @group bp_core_current_time
	 */
	public function test_bp_core_time_since_timezone_minute_ago() {
		// backup timezone
		$tz_backup = date_default_timezone_get();

		// set timezone to something other than UTC
		date_default_timezone_set( 'Europe/Paris' );

		$this->assertSame( '1 minute ago', bp_core_time_since( time() - 60 ) );

		// revert timezone back to normal
		if ( $tz_backup ) {
			date_default_timezone_set( $tz_backup );
		}
	}

	/**
	 * @group bp_core_time_since
	 * @group bp_core_current_time
	 */
	public function test_bp_core_time_since_timezone() {
		// backup timezone
		$tz_backup = date_default_timezone_get();

		// set timezone to something other than UTC
		date_default_timezone_set( 'Europe/Paris' );

		$this->assertSame( '1 hour ago', bp_core_time_since( time() - 60*60 ) );

		// revert timezone back to normal
		if ( $tz_backup ) {
			date_default_timezone_set( $tz_backup );
		}
	}
}
