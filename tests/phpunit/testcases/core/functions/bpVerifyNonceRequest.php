<?php

/**
 * @group core
 * @group functions
 * @group bp_verify_nonce_request
 */
class BP_Tests_Core_Functions_BPVerifyNonceRequest extends BP_UnitTestCase {
	private $http_host = '';
	private $server_port = '';
	private $request_uri = '';

	public function setUp() {
		parent::setUp();

		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$this->http_host = $_SERVER['HTTP_HOST'];
		}

		if ( isset( $_SERVER['SERVER_PORT'] ) ) {
			$this->server_port = $_SERVER['SERVER_PORT'];
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$this->request_uri = $_SERVER['REQUEST_URI'];
		}
	}

	public function tearDown() {
		if ( '' !== $this->http_host ) {
			$_SERVER['HTTP_HOST'] = $this->http_host;
		}

		if ( '' !== $this->server_port ) {
			$_SERVER['SERVER_PORT'] = $this->server_port;
		}

		if ( '' !== $this->request_uri ) {
			$_SERVER['REQUEST_URI'] = $this->request_uri;
		}

		parent::tearDown();
	}

	public function test_bp_verify_nonce_request_with_port_in_home_url_and_wordpress_installed_in_subdirectory() {
		// fake various $_SERVER parameters
		$host = explode( ':', $_SERVER['HTTP_HOST'] );
		$_SERVER['HTTP_HOST'] = $host[0] . ':80';
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['REQUEST_URI'] = '/wordpress/';

		// add port number and subdirecotry to home URL for testing
		add_filter( 'home_url', array( $this, 'add_port_and_subdirectory_to_home_url' ), 10, 3 );

		// test bp_verify_nonce_request()
		$action = 'verify-this';
		$_REQUEST[$action] = wp_create_nonce( $action );
		$test = bp_verify_nonce_request( $action, $action );

		// clean up!
		remove_filter( 'home_url', array( $this, 'add_port_and_subdirectory_to_home_url' ), 10 );
		unset( $_REQUEST[$action] );

		// assert!
		$this->assertSame( 1, $test );
	}

	/**
	 * Add port 80 and /wordpress/ subdirectory to home URL.
	 *
	 * @param string      $url         The complete home URL including scheme and path.
	 * @param string      $path        Path relative to the home URL. Blank string if no path is specified.
	 * @param string|null $orig_scheme Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
	 * @return string
	 */
	public function add_port_and_subdirectory_to_home_url( $url, $path, $scheme ) {
		$home = parse_url( get_option( 'home' ) );
		$home_path = isset( $home['path'] ) ? $home['path'] : '';
		return $scheme . '://' . $home['host'] . ':80' . $home_path . '/wordpress' . $path;
	}
}
