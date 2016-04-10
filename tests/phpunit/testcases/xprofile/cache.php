<?php

/**
 * @group xprofile
 * @group cache
 */
class BP_Tests_XProfile_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_xprofile_update_meta_cache
	 */
	public function test_bp_xprofile_update_meta_cache() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_xprofile_update_meta_cache( array(
			'group' => array( $g ),
			'field' => array( $f ),
			'data' => array( $d->id ),
		) );

		$g_expected = array(
			'group_foo' => array(
				'group_bar',
			),
		);

		$this->assertSame( $g_expected, wp_cache_get( $g, 'xprofile_group_meta' ) );

		$f_expected = array(
			'field_foo' => array(
				'field_bar',
			),
		);

		$this->assertSame( $f_expected, wp_cache_get( $f, 'xprofile_field_meta' ) );

		$d_expected = array(
			'data_foo' => array(
				'data_bar',
			),
		);

		$this->assertSame( $d_expected, wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @group bp_xprofile_update_meta_cache
	 * @group bp_has_profile
	 */
	public function test_bp_has_profile_meta_cache() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_has_profile( array(
			'user_id' => $u,
			'profile_group_id' => $g,
		) );

		$g_expected = array(
			'group_foo' => array(
				'group_bar',
			),
		);

		$this->assertSame( $g_expected, wp_cache_get( $g, 'xprofile_group_meta' ) );

		$f_expected = array(
			'field_foo' => array(
				'field_bar',
			),
		);

		$this->assertSame( $f_expected, wp_cache_get( $f, 'xprofile_field_meta' ) );

		$d_expected = array(
			'data_foo' => array(
				'data_bar',
			),
		);

		$this->assertSame( $d_expected, wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @group bp_xprofile_update_meta_cache
	 * @group bp_has_profile
	 */
	public function test_bp_has_profile_meta_cache_update_meta_cache_false() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_has_profile( array(
			'user_id' => $u,
			'profile_group_id' => $g,
			'update_meta_cache' => false,
		) );

		$this->assertFalse( wp_cache_get( $g, 'xprofile_group_meta' ) );
		$this->assertFalse( wp_cache_get( $f, 'xprofile_field_meta' ) );
		$this->assertFalse( wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_field_cache_should_be_invalidated_on_save() {
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Foo',
		) );

		$field = xprofile_get_field( $f );
		$this->assertSame( 'Foo', $field->name );

		$field->name = 'Bar';
		$this->assertNotEmpty( $field->save() );

		$field_2 = xprofile_get_field( $f );
		$this->assertSame( 'Bar', $field_2->name );
	}
}
