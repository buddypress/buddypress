<?php
/**
 * BuddyPress Members Filters.
 *
 * Filters specific to the Members component.
 *
 * @package BuddyPress
 * @subpackage MembersFilters
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Escape commonly used fullname output functions.
 */
add_filter( 'bp_displayed_user_fullname',    'esc_html' );
add_filter( 'bp_get_loggedin_user_fullname', 'esc_html' );

// Filter the user registration URL to point to BuddyPress's registration page.
add_filter( 'register_url', 'bp_get_signup_page' );

/**
 * Load additional sign-up sanitization filters on bp_loaded.
 *
 * These are used to prevent XSS in the BuddyPress sign-up process. You can
 * unhook these to allow for customization of your registration fields;
 * however, it is highly recommended that you leave these in place for the
 * safety of your network.
 *
 * @since 1.5.0
 */
function bp_members_signup_sanitization() {

	// Filters on sign-up fields.
	$fields = array (
		'bp_get_signup_username_value',
		'bp_get_signup_email_value',
		'bp_get_signup_with_blog_value',
		'bp_get_signup_blog_url_value',
		'bp_get_signup_blog_title_value',
		'bp_get_signup_blog_privacy_value',
		'bp_get_signup_avatar_dir_value',
	);

	// Add the filters to each field.
	foreach( $fields as $filter ) {
		add_filter( $filter, 'esc_html',       1 );
		add_filter( $filter, 'wp_filter_kses', 2 );
		add_filter( $filter, 'stripslashes',   3 );
	}

	// Sanitize email.
	add_filter( 'bp_get_signup_email_value', 'sanitize_email' );
}
add_action( 'bp_loaded', 'bp_members_signup_sanitization' );

/**
 * Make sure the username is not the blog slug in case of root profile & subdirectory blog.
 *
 * If BP_ENABLE_ROOT_PROFILES is defined & multisite config is set to subdirectories,
 * then there is a chance site.url/username == site.url/blogslug. If so, user's profile
 * is not reachable, instead the blog is displayed. This filter makes sure the signup username
 * is not the same than the blog slug for this particular config.
 *
 * @since 2.1.0
 *
 * @param array $illegal_names Array of illiegal names.
 * @return array $illegal_names
 */
function bp_members_signup_with_subdirectory_blog( $illegal_names = array() ) {
	if ( ! bp_core_enable_root_profiles() ) {
		return $illegal_names;
	}

	if ( is_network_admin() && isset( $_POST['blog'] ) ) {
		$blog = $_POST['blog'];
		$domain = '';

		if ( preg_match( '|^([a-zA-Z0-9-])$|', $blog['domain'] ) ) {
			$domain = strtolower( $blog['domain'] );
		}

		if ( username_exists( $domain ) ) {
			$illegal_names[] = $domain;
		}

	} else {
		$illegal_names[] = buddypress()->signup->username;
	}

	return $illegal_names;
}
add_filter( 'subdirectory_reserved_names', 'bp_members_signup_with_subdirectory_blog', 10, 1 );

/**
 * Filter the user profile URL to point to BuddyPress profile edit.
 *
 * @since 1.6.0
 *
 * @param string $url     WP profile edit URL.
 * @param int    $user_id ID of the user.
 * @param string $scheme  Scheme to use.
 * @return string
 */
function bp_members_edit_profile_url( $url, $user_id, $scheme = 'admin' ) {

	// If xprofile is active, use profile domain link.
	if ( ! is_admin() && bp_is_active( 'xprofile' ) ) {
		$profile_link = trailingslashit( bp_core_get_user_domain( $user_id ) . bp_get_profile_slug() . '/edit' );

	} else {
		// Default to $url.
		$profile_link = $url;
	}

	/**
	 * Filters the user profile URL to point to BuddyPress profile edit.
	 *
	 * @since 1.5.2
	 *
	 * @param string $url WP profile edit URL.
	 * @param int    $user_id ID of the user.
	 * @param string $scheme Scheme to use.
	 */
	return apply_filters( 'bp_members_edit_profile_url', $profile_link, $url, $user_id, $scheme );
}
add_filter( 'edit_profile_url', 'bp_members_edit_profile_url', 10, 3 );

/**
 * Filter the bp_user_can value to determine what the user can do in the members component.
 *
 * @since 8.0.0
 *
 * @param bool   $retval     Whether or not the current user has the capability.
 * @param int    $user_id
 * @param string $capability The capability being checked for.
 * @param int    $site_id    Site ID. Defaults to the BP root blog.
 * @param array  $args       Array of extra arguments passed.
 *
 * @return bool
 */
function bp_members_user_can_filter( $retval, $user_id, $capability, $site_id, $args = array() ) {

	switch ( $capability ) {
		case 'bp_members_manage_membership_requests':
			$retval = bp_user_can( $user_id, 'bp_moderate' );
			break;

		case 'bp_members_send_invitation':
			if ( bp_get_members_invitations_allowed() ) {
				$retval = true;
			}
			break;

		case 'bp_members_receive_invitation':
			if ( bp_get_members_invitations_allowed() ) {
				$retval = true;
				// The invited user must not already be a member of the network.
				if ( empty( $args['invitee_email'] ) || false !== get_user_by( 'email', $args['invitee_email'] ) ) {
					$retval = false;
				}
				// The invited user must not have opted out from being contacted from this site.
				if ( bp_user_has_opted_out( $args['invitee_email'] ) ) {
					$retval = false;
				}
			}
			break;
	}

	return $retval;
}
add_filter( 'bp_user_can', 'bp_members_user_can_filter', 10, 5 );
/**
 * Sanitize the invitation property output.
 *
 * @since 8.0.0
 *
 * @param int|string $value    The value for the requested property.
 * @param string     $property The name of the requested property.
 * @param string     $context  The context of display.
 * @return int|string          The sanitized value.
 */
function bp_members_sanitize_invitation_property( $value = '', $property = '', $context = 'html' ) {
	if ( ! $property ) {
		return '';
	}

	switch ( $property ) {
		case 'id':
		case 'user_id':
		case 'item_id':
		case 'secondary_item_id':
			$value = absint( $value );
			break;
		case 'invite_sent':
		case 'accepted':
			$value = absint( $value ) ? __( 'Yes', 'buddypress' ) : __( 'No', 'buddypress' );
			$value = 'attribute' === $context ? esc_attr( $value ) : esc_html( $value );
			break;
		case 'invitee_email':
			$value = sanitize_email( $value );
			break;
		case 'content':
			$value = wp_kses( $value, array() );
			$value = wptexturize( $value );
			break;
		case 'date_modified':
			$value = mysql2date( 'Y/m/d g:i:s a', $value );
			$value = 'attribute' === $context ? esc_attr( $value ) : esc_html( $value );
			break;

		default:
			$value = 'attribute' === $context ? esc_attr( $value ) : esc_html( $value );
			break;
	}

	return $value;
}
add_filter( 'bp_the_members_invitation_property', 'bp_members_sanitize_invitation_property', 10, 3 );
