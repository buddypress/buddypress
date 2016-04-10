<?php

/**
 * @group members
 * @group member_types
 */
class BP_Tests_Members_Template_BpGetMemberTypeDirectoryPermalink extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		buddypress()->members->types = array();
	}

	/**
	 * @ticket BP6840
	 */
	public function test_should_default_to_current_member_type() {
		bp_register_member_type( 'foo', array(
			'has_directory' => true,
		) );

		add_filter( 'bp_get_current_member_type', array( $this, 'fake_current_member_type' ) );
		$found = bp_get_member_type_directory_permalink( 'foo' );
		remove_filter( 'bp_get_current_member_type', array( $this, 'fake_current_member_type' ) );

		$this->assertContains( '/type/foo/', $found );
	}

	/**
	 * @ticket BP6840
	 */
	public function test_member_type_param_should_override_current_member_type() {
		bp_register_member_type( 'foo', array(
			'has_directory' => true,
		) );
		bp_register_member_type( 'bar', array(
			'has_directory' => true,
		) );

		add_filter( 'bp_get_current_member_type', array( $this, 'fake_current_member_type' ) );
		$found = bp_get_member_type_directory_permalink( 'bar' );
		remove_filter( 'bp_get_current_member_type', array( $this, 'fake_current_member_type' ) );

		$this->assertContains( '/type/bar/', $found );
	}

	public function fake_current_member_type() {
		return 'foo';
	}

	/**
	 * @ticket BP6840
	 */
	public function test_should_return_empty_string_when_type_does_not_exist() {
		$this->assertSame( '', bp_get_member_type_directory_permalink( 'foo' ) );
	}

	/**
	 * @ticket BP6840
	 */
	public function test_should_return_empty_string_when_has_directory_is_false_for_type() {
		bp_register_member_type( 'foo', array(
			'has_directory' => false,
		) );

		$this->assertSame( '', bp_get_member_type_directory_permalink( 'foo' ) );
	}

	/**
	 * @ticket BP6840
	 */
	public function test_successful_format() {
		bp_register_member_type( 'foo', array(
			'has_directory' => true,
		) );

		$expected = bp_get_members_directory_permalink() . 'type/foo/';

		$this->assertSame( $expected, bp_get_member_type_directory_permalink( 'foo' ) );
	}
}
