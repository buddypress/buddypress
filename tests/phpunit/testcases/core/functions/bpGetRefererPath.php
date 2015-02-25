<?php

/**
 * @group core
 * @group functions
 * @group bp_get_referer_path
 */
class BP_Tests_Core_Functions_BPGetRefererPath extends BP_UnitTestCase {
	private $_wp_http_referer = '';
	private $http_referer = '';

	public function setUp() {
		parent::setUp();

		$this->_wp_http_referer = '';
		$this->http_referer = '';

		if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
			$this->_wp_http_referer = $_REQUEST['_wp_http_referer'];
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$this->http_referer = $_SERVER['HTTP_REFERER'];
		}
	}

	public function tearDown() {
		if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
			unset( $_REQUEST['_wp_http_referer'] );
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			unset( $_SERVER['HTTP_REFERER'] );
		}

		if ( $this->_wp_http_referer ) {
			$_REQUEST['_wp_http_referer'] = $this->_wp_http_referer;
		}

		if ( $this->http_referer ) {
			$_SERVER['HTTP_REFERER'] = $this->http_referer;
		}

		parent::tearDown();
	}

	public function test_from__wp_http_referer_fully_qualified_uri() {
		$home = get_option( 'home' );
		$_REQUEST['_wp_http_referer'] = trailingslashit( $home ) . 'foo/';
		$found = bp_get_referer_path();
		$this->assertSame( '/foo/', bp_get_referer_path() );
	}

	public function test_from__wp_http_referer_absolute_path() {
		$_REQUEST['_wp_http_referer'] = '/foo/';
		$found = bp_get_referer_path();
		$this->assertSame( '/foo/', bp_get_referer_path() );
	}

	public function test_from_server_request_uri_fully_qualified_uri() {
		$home = get_option( 'home' );
		$_SERVER['HTTP_REFERER'] = trailingslashit( $home ) . 'foo/';
		$found = bp_get_referer_path();
		$this->assertSame( '/foo/', bp_get_referer_path() );
	}

	public function test_from_server_request_uri_absolute_path() {
		$_SERVER['HTTP_REFERER'] = '/foo/';
		$found = bp_get_referer_path();
		$this->assertSame( '/foo/', bp_get_referer_path() );
	}

	public function test__wp_http_referer_should_take_precedence_over_server_superglobal() {
		$_SERVER['HTTP_REFERER'] = '/foo/';
		$_REQUEST['_wp_http_referer'] = '/bar/';
		$found = bp_get_referer_path();
		$this->assertSame( '/bar/', bp_get_referer_path() );
	}
}
