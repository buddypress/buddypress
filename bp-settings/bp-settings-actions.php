<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** General *******************************************************************/

/**
 * Handles the changing and saving of user email addressos and passwords
 * 
 * We do quite a bit of logic and error handling here to make sure that users
 * do not accidentally lock themselves out of their accounts. We also try to
 * provide as accurate of feedback as possible without exposing anyone else's
 * inforation to them.
 * 
 * Special considerations are made for super admins that are able to edit any
 * users accounts already, without knowing their existing password.
 *
 * @global BuddyPress $bp
 * @return If no reason to proceed
 */
function bp_core_screen_general_settings() {
	global $bp;

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/** Handle Form ***********************************************************/

	if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {

		// Bail if not in settings
		if ( ! bp_is_settings_component() || ! bp_is_current_action( 'general' ) )
			return;

		// Define local defaults
		$email_error   = false;   // invalid|blocked|taken|empty|false
		$pass_error    = false;   // invalid|mismatch|empty|false
		$pass_changed  = false;   // true if the user changes their password
		$email_changed = false;   // true if the user changes their email
		$feedback_type = 'error'; // success|error
		$feedback      = array(); // array of strings for feedback

		// Nonce check
		check_admin_referer('bp_settings_general');

		// Validate the user again for the current password when making a big change
		if ( ( is_super_admin() ) || ( !empty( $_POST['pwd'] ) && wp_check_password( $_POST['pwd'], $bp->displayed_user->userdata->user_pass, bp_displayed_user_id() ) ) ) {

			$update_user = get_userdata( bp_displayed_user_id() );

			/** Email Change Attempt ******************************************/

			if ( !empty( $_POST['email'] ) ) {

				// What is missing from the profile page vs signup - lets double check the goodies
				$user_email = sanitize_email( esc_html( trim( $_POST['email'] ) ) );

				// User is changing email address
				if ( $bp->displayed_user->userdata->user_email != $user_email ) {

					// Is email valid
					if ( !is_email( $user_email ) )
						$email_error = 'invalid';

					// Get blocked email domains
					$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

					// If blocked email domains exist, see if this is one of them
					if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
						$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

						if ( in_array( $emaildomain, (array) $limited_email_domains ) == false ) {
							$email_error = 'blocked';
						}
					}

					// No errors, and email address doesn't match
					if ( ( false === $email_error ) && email_exists( $user_email ) ) {
						$email_error = 'taken';
					}

					// Yay we made it!
					if ( false === $email_error ) {
						$update_user->user_email = $user_email;
						$email_changed = true;
					}

				// No change
				} else {
					$email_error = false;
				}

			// Email address cannot be empty
			} else {
				$email_error = 'empty';
			}

			/** Password Change Attempt ***************************************/

			if ( !empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) ) {

				// Password change attempt is successful
				if ( ( $_POST['pass1'] == $_POST['pass2'] ) && !strpos( " " . $_POST['pass1'], "\\" ) ) {
					$update_user->user_pass = $_POST['pass1'];
					$pass_changed = true;

				// Password change attempt was unsuccessful
				} else {
					$pass_error = 'mismatch';
				}

			// Both password fields were empty
			} elseif ( empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
				$pass_error = false;

			// One of the password boxes was left empty
			} elseif ( ( empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) ) || ( !empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) ) {
				$pass_error = 'empty';
			}

			// The structure of the $update_user object changed in WP 3.3, but
			// wp_update_user() still expects the old format
			if ( isset( $update_user->data ) && is_object( $update_user->data ) ) {
				$update_user = $update_user->data;
				$update_user = get_object_vars( $update_user );

				// Unset the password field to prevent it from emptying out the
				// user's user_pass field in the database.
				// @see wp_update_user()
				if ( false === $pass_changed ) {
					unset( $update_user['user_pass'] );
				}
			}

			// Make sure these changes are in $bp for the current page load
			if ( ( false === $email_error ) && ( false === $pass_error ) && ( wp_update_user( $update_user ) ) ) {
				$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );
			}

		// Password Error
		} else {
			$pass_error = 'invalid';
		}

		// Email feedback
		switch ( $email_error ) {
			case 'invalid' :
				$feedback['email_invalid']  = __( 'That email address is invalid. Check the formatting and try again.', 'buddypress' );
				break;
			case 'blocked' :
				$feedback['email_blocked']  = __( 'That email address is currently unavailable for use.', 'buddypress' );
				break;
			case 'taken' :
				$feedback['email_taken']    = __( 'That email address is already taken.', 'buddypress' );
				break;
			case 'empty' :
				$feedback['email_empty']    = __( 'Email address cannot be empty.', 'buddypress' );
				break;
			case false :
				// No change
				break;
		}

		// Password feedback
		switch ( $pass_error ) {
			case 'invalid' :
				$feedback['pass_error']    = __( 'Your current password is invalid.', 'buddypress' );
				break;
			case 'mismatch' :
				$feedback['pass_mismatch'] = __( 'The new password fields did not match.', 'buddypress' );
				break;
			case 'empty' :
				$feedback['pass_empty']    = __( 'One of the password fields was empty.', 'buddypress' );
				break;
			case false :
				// No change
				break;
		}

		// No errors so show a simple success message
		if ( ( ( false === $email_error ) || ( false == $pass_error ) ) && ( ( true === $pass_changed ) || ( true === $email_changed ) ) ) {
			$feedback[]    = __( 'Your settings have been saved.', 'buddypress' );
			$feedback_type = 'success';

		// Some kind of errors occurred
		} elseif ( ( ( false === $email_error ) || ( false === $pass_error ) ) && ( ( false === $pass_changed ) || ( false === $email_changed ) ) ) {
			if ( bp_is_my_profile() ) {
				$feedback['nochange'] = __( 'No changes were made to your account.', 'buddypress' );
			} else {
				$feedback['nochange'] = __( 'No changes were made to this account.', 'buddypress' );
			}
		}

		// Set the feedback
		bp_core_add_message( implode( '</p><p>', $feedback ), $feedback_type );

		// Execute additional code
		do_action( 'bp_core_general_settings_after_save' );

		// Redirect to prevent issues with browser back button
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() . '/general' ) );
		
	// Load the template
	} else {
		bp_core_load_template( apply_filters( 'bp_core_screen_general_settings', 'members/single/settings/general' ) );
	}
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