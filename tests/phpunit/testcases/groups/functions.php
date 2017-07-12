<?php

/**
 * @group groups
 * @group functions
 */
class BP_Tests_Groups_Functions extends BP_UnitTestCase {
	public function test_creating_new_group_as_authenticated_user() {
		$u = $this->factory->user->create();
		wp_set_current_user( $u );

		$this->factory->group->create();
	}

	/**
	 * @group total_group_count
	 * @group groups_join_group
	 */
	public function test_total_group_count_groups_join_group() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g, $u2 );
		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_leave_group
	 */
	public function test_total_group_count_groups_leave_group() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );
		groups_join_group( $g2, $u2 );

		// Set the current user so the leave group request goes through.
		$this->set_current_user( $u2 );
		groups_leave_group( $g1, $u2 );
		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_ban_member
	 */
	public function test_total_group_count_groups_ban_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );
		groups_join_group( $g2, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_ban_member( $u2, $g1 );

		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_unban_member
	 */
	public function test_total_group_count_groups_unban_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );
		groups_join_group( $g2, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_ban_member( $u2, $g1 );

		groups_unban_member( $u2, $g1 );

		$this->assertEquals( 2, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_accept_invite
	 */
	public function test_total_group_count_groups_accept_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create();
		groups_invite_user( array(
			'user_id' => $u1,
			'group_id' => $g,
			'inviter_id' => $u2,
		) );

		groups_accept_invite( $u2, $g );

		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_accept_membership_request
	 */
	public function test_total_group_count_groups_accept_membership_request() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$current_user = bp_loggedin_user_id();
		$this->set_current_user( $u2 );

		$g = $this->factory->group->create();
		groups_send_membership_request( $u1, $g );

		groups_accept_membership_request( 0, $u1, $g );

		$this->assertEquals( 1, bp_get_user_meta( $u1, 'total_group_count', true ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group total_group_count
	 * @group groups_remove_member
	 */
	public function test_total_group_count_groups_remove_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		$g2 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );
		groups_join_group( $g2, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_remove_member( $u2, $g1 );

		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_join_group
	 */
	public function test_total_member_count_groups_join_group() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g, $u2 );
		$this->assertEquals( 2, groups_get_groupmeta( $g, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_leave_group
	 */
	public function test_total_member_count_groups_leave_group() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u1 );

		groups_leave_group( $g1, $u1 );
		$this->assertEquals( 1, groups_get_groupmeta( $g1, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_ban_member
	 */
	public function test_total_member_count_groups_ban_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_ban_member( $u2, $g1 );

		$this->assertEquals( 1, groups_get_groupmeta( $g1, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_unban_member
	 */
	public function test_total_member_count_groups_unban_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_ban_member( $u2, $g1 );

		groups_unban_member( $u2, $g1 );

		$this->assertEquals( 2, groups_get_groupmeta( $g1, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_accept_invite
	 */
	public function test_total_member_count_groups_accept_invite() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_invite_user( array(
			'user_id' => $u1,
			'group_id' => $g,
			'inviter_id' => $u2,
		) );

		groups_accept_invite( $u2, $g );

		$this->assertEquals( 2, groups_get_groupmeta( $g, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_accept_membership_request
	 */
	public function test_total_member_count_groups_accept_membership_request() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		groups_send_membership_request( $u2, $g );
		groups_accept_membership_request( 0, $u2, $g );

		$this->assertEquals( 2, groups_get_groupmeta( $g, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_remove_member
	 */
	public function test_total_member_count_groups_remove_member() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g1 = $this->factory->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_remove_member( $u2, $g1 );

		$this->assertEquals( 1, groups_get_groupmeta( $g1, 'total_member_count' ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_create_group
	 */
	public function test_total_member_count_groups_create_group() {
		$u1 = $this->factory->user->create();
		$g = groups_create_group( array(
			'creator_id' => $u1,
			'name' => 'Boone Is Handsome',
			'description' => 'Yes',
			'slug' => 'boone-is-handsome',
			'status' => 'public',
			'enable_forum' => 0,
			'date_created' => bp_core_current_time(),
		) );

		$this->assertEquals( 1, groups_get_groupmeta( $g, 'total_member_count' ) );
	}

	/**
	 * @group groups_create_group
	 */
	public function test_groups_create_group_dont_delete_description_for_existing_group_when_no_description_is_passed() {
		$g = $this->factory->group->create();

		$group_before = groups_get_group( $g );
		groups_create_group( array(
			'group_id' => $g,
			'enable_forum' => 1,
		) );

		$group_after = groups_get_group( $g );
		$this->assertSame( $group_before->description, $group_after->description );
	}

	/**
	 * @group groupmeta
	 * @ticket BP5180
	 */
	public function test_groups_update_groupmeta_with_line_breaks() {
		$g = $this->factory->group->create();
		$meta_value = 'Foo!

Bar!';
		groups_update_groupmeta( $g, 'linebreak_test', $meta_value );

		$this->assertEquals( $meta_value, groups_get_groupmeta( $g, 'linebreak_test' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_non_numeric_id() {
		$this->assertFalse( groups_update_groupmeta( 'foo', 'bar', 'baz' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_stripslashes() {
		$g = $this->factory->group->create();
		$value = "This string is totally slashin\'!";
		groups_update_groupmeta( $g, 'foo', $value );

		$this->assertSame( stripslashes( $value ), groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_new() {
		$g = $this->factory->group->create();
		$this->assertSame( '', groups_get_groupmeta( $g, 'foo' ), '"foo" meta should be empty for this group.' );
		$this->assertNotEmpty( groups_update_groupmeta( $g, 'foo', 'bar' ) );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_existing() {
		$g = $this->factory->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ), '"foo" meta should be set already for this group.' );
		$this->assertTrue( groups_update_groupmeta( $g, 'foo', 'baz' ) );
		$this->assertSame( 'baz', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_existing_same_value() {
		$g = $this->factory->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ), '"foo" meta should be set already for this group.' );
		$this->assertFalse( groups_update_groupmeta( $g, 'foo', 'bar' ) );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_update_groupmeta
	 */
	public function test_groups_update_groupmeta_prev_value() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );

		// In earlier versions of WordPress, bp_activity_update_meta()
		// returns true even on failure. However, we know that in these
		// cases the update is failing as expected, so we skip this
		// assertion just to keep our tests passing
		// See https://core.trac.wordpress.org/ticket/24933
		if ( version_compare( $GLOBALS['wp_version'], '3.7', '>=' ) ) {
			$this->assertFalse( groups_update_groupmeta( $g, 'foo', 'bar2', 'baz' ) );
		}

		$this->assertTrue( groups_update_groupmeta( $g, 'foo', 'bar2', 'bar' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_get_groupmeta
	 * @ticket BP5399
	 */
	public function test_groups_get_groupmeta_with_illegal_key_characters() {
		$g = $this->factory->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( '', groups_get_groupmeta( $g, $krazy_key ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_get_groupmeta_all_metas() {
		$g = $this->factory->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );
		groups_update_groupmeta( $g, 'Boone', 'is cool' );

		// There's likely some other keys (total_member_count etc)
		// Just check to make sure both of ours are there
		$metas = groups_get_groupmeta( $g );
		$count = count( $metas );
		$found = array_slice( $metas, $count - 2 );

		$expected = array(
			'foo' => array(
				'bar',
			),
			'Boone' => array(
				'is cool',
			),
		);

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_get_groupmeta_all_metas_empty() {
		$g = $this->factory->group->create();

		// Get rid of any auto-created values
		global $wpdb;

		$bp = buddypress();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_groupmeta} WHERE group_id = %d", $g ) );
		wp_cache_delete( $g, 'group_meta' );

		$metas = groups_get_groupmeta( $g );
		$this->assertSame( array(), $metas );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_get_groupmeta_empty() {
		$g = $this->factory->group->create();
		$this->assertSame( '', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_get_groupmeta
	 */
	public function test_bp_activity_get_meta_single_true() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		groups_add_groupmeta( $g, 'foo', 'baz' );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ) ); // default is true
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo', true ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_get_groupmeta
	 */
	public function test_bp_activity_get_meta_single_false() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		groups_add_groupmeta( $g, 'foo', 'baz' );
		$this->assertSame( array( 'bar', 'baz' ), groups_get_groupmeta( $g, 'foo', false ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_get_groupmeta
	 * @group cache
	 */
	public function test_groups_get_groupmeta_cache_all_on_get() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		groups_add_groupmeta( $g, 'foo1', 'baz' );
		$this->assertFalse( wp_cache_get( $g, 'group_meta' ) );

		// A single query should prime the whole meta cache
		groups_get_groupmeta( $g, 'foo' );

		$c = wp_cache_get( $g, 'group_meta' );
		$this->assertNotEmpty( $c['foo1'] );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_delete_groupmeta_non_numeric_id() {
		$this->assertFalse( groups_delete_groupmeta( 'foo', 'bar' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_delete_groupmeta
	 * @ticket BP5399
	 */
	public function test_groups_delete_groupmeta_with_illegal_key_characters() {
		$g = $this->factory->group->create();
		$this->assertNotEmpty( groups_update_groupmeta( $g, 'foo', 'bar' ), 'Value of "foo" should be set at this point.' );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_delete_groupmeta
	 * @ticket BP6326
	 */
	public function test_groups_delete_groupmeta_with_no_meta_key_when_group_has_metadata() {
		global $wpdb;

		$g = $this->factory->group->create();
		$m = groups_get_groupmeta( $g );
		foreach ( $m as $mk => $mv ) {
			groups_delete_groupmeta( $g, $mk );
		}

		$found = groups_delete_groupmeta( $g );
		$this->assertTrue( $found );
	}

	/**
	 * @group groupmeta
	 * @group groups_delete_groupmeta
	 */
	public function test_groups_delete_groupmeta_with_delete_all_but_no_meta_key() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_add_groupmeta( $g1, 'foo', 'bar' );
		groups_add_groupmeta( $g1, 'foo1', 'bar1' );
		groups_add_groupmeta( $g2, 'foo', 'bar' );
		groups_add_groupmeta( $g2, 'foo1', 'bar1' );

		$this->assertTrue( groups_delete_groupmeta( $g1, '', '', true ) );
		$this->assertEmpty( groups_get_groupmeta( $g1 ) );
		$this->assertSame( 'bar', groups_get_groupmeta( $g2, 'foo' ) );
		$this->assertSame( 'bar1', groups_get_groupmeta( $g2, 'foo1' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_delete_groupmeta
	 */
	public function test_groups_delete_groupmeta_with_delete_all() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_add_groupmeta( $g1, 'foo', 'bar' );
		groups_add_groupmeta( $g1, 'foo1', 'bar1' );
		groups_add_groupmeta( $g2, 'foo', 'bar' );
		groups_add_groupmeta( $g2, 'foo1', 'bar1' );

		$this->assertTrue( groups_delete_groupmeta( $g1, 'foo', '', true ) );
		$this->assertSame( '', groups_get_groupmeta( $g1, 'foo' ) );
		$this->assertSame( '', groups_get_groupmeta( $g2, 'foo' ) );
		$this->assertSame( 'bar1', groups_get_groupmeta( $g1, 'foo1' ) );
		$this->assertSame( 'bar1', groups_get_groupmeta( $g2, 'foo1' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_add_groupmeta
	 */
	public function test_groups_add_groupmeta_no_meta_key() {
		$this->assertFalse( groups_add_groupmeta( 1, '', 'bar' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_add_groupmeta
	 */
	public function test_groups_add_groupmeta_empty_object_id() {
		$this->assertFalse( groups_add_groupmeta( 0, 'foo', 'bar' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_add_groupmeta
	 */
	public function test_groups_add_groupmeta_existing_unique() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		$this->assertFalse( groups_add_groupmeta( $g, 'foo', 'baz', true ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_add_groupmeta
	 */
	public function test_groups_add_groupmeta_existing_not_unique() {
		$g = $this->factory->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		$this->assertNotEmpty( groups_add_groupmeta( $g, 'foo', 'baz' ) );
	}

	/**
	 * @group counts
	 */
	public function test_get_invite_count_for_user() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// create invitation
		groups_invite_user( array(
			'user_id'    => $u2,
			'group_id'   => $g,
			'inviter_id' => $u1,
		) );

		// send the invite
		// this function is imperative to set the 'invite_sent' flag in the DB
		// why is this separated from groups_invite_user()?
		// @see groups_screen_group_invite()
		groups_send_invites( $u1, $g );

		// assert invite count
		$this->assertEquals( 1, groups_get_invite_count_for_user( $u2 ) );

		// accept the invite and reassert
		groups_accept_invite( $u2, $g );
		$this->assertEquals( 0, groups_get_invite_count_for_user( $u2 ) );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_update_orphaned_groups_on_group_delete_top_level() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );

		groups_delete_group( $g1 );

		$child = groups_get_group( array( 'group_id' => $g2 ) );
		$this->assertEquals( 0, $child->parent_id );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_update_orphaned_groups_on_group_delete_two_levels() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );

		groups_delete_group( $g2 );

		$child = groups_get_group( array( 'group_id' => $g3 ) );
		$this->assertEquals( $g1, $child->parent_id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_integer() {
		$g1 = $this->factory->group->create();
		$group = groups_get_group( $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_numeric() {
		$g1 = $this->factory->group->create();
		$group = groups_get_group( (string) $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_array() {
		$g1 = $this->factory->group->create();
		$group = groups_get_group( array( 'group_id' => $g1 ) );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_query_string() {
		$g1 = $this->factory->group->create();
		$group = groups_get_group( 'group_id=' . $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @expectedDeprecated groups_edit_base_group_details
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_test_backcompat_arguments() {
		$g1 = $this->factory->group->create();
		$name = 'Great Scott';
		$description = 'A must-see in time for the holidays!';
		groups_edit_base_group_details( $g1, $name, $description, false );

		$expected = array(
			'id'          => $g1,
			'name'        => $name,
			'description' => $description
		);
		$updated_group_object = groups_get_group( $g1 );
		$updated = array(
			'id'          => $updated_group_object->id,
			'name'        => $updated_group_object->name,
			'description' => $updated_group_object->description
		);

		$this->assertEqualSets( $expected, $updated );
	}

	/**
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_test_new_arguments() {
		$g1 = $this->factory->group->create();
		$name = 'Great Scott';
		$slug = 'what-about-it';
		$description = 'A must-see in time for the holidays!';
		groups_edit_base_group_details( array(
				'group_id'       => $g1,
				'name'           => $name,
				'slug'           => $slug,
				'description'    => $description,
				'notify_members' => false,
		) );

		$expected = array(
			'id'          => $g1,
			'slug'        => $slug,
			'name'        => $name,
			'description' => $description
		);
		$updated_group_object = groups_get_group( $g1 );
		$updated = array(
			'id'          => $updated_group_object->id,
			'slug'        => $updated_group_object->slug,
			'name'        => $updated_group_object->name,
			'description' => $updated_group_object->description
		);

		$this->assertEqualSets( $expected, $updated );
	}

	/**
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_avoid_slug_collisions() {
		$slug = 'circe';
		$g1 = $this->factory->group->create( array( 'slug' => $slug ) );
		$g2 = $this->factory->group->create( array( 'slug' => 'loom' ) );

		// Attempt to use a duplicate slug.
		groups_edit_base_group_details( array(
				'group_id'       => $g2,
				'slug'           => $slug,
		) );

		$updated_group_object = groups_get_group( $g2 );

		$this->assertNotEquals( $slug, $updated_group_object->slug );
	}

	/**
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_slug_no_change() {
		$slug = 'circe';
		$g1 = $this->factory->group->create( array( 'slug' => $slug ) );

		// Make sure the slug doesn't get incremented when there's no change.
		groups_edit_base_group_details( array(
				'group_id'       => $g1,
				'slug'           => $slug,
		) );

		$updated_group_object = groups_get_group( $g1 );

		$this->assertEquals( $slug, $updated_group_object->slug );
	}

	/**
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_slug_null_value() {
		$slug = 'circe';
		$g1 = $this->factory->group->create( array( 'slug' => $slug ) );

		// Make sure the slug doesn't get changed when null is passed.
		groups_edit_base_group_details( array(
				'group_id'       => $g1,
				'slug'           => null,
		) );

		$updated_group_object = groups_get_group( $g1 );

		$this->assertEquals( $slug, $updated_group_object->slug );
	}

	/**
	 * @group groups_get_id_by_previous_slug
	 */
	public function test_groups_get_id_by_previous_slug() {
		$slug = 'circe';
		$g1 = $this->factory->group->create( array( 'slug' => $slug ) );
		$g2 = $this->factory->group->create( array( 'slug' => 'loom' ) );

		groups_edit_base_group_details( array(
			'group_id'       => $g1,
			'slug'           => 'newslug',
		) );

		// Function should return the group ID as an integer.
		$this->assertSame( $g1, groups_get_id_by_previous_slug( $slug ) );
	}

	/**
	 * @group groups_get_id_by_previous_slug
	 */
	public function test_groups_get_id_by_previous_slug_null_no_results() {
		$this->assertNull( groups_get_id_by_previous_slug( 'woohoo' ) );
	}

}
