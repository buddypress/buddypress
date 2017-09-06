<?php
/**
 * Testing ajax cover image functionality
 *
 * @group ajax
 */
class BP_Nouveau_Friends_Ajax_UnitTestCase extends Next_Template_Packs_Ajax_UnitTestCase {

	/**
	 * Helper to keep it DRY
	 *
	 * @param string $action Action.
	 */
	protected function make_ajax_call( $action ) {
		// Make the request.
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
	}

	public function test_bp_nouveau_ajax_addremove_friend_add() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();

		$this->set_current_user( $f1 );

		// Add friendship
		$_POST = array(
			'action'  => 'friends_add_friend',
			'nonce'   => wp_create_nonce( 'bp_nouveau_friends' ),
			'item_id' => $f2
		);

		$this->make_ajax_call( 'friends_add_friend' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
	}

	public function test_bp_nouveau_ajax_addremove_friend_cancel() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();

		$this->set_current_user( $f1 );

		friends_add_friend( $f1, $f2 );

		// Withdraw friendship
		$_POST = array(
			'action'  => 'friends_withdraw_friendship',
			'nonce'   => wp_create_nonce( 'bp_nouveau_friends' ),
			'item_id' => $f2
		);

		$this->make_ajax_call( 'friends_withdraw_friendship' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
	}

	public function test_bp_nouveau_ajax_addremove_friend_remove() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();

		$this->set_current_user( $f1 );

		friends_add_friend( $f1, $f2 );
		$friendship_id = friends_get_friendship_id( $f1, $f2 );
		friends_accept_friendship( $friendship_id );


		// Withdraw friendship
		$_POST = array(
			'action'  => 'friends_remove_friend',
			'nonce'   => wp_create_nonce( 'bp_nouveau_friends' ),
			'item_id' => $f2
		);

		$this->make_ajax_call( 'friends_remove_friend' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
	}

	public function test_bp_nouveau_ajax_addremove_accept_friendship() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();

		$this->set_current_user( $f1 );

		friends_add_friend( $f1, $f2 );
		$friendship_id = friends_get_friendship_id( $f1, $f2 );

		$this->set_current_user( $f2 );

		// Accept friendship
		$_POST = array(
			'action'  => 'friends_accept_friendship',
			'nonce'   => wp_create_nonce( 'bp_nouveau_friends' ),
			'item_id' => $friendship_id
		);

		$this->make_ajax_call( 'friends_accept_friendship' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
	}

	public function test_bp_nouveau_ajax_addremove_reject_friendship() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();

		$this->set_current_user( $f1 );

		friends_add_friend( $f1, $f2 );
		$friendship_id = friends_get_friendship_id( $f1, $f2 );

		$this->set_current_user( $f2 );

		// Reject friendship
		$_POST = array(
			'action'  => 'friends_reject_friendship',
			'nonce'   => wp_create_nonce( 'bp_nouveau_friends' ),
			'item_id' => $friendship_id
		);

		$this->make_ajax_call( 'friends_reject_friendship' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
	}
}
