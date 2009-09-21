<?php
/***
 * Deprecated Blogs Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are 
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run, 
 * and you will miss out on lovely performance and functionality improvements.
 * 
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

function bp_blogs_force_buddypress_theme( $template ) { 
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
		
	if ( $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$member_theme = get_site_option( 'active-member-theme' );

		if ( empty( $member_theme ) )
		        $member_theme = 'bpmember';

		add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

		return $member_theme;
	} else {
		return $template;
	}
}
add_filter( 'template', 'bp_blogs_force_buddypress_theme' );

function bp_blogs_force_buddypress_stylesheet( $stylesheet ) {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $stylesheet;
		
	if ( $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$member_theme = get_site_option( 'active-member-theme' );

		if ( empty( $member_theme ) )
		        $member_theme = 'bpmember';

		add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

		return $member_theme;
	} else {
		return $stylesheet;
	}
}
add_filter( 'stylesheet', 'bp_blogs_force_buddypress_stylesheet' );

function bp_blogs_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-blogs-structure', BP_PLUGIN_URL . '/bp-blogs/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'bp_blogs_add_structure_css' );

function bp_blogs_directory_js() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	wp_enqueue_script( 'bp-blogs-directory-blogs', BP_PLUGIN_URL . '/bp-blogs/deprecated/js/directory-blogs.js', array( 'jquery', 'jquery-livequery-pack' ) );
}
add_action( 'bp_blogs_directory_blogs_setup', 'bp_blogs_directory_js' );

function bp_blogs_ajax_directory_blogs() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer('directory_blogs');
	
	locate_template( array( 'directories/blogs/blogs-loop.php' ), true );
}
add_action( 'wp_ajax_directory_blogs', 'bp_blogs_ajax_directory_blogs' );



?>