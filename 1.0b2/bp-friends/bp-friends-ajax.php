<?php

function friends_ajax_friends_search() {
	global $bp;

	check_ajax_referer('friend_search');
	
	load_template( get_template_directory() . '/friends/friends-loop.php' );
}
add_action( 'wp_ajax_friends_search', 'friends_ajax_friends_search' );

function friends_ajax_addremove_friend() {
	global $bp;

	if ( BP_Friends_Friendship::check_is_friend( $bp['loggedin_userid'], $_POST['fid'] ) == 'is_friend' ) {
		if ( !friends_remove_friend( $bp['loggedin_userid'], $_POST['fid'] ) ) {
			echo __("Friendship could not be canceled.", 'buddypress');
		} else {
			echo '<a id="friend-' . $_POST['fid'] . '" class="add" rel="add" title="' . __( 'Add Friend', 'buddypress' ) . '" href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '/add-friend/' . $_POST['fid'] . '">' . __( 'Add Friend', 'buddypress' ) . '</a>';
		}
	} else if ( BP_Friends_Friendship::check_is_friend( $bp['loggedin_userid'], $_POST['fid'] ) == 'not_friends' ) {
		if ( !friends_add_friend( $bp['loggedin_userid'], $_POST['fid'] ) ) {
			echo __("Friendship could not be requested.", 'buddypress');
		} else {
			echo '<a href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '" class="requested">' . __( 'Friendship Requested', 'buddypres' ) . '</a>';
			//echo '<a id="friend-' . $_POST['fid'] . '" class="remove" rel="remove" title="' . __( 'Remove Friend', 'buddypress' ) . '" href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '/remove-friend/' . $_POST['fid'] . '">' . __( 'Remove Friend', 'buddypress' ) . '</a>';
		}
	} else {
		echo __('Request Pending', 'buddypress');
	}
	
	return false;
}
add_action( 'wp_ajax_addremove_friend', 'friends_ajax_addremove_friend' );

?>