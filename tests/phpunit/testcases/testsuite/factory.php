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

	/**
	 * @ticket BP7243
	 */
	public function test_friendship_should_create_default_initiator_and_friend() {
		$f = $this->factory->friendship->create_and_get();

		$u1 = new WP_User( $f->initiator_user_id );
		$u2 = new WP_User( $f->friend_user_id );

		$this->assertTrue( $u1->exists() );
		$this->assertTrue( $u2->exists() );
	}

	/**
	 * @ticket BP7243
	 */
	public function test_friendship_should_respect_passed_initiator_and_friend() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$f = $this->factory->friendship->create_and_get( array(
			'initiator_user_id' => $u1,
			'friend_user_id' => $u2,
		) );

		$this->assertSame( $u1, $f->initiator_user_id );
		$this->assertSame( $u2, $f->friend_user_id );
	}
}
