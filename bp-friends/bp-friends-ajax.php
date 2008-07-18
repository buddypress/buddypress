<?php

function friends_ajax_friends_search() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $current_userid, $creds;

	check_ajax_referer('friend_search');

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

function friends_ajax_finder_search() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $loggedin_domain;
	global $current_userid, $creds;
	
	check_ajax_referer('finder_search');
	
	$creds = bp_core_user_creds();
		
	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_user_count = 0;

	if ( $_POST['finder-search-box'] == "" ) {
		echo "-1[[SPLIT]]" . __("Please enter something to search for.");
		return;
	}
	
	$users = friends_search_users( $_POST['finder-search-box'], $creds['loggedin_userid'], $pag_num, $pag_page );

	$total_user_count = (int)$users['count'];

	if ( $total_user_count ) {
		$pag_links = paginate_links( array(
			'base' => $current_domain . $bp_friends_slug . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_user_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $users['users'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($users['users']); $i++ ) {
			$user = $users['users'][$i];
			?>
				<li>
					<?php echo $user->avatar ?>
					<h4><?php echo $user->user_link ?></h4>
					<span class="activity"><?php bp_friend_last_active( $user->last_active ) ?></span>
					<?php bp_add_friend_button( $user->id, $creds ) ?>
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
add_action( 'wp_ajax_finder_search', 'friends_ajax_finder_search' );


function friends_ajax_addremove_friend() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $current_userid, $loggedin_userid;

	$creds = bp_core_user_creds();

	if ( BP_Friends_Friendship::check_is_friend( $creds['loggedin_userid'], $_POST['fid'] ) == 'is_friend' ) {
		if ( !friends_remove_friend( $creds['loggedin_userid'], $creds['current_userid'] ) ) {
			echo "-1[[SPLIT]]" . __("Friendship could not be canceled.");
		} else {
			echo __('Add Friend');
		}
	} else if ( BP_Friends_Friendship::check_is_friend( $creds['loggedin_userid'], $_POST['fid'] ) == 'not_friends' ) {
		if ( !friends_add_friend( $creds['loggedin_userid'], $_POST['fid'] ) ) {
			echo "-1[[SPLIT]]" . __("Friend could not be added.");
		} else {
			echo __('Friendship Requested');
		}
	} else {
		echo __('Request Pending');
	}
	
	return false;
}
add_action( 'wp_ajax_addremove_friend', 'friends_ajax_addremove_friend' );

?>