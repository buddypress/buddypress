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
	
	function bp_friendship_template( $user_id, $type, $per_page, $max, $filter ) {
		global $bp;

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
					
		$this->pag_page = isset( $_REQUEST['frpage'] ) ? intval( $_REQUEST['frpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		$this->type = $type;

		switch ( $type ) {
			case 'newest':
				$this->friendships = friends_get_newest( $user_id, $this->pag_num, $this->pag_page, $filter );
				break;
			
			case 'alphabetical':
				$this->friendships = friends_get_alphabetically( $user_id, $this->pag_num, $this->pag_page, $filter );				
				break;

			case 'requests':
				$this->friendships = friends_get_friendship_requests( $user_id );
				break;

			case 'active': default:
				$this->friendships = friends_get_recently_active( $user_id, $this->pag_num, $this->pag_page, $filter );	
				break;
		}

		if ( 'requests' == $type ) {
			$this->total_friend_count = $this->friendships['total'];
			$this->friendships = $this->friendships['requests'];
			$this->friendship_count = count($this->friendships);
		} else {
			if ( !$max || $max >= (int)$this->friendships['total'] )
				$this->total_friend_count = (int)$this->friendships['total'];
			else
				$this->total_friend_count = (int)$max;

			$this->friendships = $this->friendships['friends'];

			if ( $max ) {
				if ( $max >= count($this->friendships) )
					$this->friendship_count = count($this->friendships);
				else
					$this->friendship_count = (int)$max;
			} else {
				$this->friendship_count = count($this->friendships);
			}
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'frpage', '%#%' ),
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

		if ( 'requests' == $this->type ) {
			$this->friendship = new BP_Friends_Friendship( $this->friendship );
			$this->friendship->user_id = ( $this->friendship->friend_user_id == $bp->loggedin_user->id ) ?  $this->friendship->initiator_user_id : $this->friendship->friend_user_id;
		} else {
			if ( 'newest' == $this->type )
				$user_id = $this->friendship;
			else
				$user_id = $this->friendship->user_id;
			
			$this->friendship = new stdClass;
				
			if ( !$this->friendship->friend = wp_cache_get( 'bp_user_' . $user_id, 'bp' ) ) {
				$this->friendship->friend = new BP_Core_User( $user_id );
				wp_cache_set( 'bp_user_' . $user_id, $this->friendship->friend, 'bp' );
			}
			
			/* Make sure the user_id is available in the friend object. */
			$this->friendship->friend->user_id = $user_id;
		}

		if ( 0 == $this->current_friendship ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_friendships( $args = '' ) {
	global $bp, $friends_template;

	$defaults = array(
		'type' => 'active',
		'user_id' => false,
		'per_page' => 10,
		'max' => false,
		'filter' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	/* The following code will auto set parameters based on the page being viewed.
	 * for example on example.com/members/andy/friends/my-friends/newest/
	 * $type = 'newest'
	 */
	if ( 'my-friends' == $bp->current_action ) {
		$order = $bp->action_variables[0];
		if ( 'newest' == $order )
			$type = 'newest';
		else if ( 'alphabetically' == $order )
			$type = 'alphabetical';
	} else if ( 'requests' == $bp->current_action ) {
		$type = 'requests';
	}
	
	if ( isset( $_REQUEST['friend-search-box'] ) )
		$filter = $_REQUEST['friend-search-box'];

	$friends_template = new BP_Friendship_Template( $user_id, $type, $per_page, $max, $filter );
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

function bp_friend_id() {
	echo bp_get_friend_id();
}
	function bp_get_friend_id() {
		global $friends_template;
		
		return apply_filters( 'bp_get_friend_id', $friends_template->friendship->friend->user_id );	
	}

function bp_friend_avatar_thumb() {
	echo bp_get_friend_avatar_thumb();
}
	function bp_get_friend_avatar_thumb() {
		global $friends_template;

		if ( !$template )
			$template = &$friends_template->friendship->friend;

		return apply_filters( 'bp_get_friend_avatar_thumb', $friends_template->friendship->friend->avatar_thumb );
	}

function bp_friend_name() {
	echo bp_get_friend_name();
}
	function bp_get_friend_name() {
		global $friends_template;
		
		return apply_filters( 'bp_get_friend_name', strip_tags( $friends_template->friendship->friend->user_link ) );	
	}
	
function bp_friend_link() {
	echo bp_get_friend_link();
}
	function bp_get_friend_link() {
		global $friends_template;

		return apply_filters( 'bp_get_friend_link', $friends_template->friendship->friend->user_link );
	}

function bp_friend_url() {
	echo bp_get_friend_url();
}
	function bp_get_friend_url() {
		global $friends_template;

		return apply_filters( 'bp_get_friend_url', $friends_template->friendship->friend->user_url );
	}

function bp_friend_last_active() {
	echo bp_get_friend_last_active();
}
	function bp_get_friend_last_active() {
		global $friends_template;

		return apply_filters( 'bp_get_friend_last_active', $friends_template->friendship->friend->last_active );
	}
	
function bp_friend_time_since_requested() {
	echo bp_get_friend_time_since_requested();
}
	function bp_get_friend_time_since_requested() {
		global $friends_template;

		if ( $friends_template->friendship->date_created != "0000-00-00 00:00:00" ) {
			return apply_filters( 'bp_friend_time_since_requested', sprintf( __( 'requested %s ago', 'buddypress' ), bp_core_time_since( strtotime( $friends_template->friendship->date_created ) ) ) );
		}
		
		return false;
	}

function bp_friend_accept_request_link() {
	echo bp_get_friend_accept_request_link();
}
	function bp_get_friend_accept_request_link() {
		global $friends_template, $bp;

		return apply_filters( 'bp_get_friend_accept_request_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/requests/accept/' . $friends_template->friendship->id, 'friends_accept_friendship' ) );
	}

function bp_friend_reject_request_link() {
	echo bp_get_friend_reject_request_link();
}
	function bp_get_friend_reject_request_link() {
		global $friends_template, $bp;

		return apply_filters( 'bp_get_friend_reject_request_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/requests/reject/' . $friends_template->friendship->id, 'friends_reject_friendship' ) );	
	}

function bp_friend_pagination() {
	echo bp_get_friend_pagination();
}
	function bp_get_friend_pagination() {
		global $friends_template;
		
		return apply_filters( 'bp_friend_pagination', $friends_template->pag_links );
	}

function bp_friend_pagination_count() {
	global $bp, $friends_template;

	$from_num = intval( ( $friends_template->pag_page - 1 ) * $friends_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $friends_template->pag_num - 1 ) > $friends_template->total_friend_count ) ? $friends_template->total_friend_count : $from_num + ( $friends_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing friend %d to %d (of %d friends)', 'buddypress' ), $from_num, $to_num, $friends_template->total_friend_count ); ?> &nbsp;
	<img id="ajax-loader-friends" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" /><?php
}

function bp_friend_total_for_member() {
	echo bp_get_friend_total_for_member();
}
	function bp_get_friend_total_for_member() {
		return apply_filters( 'bp_get_friend_total_for_member', BP_Friends_Friendship::total_friend_count() );
	}
	
function bp_friend_search_form() {
	global $friends_template, $bp;

	$action = $bp->displayed_user->domain . $bp->friends->slug . '/my-friends/search/';
	$label = __( 'Filter Friends', 'buddypress' );
?>
	<form action="<?php echo $action ?>" id="friend-search-form" method="post">

		<label for="friend-search-box" id="friend-search-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp->friends->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" /></label>
		<input type="search" name="friend-search-box" id="friend-search-box" value="<?php echo $value ?>"<?php echo $disabled ?> />
		
		<?php wp_nonce_field( 'friends_search', '_wpnonce_friend_search' ) ?>
		<input type="hidden" name="initiator" id="initiator" value="<?php echo attribute_escape( $bp->displayed_user->id ) ?>" />
	
	</form>
<?php
}

function bp_friends_is_filtered() {
	if ( isset( $_POST['friend-search-box'] ) )
		return true;
	
	return false;
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
			echo '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/remove-friend/' . $potential_friend_id, 'friends_remove_friend' ) . '" title="' . __('Cancel Friendship', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="remove" class="remove">' . __('Cancel Friendship', 'buddypress') . '</a>';
		} else {
			echo '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/add-friend/' . $potential_friend_id, 'friends_add_friend' ) . '" title="' . __('Add Friend', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="add" class="add">' . __('Add Friend', 'buddypress') . '</a>';
		}
		echo '</div>';
	}
}

function bp_friends_header_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( !isset($bp->action_variables[0]) || 'recently-active' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>/my-friends/recently-active"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
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
	
	if ( !$friend_ids = wp_cache_get( 'friends_friend_ids_' . $bp->displayed_user->id, 'bp' ) ) {
		$friend_ids = BP_Friends_Friendship::get_random_friends( $bp->displayed_user->id );
		wp_cache_set( 'friends_friend_ids_' . $bp->displayed_user->id, $friend_ids, 'bp' );
	}
?>	
	<div class="info-group">
		<h4><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?>  (<?php echo BP_Friends_Friendship::total_friend_count( $bp->displayed_user->id ) ?>)  <a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></h4>
		
		<?php if ( $friend_ids ) { ?>
			<ul class="horiz-gallery">
			<?php for ( $i = 0; $i < count( $friend_ids ); $i++ ) { ?>
				<li>
					<a href="<?php echo bp_core_get_userurl( $friend_ids[$i] ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $friend_ids[$i], 'type' => 'thumb' ) ) ?></a>
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
	
	if ( !$user_ids = wp_cache_get( 'friends_random_users', 'bp' ) ) {
		$user_ids = BP_Core_User::get_random_users( $total_members );
		wp_cache_set( 'friends_random_users', $user_ids, 'bp' );
	}
?>	
	<?php if ( $user_ids['users'] ) { ?>
		<ul class="item-list" id="random-members-list">
		<?php for ( $i = 0; $i < count( $user_ids['users'] ); $i++ ) { ?>
			<li>
				<a href="<?php echo bp_core_get_userurl( $user_ids['users'][$i]->user_id ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user_ids['users'][$i]->user_id, 'type' => 'thumb' ) ) ?></a>
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
