<?php

/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.3
 */

/** Loader ********************************************************************/

function bp_setup_root_components() {
	do_action( 'bp_setup_root_components' );
}
add_action( 'bp_init', 'bp_setup_root_components', 6 );

/** WP Abstraction ************************************************************/

/**
 * bp_core_is_multisite()
 *
 * This function originally served as a wrapper when WordPress and WordPress MU were separate entities.
 * Use is_multisite() instead.
 *
 * @deprecated 1.3
 * @deprecated Use is_multisite()
 */
function bp_core_is_multisite() {
	_deprecated_function( __FUNCTION__, '1.3', 'is_multisite()' );
	return is_multisite();
}

/**
 * bp_core_is_main_site
 *
 * Checks if current blog is root blog of site. Deprecated in 1.3.
 *
 * @deprecated 1.3
 * @deprecated Use is_main_site()
 * @package BuddyPress
 * @param int $blog_id optional blog id to test (default current blog)
 * @return bool True if not multisite or $blog_id is main site
 * @since 1.2.6
 */
function bp_core_is_main_site( $blog_id = '' ) {
	_deprecated_function( __FUNCTION__, '1.3', 'is_main_site()' );
	return is_main_site( $blog_id );
}

/** Admin ******************************************************************/

/**
 * In BuddyPress 1.1 - 1.2.x, this function provided a better version of add_menu_page()
 * that allowed positioning of menus. Deprecated in 1.3 in favour of a WP core function.
 *
 * @deprecated 1.3
 * @deprecated Use add_menu_page().
 * @since 1.1
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	$defaults = array(
		'page_title'   => '',
		'menu_title'   => '',
		'capability'   => 'manage_options',
		'file'         => '',
		'function'     => '',
		'icon_url'     => '',
		'position'     => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	_deprecated_function( __FUNCTION__, '1.3', 'Use add_menu_page()' );
	return add_menu_page( $page_title, $menu_title, $capability, $file, $function, $icon_url, $position );
}

/** Activity ******************************************************************/

function bp_is_activity_permalink() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_single_activity' );
	bp_is_single_activity();
}

/** Sign up *******************************************************************/

function bp_core_screen_signup() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_screen_signup' );
	bp_members_screen_signup();
}

function bp_core_screen_activation() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_screen_activation' );
	bp_members_screen_activation();
}

function bp_core_flush_illegal_names() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_flush_illegal_names' );
	bp_members_flush_illegal_names();
}

function bp_core_illegal_names( $value = '', $oldvalue = '' ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_illegal_names' );
	bp_members_illegal_names( $value, $oldvalue );
}

function bp_core_validate_user_signup( $user_name, $user_email ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_validate_user_signup' );
	bp_members_validate_user_signup( $user_name, $user_email );
}

function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_validate_blog_signup' );
	bp_members_validate_blog_signup( $blog_url, $blog_title );
}

function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_signup_user' );
	bp_members_signup_user( $user_login, $user_password, $user_email, $usermeta );
}

function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_signup_blog' );
	bp_members_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta );
}

function bp_core_activate_signup( $key ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_activate_signup' );
	bp_members_activate_signup( $key );
}

function bp_core_new_user_activity( $user ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_new_user_activity' );
	bp_members_new_user_activity( $user );
}

function bp_core_map_user_registration( $user_id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_map_user_registration' );
	bp_members_map_user_registration( $user_id );
}

function bp_core_signup_avatar_upload_dir() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_signup_avatar_upload_dir' );
	bp_members_signup_avatar_upload_dir();
}

function bp_core_signup_send_validation_email( $user_id, $user_email, $key ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_signup_send_validation_email' );
	bp_members_signup_send_validation_email( $user_id, $user_email, $key );
}

function bp_core_signup_disable_inactive( $auth_obj, $username ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_signup_disable_inactive' );
	bp_members_signup_disable_inactive( $auth_obj, $username );
}

function bp_core_wpsignup_redirect() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_wpsignup_redirect' );
	bp_members_wpsignup_redirect();
}

/** Settings ******************************************************************/

function bp_core_add_settings_nav() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_add_settings_nav' );
	bp_settings_add_settings_nav();
}

function bp_core_can_edit_settings() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_can_edit_settings' );
	bp_members_can_edit_settings();
}

function bp_core_screen_general_settings() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_general_settings' );
	bp_settings_screen_general_settings();
}

function bp_core_screen_general_settings_title() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_general_settings' );
}

function bp_core_screen_general_settings_content() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_general_settings' );
}

function bp_core_screen_notification_settings() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_notification_settings' );
	bp_settings_screen_notification_settings();
}

function bp_core_screen_notification_settings_title() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_notification_settings' );
}

function bp_core_screen_notification_settings_content() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_notification_settings' );
}

function bp_core_screen_delete_account() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_delete_account' );
	bp_settings_screen_delete_account();
}

function bp_core_screen_delete_account_title() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_delete_account' );
}

