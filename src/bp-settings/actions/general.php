<?php
/**
 * Settings: Email address and password action handler.
 *
 * @package BuddyPress
 * @subpackage SettingsActions
 * @since 3.0.0
 */

/**
 * Handles the changing and saving of user email addresses and passwords.
 *
 * We do quite a bit of logic and error handling here to make sure that users
 * do not accidentally lock themselves out of their accounts. We also try to
 * provide as accurate of feedback as possible without exposing anyone else's
 * information to them.
 *
 * Special considerations are made for super admins that are able to edit any
 * users accounts already, without knowing their existing password.
 *
 * @since 1.6.0
 *
 * @global BuddyPress $bp
 */
function bp_settings_action_general() {
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'general' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Define local defaults
	$bp            = buddypress(); // The instance
	$email_error   = false;        // invalid|blocked|taken|empty|nochange
	$pass_error    = false;        // invalid|mismatch|empty|nochange
	$pass_changed  = false;        // true if the user changes their password
	$email_changed = false;        // true if the user changes their email
	$feedback_type = 'error';      // success|error
	$feedback      = array();      // array of strings for feedback.

	// Nonce check.
	check_admin_referer('bp_settings_general');

	// Validate the user again for the current password when making a big change.
	if ( ( is_super_admin() ) || ( !empty( $_POST['pwd'] ) && wp_check_password( $_POST['pwd'], $bp->displayed_user->userdata->user_pass, bp_displayed_user_id() ) ) ) {

		$update_user = array(
			'ID' => (int) bp_displayed_user_id(),
		);

		/* Email Change Attempt ******************************************/

		if ( ! empty( $_POST['email'] ) ) {

			// What is missing from the profile page vs signup -
			// let's double check the goodies.
			$user_email     = sanitize_email( esc_html( trim( $_POST['email'] ) ) );
			$old_user_email = $bp->displayed_user->userdata->user_email;

			// User is changing email address.
			if ( $old_user_email !== $user_email ) {
				// Run some tests on the email address.
				$email_checks = bp_core_validate_email_address( $user_email );

				if ( true !== $email_checks ) {
					if ( isset( $email_checks['invalid'] ) ) {
						$email_error = 'invalid';
					}

					if ( isset( $email_checks['domain_banned'] ) || isset( $email_checks['domain_not_allowed'] ) ) {
						$email_error = 'blocked';
					}

					if ( isset( $email_checks['in_use'] ) ) {
						$email_error = 'taken';
					}
				}

				// Store a hash to enable email validation.
				if ( false === $email_error ) {
					$hash = wp_generate_password( 32, false );

					$pending_email = array(
						'hash'     => $hash,
						'newemail' => $user_email,
					);

					bp_update_user_meta( bp_displayed_user_id(), 'pending_email_change', $pending_email );
					$verify_link = bp_displayed_user_domain() . bp_get_settings_slug() . '/?verify_email_change=' . $hash;

					// Send the verification email.
					$args = array(
						'tokens' => array(
							'displayname'    => bp_core_get_user_displayname( bp_displayed_user_id() ),
							'old-user.email' => $old_user_email,
							'user.email'     => $user_email,
							'verify.url'     => esc_url( $verify_link ),
						),
					);
					bp_send_email( 'settings-verify-email-change', $user_email, $args );

					// We mark that the change has taken place so as to ensure a
					// success message, even though verification is still required.
					$email_changed = true;
				}

			// No change.
			} else {
				$email_error = false;
			}

		// Email address cannot be empty.
		} else {
			$email_error = 'empty';
		}

		/* Password Change Attempt ***************************************/

		if ( ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) {
			$pass         = wp_unslash( $_POST['pass1'] );
			$pass_confirm = wp_unslash( $_POST['pass2'] );

			// Password strength check.
			$required_password_strength = bp_members_user_pass_required_strength();
			$current_password_strength  = null;
			if ( isset( $_POST['_password_strength_score'] ) ) {
				$current_password_strength = (int) $_POST['_password_strength_score'];
			}

			if ( $required_password_strength && ! is_null( $current_password_strength ) && $required_password_strength > $current_password_strength ) {
				$pass_error = new WP_Error(
					'not_strong_enough_password',
					__( 'Your password is not strong enough to be allowed on this site. Please use a stronger password.', 'buddypress' )
				);
			} else {
				$pass_error = bp_members_validate_user_password( $pass, $pass_confirm, $update_user );

				if ( ! $pass_error->get_error_message() ) {
					// Password change attempt is successful.
					if ( ( ! empty( $_POST['pwd'] ) && wp_unslash( $_POST['pwd'] ) !== $pass ) || is_super_admin() )  {
						$update_user['user_pass'] = $_POST['pass1'];
						$pass_error               = false;
						$pass_changed             = true;

					// The new password is the same as the current password.
					} else {
						$pass_error->add( 'same_user_password', __( 'The new password must be different from the current password.', 'buddypress' ) );
					}
				}
			}

		// Both password fields were empty.
		} elseif ( empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
			$pass_error = false;

		// One of the password boxes was left empty.
		} elseif ( ( empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) || ( ! empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) ) {
			$pass_error = new WP_Error( 'empty_user_password', __( 'One of the password fields was empty.', 'buddypress' ) );
		}

		// Unset the password field to prevent it from emptying out the
		// user's user_pass field in the database.
		if ( false === $pass_changed ) {
			unset( $update_user['user_pass'] );
		}

		// Clear cached data, so that the changed settings take effect
		// on the current page load.
		if ( ( false === $email_error ) && ( false === $pass_error ) && ( wp_update_user( $update_user ) ) ) {
			$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );
		}

	// Password Error.
	} else {
		$pass_error = new WP_Error( 'invalid_user_password', __( 'Your current password is invalid.', 'buddypress' ) );
	}

	// Email feedback.
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
			// No change.
			break;
	}

	if ( is_wp_error( $pass_error ) && $pass_error->get_error_message() ) {
		$feedback[ $pass_error->get_error_code() ] = $pass_error->get_error_message();
	}

	// No errors so show a simple success message.
	if ( ( ( false === $email_error ) || ( false == $pass_error ) ) && ( ( true === $pass_changed ) || ( true === $email_changed ) ) ) {
		$feedback[]    = __( 'Your settings have been saved.', 'buddypress' );
		$feedback_type = 'success';

	// Some kind of errors occurred.
	} elseif ( ( ( false === $email_error ) || ( false === $pass_error ) ) && ( ( false === $pass_changed ) || ( false === $email_changed ) ) ) {
		if ( bp_is_my_profile() ) {
			$feedback['nochange'] = __( 'No changes were made to your account.', 'buddypress' );
		} else {
			$feedback['nochange'] = __( 'No changes were made to this account.', 'buddypress' );
		}
	}

	// Set the feedback.
	bp_core_add_message( implode( "\n", $feedback ), $feedback_type );

	/**
	 * Fires after the general settings have been saved, and before redirect.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_core_general_settings_after_save' );

	// Redirect to prevent issues with browser back button.
	bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() . '/general' ) );
}
add_action( 'bp_actions', 'bp_settings_action_general' );

/**
 * Process email change verification or cancel requests.
 *
 * @since 2.1.0
 */
