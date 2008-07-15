<?php

function friends_ajax_friends_search() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $current_userid, $creds;
	
	check_ajax_referer('friends_search');
	
	$creds = bp_core_user_creds();
	
	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_friend_count = 0;

	if ( $_POST['friend-search-box'] == "" ) {
		$friendships = friends_get_friendships( $creds['current_userid'], false, $pag_num, $pag_page, false );
	} else {
		$friendships = BP_Friends_Friendship::search_friends( $_POST['friend-search-box'], $creds['current_userid'], $pag_num, $pag_page );
	}
	
	$total_friend_count = (int)$friendships['count'];

	if ( $total_friend_count ) {
		$pag_links = paginate_links( array(
			'base' => $current_domain . $bp_friends_slug . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_friend_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $friendships['friendships'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($friendships['friendships']); $i++ ) {
			$friend = $friendships['friendships'][$i]->friend;
			?>
			<li>
				<?php echo $friend->avatar ?>
				<h4><?php echo $friend->user_link ?></h4>
				<span class="activity">active <?php echo bp_time_since( strtotime( $friend->last_active ) ) ?> ago.</span>
				<hr />
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp_friends_image_base . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No friends matched your search.");
	}
}
add_action( 'wp_ajax_friends_search', 'friends_ajax_friends_search' );

function friends_ajax_addremove_friend() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $current_userid, $loggedin_userid;
	
	check_ajax_referer('addremove_friend');
	$creds = bp_core_user_creds();

	if ( BP_Friends_Friendship::check_is_friend( $creds['loggedin_userid'], $creds['current_userid'] ) == 'is_friend' ) {
		if ( !friends_remove_friend( $creds['loggedin_userid'], $creds['current_userid'] ) ) {
			echo "-1[[SPLIT]]" . __("Friendship could not be canceled.");
		} else {
			echo __('Add Friend');
		}
	} else {
		if ( !friends_add_friend( $creds['loggedin_userid'], $creds['current_userid'] ) ) {
			echo "-1[[SPLIT]]" . __("Friend could not be added.");
		} else {
			echo __('Friendship Requested');
		}
	}
	
	return false;
}
add_action( 'wp_ajax_addremove_friend', 'friends_ajax_addremove_friend' );

?>