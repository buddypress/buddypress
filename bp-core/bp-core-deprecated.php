<?php

/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.3
 */

/** Sign up *******************************************************************/

function bp_core_screen_signup() {
	_deprecated_function( 'bp_core_screen_signup', '1.3', 'bp_users_screen_signup' );
	bp_users_screen_signup();
}

function bp_core_screen_activation() {
	_deprecated_function( 'bp_core_screen_activation', '1.3', 'bp_users_screen_activation' );
	bp_users_screen_activation();
}

function bp_core_flush_illegal_names() {
	_deprecated_function( 'bp_core_flush_illegal_names', '1.3', 'bp_users_flush_illegal_names' );
	bp_users_flush_illegal_names();
}

function bp_core_illegal_names( $value = '', $oldvalue = '' ) {
	_deprecated_function( 'bp_core_illegal_names', '1.3', 'bp_users_illegal_names' );
	bp_users_illegal_names( $value, $oldvalue );
}

function bp_core_validate_user_signup( $user_name, $user_email ) {
	_deprecated_function( 'bp_core_validate_user_signup', '1.3', 'bp_users_validate_user_signup' );
	bp_users_validate_user_signup( $user_name, $user_email );
}

function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	_deprecated_function( 'bp_core_validate_blog_signup', '1.3', 'bp_users_validate_blog_signup' );
	bp_users_validate_blog_signup( $blog_url, $blog_title );
}

function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	_deprecated_function( 'bp_core_signup_user', '1.3', 'bp_users_signup_user' );
	bp_users_signup_user( $user_login, $user_password, $user_email, $usermeta );
}

function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	_deprecated_function( 'bp_core_signup_blog', '1.3', 'bp_users_signup_blog' );
	bp_users_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta );
}

function bp_core_activate_signup( $key ) {
	_deprecated_function( 'bp_core_activate_signup', '1.3', 'bp_users_activate_signup' );
	bp_users_activate_signup( $key );
}

function bp_core_new_user_activity( $user ) {
	_deprecated_function( 'bp_core_new_user_activity', '1.3', 'bp_users_new_user_activity' );
	bp_users_new_user_activity( $user );
}

function bp_core_map_user_registration( $user_id ) {
	_deprecated_function( 'bp_core_map_user_registration', '1.3', 'bp_users_map_user_registration' );
	bp_users_map_user_registration( $user_id );
}

function bp_core_signup_avatar_upload_dir() {
	_deprecated_function( 'bp_core_signup_avatar_upload_dir', '1.3', 'bp_users_signup_avatar_upload_dir' );
	bp_users_signup_avatar_upload_dir();
}

function bp_core_signup_send_validation_email( $user_id, $user_email, $key ) {
	_deprecated_function( 'bp_core_signup_send_validation_email', '1.3', 'bp_users_signup_send_validation_email' );
	bp_users_signup_send_validation_email( $user_id, $user_email, $key );
}

function bp_core_signup_disable_inactive( $auth_obj, $username ) {
	_deprecated_function( 'bp_core_signup_disable_inactive', '1.3', 'bp_users_signup_disable_inactive' );
	bp_users_signup_disable_inactive( $auth_obj, $username );
}

function bp_core_wpsignup_redirect() {
	_deprecated_function( 'bp_core_wpsignup_redirect', '1.3', 'bp_users_wpsignup_redirect' );
	bp_users_wpsignup_redirect();
}

/** Settings ******************************************************************/

function bp_core_add_settings_nav() {
	_deprecated_function( 'bp_core_add_settings_nav', '1.3', 'bp_settings_add_settings_nav' );
	bp_settings_add_settings_nav();
}

function bp_core_can_edit_settings() {
	_deprecated_function( 'bp_core_can_edit_settings', '1.3', 'bp_users_can_edit_settings' );
	bp_users_can_edit_settings();
}

function bp_core_screen_general_settings() {
	_deprecated_function( 'bp_core_screen_general_settings', '1.3', 'bp_settings_screen_general_settings' );
	bp_settings_screen_general_settings();
}

