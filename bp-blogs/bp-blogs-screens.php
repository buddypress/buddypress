<?php

/******************************************************************************
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_blogs_screen_my_blogs() {
	global $bp;

	if ( !is_multisite() )
		return false;

	do_action( 'bp_blogs_screen_my_blogs' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}

function bp_blogs_screen_create_a_blog() {
	global $bp;

	if ( !is_multisite() || $bp->current_component != $bp->blogs->slug || 'create' != $bp->current_action )
		return false;

	if ( !is_user_logged_in() || !bp_blog_signup_enabled() )
		return false;

	do_action( 'bp_blogs_screen_create_a_blog' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'wp', 'bp_blogs_screen_create_a_blog', 3 );

?>
