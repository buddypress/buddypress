<?php

/**
 * @group members
 * @group signups
 * @group signup
 * @group BP_Signup
 */
class BP_Tests_BP_Signup extends BP_UnitTestCase {
	protected $signup_allowed;

	public function set_up() {
		if ( is_multisite() ) {
			$this->signup_allowed = get_site_option( 'registration' );
			update_site_option( 'registration', 'all' );
		} else {
			bp_get_option( 'users_can_register' );
			bp_update_option( 'users_can_register', 1 );
		}

		parent::set_up();
	}

	public function tear_down() {
		if ( is_multisite() ) {
			update_site_option( 'registration', $this->signup_allowed );
		} else {
			bp_update_option( 'users_can_register', $this->signup_allowed );
		}

		parent::tear_down();
	}


	public function test_get_sql_clauses_filter_duplicate_join_and_cached_totals() {
		$first  = self::factory()->signup->create();
		$second = self::factory()->signup->create();

		$args   = array( 'fields' => 'ids', 'number' => 2, 'include' => array( $first, $second ) );

		$this->assertSame( 2, BP_Signup::get( $args )['total'] );

		$selected = $first;
		$calls    = array();
		$filter   = static function( $clauses, $parsed ) use ( &$selected, &$calls, &$filter ) {
			remove_filter( 'bp_members_signups_get_sql_clauses', $filter, 0 );
			$calls[] = $parsed;
			$clauses['join'] .= " INNER JOIN (SELECT {$selected} AS allowed_id UNION ALL SELECT {$selected}) access ON access.allowed_id = signup_id";
			$clauses['where_conditions']['access'] = "access.allowed_id = {$selected}";

			return $clauses;
		};

		add_filter( 'bp_members_signups_get_sql_clauses', $filter, 0, 2 );

		try {
			$found = BP_Signup::get( $args );

			$this->assertSame( array( $first ), $found['signups'] );
			$this->assertSame( 1, $found['total'] );

			$selected = $second;

			add_filter( 'bp_members_signups_get_sql_clauses', $filter, 0, 2 );
			$found = BP_Signup::get( $args );

			$this->assertSame( array( $second ), $found['signups'] );
			$this->assertSame( 1, $found['total'] );
			$this->assertCount( 2, $calls );
			$this->assertSame( 'ids', $calls[0]['fields'] );
		} finally {
			remove_filter( 'bp_members_signups_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( 2, BP_Signup::get( $args )['total'] );
	}

	public function test_get_sql_can_replace_both_queries() {
		$contexts = array();
		$filter   = static function( $sql, $type, $args, $clauses ) use ( &$contexts ) {
			$contexts[ $type ] = array( $args, $clauses );

			return 'count' === $type ? 'SELECT 42' : str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql );
		};

		add_filter( 'bp_members_signups_get_sql', $filter, 10, 4 );

		try {
			$found = BP_Signup::get( array( 'fields' => 'ids', 'cache_results' => false ) );
		} finally {
			remove_filter( 'bp_members_signups_get_sql', $filter );
		}

		$this->assertSame( array(), $found['signups'] );
		$this->assertSame( 42, $found['total'] );
		$this->assertSame( array( 'paged', 'count' ), array_keys( $contexts ) );
		$this->assertSame( $contexts['paged'], $contexts['count'] );
		$this->assertArrayHasKey( 'where_conditions', $contexts['paged'][1] );
	}

	/**
	 * @expectedDeprecated bp_members_signups_paged_query
	 * @expectedDeprecated bp_members_signups_count_query
	 */
	public function test_get_legacy_sql_filters_keep_arguments_and_canonical_runs_last() {
		self::factory()->signup->create();

		$args   = array( 'fields' => 'ids', 'cache_results' => false );

		$calls  = array();
		$legacy = static function( $sql, $parts, $original, $parsed ) use ( &$calls ) {
			$calls[] = array( $parts, $original, $parsed );

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_members_signups_paged_query', $legacy, 10, 4 );
		add_filter( 'bp_members_signups_count_query', $legacy, 10, 4 );

		try {
			$legacy_only = BP_Signup::get( $args );

			$this->assertSame( array(), $legacy_only['signups'] );
			$this->assertSame( 0, $legacy_only['total'] );

			add_filter( 'bp_members_signups_get_sql', $canonical, 10, 2 );
			$found = BP_Signup::get( $args );

			$this->assertSame( array(), $found['signups'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $calls );
			$this->assertSame( $calls[0], $calls[1] );
			$this->assertSame( 'ids', $calls[0][2]['fields'] );
			$this->assertSame( $args, $calls[0][1] );
		} finally {
			remove_filter( 'bp_members_signups_get_sql', $canonical );
			remove_filter( 'bp_members_signups_paged_query', $legacy );
			remove_filter( 'bp_members_signups_count_query', $legacy );
		}
	}

	public function test_get_default_sql_text_is_unchanged() {
		$bp     = buddypress();
		$first  = self::factory()->signup->create();
		$second = self::factory()->signup->create();
		$from = "FROM {$bp->members->table_name_signups} WHERE active = 0";
		$expected = array(
			'paged' => "SELECT DISTINCT signup_id {$from} ORDER BY signup_id DESC LIMIT 0, 2",
			'count' => "SELECT COUNT(DISTINCT signup_id) {$from}",
		);

		$queries = array();
		$capture = static function( $sql, $type ) use ( &$queries ) {
			$queries[ $type ] = $sql;

			return $sql;
		};

		add_filter( 'bp_members_signups_get_sql', $capture, 10, 2 );

		try {
			$found = BP_Signup::get( array( 'fields' => 'ids', 'cache_results' => false, 'number' => 2 ) );
		} finally {
			remove_filter( 'bp_members_signups_get_sql', $capture );
		}

		$this->assertSame( $expected, $queries );
		$this->assertSame( 2, $found['total'] );
	}

	/**
	 * @group add
	 */
	public function test_add() {
		$time = bp_core_current_time();
		$args = array(
			'domain' => 'foo',
			'path' => 'bar',
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => $time,
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
			),
		);

		$signup = self::factory()->signup->create( $args );
		$this->assertNotEmpty( $signup );

		$s = new BP_Signup( $signup );

		// spot check
		$this->assertSame( $signup, $s->id );
		$this->assertSame( 'user1', $s->user_login );
		$this->assertSame( '12345', $s->activation_key );
	}

	/**
	 * @group add
	 */
	public function test_add_no_visibility_level_set_should_use_default_visiblity_level() {
		// Update field_1's default visibility to 'adminsonly'.
		bp_xprofile_update_field_meta( 1, 'default_visibility', 'adminsonly' );

		// Add new signup without a custom field visibility set for field_1.
		$signup = self::factory()->signup->create( array(
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => bp_core_current_time(),
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
				'password' => 'password',

				/*
				 * Ensure we pass the field ID.
				 *
				 * See bp_core_activate_signup() and BP_Signup::add_backcompat().
				 */
				'profile_field_ids' => '1'
			),
		) );

		// Activate the signup.
		$activate = BP_Signup::activate( (array) $signup );

		// Assert that field 1's visibility for the signup is still 'adminsonly'
		$vis = xprofile_get_field_visibility_level( 1, $activate['activated'][0] );
		$this->assertSame( 'adminsonly', $vis );
	}

	/**
	 * @group get
	 */
	public function test_get_with_offset() {
		self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'offset' => 1,
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_number() {
		self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 2,
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s3, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_usersearch() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		self::factory()->signup->create();
		self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'usersearch' => 'ghi',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s1 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'fields' => 'ids',
		) );

		// default order is DESC.
		$this->assertSame( array( $s3, $s1, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email_asc() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2, $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_login_asc() {
		$s1 = self::factory()->signup->create( array(
			'user_login' => 'fghij',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_login' => 'abcde',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_login' => 'zzzzz',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'login',
			'number' => 3,
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2, $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_registered_asc() {
		$now = time();

		$s1 = self::factory()->signup->create( array(
			'registered' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );
		$s2 = self::factory()->signup->create( array(
			'registered' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$s3 = self::factory()->signup->create( array(
			'registered' => date( 'Y-m-d H:i:s', $now - 10 ),
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'registered',
			'number' => 3,
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2, $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$s1 = self::factory()->signup->create();
		self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'include' => array( $s1, $s3 ),
			'fields' => 'ids',
		) );

		$this->assertContains( $s1, $ss['signups'] );
		$this->assertContains( $s3, $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_activation_key() {
		self::factory()->signup->create( array(
			'activation_key' => 'foo',
		) );
		$s2 = self::factory()->signup->create( array(
			'activation_key' => 'bar',
		) );
		self::factory()->signup->create( array(
			'activation_key' => 'baz',
		) );

		$ss = BP_Signup::get( array(
			'activation_key' => 'bar',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_user_login() {
		self::factory()->signup->create( array(
			'user_login' => 'aaaafoo',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_login' => 'zzzzfoo',
		) );
		self::factory()->signup->create( array(
			'user_login' => 'jjjjfoo',
		) );

		$ss = BP_Signup::get( array(
			'user_login' => 'zzzzfoo',
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group activate
	 */
	public function test_activate_user_accounts() {
		$signups = array();

		$signups['accountone'] = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$signups['accounttwo'] = self::factory()->signup->create( array(
			'user_login'     => 'accounttwo',
			'user_email'     => 'accounttwo@example.com',
			'activation_key' => 'activationkeytwo',
		) );

		$signups['accountthree'] = self::factory()->signup->create( array(
			'user_login'     => 'accountthree',
			'user_email'     => 'accountthree@example.com',
			'activation_key' => 'activationkeythree',
		) );

		$results = BP_Signup::activate( $signups );
		$this->assertNotEmpty( $results['activated'] );

		$users = array();

		foreach ( $signups as $login => $signup_id  ) {
			$users[ $login ] = get_user_by( 'login', $login );
		}

		$this->assertEqualSets( $results['activated'], wp_list_pluck( $users, 'ID' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_signup_ids_only() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 3,
			'fields' => 'ids',
		) );

		$this->assertSame( array( $s3, $s2, $s1 ), $ss['signups'] );
	}

	/**
	 * @ticket BP8552
	 * @group cache
	 */
	public function test_signup_query_with_ids_cache_results() {
		global $wpdb;

		self::factory()->signup->create_many( 2 );

		// Reset.
		$wpdb->num_queries = 0;

		$first_query = BP_Signup::get(
			array(
				'cache_results' => true,
				'fields'        => 'ids',
			)
		);

		$queries_before = get_num_queries();

		$second_query = BP_Signup::get(
			array(
				'cache_results' => false,
				'fields'        => 'ids',
			)
		);

		$queries_after = get_num_queries();

		$this->assertNotSame( $queries_before, $queries_after, 'Assert that queries are run' );
		$this->assertSame( 4, $queries_after, 'Assert that the uncached query was run' );
		$this->assertSameSets( $first_query['signups'], $second_query['signups'], 'Results of the query are expected to match.' );
		$this->assertSame( $first_query['total'], $second_query['total'], 'Results of the query are expected to match.' );
	}

	/**
	 * @ticket BP8552
	 * @group cache
	 */
	public function test_signup_query_with_all_cache_results() {
		global $wpdb;

		self::factory()->signup->create_many( 2 );

		// Reset.
		$wpdb->num_queries = 0;

		$first_query = BP_Signup::get(
			array( 'cache_results' => true )
		);

		$queries_before = get_num_queries();

		$second_query = BP_Signup::get(
			array( 'cache_results' => false )
		);

		$queries_after = get_num_queries();

		$this->assertNotSame( $queries_before, $queries_after, 'Assert that queries are run' );
		$this->assertSame( 5, $queries_after, 'Assert that the uncached query was run' );
		$this->assertSame( $first_query['total'], $second_query['total'], 'Results of the query are expected to match.' );
	}

	/**
	 * @group cache
	 */
	public function test_get_queries_should_be_cached() {
		global $wpdb;

		self::factory()->signup->create_many( 2 );

		// Reset.
		$wpdb->num_queries = 0;

		$args = array( 'fields' => 'ids' );

		$first_query = BP_Signup::get( $args );

		$queries_before = get_num_queries();

		$second_query = BP_Signup::get( $args );

		$queries_after = get_num_queries();

		$this->assertSame( $queries_before, $queries_after, 'Assert that queries are run' );
		$this->assertSame( 2, $queries_after, 'Assert that the uncached query was run' );
		$this->assertSameSets( $first_query['signups'], $second_query['signups'], 'Results of the query are expected to match.' );
	}

	/**
	 * @group cache
	 */
	public function test_get_query_caches_should_be_busted_by_add() {
		$s1   = self::factory()->signup->create();
		$args = array( 'fields' => 'ids' );

		$found1 = BP_Signup::get( $args );
		$this->assertEqualSets( array( $s1 ), $found1['signups'] );

		$s2 = self::factory()->signup->create();
		$found2 = BP_Signup::get( $args );
		$this->assertEqualSets( array( $s2 ), $found2['signups'] );
	}

	/**
	 * @group cache
	 */
	public function test_get_query_caches_should_be_busted_by_meta_update() {
		$time = bp_core_current_time();

		$args = array(
			'domain' => 'foo',
			'path' => 'bar',
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => $time,
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Fozzie',
				'meta1' => 'meta2',
			),
		);
		$s1 = self::factory()->signup->create( $args );

		$args['meta']['field_1'] = 'Fozz';
		$s2 = self::factory()->signup->create( $args );

		// Should find both.
		$found1 = BP_Signup::get( array(
			'fields' => 'ids',
			'number'  => -1,
			'usersearch' => 'Fozz',
		) );
		$this->assertEqualSets( array( $s1, $s2 ), $found1['signups'] );

		BP_Signup::update( array(
			'signup_id'  => $s1,
			'meta'       => array(
				'field_1' => 'Fonzie'
			),
		) );

		$found2 = BP_Signup::get( array(
			'fields' => 'ids',
			'number'  => -1,
			'usersearch' => 'Fozz',
		) );

		$this->assertEqualSets( array( $s2 ), $found2['signups'] );
	}

	/**
	 * @group cache
	 */
	public function test_get_query_caches_should_be_busted_by_delete() {
		global $wpdb;
		$time = bp_core_current_time();

		$args = array(
			'domain' => 'foo',
			'path' => 'bar',
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => $time,
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Fozzie',
				'meta1' => 'meta2',
			),
		);
		$s1 = self::factory()->signup->create( $args );

		$args['meta']['field_1'] = 'Fozz';
		$s2 = self::factory()->signup->create( $args );

		// Should find both.
		$found1 = BP_Signup::get( array(
			'fields' => 'ids',
			'number'  => -1,
			'usersearch' => 'Fozz',
		) );
		$this->assertEqualSets( array( $s1, $s2 ), $found1['signups'] );

		BP_Signup::delete( array( $s1 ) );

		$found2 = BP_Signup::get( array(
			'fields' => 'ids',
			'number'  => -1,
			'usersearch' => 'Fozz',
		) );

		$this->assertEqualSets( array( $s2 ), $found2['signups'] );
	}

	/**
	 * @group cache
	 */
	public function test_get_query_caches_should_be_busted_by_activation() {
		$s1 = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$s2 = self::factory()->signup->create( array(
			'user_login'     => 'accounttwo',
			'user_email'     => 'accounttwo@example.com',
			'activation_key' => 'activationkeytwo',
		) );
		$found1 = BP_Signup::get(
			array(
				'number' => -1,
				'fields' => 'ids',
			)
		);
		$this->assertEqualSets( array( $s1, $s2 ), $found1['signups'] );

		BP_Signup::activate( (array) $s2 );

		$found2 = BP_Signup::get(
			array(
				'number' => -1,
				'fields' => 'ids',
			)
		);
		$this->assertEqualSets( array( $s1 ), $found2['signups'] );
	}

	/**
	 * @group cache
	 */
	public function signup_objects_should_be_cached() {
		global $wpdb;

		$s1 = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$found1 = new BP_Signup( $s1 );

		$num_queries = $wpdb->num_queries;

		// Object should be rebuilt from cache.
		$found2 = new BP_Signup( $s1 );

		// @TODO: This fails because "get_avatar()" in populate() results in db queries.
		$this->assertSame( get_object_vars( $found1 ), get_object_vars( $found2 ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group cache
	 */
	public function test_signup_object_caches_should_be_busted_by_activation() {
		$s1 = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$found1 = new BP_Signup( $s1 );
		$this->assertSame( $s1, $found1->id );
		$this->assertFalse( $found1->active );

		BP_Signup::activate( (array) $s1 );

		$found2 = new BP_Signup( $s1 );
		$this->assertSame( $s1, $found2->id );
		$this->assertTrue( $found2->active );

	}

	/**
	 * @group resend
	 */
	public function test_bp_core_signup_send_validation_email_should_increment_sent_count() {
		$activation_key = wp_generate_password( 32, false );
		$user_email     = 'accountone@example.com';
		$s1             = self::factory()->signup->create_and_get( array(
			'user_login'     => 'accountone',
			'user_email'     => $user_email,
			'activation_key' => $activation_key
		) );

		$this->assertSame( 0, $s1->count_sent );

		bp_core_signup_send_validation_email( 0, $user_email, $activation_key );

		$signup = new BP_Signup( $s1->id );
		$this->assertSame( 1, $signup->count_sent );
	}

	/**
	 * @ticket BP9137
	 * @group resend
	 */
	public function test_bp_core_signup_resend_email_activation() {
		$s1 = self::factory()->signup->create_and_get(
			array(
				'user_login'     => 'user' . wp_rand( 1, 20 ),
				'user_email'     => sprintf( 'user%d@example.com', wp_rand( 1, 20 ) ),
				'registered'     => bp_core_current_time(),
				'activation_key' => wp_generate_password( 32, false ),
				'meta'           => array(
					'field_1' => 'Foo Bar',
				),
			)
		);

		BP_Signup::resend( $s1->id );

		$this->assertFalse( BP_Signup::allow_activation_resend( 0 ) );
		$this->assertFalse( BP_Signup::allow_activation_resend( '' ) );
		$this->assertTrue( BP_Signup::allow_activation_resend( $s1 ) );

		$s1->count_sent = 0;
		$this->assertTrue( BP_Signup::allow_activation_resend( $s1 ) );

		$s1->count_sent = 1;
		$s1->recently_sent = true;
		$this->assertFalse( BP_Signup::allow_activation_resend( $s1 ) );

		add_filter( 'bp_core_signup_resend_activation_lock_time', '__return_zero' );
		$this->assertTrue( BP_Signup::allow_activation_resend( $s1 ) );
		remove_filter( 'bp_core_signup_resend_activation_lock_time', '__return_zero' );
	}
}
