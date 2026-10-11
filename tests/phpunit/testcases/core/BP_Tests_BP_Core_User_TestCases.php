<?php

/**
 * @group core
 * @group BP_Core_User
 */
class BP_Tests_BP_Core_User_TestCases extends BP_UnitTestCase {

	public function test_get_users_by_letter_sql_clauses_restrict_duplicate_join_and_total() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$baseline = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );

		$this->assertSame( 2, $baseline['total'] );

		$calls  = array();
		$filter = static function( $clauses, $args ) use ( $first, &$calls, &$filter ) {
			remove_filter( 'bp_core_users_by_letter_get_sql_clauses', $filter, 0 );
			$calls[] = $args;
			$clauses['join'] .= " INNER JOIN (SELECT {$first} AS allowed_id UNION ALL SELECT {$first}) access ON access.allowed_id = u.ID";
			$clauses['where_conditions']['access'] = "access.allowed_id = {$first}";

			return $clauses;
		};

		add_filter( 'bp_core_users_by_letter_get_sql_clauses', $filter, 0, 2 );

		try {
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_by_letter_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( array( $first ), wp_list_pluck( $found['users'], 'id' ) );
		$this->assertSame( 1, $found['total'] );
		$this->assertCount( 1, $calls );
		$this->assertSame( 'F', $calls[0]['letter'] );
	}

	public function test_get_users_by_letter_sql_can_replace_both_queries() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );

		$contexts = array();
		$filter   = static function( $sql, $type, $args, $clauses ) use ( &$contexts ) {
			$contexts[ $type ] = array( $args, $clauses );

			return 'count' === $type ? 'SELECT 42' : str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql );
		};

		add_filter( 'bp_core_users_by_letter_get_sql', $filter, 10, 4 );

		try {
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_by_letter_get_sql', $filter );
		}

		$this->assertSame( array(), $found['users'] );
		$this->assertSame( 42, $found['total'] );
		$this->assertSame( array( 'count', 'paged' ), array_keys( $contexts ) );
		$this->assertSame( $contexts['paged'], $contexts['count'] );
		$this->assertSame( 'F', $contexts['paged'][0]['letter'] );
	}

	/**
	 * @expectedDeprecated bp_core_users_by_letter_sql
	 * @expectedDeprecated bp_core_users_by_letter_count_sql
	 */
	public function test_get_users_by_letter_legacy_sql_arguments_and_canonical_precedence() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$legacy_args = array();

		$legacy      = static function( $sql, ...$args ) use ( &$legacy_args ) {
			$legacy_args[] = $args;

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_core_users_by_letter_sql', $legacy, 10, 1 );
		add_filter( 'bp_core_users_by_letter_count_sql', $legacy, 10, 1 );

		try {
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 0, $found['total'] );

			add_filter( 'bp_core_users_by_letter_get_sql', $canonical, 10, 2 );
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $legacy_args );
			$this->assertSame( array(), $legacy_args[0] );
			$this->assertSame( array(), $legacy_args[1] );
		} finally {
			remove_filter( 'bp_core_users_by_letter_get_sql', $canonical );
			remove_filter( 'bp_core_users_by_letter_sql', $legacy );
			remove_filter( 'bp_core_users_by_letter_count_sql', $legacy );
		}
	}

	public function test_search_users_sql_clauses_restrict_duplicate_join_and_total() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$baseline = BP_Core_User::search_users( 'Formula', 2, 1, false );

		$this->assertSame( 2, $baseline['total'] );

		$calls  = array();
		$filter = static function( $clauses, $args ) use ( $first, &$calls, &$filter ) {
			remove_filter( 'bp_core_users_search_get_sql_clauses', $filter, 0 );
			$calls[] = $args;
			$clauses['join'] .= " INNER JOIN (SELECT {$first} AS allowed_id UNION ALL SELECT {$first}) access ON access.allowed_id = u.ID";
			$clauses['where_conditions']['access'] = "access.allowed_id = {$first}";

			return $clauses;
		};

		add_filter( 'bp_core_users_search_get_sql_clauses', $filter, 0, 2 );

		try {
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_search_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( array( $first ), wp_list_pluck( $found['users'], 'id' ) );
		$this->assertSame( 1, $found['total'] );
		$this->assertCount( 1, $calls );
		$this->assertSame( 'Formula', $calls[0]['search_terms'] );
	}

	public function test_search_users_sql_can_replace_both_queries() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );

		$contexts = array();
		$filter   = static function( $sql, $type, $args, $clauses ) use ( &$contexts ) {
			$contexts[ $type ] = array( $args, $clauses );

			return 'count' === $type ? 'SELECT 42' : str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql );
		};

		add_filter( 'bp_core_users_search_get_sql', $filter, 10, 4 );

		try {
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_search_get_sql', $filter );
		}

		$this->assertSame( array(), $found['users'] );
		$this->assertSame( 42, $found['total'] );
		$this->assertSame( array( 'count', 'paged' ), array_keys( $contexts ) );
		$this->assertSame( $contexts['paged'], $contexts['count'] );
		$this->assertSame( 'Formula', $contexts['paged'][0]['search_terms'] );
	}

	/**
	 * @expectedDeprecated bp_core_search_users_sql
	 * @expectedDeprecated bp_core_search_users_count_sql
	 */
	public function test_search_users_legacy_sql_arguments_and_canonical_precedence() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$legacy_args = array();

		$legacy      = static function( $sql, ...$args ) use ( &$legacy_args ) {
			$legacy_args[] = $args;

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_core_search_users_sql', $legacy, 10, 3 );
		add_filter( 'bp_core_search_users_count_sql', $legacy, 10, 3 );

		try {
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 0, $found['total'] );

			add_filter( 'bp_core_users_search_get_sql', $canonical, 10, 2 );
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $legacy_args );
			$this->assertSame( array( 'Formula' ), $legacy_args[0] );
			$this->assertSame( array( 'Formula', ' LIMIT 0, 2' ), $legacy_args[1] );
		} finally {
			remove_filter( 'bp_core_users_search_get_sql', $canonical );
			remove_filter( 'bp_core_search_users_sql', $legacy );
			remove_filter( 'bp_core_search_users_count_sql', $legacy );
		}
	}
	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_querystring() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$exclude_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_qs );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_array() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$exclude_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_querystring() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );

		$include_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_qs );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_array() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );


		$include_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 * @group get_users
	 * @group type
	 */
	public function test_type_alphabetical() {
		$u1 = self::factory()->user->create( array(
			'display_name' => 'foo',
		) );
		$u2 = self::factory()->user->create( array(
			'display_name' => 'bar',
		) );

		global $wpdb;

		$q = BP_Core_User::get_users( 'alphabetical' );
		$found = wp_list_pluck( $q['users'], 'id' );

		$this->assertSame( array( $u2, $u1 ), $found );
	}

	/**
	 * @group get_users_by_letter
	 */
	public function test_get_users_by_letter() {
		self::factory()->user->create( array(
			'display_name' => 'foo',
		) );
		$u2 = self::factory()->user->create( array(
			'display_name' => 'bar',
		) );

		$q = BP_Core_User::get_users_by_letter( 'b' );
		$found = wp_list_pluck( $q['users'], 'id' );

		$this->assertSame( array( $u2 ), $found );
		$this->assertSame( 1, $q['total'] );
	}

	/**
	 * @group search_users
	 */
	public function test_search_users() {
		$u1 = self::factory()->user->create( array(
			'display_name' => 'foo',
		) );
		$u2 = self::factory()->user->create( array(
			'display_name' => 'bar',
		) );

		$q = BP_Core_User::search_users( 'ar' );
		$found = wp_list_pluck( $q['users'], 'id' );

		$this->assertSame( array( $u2 ), $found );
		$this->assertSame( 1, $q['total'] );
	}


	public function test_get_users_by_letter_default_sql_text_is_unchanged() {
		global $wpdb;

		$bp = buddypress();
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$status = bp_core_get_status_sql( 'u.' );
		$from   = $wpdb->prepare( "FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id LEFT JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id WHERE {$status} AND pf.name = %s  AND pd.value LIKE %s ORDER BY pd.value ASC", bp_xprofile_fullname_field_name(), 'F%' );
		$expected = array(
			'count' => "SELECT COUNT(DISTINCT u.ID) {$from}",
			'paged' => "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email {$from} LIMIT 0, 2",
		);

		$queries = array();
		$capture = static function( $sql, $type ) use ( &$queries ) {
			$queries[ $type ] = $sql;

			return $sql;
		};

		add_filter( 'bp_core_users_by_letter_get_sql', $capture, 10, 2 );

		try {
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_by_letter_get_sql', $capture );
		}

		$this->assertSame( $expected, $queries );
		$this->assertSame( 2, $found['total'] );
	}

	public function test_search_users_default_sql_text_is_unchanged() {
		global $wpdb;

		$bp = buddypress();
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$status = bp_core_get_status_sql( 'u.' );
		$from   = $wpdb->prepare( "FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id WHERE {$status} AND pd.value LIKE %s ORDER BY pd.value ASC", '%Formula%' );
		$expected = array(
			'count' => "SELECT COUNT(DISTINCT u.ID) as id {$from}",
			'paged' => "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email {$from} LIMIT 0, 2",
		);

		$queries = array();
		$capture = static function( $sql, $type ) use ( &$queries ) {
			$queries[ $type ] = $sql;

			return $sql;
		};

		add_filter( 'bp_core_users_search_get_sql', $capture, 10, 2 );

		try {
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );
		} finally {
			remove_filter( 'bp_core_users_search_get_sql', $capture );
		}

		$this->assertSame( $expected, $queries );
		$this->assertSame( 2, $found['total'] );
	}

	public function test_get_specific_users() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$include_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_specific_users( $include_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		$this->assertSame( array( $u1, $u3 ), $user_ids );
		$this->assertSame( 2, $users['total'] );
	}

	/**
	 * @group last_activity
	 */
	public function test_get_last_activity() {
		$u = self::factory()->user->create();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );

		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';

		$this->assertSame( $time, $found );
		$this->assertSame( $u, $a[ $u ]['user_id'] );
		$this->assertIsInt( $a[ $u ]['activity_id'] );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_store_in_cache() {
		$u = self::factory()->user->create();
		$time = bp_core_current_time();

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u, 'bp_last_activity' );

		// prime cache
		$a = BP_Core_User::get_last_activity( $u );

		$this->assertSame( $a[ $u ], wp_cache_get( $u, 'bp_last_activity' ) );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_store_in_cache_multiple_users() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$time = bp_core_current_time();

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u1, 'bp_last_activity' );
		wp_cache_delete( $u2, 'bp_last_activity' );

		// prime cache
		$a = BP_Core_User::get_last_activity( array( $u1, $u2 ) );

		$this->assertSame( $a[ $u1 ], wp_cache_get( $u1, 'bp_last_activity' ) );
		$this->assertSame( $a[ $u2 ], wp_cache_get( $u2, 'bp_last_activity' ) );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_from_cache_single_user() {
		$u    = self::factory()->user->create();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );

		// Cache is set during user creation. Clear to reflect actual
		// pageload
		wp_cache_delete( $u, 'bp_last_activity' );

		// Prime cache
		$uncached = BP_Core_User::get_last_activity( $u );

		// Fetch again to get from the cache
		$cached = BP_Core_User::get_last_activity( $u );

		$this->assertSame( $uncached, $cached );
	}

	/**
	 * @group last_activity
	 * @group cache
	 */
	public function test_get_last_activity_in_cache_multiple_users() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u1, $time );
		BP_Core_User::update_last_activity( $u2, $time );

		// Cache is set during user creation. Clear to reflect actual pageload
		wp_cache_delete( $u1, 'bp_last_activity' );
		wp_cache_delete( $u2, 'bp_last_activity' );

		// Prime cache
		$uncached = BP_Core_User::get_last_activity( array( $u1, $u2 ) );

		// Second grab will be from the cache
		$cached = BP_Core_User::get_last_activity( array( $u1, $u2 ) );
		$cached_u1 = wp_cache_get( $u1, 'bp_last_activity' );

		$this->assertSame( $cached, $uncached );
	}

	/**
	 * @group last_activity
	 */
	public function test_update_last_activity() {
		$u = self::factory()->user->create();
		$time = bp_core_current_time();
		$time2 = '1968-12-25 01:23:45';

		BP_Core_User::update_last_activity( $u, $time );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertSame( $time, $found );

		BP_Core_User::update_last_activity( $u, $time2 );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertSame( $time2, $found );
	}

	/**
	 * @group last_activity
	 */
	public function test_delete_last_activity() {
		$u = self::factory()->user->create();
		$time = bp_core_current_time();

		BP_Core_User::update_last_activity( $u, $time );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertSame( $time, $found );

		BP_Core_User::delete_last_activity( $u );
		$a = BP_Core_User::get_last_activity( $u );
		$found = isset( $a[ $u ]['date_recorded'] ) ? $a[ $u ]['date_recorded'] : '';
		$this->assertSame( '', $found );
	}
}
