<?php

function bp_friends_header_tabs() {
	global $bp;
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
		<h4><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?>  (<?php echo BP_Friends_Friendship::total_friend_count( $bp->displayed_user->id ) ?>) <span><a href="<?php echo $bp->displayed_user->domain . $bp->friends->slug ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></span></h4>

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

function bp_friend_search_form() {
	global $friends_template, $bp;

	$action = $bp->displayed_user->domain . $bp->friends->slug . '/my-friends/search/';
	$label = __( 'Filter Friends', 'buddypress' );
	?>
		<form action="<?php echo $action ?>" id="friend-search-form" method="post">

		<label for="friend-search-box" id="friend-search-label"><?php echo $label ?></label>
		<input type="search" name="friend-search-box" id="friend-search-box" value="<?php echo $value ?>"<?php echo $disabled ?> />

		<?php wp_nonce_field( 'friends_search', '_wpnonce_friend_search' ) ?>
		<input type="hidden" name="initiator" id="initiator" value="<?php echo attribute_escape( $bp->displayed_user->id ) ?>" />

		</form>
	<?php
}

function bp_add_friend_button( $potential_friend_id = false ) {
	echo bp_get_add_friend_button( $potential_friend_id );
}
	function bp_get_add_friend_button( $potential_friend_id = false ) {
		global $bp, $friends_template;

		$button = false;

		if ( is_user_logged_in() ) {

			if ( !$potential_friend_id && $friends_template->friendship->friend )
				$potential_friend_id = $friends_template->friendship->friend->id;
			else if ( !$potential_friend_id && !$friends_template->friendship->friend )
				$potential_friend_id = $bp->displayed_user->id;

			if ( $bp->loggedin_user->id == $potential_friend_id )
				return false;

			$friend_status = BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $potential_friend_id );

			$button = '<div class="generic-button friendship-button ' . $friend_status . '" id="friendship-button-' . $potential_friend_id . '">';
			if ( 'pending' == $friend_status ) {
				$button .= '<a class="requested" href="' . $bp->loggedin_user->domain . $bp->friends->slug . '">' . __( 'Friendship Requested', 'buddypress' ) . '</a>';
			} else if ( 'is_friend' == $friend_status ) {
				$button .= '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/remove-friend/' . $potential_friend_id, 'friends_remove_friend' ) . '" title="' . __('Cancel Friendship', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="remove" class="remove">' . __('Cancel Friendship', 'buddypress') . '</a>';
			} else {
				$button .= '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/add-friend/' . $potential_friend_id, 'friends_add_friend' ) . '" title="' . __('Add Friend', 'buddypress') . '" id="friend-' . $potential_friend_id . '" rel="add" class="add">' . __('Add Friend', 'buddypress') . '</a>';
			}
			$button .= '</div>';
		}

		return apply_filters( 'bp_get_add_friend_button', $button );
	}

function bp_get_friend_ids( $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	return implode( ',', friends_get_friend_user_ids( $user_id ) );
}
function bp_get_friendship_requests() {
	global $bp;

	return apply_filters( 'bp_get_friendship_requests', implode( ',', (array) friends_get_friendship_request_user_ids( $bp->loggedin_user->id ) ) );
}

function bp_friend_accept_request_link() {
	echo bp_get_friend_accept_request_link();
}
	// You only have the user ID but you need the friendship ID !!

	function bp_get_friend_accept_request_link() {
		global $members_template, $bp;

		if ( !$friendship_id = wp_cache_get( 'friendship_id_' . $members_template->member->id . '_' . $bp->loggedin_user->id ) ) {
			$friendship_id = friends_get_friendship_id( $members_template->member->id, $bp->loggedin_user->id );
			wp_cache_set( 'friendship_id_' . $members_template->member->id . '_' . $bp->loggedin_user->id, $friendship_id, 'bp' );
		}

		return apply_filters( 'bp_get_friend_accept_request_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/requests/accept/' . $friendship_id, 'friends_accept_friendship' ) );
	}

function bp_friend_reject_request_link() {
	echo bp_get_friend_reject_request_link();
}
	function bp_get_friend_reject_request_link() {
		global $members_template, $bp;

		if ( !$friendship_id = wp_cache_get( 'friendship_id_' . $members_template->member->id . '_' . $bp->loggedin_user->id ) ) {
			$friendship_id = friends_get_friendship_id( $members_template->member->id, $bp->loggedin_user->id );
			wp_cache_set( 'friendship_id_' . $members_template->member->id . '_' . $bp->loggedin_user->id, $friendship_id, 'bp' );
		}

		return apply_filters( 'bp_get_friend_reject_request_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . '/requests/reject/' . $friendship_id, 'friends_reject_friendship' ) );
	}

function bp_total_friend_count( $user_id = false ) {
	echo bp_get_total_friend_count( $user_id );
}
	function bp_get_total_friend_count( $user_id = false ) {
		return apply_filters( 'bp_get_total_friend_count', friends_get_total_friend_count( $user_id ) );
	}
	add_filter( 'bp_get_total_friend_count', 'number_format' );
?>