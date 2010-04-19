<?php

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

	$pages[] = $bp->pages->activate->id;
	$pages[] = $bp->pages->register->id;

	if ( !bp_is_active( 'forums' ) || ( function_exists( 'bp_forums_is_installed_correctly' ) && !bp_forums_is_installed_correctly() ) )
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
 * @uses get_blog_option() fetches the value for a meta_key in the wp_X_options table
 * @return The blog name for the root blog
 */
function bp_core_email_from_name_filter() {
 	return apply_filters( 'bp_core_email_from_name_filter', get_blog_option( BP_ROOT_BLOG, 'blogname' ) );
}
add_filter( 'wp_mail_from_name', 'bp_core_email_from_name_filter' );

/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" address in emails sent
 *
 * @package BuddyPress Core
 * @global $current_site Object containing current site metadata
 * @return noreply@sitedomain email address
 */
function bp_core_email_from_address_filter() {
	$domain = (array) explode( '/', site_url() );

	return apply_filters( 'bp_core_email_from_address_filter', __( 'noreply', 'buddypress' ) . '@' . $domain[2] );
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
	global $bp, $current_blog;

	if ( !is_site_admin() )
		return $themes;

	if ( $current_blog->ID == $bp->root_blog ) {
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
	global $bp, $current_blog;

	if ( bp_core_is_multisite() && $current_blog->blog_id != BP_ROOT_BLOG )
		return $redirect_to;

	if ( !empty( $_REQUEST['redirect_to'] ) || strpos( $_REQUEST['redirect_to'], 'wp-admin' ) )
		return $redirect_to;

	if ( false === strpos( wp_get_referer(), 'wp-login.php' ) && false === strpos( wp_get_referer(), 'activate' ) && empty( $_REQUEST['nr'] ) )
		return wp_get_referer();

	return $bp->root_domain;
}
add_filter( 'login_redirect', 'bp_core_login_redirect' );

/***
 * bp_core_filter_user_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
	add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );

/***
 * bp_core_filter_blog_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
	add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

// Notify user of signup success.
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {
	global $current_site;

	// Send email with activation link.
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = clean_url($activate_url);

	$admin_email = get_site_option( "admin_email" );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf(__("Thanks for registering! To complete the activation of your account and blog, please click the following link:\n\n%1$s\n\n\n\nAfter you activate, you can visit your blog here:\n\n%2$s", 'buddypress' ), $activate_url, clean_url("http://{$domain}{$path}" ) );
	$subject = '[' . $from_name . '] ' . sprintf(__('Activate %s', 'buddypress' ), clean_url('http://' . $domain . $path));

	/* Send the message */
	$to = apply_filters( 'bp_core_activation_signup_blog_notification_to', $user_email );
	$subject = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $subject );
	$message = apply_filters( 'bp_core_activation_signup_blog_notification_message', $message );

	wp_mail( $to, $subject, $message, $message_headers );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() )
	add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {
	global $current_site;

	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = clean_url($activate_url);
	$admin_email = get_site_option( "admin_email" );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	/* If this is an admin generated activation, add a param to email the user login details */
	if ( is_admin() )
		$email = '&e=1';

	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%s\n\n", 'buddypress' ), $activate_url . $email, clean_url("http://{$domain}{$path}" ) );
	$subject = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	/* Send the message */
	$to = apply_filters( 'bp_core_activation_signup_user_notification_to', $user_email );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message );

	wp_mail( $to, $subject, $message, $message_headers );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() || ( is_admin() && empty( $_POST['noconfirmation'] ) ) )
	add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

?>