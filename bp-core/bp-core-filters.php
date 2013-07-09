<?php

/**
 * BuddyPress Filters
 *
 * @package BuddyPress
 * @subpackage Core
 *
 * This file contains the filters that are used through-out BuddyPress. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found
 *
 *  - BuddyPress: In {@link BuddyPress::setup_actions()} in buddypress.php
 *  - Component: In {@link BP_Component::setup_actions()} in
 *                bp-core/bp-core-component.php
 *  - Admin: More in {@link BP_Admin::setup_actions()} in
 *            bp-core/bp-core-admin.php
 *
 * @see bp-core-actions.php
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Attach BuddyPress to WordPress
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_filter( 'request',                 'bp_request',             10    );
add_filter( 'template_include',        'bp_template_include',    10    );
add_filter( 'login_redirect',          'bp_login_redirect',      10, 3 );
add_filter( 'map_meta_cap',            'bp_map_meta_caps',       10, 4 );

// Add some filters to feedback messages
add_filter( 'bp_core_render_message_content', 'wptexturize'       );
add_filter( 'bp_core_render_message_content', 'convert_smilies'   );
add_filter( 'bp_core_render_message_content', 'convert_chars'     );
add_filter( 'bp_core_render_message_content', 'wpautop'           );
add_filter( 'bp_core_render_message_content', 'shortcode_unautop' );
add_filter( 'bp_core_render_message_content', 'wp_kses_data', 5   );

/**
 * Template Compatibility
 *
 * If you want to completely bypass this and manage your own custom BuddyPress
 * template hierarchy, start here by removing this filter, then look at how
 * bp_template_include() works and do something similar. :)
 */
add_filter( 'bp_template_include',   'bp_template_include_theme_supports', 2, 1 );
add_filter( 'bp_template_include',   'bp_template_include_theme_compat',   4, 2 );

// Filter BuddyPress template locations
add_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations'          );

// Turn comments off for BuddyPress pages
add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

/**
 * bp_core_exclude_pages()
 *
 * Excludes specific pages from showing on page listings, for example the "Activation" page.
 *
 * @package BuddyPress Core
 * @uses bp_is_active() checks if a BuddyPress component is active.
 * @return array The list of page ID's to exclude
 */
function bp_core_exclude_pages( $pages = array() ) {

	// Bail if not the root blog
	if ( ! bp_is_root_blog() )
		return $pages;

	$bp = buddypress();

	if ( !empty( $bp->pages->activate ) )
		$pages[] = $bp->pages->activate->id;

	if ( !empty( $bp->pages->register ) )
		$pages[] = $bp->pages->register->id;

	if ( !empty( $bp->pages->forums ) && ( !bp_is_active( 'forums' ) || ( bp_is_active( 'forums' ) && bp_forums_has_directory() && !bp_forums_is_installed_correctly() ) ) )
		$pages[] = $bp->pages->forums->id;

	return apply_filters( 'bp_core_exclude_pages', $pages );
}
add_filter( 'wp_list_pages_excludes', 'bp_core_exclude_pages' );

/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" name in emails sent to the name of the site and not "WordPress"
 *
 * @package BuddyPress Core
 * @uses bp_get_option() fetches the value for a meta_key in the wp_X_options table
 * @return The blog name for the root blog
 */
function bp_core_email_from_name_filter() {
 	return apply_filters( 'bp_core_email_from_name_filter', bp_get_option( 'blogname', 'WordPress' ) );
}
add_filter( 'wp_mail_from_name', 'bp_core_email_from_name_filter' );

/**
 * bp_core_filter_comments()
 *
 * Filter the blog post comments array and insert BuddyPress URLs for users.
 *
 * @package BuddyPress Core
 */
