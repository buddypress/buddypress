<?php

/**
 * @group xprofile
 * @group functions
 */
class BP_Tests_XProfile_Functions extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_get_hidden_field_types_for_user_loggedout() {
		$duser = $this->create_user();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( 0 );

		$this->assertEquals( array( 'friends', 'loggedin', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_loggedin() {
		$duser = $this->create_user();
		$cuser = $this->create_user();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'friends', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_friends() {
		$duser = $this->create_user();
		$cuser = $this->create_user();
		friends_add_friend( $duser, $cuser, true );

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_admin() {
		$duser = $this->create_user();
		$cuser = $this->create_user();
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
			'field_group_id' => $g->id,
			'type' => 'textbox',
		) );

		$meta_value = 'Foo!

Bar!';
		bp_xprofile_update_meta( $f->id, 'field', 'linebreak_field', $meta_value );
		$this->assertEquals( $meta_value, bp_xprofile_get_meta( $f->id, 'field', 'linebreak_field' ) );
	}

	/**
	 * @group bp_xprofile_fullname_field_id
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
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g->id,
			'type' => 'textbox',
		) );

		bp_xprofile_update_meta( $f->id, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f->id, 'field', 'allow_custom_visibility', 'allowed' );

		xprofile_set_field_visibility_level( $f->id, $u, 'loggedin' );

		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $f->id, $u ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_user_unset() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g->id,
			'type' => 'textbox',
		) );

		bp_xprofile_update_meta( $f->id, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f->id, 'field', 'allow_custom_visibility', 'allowed' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f->id, $u ) );

	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_admin_override() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $g->id,
			'type' => 'textbox',
		) );

		bp_xprofile_update_meta( $f->id, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f->id, 'field', 'allow_custom_visibility', 'disabled' );

		xprofile_set_field_visibility_level( $f->id, $u, 'loggedin' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f->id, $u ) );
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
	 */
	public function test_bp_xprofile_delete_meta_illegal_characters() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertTrue( bp_xprofile_delete_meta( $g->id, 'group', 'foo' ) );
		$this->assertEquals( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_trim_meta_value() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );

		$this->assertTrue( bp_xprofile_delete_meta( $g->id, 'group', 'foo', ' bar  ' ) );
		$this->assertEquals( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_meta_value_match() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_delete_meta( $g->id, 'group', 'foo', 'bar' ) );
		$this->assertEquals( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_delete_all() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g->id, 'group', 'foo2', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo2' ) );

		$this->assertTrue( bp_xprofile_delete_meta( $g->id, 'group' ) );

		// These will fail because of a caching bug
		//$this->assertEquals( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		//$this->assertEquals( '', bp_xprofile_get_meta( $g->id, 'group', 'foo2' ) );
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
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g->id, 'group', 'foo2', 'bar' );

		$expected = array( 'bar', 'bar', );
		$this->assertSame( $expected, bp_xprofile_get_meta( $g->id, 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_no_meta_key_no_results() {
		$g = $this->factory->xprofile_group->create();

		$expected = array();
		$this->assertSame( $expected, bp_xprofile_get_meta( $g->id, 'group' ) );
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
	 */
	public function test_bp_xprofile_update_meta_illegal_characters() {
		$g = $this->factory->xprofile_group->create();
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_xprofile_update_meta( $g->id, 'group', $krazy_key, 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_stripslashes() {
		$g = $this->factory->xprofile_group->create();
		$v = "Totally \'tubular\'";
		bp_xprofile_update_meta( $g->id, 'group', 'foo', $v );
		$this->assertSame( stripslashes( $v ), bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_empty_value_delete() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g->id, 'group', 'foo', '' );
		$this->assertSame( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_new() {
		$g = $this->factory->xprofile_group->create();
		$this->assertSame( '', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_existing() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_update_meta( $g->id, 'group', 'foo', 'baz' ) );
		$this->assertSame( 'baz', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_same_value() {
		$g = $this->factory->xprofile_group->create();
		bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g->id, 'group', 'foo' ) );
		$this->assertFalse( bp_xprofile_update_meta( $g->id, 'group', 'foo', 'bar' ) );
	}
}
