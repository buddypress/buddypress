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
}
