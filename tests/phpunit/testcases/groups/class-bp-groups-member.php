<?php
/**
 * @group groups
 * @group BP_Groups_Member
 */
class BP_Tests_BP_Groups_Member_TestCases extends BP_UnitTestCase {
	public static function invite_user_to_group( $user_id, $group_id, $inviter_id ) {
		$invite                = new BP_Groups_Member;
		$invite->group_id      = $group_id;
		$invite->user_id       = $user_id;
		$invite->date_modified = bp_core_current_time();
		$invite->inviter_id    = $inviter_id;
		$invite->is_confirmed  = 0;
		$invite->invite_sent   = 1;

		$invite->save();
		return $invite->id;
	}

	public static function create_group_membership_request( $user_id, $group_id ) {
		$request                = new BP_Groups_Member;
		$request->group_id      = $group_id;
		$request->user_id       = $user_id;
		$request->date_modified = bp_core_current_time();
		$request->inviter_id    = 0;
		$request->is_confirmed  = 0;

		$request->save();
		return $request->id;
	}

	public function test_get_recently_joined_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Tab',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Diet Rite',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Member::get_recently_joined( $u, false, false, 'Rite' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_is_admin_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$m1 = new BP_Groups_Member( $u, $g1 );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u, $g2 );
		$m2->promote( 'admin' );

		$groups = BP_Groups_Member::get_is_admin_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_is_mod_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$m1 = new BP_Groups_Member( $u, $g1 );
		$m1->promote( 'mod' );
		$m2 = new BP_Groups_Member( $u, $g2 );
		$m2->promote( 'mod' );

