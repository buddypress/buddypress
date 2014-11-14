<?php

/**
 * @group notices
 */
class BP_Tests_BP_Messages_Notice_TestCases extends BP_UnitTestCase {

	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();
		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	/**
	 * @group cache
	 */
	public function test_get_active_notices() {
		// send notice
		$subject = 'Test notice';
		$message = 'This is a notice';
		messages_send_notice( $subject, $message );

		// now get the active notice and assert
		$notice = BP_Messages_Notice::get_active();
		$this->assertEquals( $subject, $notice->subject );
		$this->assertEquals( $message, $notice->message );

		// deactivate notice and make sure cache is invalidated
		$notice->deactivate();
		$this->assertFalse( wp_cache_get( 'active_notice', 'bp_messages' ) );

		// create a new notice
		$subject2 = 'Another notice';
		$message2 = 'Say what?';
		messages_send_notice( $subject2, $message2 );

		// now get the new active notice
		BP_Messages_Notice::get_active();

		// grab the cache and make sure it equals our new notice
		$cache = wp_cache_get( 'active_notice', 'bp_messages' );
		$this->assertEquals( $subject2, $cache->subject );
		$this->assertEquals( $message2, $cache->message );
	}

}
