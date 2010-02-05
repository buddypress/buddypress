<?php
/***
 * AJAX Functions
 *
 * All of these functions enhance the responsiveness of the user interface in the default
 * theme by adding AJAX functionality.
 *
 * By default your child theme will inherit this AJAX functionality. You can however create
 * your own _inc/ajax.php file and add/remove AJAX functionality as you see fit.
 */

/***
 * Each object loop (activity/members/groups/blogs/forums) contains parameters to
 * show specific information based on the page we are currently looking at.
 * The following function will take into account any cookies set in the JS and allow us
 * to override the parameters sent. That way we can change the results returned without reloading the page.
 */
function bp_dtheme_ajax_querystring( $object = false ) {
	global $bp;

	if ( empty( $object ) )
		return false;

	/* Set up the cookies passed on this AJAX request. Store a local var to avoid conflicts */
	if ( !empty( $_POST['cookie'] ) )
		$_BP_COOKIE = wp_parse_args( str_replace( '; ', '&', urldecode( $_POST['cookie'] ) ) );
	else
		$_BP_COOKIE = &$_COOKIE;

	$qs = false;

	/***
	 * Check if any cookie values are set. If there are then override the default params passed to the
	 * template loop
	 */
	if ( !empty( $_BP_COOKIE['bp-' . $object . '-filter'] ) && '-1' != $_BP_COOKIE['bp-' . $object . '-filter'] ) {
		$qs[] = 'type=' . $_BP_COOKIE['bp-' . $object . '-filter'];
		$qs[] = 'action=' . $_BP_COOKIE['bp-' . $object . '-filter']; // Activity stream filtering on action
	}

	if ( !empty( $_BP_COOKIE['bp-' . $object . '-scope'] ) ) {
		if ( 'personal' == $_BP_COOKIE['bp-' . $object . '-scope'] ) {
			$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
			$qs[] = 'user_id=' . $user_id;
		}
		if ( 'all' != $_BP_COOKIE['bp-' . $object . '-scope'] && empty( $bp->displayed_user->id ) && !$bp->is_single_item )
			$qs[] = 'scope=' . $_BP_COOKIE['bp-' . $object . '-scope']; // Activity stream scope only on activity directory.
	}

	/* If page and search_terms have been passed via the AJAX post request, use those */
	if ( !empty( $_POST['page'] ) && '-1' != $_POST['page'] )
		$qs[] = 'page=' . $_POST['page'];

	if ( !empty( $_POST['search_terms'] ) && __( 'Search anything...', 'buddypress' ) != $_POST['search_terms'] && 'false' != $_POST['search_terms'] && 'undefined' != $_POST['search_terms'] )
		$qs[] = 'search_terms=' . $_POST['search_terms'];

	/* Now pass the querystring to override default values. */
	if ( !empty( $qs ) )
		return apply_filters( 'bp_dtheme_ajax_querystring', join( '&', (array)$qs ), $object, $_BP_COOKIE['bp-' . $object . '-filter'], $_BP_COOKIE['bp-' . $object . '-scope'], $_BP_COOKIE['bp-' . $object . '-page'], $_BP_COOKIE['bp-' . $object . '-search-terms'], $_BP_COOKIE['bp-' . $object . '-extras'] );
}

function bp_dtheme_object_template_loader() {
	$object = esc_attr( $_POST['object'] );
	locate_template( array( "$object/$object-loop.php" ), true );
}
add_action( 'wp_ajax_members_filter', 'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_groups_filter', 'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_blogs_filter', 'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_forums_filter', 'bp_dtheme_object_template_loader' );

function bp_dtheme_activity_template_loader() {
	global $bp;

	/* We need to calculate and return the feed URL for each scope */
	$feed_url = site_url( BP_ACTIVITY_SLUG . '/feed/' );

	switch ( $_POST['scope'] ) {
		case 'friends':
			$feed_url = $bp->loggedin_user->domain . BP_ACTIVITY_SLUG . '/friends/feed/';
			break;
		case 'groups':
			$feed_url = $bp->loggedin_user->domain . BP_ACTIVITY_SLUG . '/groups/feed/';
			break;
		case 'favorites':
			$feed_url = $bp->loggedin_user->domain . BP_ACTIVITY_SLUG . '/favorites/feed/';
			break;
		case 'mentions':
			$feed_url = $bp->loggedin_user->domain . BP_ACTIVITY_SLUG . '/mentions/feed/';
			delete_usermeta( $bp->loggedin_user->id, 'bp_new_mention_count' );
			break;
	}

	/* Buffer the loop in the template to a var for JS to spit out. */
	ob_start();
	locate_template( array( 'activity/activity-loop.php' ), true );
	$result['contents'] = ob_get_contents();
	$result['feed_url'] = apply_filters( 'bp_dtheme_activity_feed_url', $feed_url, $_POST['scope'] );
	ob_end_clean();

	echo json_encode( $result );
}
add_action( 'wp_ajax_activity_widget_filter', 'bp_dtheme_activity_template_loader' );
add_action( 'wp_ajax_activity_get_older_updates', 'bp_dtheme_activity_template_loader' );

