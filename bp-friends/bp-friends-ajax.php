<?php

function friends_ajax_friends_search() {
	global $bp_friends_image_base;
	global $current_domain, $bp_friends_slug;
	global $current_userid;
	
	check_ajax_referer('friends_search');
	
	bp_core_user_creds();
	
	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_friend_count = 0;
	
	if ( $_POST['friend-search-box'] == "" ) {
		if ( $friendships = friends_get_friendships( $_POST['initiator_id'], false, $pag_num, $pag_page ) )
			$total_friend_count = (int)$friendships['count'];
	} else {
		if ( $friendships = BP_Friends_Friendship::search_friends( $_POST['friend-search-box'], $_POST['initiator_id'], $pag_num, $pag_page ) )
			$total_friend_count = (int) BP_Friends_Friendship::search_friends_count( $_POST['friend-search-box'], $_POST['initiator_id'] );
	}
	
	if ( $total_friend_count ) {
		
		var_dump($pag_num);
		var_dump($pag_page);
		var_dump($total_friend_count);
		
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

?>