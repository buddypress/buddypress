<?php

/**
 * xprofile_record_wire_post_notification()
 *
 * Records a notification for a new profile wire post to the database and sends out a notification
 * email if the user has this setting enabled.
 * 
 * @package BuddyPress XProfile
 * @param $wire_post_id The ID of the wire post
 * @param $user_id The id of the user that the wire post was sent to
 * @param $poster_id The id of the user who wrote the wire post
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
 * @uses get_usermeta() Get a user meta value based on meta key from wp_usermeta
 * @uses BP_Wire_Post Class Creates a new wire post object based on ID.
 * @uses site_url Returns the site URL
 * @uses wp_mail Sends an email
 */
function xprofile_record_wire_post_notification( $wire_post_id, $user_id, $poster_id ) {
	global $bp, $current_user;
	
	if ( $bp->current_component == $bp->wire->slug && !bp_is_home() ) {
		bp_core_add_notification( $poster_id, $user_id, 'xprofile', 'new_wire_post' );

		if ( !get_usermeta( $bp->loggedin_user->id, 'notification_profile_wire_post' ) || 'yes' == get_usermeta( $bp->loggedin_user->id, 'notification_profile_wire_post' ) ) {
			$poster_name = bp_fetch_user_fullname( $poster_id, false );
			$wire_post = new BP_Wire_Post( $bp->profile->table_name_wire, $wire_post_id, true );
			$ud = get_userdata($user_id);
			
			$wire_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/wire';
			$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';
			
			// Set up and send the message
			$to = $ud->user_email;
			$subject = sprintf( __( '%s posted on your wire.', 'buddypress' ), stripslashes($poster_name) );

$message = sprintf( __( 
'%s posted on your wire:

"%s"

To view your wire: %s

---------------------
', 'buddypress' ), $poster_name, stripslashes($wire_post->content), $wire_link );

			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
			
			// Send it
			wp_mail( $to, $subject, $message );
		}
	}
		
}
add_action( 'bp_wire_post_posted', 'xprofile_record_wire_post_notification', 10, 3 );

?>