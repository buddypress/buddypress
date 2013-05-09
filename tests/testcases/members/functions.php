<?php
/**
 * @group members
 */
class BP_Tests_Members_Functions extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	/**
	 * ticket BP4915
	 */
	public function test_bp_core_delete_account() {
		// Stash
		$current_user = get_current_user_id();
		$deletion_disabled = bp_disable_account_deletion();

		// Create an admin for testing
		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $admin_user );

		// 1. Admin can delete user account
		$this->set_current_user( $admin_user );
		$user1 = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		bp_core_delete_account( $user1 );
		$maybe_user = new WP_User( $user1 );
		$this->assertEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// 2. Admin cannot delete superadmin account
		$user2 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $user2 );
		bp_core_delete_account( $user2 );
		$maybe_user = new WP_User( $user2 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// User cannot delete other's account
		$user3 = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user4 = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->set_current_user( $user3 );
		bp_core_delete_account( $user4 );
		$maybe_user = new WP_User( $user4 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// User cannot delete own account when account deletion is disabled
		$user5 = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->set_current_user( $user5 );
		bp_update_option( 'bp-disable-account-deletion', 1 );
		bp_core_delete_account( $user5 );
		$maybe_user = new WP_User( $user5 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// User can delete own account when account deletion is enabled
		$user6 = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->set_current_user( $user6 );
		bp_update_option( 'bp-disable-account-deletion', 0 );
		bp_core_delete_account( $user6 );
		$maybe_user = new WP_User( $user6 );
		$this->assertEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// Cleanup
		$this->set_current_user( $current_user );
		bp_update_option( 'bp-disable-account-deletion', $deletion_disabled );
	}
}
