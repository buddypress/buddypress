<?php

/**
 * @group testsuite
 */
class BPTests_Testsuite_Factory extends BP_UnitTestCase {
	/**
	 * @ticket BP7234
	 */
	public function test_message_create_and_get_should_return_message_object() {
		$m = $this->factory->message->create_and_get();

		$this->assertTrue( $m instanceof BP_Messages_Message );
	}

	/**
	 * @ticket BP7234
	 */
	public function test_message_should_create_default_sender_and_recipient() {
		$m = $this->factory->message->create_and_get();

		$this->assertNotEmpty( $m->sender_id );
		$this->assertNotEmpty( $m->get_recipients() );
	}
}
