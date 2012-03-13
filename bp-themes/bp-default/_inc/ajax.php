<?php
/***
 * AJAX Functions
 *
 * All of these functions enhance the responsiveness of the user interface in the default
 * theme by adding AJAX functionality.
 */

/***
 * This function looks scarier than it actually is. :)
 * Each object loop (activity/members/groups/blogs/forums) contains default parameters to
 * show specific information based on the page we are currently looking at.
 * The following function will take into account any cookies set in the JS and allow us
 * to override the parameters sent. That way we can change the results returned without reloading the page.
 * By using cookies we can also make sure that user settings are retained across page loads.
 */
function bp_dtheme_ajax_querystring( $query_string, $object ) {
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

	$object_search_text = bp_get_search_default_text( $object );
 	if ( !empty( $_POST['search_terms'] ) && $object_search_text != $_POST['search_terms'] && 'false' != $_POST['search_terms'] && 'undefined' != $_POST['search_terms'] )
		$qs[] = 'search_terms=' . $_POST['search_terms'];

	/* Now pass the querystring to override default values. */
	$query_string = empty( $qs ) ? '' : join( '&', (array)$qs );

	$object_filter = '';
	if ( isset( $_BP_COOKIE['bp-' . $object . '-filter'] ) )
		$object_filter = $_BP_COOKIE['bp-' . $object . '-filter'];

	$object_scope = '';
	if ( isset( $_BP_COOKIE['bp-' . $object . '-scope'] ) )
		$object_scope = $_BP_COOKIE['bp-' . $object . '-scope'];

	$object_page = '';
	if ( isset( $_BP_COOKIE['bp-' . $object . '-page'] ) )
		$object_page = $_BP_COOKIE['bp-' . $object . '-page'];

	$object_search_terms = '';
	if ( isset( $_BP_COOKIE['bp-' . $object . '-search-terms'] ) )
		$object_search_terms = $_BP_COOKIE['bp-' . $object . '-search-terms'];

	$object_extras = '';
	if ( isset( $_BP_COOKIE['bp-' . $object . '-extras'] ) )
		$object_extras = $_BP_COOKIE['bp-' . $object . '-extras'];

	return apply_filters( 'bp_dtheme_ajax_querystring', $query_string, $object, $object_filter, $object_scope, $object_page, $object_search_terms, $object_extras );
}
add_filter( 'bp_ajax_querystring', 'bp_dtheme_ajax_querystring', 10, 2 );

/* This function will simply load the template loop for the current object. On an AJAX request */
function bp_dtheme_object_template_loader() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

 	/**
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( !bp_current_action() )
		bp_update_is_directory( true, bp_current_component() );

	// Sanitize the post object
	$object = esc_attr( $_POST['object'] );

	// Locate the object template
	locate_template( array( "$object/$object-loop.php" ), true );
}
add_action( 'wp_ajax_members_filter', 'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_groups_filter',  'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_blogs_filter',   'bp_dtheme_object_template_loader' );
add_action( 'wp_ajax_forums_filter',  'bp_dtheme_object_template_loader' );

// This function will load the activity loop template when activity is requested via AJAX
function bp_dtheme_activity_template_loader() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	$scope = '';
	if ( !empty( $_POST['scope'] ) )
		$scope = $_POST['scope'];

	// We need to calculate and return the feed URL for each scope
	switch ( $scope ) {
		case 'friends':
			$feed_url = $bp->loggedin_user->domain . bp_get_activity_slug() . '/friends/feed/';
			break;
		case 'groups':
			$feed_url = $bp->loggedin_user->domain . bp_get_activity_slug() . '/groups/feed/';
			break;
		case 'favorites':
			$feed_url = $bp->loggedin_user->domain . bp_get_activity_slug() . '/favorites/feed/';
			break;
		case 'mentions':
			$feed_url = $bp->loggedin_user->domain . bp_get_activity_slug() . '/mentions/feed/';
			bp_activity_clear_new_mentions( $bp->loggedin_user->id );
			break;
		default:
			$feed_url = home_url( bp_get_activity_root_slug() . '/feed/' );
			break;
	}

	/* Buffer the loop in the template to a var for JS to spit out. */
	ob_start();
	locate_template( array( 'activity/activity-loop.php' ), true );
	$result = array();
	$result['contents'] = ob_get_contents();
	$result['feed_url'] = apply_filters( 'bp_dtheme_activity_feed_url', $feed_url, $scope );
	ob_end_clean();

	echo json_encode( $result );
}
add_action( 'wp_ajax_activity_widget_filter', 'bp_dtheme_activity_template_loader' );
add_action( 'wp_ajax_activity_get_older_updates', 'bp_dtheme_activity_template_loader' );

