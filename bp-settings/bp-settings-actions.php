<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** General *******************************************************************/

function bp_core_screen_general_settings() {
	global $bp;

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Setup private variables
	$bp_settings_updated = $pass_error = $email_error = $pwd_error = false;

	if ( isset( $_POST['submit'] ) ) {

		// Nonce check
		check_admin_referer('bp_settings_general');

		// Validate the user again for the current password when making a big change
		if ( is_super_admin() || ( !empty( $_POST['pwd'] ) && $_POST['pwd'] != '' && wp_check_password( $_POST['pwd'], $bp->displayed_user->userdata->user_pass, $bp->displayed_user->id ) ) ) {

			$update_user = get_userdata( $bp->displayed_user->id );

			// Make sure changing an email address does not already exist
			if ( $_POST['email'] != '' ) {

				// What is missing from the profile page vs signup - lets double check the goodies
				$user_email = sanitize_email( esc_html( trim( $_POST['email'] ) ) );

				// Is email valid
				if ( !is_email( $user_email ) )
					$email_error = true;

				// Get blocked email domains
				$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

				// If blocked email domains exist, see if this is one of them
				if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
					$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

					if ( in_array( $emaildomain, (array)$limited_email_domains ) == false ) {
						$email_error = true;
					}
				}

				// No errors, and email address doesn't match
				if ( ( false === $email_error ) && ( $bp->displayed_user->userdata->user_email != $user_email ) ) {

					// We don't want email dupes in the system
					if ( email_exists( $user_email ) )
						$email_error = true;

					// Set updated user email to this email address
					$update_user->user_email = $user_email;
				}
			}

			// Password change
			if ( !empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) ) {

				// Password change attempt is successful
				if ( $_POST['pass1'] == $_POST['pass2'] && !strpos( " " . $_POST['pass1'], "\\" ) ) {
					$update_user->user_pass = $_POST['pass1'];

				// Password change attempt was unsuccessful
				} else {
					$pass_error = true;
				}

			// One of the password boxes was left empty
			} else if ( ( empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) ) || ( !empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) ) {
				$pass_error = true;

			// Not a password change attempt so empty the user_pass
			} else {
				unset( $update_user->user_pass );
			}

			// The structure of the $update_user object changed in WP 3.3, but
			// wp_update_user() still expects the old format
			if ( isset( $update_user->data ) && is_object( $update_user->data ) ) {
				$update_user = $update_user->data;
			}

			// Make sure these changes are in $bp for the current page load
			if ( ( false === $email_error ) && ( false === $pass_error ) && ( wp_update_user( get_object_vars( $update_user ) ) ) ) {
				$bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );
				$bp_settings_updated = true;
			}

		// Password Error
		} else {
			$pwd_error = true;
		}

		// Add user feedback messages
		if ( empty( $pass_error ) && empty( $pwd_error ) && ( empty( $email_error ) ) )
			bp_core_add_message( __( 'Changes saved.', 'buddypress' ), 'success' );

		elseif ( !empty( $pass_error ) )
			bp_core_add_message( __( 'Your new passwords did not match.', 'buddypress' ), 'error' );

		elseif ( !empty( $pwd_error ) )
			bp_core_add_message( __( 'Your existing password is incorrect.', 'buddypress' ), 'error' );

		elseif ( !empty( $email_error ) )
			bp_core_add_message( __( 'Sorry, that email address is already used or is invalid.', 'buddypress' ), 'error' );

		// Execute additional code
		do_action( 'bp_core_general_settings_after_save' );
	}

	// Load the template
	bp_core_load_template( apply_filters( 'bp_core_screen_general_settings', 'members/single/settings/general' ) );
}

/** Notifications *************************************************************/

function bp_core_screen_notification_settings() {
	global $bp;

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer('bp_settings_notifications');

		if ( isset( $_POST['notifications'] ) ) {
			foreach ( (array)$_POST['notifications'] as $key => $value ) {
				if ( $meta_key = bp_get_user_meta_key( $key ) )
					bp_update_user_meta( (int)$bp->displayed_user->id, $meta_key, $value );
			}
		}

		bp_core_add_message( __( 'Changes saved.', 'buddypress' ), 'success' );

		do_action( 'bp_core_notification_settings_after_save' );
	}

	bp_core_load_template( apply_filters( 'bp_core_screen_notification_settings', 'members/single/settings/notifications' ) );
}

/** Delete Account ************************************************************/

function bp_core_screen_delete_account() {
	global $bp;

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( isset( $_POST['delete-account-understand'] ) ) {
		// Nonce check
		check_admin_referer( 'delete-account' );

		// delete the users account
		if ( bp_core_delete_account( $bp->displayed_user->id ) ) {
			bp_core_redirect( home_url() );
		}
	}

	// Load the template
	bp_core_load_template( apply_filters( 'bp_core_screen_delete_account', 'members/single/settings/delete-account' ) );
}

?>