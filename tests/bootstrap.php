<?php

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _install_and_load_buddypress() {
	require dirname( __FILE__ ) . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_install_and_load_buddypress' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

// Load the BP-specific testing tools
require dirname( __FILE__ ) . '/includes/testcase.php';
