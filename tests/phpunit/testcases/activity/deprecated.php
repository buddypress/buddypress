<?php
/**
 * Tests for deprecated activity functionality.
 *
 * @group activity
 * @group deprecated
 */
class BP_Tests_Activity_Deprecated extends BP_UnitTestCase {
	/**
	 * @expectedDeprecated bp_activity_get_join_sql
	 */
	public function test_bp_activity_get_join_sql_preserves_arguments_and_runs_before_canonical_hook() {
		$allowed = self::factory()->activity->create();
		self::factory()->activity->create();

		$arguments = array();
		$order     = array();
		$legacy    = static function( ...$args ) use ( $allowed, &$arguments, &$order ) {
			$arguments = $args;
			$order[] = 'legacy';

			return $args[0] . " INNER JOIN (SELECT {$allowed} AS activity_id) allowed ON allowed.activity_id = a.id";
		};

		$canonical = static function( $sql, $type ) use ( &$order ) {
			$order[] = $type;

			return $sql;
		};

		add_filter( 'bp_activity_get_join_sql', $legacy, 10, 5 );
		add_filter( 'bp_activity_get_sql', $canonical, 10, 2 );

		try {
			$found = BP_Activity_Activity::get( array( 'count_total' => true ) );
		} finally {
			remove_filter( 'bp_activity_get_join_sql', $legacy );
			remove_filter( 'bp_activity_get_sql', $canonical );
		}

		$this->assertCount( 5, $arguments );
		$this->assertSame( array( $allowed ), wp_list_pluck( $found['activities'], 'id' ) );
		$this->assertSame( 1, $found['total'] );
		$this->assertSame( array( 'legacy', 'paged', 'count' ), $order );

		$this->assertIsArray( $arguments[1] );
		$this->assertStringContainsString( 'SELECT', $arguments[2] );
		$this->assertStringContainsString( 'FROM', $arguments[3] );
		$this->assertStringContainsString( 'WHERE', $arguments[4] );
	}

	/**
	 * @expectedDeprecated bp_activity_paged_activities_sql
	 */
	public function test_bp_activity_paged_activities_sql_preserves_arguments_and_runs_before_canonical_hook() {
		$allowed = self::factory()->activity->create();
		self::factory()->activity->create();

		$arguments = array();
		$order     = array();
		$legacy    = static function( ...$args ) use ( $allowed, &$arguments, &$order ) {
			$arguments = $args;
			$order[] = 'legacy';

			return str_replace( 'WHERE', "WHERE a.id = {$allowed} AND", $args[0] );
		};

		$canonical = static function( $sql, $type ) use ( &$order ) {
			$order[] = $type;

			return $sql;
		};

		add_filter( 'bp_activity_paged_activities_sql', $legacy, 10, 2 );
		add_filter( 'bp_activity_get_sql', $canonical, 10, 2 );

		try {
			$found = BP_Activity_Activity::get( array( 'count_total' => true ) );
		} finally {
			remove_filter( 'bp_activity_paged_activities_sql', $legacy );
			remove_filter( 'bp_activity_get_sql', $canonical );
		}

		$this->assertCount( 2, $arguments );
		$this->assertSame( array( $allowed ), wp_list_pluck( $found['activities'], 'id' ) );
		$this->assertSame( 2, $found['total'] );
		$this->assertSame( array( 'legacy', 'paged', 'count' ), $order );

		$this->assertIsArray( $arguments[1] );
		$this->assertTrue( $arguments[1]['count_total'] );
	}