function bp_settings_verify_email_change() {
	if ( ! bp_is_settings_component() ) {
		return;
	}

	if ( ! bp_is_my_profile() ) {
		return;
	}

	$redirect_to = trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() );

	// Email change is being verified.
	if ( isset( $_GET['verify_email_change'] ) ) {
		$user_id       = bp_displayed_user_id();
		$pending_email = (array) bp_get_user_meta( $user_id, 'pending_email_change', true );

		// The user may have dismissed the email change.
		if ( ! array_filter( $pending_email ) ) {
			/**
			 * Fires when a Pending Email Change is missing and before
			 * BuddyPress redirects the user to an error message.
			 *
			 * @since 9.1.0
			 *
			 * @param int    $user_id     The user ID.
			 * @param string $redirect_to The Default Front-end Settings Screen URL.
			 */
			do_action( 'bp_settings_missing_pending_email_change_hash', $user_id, $redirect_to );

			bp_core_add_message( __( 'There was a problem verifying your new email address. If you haven’t dismissed the pending email change, please request a new email update.', 'buddypress' ), 'error' );
			bp_core_redirect( $redirect_to );
		}

		// Bail if the hash provided doesn't match the one saved in the database.
		if ( ! hash_equals( urldecode( $_GET['verify_email_change'] ), $pending_email['hash'] ) ) {
			return;
		}

		$email_changed = wp_update_user( array(
			'ID'         => $user_id,
			'user_email' => trim( $pending_email['newemail'] ),
		) );

		if ( $email_changed ) {
			// Delete the pending email change key.
			bp_delete_user_meta( $user_id, 'pending_email_change' );

			/**
			 * Fires when a Pending Email Change has been validated and before
			 * BuddyPress redirects the user to a success message.
			 *
			 * @since 9.1.0
			 *
			 * @param int    $user_id     The user ID.
			 * @param string $redirect_to The Default Front-end Settings Screen URL.
			 */
			do_action( 'bp_settings_email_changed', $user_id, $redirect_to );

			// Post a success message and redirect.
			bp_core_add_message( __( 'You have successfully verified your new email address.', 'buddypress' ) );
		} else {
			// Unknown error.
			bp_core_add_message( __( 'There was a problem verifying your new email address. Please try again.', 'buddypress' ), 'error' );
		}

		bp_core_redirect( $redirect_to );

	// Email change is being dismissed.
	} elseif ( ! empty( $_GET['dismiss_email_change'] ) ) {
		$nonce_check = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'bp_dismiss_email_change' );

		if ( $nonce_check ) {
			$user_id = bp_displayed_user_id();
			bp_delete_user_meta( $user_id, 'pending_email_change' );

			/**
			 * Fires when a Pending Email Change has been dismissed and before
			 * BuddyPress redirects the user to a success message.
			 *
			 * @since 9.1.0
			 *
			 * @param int    $user_id     The user ID.
			 * @param string $redirect_to The Default Front-end Settings Screen URL.
			 */
			do_action( 'bp_settings_email_change_dismissed', $user_id, $redirect_to );

			bp_core_add_message( __( 'You have successfully dismissed your pending email change.', 'buddypress' ) );
		}

		bp_core_redirect( $redirect_to );
	}
}
add_action( 'bp_actions', 'bp_settings_verify_email_change' );