/* AJAX update posting */
function bp_dtheme_post_update() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'post_update', '_wpnonce_post_update' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	if ( empty( $_POST['content'] ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'Please enter some content to post.', 'buddypress' ) . '</p></div>';
		return false;
	}

	$activity_id = 0;
	if ( empty( $_POST['object'] ) && bp_is_active( 'activity' ) ) {
		$activity_id = bp_activity_post_update( array( 'content' => $_POST['content'] ) );

	} elseif ( $_POST['object'] == 'groups' ) {
		if ( !empty( $_POST['item_id'] ) && bp_is_active( 'groups' ) )
			$activity_id = groups_post_update( array( 'content' => $_POST['content'], 'group_id' => $_POST['item_id'] ) );

	} else {
		$activity_id = apply_filters( 'bp_activity_custom_update', $_POST['object'], $_POST['item_id'], $_POST['content'] );
	}

	if ( empty( $activity_id ) ) {
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

/* AJAX activity comment posting */
function bp_dtheme_new_activity_comment() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
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
		'activity_id' => $_POST['form_id'],
		'content'     => $_POST['content'],
		'parent_id'   => $_POST['comment_id']
	) );

	if ( !$comment_id ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was an error posting that reply, please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	global $activities_template;

	// Load the new activity item into the $activities_template global
	bp_has_activities( 'display_comments=stream&include=' . $comment_id );

	// Swap the current comment with the activity item we just loaded
	$activities_template->activity->id              = $activities_template->activities[0]->item_id;
	$activities_template->activity->current_comment = $activities_template->activities[0];

	$template = locate_template( 'activity/comment.php', false, false );

	// Backward compatibility. In older versions of BP, the markup was
	// generated in the PHP instead of a template. This ensures that
	// older themes (which are not children of bp-default and won't
	// have the new template) will still work.
	if ( empty( $template ) )
		$template = BP_PLUGIN_DIR . '/bp-themes/bp-default/activity/comment.php';

	load_template( $template, false );

	unset( $activities_template );
}
add_action( 'wp_ajax_new_activity_comment', 'bp_dtheme_new_activity_comment' );

/* AJAX delete an activity */
function bp_dtheme_delete_activity() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'bp_activity_delete_link' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	if ( empty( $_POST['id'] ) || !is_numeric( $_POST['id'] ) ) {
		echo '-1';
		return false;
	}

	$activity = new BP_Activity_Activity( (int) $_POST['id'] );

	// Check access
	if ( empty( $activity->user_id ) || !bp_activity_user_can_delete( $activity ) ) {
		echo '-1';
		return false;
	}

	// Call the action before the delete so plugins can still fetch information about it
	do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

	if ( !bp_activity_delete( array( 'id' => $activity->id, 'user_id' => $activity->user_id ) ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was a problem when deleting. Please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );

	return true;
}
add_action( 'wp_ajax_delete_activity', 'bp_dtheme_delete_activity' );

