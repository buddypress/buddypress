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

/**
 * Should BuddyPress load Legacy Widgets?
 *
 * @since 10.0.0
 *
 * @return bool False if BuddyPress shouldn't load Legacy Widgets. True otherwise.
 */
function bp_core_retain_legacy_widgets() {
	$theme_supports = current_theme_supports( 'widgets-block-editor' );
	$wp_supports    = bp_is_running_wp( '5.8.0' );

	/** This filter is documented in wp-includes/widgets.php */
	$block_widgets_enabled = $theme_supports && apply_filters( 'use_widgets_block_editor', $wp_supports );

	$retain_legacy_widgets = true;
	if ( $block_widgets_enabled ) {
		$retain_legacy_widgets = false;
	}

	/**
	 * Filter here to force Legacy Widgets to be retained or not.
	 *
	 * @since 10.0.0
	 *
	 * @param bool $retain_legacy_widgets False if BuddyPress shouldn't load Legacy Widgets. True otherwise.
	 */
	return apply_filters( 'bp_core_retain_legacy_widgets', $retain_legacy_widgets );
}

/**
 * Registers the Login widget.
 *
 * @since 10.0.0
 */
function bp_core_register_login_widget() {
	register_widget( 'BP_Core_Login_Widget' );
}

/**
 * Register bp-core widgets.
 *
 * @since 1.0.0
 */
function bp_core_register_widgets() {
	add_action( 'widgets_init', 'bp_core_register_login_widget' );
}
add_action( 'bp_register_widgets', 'bp_core_register_widgets' );

/**
 * Checks whether BuddyPress should unhook Legacy Widget registrations.
 *
 * @since 10.0.0
 */
function bp_core_maybe_unhook_legacy_widgets() {
	if ( bp_core_retain_legacy_widgets() ) {
		return;
	}

	$callbacks = array(
		'BP_Core_Login_Widget'           => 'bp_core_register_login_widget',
		'BP_Core_Members_Widget'         => 'bp_members_register_members_widget',
		'BP_Core_Whos_Online_Widget'     => 'bp_members_register_whos_online_widget',
		'BP_Core_Recently_Active_Widget' => 'bp_members_register_recently_active_widget',
	);

	if ( bp_is_active( 'friends' ) ) {
		$callbacks['BP_Core_Friends_Widget'] = 'bp_friends_register_friends_widget';
	}

	if ( bp_is_active( 'groups' ) ) {
		$callbacks['BP_Groups_Widget'] = 'bp_groups_register_groups_widget';
	}

	if ( bp_is_active( 'messages' ) ) {
		$callbacks['BP_Messages_Sitewide_Notices_Widget'] = 'bp_messages_register_sitewide_notices_widget';
	}

	if ( bp_is_active( 'blogs' ) && bp_is_active( 'activity' ) && bp_is_root_blog() ) {
		$callbacks['BP_Blogs_Recent_Posts_Widget'] = 'bp_blogs_register_recent_posts_widget';
	}

	foreach ( $callbacks as $widget_id => $callback ) {
		$widget_base = strtolower( $widget_id );

		// If there's an active widget, we need to carry on loading it.
		if ( is_active_widget( false, false, $widget_base ) ) {
			continue;
		}

		remove_action( 'widgets_init', $callback );
	}
}
add_action( 'widgets_init', 'bp_core_maybe_unhook_legacy_widgets', 0 );
