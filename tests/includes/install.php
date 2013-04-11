<?php
/**
 * Installs BuddyPress for the purpose of the unit-tests
 *
 * @todo Reuse the init/load code in init.php
 * @todo Support MULTIBLOG
 */
error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

$config_file_path = $argv[1];
$multisite = ! empty( $argv[2] );

require_once $config_file_path;
require_once dirname( $config_file_path ) . '/includes/functions.php';

// Set BP to be an active plugin
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => 'buddypress/bp-loader.php',
);
define( 'BP_ROOT_BLOG', 1 );

// Always load admin bar
tests_add_filter( 'show_admin_bar', '__return_true' );

function wp_tests_options( $value ) {
	$key = substr( current_filter(), strlen( 'pre_option_' ) );
	return $GLOBALS['wp_tests_options'][$key];
}
foreach ( array_keys( $GLOBALS['wp_tests_options'] ) as $key ) {
	tests_add_filter( 'pre_option_'.$key, 'wp_tests_options' );
}

function wp_test_bp_install( $value ) {
	return array( 'activity' => 1, 'friends' => 1, 'groups' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1, );
}
tests_add_filter( 'bp_new_install_default_components', 'wp_test_bp_install' );

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

require_once ABSPATH . '/wp-settings.php';
define( 'BP_TESTS_DB_VERSION_FILE', ABSPATH . '.bp-tests-version' );

// Check if BuddyPress has already been installed
$db_version = bp_get_db_version_raw();

if ( $db_version && file_exists( BP_TESTS_DB_VERSION_FILE ) ) {
	$file_version = file_get_contents( BP_TESTS_DB_VERSION_FILE );

	if ( $db_version == (int) $file_version )
		return;
}

echo "Installing BuddyPress...\n";

// Install BuddyPress
bp_version_updater();

file_put_contents( BP_TESTS_DB_VERSION_FILE, bp_get_db_version_raw() );
