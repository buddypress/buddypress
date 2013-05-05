<?php

define( 'BP_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . '/' );

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( __FILE__ ) . '/' );
}

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _install_and_load_buddypress() {
	require BP_TESTS_DIR . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_buddypress' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

// Load the BP-specific testing tools
require BP_TESTS_DIR . '/includes/testcase.php';
