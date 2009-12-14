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

function bp_dtheme_members_filter() {
	global $bp;

	$type = $_POST['type'];
	$filter = $_POST['filter'];
	$page = $_POST['page'];
	$search_terms = $_POST['search_terms'];

	if ( __( 'Search anything...', 'buddypress' ) == $search_terms || 'false' == $search_terms )
		$search_terms = false;

	/* Build the querystring */

	/* Sort out type ordering */
	if ( 'active' != $filter && 'newest' != $filter && 'popular' != $filter && 'online' != $filter && 'alphabetical' != $filter )
		$filter = 'active';

	$bp->ajax_querystring = 'type=' . $filter . '&page=' . $page;

	if ( $search_terms )
		$bp->ajax_querystring .= '&search_terms=' . $search_terms;

	if ( !$type || ( 'all' != $type && 'friends' != $type ) )
		$type = 'all';

	if ( ( 'friends' == $type ) && !is_user_logged_in() )
		$filter = 'all';

	if ( 'friends' == $type || $bp->displayed_user->id ) {
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
		$bp->ajax_querystring .= '&user_id=' . $user_id;
	}

	$bp->is_directory = true;
	locate_template( array( 'members/members-loop.php' ), true );
}
add_action( 'wp_ajax_members_filter', 'bp_dtheme_members_filter' );

function bp_dtheme_groups_filter() {
	global $bp;

	$type = $_POST['type'];
	$filter = $_POST['filter'];
	$page = $_POST['page'];
	$search_terms = $_POST['search_terms'];

	if ( __( 'Search anything...', 'buddypress' ) == $search_terms || 'false' == $search_terms )
		$search_terms = false;

	/* Build the querystring */

	/* Sort out type ordering */
	if ( 'active' != $filter && 'newest' != $filter && 'popular' != $filter && 'online' != $filter && 'alphabetical' != $filter )
		$type = 'active';

	$bp->ajax_querystring = 'type=' . $filter . '&page=' . $page;

	if ( $search_terms )
		$bp->ajax_querystring .= '&search_terms=' . $search_terms;

	if ( !$type || ( 'all' != $type && 'mygroups' != $type ) )
		$type = 'all';

	if ( ( 'mygroups' == $type ) && !is_user_logged_in() )
		$type = 'all';

	if ( 'mygroups' == $type || $bp->displayed_user->id ) {
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
		$bp->ajax_querystring .= '&user_id=' . $user_id;
	}

	$bp->is_directory = true;
	locate_template( array( 'groups/groups-loop.php' ), true );
}
add_action( 'wp_ajax_groups_filter', 'bp_dtheme_groups_filter' );

function bp_dtheme_blogs_filter() {
	global $bp;

	$type = $_POST['type'];
	$filter = $_POST['filter'];
	$page = $_POST['page'];
	$search_terms = $_POST['search_terms'];

	if ( __( 'Search anything...', 'buddypress' ) == $search_terms || 'false' == $search_terms )
		$search_terms = false;

	/* Build the querystring */

	/* Sort out type ordering */
	if ( 'active' != $filter && 'newest' != $filter && 'alphabetical' != $filter )
		$type = 'active';

	$bp->ajax_querystring = 'type=' . $filter . '&page=' . $page;

	if ( $search_terms )
		$bp->ajax_querystring .= '&search_terms=' . $search_terms;

	if ( !$type || ( 'all' != $type && 'myblogs' != $type ) )
		$type = 'all';

	if ( ( 'myblogs' == $type ) && !is_user_logged_in() )
		$type = 'all';

	if ( 'myblogs' == $type || $bp->displayed_user->id ) {
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
		$bp->ajax_querystring .= '&user_id=' . $user_id;
	}

	$bp->is_directory = true;
	locate_template( array( 'blogs/blogs-loop.php' ), true );
}
add_action( 'wp_ajax_blogs_filter', 'bp_dtheme_blogs_filter' );

