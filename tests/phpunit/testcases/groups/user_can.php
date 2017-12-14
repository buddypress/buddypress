<?php

/**
 * @group bp_user_can
 */
class BP_Tests_Groups_User_Can_Filter extends BP_UnitTestCase {

	public function test_user_can_join_public_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_join_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_join_public_group_if_already_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_join_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_join_private_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_join_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_join_group_if_banned() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_join_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_request_membership_in_public_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_request_membership_in_private_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_request_membership_in_hidden_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_request_membership_in_private_group_if_already_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_request_membership_in_private_group_if_already_requested() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		groups_send_membership_request( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_request_membership_in_private_group_if_banned() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_request_membership', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_receive_invitation_to_public_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_receive_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_receive_invitation_to_private_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_receive_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_receive_invitation_to_private_group_if_already_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_receive_invitation', array( 'group_id' => $g1 ) ) );
	}


	public function test_user_cannot_receive_invitation_to_private_group_if_banned() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_receive_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_receive_invitation_to_hidden_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_receive_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_send_invitation_to_public_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_send_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_send_invitation_to_private_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_send_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_send_invitation_to_private_group_if_banned() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		groups_update_groupmeta( $g1, 'invite_status', 'members' );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g1 );

		$this->assertFalse( bp_user_can( $u1, 'groups_send_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_send_invitation_to_private_group_if_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private',

		) );
		groups_update_groupmeta( $g1, 'invite_status', 'members' );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertTrue( bp_user_can( $u1, 'groups_send_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_send_invitation_to_hidden_group_if_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertTrue( bp_user_can( $u1, 'groups_send_invitation', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_access_public_group_even_when_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertTrue( bp_user_can( 0, 'groups_access_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_can_access_public_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_access_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_access_private_group_if_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertFalse( bp_user_can( 0, 'groups_access_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_cannot_access_private_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_access_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_access_private_group_if_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertTrue( bp_user_can( $u1, 'groups_access_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_access_hidden_group_if_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertFalse( bp_user_can( 0, 'groups_access_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_cannot_access_hidden_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_access_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_access_hidden_group_if_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertTrue( bp_user_can( $u1, 'groups_access_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_see_public_group_even_when_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertTrue( bp_user_can( 0, 'groups_see_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_can_see_public_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'public'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_see_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_see_private_group_even_when_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertTrue( bp_user_can( 0, 'groups_see_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_can_see_private_group() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'private'
		) );
		$u1 = $this->factory->user->create();

		$this->assertTrue( bp_user_can( $u1, 'groups_see_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_cannot_see_hidden_group_if_not_logged_in() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertFalse( bp_user_can( 0, 'groups_see_group', array( 'group_id' => $g1 ) ) );

		$this->set_current_user( $old_user );
	}

	public function test_user_cannot_see_hidden_group_if_not_a_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();

		$this->assertFalse( bp_user_can( $u1, 'groups_see_group', array( 'group_id' => $g1 ) ) );
	}

	public function test_user_can_see_hidden_group_if_member() {
		$g1 = $this->factory->group->create( array(
			'status'      => 'hidden'
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g1 );

		$this->assertTrue( bp_user_can( $u1, 'groups_see_group', array( 'group_id' => $g1 ) ) );
	}

}
