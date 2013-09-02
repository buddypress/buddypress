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
}