		$groups = BP_Groups_Member::get_is_mod_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_invites_with_exclude() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		self::add_user_to_group( $u1, $g1 );
		self::add_user_to_group( $u1, $g2 );
		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );

		$groups = BP_Groups_Member::get_invites( $u2, false, false, array( 'awesome', $g1 ) );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	/**
	 * @expectedDeprecated BP_Groups_Member::get_all_for_group
	 */
	public function test_get_all_for_group_with_exclude() {
		$g1 = $this->factory->group->create();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		self::add_user_to_group( $u1, $g1 );
		self::add_user_to_group( $u2, $g1 );

		$members = BP_Groups_Member::get_all_for_group( $g1, false, false, true, true, array( $u1 ) );

		$mm = (array) $members['members'];
		$ids = wp_list_pluck( $mm, 'user_id' );
		$this->assertEquals( array( $u2 ), $ids );
	}

	/**
	 * @group bp_groups_user_can_send_invites
	 */
	public function test_bp_groups_user_can_send_invites() {
		$u_nonmembers = $this->factory->user->create();
		$u_members = $this->factory->user->create();
		$u_mods = $this->factory->user->create();
		$u_admins = $this->factory->user->create();
		$u_siteadmin = $this->factory->user->create();
		$user_siteadmin = new WP_User( $u_siteadmin );
		$user_siteadmin->add_role( 'administrator' );

		$g = $this->factory->group->create();

		$now = time();
		$old_current_user = get_current_user_id();

		// Create member-level user
		$this->add_user_to_group( $u_members, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		// Create mod-level user
		$this->add_user_to_group( $u_mods, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$m_mod = new BP_Groups_Member( $u_mods, $g );
		$m_mod->promote( 'mod' );
		// Create admin-level user
		$this->add_user_to_group( $u_admins, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$m_admin = new BP_Groups_Member( $u_admins, $g );
		$m_admin->promote( 'admin' );

		// Test with no status
		// In bp_group_get_invite_status(), no status falls back to "members"
		$this->assertTrue( '' == groups_get_groupmeta( $g, 'invite_status' ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_nonmembers ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );

		// Test with members status
		groups_update_groupmeta( $g, 'invite_status', 'members' );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_nonmembers ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
		// Falling back to current user
		$this->set_current_user( $u_members );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );

		// Test with mod status
		groups_update_groupmeta( $g, 'invite_status', 'mods' );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_nonmembers ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
		// Falling back to current user
		$this->set_current_user( $u_members );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, null ) );
		$this->set_current_user( $u_mods );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );

		// Test with admin status
		groups_update_groupmeta( $g, 'invite_status', 'admins' );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_nonmembers ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
		// Falling back to current user
		$this->set_current_user( $u_mods );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, null ) );
		$this->set_current_user( $u_admins );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );

		// Bad or null parameters
		$this->assertFalse( bp_groups_user_can_send_invites( 59876454257, $u_members ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, 958647515 ) );
		// Not in group context
		$this->assertFalse( bp_groups_user_can_send_invites( null, $u_members ) );
		// In group context
		$g_obj = groups_get_group( array( 'group_id' => $g ) );
		$this->go_to( bp_get_group_permalink( $g_obj ) );
		groups_update_groupmeta( $g, 'invite_status', 'mods' );
		$this->assertFalse( bp_groups_user_can_send_invites( null, $u_nonmembers ) );
		$this->assertFalse( bp_groups_user_can_send_invites( null, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( null, $u_mods ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_reject_membership_request
 	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_bp_groups_reject_membership_request_remove_request() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		// Membership requests should be removed.
		self::create_group_membership_request( $u1, $g );
		groups_reject_membership_request( null, $u1, $g );
		$u1_has_request = groups_check_for_membership_request( $u1, $g );
		$this->assertEquals( 0, $u1_has_request );
	}

	/**
	 * @group groups_delete_membership_request
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_bp_groups_delete_membership_request_remove_request() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		// Membership requests should be removed.
		self::create_group_membership_request( $u1, $g );
		groups_delete_membership_request( null, $u1, $g );
		$u1_has_request = groups_check_for_membership_request( $u1, $g );
		$this->assertEquals( 0, $u1_has_request );
	}

	/**
	 * @group groups_reject_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_bp_groups_reject_invite_remove_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		$now = time();
		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );

		// The invitation should be removed.
		self::invite_user_to_group( $u2, $g, $u1 );
		groups_reject_invite( $u2, $g );
		$u2_has_invite = groups_check_user_has_invite( $u2, $g );
		$this->assertEquals( 0, $u2_has_invite );
	}

	/**
	 * @group groups_delete_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_bp_groups_delete_invite_remove_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		$now = time();
		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );

		// The invitation should be removed.
		self::invite_user_to_group( $u2, $g, $u1 );
		groups_delete_invite( $u2, $g );
		$u2_has_invite = groups_check_user_has_invite( $u2, $g );
		$this->assertEquals( 0, $u2_has_invite );
	}

	/**
	 * @group groups_delete_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_bp_groups_delete_invite_leave_memberships_intact() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		$now = time();
		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );

		groups_delete_invite( $u1, $g );
		$u1_is_member = groups_is_user_member( $u1, $g );
		$this->assertTrue( is_numeric( $u1_is_member ) && $u1_is_member > 0 );
	}

	/**
	 * @group groups_delete_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_bp_groups_delete_invite_leave_requests_intact() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		// Membership requests should be left intact.
		self::create_group_membership_request( $u1, $g );
		groups_delete_invite( $u1, $g );
		$u1_has_request = groups_check_for_membership_request( $u1, $g );
		$this->assertTrue( is_numeric( $u1_has_request ) && $u1_has_request > 0 );
	}

	/**
	 * @group groups_uninvite_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_bp_groups_uninvite_user_remove_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array(
			'status' => 'private',
		) );

		$now = time();
		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );

		// The invitation should be removed.
		self::invite_user_to_group( $u2, $g, $u1 );
		groups_uninvite_user( $u2, $g );
		$u2_has_invite = groups_check_user_has_invite( $u2, $g );
		$this->assertEquals( 0, $u2_has_invite );
	}

	/**
	 * @group groups_join_group
	 * @group group_membership
	 */
	public function test_groups_join_group_basic_join() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create();

		groups_join_group( $g, $u1 );
		$membership_id = groups_is_user_member( $u1, $g );
		$this->assertTrue( is_numeric( $membership_id ) && $membership_id > 0 );
	}

	/**
	 * @group groups_join_group
	 * @group group_membership
	 */
	public function test_groups_join_group_basic_join_use_current_user() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		groups_join_group( $g );
		$membership_id = groups_is_user_member( $u1, $g );
		$this->assertTrue( is_numeric( $membership_id ) && $membership_id > 0 );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_join_group
	 * @group group_membership
	 */
	public function test_groups_join_group_already_member() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create();
		$this->add_user_to_group( $u1, $g );

		$this->assertTrue( groups_join_group( $g, $u1 ) );
	}

	/**
	 * @group groups_join_group
	 * @group group_membership
	 */
	public function test_groups_join_group_cleanup_invites() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create();
		$this->add_user_to_group( $u1, $g );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );

		self::invite_user_to_group( $u2, $g, $u1 );

		groups_join_group( $g, $u2 );
		// Upon joining the group, outstanding invitations should be cleaned up.
		$this->assertEquals( null, groups_check_user_has_invite( $u2, $g, 'any' ) );
	}

	/**
	 * @group groups_join_group
	 * @group group_membership
	 */
	public function test_groups_join_group_cleanup_requests() {
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create();
		self::create_group_membership_request( $u1, $g );

		groups_join_group( $g, $u1 );
		// Upon joining the group, outstanding requests should be cleaned up.
		$this->assertEquals( null, groups_check_for_membership_request( $u1, $g ) );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_basic_leave_self_initiated() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u2 );
		groups_leave_group( $g, $u2 );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before - 1, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_basic_leave_use_current_user() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u2 );
		groups_leave_group( $g );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before - 1, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_basic_leave_group_admin_initiated() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u1 );
		groups_leave_group( $g, $u2 );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before - 1, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_basic_leave_site_admin_initiated() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$u1_siteadmin = new WP_User( $u1 );
		$u1_siteadmin->add_role( 'administrator' );
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u1 );
		groups_leave_group( $g, $u2 );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before - 1, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_single_admin_prevent_leave() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u1 );
		groups_leave_group( $g, $u1 );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_leave_group
	 * @group group_membership
	 */
	public function test_groups_leave_group_multiple_admins_allow_leave() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$u2 = $this->factory->user->create();
		$this->add_user_to_group( $u2, $g );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'admin' );

		$before = groups_get_total_member_count( $g );
		$this->set_current_user( $u1 );
		groups_leave_group( $g, $u1 );
		$after = groups_get_total_member_count( $g );

		$this->assertEquals( $before - 1, $after );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_get_invites_for_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_get_invites_for_user() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g3 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );
		self::invite_user_to_group( $u2, $g3, $u1 );

		$groups = groups_get_invites_for_user( $u2 );

		$this->assertEqualSets( array( $g1, $g2, $g3 ), wp_list_pluck( $groups['groups'], 'id' ) );
	}

	/**
	 * @group groups_get_invites_for_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_get_invites_for_user_infer_user() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g3 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );
		self::invite_user_to_group( $u2, $g3, $u1 );

		$this->set_current_user( $u2 );
		$groups = groups_get_invites_for_user();
		$this->assertEqualSets( array( $g1, $g2, $g3 ), wp_list_pluck( $groups['groups'], 'id' ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group groups_get_invites_for_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_get_invites_for_user_with_exclude() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g3 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );
		self::invite_user_to_group( $u2, $g3, $u1 );

		$groups = groups_get_invites_for_user( $u2, $limit = false, $page = false, $exclude = array( $g2 ) );
		$this->assertEqualSets( array( $g1, $g3 ), wp_list_pluck( $groups['groups'], 'id' ) );
	}

	/**
	 * @group groups_get_invite_count_for_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_get_invite_count_for_user() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g3 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );
		self::invite_user_to_group( $u2, $g3, $u1 );

		$this->assertEquals( 3, groups_get_invite_count_for_user( $u2 ) );
	}

	/**
	 * @group groups_get_invite_count_for_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_get_invite_count_for_user_ignore_drafts() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		// Create draft invitation.
		groups_invite_user( $args );

		// groups_get_invite_count_for_user should ignore draft invitations.
		$this->assertEquals( 0, groups_get_invite_count_for_user( $u2 ) );
	}

	/**
	 * @group groups_invite_user
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_invite_user() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Check that the draft invitation has been created.
		$draft = groups_check_user_has_invite( $u2, $g1, $type = 'all' );
		$this->assertTrue( is_numeric( $draft ) && $draft > 0 );
	}

	/**
	 * @group groups_send_invites
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_send_invites() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Send the invitation
		groups_send_invites( $u1, $g1 );
		// Check that the invitation has been sent.
		$sent = groups_check_user_has_invite( $u2, $g1, $type = 'sent' );
		$this->assertTrue( is_numeric( $sent ) && $sent > 0 );
	}

	/**
	 * @group groups_accept_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_accept_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Send the invitation
		groups_send_invites( $u1, $g1 );

		// Accept the invitation
		groups_accept_invite( $u2, $g1 );

		// Check that the user is a member of the group.
		$member = groups_is_user_member( $u2, $g1 );
		$this->assertTrue( is_numeric( $member ) && $member > 0 );
		// Check that the invite has been removed.
		$invite = groups_check_user_has_invite( $u2, $g1, $type = 'all' );
		$this->assertTrue( is_null( $invite ) );
	}

	/**
	 * @group groups_accept_invite
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_accept_invite_removes_membership_requests() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Create membership request
		groups_send_membership_request( $u2, $g1 );
		$request = groups_check_for_membership_request( $u2, $g1 );
		$this->assertTrue( is_numeric( $request ) && $request > 0 );

		// Send the invitation
		groups_send_invites( $u1, $g1 );

		// Accept the invitation
		groups_accept_invite( $u2, $g1 );

		// Check that the membership request has been removed.
		$this->assertTrue( 0 == groups_check_for_membership_request( $u2, $g1 ) );
	}

	/**
	 * @group groups_send_invites
	 * @group group_invitations
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_sent_invite_plus_request_equals_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Send the invitation
		groups_send_invites( $u1, $g1 );

		// Create membership request
		groups_send_membership_request( $u2, $g1 );

		// User should now be a group member
		$member = groups_is_user_member( $u2, $g1 );
		$this->assertTrue( is_numeric( $member ) && $member > 0 );
	}

	/**
	 * @group groups_delete_all_group_invites
	 * @group group_invitations
	 * @group group_membership
	 */
	public function test_groups_delete_all_group_invites() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u3, $g1, $u1 );

		groups_delete_all_group_invites( $g1 );

		// Get group invitations of any type, from any user in the group.
		$args = array(
			'group_id'     => $g1,
			'is_confirmed' => 0,
			'invite_sent'  => null,
			'inviter_id'   => 'any',
		);
		$invitees = new BP_Group_Member_Query( $args );

		$this->assertTrue( empty( $invitees->results ) );
	}

	/**
	 * @group groups_send_membership_request
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_send_membership_request() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();

		// Create membership request
		groups_send_membership_request( $u1, $g1 );

		$request = groups_check_for_membership_request( $u1, $g1 );
		$this->assertTrue( is_numeric( $request ) && $request > 0 );
	}

	/**
	 * @group groups_accept_membership_request
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_accept_membership_request_by_membership_id() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();

		// Create membership request
		groups_send_membership_request( $u1, $g1 );

		// Get group invitations of any type, from any user in the group.
		$member = new BP_Groups_Member( $u1, $g1 );

		groups_accept_membership_request( $member->id );

		// User should now be a group member.
		$member = groups_is_user_member( $u1, $g1 );

		$this->assertTrue( is_numeric( $member ) && $member > 0 );
	}

	/**
	 * @group groups_accept_membership_request
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_accept_membership_request_by_user_id_group_id() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();

		// Create membership request
		groups_send_membership_request( $u1, $g1 );

		groups_accept_membership_request( null, $u1, $g1 );

		// User should now be a group member
		$member = groups_is_user_member( $u1, $g1 );
		$this->assertTrue( is_numeric( $member ) && $member > 0 );
	}

	/**
	 * @group groups_send_invites
	 * @group group_invitations
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_membership_request_plus_invite_equals_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// Create membership request
		groups_send_membership_request( $u2, $g1 );

		// Create draft invitation
		$args = array(
			'user_id'       => $u2,
			'group_id'      => $g1,
			'inviter_id'    => $u1,
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0
		);
		groups_invite_user( $args );

		// Send the invitation
		groups_send_invites( $u1, $g1 );

		// User should now be a group member
		$member = groups_is_user_member( $u2, $g1 );
		$this->assertTrue( is_numeric( $member ) && $member > 0 );
	}

	/**
	 * @group groups_accept_all_pending_membership_requests
	 * @group group_membership_requests
	 * @group group_membership
	 */
	public function test_groups_accept_all_pending_membership_requests() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$g1 = $this->factory->group->create();

		// Create membership request
		groups_send_membership_request( $u1, $g1 );
		groups_send_membership_request( $u2, $g1 );
		groups_send_membership_request( $u3, $g1 );

		groups_accept_all_pending_membership_requests( $g1 );

		// All users should now be group members.
		$members = new BP_Group_Member_Query( array( 'group_id' => $g1 ) );
		$this->assertEqualSets( array( $u1, $u2, $u3 ), $members->user_ids );
	}
}