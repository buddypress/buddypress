<?php

/**
 * @group activity
 * @group bp_activity_get_comment_depth
 */
class BP_Tests_Activity_Functions_BpActivityGetCommentDepth extends BP_UnitTestCase {
	/**
	 * @ticket BP7329
	 */
	public function test_depth_inside_activity_comment_loop() {
		$u = self::factory()->user->create();

		// create an activity update
		$parent_activity = self::factory()->activity->create( array(
			'type'    => 'activity_update',
			'user_id' => $u
		) );

		// create some activity comments
		$comment_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		$comment_one_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'parent_id'   => $comment_one,
			'content'     => 'depth 2'
		) );

		$comment_two = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		// Instantiate activity loop, which also includes activity comments.
		bp_has_activities( 'display_comments=threaded' );

		// Loop through activity comments generated in activity loop.
		$recursive = new RecursiveIteratorIterator( new RecursiveArrayIterator( $GLOBALS['activities_template']->activities[0]->children ), RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $recursive as $aid => $a ) {
			if ( ! is_numeric( $aid ) || ! is_object( $a ) ) {
				continue;
			}

			/*
			 * Emulate activity comment loop global, which bp_activity_get_comment_depth()
			 * relies on by default.
			 */
			$GLOBALS['activities_template']->activity = new stdClass;
			$GLOBALS['activities_template']->activity->current_comment = $a;

			// $aid is the activity ID for the current activity comment.
			switch ( $aid ) {
				case $comment_one :
				case $comment_two :
					$this->assertSame( bp_activity_get_comment_depth(), 1 );
					break;

				case $comment_one_one :
					$this->assertSame( bp_activity_get_comment_depth(), 2 );
					break;
			}

		}

		// Clean up after ourselves!
		$GLOBALS['activities_template'] = null;
	}

	/**
	 * @ticket BP7329
	 */
	public function test_depth_outside_of_activity_comment_loop() {
		$u = self::factory()->user->create();

		// create an activity update
		$parent_activity = self::factory()->activity->create( array(
			'type'    => 'activity_update',
			'user_id' => $u
		) );

		// create some activity comments
		$comment_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		$comment_one_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'parent_id'   => $comment_one,
			'content'     => 'depth 2'
		) );

		$comment_two = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		$this->assertSame( bp_activity_get_comment_depth( $comment_one ), 1 );
		$this->assertSame( bp_activity_get_comment_depth( $comment_one_one ), 2 );
		$this->assertSame( bp_activity_get_comment_depth( $comment_two ), 1 );
	}
}
