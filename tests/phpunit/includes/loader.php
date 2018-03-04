<?php

require_once( dirname( __FILE__ ) . '/define-constants.php' );

$multisite = (int) ( defined( 'WP_TESTS_MULTISITE') && WP_TESTS_MULTISITE );
system( WP_PHP_BINARY . ' ' . escapeshellarg( dirname( __FILE__ ) . '/install.php' ) . ' ' . escapeshellarg( WP_TESTS_CONFIG_PATH ) . ' ' . escapeshellarg( WP_TESTS_DIR ) . ' ' . $multisite );

// Bootstrap BP
require dirname( __FILE__ ) . '/../../../src/bp-loader.php';

// Bail from redirects as they throw 'headers already sent' warnings.
tests_add_filter( 'wp_redirect', '__return_false' );

require_once( dirname( __FILE__ ) . '/mock-mailer.php' );
function _bp_mock_mailer( $class ) {
	return 'BP_UnitTest_Mailer';
}
tests_add_filter( 'bp_send_email_delivery_class', '_bp_mock_mailer' );
