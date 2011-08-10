<?php
/**
 * BuddyPress Members Filters
 *
 * Member specific filters
 *
 * @package BuddyPress
 * @subpackage Member Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Load additional sign-up sanitization filters on bp_loaded. These are used
 * to prevent XSS in the BuddyPress sign-up process. You can unhook these to
 * allow for customization of your registration fields, however it is highly
 * recommended that you leave these in place for the safety of your network.
 *
 * @since BuddyPress (r4079)
 * @uses add_filter()
 */
function bp_members_signup_sanitization() {

	// Filters on sign-up fields
	$fields = array (
		'bp_get_signup_username_value',
		'bp_get_signup_email_value',
		'bp_get_signup_with_blog_value',
		'bp_get_signup_blog_url_value',
		'bp_get_signup_blog_title_value',
		'bp_get_signup_blog_privacy_value',
		'bp_get_signup_avatar_dir_value',
	);

	// Add the filters to each field
	foreach( $fields as $filter ) {
		add_filter( $filter, 'esc_html',       1 );
		add_filter( $filter, 'wp_filter_kses', 2 );
		add_filter( $filter, 'stripslashes',   3 );
	}

	// Sanitize email
	add_filter( 'bp_get_signup_email_value', 'sanitize_email' );
}
add_action( 'bp_loaded', 'bp_members_signup_sanitization' );

?>