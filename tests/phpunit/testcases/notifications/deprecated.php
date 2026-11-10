<?php
/**
 * Deprecated notifications functionality tests.
 *
 * @group notifications
 * @group deprecated
 */
class BP_Tests_Notifications_Deprecated extends BP_UnitTestCase {

	/**
	 * @group get
	 * @expectedDeprecated bp_notifications_get_where_conditions
	 */
	public function test_get_where_conditions_filter_is_deprecated() {
		$user_id = self::factory()->user->create();
		self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );

		$calls  = array();
		$filter = static function( $conditions, $args, $select, $from, $join, $meta_query ) use ( &$calls ) {
			$calls[] = array( $args, $select, $from, $join, $meta_query );
			$conditions['legacy'] = '1 = 0';

			return $conditions;
		};

		add_filter( 'bp_notifications_get_where_conditions', $filter, 10, 6 );

		try {
			$found = BP_Notifications_Notification::get( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
			$total = BP_Notifications_Notification::get_total_count( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
		} finally {
			remove_filter( 'bp_notifications_get_where_conditions', $filter );
		}

		$this->assertSame( array(), $found );
		$this->assertSame( 0, $total );
		$this->assertCount( 2, $calls );
		$this->assertSame( $user_id, $calls[0][0]['user_id'] );
		$this->assertSame( 'SELECT n.*', $calls[0][1] );
		$this->assertSame( 'SELECT COUNT(*)', $calls[1][1] );
		$this->assertSame( $calls[0][2], $calls[1][2] );
		$this->assertSame( '', $calls[0][3] );
		$this->assertSame( array( 'join' => '', 'where' => '' ), $calls[0][4] );
	}
}
