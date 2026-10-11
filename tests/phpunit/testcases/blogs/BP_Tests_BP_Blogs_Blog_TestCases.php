<?php

/**
 * @group blogs
 * @group BP_Blogs_Blog
 */
class BP_Tests_BP_Blogs_Blog_TestCases extends BP_UnitTestCase {
	public function test_self_removing_sql_clauses_filter_at_priority_zero() {
		$this->skipWithoutMultisite();

		$user_id = self::factory()->user->create();
		$allowed = self::factory()->blog->create( array( 'user_id' => $user_id ) );
		$other   = self::factory()->blog->create( array( 'user_id' => $user_id ) );
		bp_blogs_record_existing_blogs();
		bp_blogs_update_blogmeta( $allowed, 'last_activity', '2026-01-01 12:00:00' );
		bp_blogs_update_blogmeta( $other, 'last_activity', '2026-01-02 12:00:00' );

		$calls  = 0;
		$filter = static function( $clauses ) use ( $allowed, &$calls, &$filter ) {
			remove_filter( 'bp_blogs_get_sql_clauses', $filter, 0 );
			++$calls;
			$clauses['where_conditions']['allowed'] = "b.blog_id = {$allowed}";

			return $clauses;
		};

		add_filter( 'bp_blogs_get_sql_clauses', $filter, 0 );

		try {
			$found = BP_Blogs_Blog::get( array( 'include_blog_ids' => array( $allowed, $other ), 'per_page' => 1, 'page' => 1 ) );
		} finally {
			remove_filter( 'bp_blogs_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( array( $allowed ), wp_list_pluck( $found['blogs'], 'blog_id' ) );
		$this->assertSame( 1, $found['total'] );
		$this->assertSame( 1, $calls );
	}

	public function test_get_sql_clauses_join_filters_results_and_total() {
		$this->skipWithoutMultisite();

		$user_id = self::factory()->user->create();
		$first   = self::factory()->blog->create( array( 'user_id' => $user_id ) );
		$second  = self::factory()->blog->create( array( 'user_id' => $user_id ) );
		bp_blogs_record_existing_blogs();
		bp_blogs_update_blogmeta( $first, 'last_activity', '2026-01-01 12:00:00' );
		bp_blogs_update_blogmeta( $second, 'last_activity', '2026-01-02 12:00:00' );

		$args = array( 'include_blog_ids' => array( $first, $second ), 'per_page' => 1, 'page' => 1 );

		$this->assertSame( 2, BP_Blogs_Blog::get( $args )['total'] );

		$calls  = array();
		$filter = static function( $clauses, $parsed_args ) use ( $first, &$calls ) {
			$calls[] = $parsed_args;
			$clauses['join'] .= " INNER JOIN (SELECT {$first} AS blog_id UNION ALL SELECT {$first}) access ON access.blog_id = b.blog_id";
			$clauses['where_conditions']['access'] = "access.blog_id = {$first}";

			return $clauses;
		};

		add_filter( 'bp_blogs_get_sql_clauses', $filter, 10, 2 );

		try {
			$found = BP_Blogs_Blog::get( $args );
		} finally {
			remove_filter( 'bp_blogs_get_sql_clauses', $filter );
		}

		$this->assertSame( array( $first ), wp_list_pluck( $found['blogs'], 'blog_id' ) );
		$this->assertSame( 1, $found['total'] );
		$this->assertCount( 1, $calls );
		$this->assertSame( array( $first, $second ), $calls[0]['include_blog_ids'] );
	}

	public function test_get_sql_filters_receive_context_and_can_replace_queries() {
		$this->skipWithoutMultisite();

		$contexts = array();
		$filter   = static function( $sql, $type, $args, $clauses ) use ( &$contexts ) {
			$contexts[ $type ] = array( $args, $clauses );

			return 'count' === $type ? 'SELECT 42' : str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql );
		};

		add_filter( 'bp_blogs_get_sql', $filter, 10, 4 );

		try {
			$found = BP_Blogs_Blog::get( array( 'type' => 'newest', 'per_page' => 1, 'page' => 1 ) );
		} finally {
			remove_filter( 'bp_blogs_get_sql', $filter );
		}

		$this->assertSame( array(), $found['blogs'] );
		$this->assertSame( 42, $found['total'] );
		$this->assertSame( array( 'paged', 'count' ), array_keys( $contexts ) );
		$this->assertSame( 'newest', $contexts['paged'][0]['type'] );
		$this->assertSame( $contexts['paged'], $contexts['count'] );
		$this->assertArrayHasKey( 'where_conditions', $contexts['paged'][1] );
	}

	public function test_get_without_callbacks_preserves_sql_text() {
		$this->skipWithoutMultisite();

		global $wpdb;

		$bp                     = buddypress();
		$hidden_sql             = 'AND wb.public = 1';
		$search_terms_left_join = '';
		$search_terms_sql       = '';
		$user_sql               = '';
		$include_sql            = '';
		$date_query_sql         = '';
		$order_sql              = 'ORDER BY bm.meta_value DESC';
		$pag_sql                = ' LIMIT 0, 1';
		$expected_paged          = "
			SELECT b.blog_id, b.user_id as admin_user_id, u.user_email as admin_user_email, wb.domain, wb.path, bm.meta_value as last_activity, bm_name.meta_value as name
			FROM
			  {$bp->blogs->table_name} b
			  LEFT JOIN {$bp->blogs->table_name_blogmeta} bm ON (b.blog_id = bm.blog_id)
			  LEFT JOIN {$bp->blogs->table_name_blogmeta} bm_name ON (b.blog_id = bm_name.blog_id)
			  {$search_terms_left_join}
			  LEFT JOIN {$wpdb->base_prefix}blogs wb ON (b.blog_id = wb.blog_id)
			  LEFT JOIN {$wpdb->users} u ON (b.user_id = u.ID)
			WHERE
			  wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql}
			  AND bm.meta_key = 'last_activity' AND bm_name.meta_key = 'name'
			  {$search_terms_sql} {$user_sql} {$include_sql} {$date_query_sql}
			GROUP BY b.blog_id {$order_sql} {$pag_sql}
		";
		$expected_count          = "
			SELECT COUNT(DISTINCT b.blog_id)
			FROM
			  {$bp->blogs->table_name} b
			  LEFT JOIN {$wpdb->base_prefix}blogs wb ON (b.blog_id = wb.blog_id)
			  LEFT JOIN {$bp->blogs->table_name_blogmeta} bm ON (b.blog_id = bm.blog_id)
			  LEFT JOIN {$bp->blogs->table_name_blogmeta} bm_name ON (b.blog_id = bm_name.blog_id)
			  {$search_terms_left_join}
			WHERE
			  wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0 {$hidden_sql}
			  AND bm.meta_key = 'last_activity' AND bm_name.meta_key = 'name'
			  {$search_terms_sql} {$user_sql} {$include_sql} {$date_query_sql}
		";

		$queries = array();
		$capture = static function( $sql ) use ( &$queries, $bp ) {
			if ( false !== strpos( $sql, "{$bp->blogs->table_name} b" ) ) {
				$queries[] = $sql;
			}

			return $sql;
		};

		$old_user = get_current_user_id();
		wp_set_current_user( 0 );

		add_filter( 'query', $capture );

		try {
			BP_Blogs_Blog::get( array( 'per_page' => 1, 'page' => 1 ) );
		} finally {
			remove_filter( 'query', $capture );
			wp_set_current_user( $old_user );
		}

		$this->assertSame( array( $expected_paged, $expected_count ), $queries );
	}

