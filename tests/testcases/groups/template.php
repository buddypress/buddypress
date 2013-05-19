<?php
/**
 * @group groups
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
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, ), $ids );
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
		) );

		$ids = wp_parse_id_list( wp_list_pluck( $members_template->members, 'user_id' ) );
		$this->assertEquals( array( $u1, $u2, ), $ids );
	}

}
