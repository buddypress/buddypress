<?php
/**
 * Tests for deprecated members functionality.
 *
 * @group members
 * @group deprecated
 */
class BP_Tests_Members_Deprecated extends BP_UnitTestCase {
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
}
