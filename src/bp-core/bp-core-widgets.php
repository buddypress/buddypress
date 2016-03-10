<?php
/**
 * BuddyPress Core Component Widgets.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-core-login-widget.php';
}

/**
 * Register bp-core widgets.
 *
 * @since 1.0.0
 */
function bp_core_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Login_Widget");') );
}
add_action( 'bp_register_widgets', 'bp_core_register_widgets' );
