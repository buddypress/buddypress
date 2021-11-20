<?php

/**
 * @group members
 * @group activity
 */
class BP_Tests_Members_Activity extends BP_UnitTestCase {

	/**
	 * @group activity_action
	 * @group bp_core_format_activity_action_new_member
	 */
	public function test_bp_members_format_activity_action_new_member() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->members->id,
			'type' => 'new_member',
			'user_id' => $u,
		) );

		$expected = sprintf( __( '%s became a registered member', 'buddypress' ), bp_core_get_userlink( $u ) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_members_format_activity_action_new_avatar
	 */
	public function test_bp_members_format_activity_action_new_avatar() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => 'members',
			'type' => 'new_avatar',
			'user_id' => $u,
		) );

		$expected = sprintf( __( '%s changed their profile picture', 'buddypress' ), bp_core_get_userlink( $u ) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group bp_migrate_new_member_activity_component
	 */
	public function test_bp_migrate_new_member_activity_component() {
		global $wpdb;
		$bp = buddypress();

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$au1 = self::factory()->activity->create( array(
			'component' => 'xprofile',
			'type' => 'new_member',
			'user_id' => $u1,
		) );

		$au2 = self::factory()->activity->create( array(
			'component' => 'xprofile',
			'type' => 'new_member',
			'user_id' => $u2,
		) );

		$au3 = self::factory()->activity->create( array(
			'component' => 'xprofile',
			'type' => 'new_member',
			'user_id' => $u3,
		) );

		bp_migrate_new_member_activity_component();

		$expected = array(
			$u1 => $au1,
			$u2 => $au2,
			$u3 => $au3,
		);

		$in = "'" . implode( "', '", array_keys( $expected ) ) . "'";
		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, id FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = %s AND user_id IN ({$in}) ORDER BY user_id ASC",
				$bp->members->id,
				'new_member'
		), OBJECT_K );

		$found = array_map( 'intval', wp_list_pluck( $found, 'id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group bp_members_new_avatar_activity
	 */
	public function test_bp_members_new_avatar_activity_throttled() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => 'members',
			'type'      => 'new_avatar',
			'user_id'   => $u,
		) );

		bp_members_new_avatar_activity( $u );

		$new_avatar_activities = bp_activity_get( array(
			'user_id'     => $u,
			'component'   => buddypress()->members->id,
			'type'        => 'new_avatar',
			'count_total' => 'count_query',
		) );

		$this->assertEquals( 1, $new_avatar_activities['total'] );
		$this->assertNotSame( $a, $new_avatar_activities['activities'][0]->id );
	}

	/**
	 * @group bp_members_new_avatar_activity
	 */
	public function test_bp_members_new_avatar_activity_outside_of_throttle_time() {
		$u = self::factory()->user->create();

		$time      = strtotime( bp_core_current_time() );
		$prev_time = date( 'Y-m-d H:i:s', $time - ( 121 * HOUR_IN_SECONDS ) );

		$a = self::factory()->activity->create( array(
			'component'     => 'members',
			'type'          => 'new_avatar',
			'user_id'       => $u,
			'recorded_time' => $prev_time,
		) );

		bp_members_new_avatar_activity( $u );

		$new_avatar_activities = bp_activity_get( array(
			'user_id'     => $u,
			'component'   => buddypress()->members->id,
			'type'        => 'new_avatar',
			'count_total' => 'count_query',
		) );

		$this->assertEquals( 2, $new_avatar_activities['total'] );
	}
}
