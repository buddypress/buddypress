<?php
/**
 * @group groups
 * @group BP_Group_Member_Query
 */
class BP_Tests_BP_Group_Member_Query_TestCases extends BP_UnitTestCase {
	public function setUp() {
		$this->current_user = get_current_user_id();
		$this->set_current_user( 0 );
		parent::setUp();
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
		parent::tearDown();
	}

	/**
	 * Make sure that a manual 'include' param is parsed correctly with
	 * BP_Group_Member_Query's limiting of the query to group members
	 */
	public function test_with_include() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );

		$query = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'include' => array( $u2 ),
		) );

		$ids = wp_parse_id_list( array_keys( $query->results ) );
		$this->assertEquals( array( $u2, ), $ids );
	}

	// Make sure we're falling back on 'member'
	public function test_with_group_role_null() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query = new BP_Group_Member_Query( array(
			'group_id' => $g,
		) );

		$expected = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member' ),
		) );

		$this->assertEquals( $expected->results, $query->results );
	}

	public function test_with_group_role_member() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u3, ), $ids );
	}

	public function test_with_group_role_mod() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'mod' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2, ), $ids );
	}

	public function test_with_group_role_admin() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$g  = $this->factory->group->create( array(
			'creator_id' => $u1
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'admin' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, ), $ids );
	}

	public function test_with_group_role_member_mod() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member', 'mod' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2, $u3, ), $ids );
	}

	public function test_with_group_role_member_admin() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$g  = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member', 'admin' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u3, ), $ids );
	}

	public function test_with_group_role_mod_admin() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$g  = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'mod', 'admin' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u2, ), $ids );
	}

	public function test_with_group_role_member_mod_admin() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$g  = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member', 'mod', 'admin' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u2, $u3, ), $ids );
	}

	public function test_with_group_role_member_mod_admin_banned() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();
		$g  = $this->factory->group->create( array(
			'creator_id' => $u1,
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );
		$this->add_user_to_group( $u3, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );
		$this->add_user_to_group( $u4, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );
		$m3 = new BP_Groups_Member( $u3, $g );
		$m3->ban();

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member', 'mod', 'admin', 'banned' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u2, $u3, $u4, ), $ids );
	}

	/**
	 * @group role
	 */
	public function test_with_group_role_banned() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );
		$this->add_user_to_group( $u2, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ) ) );

		$m1 = new BP_Groups_Member( $u1, $g );
		$m1->ban();

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'banned' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, ), $ids );
	}

	public function test_group_has_no_members_of_role_mod() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'mod' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array(), $ids );
	}

	public function test_confirmed_members() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 1,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2 ), $ids );
	}

	/**
	 * @group type
	 */
	public function test_get_with_type_last_joined() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 500 ),
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'last_joined',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2, $u1 ), $ids );
	}

	/**
	 * @group type
	 */
	public function test_get_with_type_first_joined() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 500 ),
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'first_joined',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u2 ), $ids );
	}

	/**
	 * @group type
	 * @group group_activity
	 */
	public function test_get_with_type_group_activity_with_activity_component_disabled() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$c = buddypress()->groups->id;
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 500 ),
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 400 ),
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u3,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 250 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u1,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u2,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		// Deactivate activity component
		$activity_active = isset( buddypress()->active_components['activity'] );
		if ( $activity_active ) {
			unset( buddypress()->active_components['activity'] );
		}

		$query_members1 = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'group_activity',
		) );

		$query_members2 = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'last_joined',
		) );

		if ( $activity_active ) {
			buddypress()->active_components['activity'] = '1';
		}

		$this->assertSame( wp_list_pluck( $query_members2->results, 'ID' ), wp_list_pluck( $query_members1->results, 'ID' ) );
	}

	/**
	 * @group type
	 * @group group_activity
	 */
	public function test_get_with_type_group_activity() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$c = buddypress()->groups->id;
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 500 ),
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 400 ),
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u3,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 250 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u1,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u2,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'group_activity',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2, $u1, $u3 ), $ids );
	}

	/**
	 * @group type
	 * @group group_activity
	 */
	public function test_get_with_type_group_activity_no_dupes() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$c = buddypress()->groups->id;
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 500 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u1,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 250 ),
		) );

		$this->factory->activity->create( array(
			'component' => $c,
			'type' => 'activity_update',
			'user_id' => $u1,
			'item_id' => $g,
			'recorded_time' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'group_activity',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, ), $ids );
	}
	/**
	 * @group type
	 */
	public function test_get_with_type_alphabetical() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user( array(
			'display_name' => 'AAA',
		) );
		$u2 = $this->create_user( array(
			'display_name' => 'CCC',
		) );
		$u3 = $this->create_user( array(
			'display_name' => 'BBB',
		) );
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ),
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'type' => 'alphabetical',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1, $u3, $u2 ), $ids );
	}

	/**
	 * @group invite_sent
	 */
	public function test_with_invite_sent_true() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 0,
			'invite_sent' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 0,
			'invite_sent' => 1,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'is_confirmed' => false,
			'invite_sent' => true,
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2 ), $ids );
	}

	/**
	 * @group invite_sent
	 */
	public function test_with_invite_sent_false() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 0,
			'invite_sent' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'is_confirmed' => 0,
			'invite_sent' => 1,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'is_confirmed' => false,
			'invite_sent' => false,
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1 ), $ids );
	}

	/**
	 * @group inviter_id
	 */
	public function test_with_inviter_id_false() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'inviter_id' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'inviter_id' => 1,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'inviter_id' => false,
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u1 ), $ids );
	}

	/**
	 * @group inviter_id
	 */
	public function test_with_inviter_id_specific() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'inviter_id' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
			'inviter_id' => 1,
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ),
			'inviter_id' => 6,
		) );

		$this->add_user_to_group( $u4, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 400 ),
			'inviter_id' => 2,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'inviter_id' => array( 2, 6 ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u3, $u4 ), $ids );
	}

	/**
	 * @group inviter_id
	 */
	public function test_with_inviter_id_any() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();
		$u4 = $this->create_user();
		$time = time();

		$this->add_user_to_group( $u1, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
			'inviter_id' => 0,
		) );

		$this->add_user_to_group( $u2, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 200 ),
			'inviter_id' => 1,
		) );

		$this->add_user_to_group( $u3, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 300 ),
			'inviter_id' => 6,
		) );

		$this->add_user_to_group( $u4, $g, array(
			'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 400 ),
			'inviter_id' => 2,
		) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'inviter_id' => 'any',
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array( $u2, $u3, $u4 ), $ids );
	}
}
