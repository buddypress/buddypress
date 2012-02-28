<?php
/**
 * BuddyPress Member Sign-up
 *
 * Functions and filters specific to the member sign-up process
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_core_screen_signup() {
	global $bp, $wpdb;

	if ( !bp_is_current_component( 'register' ) )
		return;

	// Not a directory
	bp_update_is_directory( false, 'register' );

	// If the user is logged in, redirect away from here
	if ( is_user_logged_in() ) {
		if ( bp_is_component_front_page( 'register' ) )
			$redirect_to = bp_get_root_domain() . '/' . bp_get_members_root_slug();
		else
			$redirect_to = bp_get_root_domain();

		bp_core_redirect( apply_filters( 'bp_loggedin_register_page_redirect_to', $redirect_to ) );

		return;
	}

	$bp->signup->step = 'request-details';

 	if ( !bp_get_signup_allowed() ) {
		$bp->signup->step = 'registration-disabled';
	}

	// If the signup page is submitted, validate and save
	elseif ( isset( $_POST['signup_submit'] ) ) {

		// Check the nonce
		check_admin_referer( 'bp_new_signup' );

		// Check the base account details for problems
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );

		// If there are errors with account details, set them for display
		if ( !empty( $account_details['errors']->errors['user_name'] ) )
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];

		if ( !empty( $account_details['errors']->errors['user_email'] ) )
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];

		// Check that both password fields are filled in
		if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) )
			$bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'buddypress' );

		// Check that the passwords match
		if ( ( !empty( $_POST['signup_password'] ) && !empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] )
			$bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'buddypress' );

		$bp->signup->username = $_POST['signup_username'];
		$bp->signup->email = $_POST['signup_email'];

		// Now we've checked account details, we can check profile information
		if ( bp_is_active( 'xprofile' ) ) {

			// Make sure hidden field is passed and populated
			if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

				// Let's compact any profile field info into an array
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				// Loop through the posted fields formatting any datebox values then validate the field
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( !isset( $_POST['field_' . $field_id] ) ) {
						if ( !empty( $_POST['field_' . $field_id . '_day'] ) && !empty( $_POST['field_' . $field_id . '_month'] ) && !empty( $_POST['field_' . $field_id . '_year'] ) )
							$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
					}

					// Create errors for required fields without values
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) )
						$bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
				}

			// This situation doesn't naturally occur so bounce to website root
			} else {
				bp_core_redirect( bp_get_root_domain() );
			}
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = $bp->site_options['registration'];

			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

				// If there are errors with blog details, set them for display
				if ( !empty( $blog_details['errors']->errors['blogname'] ) )
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

				if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
			}
		}

		do_action( 'bp_signup_validate' );

		// Add any errors to the action for the field in the template for display.
		if ( !empty( $bp->signup->errors ) ) {
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				// addslashes() and stripslashes() to avoid create_function()
				// syntax errors when the $error_message contains quotes
				add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo apply_filters(\'bp_members_signup_error_message\', "<div class=\"error\">" . stripslashes( \'' . addslashes( $error_message ) . '\' ) . "</div>" );' ) );
			}
		} else {
			$bp->signup->step = 'save-details';

			// No errors! Let's register those deets.
			$active_signup = !empty( $bp->site_options['registration'] ) ? $bp->site_options['registration'] : '';

			if ( 'none' != $active_signup ) {

				// Let's compact any profile field info into usermeta
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				// Loop through the posted fields formatting any datebox values then add to usermeta
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( !isset( $_POST['field_' . $field_id] ) ) {
						if ( isset( $_POST['field_' . $field_id . '_day'] ) )
							$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
					}

					if ( !empty( $_POST['field_' . $field_id] ) )
						$usermeta['field_' . $field_id] = $_POST['field_' . $field_id];
				}

				// Store the profile field ID's in usermeta
				$usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];

				// Hash and store the password
				$usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

				// If the user decided to create a blog, save those details to usermeta
				if ( 'blog' == $active_signup || 'all' == $active_signup )
					$usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;

				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

				// Finally, sign up the user and/or blog
				if ( isset( $_POST['signup_with_blog'] ) && is_multisite() )
					$wp_user_id = bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				else
					$wp_user_id = bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );

				if ( is_wp_error( $wp_user_id ) ) {                                   
					$bp->signup->step = 'request-details';
					bp_core_add_message( strip_tags( $wp_user_id->get_error_message() ), 'error' );
				} else {
					$bp->signup->step = 'completed-confirmation';
				} 
			}

			do_action( 'bp_complete_signup' );
		}

	}

	do_action( 'bp_core_screen_signup' );
	bp_core_load_template( apply_filters( 'bp_core_template_register', 'registration/register' ) );
}
add_action( 'bp_screens', 'bp_core_screen_signup' );

function bp_core_screen_activation() {
	global $bp, $wpdb;

	if ( !bp_is_current_component( 'activate' ) )
		return false;

	// Check if an activation key has been passed
	if ( isset( $_GET['key'] ) ) {

		// Activate the signup
		$user = apply_filters( 'bp_core_activate_account', bp_core_activate_signup( $_GET['key'] ) );

		// If there were errors, add a message and redirect
		if ( !empty( $user->errors ) ) {
			bp_core_add_message( $user->get_error_message(), 'error' );
			bp_core_redirect( trailingslashit( bp_get_root_domain() . '/' . $bp->pages->activate->slug ) );
		}

		// Check for an uploaded avatar and move that to the correct user folder
		if ( is_multisite() )
			$hashed_key = wp_hash( $_GET['key'] );
		else
			$hashed_key = wp_hash( $user );

		// Check if the avatar folder exists. If it does, move rename it, move
		// it and delete the signup avatar dir
		if ( file_exists( bp_core_avatar_upload_path() . '/avatars/signups/' . $hashed_key ) )
			@rename( bp_core_avatar_upload_path() . '/avatars/signups/' . $hashed_key, bp_core_avatar_upload_path() . '/avatars/' . $user );

		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );

		$bp->activation_complete = true;
	}

	if ( '' != locate_template( array( 'registration/activate' ), false ) )
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'activate' ) );
	else
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'registration/activate' ) );
}
add_action( 'bp_screens', 'bp_core_screen_activation' );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/**
 * Flush illegal names by getting and setting 'illegal_names' site option
 */
