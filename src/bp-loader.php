<?php
/**
 * The BuddyPress Plugin.
 *
 * BuddyPress is social networking software with a twist from the creators of WordPress.
 *
 * @package BuddyPress
 * @subpackage Main
 * @since 1.0.0
 */

/**
 * Plugin Name: BuddyPress
 * Plugin URI:  https://buddypress.org/
 * Description: BuddyPress helps site builders and WordPress developers add community features to their websites, with user profile fields, activity streams, messaging, and notifications.
 * Author:      The BuddyPress Community
 * Author URI:  https://buddypress.org/
 * Version:     2.8.2
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * This files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Required PHP version.
define( 'BP_REQUIRED_PHP_VERSION', '5.3.0' );

/**
 * The main function responsible for returning the one true BuddyPress Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $bp = buddypress(); ?>
 *
 * @return BuddyPress The one true BuddyPress Instance.
 */
function buddypress() {
	return BuddyPress::instance();
}

/**
 * Adds an admin notice to installations that don't meet BP's minimum PHP requirement.
 *
 * @since 2.8.0
 */
function bp_php_requirements_notice() {
	if ( ! current_user_can( 'update_core' ) ) {
		return;
	}

	?>

	<div id="message" class="error notice">
		<p><strong><?php esc_html_e( 'Your site does not support BuddyPress.', 'buddypress' ); ?></strong></p>
		<?php /* translators: 1: current PHP version, 2: required PHP version */ ?>
		<p><?php printf( esc_html__( 'Your site is currently running PHP version %1$s, while BuddyPress requires version %2$s or greater.', 'buddypress' ), esc_html( phpversion() ), esc_html( BP_REQUIRED_PHP_VERSION ) ); ?> <?php printf( __( 'See <a href="%s">the Codex guide</a> for more information.', 'buddypress' ), 'https://codex.buddypress.org/getting-started/buddypress-2-8-will-require-php-5-3/' ); ?></p>
		<p><?php esc_html_e( 'Please update your server or deactivate BuddyPress.', 'buddypress' ); ?></p>
	</div>

	<?php
}

if ( version_compare( phpversion(), BP_REQUIRED_PHP_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'bp_php_requirements_notice' );
	add_action( 'network_admin_notices', 'bp_php_requirements_notice' );
	return;
} else {
	require dirname( __FILE__ ) . '/class-buddypress.php';

	/*
	 * Hook BuddyPress early onto the 'plugins_loaded' action.
	 *
	 * This gives all other plugins the chance to load before BuddyPress,
	 * to get their actions, filters, and overrides setup without
	 * BuddyPress being in the way.
	 */
	if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
		add_action( 'plugins_loaded', 'buddypress', (int) BUDDYPRESS_LATE_LOAD );

	// "And now here's something we hope you'll really like!"
	} else {
		$GLOBALS['bp'] = buddypress();
	}
}
