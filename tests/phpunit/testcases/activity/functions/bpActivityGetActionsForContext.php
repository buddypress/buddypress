<?php

/**
 * @group activity
 */
class BP_Tests_Activity_Functions_BpActivityGetActionsForContext extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$bp = buddypress();

		$this->reset_actions = clone $bp->activity->actions;
		$bp->activity->actions = new stdClass();

		$this->reset_actions_sorted = ! empty( $bp->activity->actions_sorted );
		unset( $bp->activity->actions_sorted );
	}

	public function tearDown() {
		$bp = buddypress();

		$bp->activity->actions = $this->reset_actions;

		if ( $this->reset_actions_sorted ) {
			$bp->activity->actions_sorted = true;
		} else {
			unset( $bp->activity->actions_sorted );
		}

		parent::tearDown();
	}

	public function test_should_include_actions_registered_for_context() {
		bp_activity_set_action(
			'foo',
			'new_foo',
			'Did a foo',
			'',
			'',
			array( 'member' ),
			10
		);

		$actions = bp_activity_get_actions_for_context( 'member' );
		$keys = wp_list_pluck( $actions, 'key' );
		$this->assertContains( 'new_foo', $keys );
	}

	public function test_should_ignore_actions_not_registered_for_context() {
		bp_activity_set_action(
			'foo',
			'new_foo',
			'Did a foo',
			'',
			'',
			array( 'member' ),
			10
		);

		$actions = bp_activity_get_actions_for_context( 'group' );
		$keys = wp_list_pluck( $actions, 'key' );
		$this->assertNotContains( 'new_foo', $keys );
	}
}