	public function test_get_with_search_terms() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( array( $b ), $blog_ids );
	}

	/**
	 * @ticket BP5858
	 */
	public function test_get_with_search_terms_should_match_description() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( array( $b ), $blog_ids );
		$this->assertSame( 1, $blogs['total'] );
	}

	public function test_search_blogs() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( array( $b ), $blog_ids );
	}

	/**
	 * @group get_by_letter
	 */
	public function test_get_by_letter() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( array( $b ), $blog_ids );
	}

	/**
	 * @group get_order_by
	 */
	public function test_get_order_by() {
		$this->skipWithoutMultisite();

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
		$this->assertSame( array( $bs['barfoo'], $bs['foobar'] ), $blog_ids );

		/* Newest */
		update_blog_details( $bs['barfoo'], array( 'registered' => $b_time ) );
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'newest', 'user_id' => $u ] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertSame( array( $bs['foobar'], $bs['barfoo'] ), $blog_ids );

		/* Active */
		bp_blogs_update_blogmeta( $bs['barfoo'], 'last_activity', $b_time );
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'active', 'user_id' => $u ] );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertSame( array( $bs['foobar'],$bs['barfoo'] ), $blog_ids );

		/* Random */
		$blogs = BP_Blogs_Blog::get( [ 'type' => 'random', 'user_id' => $u ] );
		$this->assertCount( 2, $blogs['blogs'] );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_before() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( [ $b2 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_range() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( [ $b2 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}

	/**
	 * @group date_query
	 */
	public function test_get_with_date_query_after() {
		$this->skipWithoutMultisite();

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

		$this->assertSame( [ $b1 ], wp_list_pluck( $sites['blogs'], 'blog_id' ) );
	}
}
