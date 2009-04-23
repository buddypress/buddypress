<?php
function bp_blogs_ajax_directory_blogs() {
	global $bp;

	check_ajax_referer('directory_blogs');
	
	load_template( TEMPLATEPATH . '/directories/blogs/blogs-loop.php' );
}
add_action( 'wp_ajax_directory_blogs', 'bp_blogs_ajax_directory_blogs' );


?>