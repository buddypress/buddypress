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
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), true ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_throttled() {
		$d = $this->setup_updated_profile_data();

		$time = time();
		$prev_time = date( 'Y-m-d h:i:s', $time - ( 119 * 60 ) );
		$now_time = date( 'Y-m-d h:i:s', $time );

		$this->factory->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'date_recorded' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_outside_of_throttle() {
		$d = $this->setup_updated_profile_data();

		$time = time();
		$prev_time = date( 'Y-m-d h:i:s', $time - ( 121 * 60 ) );
		$now_time = date( 'Y-m-d h:i:s', $time );

		$this->factory->activity->create( array(
			'user_id' => $d['u'],
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'recorded_time' => $prev_time,
		) );

		// Fake new/old values to ensure a change
		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );

		$existing = bp_activity_get( array(
			'max' => 1,
			'filter' => array(
				'user_id' => $user_id,
				'object' => buddypress()->profile->id,
				'action' => 'updated_profile',
			),
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
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo2',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );

		$existing = bp_activity_get( array(
			'max' => 1,
			'filter' => array(
				'user_id' => $user_id,
				'object' => buddypress()->profile->id,
				'action' => 'updated_profile',
			),
		) );

		$this->assertEquals( 1, $existing['total'] );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_no_changes() {
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_no_public_changes() {
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'bar',
				'visibility' => 'loggedin',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_public_changed_to_private() {
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'bar',
				'visibility' => 'loggedin',
			),
		);

		$this->assertFalse( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_private_changed_to_public() {
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'loggedin',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_field_didnt_previously_exist() {
		$d = $this->setup_updated_profile_data();

		$old_values = array();
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'bar',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}

	/**
	 * @group bp_xprofile_updated_profile_activity
	 */
	public function test_bp_xprofile_updated_profile_activity_public_changes() {
		$d = $this->setup_updated_profile_data();

		$old_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'foo',
				'visibility' => 'public',
			),
		);
		$new_values = array(
			$this->updated_profile_data['f']->id => array(
				'value'      => 'bar',
				'visibility' => 'public',
			),
		);

		$this->assertTrue( bp_xprofile_updated_profile_activity( $d['u'], array( $d['f']->id ), false, $old_values, $new_values ) );
	}


	protected function setup_updated_profile_data() {
		$this->updated_profile_data['u'] = $this->create_user();
		$this->updated_profile_data['g'] = $this->factory->xprofile_group->create();
		$this->updated_profile_data['f'] = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $this->updated_profile_data['g']->id,
		) );

	}
}
