<?php

/**
 * @group notices
 */
class BP_Tests_BP_Members_Notice_TestCases extends BP_UnitTestCase {

	protected $old_current_user = 0;

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
		self::set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		self::set_current_user( $this->old_current_user );

		parent::tear_down();
	}

	/**
	 * @group cache
	 * @ticket BP9098
	 */
	public function test_get_active_notice() {
		// send notice
		$subject = 'Test notice';
		$message = 'This is a notice';

		bp_members_save_notice(
			array(
				'title'   => $subject,
				'content' => $message,
			)
		);

		$u1 = self::factory()->user->create();
		self::set_current_user( $u1 );

		// now get the active notice and assert
		$notice = bp_get_active_notice_for_user();
		$this->assertEquals( $subject, bp_get_notice_title( $notice ) );
		$this->assertEquals( $message, bp_get_notice_content( $notice, true ) );

		// deactivate notice and make sure cache is invalidated
		$test = $notice->deactivate();
		$this->assertFalse( wp_cache_get( $u1, 'bp_member_first_active_notice' ) );

		// create a new notice
		$subject2 = 'Another notice';
		$message2 = 'Say what?';

		bp_members_save_notice(
			array(
				'title'   => $subject2,
				'content' => $message2,
			)
		);

		// now get the new active notice
		BP_Members_Notice::get_active();

		// grab the cache and make sure it equals our new notice
		$cache = wp_cache_get( $u1, 'bp_member_first_active_notice' );
		$this->assertEquals( $subject2, bp_get_notice_title( $cache ) );
		$this->assertEquals( $message2, bp_get_notice_content( $cache, true ) );
	}
}
