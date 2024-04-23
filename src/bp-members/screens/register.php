<?php
/**
 * Members: Register screen handler
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 3.0.0
 */

/**
 * Handle the loading of the signup screen.
 *
 * @since 1.1.0
 */
function bp_core_screen_signup() {
	$bp = buddypress();

	if ( ! bp_is_current_component( 'register' ) || bp_current_action() ) {
		return;
	}

	// Not a directory.
	bp_update_is_directory( false, 'register' );

	// If the user is logged in, redirect away from here.
	if ( is_user_logged_in() ) {

		$redirect_to = bp_is_component_front_page( 'register' )
			? bp_get_members_directory_permalink()
			: bp_get_root_url();

		/**
		 * Filters the URL to redirect logged in users to when visiting registration page.
		 *
		 * @since 1.5.1
		 *
		 * @param string $redirect_to URL to redirect user to.
		 */
		bp_core_redirect( apply_filters( 'bp_loggedin_register_page_redirect_to', $redirect_to ) );

		return;
	}

	$bp->signup->step = 'request-details';

	// Could the user be accepting an invitation?
	$active_invite = false;
	if ( bp_get_members_invitations_allowed() ) {
		// Check to see if there's a valid invitation.
		$maybe_invite = bp_get_members_invitation_from_request();
		if ( $maybe_invite->id && $maybe_invite->invitee_email ) {
			// Check if this user is already a member.
			$args = array(
				'invitee_email' => $maybe_invite->invitee_email,
				'accepted'      => 'accepted',
				'fields'        => 'ids',
			);
			$accepted_invites = bp_members_invitations_get_invites( $args );
			if ( ! $accepted_invites ) {
				$active_invite = true;
			}
		}
	}

	$requests_enabled = bp_get_membership_requests_required();

	if ( ! bp_get_signup_allowed() && ! $active_invite && ! $requests_enabled ) {
		$bp->signup->step = 'registration-disabled';
		// If the signup page is submitted, validate and save.
	} elseif ( isset( $_POST['signup_submit'] ) && bp_verify_nonce_request( 'bp_new_signup' ) ) {

		/**
		 * Fires before the validation of a new signup.
		 *
		 * @since 2.0.0
		 */
		do_action( 'bp_signup_pre_validate' );

		// Check the base account details for problems.
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );

		// If there are errors with account details, set them for display.
		if ( ! empty( $account_details['errors']->errors['user_name'] ) ) {
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];
		}

		if ( ! empty( $account_details['errors']->errors['user_email'] ) ) {
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];
		}

		// Password strength check.
		$required_password_strength = bp_members_user_pass_required_strength();
		$current_password_strength  = null;
		if ( isset( $_POST['_password_strength_score'] ) ) {
			$current_password_strength = (int) $_POST['_password_strength_score'];
		}

		if ( $required_password_strength && ! is_null( $current_password_strength ) && $required_password_strength > $current_password_strength ) {
			$account_password = new WP_Error(
				'not_strong_enough_password',
				__( 'Your password is not strong enough to be allowed on this site. Please use a stronger password.', 'buddypress' )
			);
		} else {
			$signup_pass = '';
			if ( isset( $_POST['signup_password'] ) ) {
				$signup_pass = wp_unslash( $_POST['signup_password'] );
			}

			$signup_pass_confirm = '';
			if ( isset( $_POST['signup_password_confirm'] ) ) {
				$signup_pass_confirm = wp_unslash( $_POST['signup_password_confirm'] );
			}

			// Check the account password for problems.
			$account_password = bp_members_validate_user_password( $signup_pass, $signup_pass_confirm );
		}

		$password_error = $account_password->get_error_message();

		if ( $password_error ) {
			$bp->signup->errors['signup_password'] = $password_error;
		}

		if ( bp_signup_requires_privacy_policy_acceptance() && ! empty( $_POST['signup-privacy-policy-check'] ) && empty( $_POST['signup-privacy-policy-accept'] ) ) {
			$bp->signup->errors['signup_privacy_policy'] = __( 'You must indicate that you have read and agreed to the Privacy Policy.', 'buddypress' );
		}

		$bp->signup->username = $_POST['signup_username'];
		$bp->signup->email = $_POST['signup_email'];

		// Now we've checked account details, we can check profile information.
		if ( bp_is_active( 'xprofile' ) ) {

			// Make sure hidden field is passed and populated.
			if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

				// Let's compact any profile field info into an array.
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				// Loop through the posted fields formatting any datebox values then validate the field.
				foreach ( (array) $profile_field_ids as $field_id ) {
					bp_xprofile_maybe_format_datebox_post_data( $field_id );

					// Trim post fields.
					if ( isset( $_POST[ 'field_' . $field_id ] ) ) {
						if ( is_array( $_POST[ 'field_' . $field_id ] ) ) {
							$_POST[ 'field_' . $field_id ] = array_map( 'trim', $_POST[ 'field_' . $field_id ] );
						} else {
							$_POST[ 'field_' . $field_id ] = trim( $_POST[ 'field_' . $field_id ] );
						}
					}

					// Create errors for required fields without values.
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) )
						$bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
				}

				// This situation doesn't naturally occur so bounce to website root.
			} else {
				bp_core_redirect( bp_get_root_url() );
			}
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled.
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = bp_core_get_root_option( 'registration' );

			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

				// If there are errors with blog details, set them for display.
				if ( !empty( $blog_details['errors']->errors['blogname'] ) )
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

				if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
			}
		}

		/**
		 * Fires after the validation of a new signup.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_signup_validate' );

		// Add any errors to the action for the field in the template for display.
		if ( !empty( $bp->signup->errors ) ) {
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				/**
				 * Filters the error message in the loop.
				 *
				 * @since 1.5.0
				 * @since 8.0.0 Adds the `$fieldname` parameter to the anonymous function.
				 *
				 * @param string $value     Error message wrapped in html.
				 * @param string $fieldname The name of the signup field.
				 */
				add_action( 'bp_' . $fieldname . '_errors', function() use ( $error_message, $fieldname ) {
					echo wp_kses(
						/**
						 * Filter here to edit the error message about the invalid field value.
						 *
						 * @since 1.5.0
						 * @since 8.0.0 Adds the `$fieldname` parameter.
						 *
						 * @param string $value     Error message wrapped in html.
						 * @param string $fieldname The name of the signup field.
						 */
						apply_filters( 'bp_members_signup_error_message', "<div class=\"error\">" . $error_message . "</div>", $fieldname ),
						array(
							'div' => array( 'class' => true ),
						)
					);
				} );
			}
		} else {
			$bp->signup->step = 'save-details';

			// No errors! Let's register those deets.
			$active_signup = bp_core_get_root_option( 'registration' );

			if ( 'none' != $active_signup || $requests_enabled ) {

				// Make sure the extended profiles module is enabled.
				if ( bp_is_active( 'xprofile' ) ) {
					// Let's compact any profile field info into usermeta.
					$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

					/*
					 * Loop through the posted fields, formatting any
					 * datebox values, then add to usermeta.
					 */
					foreach ( (array) $profile_field_ids as $field_id ) {
						bp_xprofile_maybe_format_datebox_post_data( $field_id );

						if ( !empty( $_POST['field_' . $field_id] ) )
							$usermeta['field_' . $field_id] = $_POST['field_' . $field_id];

						if ( !empty( $_POST['field_' . $field_id . '_visibility'] ) )
							$usermeta['field_' . $field_id . '_visibility'] = $_POST['field_' . $field_id . '_visibility'];
					}

					// Store the profile field ID's in usermeta.
					$usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];
				}

				// Hash and store the password.
				$usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

				// If the user decided to create a blog, save those details to usermeta.
				if ( 'blog' == $active_signup || 'all' == $active_signup )
					$usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;

				/**
				 * Filters the user meta used for signup.
				 *
				 * @since 1.1.0
				 *
				 * @param array $usermeta Array of user meta to add to signup.
				 */
				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

				// Finally, sign up the user and/or blog.
				if ( isset( $_POST['signup_with_blog'] ) && is_multisite() )
					$wp_user_id = bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				else
					$wp_user_id = bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );

				if ( is_wp_error( $wp_user_id ) ) {
					$bp->signup->step = 'request-details';
					bp_core_add_message( $wp_user_id->get_error_message(), 'error' );
				} else {
					$bp->signup->step = 'completed-confirmation';
				}
			}

			/**
			 * Fires after the completion of a new signup.
			 *
			 * @since 1.1.0
			 */
			do_action( 'bp_complete_signup' );
		}

	}

	/**
	 * Fires right before the loading of the Member registration screen template file.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_core_screen_signup' );

	/**
	 * Filters the template to load for the Member registration page screen.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value Path to the Member registration template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'bp_core_screen_signup' );