/* AJAX delete an activity comment */
function bp_dtheme_delete_activity_comment() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	/* Check the nonce */
	check_admin_referer( 'bp_activity_delete_link' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	$comment = new BP_Activity_Activity( $_POST['id'] );

	/* Check access */
	if ( !is_super_admin() && $comment->user_id != $bp->loggedin_user->id )
		return false;

	if ( empty( $_POST['id'] ) || !is_numeric( $_POST['id'] ) )
		return false;

	/* Call the action before the delete so plugins can still fetch information about it */
	do_action( 'bp_activity_before_action_delete_activity', $_POST['id'], $comment->user_id );

	if ( !bp_activity_delete_comment( $comment->item_id, $comment->id ) ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was a problem when deleting. Please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	do_action( 'bp_activity_action_delete_activity', $_POST['id'], $comment->user_id );

	return true;
}
add_action( 'wp_ajax_delete_activity_comment', 'bp_dtheme_delete_activity_comment' );

/* AJAX mark an activity as a favorite */
function bp_dtheme_mark_activity_favorite() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	bp_activity_add_user_favorite( $_POST['id'] );
	_e( 'Remove Favorite', 'buddypress' );
}
add_action( 'wp_ajax_activity_mark_fav', 'bp_dtheme_mark_activity_favorite' );

/* AJAX mark an activity as not a favorite */
function bp_dtheme_unmark_activity_favorite() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	bp_activity_remove_user_favorite( $_POST['id'] );
	_e( 'Favorite', 'buddypress' );
}
add_action( 'wp_ajax_activity_mark_unfav', 'bp_dtheme_unmark_activity_favorite' );

/**
 * AJAX handler for Read More link on long activity items
 *
 * @package BuddyPress
 * @since 1.5
 */
function bp_dtheme_get_single_activity_content() {
	$activity_array = bp_activity_get_specific( array(
		'activity_ids'     => $_POST['activity_id'],
		'display_comments' => 'stream'
	) );

	$activity = !empty( $activity_array['activities'][0] ) ? $activity_array['activities'][0] : false;

	if ( !$activity )
		exit(); // todo: error?

	do_action_ref_array( 'bp_dtheme_get_single_activity_content', array( &$activity ) );

	// Activity content retrieved through AJAX should run through normal filters, but not be truncated
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
	$content = apply_filters( 'bp_get_activity_content_body', $activity->content );

	echo $content;
	exit();
}
add_action( 'wp_ajax_get_single_activity_content', 'bp_dtheme_get_single_activity_content' );

/* AJAX invite a friend to a group functionality */
function bp_dtheme_ajax_invite_user() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	check_ajax_referer( 'groups_invite_uninvite_user' );

	if ( !$_POST['friend_id'] || !$_POST['friend_action'] || !$_POST['group_id'] )
		return false;

	if ( !bp_groups_user_can_send_invites( $_POST['group_id'] ) )
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
				<a class="button remove" href="' . wp_nonce_url( $bp->loggedin_user->domain . bp_get_groups_slug() . '/' . $_POST['group_id'] . '/invites/remove/' . $user->id, 'groups_invite_uninvite_user' ) . '" id="uid-' . esc_attr( $user->id ) . '">' . __( 'Remove Invite', 'buddypress' ) . '</a>
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

/* AJAX add/remove a user as a friend when clicking the button */
function bp_dtheme_ajax_addremove_friend() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( 'is_friend' == BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {

		check_ajax_referer('friends_remove_friend');

		if ( !friends_remove_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
			echo __("Friendship could not be canceled.", 'buddypress');
		} else {
			echo '<a id="friend-' . $_POST['fid'] . '" class="add" rel="add" title="' . __( 'Add Friend', 'buddypress' ) . '" href="' . wp_nonce_url( $bp->loggedin_user->domain . bp_get_friends_slug() . '/add-friend/' . $_POST['fid'], 'friends_add_friend' ) . '">' . __( 'Add Friend', 'buddypress' ) . '</a>';
		}

	} else if ( 'not_friends' == BP_Friends_Friendship::check_is_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {

		check_ajax_referer('friends_add_friend');

		if ( !friends_add_friend( $bp->loggedin_user->id, $_POST['fid'] ) ) {
			echo __("Friendship could not be requested.", 'buddypress');
		} else {
			echo '<a href="' . $bp->loggedin_user->domain . bp_get_friends_slug() . '/requests" class="requested">' . __( 'Friendship Requested', 'buddypress' ) . '</a>';
		}
	} else {
		echo __( 'Request Pending', 'buddypress' );
	}

	return false;
}
add_action( 'wp_ajax_addremove_friend', 'bp_dtheme_ajax_addremove_friend' );

/* AJAX accept a user as a friend when clicking the "accept" button */
function bp_dtheme_ajax_accept_friendship() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	check_admin_referer( 'friends_accept_friendship' );

	if ( !friends_accept_friendship( $_POST['id'] ) )
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem accepting that request. Please try again.', 'buddypress' ) . '</p></div>';

	return true;
}
add_action( 'wp_ajax_accept_friendship', 'bp_dtheme_ajax_accept_friendship' );

