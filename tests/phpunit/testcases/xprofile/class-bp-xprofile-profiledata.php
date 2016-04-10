<?php

/**
 * @group xprofile
 * @group BP_XProfile_ProfileData
 */
class BP_Tests_BP_XProfile_ProfileData_TestCases extends BP_UnitTestCase {
	/**
	 * @group exists
	 */
	public function test_exists_when_doesnt_exist() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );

		$this->assertFalse( $d->exists() );
	}

	/**
	 * @group exists
	 */
	public function test_exists_when_exists_uncached() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		xprofile_set_field_data( $f, $u, 'foo' );

		$d = new BP_XProfile_ProfileData( $f, $u );

		wp_cache_delete( "{$u}:{$f}", 'bp_xprofile_data' );

		$this->assertTrue( $d->exists() );
	}

	/**
	 * @group exists
	 */
	public function test_exists_when_exists_in_cache() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );
		$d = new BP_XProfile_ProfileData( $f, $u );

		// Fake the cache
		$c = new stdClass;
		$c->id = 3;
		wp_cache_set( "{$u}:{$f}", $c, 'bp_xprofile_data' );

		$this->assertTrue( $d->exists() );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_doesnt_exist() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		// Just to be sure
		wp_cache_delete( "{$u}:{$f}", 'bp_xprofile_data' );

		$this->assertEquals( 0, BP_XProfile_ProfileData::get_fielddataid_byid( $f, $u ) );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_exists_uncached() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData();
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->save();

		// Ensure it's deleted from cache
		wp_cache_delete( "{$u}:{$f}", 'bp_xprofile_data' );

		$this->assertEquals( $d->id, BP_XProfile_ProfileData::get_fielddataid_byid( $f, $u ) );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_exists_in_cache() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		// Fake the cache
		$d = new stdClass;
		$d->id = 5;
		wp_cache_set( "{$u}:{$f}", $d, 'bp_xprofile_data' );

		$this->assertSame( 5, BP_XProfile_ProfileData::get_fielddataid_byid( $f, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_singleuser_uncached() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData();
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->save();

		// Ensure it's deleted from cache
		wp_cache_delete( "{$u}:{$f}", 'bp_xprofile_data' );

		$this->assertSame( 'foo', BP_XProfile_ProfileData::get_value_byid( $f, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_multipleusers_uncached() {
		$time = date( 'Y-m-d H:i:s', time() - 60*60*24 );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		// To make the tests reliable, we have to force the last_updated
		// BP doesn't easily allow this except via filter
		$this->last_updated = $time;
		add_filter( 'xprofile_data_last_updated_before_save', array( $this, 'filter_time' ) );

		$d1 = new BP_XProfile_ProfileData();
		$d1->user_id = $u1;
		$d1->field_id = $f;
		$d1->value = 'foo';
		$d1->last_updated = $time;
		$d1->save();

		$d2 = new BP_XProfile_ProfileData();
		$d2->user_id = $u2;
		$d2->field_id = $f;
		$d2->value = 'bar';
		$d2->last_updated = $time;
		$d2->save();

		remove_filter( 'xprofile_data_last_updated_before_save', array( $this, 'filter_time' ) );

		// Ensure it's deleted from cache
		wp_cache_delete( "{$u1}:{$f}", 'bp_xprofile_data' );
		wp_cache_delete( "{$u2}:{$f}", 'bp_xprofile_data' );

		$eu1 = new stdClass;
		$eu1->user_id = $u1;
		$eu1->value = 'foo';
		$eu1->id = $d1->id;
		$eu1->field_id = $f;
		$eu1->last_updated = $time;

		$eu2 = new stdClass;
		$eu2->user_id = $u2;
		$eu2->value = 'bar';
		$eu2->id = $d2->id;
		$eu2->field_id = $f;
		$eu2->last_updated = $time;

		$expected = array( $eu1, $eu2 );

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_value_byid( $f, array( $u1, $u2 ) ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_singleuser_cached() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$time = bp_core_current_time();

		// Fake the cache
		$d = new stdClass;
		$d->value = 'foo';
		$d->field_id = $f;
		wp_cache_set( "{$u}:{$f}", $d, 'bp_xprofile_data' );

		$this->assertSame( 'foo', BP_XProfile_ProfileData::get_value_byid( $f, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_multipleusers_cached() {
		$time = date( 'Y-m-d H:i:s', time() - 60*60*24 );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		// Fake the cache
		$d1 = new stdClass;
		$d1->id = 10;
		$d1->user_id = $u1;
		$d1->field_id = $f;
		$d1->value = 'foo';
		$d1->last_updated = $time;

		$d2 = new stdClass;
		$d1->id = 21;
		$d2->user_id = $u2;
		$d2->field_id = $f;
		$d2->value = 'bar';
		$d2->last_updated = $time;

		wp_cache_set( "{$u1}:{$f}", $d1, 'bp_xprofile_data' );
		wp_cache_set( "{$u2}:{$f}", $d2, 'bp_xprofile_data' );

		$eu1 = new stdClass;
		$eu1->id = 10;
		$eu1->user_id = $u1;
		$eu1->field_id = $f;
		$eu1->value = 'foo';
		$eu1->last_updated = $time;

		$eu2 = new stdClass;
		$eu1->id = 21;
		$eu2->user_id = $u2;
		$eu2->field_id = $f;
		$eu2->value = 'bar';
		$eu2->last_updated = $time;

		$expected = array( $eu1, $eu2 );

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_value_byid( $f, array( $u1, $u2 ) ) );
	}

	/**
	 * @group get_all_for_user
	 */
	public function test_get_all_for_user_uncached() {
		$u = $this->factory->user->create();
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		$f1 = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g1,
		) );
		$f2 = $this->factory->xprofile_field->create( array(
			'type' => 'radio',
			'field_group_id' => $g2,
		) );

		$time = bp_core_current_time();

		// Get the fullname field - hackish
		$f0_id = xprofile_get_field_id_from_name( bp_xprofile_fullname_field_name() );
		$f0 = new BP_XProfile_Field( $f0_id );
		$g0 = new BP_XProfile_Group( $f0->group_id );
		$d0 = new BP_XProfile_ProfileData( $f0->id, $u );

		$d1 = new BP_XProfile_ProfileData();
		$d1->user_id = $u;
		$d1->field_id = $f1;
		$d1->value = 'foo';
		$d1->last_updated = $time;
		$d1->save();

		$d2 = new BP_XProfile_ProfileData();
		$d2->user_id = $u;
		$d2->field_id = $f2;
		$d2->value = 'bar';
		$d2->last_updated = $time;
		$d2->save();

		// Ensure it's deleted from cache
		wp_cache_delete( "{$u}:{$f1}", 'bp_xprofile_data' );
		wp_cache_delete( "{$u}:{$f2}", 'bp_xprofile_data' );

		$u_obj = new WP_User( $u );

		$g1_obj = new BP_XProfile_Group( $g1 );
		$g2_obj = new BP_XProfile_Group( $g2 );
		$f1_obj = new BP_XProfile_Field( $f1 );
		$f2_obj = new BP_XProfile_Field( $f2 );

		$expected = array(
			'user_login' => $u_obj->user_login,
			'user_nicename' => $u_obj->user_nicename,
			'user_email' => $u_obj->user_email,
			$f0->name => array(
				'field_group_id' => $g0->id,
				'field_group_name' => $g0->name,
				'field_id' => $f0->id,
				'field_type' => $f0->type,
				'field_data' => $d0->value,
			),
			$f1_obj->name => array(
				'field_group_id' => $g1,
				'field_group_name' => $g1_obj->name,
				'field_id' => $f1,
				'field_type' => $f1_obj->type,
				'field_data' => $d1->value,
			),
			$f2_obj->name => array(
				'field_group_id' => $g2,
				'field_group_name' => $g2_obj->name,
				'field_id' => $f2,
				'field_type' => $f2_obj->type,
				'field_data' => $d2->value,
			),
		);

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_all_for_user( $u ) );
	}

	/**
	 * @group get_all_for_user
	 */
	public function test_get_all_for_user_cached() {
		$u = $this->factory->user->create();
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		$f1 = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g1,
		) );
		$f2 = $this->factory->xprofile_field->create( array(
			'type' => 'radio',
			'field_group_id' => $g2,
		) );

		$time = bp_core_current_time();

		$g0 = new BP_XProfile_Group( 1 );
		$f0 = new BP_XProfile_Field( 1 );
		$d0 = new BP_XProfile_ProfileData( 1, $u );

		$d1 = new stdClass;
		$d1->user_id = $u;
		$d1->field_id = $f1;
		$d1->value = 'foo';
		$d1->last_updated = $time;
		$d1->id = 1;

		$d2 = new stdClass;
		$d2->user_id = $u;
		$d2->field_id = $f2;
		$d2->value = 'bar';
		$d2->last_updated = $time;
		$d2->id = 2;

		wp_cache_set( "{$u}:{$f1}", $d1, 'bp_xprofile_data' );
		wp_cache_set( "{$u}:{$f2}", $d2, 'bp_xprofile_data' );

		$u_obj = new WP_User( $u );

		$g1_obj = new BP_XProfile_Group( $g1 );
		$g2_obj = new BP_XProfile_Group( $g2 );
		$f1_obj = new BP_XProfile_Field( $f1 );
		$f2_obj = new BP_XProfile_Field( $f2 );

		$expected = array(
			'user_login' => $u_obj->user_login,
			'user_nicename' => $u_obj->user_nicename,
			'user_email' => $u_obj->user_email,
			$f0->name => array(
				'field_group_id' => $g0->id,
				'field_group_name' => $g0->name,
				'field_id' => $f0->id,
				'field_type' => $f0->type,
				'field_data' => $d0->value,
			),
			$f1_obj->name => array(
				'field_group_id' => $g1,
				'field_group_name' => $g1_obj->name,
				'field_id' => $f1,
				'field_type' => $f1_obj->type,
				'field_data' => $d1->value,
			),
			$f2_obj->name => array(
				'field_group_id' => $g2,
				'field_group_name' => $g2_obj->name,
				'field_id' => $f2,
				'field_type' => $f2_obj->type,
				'field_data' => $d2->value,
			),
		);

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_all_for_user( $u ) );
	}

	public function filter_time() {
		return $this->last_updated;
	}
}
