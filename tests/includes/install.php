<?php
/**
 * Installs BuddyPress for the purpose of the unit-tests
 *
 * @todo Reuse the init/load code in init.php
 * @todo Support MULTIBLOG
 */
error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

$config_file_path = $argv[1];
$tests_dir_path = $argv[2];
$multisite = ! empty( $argv[3] );

require_once $config_file_path;
require_once $tests_dir_path . '/includes/functions.php';

function _load_buddypress() {
	require dirname( dirname( dirname( __FILE__ ) ) ) . '/bp-loader.php';
}
tests_add_filter( 'muplugins_loaded', '_load_buddypress' );

define( 'BP_PLUGIN_DIR', dirname( dirname( dirname( __FILE__ ) ) ) . '/' );
define( 'BP_ROOT_BLOG', 1 );

// Always load admin bar
tests_add_filter( 'show_admin_bar', '__return_true' );

function wp_test_bp_install( $value ) {
	return array( 'activity' => 1, 'blogs' => 1, 'friends' => 1, 'groups' => 1, 'members' => 1, 'messages' => 1, 'notifications' => 1, 'settings' => 1, 'xprofile' => 1, );
}
tests_add_filter( 'bp_new_install_default_components', 'wp_test_bp_install' );

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

require_once ABSPATH . '/wp-settings.php';

echo "Installing BuddyPress...\n";

// Make sure that BP has been cleaned from all blogs before reinstalling
$blogs = is_multisite() ? $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ) : array( 1 );
foreach ( $blogs as $blog ) {
	if ( is_multisite() ) {
		switch_to_blog( $blog );
	}

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%bp%'" );

	if ( is_multisite() ) {
		restore_current_blog();
	}
}

$wpdb->query( 'SET storage_engine = INNODB' );
$wpdb->select( DB_NAME, $wpdb->dbh );

// Install BuddyPress
bp_version_updater();
