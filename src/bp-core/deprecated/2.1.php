<?php
/**
 * Deprecated functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated 2.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register (not enqueue) scripts that used to be used by BuddyPress.
 *
 * @since 2.1.0
 */
function bp_core_register_deprecated_scripts() {
	// Scripts undeprecated as of 2.5.0.
}

/**
 * Register (not enqueue) styles that used to be used by BuddyPress.
 *
 * @since 2.1.0
 */
function bp_core_register_deprecated_styles() {
	// Scripts undeprecated as of 2.5.0.
}

/** BuddyBar *****************************************************************/

/**
 * Add a Sites menu to the BuddyBar.
 *
 * @since 1.0.0
 * @deprecated 2.1.0
 *
 * @return false|null Returns false on failure. Otherwise echoes the menu item.
 */
function bp_adminbar_blogs_menu() {
}

/**
 * If user has upgraded to 1.6 and chose to retain their BuddyBar, offer then a switch to change over
 * to the WP Toolbar.
 *
 * @since 1.6.0
 * @deprecated 2.1.0
 */
function bp_admin_setting_callback_force_buddybar() {
}


/**
 * Sanitization for _bp_force_buddybar
 *
 * If upgraded to 1.6 and you chose to keep the BuddyBar, a checkbox asks if you want to switch to
 * the WP Toolbar. The option we store is 1 if the BuddyBar is forced on, so we use this function
 * to flip the boolean before saving the intval.
 *
 * @since 1.6.0
 * @deprecated 2.1.0
 * @access Private
 */
function bp_admin_sanitize_callback_force_buddybar( $value = false ) {
	return $value ? 0 : 1;
}

/**
 * Wrapper function for rendering the BuddyBar.
 *
 * @return false|null Returns false if the BuddyBar is disabled.
 * @deprecated 2.1.0
 */
function bp_core_admin_bar() {
}

/**
 * Output the BuddyBar logo.
 *
 * @deprecated 2.1.0
 */
function bp_adminbar_logo() {
}

/**
 * Output the "Log In" and "Sign Up" names to the BuddyBar.
 *
 * Visible only to visitors who are not logged in.
 *
 * @deprecated 2.1.0
 *
 * @return false|null Returns false if the current user is logged in.
 */
function bp_adminbar_login_menu() {
}

/**
 * Output the My Account BuddyBar menu.
 *
 * @deprecated 2.1.0
 *
 * @return false|null Returns false on failure.
 */
function bp_adminbar_account_menu() {
}

function bp_adminbar_thisblog_menu() {
}

/**
 * Output the Random BuddyBar menu.
 *
 * Not visible for logged-in users.
 *
 * @deprecated 2.1.0
 */
function bp_adminbar_random_menu() {
}

/**
 * Enqueue the BuddyBar CSS.
 *
 * @deprecated 2.1.0
 */
function bp_core_load_buddybar_css() {
}

/**
 * Add menu items to the BuddyBar.
 *
 * @since 1.0.0
 *
 * @deprecated 2.1.0
 */
function bp_groups_adminbar_admin_menu() {
}

/**
 * Add the Notifications menu to the BuddyBar.
 *
 * @deprecated 2.1.0
 */
function bp_adminbar_notifications_menu() {
}

/**
 * Add the Blog Authors menu to the BuddyBar (visible when not logged in).
 *
 * @deprecated 2.1.0
 */
function bp_adminbar_authors_menu() {
}

/**
 * Add a member admin menu to the BuddyBar.
 *
 * Adds an Toolbar menu to any profile page providing site moderator actions
 * that allow capable users to clean up a users account.
 *
 * @deprecated 2.1.0
 */
function bp_members_adminbar_admin_menu() {
}

/**
 * Create the Notifications menu for the BuddyBar.
 *
 * @since 1.9.0
 * @deprecated 2.1.0
 */
function bp_notifications_buddybar_menu() {
}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since 1.6.0
 *
 * @deprecated 2.1.0
 */
function bp_blogs_subdomain_base() {
	_deprecated_function( __FUNCTION__, '2.1', 'bp_signup_subdomain_base()' );
	echo bp_signup_get_subdomain_base();
}

/**
 * Return the base URL for subdomain installations of WordPress Multisite.
 *
 * @since 1.6.0
 *
 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
 *
 * @deprecated 2.1.0
 */
function bp_blogs_get_subdomain_base() {
	_deprecated_function( __FUNCTION__, '2.1', 'bp_signup_get_subdomain_base()' );
	return bp_signup_get_subdomain_base();
}

/**
 * Allegedly output an avatar upload form, but it hasn't done that since 2009.
 *
 * @since 1.0.0
 * @deprecated 2.1.0
 */
function bp_avatar_upload_form() {
	_deprecated_function(__FUNCTION__, '2.1', 'No longer used' );
}

