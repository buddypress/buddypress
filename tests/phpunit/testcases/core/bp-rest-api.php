<?php
/**
 * @group core
 */

class BP_Tests_REST_API extends BP_UnitTestCase {

	public function test_bp_rest_is_plugin_active() {
		$this->assertFalse( bp_rest_is_plugin_active() );
		$this->assertTrue( bp_rest_in_buddypress() );
		$this->assertTrue( bp_rest_api_is_available() );

		function bp_rest() {
			return true;
		}

		add_action( 'bp_rest_api_init', 'bp_rest' );

		$this->assertTrue( bp_rest_is_plugin_active() );
		$this->assertFalse( bp_rest_in_buddypress() );
		$this->assertTrue( bp_rest_api_is_available() );

		remove_action( 'bp_rest_api_init', 'bp_rest' );

		add_filter( 'bp_rest_api_is_available', '__return_false' );

		$this->assertFalse( bp_rest_api_is_available() );

		remove_filter( 'bp_rest_api_is_available', '__return_false' );
	}

	public function test_bp_rest_namespace() {
		$fake_user_id = 150;

		$this->assertEquals( 'buddypress', bp_rest_namespace() );
		$this->assertStringContainsString(
			'buddypress/v2/members/' . $fake_user_id,
			bp_rest_get_object_url( $fake_user_id, 'members' )
		);

		$callback = fn () => 'foo';

		add_filter( 'bp_rest_namespace', $callback );

		$this->assertEquals( 'foo', bp_rest_namespace() );
		$this->assertStringContainsString(
			'foo/v2/members/' . $fake_user_id,
			bp_rest_get_object_url( $fake_user_id, 'members' )
		);

		remove_filter( 'bp_rest_namespace', $callback );
	}

	public function test_bp_rest_version() {
		$fake_user_id = 150;

		$this->assertEquals( 'v2', bp_rest_version() );
		$this->assertStringContainsString(
			'v2/members/' . $fake_user_id,
			bp_rest_get_object_url( $fake_user_id, 'members' )
		);

		$callback = fn () => 'v3';

		add_filter( 'bp_rest_version', $callback );

		$this->assertEquals( 'v3', bp_rest_version() );
		$this->assertStringContainsString(
			'v3/members/' . $fake_user_id,
			bp_rest_get_object_url( $fake_user_id, 'members' )
		);

		remove_filter( 'bp_rest_version', $callback );
	}

	public function test_rest_request_to_v1() {
		$request = new WP_REST_Request( 'GET', '/buddypress/v1/members/150' );
		$request->set_param( 'context', 'view' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status(), 'v1 endpoint should return 404 since it is not available.' );
		$this->assertSame( $data['message'], 'No route was found matching the URL and request method.' );
	}
}