function bp_core_screen_delete_account_content() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_settings_screen_delete_account' );
}

/** Notifications *************************************************************/

function bp_core_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id = false, $date_notified = false ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_add_notification' );
	bp_members_add_notification( $item_id, $user_id, $component_name, $component_action, $secondary_item_id, $date_notified );
}

function bp_core_delete_notification( $id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_delete_notification' );
	bp_members_delete_notification( $id );
}

function bp_core_get_notification( $id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_get_notification' );
	bp_members_get_notification( $id );
}

function bp_core_get_notifications_for_user( $user_id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_get_notifications_for_user' );
	bp_members_get_notifications_for_user( $user_id );
}

function bp_core_delete_notifications_by_type( $user_id, $component_name, $component_action ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_delete_notifications_by_type' );
	bp_members_delete_notifications_by_type( $user_id, $component_name, $component_action );
}

function bp_core_delete_notifications_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_delete_notifications_by_item_id' );
	bp_members_delete_notifications_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_all_notifications_by_type( $item_id, $component_name, $component_action = false, $secondary_item_id = false ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_delete_all_notifications_by_type' );
	bp_members_delete_all_notifications_by_type( $item_id, $component_name, $component_action, $secondary_item_id );
}

function bp_core_delete_notifications_from_user( $user_id, $component_name, $component_action ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_delete_notifications_from_user' );
	bp_members_delete_notifications_from_user( $user_id, $component_name, $component_action );
}

function bp_core_check_notification_access( $user_id, $notification_id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_members_check_notification_access' );
	bp_members_check_notification_access( $user_id, $notification_id );
}

/** Core **********************************************************************/

function bp_core_get_wp_profile() {
	_deprecated_function( __FUNCTION__, '1.3' );

	global $bp;

	$ud = get_userdata( $bp->displayed_user->id ); ?>

<div class="bp-widget wp-profile">
	<h4><?php _e( 'My Profile' ) ?></h4>

	<table class="wp-profile-fields">

		<?php if ( $ud->display_name ) : ?>

			<tr id="wp_displayname">
				<td class="label"><?php _e( 'Name', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->display_name; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_description ) : ?>

			<tr id="wp_desc">
				<td class="label"><?php _e( 'About Me', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->user_description; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_url ) : ?>

			<tr id="wp_website">
				<td class="label"><?php _e( 'Website', 'buddypress' ); ?></td>
				<td class="data"><?php echo make_clickable( $ud->user_url ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->jabber ) : ?>

			<tr id="wp_jabber">
				<td class="label"><?php _e( 'Jabber', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->jabber; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->aim ) : ?>

			<tr id="wp_aim">
				<td class="label"><?php _e( 'AOL Messenger', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->aim; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->yim ) : ?>

			<tr id="wp_yim">
				<td class="label"><?php _e( 'Yahoo Messenger', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->yim; ?></td>
			</tr>

		<?php endif; ?>

	</table>
</div>

<?php
}

function bp_is_home() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_my_profile' );
	return bp_is_my_profile();
}

/**
 * Is the user on the front page of the site?
 *
 * @deprecated 1.3
 * @deprecated Use is_front_page()
 * @return bool
 */
function bp_is_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "is_front_page()" );
	return is_front_page();
}

/**
 * Is the front page of the site set to the Activity component?
 *
 * @deprecated 1.3
 * @deprecated Use bp_is_component_front_page( 'activity' )
 * @return bool
 */
function bp_is_activity_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "bp_is_component_front_page( 'activity' )" );
	return bp_is_component_front_page( 'activity' );
}

function bp_is_member() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_user' );
	bp_is_user();
}

function bp_loggedinuser_link() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_loggedin_user_link' );
	bp_loggedin_user_link();
}

/**
 * Only show the search form if there are available objects to search for.
 * Deprecated in 1.3; not used anymore.
 *
 * @return bool
 */
function bp_search_form_enabled() {
	_deprecated_function( __FUNCTION__, '1.3', 'No longer required.' );
	return apply_filters( 'bp_search_form_enabled', true );
}

/**
 * Template tag version of bp_get_page_title()
 *
 * @deprecated 1.3
 * @deprecated Use wp_title()
 * @since 1.0
 */
function bp_page_title() {
	echo bp_get_page_title(); 
}
	/**
	 * Prior to BuddyPress 1.3, this was used to generate the page's <title> text.
	 * Now, just simply use wp_title().
	 *
	 * @deprecated 1.3
	 * @deprecated Use wp_title()
	 * @since 1.0
	 */
	function bp_get_page_title() {
		_deprecated_function( __FUNCTION__, '1.3', 'wp_title()' );
		$title = wp_title( '|', false, 'right' ) . get_bloginfo( 'name', 'display' );

		// Backpat for BP 1.2 filter
		$title = apply_filters( 'bp_page_title', esc_attr( $title ), esc_attr( $title ) );

		return apply_filters( 'bp_get_page_title', $title );
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