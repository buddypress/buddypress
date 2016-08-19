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
	public function test_bp_format_time_gmt_offset_no_timezone_string() {
		$time          = 1395169200;
		$gmt_offset    = '-6';
		$just_date     = false;
		$localize_time = true;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'gmt_offset', $gmt_offset );
		update_option( 'timezone_string', '' );

		$this->assertEquals( 'March 18, 2014 at 1:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
	}

	/**
	 * @group bp_format_time
	 */
	public function test_bp_format_time_timezone_string_no_gmt_offset() {
		$time           = 1395169200;
		$timzone_string = 'America/Chicago';
		$just_date      = false;
		$localize_time  = true;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'timezone_string', $timzone_string );
		update_option( 'gmt_offset', '0' );

		$this->assertEquals( 'March 18, 2014 at 2:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
	}

	/**
	 * @group bp_format_time
	 */
	public function test_bp_format_time_gmt_offset_no_localize() {
		$time          = 1395169200;
		$gmt_offset    = '-6';
		$just_date     = false;
		$localize_time = false;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'gmt_offset', $gmt_offset );
		update_option( 'timezone_string', '' );

		$this->assertEquals( 'March 18, 2014 at 7:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
	}

	/**
	 * @group bp_format_time
	 */
	public function test_bp_format_time_timezone_string_no_localize() {
		$time           = 1395169200;
		$timzone_string = 'America/Chicago';
		$just_date      = false;
		$localize_time  = false;

		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		update_option( 'timezone_string', $timzone_string );
		update_option( 'gmt_offset', '0' );

		$this->assertEquals( 'March 18, 2014 at 7:00 pm', bp_format_time( $time, $just_date, $localize_time ) );
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
	 * @group bp_core_get_root_option
	 */
	public function test_bp_core_get_root_option_with_unpopulated_cache() {
		// Back up and unset global cache.
		$old_options = buddypress()->site_options;
		unset( buddypress()->site_options );

		$this->assertSame( $old_options['avatar_default'], bp_core_get_root_option( 'avatar_default' ) );

		// Clean up.
		buddypress()->site_options = $old_options;
	}

	/**
	 * @group bp_core_get_root_option
	 */
	public function test_bp_core_get_root_option_with_populated_cache() {
		// Back up and unset global cache.
		$old_options = buddypress()->site_options;
		buddypress()->site_options = bp_core_get_root_options();
		$expected = buddypress()->site_options['avatar_default'];

		$this->assertSame( $expected, bp_core_get_root_option( 'avatar_default' ) );
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

	/**
	 * @group bp_core_time_since
	 */
	public function test_bp_core_time_since_mysql_and_unix_timestamp_return_same_value() {
		$mysql_date   = '2008-03-25 17:13:55';

		$ts_mysql     = bp_core_time_since( $mysql_date );
		$ts_timestamp = bp_core_time_since( strtotime( $mysql_date ) );

		$this->assertSame( $ts_mysql, $ts_timestamp );
	}

	/**
	 * @group bp_attachments
	 * @group bp_upload_dir
	 */
	public function test_bp_upload_dir() {
		$expected_upload_dir = wp_upload_dir();

		if ( is_multisite() ) {
			$b = $this->factory->blog->create();
			switch_to_blog( $b );
		}

		$tested_upload_dir = bp_upload_dir();

		if ( is_multisite() ) {
			restore_current_blog();
		}

		$this->assertSame( $expected_upload_dir, $tested_upload_dir );
	}

	/**
	 * @group bp_is_active
	 */
	public function test_bp_is_active_component() {
		$bp = buddypress();
		$reset_active_components = $bp->active_components;

		$this->assertTrue( bp_is_active( 'members' ) );

		$this->assertFalse( bp_is_active( 'foo' ) );

		// Create and activate the foo component
		$bp->foo = new BP_Component;
		$bp->foo->id   = 'foo';
		$bp->foo->slug = 'foo';
		$bp->foo->name = 'Foo';
		$bp->active_components[ $bp->foo->id ] = 1;

		$this->assertTrue( bp_is_active( 'foo' ) );

		add_filter( 'bp_is_active', '__return_false' );

		$this->assertFalse( bp_is_active( 'foo' ) );

		remove_filter( 'bp_is_active', '__return_false' );

		// Reset buddypress() vars
		$bp->active_components = $reset_active_components;
	}

	/**
	 * @group bp_is_active
	 */
	public function test_bp_is_active_feature() {
		$bp = buddypress();
		$reset_active_components = $bp->active_components;

		// Create and activate the foo component
		$bp->foo = new BP_Component;
		$bp->foo->id   = 'foo';
		$bp->foo->slug = 'foo';
		$bp->foo->name = 'Foo';
		$bp->active_components[ $bp->foo->id ] = 1;

		// foo did not register 'bar' as a feature
		$this->assertFalse( bp_is_active( 'foo', 'bar' ) );

		// fake registering the 'bar' feature
		$bp->foo->features = array( 'bar' );
		$this->assertTrue( bp_is_active( 'foo', 'bar' ) );

		// test the feature filter
		add_filter( 'bp_is_foo_bar_active', '__return_false' );
		$this->assertFalse( bp_is_active( 'foo', 'bar' ) );
		remove_filter( 'bp_is_foo_bar_active', '__return_false' );

		// test the main component filter
		add_filter( 'bp_is_active', '__return_false' );
		$this->assertFalse( bp_is_active( 'foo', 'bar' ) );
		remove_filter( 'bp_is_active', '__return_false' );

		// Reset buddypress() vars
		$bp->active_components = $reset_active_components;
	}

	/**
	 * @group bp_attachments
	 */
	public function test_bp_attachments_get_allowed_types() {
		$supported = array( 'jpeg', 'gif', 'png' );

		$avatar = bp_attachments_get_allowed_types( 'avatar' );
		$this->assertSame( $supported, $avatar );

		$cover_image = bp_attachments_get_allowed_types( 'cover_image' );
		$this->assertSame( $supported, $cover_image );

		$images = bp_attachments_get_allowed_types( 'image/' );

		foreach ( $images as $image ) {
			if ( 'image' !== wp_ext2type( $image ) ) {
				$not_image = $image;
			}
		}

		$this->assertTrue( empty( $not_image ) );
	}

	public function test_emails_should_have_correct_link_color() {
		$appearance = bp_email_get_appearance_settings();

		$content    = '<a href="http://example.com">example</a>';
		$link_color = 'style="color: ' . esc_attr( $appearance['highlight_color'] ) . ';';
		$result     = bp_email_add_link_color_to_template( $content, 'template', 'add-content' );
		$this->assertContains( $link_color, $result );

		$content     = '<a href="http://example.com" style="display: block">example</a>';
		$link_color .= 'display: block';
		$result      = bp_email_add_link_color_to_template( $content, 'template', 'add-content' );
		$this->assertContains( $link_color, $result );
	}

	/**
	 * @group bp_core_add_page_mappings
	 */
	public function test_bp_core_add_page_mappings() {
		$bp = buddypress();
		$reset_bp_pages = $bp->pages;

		$expected = array( 'activity', 'groups', 'members' );
		if ( is_multisite() ) {
			$expected = array( 'activity', 'blogs', 'groups', 'members' );
		}

		bp_core_add_page_mappings( $bp->active_components );
		$bp_pages = array_keys( bp_get_option( 'bp-pages' ) );
		sort( $bp_pages );

		$this->assertEquals( $expected, $bp_pages );

		$bp->pages = $reset_bp_pages;
	}
}
