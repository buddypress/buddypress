<?php

/**
 * @group notifications
 */
class BP_Tests_BP_Notifications_Notification_TestCases extends BP_UnitTestCase {
	/**
	 * @group get
	 */
	public function test_get_null_component_name() {
		$u = $this->create_user();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// temporarily turn on groups, shut off messages
		$groups_toggle = isset( buddypress()->active_components['groups'] );
		$messages_toggle = isset( buddypress()->active_components['messages'] );
		buddypress()->active_components['groups'] = 1;
		unset( buddypress()->active_components['messages'] );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Check that the correct items are pulled up
		$expected = array( $n1 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );

		// reset copmonent toggles
		if ( $groups_toggle ) {
			buddypress()->active_components['groups'] = 1;
		} else {
			unset( buddypress()->active_components['groups'] );
		}

		if ( $messages_toggle ) {
			buddypress()->active_components['messages'] = 1;
		} else {
			unset( buddypress()->active_components['messages'] );
		}
	}

	/**
	 * @group get_total_count
	 * @ticket BP5300
	 */
	public function test_get_total_count_null_component_name() {
		$u = $this->create_user();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// temporarily turn on groups, shut off messages
		$groups_toggle = isset( buddypress()->active_components['groups'] );
		$messages_toggle = isset( buddypress()->active_components['messages'] );
		buddypress()->active_components['groups'] = 1;
		unset( buddypress()->active_components['messages'] );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
		) );

		// Check that the correct items are pulled up
		$this->assertEquals( 1, $n );

		// reset copmonent toggles
		if ( $groups_toggle ) {
			buddypress()->active_components['groups'] = 1;
		} else {
			unset( buddypress()->active_components['groups'] );
		}

		if ( $messages_toggle ) {
			buddypress()->active_components['messages'] = 1;
		} else {
			unset( buddypress()->active_components['messages'] );
		}
	}

	/**
	 * @group get_total_count
	 * @ticket BP5300
	 */
	public function test_get_total_count_with_component_name() {
		$u = $this->create_user();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n3 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
			'component_name' => array( 'messages' ),
		) );

		$this->assertEquals( 1, $n );
	}

}
