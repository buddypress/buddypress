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
		for ( $i = 3; $i <= 10; $i++ ) {
			$this->add_user_to_group( $users[ $i ], $g );
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
			'exclude_banned' => 0,
			'exclude_admins_mods' => false,
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, $u2, ), $ids );
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
}
