<?php

/**
 * Define constants needed by test suite.
 */

define( 'BP_PLUGIN_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/' );

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( dirname( __FILE__ ) ) . '/' );
}

/**
 * Determine where the WP test suite lives. Three options are supported:
 *
 * - Define a WP_DEVELOP_DIR environment variable, which points to a checkout
 *   of the develop.svn.wordpress.org repository (this is recommended)
 * - Define a WP_TESTS_DIR environment variable, which points to a checkout of
 *   WordPress test suite
 * - Assume that we are inside of a develop.svn.wordpress.org setup, and walk
 *   up the directory tree
 */
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) );
	define( 'WP_ROOT_DIR', WP_TESTS_DIR );
} else {
	// Support WP_DEVELOP_DIR, as used by some plugins
	if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
		define( 'WP_ROOT_DIR', getenv( 'WP_DEVELOP_DIR' ) );
	} else {
		define( 'WP_ROOT_DIR', dirname( dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) ) ) );
	}

	define( 'WP_TESTS_DIR', WP_ROOT_DIR . '/tests/phpunit' );
}

// Based on the tests directory, look for a config file
if ( file_exists( WP_ROOT_DIR . '/wp-tests-config.php' ) ) {
	echo "1 - The file exists";
	// Standard develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', WP_ROOT_DIR . '/wp-tests-config.php' );

// Based on the tests directory, look for a config file
} elseif ( file_exists( WP_TESTS_DIR . '/wp-tests-config.php' ) ) {
	echo "1a - The file exists";
	// Standard develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_DIR . '/wp-tests-config.php' );

} else if ( file_exists( WP_TESTS_DIR . '/wp-tests-config.php' ) ) {
	echo "2 - The file exists";
	// Legacy unit-test.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', WP_TESTS_DIR . '/wp-tests-config.php' );

} else if ( file_exists( dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' ) ) {
	echo "3 - The file exists";
	// Environment variable exists and points to tests/phpunit of
	// develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' );

} else if ( file_exists( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-tests-config.php' ) ) {
	echo "4 - The file exists";
	// Environment variable exists and points to tests/phpunit of
	// develop.svn.wordpress.org setup
	define( 'WP_TESTS_CONFIG_PATH', dirname( dirname( dirname( dirname( BP_PLUGIN_DIR ) ) ) ) . '/wp-tests-config.php' );

} else {
	var_dump(BP_PLUGIN_DIR);
	var_dump(BP_TESTS_DIR);
	var_dump(WP_TESTS_DIR);
	var_dump(WP_ROOT_DIR);
	var_dump(WP_DEVELOP_DIR);
	var_dump(WP_TESTS_CONFIG_PATH);
	// die( "wp-tests-config.php could not be found.\n" );
	define( 'WP_TESTS_CONFIG_PATH', '/tmp/wordpress/wp-tests-config.php' );
}
	var_dump(BP_PLUGIN_DIR);
	var_dump(BP_TESTS_DIR);
	var_dump(WP_TESTS_DIR);
	var_dump(WP_ROOT_DIR);
	var_dump(WP_DEVELOP_DIR);
	var_dump(WP_TESTS_CONFIG_PATH);