function bp_core_screen_general_settings_title() {
	_deprecated_function( 'bp_core_screen_general_settings_title', '1.3', 'bp_settings_screen_general_settings' );
}

function bp_core_screen_general_settings_content() {
	_deprecated_function( 'bp_core_screen_general_settings_content', '1.3', 'bp_settings_screen_general_settings' );
}

function bp_core_screen_notification_settings() {
	_deprecated_function( 'bp_core_screen_notification_settings', '1.3', 'bp_settings_screen_notification_settings' );
	bp_settings_screen_notification_settings();
}

function bp_core_screen_notification_settings_title() {
	_deprecated_function( 'bp_core_screen_notification_settings_title', '1.3', 'bp_settings_screen_notification_settings' );
}

function bp_core_screen_notification_settings_content() {
	_deprecated_function( 'bp_core_screen_notification_settings_content', '1.3', 'bp_settings_screen_notification_settings' );
}

function bp_core_screen_delete_account() {
	_deprecated_function( 'bp_core_screen_delete_account', '1.3', 'bp_settings_screen_delete_account' );
	bp_settings_screen_delete_account();
}

function bp_core_screen_delete_account_title() {
	_deprecated_function( 'bp_core_screen_delete_account_title', '1.3', 'bp_settings_screen_delete_account' );
}

function bp_core_screen_delete_account_content() {
	_deprecated_function( 'bp_core_screen_delete_account_content', '1.3', 'bp_settings_screen_delete_account' );
}

/** Notifications *************************************************************/

function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = false, $date_notified = false ) {
	_deprecated_function( 'bp_core_add_notification', '1.3', 'bp_users_add_notification' );
	bp_users_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id, $date_notified );
}

function bp_core_delete_notification( $id ) {
	_deprecated_function( 'bp_core_delete_notification', '1.3', 'bp_users_delete_notification' );
	bp_users_delete_notification( $id );
}

function bp_core_get_notification( $id ) {
	_deprecated_function( 'bp_core_get_notification', '1.3', 'bp_users_get_notification' );
	bp_users_get_notification( $id );
}

function bp_core_get_notifications_for_user( $user_id ) {
	_deprecated_function( 'bp_core_get_notifications_for_user', '1.3', 'bp_users_get_notifications_for_user' );
	bp_users_get_notifications_for_user( $user_id );
}

function bp_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {
	_deprecated_function( 'bp_core_delete_notifications_by_type', '1.3', 'bp_users_delete_notifications_by_type' );
	bp_users_delete_notifications_by_type( $user_id, $component_name, $component_action );
}

function bp_core_delete_notifications_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	_deprecated_function( 'bp_core_delete_notifications_for_user_by_item_id', '1.3', 'bp_users_delete_notifications_by_item_id' );
	bp_users_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
	_deprecated_function( 'bp_core_delete_all_notifications_by_type', '1.3', 'bp_users_delete_all_notifications_by_type' );
	bp_users_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
	_deprecated_function( 'bp_core_delete_notifications_from_user', '1.3', 'bp_users_delete_notifications_from_user' );
	bp_users_delete_notifications_from_user( $user_id, $component_name, $component_action );
}

function bp_core_check_notification_access( $user_id, $notification_id ) {
	_deprecated_function( 'bp_core_check_notification_access', '1.3', 'bp_users_check_notification_access' );
	bp_users_check_notification_access( $user_id, $notification_id );
}

/** Core **********************************************************************/

/**
 * This function originally let plugins add support for pages in the root of the install.
 * These pages are now handled by actual WordPress pages so this function is deprecated.
 * It now simply facilitates backwards compatibility by adding a WP page if the plugin has not been
 * updated to do so.
 *
 * @deprecated 1.3
 * @deprecated Use wp_insert_post() to create a page
 * @package BuddyPress Core
 * @param $slug str The slug of the component
 * @global $bp BuddyPress global settings
 */
