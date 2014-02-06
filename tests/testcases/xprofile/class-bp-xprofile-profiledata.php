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
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		$d = new BP_XProfile_ProfileData( $f->id, $u );

		$this->assertFalse( $d->exists() );
	}

	/**
	 * @group exists
	 */
	public function test_exists_when_exists_uncached() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		xprofile_set_field_data( $f->id, $u, 'foo' );

		$d = new BP_XProfile_ProfileData( $f->id, $u );

		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u );

		$this->assertTrue( $d->exists() );
	}

	/**
	 * @group exists
	 */
	public function test_exists_when_exists_in_cache() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );
		$d = new BP_XProfile_ProfileData( $f->id, $u );

		// Fake the cache
		wp_cache_set( $f->id, 'foo', 'bp_xprofile_data_' . $u );

		$this->assertTrue( $d->exists() );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_doesnt_exist() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		// Just to be sure
		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u );

		$this->assertEquals( 0, BP_XProfile_ProfileData::get_fielddataid_byid( $f->id, $u ) );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_exists_uncached() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		$d = new BP_XProfile_ProfileData();
		$d->user_id = $u;
		$d->field_id = $f->id;
		$d->value = 'foo';
		$d->save();

		// Ensure it's deleted from cache
		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u );

		$this->assertEquals( $d->id, BP_XProfile_ProfileData::get_fielddataid_byid( $f->id, $u ) );
	}

	/**
	 * @group get_fielddataid_byid
	 */
	public function test_get_fielddataid_byid_when_exists_in_cache() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		// Fake the cache
		$d = new stdClass;
		$d->id = 5;
		wp_cache_set( $f->id, $d, 'bp_xprofile_data_' . $u );

		$this->assertSame( 5, BP_XProfile_ProfileData::get_fielddataid_byid( $f->id, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_singleuser_uncached() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		$d = new BP_XProfile_ProfileData();
		$d->user_id = $u;
		$d->field_id = $f->id;
		$d->value = 'foo';
		$d->save();

		// Ensure it's deleted from cache
		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u );

		$this->assertSame( 'foo', BP_XProfile_ProfileData::get_value_byid( $f->id, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_multipleusers_uncached() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		$time = bp_core_current_time();

		$d1 = new BP_XProfile_ProfileData();
		$d1->user_id = $u1;
		$d1->field_id = $f->id;
		$d1->value = 'foo';
		$d1->last_updated = $time;
		$d1->save();

		$d2 = new BP_XProfile_ProfileData();
		$d2->user_id = $u2;
		$d2->field_id = $f->id;
		$d2->value = 'bar';
		$d2->last_updated = $time;
		$d2->save();

		// Ensure it's deleted from cache
		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u1 );
		wp_cache_delete( $f->id, 'bp_xprofile_data_' . $u2 );

		$eu1 = new stdClass;
		$eu1->user_id = $u1;
		$eu1->value = 'foo';
		$eu1->id = $d1->id;
		$eu1->field_id = $f->id;
		$eu1->last_updated = $time;

		$eu2 = new stdClass;
		$eu2->user_id = $u2;
		$eu2->value = 'bar';
		$eu2->id = $d2->id;
		$eu2->field_id = $f->id;
		$eu2->last_updated = $time;

		$expected = array( $eu1, $eu2 );

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_value_byid( $f->id, array( $u1, $u2 ) ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_singleuser_cached() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		$time = bp_core_current_time();

		// Fake the cache
		$d = new stdClass;
		$d->value = 'foo';
		$d->field_id = $f->id;
		wp_cache_set( $f->id, $d, 'bp_xprofile_data_' . $u );

		$this->assertSame( 'foo', BP_XProfile_ProfileData::get_value_byid( $f->id, $u ) );
	}

	/**
	 * @group get_value_byid
	 */
	public function test_get_value_byid_multipleusers_cached() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g->id,
		) );

		// Fake the cache
		$d1 = new stdClass;
		$d1->id = 10;
		$d1->user_id = $u1;
		$d1->field_id = $f->id;
		$d1->value = 'foo';
		$d1->last_updated = $time;

		$d2 = new stdClass;
		$d1->id = 21;
		$d2->user_id = $u2;
		$d2->field_id = $f->id;
		$d2->value = 'bar';
		$d2->last_updated = $time;

		wp_cache_set( $f->id, $d1, 'bp_xprofile_data_' . $u1 );
		wp_cache_set( $f->id, $d2, 'bp_xprofile_data_' . $u2 );

		$eu1 = new stdClass;
		$eu1->id = 10;
		$eu1->user_id = $u1;
		$eu1->field_id = $f->id;
		$eu1->value = 'foo';
		$eu1->last_updated = $time;

		$eu2 = new stdClass;
		$eu1->id = 21;
		$eu2->user_id = $u2;
		$eu2->field_id = $f->id;
		$eu2->value = 'bar';
		$eu2->last_updated = $time;

		$expected = array( $eu1, $eu2 );

		$this->assertEquals( $expected, BP_XProfile_ProfileData::get_value_byid( $f->id, array( $u1, $u2 ) ) );
	}

}
