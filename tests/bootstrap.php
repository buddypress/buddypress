<?php

define( 'BP_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . '/' );

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( __FILE__ ) . '/' );
}

/**
 * In the pre-develop.svn WP development environment, an environmental bash
 * variable would be set to run PHP Unit tests. However, this has been done
 * away with in a post-develop.svn world. We'll still check if this variable
 * is set for backwards compat.
 */
if ( getenv( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) );
	define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_DIR . '/wp-tests-config.php' );
}
else {
	define( 'WP_ROOT_DIR', dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) );
	define( 'WP_TESTS_DIR', WP_ROOT_DIR . '/tests/phpunit' );
	define( 'WP_TESTS_CONFIG_PATH', WP_ROOT_DIR . '/wp-tests-config.php' );
}

if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) )
	die( "The WordPress PHPUnit test suite could not be found.\n" );

require_once WP_TESTS_DIR . '/includes/functions.php';

function _install_and_load_buddypress() {
	require BP_TESTS_DIR . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_buddypress' );

require WP_TESTS_DIR . '/includes/bootstrap.php';

// Load the BP-specific testing tools
require BP_TESTS_DIR . '/includes/testcase.php';
