<?php
const WP_TESTS_PHPUNIT_POLYFILLS_PATH = __DIR__ . '/../../vendor/yoast/phpunit-polyfills';

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

/**
 * Set component visibility.
 *
 * @param bool $visibility Visibility.
 */
function toggle_component_visibility( $visibility = true ) {
	$visibility = $visibility ? 'members' : 'anyone';

	update_option(
		'_bp_community_visibility',
		array(
			'global'   => $visibility,
			'activity' => $visibility,
			'members'  => $visibility,
			'groups'   => $visibility,
			'blogs'    => $visibility,
		)
	);
}
