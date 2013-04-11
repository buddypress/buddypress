<?php

// Install BP
$config_file_path = getenv( 'WP_TESTS_DIR' ) . '/wp-tests-config.php';
$multisite = (int) ( defined( 'WP_TESTS_MULTISITE') && WP_TESTS_MULTISITE );
system( WP_PHP_BINARY . ' ' . escapeshellarg( dirname( __FILE__ ) . '/install.php' ) . ' ' . escapeshellarg( $config_file_path ) . ' ' . $multisite );

// Bootstrap BP
require dirname( __FILE__ ) . '/../../bp-loader.php';
