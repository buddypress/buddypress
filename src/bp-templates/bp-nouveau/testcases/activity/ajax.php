<?php
/**
 * Testing Activity Ajax
 *
 * @group ajax
 */
class BP_Nouveau_Activity_Ajax_UnitTestCase extends Next_Template_Packs_Ajax_UnitTestCase {

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

	public function test_bp_nouveau_ajax_delete_activity_user_cannot() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();
		$a1 = $this->factory->activity->create( array( 'user_id' => $f1 ) );

		$this->set_current_user( $f2 );

		// Add friendship
		$_POST = array(
			'action'   => 'delete_activity',
			'_wpnonce' => wp_create_nonce( 'bp_activity_delete_link' ),
			'id'       => $a1,
		);

		$this->make_ajax_call( 'delete_activity' );

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );

		$activity = bp_activity_get( array( 'in' => $a1 ) );
		$this->assertEquals( array( $a1 ), wp_list_pluck( $activity['activities'], 'id' ) );
	}

	public function test_bp_nouveau_ajax_delete_activity_user_can() {
		$f1 = $this->factory->user->create();
		$a1 = $this->factory->activity->create( array( 'user_id' => $f1 ) );

		$this->set_current_user( $f1 );

		// Add friendship
		$_POST = array(
			'action'   => 'delete_activity',
			'_wpnonce' => wp_create_nonce( 'bp_activity_delete_link' ),
			'id'       => $a1,
		);

		$this->make_ajax_call( 'delete_activity' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );

		$activity = bp_activity_get( array( 'in' => $a1 ) );
		$this->assertTrue( empty( $activity['activities'] ) );
	}

	public function test_bp_nouveau_ajax_delete_activity_redirect_home() {
		$f1 = $this->factory->user->create();
		$a1 = $this->factory->activity->create( array( 'user_id' => $f1 ) );

		$this->set_current_user( $f1 );

		// Add friendship
		$_POST = array(
			'action'    => 'delete_activity',
			'_wpnonce'  => wp_create_nonce( 'bp_activity_delete_link' ),
			'id'        => $a1,
			'is_single' => true,
		);

		$this->make_ajax_call( 'delete_activity' );

		$response = json_decode( $this->_last_response, true );

		$this->assertSame( bp_core_get_user_domain( $f1 ), $response['data']['redirect'] );
	}

	public function test_bp_nouveau_ajax_delete_activity_comment_user_cannot() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();
		$a1 = $this->factory->activity->create( array( 'user_id' => $f1 ) );
		$c1 = $this->factory->activity->create( array(
			'user_id'           => $f2,
			'type'              => 'activity_comment',
			'item_id'           => $a1,
			'secondary_item_id' => $a1,
		) );

		$this->set_current_user( $f1 );

		// Add friendship
		$_POST = array(
			'action'     => 'delete_activity',
			'_wpnonce'   => wp_create_nonce( 'bp_activity_delete_link' ),
			'id'         => $c1,
			'is_comment' => true,
		);

		$this->make_ajax_call( 'delete_activity' );

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );

		$activity = bp_activity_get( array( 'in' => array( $a1, $c1 ), 'display_comments' => 'stream' ) );
		$this->assertEquals( array( $c1, $a1 ), wp_list_pluck( $activity['activities'], 'id' ) );
	}

	public function test_bp_nouveau_ajax_delete_activity_comment_user_can() {
		$f1 = $this->factory->user->create();
		$f2 = $this->factory->user->create();
		$a1 = $this->factory->activity->create( array( 'user_id' => $f1 ) );
		$c1 = $this->factory->activity->create( array(
			'user_id'           => $f2,
			'type'              => 'activity_comment',
			'item_id'           => $a1,
			'secondary_item_id' => $a1,
		) );

		$this->set_current_user( $f2 );

		// Add friendship
		$_POST = array(
			'action'     => 'delete_activity',
			'_wpnonce'   => wp_create_nonce( 'bp_activity_delete_link' ),
			'id'         => $c1,
			'is_comment' => true,
		);

		$this->make_ajax_call( 'delete_activity' );

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );

		$activity = bp_activity_get( array( 'in' => array( $a1, $c1 ), 'display_comments' => 'stream' ) );
		$this->assertEquals( array( $a1 ), wp_list_pluck( $activity['activities'], 'id' ) );
	}
}
