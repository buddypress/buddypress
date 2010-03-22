<?php

/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_core_screen_signup() {
	global $bp, $wpdb;

	if ( $bp->current_component != BP_REGISTER_SLUG )
		return false;

	/* If the user is logged in, redirect away from here */
	if ( is_user_logged_in() )
		bp_core_redirect( $bp->root_domain );

	/* If signups are disabled, just re-direct */
	if ( !bp_get_signup_allowed() )
		bp_core_redirect( $bp->root_domain );

	$bp->signup->step = 'request-details';

	/* If the signup page is submitted, validate and save */
	if ( isset( $_POST['signup_submit'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_new_signup' );

		require_once( ABSPATH . WPINC . '/registration.php' );

		/* Check the base account details for problems */
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );

		/* If there are errors with account details, set them for display */
		if ( !empty( $account_details['errors']->errors['user_name'] ) )
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];

		if ( !empty( $account_details['errors']->errors['user_email'] ) )
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];

		/* Check that both password fields are filled in */
		if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) )
			$bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'buddypress' );

		/* Check that the passwords match */
		if ( ( !empty( $_POST['signup_password'] ) && !empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] )
			$bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'buddypress' );

		$bp->signup->username = $_POST['signup_username'];
		$bp->signup->email = $_POST['signup_email'];

		if ( !empty( $_POST['signup_profile_field_ids'] ) && function_exists( 'xprofile_check_is_required_field' ) ) {
			/* Now we've checked account details, we can check profile information */
			$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

			/* Loop through the posted fields formatting any datebox values then validate the field */
			foreach ( (array) $profile_field_ids as $field_id ) {
				if ( !isset( $_POST['field_' . $field_id] ) ) {
					if ( isset( $_POST['field_' . $field_id . '_day'] ) )
						$_POST['field_' . $field_id] = strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] );
				}

				if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) )
					$bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
			}
		}

		/* Finally, let's check the blog details, if the user wants a blog and blog creation is enabled */
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = $bp->site_options['registration'];

			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

				/* If there are errors with blog details, set them for display */
				if ( !empty( $blog_details['errors']->errors['blogname'] ) )
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

				if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
			}
		}

		do_action( 'bp_signup_validate' );

		/* Add any errors to the action for the field in the template for display. */
		if ( !empty( $bp->signup->errors ) ) {
			foreach ( (array)$bp->signup->errors as $fieldname => $error_message )
				add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo "<div class=\"error\">' . $error_message . '</div>";' ) );
		} else {
			$bp->signup->step = 'save-details';

			/* No errors! Let's register those deets. */
			$active_signup = $bp->site_options['registration'];

			if ( 'none' != $active_signup ) {

				/* Let's compact any profile field info into usermeta */
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				/* Loop through the posted fields formatting any datebox values then add to usermeta */
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( !isset( $_POST['field_' . $field_id] ) ) {
						if ( isset( $_POST['field_' . $field_id . '_day'] ) )
							$_POST['field_' . $field_id] = strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] );
					}

					if ( !empty( $_POST['field_' . $field_id] ) )
						$usermeta['field_' . $field_id] = $_POST['field_' . $field_id];
				}

				/* Store the profile field ID's in usermeta */
				$usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];

				/* Hash and store the password */
				$usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

				/* If the user decided to create a blog, save those details to usermeta */
				if ( 'blog' == $active_signup || 'all' == $active_signup ) {
					$usermeta['public'] = ( 'public' == $_POST['signup_blog_privacy'] ) ? true : false;
				}

				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

				/* Finally, sign up the user and/or blog */
				if ( isset( $_POST['signup_with_blog'] ) && bp_core_is_multisite() )
					bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				else {
					bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );
				}

				$bp->signup->step = 'completed-confirmation';
			}

			do_action( 'bp_complete_signup' );
		}

	}

	$bp->avatar_admin->step = 'upload-image';

	/* If user has uploaded a new avatar */
	if ( !empty( $_FILES ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_avatar_upload' );

		$bp->signup->step = 'completed-confirmation';

		if ( bp_core_is_multisite() ) {
			/* Get the activation key */
			if ( !$bp->signup->key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM {$wpdb->signups} WHERE user_login = %s AND user_email = %s", $_POST[ 'signup_username' ], $_POST[ 'signup_email' ] ) ) ) {
				bp_core_add_message( __( 'There was a problem uploading your avatar, please try uploading it again', 'buddypress' ) );
			} else {
				/* Hash the key to create the upload folder (added security so people don't sniff the activation key) */
				$bp->signup->avatar_dir = wp_hash( $bp->signup->key );
			}
		} else {
			$user_id = bp_core_get_userid( $_POST['signup_username'] );
			$bp->signup->avatar_dir = wp_hash( $user_id );
		}

		/* Pass the file to the avatar upload handler */
		if ( bp_core_avatar_handle_upload( $_FILES, 'bp_core_signup_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			/* Make sure we include the jQuery jCrop file for image cropping */
			add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
		}
	}

	/* If the image cropping is done, crop the image and save a full/thumb version */
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_avatar_cropstore' );

		/* Reset the avatar step so we can show the upload form again if needed */
		$bp->signup->step = 'completed-confirmation';
		$bp->avatar_admin->step = 'upload-image';

		if ( !bp_core_avatar_handle_crop( array( 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
			bp_core_add_message( __( 'There was a problem cropping your avatar, please try uploading it again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Your new avatar was uploaded successfully', 'buddypress' ) );
	}
	bp_core_load_template( 'registration/register' );
}
add_action( 'wp', 'bp_core_screen_signup', 3 );

function bp_core_screen_activation() {
	global $bp, $wpdb;

	if ( BP_ACTIVATION_SLUG != $bp->current_component )
		return false;

	/* Check if an activation key has been passed */
	if ( isset( $_GET['key'] ) ) {

		require_once( ABSPATH . WPINC . '/registration.php' );

		/* Activate the signup */
		$user = apply_filters( 'bp_core_activate_account', bp_core_activate_signup( $_GET['key'] ) );

		/* If there was errors, add a message and redirect */
		if ( $user->errors ) {
			bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
			bp_core_redirect( $bp->root_domain . '/' . BP_ACTIVATION_SLUG );
		}

		/* Check for an uploaded avatar and move that to the correct user folder */
		if ( bp_core_is_multisite() )
			$hashed_key = wp_hash( $_GET['key'] );
		else
			$hashed_key = wp_hash( $user );

		/* Check if the avatar folder exists. If it does, move rename it, move it and delete the signup avatar dir */
		if ( file_exists( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key ) )
			@rename( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key, BP_AVATAR_UPLOAD_PATH . '/avatars/' . $user );

		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );

		$bp->activation_complete = true;
	}

	if ( '' != locate_template( array( 'registration/activate' ), false ) )
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'activate' ) );
	else
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'registration/activate' ) );
}
add_action( 'wp', 'bp_core_screen_activation', 3 );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_core_validate_user_signup( $user_name, $user_email ) {
	global $wpdb, $bp;

	$errors = new WP_Error();
	$user_email = sanitize_email( $user_email );

	if ( empty( $user_name ) )
	   	$errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );

	$maybe = array();
	preg_match( "/[a-z0-9]+/", $user_name, $maybe );

	$db_illegal_names = get_site_option( 'illegal_names' );
	$filtered_illegal_names = apply_filters( 'bp_core_illegal_usernames', array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator', BP_GROUPS_SLUG, $bp->members->slug, BP_FORUMS_SLUG, BP_BLOGS_SLUG, BP_REGISTER_SLUG, BP_ACTIVATION_SLUG ) );

	$illegal_names = array_merge( (array)$db_illegal_names, (array)$filtered_illegal_names );
	update_site_option( 'illegal_names', $illegal_names );

	if ( !validate_username( $user_name ) || in_array( $user_name, (array)$illegal_names ) || $user_name != $maybe[0] )
	    $errors->add( 'user_name', __( 'Only lowercase letters and numbers allowed', 'buddypress' ) );

	if( strlen( $user_name ) < 4 )
	    $errors->add( 'user_name',  __( 'Username must be at least 4 characters', 'buddypress' ) );

	if ( strpos( ' ' . $user_name, '_' ) != false )
		$errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );

	/* Is the user_name all numeric? */
	$match = array();
	preg_match( '/[0-9]*/', $user_name, $match );

	if ( $match[0] == $user_name )
		$errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );

	if ( !is_email( $user_email ) )
		$errors->add( 'user_email', __( 'Please check your email address.', 'buddypress' ) );

	$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

		if ( in_array( $emaildomain, (array)$limited_email_domains ) == false )
			$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddypress' ) );
	}

	/* Check if the username has been used already. */
	if ( username_exists( $user_name ) )
		$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );

	/* Check if the email address has been used already. */
	if ( email_exists( $user_email ) )
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddypress' ) );

	$result = array( 'user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors );

	/* Apply WPMU legacy filter */
	$result = apply_filters( 'wpmu_validate_user_signup', $result );

 	return apply_filters( 'bp_core_validate_user_signup', $result );
}

