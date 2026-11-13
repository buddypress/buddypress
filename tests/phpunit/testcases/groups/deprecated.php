<?php
/**
 * Tests for deprecated groups functionality.
 *
 * @group groups
 * @group deprecated
 */
class BP_Tests_Groups_Deprecated extends BP_UnitTestCase {
	/**
	 * @expectedDeprecated BP_Groups_Group::__construct
	 */
	public function test_deprecated_arg() {
		$group = new BP_Groups_Group( 123456789, array( 'populate_extras' => true ) );
		$this->assertSame( 0, $group->id );
	}

	/**
	 * @expectedDeprecated bp_groups_get_paged_groups_sql
	 * @expectedDeprecated bp_groups_get_total_groups_sql
	 */
	public function test_get_legacy_sql_filters_keep_arguments_and_canonical_runs_last() {
		self::factory()->group->create();

		$args   = array( 'fields' => 'ids', 'cache_results' => false );
		$calls  = array();
		$legacy = static function( $sql, $parts, $parsed ) use ( &$calls ) {
			$calls[] = array( $parts, $parsed );

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_groups_get_paged_groups_sql', $legacy, 10, 3 );
		add_filter( 'bp_groups_get_total_groups_sql', $legacy, 10, 3 );

		try {
			$legacy_only = BP_Groups_Group::get( $args );

			$this->assertSame( array(), $legacy_only['groups'] );
			$this->assertSame( 0, $legacy_only['total'] );

			add_filter( 'bp_groups_group_get_sql', $canonical, 10, 2 );
			$found = BP_Groups_Group::get( $args );

			$this->assertSame( array(), $found['groups'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $calls );
			$this->assertSame( $calls[0], $calls[1] );
			$this->assertSame( 'ids', $calls[0][1]['fields'] );
		} finally {
			remove_filter( 'bp_groups_group_get_sql', $canonical );
			remove_filter( 'bp_groups_get_paged_groups_sql', $legacy );
			remove_filter( 'bp_groups_get_total_groups_sql', $legacy );
		}
	}

	/**
	 * @expectedDeprecated BP_Groups_Group::group_exists
	 */
	public function test_get_group_with_slug_with_deprecated_args() {
		$slug     = 'group-test';
		$g1       = self::factory()->group->create( array( 'slug' => $slug ) );
		$group_id = BP_Groups_Group::group_exists( $slug, 'random-name' );

		$this->assertSame( $g1, $group_id );
	}

	/**
	 * @expectedDeprecated BP_Groups_Group::get_by_letter
	 */
	public function test_get_by_letter_with_deprecated_arg() {
		$g1 = self::factory()->group->create( array(
			'name'        => 'Awesome Cool Group',
			'description' => 'Neat',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get_by_letter( 'A', null, null, false );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertSame( array( $g1 ), $found );
		$this->assertNotContains( $g2, $found );
	}

	/**
	 * @expectedDeprecated BP_Groups_Group::get_random
	 */
	public function test_get_random_with_deprecated_arg() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		// There are only two groups, so excluding one should give us the other
		$groups = BP_Groups_Group::get_random( null, null, 0, false, false, array( $g1, 'ignore this' ) );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertSame( array( $g2 ), $found );
	}

	/**
	 * @expectedDeprecated BP_Groups_Member::get_all_for_group
	 */
	public function test_get_all_for_group_with_exclude() {
		$g1 = self::factory()->group->create();

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		self::add_user_to_group( $u1, $g1 );
		self::add_user_to_group( $u2, $g1 );

		$members = BP_Groups_Member::get_all_for_group( $g1, false, false, true, true, array( $u1 ) );

		$mm = (array) $members['members'];
		$ids = wp_list_pluck( $mm, 'user_id' );
		$this->assertSame( array( $u2 ), $ids );
	}

	/**
	 * @expectedDeprecated groups_send_invites
	 */
	public function test_groups_send_invites_deprecated_args() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1, 'status' => 'private' ) );

		// Create draft invitation
		groups_invite_user( array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		) );

		// Send the invitation
		groups_send_invites( $u1, $g1 );

		// Check that the invitation has been sent.
		$sent = groups_check_user_has_invite( $u2, $g1, $type = 'sent' );
		$this->assertTrue( is_numeric( $sent ) && $sent > 0 );
	}

	/**
	 * @expectedDeprecated groups_send_membership_request
	 */
	public function test_groups_send_membership_request_deprecated_args() {
		$u1 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'status' => 'private' ) );

		// Create membership request
		groups_send_membership_request( $u1, $g1 );

		$request = groups_check_for_membership_request( $u1, $g1 );
		$this->assertTrue( is_numeric( $request ) && $request > 0 );
	}

	/**
	 * @expectedDeprecated groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_test_backcompat_arguments() {
		$g1 = self::factory()->group->create();
		$name = 'Great Scott';
		$description = 'A must-see in time for the holidays!';
		groups_edit_base_group_details( $g1, $name, $description, false );

		$expected = array(
			'id'          => $g1,
			'name'        => $name,
			'description' => $description
		);
		$updated_group_object = groups_get_group( $g1 );
		$updated = array(
			'id'          => $updated_group_object->id,
			'name'        => $updated_group_object->name,
			'description' => $updated_group_object->description
		);

		$this->assertEqualSets( $expected, $updated );
	}
}