function bp_dtheme_post_update() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'post_update', '_wpnonce_post_update' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	if ( empty( $_POST['content'] ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'Please enter some content to post.', 'buddypress' ) . '</p></div>';
		return false;
	}

	if ( empty( $_POST['object'] ) ) {
		$activity_id = bp_activity_post_update( array(
			'content' => $_POST['content']
		));
	} elseif ( $_POST['object'] == 'groups' ) {
		if ( !empty( $_POST['item_id'] ) ) {
		$activity_id = groups_post_update( array(
			'content' => $_POST['content'],
				'group_id' => $_POST['item_id']
		));
		}
	} else {
		$activity_id = apply_filters( 'bp_activity_custom_update', $_POST['object'], $_POST['item_id'], $_POST['content'] );
	}

	if ( !$activity_id ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was a problem posting your update, please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	if ( bp_has_activities ( 'include=' . $activity_id ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<?php locate_template( array( 'activity/entry.php' ), true ) ?>
		<?php endwhile; ?>
	 <?php endif;
}
add_action( 'wp_ajax_post_update', 'bp_dtheme_post_update' );

function bp_dtheme_new_activity_comment() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'new_activity_comment', '_wpnonce_new_activity_comment' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	if ( empty( $_POST['content'] ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'Please do not leave the comment area blank.', 'buddypress' ) . '</p></div>';
		return false;
	}

	if ( empty( $_POST['form_id'] ) || empty( $_POST['comment_id'] ) || !is_numeric( $_POST['form_id'] ) || !is_numeric( $_POST['comment_id'] ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was an error posting that reply, please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	$comment_id = bp_activity_new_comment( array(
		'content' => $_POST['content'],
		'activity_id' => $_POST['form_id'],
		'parent_id' => $_POST['comment_id']
	));

	if ( !$comment_id ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was an error posting that reply, please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	if ( bp_has_activities ( 'include=' . $comment_id ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li id="acomment-<?php bp_activity_id() ?>">
				<div class="acomment-avatar">
					<?php bp_activity_avatar( array( 'width' => 25, 'height' => 25 ) ) ?>
				</div>

				<div class="acomment-meta">
					<?php echo bp_core_get_userlink( bp_get_activity_user_id() ) ?> &middot; <?php printf( __( '%s ago', 'buddypress' ), bp_core_time_since( gmdate( "Y-m-d H:i:s" ) ) ) ?> &middot;
					<a class="acomment-reply" href="#acomment-<?php bp_activity_id() ?>" id="acomment-reply-<?php echo attribute_escape( $_POST['form_id'] ) ?>"><?php _e( 'Reply', 'buddypress' ) ?></a>
					 &middot; <a href="<?php echo wp_nonce_url( $bp->root_domain . '/' . $bp->activity->slug . '/delete/' . bp_get_activity_id() . '?cid=' . $comment_id, 'bp_activity_delete_link' ) ?>" class="delete acomment-delete confirm"><?php _e( 'Delete', 'buddypress' ) ?></a>
				</div>

				<div class="acomment-content">
					<?php bp_activity_content() ?>
				</div>
			</li>
		<?php endwhile; ?>
	 <?php endif;
}
add_action( 'wp_ajax_new_activity_comment', 'bp_dtheme_new_activity_comment' );

function bp_dtheme_delete_activity() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'bp_activity_delete_link' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	$activity = new BP_Activity_Activity( $_POST['id'] );

	/* Check access */
	if ( !is_site_admin() && $activity->user_id != $bp->loggedin_user->id )
		return false;

	if ( empty( $_POST['id'] ) || !is_numeric( $_POST['id'] ) || !bp_activity_delete( array( 'id' => $_POST['id'], 'user_id' => $activity->user_id ) ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was a problem when deleting. Please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	return true;
}
add_action( 'wp_ajax_delete_activity_comment', 'bp_dtheme_delete_activity' );
add_action( 'wp_ajax_delete_activity', 'bp_dtheme_delete_activity' );

function bp_dtheme_mark_activity_favorite() {
	global $bp;

	bp_activity_add_user_favorite( $_POST['id'] );
	_e( 'Remove Favorite', 'buddypress' );
}
add_action( 'wp_ajax_activity_mark_fav', 'bp_dtheme_mark_activity_favorite' );

function bp_dtheme_unmark_activity_favorite() {
	global $bp;

	bp_activity_remove_user_favorite( $_POST['id'] );
	_e( 'Favorite', 'buddypress' );
}
add_action( 'wp_ajax_activity_mark_unfav', 'bp_dtheme_unmark_activity_favorite' );

function bp_dtheme_ajax_invite_user() {
	global $bp;

	check_ajax_referer( 'groups_invite_uninvite_user' );

	if ( !$_POST['friend_id'] || !$_POST['friend_action'] || !$_POST['group_id'] )
		return false;

	if ( !groups_is_user_admin( $bp->loggedin_user->id, $_POST['group_id'] ) )
		return false;

	if ( !friends_check_friendship( $bp->loggedin_user->id, $_POST['friend_id'] ) )
		return false;

	if ( 'invite' == $_POST['friend_action'] ) {

		if ( !groups_invite_user( array( 'user_id' => $_POST['friend_id'], 'group_id' => $_POST['group_id'] ) ) )
			return false;

		$user = new BP_Core_User( $_POST['friend_id'] );

		echo '<li id="uid-' . $user->id . '">';
		echo $user->avatar_thumb;
		echo '<h4>' . $user->user_link . '</h4>';
		echo '<span class="activity">' . esc_attr( $user->last_active ) . '</span>';
		echo '<div class="action">
				<a class="remove" href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->groups->slug . '/' . $_POST['group_id'] . '/invites/remove/' . $user->id, 'groups_invite_uninvite_user' ) . '" id="uid-' . attribute_escape( $user->id ) . '">' . __( 'Remove Invite', 'buddypress' ) . '</a>
			  </div>';
		echo '</li>';

	} else if ( 'uninvite' == $_POST['friend_action'] ) {

		if ( !groups_uninvite_user( $_POST['friend_id'], $_POST['group_id'] ) )
			return false;

		return true;

	} else {
		return false;
	}
}
add_action( 'wp_ajax_groups_invite_user', 'bp_dtheme_ajax_invite_user' );

function bp_dtheme_ajax_addremove_friend() {
	global $bp;

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
add_action( 'wp_ajax_addremove_friend', 'bp_dtheme_ajax_addremove_friend' );

function bp_dtheme_ajax_accept_friendship() {
	check_admin_referer( 'friends_accept_friendship' );

	if ( !friends_accept_friendship( $_POST['id'] ) )
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem accepting that request. Please try again.', 'buddypress' ) . '</p></div>';

	return true;
}
add_action( 'wp_ajax_accept_friendship', 'bp_dtheme_ajax_accept_friendship' );

function bp_dtheme_ajax_reject_friendship() {
	check_admin_referer( 'friends_reject_friendship' );

	if ( !friends_reject_friendship( $_POST['id'] ) )
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem rejecting that request. Please try again.', 'buddypress' ) . '</p></div>';

	return true;
}
add_action( 'wp_ajax_reject_friendship', 'bp_dtheme_ajax_reject_friendship' );

function bp_dtheme_ajax_joinleave_group() {
	global $bp;

	if ( groups_is_user_banned( $bp->loggedin_user->id, $_POST['gid'] ) )
		return false;

	if ( !$group = new BP_Groups_Group( $_POST['gid'], false, false ) )
		return false;

	if ( 'hidden' == $group->status )
		return false;

	if ( !groups_is_user_member( $bp->loggedin_user->id, $group->id ) ) {

		if ( 'public' == $group->status ) {

			check_ajax_referer( 'groups_join_group' );

			if ( !groups_join_group( $group->id ) ) {
				_e( 'Error joining group', 'buddypress' );
			} else {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="leave-group" rel="leave" title="' . __( 'Leave Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
			}

		} else if ( 'private' == $group->status ) {

			check_ajax_referer( 'groups_request_membership' );

			if ( !groups_send_membership_request( $bp->loggedin_user->id, $group->id ) ) {
				_e( 'Error requesting membership', 'buddypress' );
			} else {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="membership-requested" rel="membership-requested" title="' . __( 'Membership Requested', 'buddypress' ) . '" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Membership Requested', 'buddypress' ) . '</a>';
			}
		}

	} else {

		check_ajax_referer( 'groups_leave_group' );

		if ( !groups_leave_group( $group->id ) ) {
			_e( 'Error leaving group', 'buddypress' );
		} else {
			if ( 'public' == $group->status ) {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="join-group" rel="join" title="' . __( 'Join Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';
			} else if ( 'private' == $group->status ) {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="request-membership" rel="join" title="' . __( 'Request Membership', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'request-membership', 'groups_send_membership_request' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
			}
		}
	}
}
add_action( 'wp_ajax_joinleave_group', 'bp_dtheme_ajax_joinleave_group' );

function bp_dtheme_ajax_close_notice() {
	global $userdata;

	if ( !isset( $_POST['notice_id'] ) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem closing the notice.', 'buddypress') . '</p></div>';
	} else {
		$notice_ids = get_usermeta( $userdata->ID, 'closed_notices' );

		$notice_ids[] = (int) $_POST['notice_id'];

		update_usermeta( $userdata->ID, 'closed_notices', $notice_ids );
	}
}
add_action( 'wp_ajax_messages_close_notice', 'bp_dtheme_ajax_close_notice' );

function bp_dtheme_ajax_messages_send_reply() {
	global $bp;

	check_ajax_referer( 'messages_send_message' );

	$result = messages_new_message( array( 'thread_id' => $_REQUEST['thread_id'], 'content' => $_REQUEST['content'] ) );

	if ( $result ) { ?>
		<div class="message-box new-message">
			<div class="message-metadata">
				<?php do_action( 'bp_before_message_meta' ) ?>
				<?php echo bp_loggedin_user_avatar( 'type=thumb&width=30&height=30' ); ?>

				<strong><a href="<?php echo $bp->loggedin_user->domain ?>"><?php echo $bp->loggedin_user->fullname ?></a> <span class="activity"><?php printf( __( 'Sent %s ago', 'buddypress' ), bp_core_time_since( time() ) ) ?></span></strong>

				<?php do_action( 'bp_after_message_meta' ) ?>
			</div>

			<?php do_action( 'bp_before_message_content' ) ?>

			<div class="message-content">
				<?php echo stripslashes( apply_filters( 'bp_get_the_thread_message_content', $_REQUEST['content'] ) ) ?>
			</div>

			<?php do_action( 'bp_after_message_content' ) ?>

			<div class="clear"></div>
		</div>
	<?php
	} else {
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem sending that reply. Please try again.', 'buddypress' ) . '</p></div>';
	}
}
add_action( 'wp_ajax_messages_send_reply', 'bp_dtheme_ajax_messages_send_reply' );

function bp_dtheme_ajax_message_markunread() {
	global $bp;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem marking messages as unread.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_unread($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markunread', 'bp_dtheme_ajax_message_markunread' );

function bp_dtheme_ajax_message_markread() {
	global $bp;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem marking messages as read.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_read($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markread', 'bp_dtheme_ajax_message_markread' );

function bp_dtheme_ajax_messages_delete() {
	global $bp;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem deleting messages.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ )
			BP_Messages_Thread::delete($thread_ids[$i]);

		_e('Messages deleted.', 'buddypress');
	}
}
add_action( 'wp_ajax_messages_delete', 'bp_dtheme_ajax_messages_delete' );

function bp_dtheme_ajax_messages_autocomplete_results() {
	global $bp;

	$friends = false;

	// Get the friend ids based on the search terms
	if ( function_exists( 'friends_search_friends' ) )
		$friends = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $_GET['limit'], 1 );

	$friends = apply_filters( 'bp_friends_autocomplete_list', $friends, $_GET['q'], $_GET['limit'] );

	if ( $friends['friends'] ) {
		foreach ( (array)$friends['friends'] as $user_id ) {
			$ud = get_userdata($user_id);
			$username = $ud->user_login;
			echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) ) . ' &nbsp;' . bp_core_get_user_displayname( $user_id ) . ' (' . $username . ')
			';
		}
	}
}
add_action( 'wp_ajax_messages_autocomplete_results', 'bp_dtheme_ajax_messages_autocomplete_results' );

?>