<?php
/**
 * Minimal Akismet stand-in, so BP_Akismet requests run without the Akismet plugin or the network.
 */
if ( ! class_exists( 'Akismet' ) ) {
	class Akismet {
		public static function is_test_mode() {
			return true;
		}

		public static function http_post( $request, $path ) {
			return array( array(), 'false' );
		}
	}
}

/**
 * @group activity
 * @group akismet
 */
class BP_Tests_Akismet extends BP_UnitTestCase {
	protected $http_referer = null;

	public function set_up() {
		parent::set_up();

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$this->http_referer = $_SERVER['HTTP_REFERER'];
		}

		$_SERVER['HTTP_REFERER'] = '';
		add_filter( 'pre_http_request', array( $this, 'short_circuit_akismet_request' ), 10, 3 );
		wp_clear_scheduled_hook( 'bp_activity_akismet_delete_old_metadata' );
	}

	public function tear_down() {
		wp_clear_scheduled_hook( 'bp_activity_akismet_delete_old_metadata' );
		unset( $_SERVER['HTTP_REFERER'] );

		if ( null !== $this->http_referer ) {
			$_SERVER['HTTP_REFERER'] = $this->http_referer;
		}

		parent::tear_down();
	}

	/**
	 * @ticket BP7904
	 */
	public function test_metadata_cleanup_is_hooked_without_akismet_loaded() {
		if ( defined( 'AKISMET_VERSION' ) ) {
			$this->markTestSkipped( 'This test needs a site where Akismet is not loaded.' );
		}

		$this->assertSame( 10, has_action( 'bp_activity_akismet_delete_old_metadata', 'bp_activity_akismet_delete_old_metadata' ) );
	}

	/**
	 * @ticket BP7904
	 */
	public function test_metadata_cleanup_is_scheduled_on_root_blog_when_called_on_secondary_blog() {
		$this->skipWithoutMultisite();

		$blog_id = self::factory()->blog->create();
		$akismet = new BP_Akismet();

		$this->assertFalse( wp_next_scheduled( 'bp_activity_akismet_delete_old_metadata' ) );

		switch_to_blog( $blog_id );
		$akismet->send_akismet_request( array( 'comment_content' => 'Hello' ) );
		$current_blog_after_request  = get_current_blog_id();
		$scheduled_on_secondary_blog = wp_next_scheduled( 'bp_activity_akismet_delete_old_metadata' );
		restore_current_blog();

		$this->assertSame( $blog_id, $current_blog_after_request, 'The request should restore the blog it was called from.' );
		$this->assertFalse( $scheduled_on_secondary_blog, 'The cleanup event should not be scheduled on a secondary blog.' );
		$this->assertNotFalse( wp_next_scheduled( 'bp_activity_akismet_delete_old_metadata' ), 'The cleanup event should be scheduled on the root blog.' );
	}

	/**
	 * @ticket BP7904
	 */
	public function test_metadata_cleanup_is_scheduled_when_called_on_root_blog() {
		$akismet = new BP_Akismet();

		$this->assertFalse( wp_next_scheduled( 'bp_activity_akismet_delete_old_metadata' ) );

		$akismet->send_akismet_request( array( 'comment_content' => 'Hello' ) );

		$this->assertNotFalse( wp_next_scheduled( 'bp_activity_akismet_delete_old_metadata' ), 'The cleanup event should be scheduled on the root blog.' );
	}

	public function short_circuit_akismet_request( $response, $args, $url ) {
		if ( false === strpos( $url, 'akismet.com' ) ) {
			return $response;
		}

		return array(
			'headers'  => array(),
			'body'     => 'false',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}
}
