<?php
/**
 * @group core
 * @group template
 * @group bp_is_site_home
 * @ticket BP9330
 */
class BP_Tests_Core_Template_BpIsSiteHome extends BP_UnitTestCase {
	/**
	 * @dataProvider homepage_urls
	 */
	public function test_homepage_url_comparison( $home, $requested, $expected, $query = null ) {
		$home_filter = function () use ( $home ) {
			return $home;
		};

		$request_filter = function () use ( $requested ) {
			return $requested;
		};

		add_filter( 'home_url', $home_filter );
		add_filter( 'bp_get_requested_url', $request_filter );

		try {
			$this->assertSame( $expected, bp_is_site_home( $query ) );
		} finally {
			remove_filter( 'home_url', $home_filter );
			remove_filter( 'bp_get_requested_url', $request_filter );
		}
	}

	/**
	 * @dataProvider homepage_queries
	 */
	public function test_homepage_query_comparison( $query_args, $expected ) {
		$show_on_front = get_option( 'show_on_front' );
		$page_on_front = get_option( 'page_on_front' );
		$request_filter = function () {
			return home_url( '/?anything=value' );
		};

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 123 );
		add_filter( 'bp_get_requested_url', $request_filter );

		try {
			$query = new WP_Query();
			$query->parse_query( $query_args );
			$this->assertSame( $expected, bp_is_site_home( $query ) );
		} finally {
			remove_filter( 'bp_get_requested_url', $request_filter );
			update_option( 'show_on_front', $show_on_front );
			update_option( 'page_on_front', $page_on_front );
		}
	}

	public static function homepage_queries() {
		return array(
			'front page' => array( array( 'page_id' => 123 ), true ),
			'other page' => array( array( 'page_id' => 456 ), false ),
			'post'       => array( array( 'p' => 456 ), false ),
			'search'     => array( array( 's' => 'example' ), false ),
			'preview'    => array( array( 'p' => 456, 'preview' => true ), false ),
			'page preview' => array( array( 'page_id' => 456, 'preview' => true ), false ),
		);
	}

	public static function homepage_urls() {
		return array(
			'home'         => array( 'https://example.org/', 'https://example.org/', true ),
			'query'        => array( 'https://example.org/', 'https://example.org/?anything=value', true ),
			'tracking parameters' => array( 'https://example.org/', 'https://example.org/?utm_source=newsletter&ref=test', true ),
			'encoded parameter' => array( 'https://example.org/', 'https://example.org/?anything=hello%20world', true ),
			'fragment'     => array( 'https://example.org/', 'https://example.org/#section', true ),
			'both'         => array( 'https://example.org/', 'https://example.org/?anything=value#section', true ),
			'path'         => array( 'https://example.org/', 'https://example.org/other/', false ),
			'host'         => array( 'https://example.org/', 'https://other.example.org/', false ),
			'scheme'       => array( 'https://example.org/', 'http://example.org/', false ),
			'port'         => array( 'https://example.org/', 'https://example.org:8080/', false ),
			'subdirectory' => array( 'https://example.org/site/', 'https://example.org/site/?anything=value', true ),
			'wrong root'   => array( 'https://example.org/site/', 'https://example.org/?anything=value', false ),
		);
	}

	/**
	 * @dataProvider customizer_urls
	 */
	public function test_customizer_url_comparison( $email, $requested, $expected, $query_args = null ) {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';

		$old_customize = isset( $GLOBALS['wp_customize'] ) ? $GLOBALS['wp_customize'] : null;
		$old_get       = $_GET;

		// Set preview state without registering the manager's request-wide hooks.
		$reflection = new ReflectionClass( 'WP_Customize_Manager' );
		$customize  = $reflection->newInstanceWithoutConstructor();
		$previewing = $reflection->getProperty( 'previewing' );
		$previewing->setAccessible( true );
		$previewing->setValue( $customize, true );

		$GLOBALS['wp_customize'] = $customize;
		unset( $_GET['bp_customizer'] );
		if ( $email ) {
			$_GET['bp_customizer'] = 'email';
		}

		$show_on_front = get_option( 'show_on_front' );
		$page_on_front = get_option( 'page_on_front' );

		try {
			$query = null;
			if ( null !== $query_args ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', 123 );
				$query = new WP_Query();
				$query->parse_query( $query_args );
			}

			$this->test_homepage_url_comparison( 'https://example.org/', $requested, $expected, $query );
		} finally {
			update_option( 'show_on_front', $show_on_front );
			update_option( 'page_on_front', $page_on_front );
			$GLOBALS['wp_customize'] = $old_customize;
			$_GET                   = $old_get;
		}
	}

	public static function customizer_urls() {
		return array(
			'ordinary front-page query' => array( false, 'http://example.org/?customize_changeset_uuid=example', true, array( 'page_id' => 123 ) ),
			'ordinary other-page query' => array( false, 'https://example.org/?customize_changeset_uuid=example', false, array( 'page_id' => 456 ) ),
			'ordinary post query' => array( false, 'https://example.org/?customize_changeset_uuid=example', false, array( 'p' => 456 ) ),
			'email front-page query' => array( true, 'https://example.org/?bp_customizer=email', false, array( 'page_id' => 123 ) ),
			'email exact front-page query' => array( true, 'https://example.org/', true, array( 'page_id' => 123 ) ),
			'email other-page query' => array( true, 'https://example.org/', false, array( 'page_id' => 456 ) ),
			'ordinary preview' => array( false, 'http://example.org/?customize_changeset_uuid=example', true ),
			'preview path'     => array( false, 'https://example.org/other/?customize_changeset_uuid=example', false ),
			'email preview'    => array( true, 'https://example.org/?bp_customizer=email', false ),
			'email exact URL'  => array( true, 'https://example.org/', true ),
			'email tracking parameters' => array( true, 'https://example.org/?bp_customizer=email&utm_source=newsletter', false ),
		);
	}
}