function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( !bp_core_is_multisite() || !function_exists( 'wpmu_validate_blog_signup' ) )
		return false;

	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	global $bp, $wpdb;

	/* Multisite installs have their own install procedure */
	if ( bp_core_is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

	} else {
		$errors = new WP_Error();

		$user_id = wp_insert_user( array(
			'user_login' => $user_login,
			'user_pass' => $user_password,
			'display_name' => sanitize_title( $user_login ),
			'user_email' => $user_email
		) );

		if ( !$user_id ) {
			$errors->add( 'registerfail', sprintf( __('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'buddypress' ), get_option( 'admin_email' ) ) );
			return $errors;
		}

		/* Update the user status to '2' which we will use as 'not activated' (0 = active, 1 = spam, 2 = not active) */
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 2 WHERE ID = %d", $user_id ) );

		/* Set any profile data */
		if ( function_exists( 'xprofile_set_field_data' ) ) {
			if ( !empty( $usermeta['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $usermeta['profile_field_ids'] );

				foreach( (array)$profile_field_ids as $field_id ) {
					$current_field = $usermeta["field_{$field_id}"];

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $user_id, $current_field );
				}
			}
		}
	}
	$bp->signup->username = $user_login;

	/***
	 * Now generate an activation key and send an email to the user so they can activate their account
	 * and validate their email address. Multisite installs send their own email, so this is only for single blog installs.
	 */
	if ( !bp_core_is_multisite() ) {
		$activation_key = wp_hash( $user_id );
		update_usermeta( $user_id, 'activation_key', $activation_key );
		bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key );
	}

	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( !bp_core_is_multisite() || !function_exists( 'wpmu_signup_blog' ) )
		return false;

	return apply_filters( 'bp_core_signup_blog', wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) );
}

function bp_core_activate_signup( $key ) {
	global $wpdb;

	$user = false;

	/* Multisite installs have their own activation routine */
	if ( bp_core_is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		/* If there was errors, add a message and redirect */
		if ( $user->errors ) {
			bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
			bp_core_redirect( $bp->root_domain . '/' . BP_ACTIVATION_SLUG );
		}

		$user_id = $user['user_id'];

		/* Set any profile data */
		if ( function_exists( 'xprofile_set_field_data' ) ) {
			if ( !empty( $user['meta']['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

				foreach( (array)$profile_field_ids as $field_id ) {
					$current_field = $user['meta']["field_{$field_id}"];

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $user_id, $current_field );
				}
			}
		}

	} else {
		/* Get the user_id based on the $key */
		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_value = %s", $key ) );

		if ( empty( $user_id ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		/* Change the user's status so they become active */
		if ( !$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 0 WHERE ID = %d", $user_id ) ) )
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );

		/* Notify the site admin of a new user registration */
		wp_new_user_notification( $user_id );

		/* Remove the activation key meta */
		delete_usermeta( $user_id, 'activation_key' );
	}

	/* Update the user_url and display_name */
	wp_update_user( array( 'ID' => $user_id, 'user_url' => bp_core_get_user_domain( $user_id, sanitize_title( $user_login ), $user_login ), 'display_name' => bp_core_get_user_displayname( $user_id ) ) );

	/* Add a last active entry */
	update_usermeta( $user_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );

	/* Set the password on multisite installs */
	if ( bp_core_is_multisite() && !empty( $user['meta']['password'] ) )
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

	/* Delete the total member cache */
	wp_cache_delete( 'bp_total_member_count', 'bp' );

	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

function bp_core_new_user_activity( $user ) {
	if ( empty( $user ) || !function_exists( 'bp_activity_add' ) )
		return false;

	if ( is_array( $user ) )
		$user_id = $user['user_id'];
	else
		$user_id = $user;

	if ( empty( $user_id ) )
		return false;

	$userlink = bp_core_get_userlink( $user_id );

	bp_activity_add( array(
		'user_id' => $user_id,
		'action' => apply_filters( 'bp_core_activity_registered_member_action', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $user_id ),
		'component' => 'profile',
		'type' => 'new_member'
	) );
}
add_action( 'bp_core_activated_user', 'bp_core_new_user_activity' );

function bp_core_map_user_registration( $user_id ) {
	/* Only map data when the site admin is adding users, not on registration. */
	if ( !is_admin() )
		return false;

	/* Add a last active entry */
	update_usermeta( $user_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );

	/* Add the user's fullname to Xprofile */
	if ( function_exists( 'xprofile_set_field_data' ) ) {
		$firstname = get_usermeta( $user_id, 'first_name' );
		$lastname = ' ' . get_usermeta( $user_id, 'last_name' );
		$name = $firstname . $lastname;

		if ( empty( $name ) || ' ' == $name )
			$name = get_usermeta( $user_id, 'nickname' );

		xprofile_set_field_data( 1, $user_id, $name );
	}
}
add_action( 'user_register', 'bp_core_map_user_registration' );

function bp_core_signup_avatar_upload_dir() {
	global $bp;

	if ( !$bp->signup->avatar_dir )
		return false;

	$path  = BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $bp->signup->avatar_dir;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl = str_replace( BP_AVATAR_UPLOAD_PATH, BP_AVATAR_URL, $path );
	$newburl = $newurl;
	$newsubdir = '/avatars/signups/' . $bp->signup->avatar_dir;

	return apply_filters( 'bp_core_signup_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

function bp_core_signup_send_validation_email( $user_id, $user_email, $key ) {
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = clean_url( $activate_url );
	$admin_email = get_site_option( "admin_email" );

	if ( empty( $admin_email ) )
		$admin_email = 'noreply@' . $_SERVER['SERVER_NAME'];

	$from_name = ( '' == get_option( 'blogname' ) ) ? 'BuddyPress' : wp_specialchars( get_option( 'blogname' ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%s\n\n", 'buddypress' ), $activate_url );
	$subject = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	/* Send the message */
	$to = apply_filters( 'bp_core_activation_signup_user_notification_to', $user_email );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message );

	wp_mail( $to, $subject, $message, $message_headers );
}

/* Stop user accounts logging in that have not been activated (user_status = 2) */
function bp_core_signup_disable_inactive( $auth_obj, $username ) {
	global $bp, $wpdb;

	if ( !$user_id = bp_core_get_userid( $username ) )
		return $auth_obj;

	$user_status = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_status FROM $wpdb->users WHERE ID = %d", $user_id ) );

	if ( 2 == $user_status )
		bp_core_redirect( $bp->root_domain );
	else
		return $auth_obj;
}
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 11, 2 );

/* Kill the wp-signup.php if custom registration signup templates are present */
function bp_core_wpsignup_redirect() {
	if ( false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php') && $_GET['action'] != 'register' )
		return false;

	if ( locate_template( array( 'registration/register.php' ), false ) || locate_template( array( 'register.php' ), false ) )
		wp_redirect( bp_get_root_domain() . '/' . BP_REGISTER_SLUG . '/' );
}
if ( bp_core_is_multisite() )
	add_action( 'wp', 'bp_core_wpsignup_redirect' );
else
	add_action( 'init', 'bp_core_wpsignup_redirect' );

?>