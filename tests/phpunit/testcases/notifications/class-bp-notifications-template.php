<?php

/**
 * @group notifications
 * @group template
 */

class BP_Tests_Notifications_BPNotificationsTemplate extends BP_UnitTestCase {
	/**
 	 * @group pagination
 	 * @group BP6229
 	 */
 	public function test_pagination_params_in_url_should_be_passed_to_query() {
		$u = $this->factory->user->create();

		$notifications = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$notifications[] = $this->factory->notification->create( array(
				'component_name' => 'activity',
				'secondary_item_id' => $i,
				'user_id' => $u,
				'is_new' => true,
			) );
		}

		$_REQUEST['npage'] = 2;

		$template = new BP_Notifications_Template( array(
			'user_id' => $u,
			'is_new' => true,
			'per_page' => 2,
			'order_by' => 'id',
		) );

		unset( $_REQUEST['npage'] );

		// Check that the correct number of items are pulled up
		$expected = array( $notifications[3], $notifications[2] );
		$this->assertEquals( $expected, wp_list_pluck( $template->notifications, 'id' ) );
 	}
}