/* AJAX reject a user as a friend when clicking the "reject" button */
function bp_dtheme_ajax_reject_friendship() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	check_admin_referer( 'friends_reject_friendship' );

	if ( !friends_reject_friendship( $_POST['id'] ) )
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem rejecting that request. Please try again.', 'buddypress' ) . '</p></div>';

	return true;
}
add_action( 'wp_ajax_reject_friendship', 'bp_dtheme_ajax_reject_friendship' );

/* AJAX join or leave a group when clicking the "join/leave" button */
function bp_dtheme_ajax_joinleave_group() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( groups_is_user_banned( $bp->loggedin_user->id, $_POST['gid'] ) )
		return false;

	if ( !$group = new BP_Groups_Group( $_POST['gid'], false, false ) )
		return false;

	if ( !groups_is_user_member( $bp->loggedin_user->id, $group->id ) ) {

		if ( 'public' == $group->status ) {

			check_ajax_referer( 'groups_join_group' );

			if ( !groups_join_group( $group->id ) ) {
				_e( 'Error joining group', 'buddypress' );
			} else {
				echo '<a id="group-' . esc_attr( $group->id ) . '" class="leave-group" rel="leave" title="' . __( 'Leave Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
			}

		} else if ( 'private' == $group->status ) {

			check_ajax_referer( 'groups_request_membership' );

			if ( !groups_send_membership_request( $bp->loggedin_user->id, $group->id ) ) {
				_e( 'Error requesting membership', 'buddypress' );
			} else {
				echo '<a id="group-' . esc_attr( $group->id ) . '" class="membership-requested" rel="membership-requested" title="' . __( 'Membership Requested', 'buddypress' ) . '" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Membership Requested', 'buddypress' ) . '</a>';
			}
		}

	} else {

		check_ajax_referer( 'groups_leave_group' );

		if ( !groups_leave_group( $group->id ) ) {
			_e( 'Error leaving group', 'buddypress' );
		} else {
			if ( 'public' == $group->status ) {
				echo '<a id="group-' . esc_attr( $group->id ) . '" class="join-group" rel="join" title="' . __( 'Join Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';
			} else if ( 'private' == $group->status ) {
				echo '<a id="group-' . esc_attr( $group->id ) . '" class="request-membership" rel="join" title="' . __( 'Request Membership', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'request-membership', 'groups_send_membership_request' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
			}
		}
	}
}
add_action( 'wp_ajax_joinleave_group', 'bp_dtheme_ajax_joinleave_group' );

/* AJAX close and keep closed site wide notices from an admin in the sidebar */
function bp_dtheme_ajax_close_notice() {
	global $userdata;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( !isset( $_POST['notice_id'] ) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem closing the notice.', 'buddypress') . '</p></div>';
	} else {
		$notice_ids = bp_get_user_meta( $userdata->ID, 'closed_notices', true );

		$notice_ids[] = (int) $_POST['notice_id'];

		bp_update_user_meta( $userdata->ID, 'closed_notices', $notice_ids );
	}
}
add_action( 'wp_ajax_messages_close_notice', 'bp_dtheme_ajax_close_notice' );

/* AJAX send a private message reply to a thread */
function bp_dtheme_ajax_messages_send_reply() {
	global $bp;

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	check_ajax_referer( 'messages_send_message' );

	$result = messages_new_message( array( 'thread_id' => $_REQUEST['thread_id'], 'content' => $_REQUEST['content'] ) );

	if ( $result ) { ?>
		<div class="message-box new-message">
			<div class="message-metadata">
				<?php do_action( 'bp_before_message_meta' ) ?>
				<?php echo bp_loggedin_user_avatar( 'type=thumb&width=30&height=30' ); ?>

				<strong><a href="<?php echo $bp->loggedin_user->domain ?>"><?php echo $bp->loggedin_user->fullname ?></a> <span class="activity"><?php printf( __( 'Sent %s', 'buddypress' ), bp_core_time_since( bp_core_current_time() ) ) ?></span></strong>

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

/* AJAX mark a private message as unread in your inbox */
function bp_dtheme_ajax_message_markunread() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem marking messages as unread.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0, $count = count( $thread_ids ); $i < $count; ++$i ) {
			BP_Messages_Thread::mark_as_unread($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markunread', 'bp_dtheme_ajax_message_markunread' );

/* AJAX mark a private message as read in your inbox */
function bp_dtheme_ajax_message_markread() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __('There was a problem marking messages as read.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0, $count = count( $thread_ids ); $i < $count; ++$i ) {
			BP_Messages_Thread::mark_as_read($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markread', 'bp_dtheme_ajax_message_markread' );

/* AJAX delete a private message or array of messages in your inbox */
function bp_dtheme_ajax_messages_delete() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem deleting messages.', 'buddypress' ) . '</p></div>';
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0, $count = count( $thread_ids ); $i < $count; ++$i )
			BP_Messages_Thread::delete($thread_ids[$i]);

		_e( 'Messages deleted.', 'buddypress' );
	}
}
add_action( 'wp_ajax_messages_delete', 'bp_dtheme_ajax_messages_delete' );

/**
 * bp_dtheme_ajax_messages_autocomplete_results()
 *
 * AJAX handler for autocomplete. Displays friends only, unless BP_MESSAGES_AUTOCOMPLETE_ALL is defined
 *
 * @global object object $bp Global BuddyPress settings object
 * @return none
 */
function bp_dtheme_ajax_messages_autocomplete_results() {
	global $bp;

	// Include everyone in the autocomplete, or just friends?
	if ( $bp->messages->slug == $bp->current_component )
		$autocomplete_all = $bp->messages->autocomplete_all;

	$pag_page = 1;
	$limit    = !empty( $_GET['limit'] ) ? $_GET['limit'] : apply_filters( 'bp_autocomplete_max_results', 10 );

	// Get the user ids based on the search terms
	if ( !empty( $autocomplete_all ) ) {
		$users = BP_Core_User::search_users( $_GET['q'], $limit, $pag_page );

		if ( !empty( $users['users'] ) ) {
			// Build an array with the correct format
			$user_ids = array();
			foreach( $users['users'] as $user ) {
				if ( $user->id != $bp->loggedin_user->id )
					$user_ids[] = $user->id;
			}

			$user_ids = apply_filters( 'bp_core_autocomplete_ids', $user_ids, $_GET['q'], $limit );
		}
	} else {
		if ( bp_is_active( 'friends' ) ) {
			$users = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $limit, 1 );

			// Keeping the bp_friends_autocomplete_list filter for backward compatibility
			$users = apply_filters( 'bp_friends_autocomplete_list', $users, $_GET['q'], $limit );

			if ( !empty( $users['friends'] ) )
				$user_ids = apply_filters( 'bp_friends_autocomplete_ids', $users['friends'], $_GET['q'], $limit );
		}
	}

	if ( !empty( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {
			$ud = get_userdata( $user_id );
			if ( !$ud )
				continue;

			if ( bp_is_username_compatibility_mode() )
				$username = $ud->user_login;
			else
				$username = $ud->user_nicename;

			echo '<span id="link-' . $username . '" href="' . bp_core_get_user_domain( $user_id ) . '"></span>' . bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) ) . ' &nbsp;' . bp_core_get_user_displayname( $user_id ) . ' (' . $username . ')
			';
		}
	}
}
add_action( 'wp_ajax_messages_autocomplete_results', 'bp_dtheme_ajax_messages_autocomplete_results' );

?>