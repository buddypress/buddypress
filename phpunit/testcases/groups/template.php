<?php
/**
 * @group groups
 * @group template
 */
class BP_Tests_Groups_Template extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Integration test to make sure meta_query is getting passed through
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_with_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		global $groups_template;
		bp_has_groups( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$ids = wp_list_pluck( $groups_template->groups, 'id' );
		$this->assertEquals( $ids, array( $g1, ) );
	}

	/**
	 * Integration test to make sure order and orderby are interpreted when
	 * no 'type' value has been passed
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_with_order_orderby_with_null_type() {
		$g1 = $this->factory->group->create( array(
			'name' => 'AAAAA',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000000 ),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'BBBBB',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 1000000 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 10000 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'CCCCC',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 10000 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 10 ),
		) );

		global $groups_template;
		bp_has_groups( array(
			'order' => 'ASC',
			'orderby' => 'name',
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEquals( array( $g1, $g2, $g3, ), $ids );
	}

	/**
	 * Integration test to make sure 'order' is set to 'DESC' and 'orderby'
	 * to 'last_activity' when no type or order/orderby params are passed.
	 * This ensures backpat with the old system, where 'active' was the
	 * default type param, and there were no order/orderby params.
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_defaults_to_DESC_last_activity_for_default_type_active_backpat() {
		$g1 = $this->factory->group->create( array(
			'name' => 'AAAAA',
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'BBBBB',
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000000 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'CCCCC',
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 10000 ),
		) );

		global $groups_template;
		bp_has_groups();

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEquals( array( $g1, $g3, $g2, ), $ids );
	}

	/**
	 * Test using the 'slug' parameter in bp_has_groups()
	 *
	 * Note: The 'slug' parameter currently also requires the 'type' to be set
	 * to 'single-group'.
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_single_group_with_slug() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Test Group',
			'slug' => 'test-group',
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
		) );

		global $groups_template;
		bp_has_groups( array(
			'type' => 'single-group',
			'slug' => 'test-group',
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEquals( array( $g1 ), $ids );

		$this->assertEquals( 1, $groups_template->group_count );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_vanilla() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$this->add_user_to_group( $u1, $g );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_admins_mods' => false,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, ), $ids );
	}

	/**
	 * Switching from BP_Groups_Member to BP_Group_Member_Query meant a
	 * change in the format of the values returned from the query. For
	 * backward compatibility, we translate some of the return values
	 * of BP_Group_Member_Query to the older format. This test makes sure
	 * that the translation happens properly.
	 *
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_backpat_retval_format() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$g = $this->factory->group->create( array( 'creator_id' => $u2 ) );

		$date_modified = gmdate( 'Y-m-d H:i:s', time() - 100 );

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => $date_modified ) );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
		) );

		$u1_object = new WP_User( $u1 );

		$expected = new stdClass;
		$expected->user_id = $u1;
		$expected->date_modified = $date_modified;
		$expected->is_banned = 0;
		$expected->user_login = $u1_object->user_login;
		$expected->user_nicename = $u1_object->user_nicename;
		$expected->user_email = $u1_object->user_email;
		$expected->display_name = $u1_object->display_name;

		// In order to use assertEquals, we need to discard the
		// irrelevant properties of the found object. Hack alert
		$found = new stdClass;
		foreach ( array( 'user_id', 'date_modified', 'is_banned', 'user_login', 'user_nicename', 'user_email', 'display_name' ) as $key ) {
			if ( isset( $members_template->members[0]->{$key} ) ) {
				$found->{$key} = $members_template->members[0]->{$key};
			}
		}

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_per_page() {
		$g = $this->factory->group->create();

		$users = array();
		for ( $i = 1; $i <= 10; $i++ ) {
			$users[ $i ] = $this->create_user();
		}

		$expected = array();
		$now = time();
		for ( $i = 3; $i <= 10; $i++ ) {
			$this->add_user_to_group( $users[ $i ], $g, array(
				'date_modified' => $now - 60 * $i,
			) );
			$expected[] = $users[ $i ];
		}

		// hack it down to 5 (per page arg below)
		$expected = array_slice( $expected, 0, 5 );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'per_page' => 5,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( $expected, $ids );
	}

	/**
	 * Note: 'max' is a weird parameter. It just changes the member_count
	 * in the global - not the sql query at all. I'm testing what it
	 * appears to be designed to do, not what it feels like it ought to do
	 * if it made any sense. Programming is fun, QED.
	 *
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_max() {
		$g = $this->factory->group->create();

		$users = array();
		for ( $i = 1; $i <= 10; $i++ ) {
			$users[ $i ] = $this->create_user();
		}

		$expected = array();
		for ( $i = 3; $i <= 10; $i++ ) {
			$this->add_user_to_group( $users[ $i ], $g );
			$expected[] = $users[ $i ];
		}

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'max' => 5,
		) );

		$this->assertEquals( 5, $members_template->member_count );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$this->add_user_to_group( $u1, $g );
		$this->add_user_to_group( $u2, $g );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude' => $u1,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u2 ), $ids );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude_admins_mods_1() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		$this->add_user_to_group( $u1, $g );
		$this->add_user_to_group( $u2, $g );
		$this->add_user_to_group( $u3, $g );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_admins_mods' => 1,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u3 ), $ids );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude_admins_mods_0() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$now = time();
		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => $now - 60,
		) );
		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => $now - 60*60,
		) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_admins_mods' => 0,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, $u2, $u3 ), $ids );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude_banned_1() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$this->add_user_to_group( $u1, $g );
		$this->add_user_to_group( $u2, $g );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->ban();

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_banned' => 1,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u2, ), $ids );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude_banned_0() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*24 ),
		) );
		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*12 ),
		) );

		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->ban();

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_banned' => 0,
			'exclude_admins_mods' => false,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, $u3, $u2 ), $ids );
	}

	/**
	 * Default sort order should be the joined date
	 *
	 * @tickett BP5106
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_default_order() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 600 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 6000 ),
		) );

		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*24 ),
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*12 ),
		) );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'exclude_banned' => 0,
			'exclude_admins_mods' => false,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, $u3, $u2, ), $ids );
	}

	/**
	 * @group bp_group_has_invites
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_group_has_invites_template_structure() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );
		$u2 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 600 ),
		) );
		$u3 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 6000 ),
		) );
		$u4 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60000 ),
		) );


		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$m2 = $this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*24 ),
			'is_confirmed' => 0,
			'inviter_id' => $u1,
			'invite_sent' => true,
		) );

		$m3 = $this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*12 ),
			'is_confirmed' => 0,
			'inviter_id' => $u1,
			'invite_sent' => true,
		) );

		$m4 = $this->add_user_to_group( $u4, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', time() - 60*60*36 ),
			'is_confirmed' => 1,
			'inviter_id' => $u1,
			'invite_sent' => true,
		) );

		// Populate the global
		bp_group_has_invites( array(
			'group_id' => $g,
			'user_id' => $u1,
		) );

		global $invites_template;

		$found_users = array(
			0 => $u2,
			1 => $u3,
		);

		// Invites array
		$this->assertEquals( $found_users, $invites_template->invites );

		// Make sure user is set when loop starts
		$counter = 0;
		while ( bp_group_invites() ) : bp_group_the_invite();
			$this->assertEquals( $g, $invites_template->invite->group_id );

			$this_user = new BP_Core_User( $found_users[ $counter ] );
			foreach ( get_object_vars( $this_user ) as $k => $v ) {
				// Doesn't matter if the backpat provides *more*
				// details than the old method, so we skip cases
				// where the BP_Core_User value is empty
				if ( empty( $v ) ) {
					continue;
				}

				$this->assertEquals( $v, $invites_template->invite->user->{$k} );
			}
			$counter++;
		endwhile;
	}

	/**
	 * @group bp_group_has_invites
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_group_has_invites_pagination() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );

		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$users = array();
		for ( $i = 1; $i < 15; $i++ ) {
			$users[ $i ] = $this->create_user( array(
				'last_activity' => gmdate( 'Y-m-d H:i:s', time() - $i ),
			) );

			$this->add_user_to_group( $users[ $i ], $g, array(
				'date_modified' => gmdate( 'Y-m-d H:i:s', time() - $i ),
				'is_confirmed' => 0,
				'inviter_id' => $u1,
				'invite_sent' => true,
			) );
		}

		// Populate the global
		bp_group_has_invites( array(
			'group_id' => $g,
			'user_id' => $u1,
			'page' => 2,
			'per_page' => 5,
		) );

		global $invites_template;

		$this->assertEquals( array( $users[ 9 ], $users[ 8 ], $users[ 7 ], $users[ 6 ], $users[ 5 ], ), $invites_template->invites );
	}

	/**
	 * Checks for proper queried items
	 *
	 * @group bp_group_has_membership_requests
	 * @group BP_Group_Membership_Requests_Template
	 */
	public function test_bp_group_has_membership_requests_results() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );

		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$users = array();
		$memberships = array();
		for ( $i = 1; $i < 15; $i++ ) {
			$users[ $i ] = $this->create_user( array(
				'last_activity' => gmdate( 'Y-m-d H:i:s', time() - ( 100 - $i ) ),
			) );

			$memberships[ $i ] = $this->add_user_to_group( $users[ $i ], $g, array(
				// this date_modified ensures that order will match
				// id order. necessary due to a quirk in the legacy
				// implementation
				'date_modified' => gmdate( 'Y-m-d H:i:s', time() - ( 100 - $i ) ),
				'is_confirmed' => 0,
				'inviter_id' => 0,
				'invite_sent' => false,
			) );
		}

		// Fake the current group
		global $groups_template;

		if ( ! isset( $groups_template ) ) {
			$groups_template = new stdClass;
		}

		if ( ! isset( $groups_template->group ) ) {
			$groups_template->group = new stdClass;
		}

		$groups_template->group->id = $g;

		// Populate the global
		bp_group_has_membership_requests( array(
			'group_id' => $g,
		) );

		global $requests_template;

		$expected_user_ids = array();
		$expected_mem_ids = array();
		for ( $j = 1; $j <= 10; $j++ ) {
			$expected_user_ids[] = (string) $users[ $j ];
			$expected_mem_ids[] = (string) $memberships[ $j ];
		}

		$this->assertEquals( $expected_user_ids, wp_list_pluck( $requests_template->requests, 'user_id' ) );
		$this->assertEquals( $expected_mem_ids, wp_list_pluck( $requests_template->requests, 'id' ) );
	}

	/**
	 * Checks that the requests_template object is properly formatted
	 *
	 * @group bp_group_has_membership_requests
	 * @group BP_Group_Membership_Requests_Template
	 */
	public function test_bp_group_has_membership_requests_format() {
		$u1 = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );

		$g = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );

		$time = time();

		$user = $this->create_user( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $time ),
		) );

		$membership = $this->add_user_to_group( $user, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time ),
			'is_confirmed' => 0,
			'inviter_id' => 0,
			'invite_sent' => false,
		) );

		// Fake the current group
		global $groups_template;

		if ( ! isset( $groups_template ) ) {
			$groups_template = new stdClass;
		}

		if ( ! isset( $groups_template->group ) ) {
			$groups_template->group = new stdClass;
		}

		$groups_template->group->id = $g;

		// Populate the global
		bp_group_has_membership_requests( array(
			'group_id' => $g,
			'per_page' => 1,
			'max' => 1,
		) );

		global $requests_template;

		$expected = new stdClass;
		$expected->id = $membership;
		$expected->group_id = $g;
		$expected->user_id = $user;
		$expected->inviter_id = '0';
		$expected->is_admin = '0';
		$expected->is_mod = '0';
		$expected->user_title = '';
		$expected->date_modified = gmdate( 'Y-m-d H:i:s', $time );
		$expected->comments = '';
		$expected->is_confirmed = '0';
		$expected->is_banned = '0';
		$expected->invite_sent = '0';

		// Check each expected value. If there are more in the results,
		// that's OK
		foreach ( get_object_vars( $expected ) as $k => $v ) {
			$this->assertEquals( $v, $requests_template->requests[0]->{$k} );
		}
	}
}
