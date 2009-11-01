<?php

function bp_core_screen_signup() {
	global $bp, $wpdb;
	
	if ( $bp->current_component != BP_REGISTER_SLUG )
		return false;
		
	/* If the user is logged in, redirect away from here */
	if ( is_user_logged_in() )
		bp_core_redirect( $bp->root_domain );
	
	/***
	 * For backwards compatibility with the old pre 1.1 two theme system, skip this screen function
	 * if the user is using the old two theme method.
	 */
	if ( file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	/* If signups are disabled, just re-direct */
	if ( 'none' == bp_get_signup_allowed() || 'blog' == bp_get_signup_allowed() )
		bp_core_redirect( $bp->root_domain );
		
	$bp->signup->step = 'request-details';
	
	/* If the signup page is submitted, validate and save */
	if ( isset( $_POST['signup_submit'] ) ) {
		
		/* Check the nonce */
		check_admin_referer( 'bp_new_signup' );
			
		require_once( ABSPATH . WPINC . '/registration.php' );
		
		/* Check the base account details for problems */
		$account_details = wpmu_validate_user_signup( $_POST['signup_username'] , $_POST['signup_email'] );
		
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

		/* Finally, let's check the blog details, if the user wants a blog and blog creation is enabled */
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = get_site_option( 'registration' );
		
			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = wpmu_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );
				
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
			foreach ( $bp->signup->errors as $fieldname => $error_message )
				add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo "<div class=\"error\">' . $error_message . '</div>";' ) );
		} else {
			$bp->signup->step = 'save-details';
			
			/* No errors! Let's register those deets. */
			$active_signup = get_site_option( 'registration' );
			
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
								
				/* Finally, sign up the user and/or blog*/
				if ( isset( $_POST['signup_with_blog'] ) )
					wpmu_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				else
					wpmu_signup_user( $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				
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
		
		/* Get the activation key */
		if ( !$bp->signup->key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM {$wpdb->signups} WHERE user_login = %s AND user_email = %s", $_POST[ 'signup_username' ], $_POST[ 'signup_email' ] ) ) ) {
			bp_core_add_message( __( 'There was a problem uploading your avatar, please try uploading it again', 'buddypress' ) );
		} else {
			/* Hash the key to create the upload folder (added security so people don't sniff the activation key) */
			$bp->signup->avatar_dir = wp_hash( $bp->signup->key );
			
			/* Pass the file to the avatar upload handler */		
			if ( bp_core_avatar_handle_upload( $_FILES, 'bp_core_signup_avatar_upload_dir' ) ) {		
				$bp->avatar_admin->step = 'crop-image';

				/* Make sure we include the jQuery jCrop file for image cropping */
				add_action( 'wp', 'bp_core_add_jquery_cropper' );
			}			
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

function bp_core_signup_avatar_upload_dir() {
	global $bp;

	if ( !$bp->signup->avatar_dir )
		return false;
	
	$path = get_blog_option( BP_ROOT_BLOG, 'upload_path' );
	$newdir = WP_CONTENT_DIR . str_replace( 'wp-content', '', $path );
	$newdir .= '/avatars/signups/' . $bp->signup->avatar_dir;

	$newbdir = $newdir;
	
	if ( !file_exists( $newdir ) )
		@wp_mkdir_p( $newdir );

	$newurl = WP_CONTENT_URL . '/blogs.dir/' . BP_ROOT_BLOG . '/files/avatars/signups/' . $bp->signup->avatar_dir;
	$newburl = $newurl;
	$newsubdir = '/avatars/signups/' . $bp->signup->avatar_dir;

	return apply_filters( 'bp_core_signup_avatar_upload_dir', array( 'path' => $newdir, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );	
}

/* Kill the wp-signup.php if custom registration signup templates are present */
function bp_core_wpsignup_redirect() {
	if ( false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php') )
		return false;

	if ( locate_template( array( 'registration/register.php' ), false ) || locate_template( array( 'register.php' ), false ) )
		 wp_redirect( bp_root_domain() . BP_REGISTER_SLUG );
}
add_action( 'signup_header', 'bp_core_wpsignup_redirect' );

?>