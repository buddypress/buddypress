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
}
