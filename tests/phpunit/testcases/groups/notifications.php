<?php

/**
 * @group groups
 * @group notifications
 */
class BP_Tests_Groups_Notifications extends BP_UnitTestCase {
	protected $filter_fired;
	protected $current_user;
	protected $requesting_user_id;
	protected $group;

	public function setUp() {
		parent::setUp();
		$this->current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create() );

		$this->requesting_user_id = $this->factory->user->create();
		$this->group = $this->factory->group->create();
		$this->filter_fired = '';
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->current_user );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_new_membership_requests_notification() {
		add_filter( 'bp_groups_multiple_new_membership_requests_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'new_membership_request', $this->group, $this->requesting_user_id, 5 );
		remove_filter( 'bp_groups_multiple_new_membership_requests_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_new_membership_requests_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_new_membership_request_notification() {
		add_filter( 'bp_groups_single_new_membership_request_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'new_membership_request', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_new_membership_request_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_new_membership_request_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_membership_request_accepted_notification() {
		add_filter( 'bp_groups_multiple_membership_request_accepted_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'membership_request_accepted', $this->group, 0, 5 );
		remove_filter( 'bp_groups_multiple_membership_request_accepted_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_membership_request_accepted_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_membership_request_accepted_notification() {
		add_filter( 'bp_groups_single_membership_request_accepted_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'membership_request_accepted', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_membership_request_accepted_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_membership_request_accepted_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_membership_request_rejected_notification() {
		add_filter( 'bp_groups_multiple_membership_request_rejected_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'membership_request_rejected', $this->group, 0, 5 );
		remove_filter( 'bp_groups_multiple_membership_request_rejected_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_membership_request_rejected_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_membership_request_rejected_notification() {
		add_filter( 'bp_groups_single_membership_request_rejected_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'membership_request_rejected', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_membership_request_rejected_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_membership_request_rejected_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_member_promoted_to_admin_notification() {
		add_filter( 'bp_groups_multiple_member_promoted_to_admin_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'member_promoted_to_admin', $this->group, 0, 5 );
		remove_filter( 'bp_groups_multiple_member_promoted_to_admin_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_member_promoted_to_admin_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_member_promoted_to_admin_notification() {
		add_filter( 'bp_groups_single_member_promoted_to_admin_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'member_promoted_to_admin', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_member_promoted_to_admin_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_member_promoted_to_admin_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_member_promoted_to_mod_notification() {
		add_filter( 'bp_groups_multiple_member_promoted_to_mod_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'member_promoted_to_mod', $this->group, 0, 5 );
		remove_filter( 'bp_groups_multiple_member_promoted_to_mod_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_member_promoted_to_mod_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_member_promoted_to_mod_notification() {
		add_filter( 'bp_groups_single_member_promoted_to_mod_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'member_promoted_to_mod', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_member_promoted_to_mod_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_member_promoted_to_mod_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_multiple_group_invite_notification() {
		add_filter( 'bp_groups_multiple_group_invite_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'group_invite', $this->group, 0, 5 );
		remove_filter( 'bp_groups_multiple_group_invite_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_multiple_group_invite_notification', $this->filter_fired );
	}

	/**
	 * @group groups_format_notifications
	 */
	public function test_groups_format_notifications_bp_groups_single_group_invite_notification() {
		add_filter( 'bp_groups_single_group_invite_notification', array( $this, 'notification_filter_callback' ) );
		$n = groups_format_notifications( 'group_invite', $this->group, 0, 1 );
		remove_filter( 'bp_groups_single_group_invite_notification', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_groups_single_group_invite_notification', $this->filter_fired );
	}

	/**
	 * @group bp_groups_delete_promotion_notifications
	 */
	public function test_bp_groups_delete_promotion_notifications() {
		// Dummy group and user IDs
		$u = 5;
		$g = 12;

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'item_id' => $g,
			'component_action' => 'member_promoted_to_admin',
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Double check it's there
		$this->assertEquals( array( $n ), wp_list_pluck( $notifications, 'id' ) );

		// fire the hook
		do_action( 'groups_demoted_member', $u, $g );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		$this->assertEmpty( $notifications );

		// Mod
		$n = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'item_id' => $g,
			'component_action' => 'member_promoted_to_mod',
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Double check it's there
		$this->assertEquals( array( $n ), wp_list_pluck( $notifications, 'id' ) );

		// fire the hook
		do_action( 'groups_demoted_member', $u, $g );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		$this->assertEmpty( $notifications );
	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}
}
