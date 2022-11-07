<?php

/**
 * @group groups
 * @group functions
 */
class BP_Tests_Groups_Functions extends BP_UnitTestCase {
	static public $group_ids;
	static public $user_ids;
	protected $did_group_member_count = 0;

	static public function wpSetUpBeforeClass( $factory ) {
		self::$user_ids  = $factory->user->create_many( 3 );
		self::$group_ids = $factory->group->create_many( 2, array(
			'creator_id' => self::$user_ids[2],
			'status'     => 'private'
		) );
	}

	static public function wpTearDownAfterClass() {
		array_map( 'groups_delete_group', self::$group_ids );
		array_map( array( __CLASS__, 'delete_user' ), self::$user_ids );
	}

	public function test_creating_new_group_as_authenticated_user() {
		$u = self::factory()->user->create();
		wp_set_current_user( $u );

		self::factory()->group->create();

		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @group total_group_count
	 * @group groups_join_group
	 */
	public function test_total_group_count_groups_join_group() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g, $u2 );
		$this->assertEquals( 1, bp_get_user_meta( $u2, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_leave_group
	 */
	public function test_total_group_count_groups_leave_group() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$g2 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$g2 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$g2 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private', 'creator_id' => $u2 ) );

		groups_invite_user( array(
			'user_id' => $u1,
			'group_id' => $g,
			'inviter_id' => $u2,
			'send_invite' => 1,
		) );

		groups_accept_invite( $u1, $g );

		$this->assertEquals( 1, bp_get_user_meta( $u1, 'total_group_count', true ) );
	}

	/**
	 * @group total_group_count
	 * @group groups_accept_membership_request
	 */
	public function test_total_group_count_groups_accept_membership_request() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$current_user = bp_loggedin_user_id();
		$this->set_current_user( $u2 );

		$g = self::factory()->group->create( array( 'status' => 'private' ) );
		groups_send_membership_request( array(
			'user_id'       => $u1,
			'group_id'      => $g,
		) );

		groups_accept_membership_request( 0, $u1, $g );

		$this->assertEquals( 1, bp_get_user_meta( $u1, 'total_group_count', true ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group total_group_count
	 * @group groups_remove_member
	 */
	public function test_total_group_count_groups_remove_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$g2 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g, $u2 );
		$this->assertEquals( 2, groups_get_total_member_count( $g ) );
	}

	/**
	 * @group total_member_count
	 */
	public function test_total_member_count_with_invalid_group() {
		$this->assertFalse( groups_get_total_member_count( 'invalid-group' ) );
		$this->assertFalse( groups_get_total_member_count( '' ) );
		$this->assertFalse( groups_get_total_member_count( 123456789 ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_leave_group
	 */
	public function test_total_member_count_groups_leave_group() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g1, $u2 );

		$this->assertEquals( 2, groups_get_total_member_count( $g1 ) );

		groups_leave_group( $g1, $u2 );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_ban_member
	 */
	public function test_total_member_count_groups_ban_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		$this->assertEquals( 2, groups_get_total_member_count( $g1 ) );

		groups_ban_member( $u2, $g1 );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_unban_member
	 */
	public function test_total_member_count_groups_unban_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_ban_member( $u2, $g1 );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ) );

		groups_unban_member( $u2, $g1 );

		$this->assertEquals( 2, groups_get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_accept_invite
	 */
	public function test_total_member_count_groups_accept_invite() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private', 'creator_id' => $u1 ) );
		groups_invite_user( array(
			'user_id'     => $u2,
			'group_id'    => $g,
			'inviter_id'  => $u1,
			'send_invite' => 1,
		) );

		$this->assertEquals( 1, groups_get_total_member_count( $g ) );

		groups_accept_invite( $u2, $g );

		$this->assertEquals( 2, groups_get_total_member_count( $g ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_accept_membership_request
	 */
	public function test_total_member_count_groups_accept_membership_request() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private', 'creator_id' => $u1 ) );

		groups_send_membership_request( array(
			'user_id'       => $u2,
			'group_id'      => $g,
		) );
		groups_accept_membership_request( 0, $u2, $g );

		$this->assertEquals( 2, groups_get_total_member_count( $g ) );
	}

	/**
	 * @group total_member_count
	 * @group groups_remove_member
	 */
	public function test_total_member_count_groups_remove_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $g1, $u2 );

		// Fool the admin check
		$this->set_current_user( $u1 );
		buddypress()->is_item_admin = true;

		groups_remove_member( $u2, $g1 );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ));
	}

	/**
	 * @group total_member_count
	 * @group groups_remove_member
	 */
	public function test_total_member_count_groups_delete_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g1, $u2 );
		groups_join_group( $g1, $u3 );

		$this->assertEquals( 3, groups_get_total_member_count( $g1 ) );
		$this->assertEquals( 3, BP_Groups_Group::get_total_member_count( $g1 ) );

		add_filter( 'bp_remove_user_data_on_delete_user_hook', '__return_true' );

		// Delete user.
		wp_delete_user( $u2 );

		remove_filter( 'bp_remove_user_data_on_delete_user_hook', '__return_true' );

		$this->assertEquals( 2, groups_get_total_member_count( $g1 ) );
		$this->assertEquals( 2, BP_Groups_Group::get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @ticket BP8688
	 */
	public function test_total_member_count_groups_inactive_user() {
		$u1 = self::factory()->user->create();
		$u2 = wp_insert_user( array(
			'user_pass'  => 'foobar',
			'user_login' => 'foobar',
			'user_email' => 'foobar@buddypress.org',
		) );

		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g1, $u2 );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @ticket BP7614
	 */
	public function test_total_member_count_groups_inactive_user_from_admin() {
		$current_user = get_current_user_id();
		$u1           = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$u2           = wp_insert_user( array(
			'user_pass'  => 'barfoo',
			'user_login' => 'barfoo',
			'user_email' => 'barfoo@buddypress.org',
		) );

		$this->set_current_user( $u1 );
		$g1 = self::factory()->group->create();

		groups_join_group( $g1, $u2 );

		$this->assertEquals( 2, groups_get_total_member_count( $g1 ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group total_member_count
	 * @ticket BP8688
	 */
	public function test_total_member_count_groups_spammed_user() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		groups_join_group( $g1, $u2 );
		bp_core_process_spammer_status( $u2, 'spam' );

		$this->assertEquals( 1, groups_get_total_member_count( $g1 ) );
	}

	/**
	 * @group total_member_count
	 * @ticket BP8688
	 */
	public function test_total_member_count_groups_deferred() {
		$u1 = self::factory()->user->create();
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$members = array();
		$this->did_group_member_count = 0;

		add_filter( 'bp_groups_total_member_count', array( $this, 'filter_bp_groups_total_member_count' ) );

		bp_groups_defer_group_members_count( true );
		for ( $i = 1; $i < 6; $i++ ) {
			$members[ $i ] = self::factory()->user->create();
			groups_join_group( $g1, $members[ $i ] );
		}
		bp_groups_defer_group_members_count( false, $g1 );

		remove_filter( 'bp_groups_total_member_count', array( $this, 'filter_bp_groups_total_member_count' ) );

		$this->assertTrue( 1 === $this->did_group_member_count );
		$this->assertEquals( count( $members ) + 1, groups_get_total_member_count( $g1 ) );
	}

	public function filter_bp_groups_total_member_count( $count ) {
		$this->did_group_member_count += 1;
		return $count;
	}

	/**
	 * @group total_member_count
	 * @group groups_create_group
	 */
	public function test_total_member_count_groups_create_group() {
		$u1 = self::factory()->user->create();
		$g = groups_create_group( array(
			'creator_id' => $u1,
			'name' => 'Boone Is Handsome',
			'description' => 'Yes',
			'slug' => 'boone-is-handsome',
			'status' => 'public',
			'enable_forum' => 0,
			'date_created' => bp_core_current_time(),
		) );

		$this->assertEquals( 1, groups_get_total_member_count( $g ) );
	}

	/**
	 * @group groups_create_group
	 */
	public function test_groups_create_group_dont_delete_description_for_existing_group_when_no_description_is_passed() {
		$g = self::factory()->group->create();

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
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
		$value = "This string is totally slashin\'!";
		groups_update_groupmeta( $g, 'foo', $value );

		$this->assertSame( stripslashes( $value ), groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_new() {
		$g = self::factory()->group->create();
		$this->assertSame( '', groups_get_groupmeta( $g, 'foo' ), '"foo" meta should be empty for this group.' );
		$this->assertNotEmpty( groups_update_groupmeta( $g, 'foo', 'bar' ) );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_existing() {
		$g = self::factory()->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );
		$this->assertSame( 'bar', groups_get_groupmeta( $g, 'foo' ), '"foo" meta should be set already for this group.' );
		$this->assertTrue( groups_update_groupmeta( $g, 'foo', 'baz' ) );
		$this->assertSame( 'baz', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_update_groupmeta_existing_same_value() {
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
		groups_update_groupmeta( $g, 'foo', 'bar' );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( '', groups_get_groupmeta( $g, $krazy_key ) );
	}

	/**
	 * @group groupmeta
	 */
	public function test_groups_get_groupmeta_all_metas() {
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();

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
		$g = self::factory()->group->create();
		$this->assertSame( '', groups_get_groupmeta( $g, 'foo' ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_get_groupmeta
	 */
	public function test_bp_activity_get_meta_single_true() {
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
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
		$g = self::factory()->group->create();
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

		$g = self::factory()->group->create();
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
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
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
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
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
		$g = self::factory()->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		$this->assertFalse( groups_add_groupmeta( $g, 'foo', 'baz', true ) );
	}

	/**
	 * @group groupmeta
	 * @group groups_add_groupmeta
	 */
	public function test_groups_add_groupmeta_existing_not_unique() {
		$g = self::factory()->group->create();
		groups_add_groupmeta( $g, 'foo', 'bar' );
		$this->assertNotEmpty( groups_add_groupmeta( $g, 'foo', 'baz' ) );
	}

	/**
	 * @group counts
	 */
	public function test_get_invite_count_for_user() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u1, 'status' => 'private' ) );

		// create invitation
		groups_invite_user( array(
			'user_id'    => $u2,
			'group_id'   => $g,
			'inviter_id' => $u1,
			'send_invite' => 1
		) );

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
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
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
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
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
		$g1 = self::factory()->group->create();
		$group = groups_get_group( $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_numeric() {
		$g1 = self::factory()->group->create();
		$group = groups_get_group( (string) $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_array() {
		$g1 = self::factory()->group->create();
		$group = groups_get_group( array( 'group_id' => $g1 ) );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @group groups_get_group
 	 * @ticket BP7302
	 */
	public function test_groups_get_group_accept_query_string() {
		$g1 = self::factory()->group->create();
		$group = groups_get_group( 'group_id=' . $g1 );

		$this->assertEquals( $g1, $group->id );
	}

	/**
	 * @expectedDeprecated groups_edit_base_group_details
	 * @group groups_edit_base_group_details
	 */
	public function test_groups_edit_base_group_details_test_backcompat_arguments() {
		$g1 = self::factory()->group->create();
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
		$g1 = self::factory()->group->create();
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
		$g1 = self::factory()->group->create( array( 'slug' => $slug ) );
		$g2 = self::factory()->group->create( array( 'slug' => 'loom' ) );

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
		$g1 = self::factory()->group->create( array( 'slug' => $slug ) );

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
		$g1 = self::factory()->group->create( array( 'slug' => $slug ) );

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
		$g1 = self::factory()->group->create( array( 'slug' => $slug ) );
		$g2 = self::factory()->group->create( array( 'slug' => 'loom' ) );

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

	/**
	 * @ticket BP7820
	 * @ticket BP7698
	 */
	public function test_bp_groups_memberships_personal_data_exporter() {
		groups_join_group( self::$group_ids[0], self::$user_ids[0] );

		$test_user = new WP_User( self::$user_ids[0] );

		$actual = bp_groups_memberships_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );
		$this->assertCount( 1, $actual['data'] );
		$this->assertSame( 'bp-group-membership-' . self::$group_ids[0], $actual['data'][0]['item_id'] );
	}

	/**
	 * @ticket BP7820
	 * @ticket BP7698
	 */
	public function test_bp_groups_pending_requests_personal_data_exporter() {
		groups_send_membership_request( array(
			'user_id'       => self::$user_ids[0],
			'group_id'      => self::$group_ids[0],
		) );

		$test_user = new WP_User( self::$user_ids[0] );

		$actual = bp_groups_pending_requests_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );
		$this->assertCount( 1, $actual['data'] );
		$this->assertSame( 'bp-group-pending-request-' . self::$group_ids[0], $actual['data'][0]['item_id'] );
	}

	/**
	 * @ticket BP7820
	 * @ticket BP7698
	 */
	public function test_bp_groups_pending_sent_invitations_personal_data_exporter() {
		groups_invite_user( array(
			'user_id'     => self::$user_ids[0],
			'group_id'    => self::$group_ids[0],
			'inviter_id'  => self::$user_ids[2],
			'send_invite' => 1,
		) );

		$test_user = new WP_User( self::$user_ids[2] );

		$actual = bp_groups_pending_sent_invitations_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );
		$this->assertCount( 1, $actual['data'] );
		$this->assertSame( 'bp-group-pending-sent-invitation-' . self::$group_ids[0], $actual['data'][0]['item_id'] );
	}

	/**
	 * @ticket BP7820
	 * @ticket BP7698
	 */
	public function test_bp_groups_pending_received_invitations_personal_data_exporter() {
		groups_invite_user( array(
			'user_id'    => self::$user_ids[0],
			'group_id'   => self::$group_ids[0],
			'inviter_id' => self::$user_ids[2],
		) );

		$test_user = new WP_User( self::$user_ids[0] );

		$actual = bp_groups_pending_received_invitations_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );
		$this->assertCount( 1, $actual['data'] );
		$this->assertSame( 'bp-group-pending-received-invitation-' . self::$group_ids[0], $actual['data'][0]['item_id'] );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_groups_data_should_be_deleted_on_user_delete_non_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires non-multisite.' );
		}

		$u = self::factory()->user->create();

		groups_join_group( self::$group_ids[0], $u );

		$this->assertNotEmpty( groups_is_user_member( $u, self::$group_ids[0] ) );

		wp_delete_user( $u );

		$this->assertFalse( groups_is_user_member( $u, self::$group_ids[0] ) );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_groups_data_should_be_deleted_on_user_delete_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u = self::factory()->user->create();

		groups_join_group( self::$group_ids[0], $u );

		$this->assertNotEmpty( groups_is_user_member( $u, self::$group_ids[0] ) );

		wpmu_delete_user( $u );

		$this->assertFalse( groups_is_user_member( $u, self::$group_ids[0] ) );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_groups_data_should_not_be_deleted_on_wp_delete_user_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u = self::factory()->user->create();

		groups_join_group( self::$group_ids[0], $u );

		$this->assertNotEmpty( groups_is_user_member( $u, self::$group_ids[0] ) );

		wp_delete_user( $u );

		$this->assertNotEmpty( groups_is_user_member( $u, self::$group_ids[0] ) );
	}
}
