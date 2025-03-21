<?php
/**
 * @group groups
 * @group template
 */
#[AllowDynamicProperties]
class BP_Tests_Groups_Template extends BP_UnitTestCase {
	protected $groups_template = null;

	public function set_up() {
		parent::set_up();

		if ( isset( $GLOBALS['groups_template'] ) ) {
			$this->groups_template = $GLOBALS['groups_template'];
		}
	}

	public function tear_down() {
		if ( $this->groups_template ) {
			$GLOBALS['groups_template'] = $this->groups_template;
		}

		parent::tear_down();
	}

	/**
	 * Integration test to make sure meta_query is getting passed through
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_with_meta_query() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
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
		$now = time();
		$g1 = self::factory()->group->create( array(
			'name' => 'AAAAA',
			'date_created' => gmdate( 'Y-m-d H:i:s', $now - 100 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 1000000 ),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'BBBBB',
			'date_created' => gmdate( 'Y-m-d H:i:s', $now - 1000000 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 10000 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'CCCCC',
			'date_created' => gmdate( 'Y-m-d H:i:s', $now - 10000 ),
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 10 ),
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
		$now = time();
		$g1 = self::factory()->group->create( array(
			'name' => 'AAAAA',
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'BBBBB',
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 1000000 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'CCCCC',
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 10000 ),
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
		$g1 = self::factory()->group->create( array(
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
	 * Test using the 'status' parameter in bp_has_groups()
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_status() {
		$g1 = self::factory()->group->create( array(
			'status' => 'public',
		) );
		$g2 = self::factory()->group->create( array(
			'status' => 'private',
		) );
		$g3 = self::factory()->group->create( array(
			'status' => 'hidden',
		) );

		global $groups_template;
		bp_has_groups( array(
			'status' => 'private',
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g2 ), $ids );

		$this->assertEquals( 1, $groups_template->group_count );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => $g1,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEquals( array( $g2 ), $ids );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id_array() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => array( $g1, $g2 ),
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g2, $g3 ), $ids );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id_comma_separated() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => "{$g1},{$g2}",
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g2, $g3 ), $ids );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id_null() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => null,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g1, $g2, $g3, $g4 ), $ids );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id_top_level_groups() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => 0,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g1, $g4 ), $ids );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_bp_has_groups_parent_id_top_level_groups_using_false() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		global $groups_template;
		bp_has_groups( array(
			'parent_id' => false,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $groups_template->groups, 'id' ) );
		$this->assertEqualSets( array( $g1, $g4 ), $ids );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_vanilla() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g  = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u2 ) );

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
		$g = self::factory()->group->create();

		$users = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$users[ $i ] = self::factory()->user->create();
		}

		$expected = array();
		$now = time();
		for ( $i = 3; $i <= 5; $i++ ) {
			$this->add_user_to_group( $users[ $i ], $g, array(
				'date_modified' => date( 'Y-m-d H:i:s', $now - ( 60 * $i ) ),
			) );
			$expected[] = $users[ $i ];
		}

		// hack it down to 2 (per page arg below)
		$expected = array_slice( $expected, 0, 2 );

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'per_page' => 2,
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
		$g = self::factory()->group->create();

		$users = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$users[ $i ] = self::factory()->user->create();
		}

		$expected = array();
		for ( $i = 3; $i <= 5; $i++ ) {
			$this->add_user_to_group( $users[ $i ], $g );
			$expected[] = $users[ $i ];
		}

		global $members_template;
		bp_group_has_members( array(
			'group_id' => $g,
			'max' => 1,
		) );

		$this->assertEquals( 1, $members_template->member_count );
	}

	/**
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_with_exclude() {
		$g = self::factory()->group->create();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

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
		$g = self::factory()->group->create();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

		$now = time();
		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60*60 ),
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
		$g = self::factory()->group->create();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

		$now = time();
		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 60*60*24 ),
		) );
		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 60*60*12 ),
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
	 * @ticket BP5106
	 * @group bp_group_has_members
	 */
	public function test_bp_group_has_members_default_order() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 600 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 6000 ),
		) );

		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 60*60*24 ),
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 60*60*12 ),
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
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 600 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 6000 ),
		) );
		$u4 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 60000 ),
		) );


		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

		groups_invite_user( array(
			'user_id'       => $u2,
			'group_id'      => $g,
			'inviter_id'    => $u1,
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 50 ),
			'send_invite'   => 1,
		) );

		groups_invite_user( array(
			'user_id'       => $u3,
			'group_id'      => $g,
			'inviter_id'    => $u1,
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 30 ),
			'send_invite'   => 1,
		) );

		$m4 = $this->add_user_to_group( $u4, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $now - 60*60*36 ),
			'is_confirmed' => 1
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
		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );

		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
		) );

		$users = array();
		$now = time();
		for ( $i = 1; $i < 6; $i++ ) {
			$users[ $i ] = self::factory()->user->create( array(
				'last_activity' => gmdate( 'Y-m-d H:i:s', $now - $i*60 ),
			) );

			$inv = groups_invite_user( array(
				'user_id'       => $users[ $i ],
				'group_id'      => $g,
				'inviter_id'    => $u1,
				'send_invite'   => 1,
				'date_modified' => gmdate( 'Y-m-d H:i:s', $now - $i*60 ),
			) );
		}

		// Populate the global
		bp_group_has_invites( array(
			'group_id' => $g,
			'user_id' => $u1,
			'page' => 2,
			'per_page' => 2,
		) );

		global $invites_template;

		$this->assertEquals( array( $users[ 3 ], $users[ 2 ] ), $invites_template->invites );
	}

	/**
	 * Checks for proper queried items
	 *
	 * @group bp_group_has_membership_requests
	 * @group BP_Group_Membership_Requests_Template
	 */
	public function test_bp_group_has_membership_requests_results() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $now - 60 ),
		) );

		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
			'status'     => 'private'
		) );

		$users = array();
		$memberships = array();
		for ( $i = 1; $i < 5; $i++ ) {
			$users[ $i ] = self::factory()->user->create( array(
				'last_activity' => gmdate( 'Y-m-d H:i:s', $now - ( 100 - $i ) ),
			) );

			$memberships[ $i ] = groups_send_membership_request( array(
				'user_id'       => $users[ $i ],
				'group_id'      => $g,
				'date_modified' => gmdate( 'Y-m-d H:i:s', $now - ( 100 - $i ) ),
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
			'per_page' => 3,
		) );

		global $requests_template;

		$expected_user_ids = array();
		$expected_mem_ids = array();
		for ( $j = 1; $j <= 3; $j++ ) {
			$expected_user_ids[] = (string) $users[ $j ];
			$expected_mem_ids[] = (string) $memberships[ $j ];
		}

		$this->assertEquals( $expected_user_ids, wp_list_pluck( $requests_template->requests, 'user_id' ) );
		$this->assertEquals( $expected_mem_ids, wp_list_pluck( $requests_template->requests, 'invitation_id' ) );
	}

	/**
	 * Checks that the requests_template object is properly formatted
	 *
	 * @group bp_group_has_membership_requests
	 * @group BP_Group_Membership_Requests_Template
	 */
	public function test_bp_group_has_membership_requests_format() {
		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
		) );

		$g = self::factory()->group->create( array(
			'creator_id' => $u1,
			'status'     => 'private'
		) );

		$time = time();

		$user = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', $time ),
		) );

		$membership = groups_send_membership_request( array(
			'user_id'       => $user,
			'group_id'      => $g,
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time ),
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
		$expected->invitation_id = $membership;
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
		$expected->invite_sent = '1';

		// Check each expected value. If there are more in the results,
		// that's OK
		foreach ( get_object_vars( $expected ) as $k => $v ) {
			$this->assertEquals( $v, $requests_template->requests[0]->{$k} );
		}
	}

	/**
	 * @group bp_group_is_user_banned
	 */
	public function test_bp_group_is_user_banned_in_groups_loop() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		$g1 = self::factory()->group->create( array(
			'creator_id' => $u1,
			'last_activity' => $now - 100,
		) );
		$g2 = self::factory()->group->create( array(
			'creator_id' => $u2,
			'last_activity' => $now - 200,
		) );

		$this->add_user_to_group( $u1, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );
		$this->add_user_to_group( $u2, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 500 ),
		) );
		$this->add_user_to_group( $u1, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		// Ban user 1 from group 2
		// Fool the admin check
		$old_user = get_current_user_id();
		wp_set_current_user( $u2 );
		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g2 );

		// Start the groups loop
		wp_set_current_user( $u1 );
		if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group();
			$found[] = bp_group_is_user_banned();
		endwhile; endif;

		// Assert
		$expected = array( false, true );
		$this->assertEquals( $expected, $found );

		// Clean up
		$GLOBALS['groups_template'] = null;
		wp_set_current_user( $old_user );
	}

	/**
	 * @group bp_group_is_user_banned
	 */
	public function test_bp_group_is_user_banned_not_in_groups_loop() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$g1 = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		$g2 = self::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->add_user_to_group( $u1, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );
		$this->add_user_to_group( $u2, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 500 ),
		) );
		$this->add_user_to_group( $u1, $g2, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		// Ban user 1 from group 2
		// Fool the admin check
		$old_user = get_current_user_id();
		wp_set_current_user( $u2 );
		buddypress()->is_item_admin = true;
		groups_ban_member( $u1, $g2 );

		// Do group ban checks
		$group1 = new BP_Groups_Group( $g1 );
		$group2 = new BP_Groups_Group( $g2 );

		$found = array();
		$found[] = bp_group_is_user_banned( $group1, $u1 );
		$found[] = bp_group_is_user_banned( $group2, $u1 );

		// Assert
		$expected = array( false, true );
		$this->assertEquals( $expected, $found );

		// Clean up
		wp_set_current_user( $old_user );
	}

	/**
	 * @group bp_group_is_forum_enabled
	 */
	public function test_bp_group_is_forum_enabled() {
		$g1 = self::factory()->group->create( array( 'enable_forum' => 0 ) );
		$g2 = self::factory()->group->create( array( 'enable_forum' => 1 ) );

		$this->assertFalse( bp_group_is_forum_enabled( $g1 ) );
		$this->assertTrue( bp_group_is_forum_enabled( $g2 ) );
	}

	/**
	 * @group bp_get_group_member_is_banned
	 */
	public function test_bp_group_member_is_banned() {
		$this->assertFalse( bp_get_group_member_is_banned() );
	}

	/**
	 * @group bp_get_group_member_id
	 */
	public function test_bp_get_group_member_id() {
		$this->assertFalse( (bool) bp_get_group_member_id() );
	}

	/**
	 * @group bp_get_group_form_action
	 */
	public function test_bp_bp_get_group_form_action_when_empty() {
		$this->assertEmpty( bp_get_group_form_action( '' ) );
	}

	/**
	 * @group bp_get_group_form_action
	 */
	public function test_bp_bp_get_group_form_action() {
		$g   = self::factory()->group->create();
		$p   = 'members';
		$url = bp_get_group_url(
			$g,
			array(
				'single_item_action' => $p,
			)
		);

		$this->assertSame( bp_get_group_form_action( $p, $g ), $url );
	}

	/**
	 * @group bp_get_group_member_count
	 */
	public function test_bp_get_group_member_count_0_members() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u ) );

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $g );

		// Kick group creator.
		wp_delete_user( $u );

		$this->assertNotSame( '0 member', bp_get_group_member_count() );
	}

	/**
	 * @group bp_get_group_member_count
	 */
	public function test_bp_get_group_member_count_1_member() {
		$g = self::factory()->group->create();

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $g );

		$this->assertSame( '1 member', bp_get_group_member_count() );
	}

	/**
	 * @group bp_get_group_member_count
	 */
	public function test_bp_get_group_member_count_2_members() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g  = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		$this->add_user_to_group( $u2, $g );

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $g );

		$this->assertSame( '2 members', bp_get_group_member_count() );
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Template
	 */
	public function test_bp_groups_template_should_give_precedence_to_grpage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['grpage'] = '5';

		$at = new BP_Groups_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Template
	 */
	public function test_bp_groups_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['grpage'] = '0';

		$at = new BP_Groups_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Template
	 */
	public function test_bp_groups_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Groups_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Template
	 */
	public function test_bp_groups_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Groups_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Group_Members_Template
	 */
	public function test_bp_groups_group_members_template_should_give_precedence_to_mlpage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['mlpage'] = '5';

		$at = new BP_Groups_Group_Members_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Group_Members_Template
	 */
	public function test_bp_groups_group_members_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['mlpage'] = '0';

		$at = new BP_Groups_Group_Members_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Group_Members_Template
	 */
	public function test_bp_groups_group_members_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Groups_Group_Members_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Group_Members_Template
	 */
	public function test_bp_groups_group_members_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Groups_Group_Members_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Membership_Requests_Template
	 */
	public function test_bp_groups_membership_requests_template_should_give_precedence_to_mrpage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['mrpage'] = '5';

		$at = new BP_Groups_Membership_Requests_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Membership_Requests_Template
	 */
	public function test_bp_groups_membership_requests_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['mrpage'] = '0';

		$at = new BP_Groups_Membership_Requests_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Membership_Requests_Template
	 */
	public function test_bp_groups_membership_requests_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Groups_Membership_Requests_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Membership_Requests_Template
	 */
	public function test_bp_groups_membership_requests_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Groups_Membership_Requests_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_groups_invite_template_should_give_precedence_to_invitepage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['invitepage'] = '5';

		$at = new BP_Groups_Invite_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_groups_invite_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['invitepage'] = '0';

		$at = new BP_Groups_Invite_Template( array(
			'page' => 8,
		) );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_groups_invite_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$at = new BP_Groups_Invite_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Groups_Invite_Template
	 */
	public function test_bp_groups_invite_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$at = new BP_Groups_Invite_Template( array(
			'per_page' => 13,
		) );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}
}
