<?php
const WP_TESTS_PHPUNIT_POLYFILLS_PATH = __DIR__ . '/../../vendor/yoast/phpunit-polyfills';

if ( defined( 'BP_USE_WP_ENV_TESTS' ) ) {
	// wp-env setup.
	define( 'WP_TESTS_CONFIG_FILE_PATH', dirname( __FILE__ ) . '/assets/phpunit-wp-config.php' );
	define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_CONFIG_FILE_PATH );

	// Use WP PHPUnit.
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/wp-phpunit/wp-phpunit/__loaded.php';
}

require( dirname( __FILE__ ) . '/includes/define-constants.php' );

if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

function _install_and_load_buddypress() {
	require BP_TESTS_DIR . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_buddypress' );

require WP_TESTS_DIR . '/includes/bootstrap.php';

// Load the BP-specific testing tools
require BP_TESTS_DIR . '/includes/testcase.php';
require BP_TESTS_DIR . '/includes/testcase-emails.php';