function bp_core_add_root_component( $slug ) {
	global $bp, $bp_pages;

	_deprecated_function( __FUNCTION__, '1.3', 'wp_insert_post()' );
	if ( empty( $bp_pages ) )
		$bp_pages = bp_core_get_page_names();

	$match = false;

	// Check if the slug is registered in the $bp->pages global
	foreach ( (array)$bp_pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug )
			$match = true;
	}

	// If there was no match, add a page for this root component
	if ( empty( $match ) ) {
		$bp->add_root[] = $slug;
		add_action( 'bp_init', 'bp_core_create_root_component_page' );
	}
}

function bp_core_create_root_component_page() {
	global $bp;

	$new_page_ids = array();

	foreach ( (array)$bp->add_root as $slug )
		$new_page_ids[$slug] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $slug ), 'post_status' => 'publish', 'post_type' => 'page' ) );

	$page_ids = bp_core_get_page_meta();
	$page_ids = (array) $page_ids;
	$page_ids = array_merge( (array) $new_page_ids, (array) $page_ids );

	bp_core_update_page_meta( $page_ids );
}

/** Theme *********************************************************************/

/**
 * Contains functions which were moved out of BP-Default's functions.php
 * in BuddyPress 1.3.
 *
 * @since 1.3
 */
function bp_dtheme_deprecated() {
	if ( !function_exists( 'bp_dtheme_wp_pages_filter' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function filtered the dropdown on the
	 * Settings > Reading screen for selecting the page to show on front to
	 * include "Activity Stream." As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param string $page_html A list of pages as a dropdown (select list)
	 * @return string
	 * @see wp_dropdown_pages()
	 * @since 1.2
	 */
	function bp_dtheme_wp_pages_filter( $page_html ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $page_html;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_page_on_front_update' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function hijacked the saving of page on front setting to save the activity stream setting.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param $string $oldvalue Previous value of get_option( 'page_on_front' )
	 * @param $string $oldvalue New value of get_option( 'page_on_front' )
	 * @return string
	 * @since 1.2
	 */
	function bp_dtheme_page_on_front_update( $oldvalue, $newvalue ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		if ( !is_admin() || !is_super_admin() )
			return false;

		return $oldvalue;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_page_on_front_template' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function loaded the activity stream template if the front page display settings allow.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param string $template Absolute path to the page template
	 * @return string
	 * @since 1.2
	 */
	function bp_dtheme_page_on_front_template( $template ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $template;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_fix_get_posts_on_activity_front' ) ) :
	/**
	 * In BuddyPress 1.2.x, this forced the page ID as a string to stop the get_posts query from kicking up a fuss.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @since 1.2
	 */
	function bp_dtheme_fix_get_posts_on_activity_front() {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
	}
	endif;

	if ( !function_exists( 'bp_dtheme_fix_the_posts_on_activity_front' ) ) :
	/**
	 * In BuddyPress 1.2.x, this was used as part of the code that set the activity stream to be on the front page.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param array $posts Posts as retrieved by WP_Query
	 * @return array
	 * @since 1.2.5
	 */
	function bp_dtheme_fix_the_posts_on_activity_front( $posts ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $posts;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_add_blog_comments_js' ) ) :
	/**
	 * In BuddyPress 1.2.x, this added the javascript needed for blog comment replies.
	 * As of 1.3.x, we recommend that you enqueue the comment-reply javascript in your theme's header.php.
	 *
	 * @deprecated 1.3
	 * @deprecated Enqueue the comment-reply script in your theme's header.php.
	 * @since 1.2
	 */
	function bp_dtheme_add_blog_comments_js() {
		_deprecated_function( __FUNCTION__, '1.3', "Enqueue the comment-reply script in your theme's header.php." );
		if ( is_singular() && bp_is_blog_page() && get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );
	}
	endif;
}
add_action( 'after_setup_theme', 'bp_dtheme_deprecated', 15 );
?>