	/**
	 * @expectedDeprecated bp_activity_total_activities_sql
	 */
	public function test_bp_activity_total_activities_sql_preserves_arguments_and_runs_before_canonical_hook() {
		$allowed = self::factory()->activity->create();
		self::factory()->activity->create();

		$arguments = array();
		$order     = array();
		$legacy    = static function( ...$args ) use ( $allowed, &$arguments, &$order ) {
			$arguments = $args;
			$order[] = 'legacy';

			return 'SELECT 42';
		};

		$canonical = static function( $sql, $type ) use ( &$order ) {
			$order[] = $type;

			return $sql;
		};

		add_filter( 'bp_activity_total_activities_sql', $legacy, 10, 3 );
		add_filter( 'bp_activity_get_sql', $canonical, 10, 2 );

		try {
			$found = BP_Activity_Activity::get( array( 'count_total' => true ) );
		} finally {
			remove_filter( 'bp_activity_total_activities_sql', $legacy );
			remove_filter( 'bp_activity_get_sql', $canonical );
		}

		$this->assertCount( 3, $arguments );
		$this->assertSame( 42, $found['total'] );
		$this->assertSame( array( 'paged', 'legacy', 'count' ), $order );

		$this->assertStringContainsString( 'WHERE', $arguments[1] );
		$this->assertSame( 'DESC', $arguments[2] );
	}

	/**
	 * @expectedDeprecated bp_activity_get_user_join_filter
	 */
	public function test_bp_activity_get_user_join_filter_preserves_arguments_and_runs_before_canonical_hook() {
		$allowed = self::factory()->activity->create();
		self::factory()->activity->create();

		$arguments = array();
		$order     = array();
		$legacy    = static function( ...$args ) use ( $allowed, &$arguments, &$order ) {
			$arguments = $args;
			$order[] = 'legacy';

			return str_replace( 'WHERE', "WHERE a.id = {$allowed} AND", $args[0] );
		};

		$canonical = static function( $sql, $type ) use ( &$order ) {
			$order[] = $type;

			return $sql;
		};

		$use_legacy = static function() { return true; };

		add_filter( 'bp_use_legacy_activity_query', $use_legacy );
		add_filter( 'bp_activity_get_user_join_filter', $legacy, 10, 6 );
		add_filter( 'bp_activity_get_sql', $canonical, 10, 2 );

		try {
			$found = BP_Activity_Activity::get( array( 'count_total' => true ) );
		} finally {
			remove_filter( 'bp_activity_get_user_join_filter', $legacy );
			remove_filter( 'bp_activity_get_sql', $canonical );
			remove_filter( 'bp_use_legacy_activity_query', $use_legacy );
		}

		$this->assertCount( 6, $arguments );
		$this->assertSame( array( $allowed ), wp_list_pluck( $found['activities'], 'id' ) );
		$this->assertSame( 2, $found['total'] );
		$this->assertSame( array( 'legacy', 'paged', 'count' ), $order );

		$this->assertStringContainsString( 'SELECT', $arguments[1] );
		$this->assertStringContainsString( 'FROM', $arguments[2] );
		$this->assertStringContainsString( 'WHERE', $arguments[3] );
		$this->assertSame( 'DESC', $arguments[4] );
		$this->assertStringContainsString( 'LIMIT', $arguments[5] );
	}

	/**
	 * @expectedDeprecated bp_activity_get_where_conditions
	 */
	public function test_get_with_legacy_where_conditions_filter() {
		$activity_1 = self::factory()->activity->create();
		self::factory()->activity->create();

		$filter = static function ( $where_conditions ) use ( $activity_1 ) {
			$where_conditions['activity_id'] = 'a.id = ' . (int) $activity_1;

			return $where_conditions;
		};

		add_filter( 'bp_activity_get_where_conditions', $filter );

		try {
			$activity = BP_Activity_Activity::get();
		} finally {
			remove_filter( 'bp_activity_get_where_conditions', $filter );
		}

		$this->assertSame( array( $activity_1 ), wp_list_pluck( $activity['activities'], 'id' ) );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/8596
	 * @expectedDeprecated BP_Activity_Activity::get_id
	 */
	public function test_get_id_with_deprecated_args() {
		$a1 = self::factory()->activity->create( array(
			'item_id' => 573,
		) );
		$a2 = self::factory()->activity->create( array(
			'item_id' => 1098,
		) );

		$activity = BP_Activity_Activity::get_id( false, false, false, 1098, false, false, false, false );
		$this->assertSame( $a2, $activity );
	}
}
