<?php

/**
 * @group xprofile
 * @group activity
 */
class BP_Tests_XProfile_Activity extends BP_UnitTestCase {
	protected $updated_profile_data = array();

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_with_errors() {
		$d = $this->setup_updated_profile_data();

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), true ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_throttled() {
		$d = $this->setup_updated_profile_data();

		$time = time();
		$prev_time = date( 'Y-m-d H:i:s', $time - ( 119 * 60 ) );
		$now_time = date( 'Y-m-d H:i:s', $time );

		$this->factory->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'date_recorded' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$time = strtotime( bp_core_current_time() );
		$prev_time = date( 'Y-m-d H:i:s', $time - ( 121 * 60 ) );
		$now_time = date( 'Y-m-d H:i:s', $time );

		$this->factory->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'recorded_time' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array();
		$new_values = array(
			$this->updated_profile_data['f'] => array(
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
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f'] => array(
				'value'      => 'bar',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f'] ), false, $old_values, $new_values ) );
	}

	/**
	 * @group activity_action
	 * @group bp_xprofile_format_activity_action_new_avatar
	 */
	public function test_bp_xprofile_format_activity_action_new_avatar() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create( array(
			'component' => 'profile',
			'type' => 'new_avatar',
			'user_id' => $u,
		) );

		$expected = sprintf( __( '%s changed their profile picture', 'buddypress' ), bp_core_get_userlink( $u ) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_xprofile_format_activity_action_updated_profile
	 */
	public function test_bp_xprofile_format_activity_action_updated_profile() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create( array(
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'user_id' => $u,
		) );

		$expected = sprintf( __( '%s&#8217;s profile was updated', 'buddypress' ), '<a href="' . bp_core_get_user_domain( $u ) . bp_get_profile_slug() . '/">' . bp_core_get_user_displayname( $u ) . '</a>' );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}

	protected function setup_updated_profile_data() {
		$this->updated_profile_data['u'] = $this->factory->user->create();
		$this->updated_profile_data['g'] = $this->factory->xprofile_group->create();
		$this->updated_profile_data['f'] = $this->factory->xprofile_field->create( array(
			'field_group_id' => $this->updated_profile_data['g'],
		) );

	}
}
