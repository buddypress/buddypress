<?php
/**
 * BuddyPress signups functions.
 *
 * @package BuddyPress
 * @subpackage MembersSignups
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Functions *****************************************************************/

/**
 * Add the appropriate errors to a WP_Error object, given results of a validation test.
 *
 * Functions like bp_core_validate_email_address() return a structured array
 * of error codes. bp_core_add_validation_error_messages() takes this array and
 * parses, adding the appropriate error messages to the WP_Error object.
 *
 * @since 1.7.0
 *
 * @see bp_core_validate_email_address()
 *
 * @param WP_Error $errors             WP_Error object.
 * @param array    $validation_results The return value of a validation function
 *                                     like bp_core_validate_email_address().
 */
function bp_core_add_validation_error_messages( WP_Error $errors, $validation_results ) {
	if ( ! empty( $validation_results['invalid'] ) ) {
		$errors->add( 'user_email', __( 'Please check your email address.', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['domain_banned'] ) ) {
		$errors->add( 'user_email',  __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['domain_not_allowed'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	if ( ! empty( $validation_results['in_use'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddypress' ) );
	}
}

/**
 * Validate a user name and email address when creating a new user.
 *
 * @since 1.2.2
 *
 * @param string $user_name  Username to validate.
 * @param string $user_email Email address to validate.
 * @return array Results of user validation including errors, if any.
 */
function bp_core_validate_user_signup( $user_name, $user_email ) {

	// Make sure illegal names include BuddyPress slugs and values.
	bp_core_flush_illegal_names();

	// WordPress Multisite has its own validation. Use it, so that we
	// properly mirror restrictions on username, etc.
	if ( function_exists( 'wpmu_validate_user_signup' ) ) {
		$result = wpmu_validate_user_signup( $user_name, $user_email );

	// When not running Multisite, we perform our own validation. What
	// follows reproduces much of the logic of wpmu_validate_user_signup(),
	// minus the multisite-specific restrictions on user_login.
	} else {
		$errors = new WP_Error();

		/**
		 * Filters the username before being validated.
		 *
		 * @since 1.5.5
		 *
		 * @param string $user_name Username to validate.
		 */
		$user_name = apply_filters( 'pre_user_login', $user_name );

		// User name can't be empty.
		if ( empty( $user_name ) ) {
			$errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );
		}

		// User name can't be on the list of illegal names.
		$illegal_names = get_site_option( 'illegal_names' );
		if ( in_array( $user_name, (array) $illegal_names, true ) ) {
			$errors->add( 'user_name', __( 'That username is not allowed.', 'buddypress' ) );
		}

		// User name must pass WP's validity check.
		if ( ! validate_username( $user_name ) ) {
			$errors->add( 'user_name', __( 'Usernames can contain only letters, numbers, ., -, and @', 'buddypress' ) );
		}

		// Minimum of 4 characters.
		if ( strlen( $user_name ) < 4 ) {
			$errors->add( 'user_name', __( 'Username must be at least 4 characters.', 'buddypress' ) );
		}

		// Maximum of 60 characters.
		if ( strlen( $user_name ) > 60 ) {
			$errors->add( 'user_name', __( 'Username may not be longer than 60 characters.', 'buddypress' ) );
		}

		// No underscores. @todo Why not?
		if ( str_contains( ' ' . $user_name, '_' ) ) {
			$errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );
		}

		// No usernames that are all numeric. @todo Why?
		$match = array();
		preg_match( '/[0-9]*/', $user_name, $match );

		// Check for valid letters.
		$valid_letters = preg_match( '/[a-zA-Z]+/', $user_name );

		if ( $match[0] === $user_name || ! $valid_letters ) {
			$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );
		}

		// Check into signups.
		$signups = BP_Signup::get(
			array(
				'user_login' => $user_name,
			)
		);

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Check if the username has been used already.
		if ( username_exists( $user_name ) || ! empty( $signup ) ) {
			$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );
		}

		// Validate the email address and process the validation results into
		// error messages.
		$validate_email = bp_core_validate_email_address( $user_email );
		bp_core_add_validation_error_messages( $errors, $validate_email );

		// Assemble the return array.
		$result = array(
			'user_name'  => $user_name,
			'user_email' => $user_email,
			'errors'     => $errors,
		);

		// Apply WPMU legacy filter.
		$result = apply_filters( 'wpmu_validate_user_signup', $result );
	}

	/**
	 * Filters the result of the user signup validation.
	 *
	 * @since 1.2.2
	 *
	 * @param array $result Results of user validation including errors, if any.
	 */
	return apply_filters( 'bp_core_validate_user_signup', $result );
}

/**
 * Validate blog URL and title provided at signup.
 *
 * @since 1.2.2
 *
 * @todo Why do we have this wrapper?
 *
 * @param string $blog_url   Blog URL requested during registration.
 * @param string $blog_title Blog title requested during registration.
 * @return array
 */
function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_validate_blog_signup' ) ) {
		return false;
	}

	/**
	 * Filters the validated blog url and title provided at signup.
	 *
	 * @since 1.2.2
	 *
	 * @param array $value Array with the new site data and error messages.
	 */
	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

/**
 * Process data submitted at user registration and convert to a signup object.
 *
 * @since 1.2.0
 *
 * @todo There appears to be a bug in the return value on success.
 *
 * @param string $user_login    Login name requested by the user.
 * @param string $user_password Password requested by the user.
 * @param string $user_email    Email address entered by the user.
 * @param array  $usermeta      Miscellaneous metadata about the user (blog-specific
 *                              signup data, xprofile data, etc).
 * @return int|false True on success, WP_Error on failure.
 */
function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	$bp = buddypress();

	// We need to cast $user_id to pass to the filters.
	$user_id = false;

	// Multisite installs have their own install procedure.
	if ( is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

	} else {
		// Format data.
		$user_login     = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
		$user_email     = sanitize_email( $user_email );
		$activation_key = wp_generate_password( 32, false );
		$create_user    = false;

		// @deprecated.
		if ( defined( 'BP_SIGNUPS_SKIP_USER_CREATION' ) ) {
			_doing_it_wrong( 'BP_SIGNUPS_SKIP_USER_CREATION', esc_html__( 'the `BP_SIGNUPS_SKIP_USER_CREATION` constant is deprecated as skipping user creation is now the default behavior.', 'buddypress' ), 'BuddyPress 14.0.0' );
		}

		/**
		 * Please stop using this deprecated filter.
		 *
		 * It was here to keep creating a user when a registration is performed on regular WordPress configs.
		 *
		 * @since 14.0.0
		 * @deprecated in 15.0.0
		 *
		 * @param boolean $create_user True to carry on creating a user when a registration is performed.
		 *                             False otherwise.
		 */
		apply_filters_deprecated( 'bp_signups_create_user', array( $create_user ), '15.0.0' );

		BP_Signup::add(
			array(
				'user_login'     => $user_login,
				'user_email'     => $user_email,
				'activation_key' => $activation_key,
				'meta'           => $usermeta,
			)
		);

		/**
		 * Filters if BuddyPress should send an activation key for a new signup.
		 *
		 * @since 1.2.3
		 *
		 * @param bool   $value          Whether or not to send the activation key.
		 * @param int    $user_id        User ID to send activation key to.
		 * @param string $user_email     User email to send activation key to.
		 * @param string $activation_key Activation key to be sent.
		 * @param array  $usermeta       Miscellaneous metadata about the user (blog-specific
		 *                               signup data, xprofile data, etc).
		 */
		if ( apply_filters( 'bp_core_signup_send_activation_key', true, $user_id, $user_email, $activation_key, $usermeta ) ) {
			$salutation = $user_login;
			if ( bp_is_active( 'xprofile' ) && isset( $usermeta[ 'field_' . bp_xprofile_fullname_field_id() ] ) ) {
				$salutation = $usermeta[ 'field_' . bp_xprofile_fullname_field_id() ];
			}

			bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key, $salutation );
		}
	}

	$bp->signup->username = $user_login;

	/**
	 * Fires at the end of the process to sign up a user.
	 *
	 * @since 1.2.2
	 *
	 * @param bool|WP_Error   $user_id       True on success, WP_Error on failure.
	 * @param string          $user_login    Login name requested by the user.
	 * @param string          $user_password Password requested by the user.
	 * @param string          $user_email    Email address requested by the user.
	 * @param array           $usermeta      Miscellaneous metadata about the user (blog-specific
	 *                                       signup data, xprofile data, etc).
	 */
	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