function bp_core_filter_comments( $comments, $post_id ) {
	global $wpdb;

	foreach( (array) $comments as $comment ) {
		if ( $comment->user_id )
			$user_ids[] = $comment->user_id;
	}

	if ( empty( $user_ids ) )
		return $comments;

	$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

	if ( !$userdata = $wpdb->get_results( "SELECT ID as user_id, user_login, user_nicename FROM {$wpdb->users} WHERE ID IN ({$user_ids})" ) )
		return $comments;

	foreach( (array) $userdata as $user )
		$users[$user->user_id] = bp_core_get_user_domain( $user->user_id, $user->user_nicename, $user->user_login );

	foreach( (array) $comments as $i => $comment ) {
		if ( !empty( $comment->user_id ) ) {
			if ( !empty( $users[$comment->user_id] ) )
				$comments[$i]->comment_author_url = $users[$comment->user_id];
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );

/**
 * When a user logs in, redirect him in a logical way
 *
 * @package BuddyPress Core
 *
 * @uses apply_filters Filter bp_core_login_redirect to modify where users are redirected to on
 *   login
 * @param string $redirect_to The URL to be redirected to, sanitized in wp-login.php
 * @param string $redirect_to_raw The unsanitized redirect_to URL ($_REQUEST['redirect_to'])
 * @param obj $user The WP_User object corresponding to a successfully logged-in user. Otherwise
 *   a WP_Error object
 * @return string The redirect URL
 */
function bp_core_login_redirect( $redirect_to, $redirect_to_raw, $user ) {

	// Only modify the redirect if we're on the main BP blog
	if ( !bp_is_root_blog() ) {
		return $redirect_to;
	}

	// Only modify the redirect once the user is logged in
	if ( !is_a( $user, 'WP_User' ) ) {
		return $redirect_to;
	}

	// Allow plugins to allow or disallow redirects, as desired
	$maybe_redirect = apply_filters( 'bp_core_login_redirect', false, $redirect_to, $redirect_to_raw, $user );
	if ( false !== $maybe_redirect ) {
		return $maybe_redirect;
	}

	// If a 'redirect_to' parameter has been passed that contains 'wp-admin', verify that the
	// logged-in user has any business to conduct in the Dashboard before allowing the
	// redirect to go through
	if ( !empty( $redirect_to ) && ( false === strpos( $redirect_to, 'wp-admin' ) || user_can( $user, 'edit_posts' ) ) ) {
		return $redirect_to;
	}

	if ( false === strpos( wp_get_referer(), 'wp-login.php' ) && false === strpos( wp_get_referer(), 'activate' ) && empty( $_REQUEST['nr'] ) ) {
		return wp_get_referer();
	}

	return bp_get_root_domain();
}
add_filter( 'bp_login_redirect', 'bp_core_login_redirect', 10, 3 );

/***
 * bp_core_filter_user_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 *
 * @uses locate_template To see if custom registration files exist
 * @param string $welcome_email Complete email passed through WordPress
 * @return string Filtered $welcome_email with 'PASSWORD' replaced by [User Set]
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {

	// Don't touch the email if we don't have a custom registration template
	if ( ! bp_has_custom_signup_page() )
		return $welcome_email;

	// [User Set] Replaces 'PASSWORD' in welcome email; Represents value set by user
	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}
add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );

/***
 * bp_core_filter_blog_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 *
 * @uses locate_template To see if custom registration files exist
 * @param string $welcome_email Complete email passed through WordPress
 * @param integer $blog_id ID of the blog user is joining
 * @param integer $user_id ID of the user joining
 * @param string $password Password of user
 * @return string Filtered $welcome_email with $password replaced by [User Set]
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {

	// Don't touch the email if we don't have a custom registration template
	if ( ! bp_has_custom_signup_page() )
		return $welcome_email;

	// [User Set] Replaces $password in welcome email; Represents value set by user
	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}
add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

// Notify user of signup success.
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {

	// Send email with activation link.
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = esc_url( $activate_url );

	$admin_email = get_site_option( 'admin_email' );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	$from_name       = bp_get_option( 'blogname', 'WordPress' );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
	$message         = sprintf( __( "Thanks for registering! To complete the activation of your account and blog, please click the following link:\n\n%1\$s\n\n\n\nAfter you activate, you can visit your blog here:\n\n%2\$s", 'buddypress' ), $activate_url, esc_url( "http://{$domain}{$path}" ) );
	$subject         = bp_get_email_subject( array( 'text' => sprintf( __( 'Activate %s', 'buddypress' ), 'http://' . $domain . $path ) ) );

	// Send the message
	$to              = apply_filters( 'bp_core_activation_signup_blog_notification_to',   $user_email, $domain, $path, $title, $user, $user_email, $key, $meta );
	$subject         = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $subject, $domain, $path, $title, $user, $user_email, $key, $meta );
	$message         = apply_filters( 'bp_core_activation_signup_blog_notification_message', $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	wp_mail( $to, $subject, $message, $message_headers );

	do_action( 'bp_core_sent_blog_signup_email', $admin_email, $subject, $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {

	$activate_url = bp_get_activation_page() . "?key=$key";
	$activate_url = esc_url($activate_url);
	$admin_email  = get_site_option( 'admin_email' );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	$from_name       = bp_get_option( 'blogname', 'WordPress' );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
	$message         = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%1\$s\n\n", 'buddypress' ), $activate_url );
	$subject         = bp_get_email_subject( array( 'text' => __( 'Activate Your Account', 'buddypress' ) ) );

	// Send the message
	$to      = apply_filters( 'bp_core_activation_signup_user_notification_to',   $user_email, $user, $user_email, $key, $meta );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject, $user, $user_email, $key, $meta );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message, $user, $user_email, $key, $meta );

	wp_mail( $to, $subject, $message, $message_headers );

	do_action( 'bp_core_sent_user_signup_email', $admin_email, $subject, $message, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

/**
 * Filter the page title for BuddyPress pages
 *
 * @global object $bp BuddyPress global settings
 * @param string $title Original page title
 * @param string $sep How to separate the various items within the page title.
 * @param string $seplocation Direction to display title
 * @return string new page title
 * @see wp_title()
 * @since BuddyPress (1.5)
 */
function bp_modify_page_title( $title, $sep, $seplocation ) {
	global $bp;

	// If this is not a BP page, just return the title produced by WP
	if ( bp_is_blog_page() )
		return $title;

	// If this is the front page of the site, return WP's title
	if ( is_front_page() || is_home() )
		return $title;

	$title = '';

	// Displayed user
	if ( bp_get_displayed_user_fullname() && !is_404() ) {

		// Get the component's ID to try and get it's name
		$component_id = $component_name = bp_current_component();

		// Use the actual component name
		if ( !empty( $bp->{$component_id}->name ) ) {
			$component_name = $bp->{$component_id}->name;

		// Fall back on the component ID (probably same as current_component)
		} elseif ( !empty( $bp->{$component_id}->id ) ) {
			$component_name = $bp->{$component_id}->id;
		}

		// Construct the page title. 1 = user name, 2 = seperator, 3 = component name
		$title = strip_tags( sprintf( _x( '%1$s %3$s %2$s', 'Construct the page title. 1 = user name, 2 = component name, 3 = seperator', 'buddypress' ), bp_get_displayed_user_fullname(), ucwords( $component_name ), $sep ) );

	// A single group
	} elseif ( bp_is_active( 'groups' ) && !empty( $bp->groups->current_group ) && !empty( $bp->bp_options_nav[$bp->groups->current_group->slug] ) ) {
		$subnav = isset( $bp->bp_options_nav[$bp->groups->current_group->slug][bp_current_action()]['name'] ) ? $bp->bp_options_nav[$bp->groups->current_group->slug][bp_current_action()]['name'] : '';
		// translators: "group name | group nav section name"
		$title = sprintf( __( '%1$s | %2$s', 'buddypress' ), $bp->bp_options_title, $subnav );

	// A single item from a component other than groups
	} elseif ( bp_is_single_item() ) {
		// translators: "component item name | component nav section name | root component name"
		$title = sprintf( __( '%1$s | %2$s | %3$s', 'buddypress' ), $bp->bp_options_title, $bp->bp_options_nav[bp_current_item()][bp_current_action()]['name'], bp_get_name_from_root_slug( bp_get_root_slug() ) );

	// An index or directory
	} elseif ( bp_is_directory() ) {
		if ( !bp_current_component() ) {
			$title = sprintf( __( '%s Directory', 'buddypress' ), bp_get_name_from_root_slug() );
		} else {
			$title = sprintf( __( '%s Directory', 'buddypress' ), bp_get_name_from_root_slug() );
		}

	// Sign up page
	} elseif ( bp_is_register_page() ) {
		$title = __( 'Create an Account', 'buddypress' );

	// Activation page
	} elseif ( bp_is_activation_page() ) {
		$title = __( 'Activate your Account', 'buddypress' );

	// Group creation page
	} elseif ( bp_is_group_create() ) {
		$title = __( 'Create a Group', 'buddypress' );

	// Blog creation page
	} elseif ( bp_is_create_blog() ) {
		$title = __( 'Create a Site', 'buddypress' );
	}

	// Some BP nav items contain item counts. Remove them
	$title = preg_replace( '|<span>[0-9]+</span>|', '', $title );

	return apply_filters( 'bp_modify_page_title', $title . ' ' . $sep . ' ', $title, $sep, $seplocation );
}
add_filter( 'wp_title', 'bp_modify_page_title', 10, 3 );
add_filter( 'bp_modify_page_title', 'wptexturize'     );
add_filter( 'bp_modify_page_title', 'convert_chars'   );
add_filter( 'bp_modify_page_title', 'esc_html'        );
