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
add_filter( 'bp_displayed_user_fullname', 'esc_html' );
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
	$fields = array(
		'bp_get_signup_username_value',
		'bp_get_signup_email_value',
		'bp_get_signup_with_blog_value',
		'bp_get_signup_blog_url_value',
		'bp_get_signup_blog_title_value',
		'bp_get_signup_blog_privacy_value',
		'bp_get_signup_avatar_dir_value',
	);

	// Add the filters to each field.
	foreach ( $fields as $filter ) {
		add_filter( $filter, 'esc_html', 1 );
		add_filter( $filter, 'wp_filter_kses', 2 );
		add_filter( $filter, 'stripslashes', 3 );
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
		$blog   = $_POST['blog'];
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
		$profile_link = bp_members_get_user_url(
			$user_id,
			bp_members_get_path_chunks( array( bp_get_profile_slug(), 'edit' ) )
		);

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
 * Filter BP_User_Query::populate_extras to add last activity.
 *
 * @since 15.0.0
 *
 * @param BP_User_Query $user_query The BP_User_Query object.
 */
function bp_members_filter_user_query_populate_extras_last_activity( $user_query ) {
	$last_activities = BP_Core_User::get_last_activity( $user_query->user_ids );

	// Set a last_activity value for each user, even if it's empty.
	foreach ( $user_query->results as $user_id => $user ) {
		$user_last_activity = isset( $last_activities[ $user_id ]['date_recorded'] )
			? $last_activities[ $user_id ]['date_recorded']
			: '';

		$user_query->results[ $user_id ]->last_activity = $user_last_activity;
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_members_filter_user_query_populate_extras_last_activity' );

/**
 * Filter BP_User_Query::populate_extras to add friend count & latest update.
 *
 * @since 15.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param BP_User_Query $user_query   The BP_User_Query object.
 * @param string        $user_ids_sql Comma-separated list of user IDs to fetch extra
 *                                    data for, as determined by BP_User_Query.
 */
function bp_members_filter_user_query_populate_extras_friend_count_latest_update( $user_query, $user_ids_sql ) {
	global $wpdb;

	$total_friend_count_key = bp_get_user_meta_key( 'total_friend_count' );
	$bp_latest_update_key   = bp_get_user_meta_key( 'bp_latest_update' );

	// Total_friend_count must be set for each user, even if its
	// value is 0.
	foreach ( $user_query->results as $uindex => $user ) {
		$user_query->results[ $uindex ]->total_friend_count = 0;
	}

	// Create, prepare, and run the separate usermeta query.
	$user_metas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s) AND user_id IN ({$user_ids_sql})",
			$total_friend_count_key,
			$bp_latest_update_key
		)
	);

	// The $members_template global expects the index key to be different
	// from the meta_key in some cases, so we rejig things here.
	foreach ( $user_metas as $user_meta ) {
		switch ( $user_meta->meta_key ) {
			case $total_friend_count_key:
				$key = 'total_friend_count';
				break;

			case $bp_latest_update_key:
				$key = 'latest_update';
				break;
		}

		if ( isset( $user_query->results[ $user_meta->user_id ] ) ) {
			$user_query->results[ $user_meta->user_id ]->{$key} = $user_meta->meta_value;
		}
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_members_filter_user_query_populate_extras_friend_count_latest_update', 10, 2 );

/**
 * Filter BP_User_Query::populate_extras to add meta key / value.
 *
 * Only added if 'meta_key' or 'meta_value' is passed to the user query.
 *
 * @since 15.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param BP_User_Query $user_query The BP_User_Query object.
 */
function bp_members_filter_user_query_populate_extras_meta( $user_query ) {
	global $wpdb;

	if ( empty( $user_query->query_vars['meta_key'] ) ) {
		return;
	}

	$meta_sql = array(
		'select' => 'SELECT user_id, meta_key, meta_value',
		'from'   => "FROM $wpdb->usermeta",
		'where'  => $wpdb->prepare( 'WHERE meta_key = %s', $user_query->query_vars['meta_key'] ),
	);

	if ( false !== $user_query->query_vars['meta_value'] ) {
		$meta_sql['where'] .= $wpdb->prepare( ' AND meta_value = %s', $user_query->query_vars['meta_value'] );
	}

	$metas = $wpdb->get_results( "{$meta_sql['select']} {$meta_sql['from']} {$meta_sql['where']}" );

	if ( empty( $metas ) || ! is_array( $metas ) ) {
		return;
	}

	foreach ( $metas as $meta ) {
		if ( isset( $user_query->results[ $meta->user_id ] ) ) {
			$user_query->results[ $meta->user_id ]->meta_key = $meta->meta_key;

			if ( ! empty( $meta->meta_value ) ) {
				$user_query->results[ $meta->user_id ]->meta_value = $meta->meta_value;
			}
		}
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_members_filter_user_query_populate_extras_meta' );

/**
 * Filter the bp_user_can value to determine what the user can do in the members component.
 *
 * @since 8.0.0
 *
 * @param bool   $retval     Whether or not the current user has the capability.
 * @param int    $user_id    User ID.
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
			if ( is_user_logged_in() && bp_get_members_invitations_allowed() ) {
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

		case 'bp_members_invitations_view_screens':
			$retval = bp_get_members_invitations_allowed() && ( bp_user_can( $user_id, 'bp_members_send_invitation' ) || bp_members_invitations_user_has_sent_invites( $user_id ) );
			break;

		case 'bp_members_invitations_view_send_screen':
			$retval = is_user_logged_in() && bp_get_members_invitations_allowed();
			break;
	}

	return $retval;
}
add_filter( 'bp_user_can', 'bp_members_user_can_filter', 10, 5 );

/**
 * Do not allow the new user to change the email address
 * if they are accepting a community invitation.
 *
 * @since 8.0.0
 *
 * @param array  $attributes The field attributes.
 * @param string $name       The field name.
 *
 * @return array $attributes The field attributes.
 */
function bp_members_invitations_make_registration_email_input_readonly_if_invite( $attributes, $name ) {
	if ( 'email' === $name && bp_get_members_invitations_allowed() ) {
		$invite = bp_get_members_invitation_from_request();
		if ( $invite->id ) {
			$attributes['readonly'] = 'readonly';
		}
	}
	return $attributes;
}
add_filter( 'bp_get_form_field_attributes', 'bp_members_invitations_make_registration_email_input_readonly_if_invite', 10, 2 );

/**
 * Provide a more-specific welcome message if the new user
 * is accepting a network invitation.
 *
 * @since 8.0.0
 *
 * @return string $message The message text.
 */
function bp_members_invitations_get_registration_welcome_message() {
	$message = '';
	if ( ! bp_get_members_invitations_allowed() ) {
		return $message;
	}

	$invite = bp_get_members_invitation_from_request();
	if ( ! $invite->id || ! $invite->invitee_email ) {
		return $message;
	}

	// Check if the user is already a site member.
	$maybe_user = get_user_by( 'email', $invite->invitee_email );

	// This user is already a member
	if ( $maybe_user ) {
		$message = sprintf(
			/* translators: %s: The log in link `<a href="login_url">log in</a>` */
			esc_html__( 'Welcome! You are already a member of this site. Please %s to continue.', 'buddypress' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( wp_login_url( bp_get_root_url() ) ),
				esc_html__( 'log in', 'buddypress' )
			)
		);

		// This user can register!
	} else {

		// Fetch the display names of all inviters to personalize the welcome message.
		$args = array(
			'invitee_email' => $invite->invitee_email,
			'invite_sent'   => 'sent',
		);

		$all_invites = bp_members_invitations_get_invites( $args );
		$inviters    = array();

		foreach ( $all_invites as $inv ) {
			$inviters[] = bp_core_get_user_displayname( $inv->inviter_id );
		}

		if ( ! empty( $inviters ) ) {
			$message = sprintf(
				/* translators: %s: The comma separated list of inviters display names */
				_n( 'Welcome! You&#8217;ve been invited to join the site by the following user: %s.', 'Welcome! You&#8217;ve been invited to join the site by the following users: %s.', count( $inviters ), 'buddypress' ),
				implode( ', ', $inviters )
			);
		} else {
			$message = __( 'Welcome! You&#8217;ve been invited to join the site. ', 'buddypress' );
		}
	}

	return $message;
}

/**
 * Provide a more-specific "registration is disabled" message
 * if registration is available by invitation only.
 * Also provide failure note if new user is trying to accept
 * a network invitation but there's a problem.
 *
 * @since 8.0.0
 *
 * @return string $message The message text.
 */
function bp_members_invitations_get_modified_registration_disabled_message() {
	$message = '';
	if ( bp_get_members_invitations_allowed() ) {

		$invite = bp_get_members_invitation_from_request();
		if ( ! $invite->id || ! $invite->invitee_email ) {
			return $message;
		}

		// Check if the user is already a site member.
		$maybe_user = get_user_by( 'email', $invite->invitee_email );

		if ( ! $maybe_user ) {
			$message_parts = array( esc_html__( 'Member registration is allowed by invitation only.', 'buddypress' ) );

			// Is the user trying to accept an invitation but something is wrong?
			if ( ! empty( $_GET['inv'] ) ) {
				$message_parts[] = esc_html__( 'It looks like there is a problem with your invitation. Please try again.', 'buddypress' );
			}

			$message = implode( ' ', $message_parts );
		} elseif ( 'nouveau' === bp_get_theme_package_id() ) {
			$message = sprintf(
				/* translators: 1: The log in link `<a href="login_url">log in</a>`. 2: The lost password link `<a href="lost_password_url">log in</a>` */
				esc_html__( 'Welcome! You are already a member of this site. Please %1$s to continue. If you have forgotten your password, you can %2$s.', 'buddypress' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( wp_login_url( bp_get_root_url() ) ),
					esc_html__( 'log in', 'buddypress' )
				),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( wp_lostpassword_url( bp_get_root_url() ) ),
					esc_html__( 'reset it', 'buddypress' )
				)
			);
		}
	}

	return $message;
}

/**
 * Sanitize the invitation property output.
 *
 * @since 8.0.0
 *
 * @param int|string $value    The value for the requested property.
 * @param string     $property The name of the requested property.
 * @param string     $context  Optional. The context of display.
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
