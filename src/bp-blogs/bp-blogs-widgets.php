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

/**
 * Registers the Recent Posts Legacy Widget.
 *
 * @since 10.0.0
 */
function bp_blogs_register_recent_posts_widget() {
	register_widget( 'BP_Blogs_Recent_Posts_Widget' );
}

/**
 * Register the widgets for the Blogs component.
 */
function bp_blogs_register_widgets() {
	global $wpdb;

	if ( bp_is_active( 'activity' ) && bp_is_root_blog( $wpdb->blogid ) ) {
		add_action( 'widgets_init', 'bp_blogs_register_recent_posts_widget' );
	}
}
add_action( 'bp_register_widgets', 'bp_blogs_register_widgets' );