function bp_core_flush_illegal_names() {
	$illegal_names = get_site_option( 'illegal_names' );
	update_site_option( 'illegal_names', $illegal_names );
}

/**
 * Filter the illegal_names site option and make sure it includes a few
 * specific BuddyPress and Multi-site slugs
 *
 * @param array|string $value Illegal names from field
 * @param array|string $oldvalue The value as it is currently
 * @return array Merged and unique array of illegal names
 */
function bp_core_get_illegal_names( $value = '', $oldvalue = '' ) {

	// Make sure $value is array
	if ( empty( $value ) )
		$db_illegal_names = array();
	if ( is_array( $value ) )
		$db_illegal_names = $value;
	elseif ( is_string( $value ) )
		$db_illegal_names = explode( ' ', $value );

	// Add the core components' slugs to the banned list even if their components aren't active.
	$bp_component_slugs = array(
		'groups',
		'members',
		'forums',
		'blogs',
		'activity',
		'profile',
		'friends',
		'search',
		'settings',
		'register',
		'activate'
	);

	// Core constants
	$slug_constants = array(
		'BP_GROUPS_SLUG',
		'BP_MEMBERS_SLUG',
		'BP_FORUMS_SLUG',
		'BP_BLOGS_SLUG',
		'BP_ACTIVITY_SLUG',
		'BP_XPROFILE_SLUG',
		'BP_FRIENDS_SLUG',
		'BP_SEARCH_SLUG',
		'BP_SETTINGS_SLUG',
		'BP_REGISTER_SLUG',
		'BP_ACTIVATION_SLUG',
	);
	foreach( $slug_constants as $constant )
		if ( defined( $constant ) )
			$bp_component_slugs[] = constant( $constant );

	// Add our slugs to the array and allow them to be filtered
	$filtered_illegal_names = apply_filters( 'bp_core_illegal_usernames', array_merge( array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' ), $bp_component_slugs ) );

	// Merge the arrays together
	$merged_names           = array_merge( (array)$filtered_illegal_names, (array)$db_illegal_names );

	// Remove duplicates
	$illegal_names          = array_unique( (array)$merged_names );

	return apply_filters( 'bp_core_illegal_names', $illegal_names );
}
add_filter( 'pre_update_site_option_illegal_names', 'bp_core_get_illegal_names', 10, 2 );

/**
 * Validate a user name and email address when creating a new user.
 *
 * @global object $wpdb DB Layer
 * @param string $user_name Username to validate
 * @param string $user_email Email address to validate
 * @return array Results of user validation including errors, if any
 */
function bp_core_validate_user_signup( $user_name, $user_email ) {
	global $wpdb;

	$errors = new WP_Error();
	$user_email = sanitize_email( $user_email );

	// Apply any user_login filters added by BP or other plugins before validating 
 	$user_name = apply_filters( 'pre_user_login', $user_name ); 

	if ( empty( $user_name ) )
		$errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );

	$maybe = array();
	preg_match( "/[a-z0-9]+/", $user_name, $maybe );

	// Make sure illegal names include BuddyPress slugs and values
	bp_core_flush_illegal_names();

	$illegal_names = get_site_option( 'illegal_names' );

	if ( !validate_username( $user_name ) || in_array( $user_name, (array)$illegal_names ) || ( !empty( $maybe[0] ) && $user_name != $maybe[0] ) )
		$errors->add( 'user_name', __( 'Only lowercase letters and numbers allowed', 'buddypress' ) );

	if( strlen( $user_name ) < 4 )
		$errors->add( 'user_name',  __( 'Username must be at least 4 characters', 'buddypress' ) );

	if ( strpos( ' ' . $user_name, '_' ) != false )
		$errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );

	// Is the user_name all numeric?
	$match = array();
	preg_match( '/[0-9]*/', $user_name, $match );

	if ( $match[0] == $user_name )
		$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );

	if ( !is_email( $user_email ) )
		$errors->add( 'user_email', __( 'Please check your email address.', 'buddypress' ) );

	if ( function_exists( 'is_email_address_unsafe' ) && is_email_address_unsafe( $user_email ) )
		$errors->add( 'user_email',  __( 'Sorry, that email address is not allowed!', 'buddypress' ) );

	$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

		if ( in_array( $emaildomain, (array)$limited_email_domains ) == false )
			$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	// Check if the username has been used already.
	if ( username_exists( $user_name ) )
		$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );

	// Check if the email address has been used already.
	if ( email_exists( $user_email ) )
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddypress' ) );

	$result = array( 'user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors );

	// Apply WPMU legacy filter
	$result = apply_filters( 'wpmu_validate_user_signup', $result );

 	return apply_filters( 'bp_core_validate_user_signup', $result );
}

