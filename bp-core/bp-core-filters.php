<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Add some filters to feedback messages
add_filter( 'bp_core_render_message_content', 'wptexturize'       );
add_filter( 'bp_core_render_message_content', 'convert_smilies'   );
add_filter( 'bp_core_render_message_content', 'convert_chars'     );
add_filter( 'bp_core_render_message_content', 'wpautop'           );
add_filter( 'bp_core_render_message_content', 'shortcode_unautop' );

/**
 * bp_core_exclude_pages()
 *
 * Excludes specific pages from showing on page listings, for example the "Activation" page.
 *
 * @package BuddyPress Core
 * @uses bp_is_active() checks if a BuddyPress component is active.
 * @return array The list of page ID's to exclude
 */
function bp_core_exclude_pages( $pages ) {
	global $bp;
	
	if ( bp_is_root_blog() ) {
		if ( !empty( $bp->pages->activate ) )
			$pages[] = $bp->pages->activate->id;
	
		if ( !empty( $bp->pages->register ) )
			$pages[] = $bp->pages->register->id;
	
		if ( !empty( $bp->pages->forums ) && ( !bp_is_active( 'forums' ) || ( bp_is_active( 'forums' ) && bp_forums_has_directory() && !bp_forums_is_installed_correctly() ) ) )
			$pages[] = $bp->pages->forums->id;
	}

	return apply_filters( 'bp_core_exclude_pages', $pages );
}
add_filter( 'wp_list_pages_excludes', 'bp_core_exclude_pages' );

/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" name in emails sent to the name of the site and not "WordPress"
 *
 * @package BuddyPress Core
 * @uses get_blog_option() fetches the value for a meta_key in the wp_X_options table
 * @return The blog name for the root blog
 */
function bp_core_email_from_name_filter() {
 	return apply_filters( 'bp_core_email_from_name_filter', wp_specialchars_decode( get_blog_option( bp_get_root_blog_id(), 'blogname' ), ENT_QUOTES ) );
}
add_filter( 'wp_mail_from_name', 'bp_core_email_from_name_filter' );

/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" address in emails sent
 *
 * @package BuddyPress Core
 * @return noreply@sitedomain email address
 */
function bp_core_email_from_address_filter() {
	$domain = (array) explode( '/', site_url() );

	return apply_filters( 'bp_core_email_from_address_filter', 'noreply@' . $domain[2] );
}
add_filter( 'wp_mail_from', 'bp_core_email_from_address_filter' );

/**
 * bp_core_allow_default_theme()
 *
 * On multiblog installations you must first allow themes to be activated and show
 * up on the theme selection screen. This function will let the BuddyPress bundled
 * themes show up on the root blog selection screen and bypass this step. It also
 * means that the themes won't show for selection on other blogs.
 *
 * @package BuddyPress Core
 */
function bp_core_allow_default_theme( $themes ) {
	global $bp, $wpdb;

	if ( !is_super_admin() )
		return $themes;

	if ( $wpdb->blogid == bp_get_root_blog_id() ) {
		$themes['bp-default'] = 1;
	}

	return $themes;
}
add_filter( 'allowed_themes', 'bp_core_allow_default_theme' );

/**
 * bp_core_filter_comments()
 *
 * Filter the blog post comments array and insert BuddyPress URLs for users.
 *
 * @package BuddyPress Core
 */
