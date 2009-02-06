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
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['fpage'] ) ? intval( $_REQUEST['fpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : 10;

		if ( 'my-friends' == $bp->current_action && !empty( $_POST['friend-search-box'] ) ) {
			
			// Search results
			$this->friendships = friends_search_friends( $_POST['friend-search-box'], $bp->displayed_user->id, $this->pag_num, $this->pag_page );
			$this->total_friend_count = (int)$this->friendships['total'];
			$this->friendships = $this->friendships['friends'];
		
		} else if ( 'requests' == $bp->current_action ) {
		
			// Friendship Requests
			$this->friendships = friends_get_friendship_requests( $bp->displayed_user->id );
			$this->total_friend_count = $this->friendships['total'];
			$this->friendships = $this->friendships['requests'];

		} else {
			$order = $bp->action_variables[0];
			
			if ( 'newest' == $order ) {
				$this->friendships = friends_get_newest( $bp->displayed_user->id, $this->pag_num, $this->pag_page );
			} else if ( 'alphabetically' == $order ) {
				$this->friendships = friends_get_alphabetically( $bp->displayed_user->id, $this->pag_num, $this->pag_page );				
			} else {
				$this->friendships = friends_get_recently_active( $bp->displayed_user->id, $this->pag_num, $this->pag_page );	
			}
			
			$this->total_friend_count = (int)$this->friendships['total'];
			$this->friendships = $this->friendships['friends'];
		}

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
			$this->friendship = $this->friendships[0];
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
		global $friendship, $bp;

		$this->in_the_loop = true;
		$this->friendship = $this->next_friendship();
		
		if ( 'requests' == $bp->current_action ) {
			$this->friendship = new BP_Friends_Friendship( $this->friendship );
		} else {
			$this->friendship = (object) $this->friendship;
			$this->friendship->friend = new BP_Core_User( $this->friendship->user_id );
		}
		
		if ( 0 == $this->current_friendship ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_friendships() {
	global $bp, $friends_template;

	$friends_template = new BP_Friendship_Template( $bp->displayed_user->id );
	
	return $friends_template->has_friendships();
}

	function bp_has_users() {
		bp_has_friendships();
	}

function bp_the_friendship() {
	global $friends_template;
	return $friends_template->the_friendship();
}
	function bp_the_user() {
		bp_the_friendship();
	}

function bp_user_friendships() {
	global $friends_template;
	return $friends_template->user_friendships();
}
	function bp_friends_user_users() {
		bp_friends_user_friendships();
	}

function bp_friend_avatar_thumb( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	echo apply_filters( 'bp_friend_avatar_thumb', $template->avatar_thumb );
}
	function bp_user_avatar_thumb() {
		global $friends_template;
		bp_friend_avatar_thumb( $friends_template->friendship );
	}

function bp_friend_status( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	if ( $template->status )
		echo apply_filters( 'bp_friend_status', $template->status );
}
	function bp_user_status_message() {
		global $friends_template;
		bp_friend_status( $friends_template->friendship );
	}

function bp_friend_link( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	echo apply_filters( 'bp_friend_link', $template->user_link );
}
	function bp_user_url() {
		global $friends_template;
		bp_friend_link( $friends_template->friendship );
	}

function bp_friend_last_active( $time = false, $template = false ) {
	global $friends_template;
	
	if ( !$time )
		$time = $friends_template->friendship->friend->last_active;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo apply_filters( 'bp_friend_last_active', $time );
}
	function bp_user_last_active( $time = false ) {
		global $friends_template;
		bp_friend_last_active( $time, $friends_template->friendship );
	}

function bp_friend_last_profile_update( $template = false ) {
	global $friends_template;

	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo apply_filters( 'bp_friend_last_profile_update', $template->profile_last_updated );
}
	function bp_user_last_profile_update() {
		global $friends_template;
		bp_friend_last_profile_update( $friends_template->friendship );
	}

function bp_friend_last_status_update( $template = false ) {
	global $friends_template;

	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo apply_filters( 'bp_friend_last_status_update', $template->profile_last_updated );
}
	function bp_user_last_status_update() {
		global $friends_template;
		bp_friend_last_status_update( $friends_template->friendship );
	}

function bp_friend_last_content_update( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo apply_filters( 'bp_friend_last_content_update', $template->content_last_updated );
}
	function bp_user_last_content_update() {
		global $friends_template;
		bp_friend_last_content_update( $friends_template->friendship );
	}

function bp_friend_time_since_requested() {
	global $friends_template;
	
	if ( $friends_template->friendship->date_created != "0000-00-00 00:00:00" ) {
		echo apply_filters( 'bp_friend_time_since_requested', sprintf( __( 'requested %s ago', 'buddypress' ), bp_core_time_since( strtotime( $friends_template->friendship->date_created ) ) ) );
	}
}

function bp_friend_accept_request_link() {
	global $friends_template, $bp;
	
	echo apply_filters( 'bp_friend_accept_request_link', $bp->loggedin_user->domain . $bp->friends->slug . '/requests/accept/' . $friends_template->friendship->id );
}

function bp_friend_reject_request_link() {
	global $friends_template, $bp;
	
	echo apply_filters( 'bp_friend_reject_request_link', $bp->loggedin_user->domain . $bp->friends->slug . '/requests/reject/' . $friends_template->friendship->id );	
}

function bp_friend_pagination() {
	global $friends_template;
	echo apply_filters( 'bp_friend_pagination', $friends_template->pag_links );
}

function bp_friend_search_form() {
	global $friends_template, $bp;

	$action = $bp->displayed_user->domain . $bp->friends->slug . '/my-friends/search/';
	$label = __( 'Filter Friends', 'buddypress' );
?>
	<form action="<?php echo $action ?>" id="friend-search-form" method="post">
		<label for="friend-search-box" id="friend-search-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp->friends->image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></label>
		<input type="search" name="friend-search-box" id="friend-search-box" value="<?php echo $value ?>"<?php echo $disabled ?> />
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field('friend_search' );
		?>
		<input type="hidden" name="initiator" id="initiator" value="<?php echo $bp->displayed_user->id ?>" />
	</form>
<?php
}

function bp_friend_all_friends_link() {
	global $bp;
	echo apply_filters( 'bp_friend_all_friends_link', $bp->displayed_user->domain . 'my-friends/all-friends' );
}

function bp_friend_latest_update_link() {
	global $bp;
	echo apply_filters( 'bp_friend_latest_update_link', $bp->displayed_user->domain . 'my-friends/last-updated' );	
}

function bp_friend_recent_activity_link() {
	global $bp;
	echo apply_filters( 'bp_friend_recent_activity_link', $bp->displayed_user->domain . 'my-friends/recently-active' );	
}

function bp_friend_recent_status_link() {
	global $bp;
	echo apply_filters( 'bp_friend_recent_status_link', $bp->displayed_user->domain . 'my-friends/status-updates' );	
}

function bp_add_friend_button( $potential_friend_id = false ) {
	global $bp, $friends_template;
	
	if ( is_user_logged_in() ) {
		
		if ( !$potential_friend_id && $friends_template->friendship->friend )
			$potential_friend_id = $friends_template->friendship->friend->id;
		else if ( !$potential_friend_id && !$friends_template->friendship->friend )
			$potential_friend_id = $bp->displayed_user->id;

		if ( $bp->loggedin_user->id == $potential_friend_id )
			return false;

		$friend_status = BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $potential_friend_id );

		echo '<div class="friendship-button ' . $friend_status . '" id="friendship-button-' . $potential_friend_id . '">';
		if ( 'pending' == $friend_status ) {
			echo '<a class="requested" href="' . $bp->loggedin_user->domain . $bp->friends->slug . '">' . __( 'Friendship Requested', 'buddypress' ) . '</a>';
		} else if ( 'is_friend' == $friend_status ) {
			echo '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/remove-friend/' . $potential_friend_id . '" title="' . __('Cancel Friendship', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="remove" class="remove">' . __('Cancel Friendship', 'buddypress') . '</a>';
		} else {
			echo '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/add-friend/' . $potential_friend_id . '" title="' . __('Add Friend', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="add" class="add">' . __('Add Friend', 'buddypress') . '</a>';
		}
		echo '</div>';

		// This causes duplicates, so it's not feasible as is.
		// if ( function_exists('wp_nonce_field') )
		//	wp_nonce_field('addremove_friend');
	}
}

function bp_friends_header_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( !isset($bp->action_variables[0]) || 'recently_active' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>/my-friends/recently-active"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( 'newest' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>/my-friends/newest"><?php _e( 'Newest', 'buddypress' ) ?></a></li>
	<li<?php if ( 'alphabetically' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>/my-friends/alphabetically""><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>
<?php
	do_action( 'friends_header_tabs' );
}

function bp_friends_filter_title() {
	global $bp;
	
	$current_filter = $bp->action_variables[0];
	
	switch ( $current_filter ) {
		case 'recently-active': default:
			_e( 'Recently Active', 'buddypress' );
			break;
		case 'newest':
			_e( 'Newest', 'buddypress' );
			break;
		case 'alphabetically':
			_e( 'Alphabetically', 'buddypress' );
		break;
	}
}

function bp_friends_random_friends() {
	global $bp;
	
	$friend_ids = BP_Friends_Friendship::get_random_friends( $bp->displayed_user->id );
?>	
	<div class="info-group">
		<h4><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?>  (<?php echo BP_Friends_Friendship::total_friend_count( $bp->displayed_user->id ) ?>)  <a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></h4>
		
		<?php if ( $friend_ids ) { ?>
			<ul class="horiz-gallery">
			<?php for ( $i = 0; $i < count( $friend_ids ); $i++ ) { ?>
				<li>
					<a href="<?php echo bp_core_get_userurl( $friend_ids[$i] ) ?>"><?php echo bp_core_get_avatar( $friend_ids[$i], 1 ) ?></a>
					<h5><?php echo bp_core_get_userlink($friend_ids[$i]) ?></h5>
				</li>
			<?php } ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "You haven't added any friend connections yet.", 'buddypress' ), __( "%s hasn't created any friend connections yet.", 'buddypress' ) ) ?></p>
			</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
<?php
}

function bp_friends_random_members( $total_members = 5 ) {
	global $bp;
	
	$user_ids = BP_Core_User::get_random_users( $total_members );
?>	
	<?php if ( $user_ids['users'] ) { ?>
		<ul class="item-list" id="random-members-list">
		<?php for ( $i = 0; $i < count( $user_ids['users'] ); $i++ ) { ?>
			<li>
				<a href="<?php echo bp_core_get_userurl( $user_ids['users'][$i]->user_id ) ?>"><?php echo bp_core_get_avatar( $user_ids['users'][$i]->user_id, 1 ) ?></a>
				<h5><?php echo bp_core_get_userlink($user_ids['users'][$i]->user_id) ?></h5>
				<?php if ( function_exists( 'xprofile_get_random_profile_data' ) ) { ?>
					<?php $random_data = xprofile_get_random_profile_data( $user_ids['users'][$i]->user_id, true ); ?>
					<div class="profile-data">
						<p class="field-name"><?php echo $random_data[0]->name ?></p>
						<?php echo $random_data[0]->value ?>
					</div>
				<?php } ?>
				
				<div class="action">
					<?php if ( function_exists( 'bp_add_friend_button' ) ) { ?>
						<?php bp_add_friend_button( $user_ids['users'][$i]->user_id ) ?>
					<?php } ?>
				</div>
			</li>
		<?php } ?>
		</ul>
	<?php } else { ?>
		<div id="message" class="info">
			<p><?php _e( "There aren't enough site members to show a random sample just yet.", 'buddypress' ) ?></p>
		</div>		
	<?php } ?>
<?php
}

?>
