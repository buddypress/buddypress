<?php

/**
 * @group activity
 * @group cache
 */
class BP_Tests_Activity_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_activity_update_meta_cache
	 */
	public function test_bp_activity_update_meta_cache() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		// Set up some data
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'Boone', 'Rules' );

		bp_activity_update_meta( $a2, 'foo', 'baz' );
		bp_activity_update_meta( $a2, 'BuddyPress', 'Is Cool' );

		// Prime the cache for $a1
		bp_activity_get_meta( $a1, 'foo' );

		// Ensure an empty cache for $a2
		wp_cache_delete( $a2, 'activity_meta' );

		bp_activity_update_meta_cache( array( $a1, $a2 ) );

		$expected = array(
			$a1 => array(
				'foo' => array(
					'bar',
				),
				'Boone' => array(
					'Rules',
				),
			),
			$a2 => array(
				'foo' => array(
					'baz',
				),
				'BuddyPress' => array(
					'Is Cool',
				),
			),
		);

		$found = array(
			$a1 => wp_cache_get( $a1, 'activity_meta' ),
			$a2 => wp_cache_get( $a2, 'activity_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_activity_clear_cache_for_activity
	 */
	public function test_bp_activity_clear_cache_for_activity() {
		$u = $this->factory->user->create();

		$a = $this->factory->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$a_fp = bp_activity_get( array(
			'type' => 'activity_update',
			'user' => array( 'filter' => array( 'user_id' => $u ) ),
		) );

		$activity_updated = new BP_Activity_Activity( $a );
		$activity_updated->content = 'bar foo';
		$activity_updated->save();

		$a_fp = bp_activity_get( array(
			'type' => 'activity_update',
			'user' => array( 'filter' => array( 'user_id' => $u ) ),
		) );

		$this->assertSame( 'bar foo', $a_fp['activities'][0]->content );
	}
}