/**
 * Create a blog and user based on data supplied at user registration.
 *
 * @since 1.2.2
 *
 * @param string $blog_domain Domain requested by user.
 * @param string $blog_path   Path requested by user.
 * @param string $blog_title  Title as entered by user.
 * @param string $user_name   user_login of requesting user.
 * @param string $user_email  Email address of requesting user.
 * @param string $usermeta    Miscellaneous metadata for the user.
 * @return bool|null
 */
function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_signup_blog' ) ) {
		return false;
	}

	wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta );

	/**
	 * Filters the result of wpmu_signup_blog().
	 *
	 * This filter provides no value and is retained for
	 * backwards compatibility.
	 *
	 * @since 1.2.2
	 *
	 * @param null $value Null value.
	 */
	return apply_filters( 'bp_core_signup_blog', null );
}

/**
 * Activate a signup, as identified by an activation key.
 *
 * @since 1.2.2
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param string $key Activation key.
 * @return int|bool User ID on success, false on failure.
 */
function bp_core_activate_signup( $key ) {
	global $wpdb;

	$user = false;

	// Multisite installs have their own activation routine.
	if ( is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		// If there were errors, add a message and redirect.
		if ( ! empty( $user->errors ) ) {
			return $user;
		}

		$user_id = $user['user_id'];

	} else {
		$signups = BP_Signup::get(
			array(
				'activation_key' => $key,
			)
		);

		if ( empty( $signups['signups'] ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid activation key.', 'buddypress' ) );
		}

		$signup = $signups['signups'][0];

		if ( $signup->active ) {
			if ( empty( $signup->domain ) ) {
				return new WP_Error( 'already_active', __( 'The user is already active.', 'buddypress' ), $signup );
			} else {
				return new WP_Error( 'already_active', __( 'The site is already active.', 'buddypress' ), $signup );
			}
		}

		// Password is hashed again in wp_insert_user.
		$password = wp_generate_password( 12, false );
		$user_id  = username_exists( $signup->user_login );

		// Create the user.
		if ( ! $user_id ) {
			$user_id = wp_create_user( $signup->user_login, $password, $signup->user_email );

		} else {
			$user_already_exists = true;
		}

		if ( ! $user_id ) {
			return new WP_Error( 'create_user', __( 'Could not create user', 'buddypress' ), $signup );
		}

		// Fetch the signup so we have the data later on.
		$signups = BP_Signup::get(
			array(
				'activation_key' => $key,
			)
		);

		$signup = false;
		if ( isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ) {
			$signup = $signups['signups'][0];
		}

		// Activate the signup.
		BP_Signup::validate( $key );

		if ( isset( $user_already_exists ) ) {
			return new WP_Error( 'user_already_exists', __( 'That username is already activated.', 'buddypress' ), $signup );
		}

		// Set up data to pass to the legacy filter.
		$user = array(
			'user_id'  => $user_id,
			'password' => isset( $signup->meta['password'] ) ? $signup->meta['password'] : '',
			'meta'     => $signup->meta,
		);

		/**
		 * Maybe notify the site admin of a new user registration.
		 *
		 * @since 1.2.2
		 *
		 * @param bool $notification Whether to send the notification or not.
		 */
		if ( apply_filters( 'bp_core_send_user_registration_admin_notification', true ) ) {
			wp_new_user_notification( $user_id );
		}

		if ( isset( $user_already_created ) ) {

			/**
			 * Fires if the user has already been created.
			 *
			 * @since 1.2.2
			 *
			 * @param int    $user_id ID of the user being checked.
			 * @param string $key     Activation key.
			 * @param array  $user    Array of user data.
			 */
			do_action( 'bp_core_activated_user', $user_id, $key, $user );
			return $user_id;
		}
	}

	// Set any profile data.
	if ( bp_is_active( 'xprofile' ) ) {
		if ( ! empty( $user['meta']['profile_field_ids'] ) ) {
			$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

			foreach ( (array) $profile_field_ids as $field_id ) {
				$current_field = isset( $user['meta']["field_{$field_id}"] ) ? $user['meta']["field_{$field_id}"] : false;

				if ( ! empty( $current_field ) ) {
					xprofile_set_field_data( $field_id, $user_id, $current_field );
				}

				/*
				 * Save the visibility level.
				 *
				 * Use the field's default visibility if not present, and 'public' if a
				 * default visibility is not defined.
				 */
				$key = "field_{$field_id}_visibility";
				if ( isset( $user['meta'][ $key ] ) ) {
					$visibility_level = $user['meta'][ $key ];
				} else {
					$vfield           = xprofile_get_field( $field_id, null, false );
					$visibility_level = isset( $vfield->default_visibility ) ? $vfield->default_visibility : 'public';
				}
				xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
			}
		}
	}

	// Replace the password automatically generated by WordPress by the one the user chose.
	if ( ! empty( $user['meta']['password'] ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

		/**
		 * Make sure to clean the user's cache as we've
		 * directly edited the password without using
		 * wp_update_user().
		 *
		 * If we can't use wp_update_user() that's because
		 * we already hashed the password at the signup step.
		 */
		$uc = wp_cache_get( $user_id, 'users' );

		if ( ! empty( $uc->ID ) ) {
			clean_user_cache( $uc->ID );
		}
	}

	/**
	 * Fires at the end of the user activation process.
	 *
	 * @since 1.2.2
	 *
	 * @param int    $user_id ID of the user being checked.
	 * @param string $key     Activation key.
	 * @param array  $user    Array of user data.
	 */
	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

/**
 * Get the avatar storage directory for use during registration.
 *
 * @since 1.1.0
 *
 * @return string|bool Directory path on success, false on failure.
 */
function bp_core_signup_avatar_upload_dir() {
	$bp = buddypress();

	if ( empty( $bp->signup->avatar_dir ) ) {
		return false;
	}

	$directory = 'avatars/signups';
	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $bp->signup->avatar_dir;

	/**
	 * Filters the avatar storage directory for use during registration.
	 *
	 * @since 1.1.1
	 *
	 * @param array $value Array of path and URL values for created storage directory.
	 */
	return apply_filters(
		'bp_core_signup_avatar_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * Send activation email to a newly registered user.
 *
 * @since 1.2.2
 * @since 2.5.0 Add the $user_login parameter.
 * @since 5.0.0 Change $user_login parameter to more general $salutation.
 *
 * @param int|bool $user_id    ID of the new user, false if BP_SIGNUPS_SKIP_USER_CREATION is true.
 * @param string   $user_email   Email address of the new user.
 * @param string   $key          Activation key.
 * @param string   $salutation   Optional. The name to be used as a salutation in the email.
 */
function bp_core_signup_send_validation_email( $user_id, $user_email, $key, $salutation = '' ) {
	$args = array(
		'tokens' => array(
			'activate.url' => esc_url( trailingslashit( bp_get_activation_page() ) . "{$key}/" ),
			'key'          => $key,
			'user.email'   => $user_email,
			'user.id'      => $user_id,
		),
	);

	$to = array( array( $user_email => $salutation ) );

	bp_send_email( 'core-user-registration', $to, $args );

	// Record that the activation email has been sent.
	$signup = bp_members_get_signup_by( 'activation_key', $key );

	if ( $signup ) {
		BP_Signup::update(
			array(
				'signup_id' => $signup->id,
				'meta'      => array(
					'sent_date'  => current_time( 'mysql', true ),
					'count_sent' => $signup->count_sent + 1
				),
			)
		);
	}
}

/**
 * Display a "resend email" link when an unregistered user attempts to log in.
 *
 * @since 1.2.2
 *
 * @param WP_User|WP_Error|null $user     Either the WP_User or the WP_Error object.
 * @param string                $username The inputted, attempted username.
 * @param string                $password The inputted, attempted password.
 * @return WP_User|WP_Error
 */
function bp_core_signup_disable_inactive( $user = null, $username = '', $password ='' ) {
	// Login form not used.
	if ( empty( $username ) && empty( $password ) ) {
		return $user;
	}

	/*
	 * An existing WP_User with a user_status of 2 is either a legacy signup, or is a user
	 * created for backward compatibility. See {@link bp_core_signup_user()} for more details.
	 */
	if ( $user instanceof WP_User && 2 == $user->user_status ) {
		$user_login = $user->user_login;

		// If no WP_User is found corresponding to the username, this is a potential signup.
	} elseif ( is_wp_error( $user ) && 'invalid_username' == $user->get_error_code() ) {
		$user_login = $username;

		// This is an activated user, so bail.
	} else {
		return $user;
	}

	// Look for the unactivated signup corresponding to the login name.
	$signup = BP_Signup::get(
		array(
			'user_login' => sanitize_user( $user_login )
		)
	);

	// No signup or more than one, something is wrong. Let's bail.
	if ( empty( $signup['signups'][0] ) || $signup['total'] > 1 ) {
		return $user;
	}

	/*
	 * Unactivated user account found!
	 * Don't allow users to resend their own activation email
	 * when membership requests are enabled.
	 */
	if ( bp_get_membership_requests_required() ) {
		$error_message = sprintf(
			'<strong>%1$s</strong> %2$s',
			esc_html_x( 'Error:', 'Warning displayed on the WP Login screen', 'buddypress' ),
			esc_html_x( 'Your membership request has not yet been approved.', 'Error message displayed on the WP Login screen', 'buddypress' )
		);

		// Set up the feedback message.
	} else {
		$signup_id = $signup['signups'][0]->signup_id;

		$resend_url_params = array(
			'action' => 'bp-resend-activation',
			'id'     => $signup_id,
		);

		$resend_url = wp_nonce_url(
			add_query_arg( $resend_url_params, wp_login_url() ),
			'bp-resend-activation'
		);

		$error_message = sprintf(
			'<strong>%1$s</strong> %2$s<br /><br />%3$s',
			esc_html_x( 'Error:', 'Warning displayed on the WP Login screen', 'buddypress' ),
			esc_html_x( 'Your account has not been activated. Check your email for the activation link.', 'Error message displayed on the WP Login screen', 'buddypress' ),
			sprintf(
				/* translators: %s: the link to resend the activation email. */
				esc_html_x( 'If you have not received an email yet, %s.', 'WP Login screen message', 'buddypress' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $resend_url ),
					esc_html_x( 'click here to resend it', 'Text of the link to resend the activation email', 'buddypress' )
				)
			)
		);
	}

	return new WP_Error( 'bp_account_not_activated', $error_message );
}

/**
 * On the login screen, resends the activation email for a user.
 *
 * @since 2.0.0
 *
 * @global string $error The error message.
 *
 * @see bp_core_signup_disable_inactive()
 */
function bp_members_login_resend_activation_email() {
	global $error;

	if ( empty( $_GET['id'] ) || empty( $_GET['_wpnonce'] ) ) {
		return;
	}

	// Verify nonce.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bp-resend-activation' ) ) {
		wp_die( esc_html__( 'This request has been interrupted for security reasons.', 'buddypress' ) );
	}

	$signup_id = (int) $_GET['id'];

	// Resend the activation email.
	// also updates the 'last sent' and '# of emails sent' values.
	$resend = BP_Signup::resend( array( $signup_id ) );

	// Add feedback message.
	if ( ! empty( $resend['errors'] ) ) {
		$error = __( '<strong>Error</strong>: Your account has already been activated.', 'buddypress' );
	} else {
		$error = __( 'Activation email resent! Please check your inbox or spam folder.', 'buddypress' );
	}
}

/**
 * Redirect away from wp-signup.php if BP registration templates are present.
 *
 * @since 1.1.0
 */
function bp_core_wpsignup_redirect() {

	// Bail in admin or if custom signup page is broken.
	if ( is_admin() || ! bp_has_custom_signup_page() ) {
		return;
	}

	$is_wp_signup = false;
	if ( ! empty( $_SERVER['SCRIPT_NAME'] ) ) {
		$script_name_path = wp_parse_url( $_SERVER['SCRIPT_NAME'], PHP_URL_PATH );

		if ( 'wp-signup.php' === basename( $script_name_path ) || ( 'wp-login.php' === basename( $script_name_path ) && ! empty( $_GET['action'] ) && 'register' === $_GET['action'] ) ) {
			$is_wp_signup = true;
		}
	}

	// If this is not wp-signup.php, there's nothing to do here.
	if ( ! $is_wp_signup ) {
		return;
	}

	/*
	 * We redirect wp-signup.php to the registration page except when it's a site signup.
	 * In that case, redirect to the BP site creation page if available, otherwise allow
	 * access to wp-signup.php.
	 */
	$redirect_to = bp_get_signup_page();
	$referer     = wp_get_referer();

	$is_site_creation = false;

	// A new site is being added.
	if ( isset( $_POST['stage'] ) && $_POST['stage'] === 'gimmeanotherblog' ) {
		$is_site_creation = true;

	// We've arrived at wp-signup.php from my-sites.php.
	} elseif ( $referer ) {
		$referer_path     = wp_parse_url( $referer, PHP_URL_PATH );
		$is_site_creation = false !== strpos( $referer_path, 'wp-admin/my-sites.php' );
	} else {
		// The WordPress registration setting must allow access.
		$registration = get_site_option( 'registration' );
		if ( is_user_logged_in() && in_array( $registration, array( 'blog', 'all' ), true ) ) {
			$is_site_creation = true;
		}
	}

	if ( $is_site_creation ) {
		if ( bp_is_active( 'blogs' ) ) {
			$url = bp_get_blogs_directory_url(
				array(
					'create_single_item' => 1,
				)
			);

			$redirect_to = trailingslashit( $url );

			// Perform no redirect in this case.
		} else {
			$redirect_to = '';
		}
	}

	if ( ! $redirect_to ) {
		return;
	}

	bp_core_redirect( $redirect_to );
}

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @since 1.2.1
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @return string Filtered $welcome_email with the password replaced
 *                by '[User Set]'.
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {

	// Don't touch the email when a user is registered by the site admin.
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page !== get_current_screen()->id ) {
		return $welcome_email;
	}

	if ( strpos( bp_get_requested_url(), 'wp-activate.php' ) !== false ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template.
	if ( ! bp_has_custom_signup_page() ) {
		return $welcome_email;
	}

	// [User Set] Replaces 'PASSWORD' in welcome email; Represents value set by user
	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @since 1.2.1
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @param int    $blog_id       ID of the blog user is joining.
 * @param int    $user_id       ID of the user joining.
 * @param string $password      Password of user.
 * @return string Filtered $welcome_email with $password replaced by '[User Set]'.
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {

	// Don't touch the email when a user is registered by the site admin.
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page !== get_current_screen()->id ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template.
	if ( ! bp_has_custom_signup_page() ) {
		return $welcome_email;
	}

	// [User Set] Replaces $password in welcome email; Represents value set by user.
	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}

/**
 * Notify new users of a successful registration (with blog).
 *
 * This function filter's WP's 'wpmu_signup_blog_notification', and replaces
 * WP's default welcome email with a BuddyPress-specific message.
 *
 * @since 1.0.0
 *
 * @see wpmu_signup_blog_notification() for a description of parameters.
 *
 * @param string $domain     The new blog domain.
 * @param string $path       The new blog path.
 * @param string $title      The site title.
 * @param string $user       The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key        The activation key created in wpmu_signup_blog().
 * @return bool              Returns false to stop original WPMU function from continuing.
 */
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key ) {
	$is_signup_resend = false;
	if ( is_admin() && buddypress()->members->admin->signups_page === get_current_screen()->id ) {
		// The admin is just approving/sending/resending the verification email.
		$is_signup_resend = true;
	}

	$args = array(
		'tokens' => array(
			'activate-site.url' => esc_url( bp_get_activation_page() . '?key=' . urlencode( $key ) ),
			'domain'            => $domain,
			'key_blog'          => $key,
			'path'              => $path,
			'user-site.url'     => esc_url( set_url_scheme( "http://{$domain}{$path}" ) ),
			'title'             => $title,
			'user.email'        => $user_email,
		),
	);

	$signup     = bp_members_get_signup_by( 'activation_key', $key );
	$salutation = $user;
	if ( $signup && bp_is_active( 'xprofile' ) ) {
		if ( isset( $signup->meta[ 'field_' . bp_xprofile_fullname_field_id() ] ) ) {
			$salutation = $signup->meta[ 'field_' . bp_xprofile_fullname_field_id() ];
		}
	}

	/**
	 * Filters if BuddyPress should send an activation key for a new multisite signup.
	 *
	 * @since 10.0.0
	 *
	 * @param string $user             The user's login name.
	 * @param string $user_email       The user's email address.
	 * @param string $key              The activation key created in wpmu_signup_blog().
	 * @param bool   $is_signup_resend Is the site admin sending this email?
	 * @param string $domain           The new blog domain.
	 * @param string $path             The new blog path.
	 * @param string $title            The site title.
	 */
	if ( apply_filters( 'bp_core_signup_send_activation_key_multisite_blog', true, $user, $user_email, $key, $is_signup_resend, $domain, $path, $title ) ) {
		bp_send_email( 'core-user-registration-with-blog', array( array( $user_email => $salutation ) ), $args );
	}

	// Return false to stop the original WPMU function from continuing.
	return false;
}

/**
 * Notify new users of a successful registration (without blog).
 *
 * @since 1.0.0
 *
 * @see wpmu_signup_user_notification() for a full description of params.
 *
 * @param string $user       The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key        The activation key created in wpmu_signup_user().
 * @param array  $meta       By default, an empty array.
 * @return false|string Returns false to stop original WPMU function from continuing.
 */
function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {
	$is_signup_resend = false;
	if ( is_admin() ) {

		// If the user is created from the WordPress Add User screen, don't send BuddyPress signup notifications.
		if ( in_array( get_current_screen()->id, array( 'user', 'user-network' ), true ) ) {
			// If the Super Admin want to skip confirmation email.
			if ( isset( $_POST['noconfirmation'] ) && is_super_admin() ) {
				return false;

				// WordPress will manage the signup process.
			} else {
				return $user;
			}

			// The site admin is approving/resending from the "manage signups" screen.
		} elseif ( buddypress()->members->admin->signups_page === get_current_screen()->id ) {
			/*
			 * There can be a case where the user was created without the skip confirmation
			 * And the super admin goes in pending accounts to resend it. In this case, as the
			 * meta['password'] is not set, the activation url must be WordPress one.
			 */
			$is_hashpass_in_meta = maybe_unserialize( $meta );

			if ( empty( $is_hashpass_in_meta['password'] ) ) {
				return $user;
			}

			// Or the admin is just approving/sending/resending the verification email.
			$is_signup_resend = true;
		}
	}

	$user_id     = 0;
	$user_object = get_user_by( 'login', $user );
	if ( $user_object ) {
		$user_id = $user_object->ID;
	}

	$salutation = $user;
	if ( bp_is_active( 'xprofile' ) && isset( $meta[ 'field_' . bp_xprofile_fullname_field_id() ] ) ) {
		$salutation = $meta[ 'field_' . bp_xprofile_fullname_field_id() ];
	} elseif ( $user_id ) {
		$salutation = bp_core_get_user_displayname( $user_id );
	}

	$args = array(
		'tokens' => array(
			'activate.url' => esc_url( trailingslashit( bp_get_activation_page() ) . "{$key}/" ),
			'key'          => $key,
			'user.email'   => $user_email,
			'user.id'      => $user_id,
		),
	);

	/**
	 * Filters if BuddyPress should send an activation key for a new multisite signup.
	 *
	 * @since 10.0.0
	 *
	 * @param string $user             The user's login name.
	 * @param string $user_email       The user's email address.
	 * @param string $key              The activation key created in wpmu_signup_blog().
	 * @param bool   $is_signup_resend Is the site admin sending this email?
	 */
	if ( apply_filters( 'bp_core_signup_send_activation_key_multisite', true, $user, $user_email, $key, $is_signup_resend ) ) {
		bp_send_email( 'core-user-registration', array( array( $user_email => $salutation ) ), $args );
	}

	// Return false to stop the original WPMU function from continuing.
	return false;
}

/**
 * Ensure that some meta values are set for new multisite signups.
 *
 * @since 10.0.0
 *
 * @see wpmu_signup_user() for a full description of params.
 *
 * @param array $meta Signup meta data. Default empty array.
 * @return array Signup meta data.
 */
function bp_core_add_meta_to_multisite_signups( $meta ) {

	// Ensure that sent_date and count_sent are set in meta.
	if ( ! isset( $meta['sent_date'] ) ) {
		$meta['sent_date'] = '0000-00-00 00:00:00';
	}
	if ( ! isset( $meta['count_sent'] ) ) {
		$meta['count_sent'] = 0;
	}

	return $meta;
}

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

/**
 * Get WP_User object corresponding to a record in the signups table.
 *
 * @since 10.0.0
 *
 * @param string $field Which fields to search by. Possible values are
 *                      activation_key, user_email, id.
 * @param string $value Value to search by.
 * @return bool|BP_Signup $signup Found signup, returns first found
 *                                if more than one is found.
 */
function bp_members_get_signup_by( $field = 'activation_key', $value = '' ) {
	switch ( $field ) {
		case 'activation_key':
		case 'user_email':
			$key = $field;
			break;

		case 'id':
		default:
			$key = 'include';
			break;
	}

	$signups = BP_Signup::get(
		array(
			$key => $value,
		)
	);

	if ( ! empty( $signups['signups'] ) ) {
		$signup = current( $signups['signups'] );
	} else {
		$signup = false;
	}

	return $signup;
}

/** Templates *****************************************************************/

/**
 * Do we have a working custom sign up page?
 *
 * @since 1.5.0
 *
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_signup_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_signup_slug() && bp_locate_template( array( 'registration/register.php', 'members/register.php', 'register.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL to the signup page.
 *
 * @since 1.0.0
 */
function bp_signup_page() {
	echo esc_url( bp_get_signup_page() );
}

/**
 * Get the URL to the signup page.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bp_get_signup_page() {
	if ( bp_has_custom_signup_page() ) {
		$page = bp_rewrites_get_url(
			array(
				'component_id'    => 'members',
				'member_register' => 1,
			)
		);

	} else {
		$page = trailingslashit( bp_get_root_url() ) . 'wp-signup.php';
	}

	/**
	 * Filters the URL to the signup page.
	 *
	 * @since 1.1.0
	 *
	 * @param string $page URL to the signup page.
	 */
	return apply_filters( 'bp_get_signup_page', $page );
}

/**
 * Do we have a working custom activation page?
 *
 * @since 1.5.0
 *
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_activation_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_activate_slug() && bp_locate_template( array( 'registration/activate.php', 'members/activate.php', 'activate.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL of the activation page.
 *
 * @since 1.0.0
 */
function bp_activation_page() {
	echo esc_url( bp_get_activation_page() );
}

/**
 * Get the URL of the activation page.
 *
 * @since 1.2.0
 *
 * @return string
 */
function bp_get_activation_page() {
	if ( bp_has_custom_activation_page() ) {
		$page = bp_rewrites_get_url(
			array(
				'component_id'    => 'members',
				'member_activate' => 1,
			)
		);

	} else {
		$page = trailingslashit( bp_get_root_url() ) . 'wp-activate.php';
	}

	/**
	 * Filters the URL of the activation page.
	 *
	 * @since 1.2.0
	 *
	 * @param string $page URL to the activation page.
	 */
	return apply_filters( 'bp_get_activation_page', $page );
}

/**
 * Get the activation key from the current request URL.
 *
 * @since 3.0.0
 *
 * @return string
 */
function bp_get_current_activation_key() {
	$key = '';

	if ( bp_is_current_component( 'activate' ) ) {
		if ( isset( $_GET['key'] ) ) {
			$key = wp_unslash( $_GET['key'] );
		} else {
			$key = bp_current_action();
		}
	}

	/**
	 * Filters the activation key from the current request URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key Activation key.
	 */
	return apply_filters( 'bp_get_current_activation_key', $key );
}

/**
 * Output the username submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_username_value() {
	echo esc_html( bp_get_signup_username_value() );
}

/**
 * Get the username submitted during signup.
 *
 * @since 1.1.0
 *
 * @todo This should be properly escaped.
 *
 * @return string
 */
function bp_get_signup_username_value() {
	$value = '';
	if ( isset( $_POST['signup_username'] ) )
		$value = $_POST['signup_username'];

	/**
	 * Filters the username submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value Username submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_username_value', $value );
}

/**
 * Output the user email address submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_email_value() {
	echo esc_html( bp_get_signup_email_value() );
}

/**
 * Get the email address submitted during signup.
 *
 * @since 1.1.0
 *
 * @todo This should be properly escaped.
 *
 * @return string
 */
function bp_get_signup_email_value() {
	$value = '';
	if ( isset( $_POST['signup_email'] ) ) {
		$value = $_POST['signup_email'];
	} else if ( bp_get_members_invitations_allowed() ) {
		$invite = bp_get_members_invitation_from_request();
		if ( $invite ) {
			$value = $invite->invitee_email;
		}
	}

	/**
	 * Filters the email address submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value Email address submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_email_value', $value );
}

/**
 * Output the 'signup_with_blog' value submitted during signup.
 *
 * @since 1.1.0
 */
function bp_signup_with_blog_value() {
	echo intval( bp_get_signup_with_blog_value() );
}

/**
 * Get the 'signup_with_blog' value submitted during signup.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bp_get_signup_with_blog_value() {
	$value = '';
	if ( isset( $_POST['signup_with_blog'] ) )
		$value = $_POST['signup_with_blog'];

	/**
	 * Filters the 'signup_with_blog' value submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value 'signup_with_blog' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_with_blog_value', $value );
}

/**
 * Output the 'signup_blog_url' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_url_value() {
	echo esc_url( bp_get_signup_blog_url_value() );
}

/**
 * Get the 'signup_blog_url' value submitted at signup.
 *
 * @since 1.1.0
 *
 * @todo Should be properly escaped.
 *
 * @return string
 */
function bp_get_signup_blog_url_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_url'] ) )
		$value = $_POST['signup_blog_url'];

	/**
	 * Filters the 'signup_blog_url' value submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value 'signup_blog_url' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_url_value', $value );
}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since 2.1.0
 */
function bp_signup_subdomain_base() {
	echo esc_attr( bp_signup_get_subdomain_base() );
}

/**
 * Return the base URL for subdomain installations of WordPress Multisite.
 *
 * Replaces bp_blogs_get_subdomain_base()
 *
 * @since 2.1.0
 *
 * @global WP_Network $current_site
 *
 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
 */
function bp_signup_get_subdomain_base() {
	global $current_site;

	// In case plugins are still using this filter.
	$subdomain_base = apply_filters( 'bp_blogs_subdomain_base', preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path );

	/**
	 * Filters the base URL for subdomain installations of WordPress Multisite.
	 *
	 * @since 2.1.0
	 *
	 * @param string $subdomain_base The base URL - eg, 'example.com' for
	 *                               site_url() example.com or www.example.com.
	 */
	return apply_filters( 'bp_signup_subdomain_base', $subdomain_base );
}

/**
 * Output the 'signup_blog_titl' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_title_value() {
	echo esc_html( bp_get_signup_blog_title_value() );
}

/**
 * Get the 'signup_blog_title' value submitted at signup.
 *
 * @since 1.1.0
 *
 * @todo Should be properly escaped.
 *
 * @return string
 */
function bp_get_signup_blog_title_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_title'] ) )
		$value = $_POST['signup_blog_title'];

	/**
	 * Filters the 'signup_blog_title' value submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value 'signup_blog_title' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_title_value', $value );
}

/**
 * Output the 'signup_blog_privacy' value submitted at signup.
 *
 * @since 1.1.0
 */
function bp_signup_blog_privacy_value() {
	echo esc_html( bp_get_signup_blog_privacy_value() );
}

/**
 * Get the 'signup_blog_privacy' value submitted at signup.
 *
 * @since 1.1.0
 *
 * @todo Should be properly escaped.
 *
 * @return string
 */
function bp_get_signup_blog_privacy_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_privacy'] ) )
		$value = $_POST['signup_blog_privacy'];

	/**
	 * Filters the 'signup_blog_privacy' value submitted during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value 'signup_blog_privacy' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_privacy_value', $value );
}

/**
 * Output the avatar dir used during signup.
 *
 * @since 1.1.0
 */
function bp_signup_avatar_dir_value() {
	echo esc_html( bp_get_signup_avatar_dir_value() );
}

/**
 * Get the avatar dir used during signup.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bp_get_signup_avatar_dir_value() {
	$bp = buddypress();

	// Check if signup_avatar_dir is passed.
	if ( ! empty( $_POST['signup_avatar_dir'] ) ) {
		$signup_avatar_dir = $_POST['signup_avatar_dir'];

		// If not, check if global is set.
	} elseif ( ! empty( $bp->signup->avatar_dir ) ) {
		$signup_avatar_dir = $bp->signup->avatar_dir;

		// If not, set false.
	} else {
		$signup_avatar_dir = false;
	}

	/**
	 * Filters the avatar dir used during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string|bool $signup_avatar_dir Avatar dir used during signup or false.
	 */
	return apply_filters( 'bp_get_signup_avatar_dir_value', $signup_avatar_dir );
}

/**
 * Determines whether privacy policy acceptance is required for registration.
 *
 * @since 4.0.0
 *
 * @return bool
 */
function bp_signup_requires_privacy_policy_acceptance() {

	// Default to true when a published Privacy Policy page exists.
	$privacy_policy_url = get_privacy_policy_url();
	$required           = ! empty( $privacy_policy_url );

	/**
	 * Filters whether privacy policy acceptance is required for registration.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $required Whether privacy policy acceptance is required.
	 */
	return (bool) apply_filters( 'bp_signup_requires_privacy_policy_acceptance', $required );
}

/**
 * Output the current signup step.
 *
 * @since 1.1.0
 */
function bp_current_signup_step() {
	echo esc_html( bp_get_current_signup_step() );
}

/**
 * Get the current signup step.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bp_get_current_signup_step() {
	return (string) buddypress()->signup->step;
}

/**
 * Output the user avatar during signup.
 *
 * @since 1.1.0
 *
 * @see bp_get_signup_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_signup_avatar(}.
 */
function bp_signup_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_avatar( $args );
}

/**
 * Get the user avatar during signup.
 *
 * @since 1.1.0
 *
 * @see bp_core_fetch_avatar() for description of arguments.
 *
 * @param array|string $args {
 *     Array of optional arguments.
 *     @type int    $size  Height/weight in pixels. Default: value of
 *                         bp_core_avatar_full_width().
 *     @type string $class CSS class. Default: 'avatar'.
 *     @type string $alt   HTML 'alt' attribute. Default: 'Your Avatar'.
 * }
 * @return string
 */
function bp_get_signup_avatar( $args = '' ) {
	$bp = buddypress();
	$r  = bp_parse_args(
		$args,
		array(
			'size'  => bp_core_avatar_full_width(),
			'class' => 'avatar',
			'alt'   => __( 'Your Profile Photo', 'buddypress' ),
		)
	);

	$signup_avatar_dir = bp_get_signup_avatar_dir_value();

	// Avatar DIR is found.
	if ( $signup_avatar_dir ) {
		$gravatar_img = bp_core_fetch_avatar( array(
			'item_id'    => $signup_avatar_dir,
			'object'     => 'signup',
			'avatar_dir' => 'avatars/signups',
			'type'       => 'full',
			'width'      => $r['size'],
			'height'     => $r['size'],
			'alt'        => $r['alt'],
			'class'      => $r['class'],
		) );

		// No avatar DIR was found.
	} else {

		// Set default gravatar type.
		if ( empty( $bp->grav_default->user ) ) {
			$default_grav = 'wavatar';
		} elseif ( 'mystery' === $bp->grav_default->user ) {
			$default_grav = $bp->plugin_url . 'bp-core/images/mystery-man.jpg';
		} else {
			$default_grav = $bp->grav_default->user;
		}

		/**
		 * Filters the base Gravatar url used for signup avatars when no avatar dir found.
		 *
		 * @since 1.0.2
		 *
		 * @param string $value Gravatar url to use.
		 */
		$gravatar_url    = apply_filters( 'bp_gravatar_url', '//www.gravatar.com/avatar/' );
		$md5_lcase_email = md5( strtolower( bp_get_signup_email_value() ) );
		$gravatar_img    = '<img src="' . $gravatar_url . $md5_lcase_email . '?d=' . $default_grav . '&amp;s=' . $r['size'] . '" width="' . esc_attr( $r['size'] ) . '" height="' . esc_attr( $r['size'] ) . '" alt="' . esc_attr( $r['alt'] ) . '" class="' . esc_attr( $r['class'] ) . '" />';
	}

	/**
	 * Filters the user avatar during signup.
	 *
	 * @since 1.1.0
	 *
	 * @param string $gravatar_img Avatar HTML image tag.
	 * @param array  $args         Array of parsed args for avatar query.
	 */
	return apply_filters( 'bp_get_signup_avatar', $gravatar_img, $args );
}

/** Actions & Filters *********************************************************/

add_action( 'login_form_bp-resend-activation', 'bp_members_login_resend_activation_email' );
add_action( 'bp_init', 'bp_core_wpsignup_redirect' );
add_action( 'bp_loaded', 'bp_members_signup_sanitization' );

add_filter( 'register_url', 'bp_get_signup_page' );
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 30, 3 );
add_filter( 'subdirectory_reserved_names', 'bp_members_signup_with_subdirectory_blog', 10, 1 );
add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );
add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );
add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 6 );
add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
add_filter( 'signup_user_meta', 'bp_core_add_meta_to_multisite_signups' );
add_filter( 'signup_site_meta', 'bp_core_add_meta_to_multisite_signups' );
