<?php
/**
 * BuddyPress Blogs Widgets.
 *
 * @package BuddyPress
 * @subpackage BlogsWidgets
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-blogs-recent-posts-widget.php';
}

/**
 * Register the widgets for the Blogs component.
 */
function bp_blogs_register_widgets() {
	global $wpdb;

	if ( bp_is_active( 'activity' ) && bp_is_root_blog( $wpdb->blogid ) ) {
		add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Blogs_Recent_Posts_Widget");' ) );
	}
}
add_action( 'bp_register_widgets', 'bp_blogs_register_widgets' );
