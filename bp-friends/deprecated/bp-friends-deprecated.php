<?php
/***
 * Deprecated Friends Functionality
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
function friends_deprecated_globals() {
	global $bp;
	
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	$bp->groups->image_base = BP_PLUGIN_URL . '/bp-friends/deprecated/images';
}
add_action( 'plugins_loaded', 'friends_deprecated_globals', 5 );	
add_action( 'admin_menu', 'friends_deprecated_globals', 2 );

function friends_add_js() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
		
	if ( $bp->current_component == $bp->friends->slug )
		wp_enqueue_script( 'bp-friends-js', BP_PLUGIN_URL . '/bp-friends/deprecated/js/general.js' );
}
add_action( 'template_redirect', 'friends_add_js', 1 );

function friends_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
		
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-friends-structure', BP_PLUGIN_URL . '/bp-friends/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'friends_add_structure_css' );

function friends_ajax_friends_search() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer( 'friends_search' );
	
	load_template( get_template_directory() . '/friends/friends-loop.php' );
}
add_action( 'wp_ajax_friends_search', 'friends_ajax_friends_search' );

function friends_ajax_addremove_friend() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( 'is_friend' == BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
		
		check_ajax_referer('friends_remove_friend');
		
		if ( !friends_remove_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
			echo __("Friendship could not be canceled.", 'buddypress');
		} else {
			echo '<a id="friend-' . $_POST['fid'] . '" class="add" rel="add" title="' . __( 'Add Friend', 'buddypress' ) . '" href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/add-friend/' . $_POST['fid'], 'friends_add_friend' ) . '">' . __( 'Add Friend', 'buddypress' ) . '</a>';
		}
	} else if ( 'not_friends' == BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
		
		check_ajax_referer('friends_add_friend');
		
		if ( !friends_add_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
			echo __("Friendship could not be requested.", 'buddypress');
		} else {
			echo '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '" class="requested">' . __( 'Friendship Requested', 'buddypress' ) . '</a>';
		}
	} else {
		echo __( 'Request Pending', 'buddypress' );
	}
	
	return false;
}
add_action( 'wp_ajax_addremove_friend', 'friends_ajax_addremove_friend' );

?>