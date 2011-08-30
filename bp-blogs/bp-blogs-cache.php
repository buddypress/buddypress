<?php
/*******************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_blogs_clear_blog_object_cache( $blog_id, $user_id ) {
	wp_cache_delete( 'bp_blogs_of_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_blogs_for_user_' . $user_id, 'bp' );
}

function bp_blogs_format_clear_blog_cache( $recorded_blog_obj ) {
	bp_blogs_clear_blog_object_cache( false, $recorded_blog_obj->user_id );
	wp_cache_delete( 'bp_total_blogs', 'bp' );
}

// List actions to clear object caches on
add_action( 'bp_blogs_remove_blog_for_user', 'bp_blogs_clear_blog_object_cache', 10, 2 );
add_action( 'bp_blogs_new_blog',             'bp_blogs_format_clear_blog_cache', 10, 2 );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_blogs_remove_data_for_blog', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_comment',       'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_post',          'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_blog_for_user', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_blog',          'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog_comment',     'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog_post',        'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog',             'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_data',          'bp_core_clear_cache' );

?>