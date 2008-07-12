<?php

class BP_Friendship_Template {
	var $current_friendship = -1;
	var $friendship_count;
	var $friendships;
	var $friendship;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_friend_count;
	
	function bp_friendship_template() {
		global $action_variables, $current_action;
		global $current_userid;
		
		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		if ( $current_action = 'my-friends' && in_array( 'search', $action_variables) && $_POST['friend-search-box'] != '' ) {
			if ( $this->friendships = BP_Friends_Friendship::search_friends( $_POST['friend-search-box'], $current_userid, $this->pag_num, $this->pag_page ) )
				$this->total_friend_count = (int) BP_Friends_Friendship::search_friends_count( $_POST['friend-search-box'], $current_userid );
		} else {
			if ( $this->friendships = friends_get_friendships( $current_userid, false, $this->pag_num, $this->pag_page ) )
				$this->total_friend_count = (int)$this->friendships['count'];
		}

		$this->friendships = $this->friendships['friendships'];
		$this->friendship_count = count($this->friendships);

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_friend_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_friendships() {
		if ( $this->friendship_count )
			return true;
		
		return false;
	}
	
	function next_friendship() {
		$this->current_friendship++;
		$this->friendship = $this->friendships[$this->current_friendship];
		
		return $this->friendship;
	}
	
	function rewind_friendships() {
		$this->current_friendship = -1;
		if ( $this->friendship_count > 0 ) {
			$this->friendship = $this->friendship[0];
		}
	}
	
	function user_friendships() { 
		if ( $this->current_friendship + 1 < $this->friendship_count ) {
			return true;
		} elseif ( $this->current_friendship + 1 == $this->friendship_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_friendships();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_friendship() {
		global $friendship;

		$this->in_the_loop = true;
		$this->friendship = $this->next_friendship();

		if ( $this->current_friendship == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_friendships() {
	global $friends_template;
	return $friends_template->has_friendships();
}

function bp_the_friendship() {
	global $friends_template;
	return $friends_template->the_friendship();
}

function bp_user_friendships() {
	global $friends_template;
	return $friends_template->user_friendships();
}

function bp_friend_avatar_thumb() {
	global $friends_template;
	echo $friends_template->friendship->friend->avatar;
}

function bp_friend_status() {
	global $friends_template;
	
	if ( $friends_template->friendship->friend->status )
		echo $friends_template->friendship->friend->status;
}

function bp_friend_link() {
	global $friends_template;
	echo $friends_template->friendship->friend->user_link;
}

function bp_friend_last_active() {
	global $friends_template;
	echo bp_time_since( strtotime( $friends_template->friendship->friend->last_active ) );
}

function bp_friend_last_profile_update() {
	global $friends_template;
	echo $friends_template->friendship->friend->profile_last_updated;
}

function bp_friend_last_status_update() {
	global $friends_template;
	echo $friends_template->friendship->friend->profile_last_updated;
}

function bp_friend_last_content_update() {
	global $friends_template;
	echo $friends_template->friendship->friend->content_last_updated;
}

function bp_friend_pagination() {
	global $friends_template;
	echo $friends_template->pag_links;
}

function bp_friend_search_form() {
	global $current_domain, $current_userid, $bp_friends_image_base, $bp_friends_slug;
	global $friends_template;

	if ( !$friends_template->friendship_count ) {
		$disabled = ' disabled="disabled"';
	}
?>
	<form action="<?php echo $current_domain . $bp_friends_slug ?>/my-friends/search/" id="friend-search-form" method="post">
		<label for="friend-search-box" id="friend-search-label">Search Friends <img id="ajax-loader" src="<?php echo $bp_friends_image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></label>
		<input type="search" name="friend-search-box" id="friend-search-box" value=""<?php echo $disabled ?> />
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field('friends_search');
		?>
		<input type="hidden" name="initiator" id="initiator" value="<?php echo $current_userid ?>" />
	</form>
<?php
}

function bp_friend_all_friends_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/all-friends';
}

function bp_friend_latest_update_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/last-updated';	
}

function bp_friend_recent_activity_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/recently-active';	
}

function bp_friend_recent_status_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/status-updates';	
}

function bp_add_friend_button() {
	global $loggedin_userid, $current_userid;
	global $loggedin_domain, $bp_friends_slug;
	
	if ( $loggedin_userid == $current_userid )
		return false;
	
	if ( BP_Friends_Friendship::check_is_friend() ) {
		echo '<a href="' . $loggedin_domain . $bp_friends_slug . '/add-friend/' . $current_userid . '" title="' . __('Cancel Friendship') . '" id="remove-friend">' . __('Cancel Friendship') . '</a>';
	} else {
		echo '<a href="' . $loggedin_domain . $bp_friends_slug . '/add-friend/' . $current_userid . '" title="' . __('Add Friend') . '" id="add-friend">' . __('Add Friend') . '</a>';
	}
}

?>
