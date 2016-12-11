<?php

/**
 * @group xprofile
 * @group functions
 */
class BP_Tests_XProfile_Functions extends BP_UnitTestCase {
	public function test_get_hidden_field_types_for_user_loggedout() {
		$duser = $this->factory->user->create();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( 0 );

		$this->assertEquals( array( 'friends', 'loggedin', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_loggedin() {
		$duser = $this->factory->user->create();
		$cuser = $this->factory->user->create();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'friends', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_friends() {
		$duser = $this->factory->user->create();
		$cuser = $this->factory->user->create();
		friends_add_friend( $duser, $cuser, true );

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_admin() {
		$duser = $this->factory->user->create();
		$cuser = $this->factory->user->create();
		$this->grant_bp_moderate( $cuser );

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array(), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->revoke_bp_moderate( $cuser );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_xprofile_update_meta
	 * @ticket BP5180
	 */
	public function test_bp_xprofile_update_meta_with_line_breaks() {
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$meta_value = 'Foo!

Bar!';
		bp_xprofile_update_meta( $f, 'field', 'linebreak_field', $meta_value );
		$this->assertEquals( $meta_value, bp_xprofile_get_meta( $f, 'field', 'linebreak_field' ) );
	}

	/**
	 * @group bp_xprofile_fullname_field_id
	 * @group cache
	 */
	public function test_bp_xprofile_fullname_field_id_invalidation() {
		// Prime the cache
		$id = bp_xprofile_fullname_field_id();

		bp_update_option( 'bp-xprofile-fullname-field-name', 'foo' );

		$this->assertFalse( wp_cache_get( 'fullname_field_id', 'bp_xprofile' ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_missing_params() {
		$this->assertSame( '', xprofile_get_field_visibility_level( 0, 1 ) );
		$this->assertSame( '', xprofile_get_field_visibility_level( 1, 0 ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_user_set() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'allowed' );

		xprofile_set_field_visibility_level( $f, $u, 'loggedin' );

		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $f, $u ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_user_unset() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'allowed' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f, $u ) );

	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_admin_override() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'disabled' );

		xprofile_set_field_visibility_level( $f, $u, 'loggedin' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f, $u ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_delete_meta( '', 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_empty_object_type() {
		$this->assertFalse( bp_xprofile_delete_meta( 1, '' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_delete_meta( 1, 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_delete_meta_illegal_characters() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_xprofile_delete_meta( $g, 'group', $krazy_key );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_delete_meta_trim_meta_value() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );

		bp_xprofile_delete_meta( $g, 'group', 'foo', ' bar  ' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_meta_value_match() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_delete_meta( $g, 'group', 'foo', 'bar' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_delete_all() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo2', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo2' ) );

		$this->assertTrue( bp_xprofile_delete_meta( $g, 'group' ) );

		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo2' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_with_delete_all_but_no_meta_key() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g1, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g1, 'group', 'foo1', 'bar1' );
		bp_xprofile_add_meta( $g2, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g2, 'group', 'foo1', 'bar1' );

		$this->assertTrue( bp_xprofile_delete_meta( $g1, 'group', '', '', true ) );
		$this->assertEmpty( bp_xprofile_get_meta( $g1, 'group' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g2, 'group', 'foo' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g2, 'group', 'foo1' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_with_delete_all() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g1, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g1, 'group', 'foo1', 'bar1' );
		bp_xprofile_add_meta( $g2, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g2, 'group', 'foo1', 'bar1' );

		$this->assertTrue( bp_xprofile_delete_meta( $g1, 'group', 'foo', '', true ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g1, 'group', 'foo' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g2, 'group', 'foo' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g1, 'group', 'foo1' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g2, 'group', 'foo1' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_get_meta( 0, 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_empty_object_type() {
		$this->assertFalse( bp_xprofile_get_meta( 1, '' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_get_meta( 1, 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_no_meta_key() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo2', 'bar' );

		$expected = array(
			'foo' => array(
				'bar',
			),
			'foo2' => array(
				'bar',
			),
		);
		$this->assertSame( $expected, bp_xprofile_get_meta( $g, 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_single_true() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) ); // default is true
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo', true ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_single_false() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' );
		$this->assertSame( array( 'bar', 'baz' ), bp_xprofile_get_meta( $g, 'group', 'foo', false ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_no_meta_key_no_results() {
		$g = $this->factory->xprofile_group->create();

		$expected = array();
		$this->assertSame( $expected, bp_xprofile_get_meta( $g, 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_no_object_id() {
		$this->assertFalse( bp_xprofile_update_meta( 0, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_no_object_type() {
		$this->assertFalse( bp_xprofile_update_meta( 1, '', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_update_meta( 1, 'foo', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_update_meta_illegal_characters() {
		$g = $this->factory->xprofile_group->create();
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_xprofile_update_meta( $g, 'group', $krazy_key, 'bar' );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_stripslashes() {
		$g = $this->factory->xprofile_group->create();
		$v = "Totally \'tubular\'";
		bp_xprofile_update_meta( $g, 'group', 'foo', $v );
		$this->assertSame( stripslashes( $v ), bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_empty_value_delete() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', '' );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_new() {
		$g = $this->factory->xprofile_group->create();
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertNotEmpty( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_existing() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_update_meta( $g, 'group', 'foo', 'baz' ) );
		$this->assertSame( 'baz', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_same_value() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertFalse( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_prev_value() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );

		// In earlier versions of WordPress, bp_activity_update_meta()
		// returns true even on failure. However, we know that in these
		// cases the update is failing as expected, so we skip this
		// assertion just to keep our tests passing
		// See https://core.trac.wordpress.org/ticket/24933
		if ( version_compare( $GLOBALS['wp_version'], '3.7', '>=' ) ) {
			$this->assertFalse( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar2', 'baz' ) );
		}

		$this->assertTrue( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar2', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5919
	 */
	public function test_bp_xprofile_update_meta_where_sql_filter_keywords_are_in_quoted_value() {
		$g = $this->factory->xprofile_group->create();
		$value = "SELECT object_id FROM wp_bp_xprofile_groups WHERE \"foo\" VALUES (foo = 'bar'";
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', $value );
		$this->assertSame( $value, bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5919
	 */
	public function test_bp_xprofile_update_meta_where_meta_id_is_in_quoted_value() {
		$g = $this->factory->xprofile_group->create();
		$value = "foo meta_id bar";
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', $value );
		$this->assertSame( $value, bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_no_meta_key() {
		$this->assertFalse( bp_xprofile_add_meta( 1, 'group', '', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_add_meta( 0, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_existing_unique() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		$this->assertFalse( bp_xprofile_add_meta( $g, 'group', 'foo', 'baz', true ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_existing_not_unique() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		$this->assertNotEmpty( bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' ) );
	}

	/**
	 * @group bp_get_member_profile_data
	 */
	public function test_bp_get_member_profile_data_inside_loop() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Neato',
		) );
		xprofile_set_field_data( $f, $u, 'foo' );

		if ( bp_has_members() ) : while ( bp_members() ) : bp_the_member();
		$found = bp_get_member_profile_data( array(
			'user_id' => $u,
			'field' => 'Neato',
		) );
		endwhile; endif;

		// Cleanup
		unset( $GLOBALS['members_template'] );

		$this->assertSame( 'foo', $found );
	}
	/**
	 * @group bp_get_member_profile_data
	 */
	public function test_bp_get_member_profile_data_outside_of_loop() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Kewl',
		) );
		xprofile_set_field_data( $f, $u, 'foo' );

		$found = bp_get_member_profile_data( array(
			'user_id' => $u,
			'field' => 'Kewl',
		) );

		$this->assertSame( 'foo', $found );
	}

	/**
	 * @group xprofile_set_field_data
	 */
	public function test_get_field_data_integer_zero() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'number',
			'name' => 'Pens',
		) );
		xprofile_set_field_data( $f, $u, 0 );

		$this->assertEquals( 0, xprofile_get_field_data( 'Pens', $u ) );
	}

	/**
	 * @group xprofile_set_field_data
	 * @ticket BP5836
	 */
	public function test_xprofile_sync_bp_profile_new_user() {
		$post_vars = $_POST;

		$_POST = array(
			'user_login' => 'foobar',
			'pass1'      => 'password',
			'pass2'      => 'password',
			'role'       => 'subscriber',
			'email'      => 'foo@bar.com',
			'first_name' => 'Foo',
			'last_name'  => 'Bar',
		);

		$id = add_user();

		$display_name = 'Bar Foo';

		$_POST = array(
			'display_name' => $display_name,
			'email' => 'foo@bar.com',
			'nickname' => 'foobar',
		);

		$id = edit_user( $id );

		// clean up post vars
		$_POST = $post_vars;

		$this->assertEquals( $display_name, xprofile_get_field_data( bp_xprofile_fullname_field_id(), $id ) );
	}

	/**
	 * @group xprofile_insert_field
	 */
	public function test_xprofile_insert_field_type_option() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
		) );

		$this->assertNotEmpty( $f );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6354
	 */
	public function test_xprofile_insert_field_should_process_falsey_values_for_boolean_params_on_existing_fields() {
		$g = $this->factory->xprofile_group->create();
		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'type' => 'textbox',
			'name' => 'Foo',
			'is_required' => true,
			'can_delete' => true,
			'is_default_option' => true,
			'parent_id' => 13,
			'field_order' => 5,
			'option_order' => 8,
			'description' => 'foo',
			'order_by' => 'custom',
		) );

		$this->assertNotEmpty( $f );

		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_required );
		$this->assertEquals( 1, $field->can_delete );
		$this->assertEquals( 1, $field->is_default_option );
		$this->assertEquals( 13, $field->parent_id );
		$this->assertEquals( 5, $field->field_order );
		$this->assertEquals( 8, $field->option_order );
		$this->assertEquals( 'foo', $field->description );
		$this->assertEquals( 'custom', $field->order_by );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'type' => 'textbox',
			'name' => 'Foo',
			'is_required' => false,
			'can_delete' => false,
			'is_default_option' => false,
			'parent_id' => 0,
			'field_order' => 0,
			'option_order' => 0,
			'description' => '',
			'order_by' => '',
		) );

		$this->assertNotEmpty( $f );

		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_required );
		$this->assertEquals( 0, $field->can_delete );
		$this->assertEquals( 0, $field->is_default_option );
		$this->assertEquals( 0, $field->parent_id );
		$this->assertEquals( 0, $field->field_order );
		$this->assertEquals( 0, $field->option_order );
		$this->assertEquals( '', $field->description );
		$this->assertEquals( '', $field->order_by );
	}

	/**
	 * @group xprofile_insert_field
	 */
	public function test_xprofile_insert_field_type_option_option_order() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'option_order' => 5,
		) );

		$field = new BP_XProfile_Field( $f );

		$this->assertEquals( 5, $field->option_order );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_false_for_new_option() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => false,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_true_for_new_option() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => true,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_false_for_existing_option() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => true,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_default_option );

		$f = xprofile_insert_field( array(
			'field_id' => $f,
			'field_group_id' => $g,
			'type' => 'textbox',
			'is_default_option' => false,
		) );

		$field2 = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field2->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_true_for_existing_option() {
		$g = $this->factory->xprofile_group->create();
		$parent = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => false,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_default_option );

		$f = xprofile_insert_field( array(
			'field_id' => $f,
			'field_group_id' => $g,
			'type' => 'textbox',
			'is_default_option' => true,
		) );

		$field2 = new BP_XProfile_Field( $f );

		$this->assertEquals( 1, $field2->is_default_option );
	}

	/**
	 * @group xprofile_update_field_group_position
	 * @group bp_profile_get_field_groups
	 */
	public function test_bp_profile_get_field_groups_update_position() {
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		$g3 = $this->factory->xprofile_group->create();

		// prime the cache
		bp_profile_get_field_groups();

		// switch the field group positions for the last two groups
		xprofile_update_field_group_position( $g2, 3 );
		xprofile_update_field_group_position( $g3, 2 );

		// now refetch field groups
		$field_groups = bp_profile_get_field_groups();

		// assert!
		$this->assertEquals( array( 1, $g1, $g3, $g2 ), wp_list_pluck( $field_groups, 'id' ) );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_xprofile_get_field_should_return_bp_xprofile_field_object() {
		global $wpdb;

		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Foo',
		) );

		$field = xprofile_get_field( $f );

		$this->assertTrue( $field instanceof BP_XProfile_Field );
	}

	/**
	 * @ticket BP6638
	 * @group cache
	 */
	public function test_xprofile_get_field_should_prime_field_cache() {
		global $wpdb;

		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Foo',
		) );

		$num_queries = $wpdb->num_queries;

		// Prime the cache.
		$field_1 = xprofile_get_field( $f );
		$num_queries++;
		$this->assertSame( $num_queries, $wpdb->num_queries );

		// No more queries.
		$field_2 = xprofile_get_field( $f );
		$this->assertEquals( $field_1, $field_2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP5625
	 */
	public function test_bp_xprofie_is_richtext_enabled_for_field_should_default_to_true_for_textareas() {
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'textarea',
		) );

		$this->assertTrue( bp_xprofile_is_richtext_enabled_for_field( $f ) );
	}

	/**
	 * @ticket BP5625
	 */
	public function test_bp_xprofie_is_richtext_enabled_for_field_should_default_to_false_for_non_textareas() {
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'radio',
		) );

		$this->assertFalse( bp_xprofile_is_richtext_enabled_for_field( $f ) );
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_empty_param() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'visibility-public'
			);

		$classes = bp_get_field_css_class();
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_space_delimited_string() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'visibility-public',
			'rhubarb',
			'apple'
			);

		$classes = bp_get_field_css_class( 'rhubarb apple' );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_array() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'visibility-public',
			'blueberry',
			'gooseberry'
			);

		$classes = bp_get_field_css_class( array( 'blueberry', 'gooseberry' ) );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group xprofile_filter_link_profile_data
	 */
	public function test_field_comma_seperated_values_are_autolinked() {
		$field_group_id = $this->factory->xprofile_group->create();
		$field_id = $this->factory->xprofile_field->create( array( 'field_group_id' => $field_group_id ) );
		$GLOBALS['field'] = new BP_XProfile_Field( $field_id );
		$GLOBALS['field']->do_autolink = true;

		$output = xprofile_filter_link_profile_data( 'Hello world this is a test; with, some, words', 'textbox' );
		$regex = '#^Hello world this is a test; with, <a href="([^"]+)" rel="nofollow">some</a>, <a href="([^"]+)" rel="nofollow">words</a>$#i';

		$this->assertRegExp( $regex, $output );
		unset( $GLOBALS['field'] );
	}

	/**
	 * @group xprofile_filter_link_profile_data
	 */
	public function test_field_semicolon_seperated_values_are_autolinked() {
		$field_group_id = $this->factory->xprofile_group->create();
		$field_id = $this->factory->xprofile_field->create( array( 'field_group_id' => $field_group_id ) );
		$GLOBALS['field'] = new BP_XProfile_Field( $field_id );
		$GLOBALS['field']->do_autolink = true;

		$output = xprofile_filter_link_profile_data( 'Hello world this is a test with; some; words', 'textbox' );
		$regex = '#^Hello world this is a test with; <a href="([^"]+)" rel="nofollow">some</a>; <a href="([^"]+)" rel="nofollow">words</a>$#i';

		$this->assertRegExp( $regex, $output );
		unset( $GLOBALS['field'] );
	}
}
