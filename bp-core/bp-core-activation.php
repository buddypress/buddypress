<?php

function bp_core_screen_activation() {
	global $bp, $wpdb;
	
	if ( BP_ACTIVATION_SLUG != $bp->current_component )
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
				'content' => apply_filters( 'bp_core_activity_registered_member', sprintf( '%s became a registered member', $userlink ), $signup['user_id'] ),
				'primary_link' => apply_filters( 'bp_core_actiivty_registered_member_primary_link', $userlink ),
				'component_name' => 'profile',
				'component_action' => 'new_member'	
			) );
		}

		do_action( 'bp_core_account_activated', &$signup, $_GET['key'] );
		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );
		
		$bp->activation_complete = true;
	}
	
	bp_core_load_template( 'registration/activate' );
}
add_action( 'wp', 'bp_core_screen_activation', 3 );


/***
 * bp_core_disable_welcome_email()
 *
 * Since the user now chooses their password, sending it over clear-text to an
 * email address is no longer necessary. It's also a terrible idea security wise.
 */
function bp_core_disable_welcome_email() {
	return false;
}
add_filter( 'wpmu_welcome_user_notification', 'bp_core_disable_welcome_email' );

?>