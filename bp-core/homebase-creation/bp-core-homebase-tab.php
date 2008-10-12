<?php

if ( $bp['loggedin_homebase_id'] && !wp_verify_nonce($_POST['nonce'], 'slick_avatars') )
	wp_die('Home Base already created.');
	
require_once( ABSPATH . WPINC . '/registration.php' );

if( is_array( get_site_option( 'illegal_names' )) && $_GET[ 'new' ] != '' && in_array( $_GET[ 'new' ], get_site_option( 'illegal_names' ) ) == true ) {
	wp_redirect( "admin.php?page=bp-core/homebase-creation/bp-core-homebase-tab.php" );
	die();
}

$active_signup = get_site_option( 'registration' );
if( !$active_signup )
	$active_signup = 'all';

$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

$newblogname = isset($_GET['new']) ? strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'])) : null;

$current_user = wp_get_current_user();
if( $active_signup == "none" ) {
	_e( "Registration has been disabled." , 'buddypress');
} else {
	if( $active_signup == 'blog' && !is_user_logged_in() )
		wp_die( 'You must be logged in to register a blog.' );

	switch ($_POST['stage']) {
		case 'gimmeanotherblog':
			if ( function_exists('xprofile_install') ) {
				do_action( "preprocess_signup_form" );
			} else {
				bp_core_validate_homebase_form_secondary();
			}
		break;
		default :
			if ( isset( $_GET['cropped'] ) ) {
				// Finalize the crop and store the avatar.
				global $blog_id;
				bp_core_confirm_homebase_signup( $blog_id );
			} else {		
				$user_email = $_POST[ 'user_email' ];
				do_action( "preprocess_signup_form" );
				if ( is_user_logged_in() && ( $active_signup == 'all' || $active_signup == 'blog' ) ) {
					bp_core_homebase_signup_form($newblogname);
				} elseif( is_user_logged_in() == false && ( $active_signup == 'blog' ) ) {
					_e( "I'm sorry. We're not accepting new registrations at this time." , 'buddypress');
				} else {
					_e( "You're logged in already. No need to register again!" , 'buddypress');
				}
			}
		break;
	}
}
?>