function bp_dtheme_forums_filter() {
	global $bp;

	$type = $_POST['type'];
	$filter = $_POST['filter'];
	$page = $_POST['page'];
	$search_terms = $_POST['search_terms'];

	if ( __( 'Search anything...', 'buddypress' ) == $search_terms || 'false' == $search_terms )
		$search_terms = false;

	/* Build the querystring */

	/* Sort out type ordering */
	if ( 'active' != $filter && 'newest' != $filter && 'alphabetical' != $filter )
		$type = 'active';

	$bp->ajax_querystring = 'type=' . $filter . '&page=' . $page;

	if ( $search_terms )
		$bp->ajax_querystring .= '&search_terms=' . $search_terms;

	if ( !$type || ( 'all' != $type && 'myblogs' != $type ) )
		$type = 'all';

	if ( ( 'myblogs' == $type ) && !is_user_logged_in() )
		$type = 'all';

	if ( 'my' == $type || $bp->displayed_user->id ) {
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;
		$bp->ajax_querystring .= '&user_id=' . $user_id;
	}

	$bp->is_directory = true;
	locate_template( array( 'forums/topics-loop.php' ), true );
}
add_action( 'wp_ajax_forums_filter', 'bp_dtheme_forums_filter' );


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

	if ( !(int)$_POST['group'] ) {
		$item_id = $bp->loggedin_user->id;
		$component = $bp->profile->id;
	} else {
		$item_id = $_POST['group'];
		$component = $bp->groups->id;
	}

	if ( $bp->profile->id == $component ) {
		/* Record this on the poster's activity screen */
		$from_user_link = bp_core_get_userlink($bp->loggedin_user->id);
		$activity_content = sprintf( __('%s posted an update:', 'buddypress'), $from_user_link ) . ' <span class="time-since">%s</span></p><p>
		';
		$primary_link = bp_core_get_userlink( $wire_post->user_id, false, true );
		$activity_content .= '<div class="activity-inner">' . $_POST['content'] . '</div>';

		/* Now write the values */
		$activity_id = xprofile_record_activity( array(
			'user_id' => $bp->loggedin_user->id,
			'content' => apply_filters( 'xprofile_activity_new_wire_post', $activity_content, &$wire_post ),
			'primary_link' => apply_filters( 'xprofile_activity_new_wire_post_primary_link', $primary_link ),
			'component_action' => 'new_wire_post'
		) );

		/* Add this update to the "latest update" usermeta so it can be fetched anywhere. */
		update_usermeta( $bp->loggedin_user->id, 'bp_latest_update', array( 'id' => $activity_id, 'content' => wp_filter_kses( $_POST['content'] ) ) );

		do_action( 'xprofile_new_wire_post', &$wire_post );
	} else {
		$bp->groups->current_group = new BP_Groups_Group( $item_id );

		/* Record this in activity streams */
		$activity_content = sprintf( __( '%s posted an update in the group %s:', 'buddypress'), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' ) . ' <span class="time-since">%s</span>
		';
		$activity_content .= '<div class="activity-inner">' . $_POST['content'] . '</div>';

		$activity_id = groups_record_activity( array(
			'content' => apply_filters( 'groups_activity_new_wire_post', $activity_content ),
			'primary_link' => apply_filters( 'groups_activity_new_wire_post_primary_link', bp_get_group_permalink( $bp->groups->current_group ) ),
			'component_action' => 'new_wire_post',
			'item_id' => $item_id
		) );

		do_action( 'groups_new_wire_post', $item_id, $wire_post->id );
	}

	if ( !$activity_id ) {
		echo '-1<div class="error"><p>' . __( 'There was a problem posting your update, please try again.', 'buddypress' ) . '</p></div>';
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

	/* Insert the "user posted a new activity comment header text" */
	$comment_header = '<div class="comment-header">' . sprintf( __( '%s posted a new activity comment:', 'buddypress' ), bp_core_get_userlink( $bp->loggedin_user->id ) ) . ' <span class="time-since">%s</span></div> ';

	/* Insert the activity comment */
	$comment_id = bp_activity_add( array(
		'content' => apply_filters( 'bp_activity_comment_content', $comment_header . '<div class="activity-inner">' . $_POST['content'] . '</div>' ),
		'primary_link' => '',
		'component_name' => $bp->activity->id,
		'component_action' => 'activity_comment',
		'user_id' => $bp->loggedin_user->id,
		'item_id' => $_POST['form_id'],
		'secondary_item_id' => $_POST['comment_id']
	) );

	if ( !$comment_id ) {
		echo '-1<div id="message" class="error"><p>' . __( 'There was an error posting that reply, please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}
?>
	<li id="acomment-<?php echo $comment_id ?>">
		<div class="acomment-avatar">
			<?php echo bp_core_fetch_avatar( array( 'item_id' => $bp->loggedin_user->id, 'width' => 25, 'height' => 25 ) ) ?>
		</div>

		<div class="acomment-meta">
			<?php echo bp_core_get_userlink( $bp->loggedin_user->id ) ?> &middot; <?php echo bp_core_time_since( time() ) ?> &middot;
			<a class="acomment-reply" href="#acomment-<?php echo $comment_id ?>" id="acomment-reply-<?php echo attribute_escape( $_POST['form_id'] ) ?>"><?php _e( 'Reply', 'buddypress' ) ?></a>
			<?php if ( is_site_admin() || $bp->loggedin_user->id == $comment->user_id ) : ?>
				 &middot; <a href="<?php echo wp_nonce_url( $bp->activity->id . '/delete/?cid=' . $comment_id, 'delete_activity_comment' ) ?>" class="delete acomment-delete"><?php _e( 'Delete', 'buddypress' ) ?></a>
			<?php endif; ?>
		</div>

		<div class="acomment-content">
			<?php echo apply_filters( 'bp_get_activity_content', $_POST['content'] ) ?>
		</div>
	</li>
<?php
}
add_action( 'wp_ajax_new_activity_comment', 'bp_dtheme_new_activity_comment' );

function bp_dtheme_delete_activity_comment() {
	/* Check the nonce */
	check_admin_referer( 'delete_activity_comment' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	if ( empty( $_POST['comment_id'] ) || !is_numeric( $_POST['comment_id'] ) || !bp_activity_delete_by_activity_id( $_POST['comment_id'] ) ) {
		echo '-1<div class="error"><p>' . __( 'There was a problem deleting that comment. Please try again.', 'buddypress' ) . '</p></div>';
		return false;
	}

	return true;
}
add_action( 'wp_ajax_delete_activity_comment', 'bp_dtheme_delete_activity_comment' );

function bp_dtheme_activity_loop( $type = 'all', $filter = false, $query_string = false, $per_page = 20, $page = 1 ) {
	global $bp;

	if ( !$query_string ) {

		/* If we are on a profile page we only want to show that users activity */
		if ( $bp->displayed_user->id ) {
			$query_string = 'user_id=' . $bp->displayed_user->id;
		} else {
			/* Set a valid type */
			if ( !$type || ( 'all' != $type && 'friends' != $type && 'groups' != $type ) )
				$type = 'all';

			if ( ( 'friends' == $type || 'groups' == $type ) && !is_user_logged_in() )
				$type = 'all';

			switch( $type ) {
				case 'friends':
					$friend_ids = implode( ',', friends_get_friend_user_ids( $bp->loggedin_user->id ) );
					$query_string = 'user_id=' . $friend_ids;
					break;
				case 'groups':
					$groups = groups_get_user_groups( $bp->loggedin_user->id );
					$group_ids = implode( ',', $groups['groups'] );
					$query_string = 'object=groups&primary_id=' . $group_ids;
					break;
			}
		}

		/* Build the filter */
		if ( $filter && $filter != '-1' )
			$query_string .= '&action=' . $filter;

		/* If we are viewing a group then filter the activity just for this group */
		if ( $bp->groups->current_group )
			$query_string .= '&object=' . $bp->groups->id . '&primary_id=' . $bp->groups->current_group->id;

		/* Add the per_page param */
		$query_string .= '&per_page=' . $per_page;

		/* Add the comments param */
		if ( $bp->displayed_user->id )
			$query_string .= '&display_comments=stream';
		else
			$query_string .= '&display_comments=threaded';
	}

	/* Add the new page param */
	$args = explode( '&', trim( $query_string ) );
	foreach( $args as $arg ) {
		if ( false === strpos( $arg, 'page' ) )
			$new_args[] = $arg;
	}
	$query_string = implode( '&', $new_args ) . '&page=' . $page;

	$bp->ajax_querystring = $query_string;
	$result['query_string'] = $bp->ajax_querystring;

	/* Buffer the loop in the template to a var for JS to spit out. */
	ob_start();
	locate_template( array( 'activity/activity-loop.php' ), true );
	$result['contents'] = ob_get_contents();
	ob_end_clean();

	echo json_encode( $result );
}

function bp_dtheme_ajax_widget_filter() {
	bp_dtheme_activity_loop( $_POST['type'], $_POST['filter'] );
}
add_action( 'wp_ajax_activity_widget_filter', 'bp_dtheme_ajax_widget_filter' );

function bp_dtheme_ajax_load_older_updates() {
	bp_dtheme_activity_loop( false, false, $_POST['query_string'], 20, $_POST['page'] );
}
add_action( 'wp_ajax_activity_get_older_updates', 'bp_dtheme_ajax_load_older_updates' );

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
		echo '<span class="activity">' . attribute_escape( $user->last_activity ) . '</span>';
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
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="leave-group" rel="leave" title="' . __( 'Leave Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
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
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="join-group" rel="join" title="' . __( 'Join Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';
			} else if ( 'private' == $group->status ) {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="request-membership" rel="join" title="' . __( 'Request Membership', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/request-membership', 'groups_send_membership_request' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
			}
		}
	}
}
add_action( 'wp_ajax_joinleave_group', 'bp_dtheme_ajax_joinleave_group' );


?>