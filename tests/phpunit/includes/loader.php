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

/**
 * Load up component action and screen code.
 *
 * In BuddyPress, this is loaded conditionally, but PHPUnit needs all files
 * loaded at the same time to prevent weird load order issues.
 */
$components = array( 'activity', 'blogs', 'friends', 'groups', 'members', 'messages', 'notifications', 'settings', 'xprofile' );
foreach ( $components as $component ) {
	add_action( "bp_{$component}_includes", function() use ( $component ) {
		$dirs = array(
			buddypress()->plugin_dir . 'bp-' . $component . '/actions/',
			buddypress()->plugin_dir . 'bp-' . $component . '/screens/',
		);

		foreach ( $dirs as $dir ) {
			foreach ( glob( $dir . "*.php" ) as $file ) {
				require $file;
			}
		}
	} );
}