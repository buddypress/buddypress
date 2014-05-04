<?php

/**
 * Define constants needed by test suite.
 */

define( 'BP_PLUGIN_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/' );

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( dirname( __FILE__ ) ) . '/' );
}

/**
 * In the pre-develop.svn WP development environment, an environmental bash
 * variable would be set to run PHP Unit tests. However, this has been done
 * away with in a post-develop.svn world. We'll still check if this variable
 * is set for backwards compat.
 */
if ( getenv( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) );
	define( 'WP_ROOT_DIR', WP_TESTS_DIR );
} else {
	define( 'WP_ROOT_DIR', dirname( dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) ) ) );
	define( 'WP_TESTS_DIR', WP_ROOT_DIR . '/tests/phpunit' );
}

// Based on the tests directory, look for a config file
if ( file_exists( WP_ROOT_DIR . '/wp-tests-config.php' ) ) {
	// Standard develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', WP_ROOT_DIR . '/wp-tests-config.php' );

} else if ( file_exists( WP_TESTS_DIR . '/wp-tests-config.php' ) ) {
	// Legacy unit-test.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_DIR . '/wp-tests-config.php' );

} else if ( file_exists( dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' ) ) {
	// Environment variable exists and points to tests/phpunit of
	// develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' );

} else {
	die( "wp-tests-config.php could not be found.\n" );
}
