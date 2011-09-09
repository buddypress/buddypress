<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_blogs_screen_my_blogs() {
	if ( !is_multisite() )
		return false;

	do_action( 'bp_blogs_screen_my_blogs' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}

function bp_blogs_screen_create_a_blog() {
	if ( !is_multisite() ||  !bp_is_blogs_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_user_logged_in() || !bp_blog_signup_enabled() )
		return false;

	do_action( 'bp_blogs_screen_create_a_blog' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'bp_screens', 'bp_blogs_screen_create_a_blog', 3 );

function bp_blogs_screen_index() {
	if ( is_multisite() && bp_is_blogs_component() && !bp_current_action() ) {
		bp_update_is_directory( true, 'blogs' );

		do_action( 'bp_blogs_screen_index' );

		bp_core_load_template( apply_filters( 'bp_blogs_screen_index', 'blogs/index' ) );
	}
}
add_action( 'bp_screens', 'bp_blogs_screen_index', 2 );

?>