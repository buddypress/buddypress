<?php

/**
 * @group blogs
 * @group BP_Blogs_Blog
 */
class BP_Tests_BP_Blogs_Blog_TestCases extends BP_UnitTestCase {
	public function test_get_with_search_terms() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();

		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$b = self::factory()->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get( [
			'type'         => 'active',
			'search_terms' => 'Foo'
		] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	/**
	 * @ticket BP5858
	 */
	public function test_get_with_search_terms_should_match_description() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();

		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$b = self::factory()->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'domain' => __METHOD__,
			'user_id' => $u,
		) );
		update_blog_option( $b, 'blogdescription', 'Full of foorificness' );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get( [
			'type'         => 'active',
			'search_terms' => 'Full'
		] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
		$this->assertEquals( 1, $blogs['total'] );
	}

	public function test_search_blogs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();

		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$b = self::factory()->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
			'path' => '/path' . rand() . time() . '/',
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::search_blogs( 'Foo' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	/**
	 * @group get_by_letter
	 */
	public function test_get_by_letter() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
			return;
		}

		$old_user = get_current_user_id();

		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$b = self::factory()->blog->create( array(
			'title' => 'Foo Bar Blog',
			'user_id' => $u,
			'path' => '/path' . rand() . time() . '/',
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get_by_letter( 'F' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	/**
	 * @group get_order_by
	 */
	public function test_get_order_by() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();

		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$bs = array(
			'foobar' => self::factory()->blog->create( array(
				'title' => 'Foo Bar Blog',
				'user_id' => $u,
				'path' => '/path' . rand() . time() . '/',
			) ),
			'barfoo' => self::factory()->blog->create( array(
				'title' => 'Bar foo Blog',
				'user_id' => $u,
				'path' => '/path' . rand() . time() . '/',
			) ),
		);

		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		foreach ( $bs as $b ) {
			update_blog_option( $b, 'blog_public', '1' );
		}

		// Used to make sure barfoo is older than foobar
		$b_time = date_i18n( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );

		/* Alphabetical */
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'alphabetical', 'user_id' => $u ] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['barfoo'], $bs['foobar'] ), $blog_ids );

		/* Newest */
		update_blog_details( $bs['barfoo'], array( 'registered' => $b_time ) );
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'newest', 'user_id' => $u ] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['foobar'], $bs['barfoo'] ), $blog_ids );

		/* Active */
		bp_blogs_update_blogmeta( $bs['barfoo'], 'last_activity', $b_time );
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'active', 'user_id' => $u ] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['foobar'],$bs['barfoo'] ), $blog_ids );

		/* Random */
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'random', 'user_id' => $u ] );
		$this->assertTrue( 2 == count( $blogs['blogs'] ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_before() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		wp_set_current_user( $u );

		$r = [
			'user_id' => $u
		];

		$b1 = self::factory()->blog->create( $r );
		$b2 = self::factory()->blog->create( $r );
		$b3 = self::factory()->blog->create( $r );

		bp_blogs_record_existing_blogs();

		// Set last activity for each site.
		bp_blogs_update_blogmeta( $b1, 'last_activity', date( 'Y-m-d H:i:s', time() ) );
		bp_blogs_update_blogmeta( $b2, 'last_activity', '2008-03-25 17:13:55' );
		bp_blogs_update_blogmeta( $b3, 'last_activity', '2010-01-01 12:00' );

		// 'date_query' before test
		$sites = BP_Blogs_Blog::get( array(
			'date_query' => array( array(
				'before' => array(
					'year'  => 2010,
					'month' => 1,
					'day'   => 1,
				),
			) )
		) );

		$this->assertEquals( [ $b2 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_range() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		wp_set_current_user( $u );

		$r = [
			'user_id' => $u
		];

		$b1 = self::factory()->blog->create( $r );
		$b2 = self::factory()->blog->create( $r );
		$b3 = self::factory()->blog->create( $r );

		bp_blogs_record_existing_blogs();

		// Set last activity for each site.
		bp_blogs_update_blogmeta( $b1, 'last_activity', date( 'Y-m-d H:i:s', time() ) );
		bp_blogs_update_blogmeta( $b2, 'last_activity', '2008-03-25 17:13:55' );
		bp_blogs_update_blogmeta( $b3, 'last_activity', '2001-01-01 12:00' );

		// 'date_query' range test
		$sites = BP_Blogs_Blog::get( array(
			'date_query' => array( array(
				'after'  => 'January 2nd, 2001',
				'before' => array(
					'year'  => 2010,
					'month' => 1,
					'day'   => 1,
				),
				'inclusive' => true,
			) )
		) );

		$this->assertEquals( [ $b2 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_after() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		wp_set_current_user( $u );

		$r = [
			'user_id' => $u
		];

		$b1 = self::factory()->blog->create( $r );
		$b2 = self::factory()->blog->create( $r );
		$b3 = self::factory()->blog->create( $r );

		bp_blogs_record_existing_blogs();

		// Set last activity for each site.
		bp_blogs_update_blogmeta( $b1, 'last_activity', date( 'Y-m-d H:i:s', time() ) );
		bp_blogs_update_blogmeta( $b2, 'last_activity', '2008-03-25 17:13:55' );
		bp_blogs_update_blogmeta( $b3, 'last_activity', '2001-01-01 12:00' );

		/*
		 * Set initial site's last activity to two days ago so our expected site
		 * is the only match.
		 */
		bp_blogs_update_blogmeta( 1, 'last_activity', date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ) );

		// 'date_query' after and relative test
		$sites = BP_Blogs_Blog::get( array(
			'date_query' => array( array(
				'after' => '1 day ago'
			) )
		) );

		$this->assertEquals( [ $b1 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}
}
