<?php

/**
 * @group blogs
 * @group cache
 */
class BP_Tests_Blogs_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_blogs_update_meta_cache
	 */
	public function test_bp_blogs_update_meta_cache() {
		if ( ! is_multisite() ) {
			return;
		}

		$b1 = self::factory()->blog->create();
		$b2 = self::factory()->blog->create();

		bp_blogs_add_blogmeta( $b1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( $b1, 'foo2', 'bar2' );
		bp_blogs_add_blogmeta( $b2, 'foo', 'bar' );

		// Need this info
		$b1_name = bp_blogs_get_blogmeta( $b1, 'name' );
		$b1_description = bp_blogs_get_blogmeta( $b1, 'description' );
		$b1_last_activity = bp_blogs_get_blogmeta( $b1, 'last_activity' );

		$b2_name = bp_blogs_get_blogmeta( $b2, 'name' );
		$b2_description = bp_blogs_get_blogmeta( $b2, 'description' );
		$b2_last_activity = bp_blogs_get_blogmeta( $b2, 'last_activity' );

		// Clear caches (due to _get_)
		wp_cache_delete( $b1, 'blog_meta' );
		wp_cache_delete( $b2, 'blog_meta' );

		// Caches should be empty
		$this->assertFalse( wp_cache_get( $b1, 'blog_meta' ) );
		$this->assertFalse( wp_cache_get( $b2, 'blog_meta' ) );

		bp_blogs_update_meta_cache( array( $b1, $b2 ) );

		$b1_expected = array(
			'name' => array(
				$b1_name,
			),
			'description' => array(
				$b1_description,
			),
			'last_activity' => array(
				$b1_last_activity,
			),
			'foo' => array(
				'bar',
			),
			'foo2' => array(
				'bar2',
			),
		);

		$b2_expected = array(
			'name' => array(
				$b2_name,
			),
			'description' => array(
				$b2_description,
			),
			'last_activity' => array(
				$b2_last_activity,
			),
			'foo' => array(
				'bar',
			),
		);

		// The cache may contain more than just this, so loop through
		// and check only relevant keys
		$b1_found = wp_cache_get( $b1, 'blog_meta' );
		foreach ( $b1_expected as $k => $v ) {
			$this->assertSame( $v, $b1_found[ $k ] );
		}

		$b2_found = wp_cache_get( $b2, 'blog_meta' );
		foreach ( $b2_expected as $k => $v ) {
			$this->assertSame( $v, $b2_found[ $k ] );
		}
	}

	/**
	 * @group bp_blogs_update_meta_cache
	 * @group bp_has_blogs
	 */
	public function test_bp_blogs_update_meta_cache_bp_has_blogs() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = self::factory()->user->create();

		// Switch user so we have access to non-public blogs
		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$b1 = self::factory()->blog->create();
		$b2 = self::factory()->blog->create();

		bp_blogs_record_blog( $b1, $u, true );
		bp_blogs_record_blog( $b2, $u, true );

		bp_blogs_add_blogmeta( $b1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( $b1, 'foo2', 'bar2' );
		bp_blogs_add_blogmeta( $b2, 'foo', 'bar' );

		// Need this info
		$b1_name = bp_blogs_get_blogmeta( $b1, 'name' );
		$b1_description = bp_blogs_get_blogmeta( $b1, 'description' );
		$b1_last_activity = bp_blogs_get_blogmeta( $b1, 'last_activity' );

		$b2_name = bp_blogs_get_blogmeta( $b2, 'name' );
		$b2_description = bp_blogs_get_blogmeta( $b2, 'description' );
		$b2_last_activity = bp_blogs_get_blogmeta( $b2, 'last_activity' );

		// Clear caches (due to _get_)
		wp_cache_delete( $b1, 'blog_meta' );
		wp_cache_delete( $b2, 'blog_meta' );

		// Caches should be empty
		$this->assertFalse( wp_cache_get( $b1, 'blog_meta' ) );
		$this->assertFalse( wp_cache_get( $b2, 'blog_meta' ) );

		bp_has_blogs( array(
			'user_id' => $u,
		) );

		$b1_expected = array(
			'name' => array(
				$b1_name,
			),
			'description' => array(
				$b1_description,
			),
			'last_activity' => array(
				$b1_last_activity,
			),
			'foo' => array(
				'bar',
			),
			'foo2' => array(
				'bar2',
			),
		);

		$b2_expected = array(
			'name' => array(
				$b2_name,
			),
			'description' => array(
				$b2_description,
			),
			'last_activity' => array(
				$b2_last_activity,
			),
			'foo' => array(
				'bar',
			),
		);

		// The cache may contain more than just this, so loop through
		// and check only relevant keys
		$b1_found = wp_cache_get( $b1, 'blog_meta' );
		foreach ( $b1_expected as $k => $v ) {
			$this->assertSame( $v, $b1_found[ $k ] );
		}

		$b2_found = wp_cache_get( $b2, 'blog_meta' );
		foreach ( $b2_expected as $k => $v ) {
			$this->assertSame( $v, $b2_found[ $k ] );
		}

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_update_meta_cache
	 * @group bp_has_blogs
	 */
	public function test_bp_blogs_update_meta_cache_bp_has_blogs_false() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = self::factory()->user->create();

		// Switch user so we have access to non-public blogs
		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$b1 = self::factory()->blog->create();
		$b2 = self::factory()->blog->create();

		bp_blogs_record_blog( $b1, $u, true );
		bp_blogs_record_blog( $b2, $u, true );

		bp_blogs_add_blogmeta( $b1, 'foo', 'bar' );
		bp_blogs_add_blogmeta( $b1, 'foo2', 'bar2' );
		bp_blogs_add_blogmeta( $b2, 'foo', 'bar' );

		// Need this info
		$b1_name = bp_blogs_get_blogmeta( $b1, 'name' );
		$b1_description = bp_blogs_get_blogmeta( $b1, 'description' );
		$b1_last_activity = bp_blogs_get_blogmeta( $b1, 'last_activity' );

		$b2_name = bp_blogs_get_blogmeta( $b2, 'name' );
		$b2_description = bp_blogs_get_blogmeta( $b2, 'description' );
		$b2_last_activity = bp_blogs_get_blogmeta( $b2, 'last_activity' );

		// Clear caches (due to _get_)
		wp_cache_delete( $b1, 'blog_meta' );
		wp_cache_delete( $b2, 'blog_meta' );

		// Caches should be empty
		$this->assertFalse( wp_cache_get( $b1, 'blog_meta' ) );
		$this->assertFalse( wp_cache_get( $b2, 'blog_meta' ) );

		bp_has_blogs( array(
			'update_meta_cache' => false,
		) );

		// Caches should be empty
		$this->assertFalse( wp_cache_get( $b1, 'blog_meta' ) );
		$this->assertFalse( wp_cache_get( $b2, 'blog_meta' ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_blogs_total_blogs
	 * @group counts
	 */
	public function test_bp_blogs_total_count_should_respect_cached_value_of_0() {
		if ( ! is_multisite() ) {
			return;
		}

		global $wpdb;

		// prime cache
		// no blogs are created by default, so count is zero
		bp_blogs_total_blogs();
		$first_query_count = $wpdb->num_queries;

		// run function again
		bp_blogs_total_blogs();

		// check if function references cache or hits the DB by comparing query count
		$this->assertEquals( $first_query_count, $wpdb->num_queries );
	}

	/**
	 * @group bp_blogs_total_blogs
	 */
	public function test_bp_blogs_total_blogs_count_after_delete_blog() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = self::factory()->user->create();

		// need to make sure we set the 'public' flag due to how BP_Blogs_Blogs:get_all() works
		$b1 = self::factory()->blog->create( array(
			'meta' => array(
				'public' => 1
			)
		) );
		$b2 = self::factory()->blog->create( array(
			'meta' => array(
				'public' => 1
			)
		) );

		bp_blogs_record_blog( $b1, $u );
		bp_blogs_record_blog( $b2, $u );

		// prime total blog count
		bp_blogs_total_blogs();

		// delete a blog
		wpmu_delete_blog( $b2 );

		$this->assertEquals( 1, bp_blogs_total_blogs() );
	}

	/**
	 * @group update_blog_details
	 */
	public function test_update_blog_details_should_purge_blogmeta_cache() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = self::factory()->user->create();

		$b1 = self::factory()->blog->create();
		bp_blogs_record_blog( $b1, $u, true );

		// prime cache
		bp_blogs_get_blogmeta( $b1, 'url' );
		$this->assertNotEmpty( wp_cache_get( $b1, 'blog_meta' ) );

		// updating blog details should purge cache
		update_blog_details( $b1, array(
			'domain' => 'awesome.com'
		) );

		// assert cache is purged
		$this->assertEmpty( wp_cache_get( $b1, 'blog_meta' ) );
	}
}
