<?php

function bp_core_screen_activation() {
	global $bp, $wpdb;

	if ( BP_ACTIVATION_SLUG != $bp->current_component )
		return false;

	/* If we are using a BuddyPress 1.0 theme ignore this. */
	if ( file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	/* Check if an activation key has been passed */
	if ( isset( $_GET['key'] ) ) {

		require_once( ABSPATH . WPINC . '/registration.php' );

		/* Activate the signup */
		$signup = apply_filters( 'bp_core_activate_account', wpmu_activate_signup( $_GET['key'] ) );

		/* If there was errors, add a message and redirect */
		if ( $signup->errors ) {
			bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
			bp_core_redirect( $bp->root_domain . '/' . BP_ACTIVATION_SLUG );
		}

		/* Set the password */
		if ( !empty( $signup['meta']['password'] ) )
			$wpdb->update( $wpdb->users, array( 'user_pass' => $signup['meta']['password'] ), array( 'ID' => $signup['user_id'] ), array( '%s' ), array( '%d' ) );

		/* Set any profile data */
		if ( function_exists( 'xprofile_set_field_data' ) ) {

			if ( !empty( $signup['meta']['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $signup['meta']['profile_field_ids'] );

				foreach( $profile_field_ids as $field_id ) {
					$current_field = $signup['meta']["field_{$field_id}"];

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $signup['user_id'], $current_field );
				}
			}

		}

		/* Check for an uploaded avatar and move that to the correct user folder */
		$hashed_key = wp_hash( $_GET['key'] );

		/* Check if the avatar folder exists. If it does, move rename it, move it and delete the signup avatar dir */
		if ( file_exists( WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/avatars/signups/' . $hashed_key ) ) {
			@rename( WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/avatars/signups/' . $hashed_key, WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/avatars/' . $signup['user_id'] );
		}

		/* Record the new user in the activity streams */
		if ( function_exists( 'bp_activity_add' ) ) {
			$userlink = bp_core_get_userlink( $signup['user_id'] );

			bp_activity_add( array(
				'user_id' => $signup['user_id'],
				'content' => apply_filters( 'bp_core_activity_registered_member', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $signup['user_id'] ),
				'primary_link' => apply_filters( 'bp_core_actiivty_registered_member_primary_link', $userlink ),
				'component_name' => 'profile',
				'component_action' => 'new_member'
			) );
		}

		do_action( 'bp_core_account_activated', &$signup, $_GET['key'] );
		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );

		$bp->activation_complete = true;
	}

	if ( '' != locate_template( array( 'registration/activate' ), false ) )
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'activate' ) );
	else
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'registration/activate' ) );
}
add_action( 'wp', 'bp_core_screen_activation', 3 );


/***
 * bp_core_disable_welcome_email()
 *
 * Since the user now chooses their password, sending it over clear-text to an
 * email address is no longer necessary. It's also a terrible idea security wise.
 *
 * This will only disable the email if a custom registration template is being used.
 */
function bp_core_disable_welcome_email() {
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return true;

	return false;
}
add_filter( 'wpmu_welcome_user_notification', 'bp_core_disable_welcome_email' );

/***
 * bp_core_filter_activation_email()
 *
 * Filter the activation email to remove the line stating that another email will be sent
 * with the generated login details. This is not the case due to bp_core_disable_welcome_email()
 */
function bp_core_filter_activation_email( $email ) {
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $email;

	return str_replace( __( 'After you activate, you will receive *another email* with your login.', 'buddypress' ), '', $email );
}
add_filter( 'wpmu_signup_user_notification_email', 'bp_core_filter_activation_email' );
add_filter( 'wpmu_signup_blog_notification_email', 'bp_core_filter_activation_email' );

?>