function bp_core_filter_comments( $comments, $post_id ) {
	global $wpdb;

	foreach( (array)$comments as $comment ) {
		if ( $comment->user_id )
			$user_ids[] = $comment->user_id;
	}

	if ( empty( $user_ids ) )
		return $comments;

	$user_ids = implode( ',', $user_ids );

	if ( !$userdata = $wpdb->get_results( $wpdb->prepare( "SELECT ID as user_id, user_login, user_nicename FROM {$wpdb->users} WHERE ID IN ({$user_ids})" ) ) )
		return $comments;

	foreach( (array)$userdata as $user )
		$users[$user->user_id] = bp_core_get_user_domain( $user->user_id, $user->user_nicename, $user->user_login );

	foreach( (array)$comments as $i => $comment ) {
		if ( !empty( $comment->user_id ) ) {
			if ( !empty( $users[$comment->user_id] ) )
				$comments[$i]->comment_author_url = $users[$comment->user_id];
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );

/**
 * bp_core_login_redirect()
 *
 * When a user logs in, always redirect them back to the previous page. NOT the admin area.
 *
 * @package BuddyPress Core
 */
function bp_core_login_redirect( $redirect_to ) {
	global $bp, $wpdb;

	// Don't mess with the redirect if this is not the root blog
	if ( is_multisite() && $wpdb->blogid != bp_get_root_blog_id() )
		return $redirect_to;

	// If the redirect doesn't contain 'wp-admin', it's OK
	if ( !empty( $_REQUEST['redirect_to'] ) && false === strpos( $_REQUEST['redirect_to'], 'wp-admin' ) )
		return $redirect_to;

	if ( false === strpos( wp_get_referer(), 'wp-login.php' ) && false === strpos( wp_get_referer(), 'activate' ) && empty( $_REQUEST['nr'] ) )
		return wp_get_referer();

	return bp_get_root_domain();
}
add_filter( 'login_redirect', 'bp_core_login_redirect' );

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
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	// [User Set] Replaces 'PASSWORD' in welcome email; Represents value set by user
	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
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
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	// [User Set] Replaces $password in welcome email; Represents value set by user
	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
	add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

// Notify user of signup success.
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {

	// Send email with activation link.
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = esc_url( $activate_url );

	$admin_email = get_site_option( 'admin_email' );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	$from_name       = ( '' == get_site_option( 'site_name' ) ) ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
	$message         = sprintf( __( "Thanks for registering! To complete the activation of your account and blog, please click the following link:\n\n%1\$s\n\n\n\nAfter you activate, you can visit your blog here:\n\n%2\$s", 'buddypress' ), $activate_url, esc_url( "http://{$domain}{$path}" ) );
	$subject         = '[' . $from_name . '] ' . sprintf(__( 'Activate %s', 'buddypress' ), esc_url( 'http://' . $domain . $path ) );

	// Send the message
	$to              = apply_filters( 'bp_core_activation_signup_blog_notification_to',   $user_email, $domain, $path, $title, $user, $user_email, $key, $meta );
	$subject         = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $subject, $domain, $path, $title, $user, $user_email, $key, $meta );
	$message         = apply_filters( 'bp_core_activation_signup_blog_notification_message', $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	wp_mail( $to, $subject, $message, $message_headers );

	do_action( 'bp_core_sent_blog_signup_email', $admin_email, $subject, $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() )
	add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {

	$activate_url = bp_get_activation_page() . "?key=$key";
	$activate_url = esc_url($activate_url);
	$admin_email  = get_site_option( 'admin_email' );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	// If this is an admin generated activation, add a param to email the
	// user login details
	$email = is_admin() ? '&e=1' : '';

	$from_name       = ( '' == get_site_option( 'site_name' ) ) ? 'WordPress' : esc_html( get_site_option( 'site_name' ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
	$message         = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%s\n\n", 'buddypress' ), $activate_url . $email );
	$subject         = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	// Send the message
	$to      = apply_filters( 'bp_core_activation_signup_user_notification_to',   $user_email, $user, $user_email, $key, $meta );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject, $user, $user_email, $key, $meta );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message, $user, $user_email, $key, $meta );

	wp_mail( $to, $subject, $message, $message_headers );

	do_action( 'bp_core_sent_user_signup_email', $admin_email, $subject, $message, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() || ( is_admin() && empty( $_POST['noconfirmation'] ) ) )
	add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

/**
 * Filter the page title for BuddyPress pages
 *
 * @global object $bp BuddyPress global settings
 * @global unknown $post
 * @global WP_Query $wp_query WordPress query object
 * @param string $title Original page title
 * @param string $sep How to separate the various items within the page title.
 * @param string $seplocation Direction to display title
 * @return string new page title
 * @see wp_title()
 * @since 1.5
 */
function bp_modify_page_title( $title, $sep, $seplocation ) {
	global $bp, $post, $wp_query;

	// If this is not a BP page, just return the title produced by WP
	if ( bp_is_blog_page() )
		return $title;

	// If this is the front page of the site, return WP's title
	if ( is_front_page() || is_home() )
		return $title;

	$title = '';

	// Displayed user
	if ( !empty( $bp->displayed_user->fullname ) && !is_404() ) {
		// translators: "displayed user's name | canonicalised component name"
		$title = strip_tags( sprintf( __( '%1$s | %2$s', 'buddypress' ), bp_get_displayed_user_fullname(), __( ucwords( bp_current_component() ), 'buddypress' ) ) );

	// A single group
	} elseif ( bp_is_active( 'groups' ) && !empty( $bp->groups->current_group ) && !empty( $bp->bp_options_nav[$bp->groups->current_group->slug] ) ) {
		$subnav = isset( $bp->bp_options_nav[$bp->groups->current_group->slug][$bp->current_action]['name'] ) ? $bp->bp_options_nav[$bp->groups->current_group->slug][$bp->current_action]['name'] : '';
		// translators: "group name | group nav section name"
		$title = sprintf( __( '%1$s | %2$s', 'buddypress' ), $bp->bp_options_title, $subnav );

	// A single item from a component other than groups
	} elseif ( bp_is_single_item() ) {
		// translators: "component item name | component nav section name | root component name"
		$title = sprintf( __( '%1$s | %2$s | %3$s', 'buddypress' ), $bp->bp_options_title, $bp->bp_options_nav[$bp->current_item][$bp->current_action]['name'], bp_get_name_from_root_slug( bp_get_root_slug() ) );

	// An index or directory
	} elseif ( bp_is_directory() ) {
		if ( !bp_current_component() )
			$title = sprintf( __( '%s Directory', 'buddypress' ), __( bp_get_name_from_root_slug(), 'buddypress' ) );
		else
			$title = sprintf( __( '%s Directory', 'buddypress' ), __( bp_get_name_from_root_slug(), 'buddypress' ) );

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

	return apply_filters( 'bp_modify_page_title', $title . " $sep ", $title, $sep, $seplocation );
}
add_filter( 'wp_title', 'bp_modify_page_title', 10, 3 );
add_filter( 'bp_modify_page_title', 'wptexturize'     );
add_filter( 'bp_modify_page_title', 'convert_chars'   );
add_filter( 'bp_modify_page_title', 'esc_html'        );

?>