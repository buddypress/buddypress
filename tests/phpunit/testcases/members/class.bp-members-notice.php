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
	 */
	public function test_get_active_notices() {
		/*
		 * @todo Update this test then remove the skipping instruction.
		 */
		$this->markTestSkipped();

		// send notice
		$subject = 'Test notice';
		$message = 'This is a notice';

		bp_members_save_notice(
			array(
				'title'   => $subject,
				'content' => $message,
			)
		);

		// now get the active notice and assert
		$notice = BP_Members_Notice::get_active();
		$this->assertEquals( $subject, $notice->subject );
		$this->assertEquals( $message, $notice->message );

		// deactivate notice and make sure cache is invalidated
		$notice->deactivate();
		$this->assertFalse( wp_cache_get( 'active_notice', 'bp_messages' ) );

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
		$cache = wp_cache_get( 'active_notice', 'bp_messages' );
		$this->assertEquals( $subject2, $cache->subject );
		$this->assertEquals( $message2, $cache->message );
	}
}