function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( !is_multisite() || !function_exists( 'wpmu_validate_blog_signup' ) )
		return false;

	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	global $bp, $wpdb;

	// Multisite installs have their own install procedure
	if ( is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

		// On multisite, the user id is not created until the user activates the account
		// but we need to cast $user_id to pass to the filters
		$user_id = false;

	} else {
		$errors = new WP_Error();

		$user_id = wp_insert_user( array(
			'user_login' => $user_login,
			'user_pass' => $user_password,
			'display_name' => sanitize_title( $user_login ),
			'user_email' => $user_email
		) );

		if ( is_wp_error( $user_id ) || empty( $user_id ) ) {
			$errors->add( 'registerfail', sprintf( __('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'buddypress' ), get_option( 'admin_email' ) ) );
			return $errors;
		}

		// Update the user status to '2' which we will use as 'not activated' (0 = active, 1 = spam, 2 = not active)
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 2 WHERE ID = %d", $user_id ) );

		// Set any profile data
		if ( bp_is_active( 'xprofile' ) ) {
			if ( !empty( $usermeta['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $usermeta['profile_field_ids'] );

				foreach( (array)$profile_field_ids as $field_id ) {
					if ( empty( $usermeta["field_{$field_id}"] ) )
						continue;

					$current_field = $usermeta["field_{$field_id}"];
					xprofile_set_field_data( $field_id, $user_id, $current_field );
				}
			}
		}
	}
	$bp->signup->username = $user_login;

	/***
	 * Now generate an activation key and send an email to the user so they can activate their account
	 * and validate their email address. Multisite installs send their own email, so this is only for single blog installs.
	 *
	 * To disable sending activation emails you can user the filter 'bp_core_signup_send_activation_key' and return false.
	 */
	if ( apply_filters( 'bp_core_signup_send_activation_key', true ) ) {
		if ( !is_multisite() ) {
			$activation_key = wp_hash( $user_id );
			update_user_meta( $user_id, 'activation_key', $activation_key );
			bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key );
		}
	}

	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( !is_multisite() || !function_exists( 'wpmu_signup_blog' ) )
		return false;

	return apply_filters( 'bp_core_signup_blog', wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) );
}

function bp_core_activate_signup( $key ) {
	global $bp, $wpdb;

	$user = false;

	// Multisite installs have their own activation routine
	if ( is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		// If there were errors, add a message and redirect
		if ( !empty( $user->errors ) ) {
			return $user;
		}

		$user_id = $user['user_id'];

		// Set any profile data
		if ( bp_is_active( 'xprofile' ) ) {
			if ( !empty( $user['meta']['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

				foreach( (array)$profile_field_ids as $field_id ) {
					$current_field = isset( $user['meta']["field_{$field_id}"] ) ? $user['meta']["field_{$field_id}"] : false;

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $user_id, $current_field );
				}
			}
		}

	} else {
		// Get the user_id based on the $key
		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'activation_key' AND meta_value = %s", $key ) );

		if ( empty( $user_id ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		// Change the user's status so they become active
		if ( !$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 0 WHERE ID = %d", $user_id ) ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		// Notify the site admin of a new user registration
		wp_new_user_notification( $user_id );

		// Remove the activation key meta
		delete_user_meta( $user_id, 'activation_key' );
	}

	// Update the display_name
	wp_update_user( array( 'ID' => $user_id, 'display_name' => bp_core_get_user_displayname( $user_id ) ) );

	// Set the password on multisite installs
	if ( is_multisite() && !empty( $user['meta']['password'] ) )
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

	// Delete the total member cache
	wp_cache_delete( 'bp_total_member_count', 'bp' );

	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

function bp_core_new_user_activity( $user ) {
	if ( empty( $user ) || !bp_is_active( 'activity' ) )
		return false;

	if ( is_array( $user ) )
		$user_id = $user['user_id'];
	else
		$user_id = $user;

	if ( empty( $user_id ) )
		return false;

	$userlink = bp_core_get_userlink( $user_id );

	bp_activity_add( array(
		'user_id'   => $user_id,
		'action'    => apply_filters( 'bp_core_activity_registered_member_action', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $user_id ),
		'component' => 'xprofile',
		'type'      => 'new_member'
	) );
}
add_action( 'bp_core_activated_user', 'bp_core_new_user_activity' );

function bp_core_map_user_registration( $user_id ) {
	// Only map data when the site admin is adding users, not on registration.
	if ( !is_admin() )
		return false;

	// Add the user's fullname to Xprofile
	if ( bp_is_active( 'xprofile' ) ) {
		$firstname = get_user_meta( $user_id, 'first_name', true );
		$lastname = ' ' . get_user_meta( $user_id, 'last_name', true );
		$name = $firstname . $lastname;

		if ( empty( $name ) || ' ' == $name )
			$name = get_user_meta( $user_id, 'nickname', true );

		xprofile_set_field_data( 1, $user_id, $name );
	}
}
add_action( 'user_register', 'bp_core_map_user_registration' );

function bp_core_signup_avatar_upload_dir() {
	global $bp;

	if ( !$bp->signup->avatar_dir )
		return false;

	$path  = bp_core_avatar_upload_path() . '/avatars/signups/' . $bp->signup->avatar_dir;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl = bp_core_avatar_url() . '/avatars/signups/' . $bp->signup->avatar_dir;
	$newburl = $newurl;
	$newsubdir = '/avatars/signups/' . $bp->signup->avatar_dir;

	return apply_filters( 'bp_core_signup_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

function bp_core_signup_send_validation_email( $user_id, $user_email, $key ) {
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = esc_url( $activate_url );

	$from_name = ( '' == get_option( 'blogname' ) ) ? __( 'BuddyPress', 'buddypress' ) : esc_html( get_option( 'blogname' ) );

	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%s\n\n", 'buddypress' ), $activate_url );
	$subject = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	// Send the message
	$to      = apply_filters( 'bp_core_signup_send_validation_email_to',     $user_email, $user_id                );
	$subject = apply_filters( 'bp_core_signup_send_validation_email_subject', $subject,    $user_id                );
	$message = apply_filters( 'bp_core_signup_send_validation_email_message', $message,    $user_id, $activate_url );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_core_sent_user_validation_email', $subject, $message, $user_id, $user_email, $key );
}

// Stop user accounts logging in that have not been activated (user_status = 2)
function bp_core_signup_disable_inactive( $auth_obj, $username ) {
	global $bp, $wpdb;

	if ( !$user_id = bp_core_get_userid( $username ) )
		return $auth_obj;

	$user_status = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_status FROM $wpdb->users WHERE ID = %d", $user_id ) );

	if ( 2 == $user_status )
		return new WP_Error( 'bp_account_not_activated', __( '<strong>ERROR</strong>: Your account has not been activated. Check your email for the activation link.', 'buddypress' ) );
	else
		return $auth_obj;
}
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 30, 2 );

// Kill the wp-signup.php if custom registration signup templates are present
function bp_core_wpsignup_redirect() {
	$action = !empty( $_GET['action'] ) ? $_GET['action'] : '';

	if ( is_admin() || is_network_admin() )
		return;
	
	// Not at the WP core signup page and action is not register
	if ( false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php' ) && ( 'register' != $action ) )
		return;

	// Redirect to sign-up page
	if ( locate_template( array( 'registration/register.php' ), false ) || locate_template( array( 'register.php' ), false ) )
		bp_core_redirect( bp_get_signup_page() );
}
add_action( 'bp_init', 'bp_core_wpsignup_redirect' );
?>