<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_blogs_redirect_to_random_blog() {
	global $bp, $wpdb;

	if ( bp_is_blogs_component() && isset( $_GET['random-blog'] ) ) {
		$blog = bp_blogs_get_random_blogs( 1, 1 );

		bp_core_redirect( get_site_url( $blog['blogs'][0]->blog_id ) );
	}
}
add_action( 'bp_actions', 'bp_blogs_redirect_to_random_blog' );

?>