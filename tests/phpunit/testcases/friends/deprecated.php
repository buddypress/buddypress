<?php

/**
 * @group friends
 * @group deprecated
 */
class BP_Tests_Friends_Deprecated extends BP_UnitTestCase {

	/**
	 * @expectedDeprecated BP_Friends_Friendship::__construct
	 */
	public function test_deprecated_arg() {
		$friendship = new BP_Friends_Friendship( 123456789, true );
		$this->assertSame( 0, $friendship->id );
	}

	/**
	 * @expectedDeprecated friends_friendship_whithdrawn
	 */
	public function test_withdraw_friendship_keeps_the_legacy_action_arguments() {
		list( $initiator, $recipient ) = self::factory()->user->create_many( 2 );
		friends_add_friend( $initiator, $recipient );
		$id = BP_Friends_Friendship::get_friendship_id( $initiator, $recipient );

		$replacement = null;
		$received    = array();
		$legacy      = static function ( $friendship_id, &$friendship ) use ( &$replacement, &$received ) {
			$received[]  = $friendship_id;
			$replacement = clone $friendship;
			$friendship  = $replacement;
		};
		$current     = static function ( $friendship_id, $friendship ) use ( &$received ) {
			$received[] = $friendship_id;
			$received[] = $friendship;
		};

		// Run after the current hook's regular callbacks.
		add_action( 'friends_friendship_whithdrawn', $legacy, 10, 2 );
		add_action( 'friends_friendship_withdrawn', $current, 99, 2 );

		$old_user = get_current_user_id();
		wp_set_current_user( $initiator );
		try {
			$this->assertTrue( friends_withdraw_friendship( $initiator, $recipient ) );
		} finally {
			wp_set_current_user( $old_user );
			remove_action( 'friends_friendship_whithdrawn', $legacy );
			remove_action( 'friends_friendship_withdrawn', $current );
		}

		$this->assertInstanceOf( BP_Friends_Friendship::class, $replacement );
		$this->assertSame( array( $id, $id, $replacement ), $received );
	}
}
