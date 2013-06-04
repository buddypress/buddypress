<?php
/**
 * @group groups
 * @group BP_Group_Member_Query
 */
class BP_Tests_BP_Group_Member_Query_TestCases extends BP_UnitTestCase {
	/**
	 * Make sure that a manual 'include' param is parsed correctly with
	 * BP_Group_Member_Query's limiting of the query to group members
	 */
	public function test_with_include() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

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
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

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
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

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
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

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
		$g = $this->factory->group->create();
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$u3 = $this->create_user();

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

	public function test_group_has_no_members() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'member', 'mod', 'admin' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array(), $ids );
	}

	public function test_group_has_no_members_of_role_mod() {
		$g = $this->factory->group->create();
		$u1 = $this->create_user();

		$this->add_user_to_group( $u1, $g, array( 'date_modified' => gmdate( 'Y-m-d H:i:s', $time - 100 ) ) );

		$query_members = new BP_Group_Member_Query( array(
			'group_id' => $g,
			'group_role' => array( 'mod' ),
		) );

		$ids = wp_parse_id_list( array_keys( $query_members->results ) );
		$this->assertEquals( array(), $ids );
	}

}
