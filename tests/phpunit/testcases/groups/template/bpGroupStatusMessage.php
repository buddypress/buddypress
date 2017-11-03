<?php

/**
 * @group groups
 */
class BP_Tests_Groups_Template_BpGroupStatusMessage extends BP_UnitTestCase {
	private $current_user;
	private $groups_template = null;

	public function setUp() {
		parent::setUp();
		$this->current_user = bp_loggedin_user_id();
		$this->set_current_user( 0 );

		if ( isset( $GLOBALS['groups_template'] ) ) {
			$this->groups_template = $GLOBALS['groups_template'];
		}
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
		if ( $this->groups_template ) {
			$GLOBALS['groups_template'] = $this->groups_template;
		}

		parent::tearDown();
	}

	/**
	 * @group BP6319
	 */
	public function test_private_group_where_logged_in_user_has_not_requested_membership_but_has_been_invited() {
		$users = self::factory()->user->create_many( 2 );
		$g = self::factory()->group->create( array( 'status' => 'private' ) );

		$this->set_current_user( $users[0] );

		groups_invite_user( array(
			'user_id' => $users[0],
			'group_id' => $g,
			'inviter_id' => $users[1],
		) );

		if ( bp_has_groups( array( 'include' => array( $g ) ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$found = get_echo( 'bp_group_status_message' );
			}
		}

		$expected = __( 'This is a private group and you must request group membership in order to join.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}

	/**
	 * @group BP6319
	 */
	public function test_private_group_where_logged_in_user_has_not_requested_membership_and_has_not_been_invited() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private' ) );

		$this->set_current_user( $u );

		if ( bp_has_groups( array( 'include' => array( $g ) ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$found = get_echo( 'bp_group_status_message' );
			}
		}

		$expected = __( 'This is a private group and you must request group membership in order to join.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}

	/**
	 * @group BP6319
	 */
	public function test_private_group_visited_by_a_non_logged_in_user() {
		$g = self::factory()->group->create( array( 'status' => 'private' ) );

		if ( bp_has_groups( array( 'include' => array( $g ) ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$found = get_echo( 'bp_group_status_message' );
			}
		}

		$expected = __( 'This is a private group. To join you must be a registered site member and request group membership.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}

	/**
	 * @group BP6319
	 */
	public function test_private_group_where_loggedin_user_has_requested_membership() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private' ) );

		$this->set_current_user( $u );

		groups_send_membership_request( $u, $g );

		if ( bp_has_groups( array( 'include' => array( $g ) ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$found = get_echo( 'bp_group_status_message' );
			}
		}

		$expected = __( 'This is a private group. Your membership request is awaiting approval from the group administrator.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}

	/**
	 * @group BP6319
	 */
	public function test_hidden_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'hidden' ) );

		$this->set_current_user( $u );

		$group = groups_get_group( $g );

		$found = get_echo( 'bp_group_status_message', array( $group ) );

		$expected = __( 'This is a hidden group and only invited members can join.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}

	/**
	 * @group BP6319
	 */
	public function test_group_parameter_should_be_obeyed() {
		$u = self::factory()->user->create();
		$groups = self::factory()->group->create_many( 2, array( 'status' => 'private' ) );

		$this->set_current_user( $u );

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $groups[0] );

		groups_send_membership_request( $u, $groups[1] );

		$group1 = groups_get_group( array(
			'group_id' => $groups[1],
			'populate_extras' => true,
		) );

		$found = get_echo( 'bp_group_status_message', array( $group1 ) );

		$expected = __( 'This is a private group. Your membership request is awaiting approval from the group administrator.', 'buddypress' );
		$this->assertSame( $expected, $found );
	}
}
