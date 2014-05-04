<?php

/**
 * @group groups
 * @group notifications
 */
class BP_Tests_Groups_Notifications extends BP_UnitTestCase {
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
}
