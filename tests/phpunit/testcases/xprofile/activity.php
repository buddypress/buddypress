<?php

/**
 * @group xprofile
 * @group activity
 */
class BP_Tests_XProfile_Activity extends BP_UnitTestCase {
	protected static $updated_profile_data = array();

	public static function wpSetUpBeforeClass( $factory ) {
		self::$updated_profile_data['u'] = $factory->user->create();
		self::$updated_profile_data['g'] = $factory->xprofile_group->create();
		self::$updated_profile_data['f'] = $factory->xprofile_field->create( array(
			'field_group_id' => self::$updated_profile_data['g'],
		) );
	}

	public static function tearDownAfterClass() {
		$d = self::$updated_profile_data;

		xprofile_delete_field_group( $d['g'] );
		xprofile_delete_field( $d['f'] );

		self::delete_user( $d['u'] );

		self::commit_transaction();
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_with_errors() {
		$d = self::$updated_profile_data;

		// Fake new/old values to ensure a change
		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$du = isset( $d['u'] ) ? $d['u'] : '';
		$df = isset( $d['f'] ) ? $d['f'] : '';

		$this->assertFalse( bp_xprofile_updated_profile_activity( $du, array( $df ), true ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_throttled() {
		$d = self::$updated_profile_data;

		$time = time();
		$prev_time = date( 'Y-m-d H:i:s', $time - ( 119 * 60 ) );
		$now_time = date( 'Y-m-d H:i:s', $time );

		self::factory()->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'date_recorded' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_outside_of_throttle() {
		$d = self::$updated_profile_data;

		$time = strtotime( bp_core_current_time() );
		$prev_time = date( 'Y-m-d H:i:s', $time - ( 121 * 60 ) );
		$now_time = date( 'Y-m-d H:i:s', $time );

		self::factory()->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'recorded_time' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );

		$existing = bp_activity_get( array(
			'max' => 1,
			'filter' => array(
				'user_id' => $d['u'],
				'object' => buddypress()->profile->id,
				'action' => 'updated_profile',
			),
			'count_total' => 'count_query',
		) );

		$this->assertEquals( 1, $existing['total'] );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_no_existing_activity() {
		$d = self::$updated_profile_data;

		// Fake new/old values to ensure a change
		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );

		$existing = bp_activity_get( array(
			'max' => 1,
			'filter' => array(
				'user_id' => $d['u'],
				'object' => buddypress()->profile->id,
				'action' => 'updated_profile',
			),
			'count_total' => 'count_query',
		) );

		$this->assertEquals( 1, $existing['total'] );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_no_changes() {
		$d = self::$updated_profile_data;

		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_no_public_changes() {
		$d = self::$updated_profile_data;

		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'bar',
				'visibility' => 'loggedin',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_public_changed_to_private() {
		$d = self::$updated_profile_data;

		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'bar',
				'visibility' => 'loggedin',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_private_changed_to_public() {
		$d = self::$updated_profile_data;

		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_field_didnt_previously_exist() {
		$d = self::$updated_profile_data;

		$old_values = array();
		$new_values = array(
			$d['f'] => array(
				'value'      => 'bar',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_public_changes() {
		$d = self::$updated_profile_data;

		$old_values = array(
			$d['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$d['f'] => array(
				'value'      => 'bar',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group activity_action
	 * @group bp_xprofile_format_activity_action_updated_profile
	 */
	public function test_bp_xprofile_format_activity_action_updated_profile() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'user_id' => $u,
		) );

		$expected = sprintf( esc_html__( "%s's profile was updated", 'buddypress' ), '<a href="' . bp_core_get_user_domain( $u ) . bp_get_profile_slug() . '/">' . bp_core_get_user_displayname( $u ) . '</a>' );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}
}
