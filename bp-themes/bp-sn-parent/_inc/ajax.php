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

function bp_dtheme_ajax_directory_blogs() {
	global $bp;

	check_ajax_referer('directory_blogs');

	locate_template( array( 'directories/blogs/blogs-loop.php' ), true );
}
add_action( 'wp_ajax_directory_blogs', 'bp_dtheme_ajax_directory_blogs' );

function bp_dtheme_ajax_directory_members() {
	check_ajax_referer('directory_members');

	locate_template( array( 'directories/members/members-loop.php' ), true );
}
add_action( 'wp_ajax_directory_members', 'bp_dtheme_ajax_directory_members' );

function bp_dtheme_ajax_friends_search() {
	global $bp;

	check_ajax_referer( 'friends_search' );

	locate_template( array( 'directories/friends/friends-loop.php' ), true );
}
add_action( 'wp_ajax_friends_search', 'bp_dtheme_ajax_friends_search' );

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
		echo '<span class="activity">' . attribute_escape( $user->last_active ) . '</span>';
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

function bp_dtheme_ajax_group_filter() {
	global $bp;

	check_ajax_referer( 'group-filter-box' );
	
	locate_template( array( 'groups/group-loop.php' ), true );
}
add_action( 'wp_ajax_group_filter', 'bp_dtheme_ajax_group_filter' );

function bp_dtheme_ajax_member_list() {
	global $bp;
	?>
	
	<?php if ( bp_group_has_members( 'group_id=' . $_REQUEST['group_id'] ) ) : ?>
		
		<?php if ( bp_group_member_needs_pagination() ) : ?>
			<div id="member-count" class="pag-count">
				<?php bp_group_member_pagination_count() ?>
			</div>

			<div id="member-pagination" class="pagination-links">
				<?php bp_group_member_pagination() ?>
			</div>
		<?php endif; ?>
		
		<ul id="member-list" class="item-list">
		<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
			<li>
				<?php bp_group_member_avatar_thumb() ?>
				<h5><?php bp_group_member_link() ?></h5>
				<span class="activity"><?php bp_group_member_joined_since() ?></span>
				
				<?php if ( function_exists( 'friends_install' ) ) : ?>
					<div class="action">
						<?php bp_add_friend_button( bp_get_group_member_id() ) ?>
					</div>
				<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no members.', 'buddypress' ) ?></p>
		</div>

	<?php endif; ?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $_REQUEST['group_id'] ); ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members', 'bp_dtheme_ajax_member_list' );

function bp_dtheme_ajax_member_admin_list() {
	global $bp;
	?>
	
	<?php if ( bp_group_has_members( 'group_id=' . $_REQUEST['group_id'] . '&per_page=' . $_REQUEST['num'] ) ) : ?>
	
		<?php if ( bp_group_member_needs_pagination() ) : ?>
			<div id="member-count" class="pag-count">
				<?php bp_group_member_pagination_count() ?>
			</div>

			<div id="member-admin-pagination" class="pagination-links">
				<?php bp_group_member_admin_pagination() ?>
			</div>
		<?php endif; ?>
	
		<ul id="members-list" class="item-list single-line">
		<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
			<?php if ( bp_get_group_member_is_banned() ) : ?>
				<li class="banned-user">
					<?php bp_group_member_avatar_mini() ?>

					<h5><?php bp_group_member_link() ?> <?php _e( '(banned)', 'buddypress' ) ?> <span class="small"> &mdash; <a href="<?php bp_group_member_unban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Remove Ban', 'buddypress' ) ?></a> </h5>
			<?php else : ?>
				<li>
					<?php bp_group_member_avatar_mini() ?>
					<h5><?php bp_group_member_link() ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ) ?></a> | <a href="<?php bp_group_member_promote_link() ?>" title="<?php _e( 'Promote this member', 'buddypress' ) ?>"><?php _e( 'Promote to Moderator', 'buddypress' ) ?></a></span></h5>

			<?php endif; ?>
				</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no members.', 'buddypress' ) ?></p>
		</div>

	<?php endif;?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $_REQUEST['group_id'] ); ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members_admin', 'bp_dtheme_ajax_member_admin_list' );

function bp_dtheme_ajax_directory_groups() {
	global $bp;

	check_ajax_referer('directory_groups');

	locate_template( array( 'directories/groups/groups-loop.php' ), true );
}
add_action( 'wp_ajax_directory_groups', 'bp_dtheme_ajax_directory_groups' );

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

function bp_dtheme_ajax_send_reply() {
	global $bp;
	
	check_ajax_referer( 'messages_send_message' );
	
	$result = messages_new_message( array( 'thread_id' => $_REQUEST['thread_id'], 'subject' => $_REQUEST['subject'], 'content' => $_REQUEST['content'] ) ); 

	if ( $result ) { ?>
			<div class="message-metadata">
				<?php echo bp_loggedin_user_avatar(); ?>
	
				<h3><a href="<?php echo $bp->loggedin_user->domain ?>"><?php echo $bp->loggedin_user->fullname ?></a></h3>
				<small><?php printf( __( 'Sent %s ago', 'buddypress' ), bp_core_time_since( time() ) ) ?></small>
			</div>
			
			<div class="message-content">
				<?php echo stripslashes( apply_filters( 'bp_get_message_content', $_REQUEST['content'] ) ) ?>
			</div>
		<?php
	} else {
		echo "-1[[split]]" . __( 'There was a problem sending that reply. Please try again.', 'buddypress' );
	}
}
add_action( 'wp_ajax_messages_send_reply', 'bp_dtheme_ajax_send_reply' );

function bp_dtheme_ajax_autocomplete_results() {
	global $bp;
	
	$friends = false;

	// Get the friend ids based on the search terms
	if ( function_exists( 'friends_search_friends' ) )
		$friends = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $_GET['limit'], 1 );
	
	$friends = apply_filters( 'bp_friends_autocomplete_list', $friends, $_GET['q'], $_GET['limit'] );

	if ( $friends['friends'] ) {
		foreach ( $friends['friends'] as $user_id ) {
			$ud = get_userdata($user_id);
			$username = $ud->user_login;
			echo bp_core_get_avatar( $user_id, 1, 15, 15 ) . ' ' . bp_core_get_user_displayname( $user_id ) . ' (' . $username . ')
			';
		}		
	}
}
add_action( 'wp_ajax_messages_autocomplete_results', 'bp_dtheme_ajax_autocomplete_results' );

function bp_dtheme_ajax_markunread() {
	global $bp;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __('There was a problem marking messages as unread.', 'buddypress');
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );
		
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_unread($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markunread', 'bp_dtheme_ajax_markunread' );

function bp_dtheme_ajax_markread() {
	global $bp;
	
	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __('There was a problem marking messages as read.', 'buddypress');
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_read($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markread', 'bp_dtheme_ajax_markread' );

function bp_dtheme_ajax_delete() {
	global $bp;

	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __( 'There was a problem deleting messages.', 'buddypress' );
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::delete($thread_ids[$i]);
		}
		
		_e('Messages deleted.', 'buddypress');
	}
}
add_action( 'wp_ajax_messages_delete', 'bp_dtheme_ajax_delete' );

function bp_dtheme_ajax_close_notice() {
	global $userdata;

	if ( !isset($_POST['notice_id']) ) {
		echo "-1[[split]]" . __('There was a problem closing the notice.', 'buddypress');
	} else {
		$notice_ids = get_usermeta( $userdata->ID, 'closed_notices' );
	
		$notice_ids[] = (int) $_POST['notice_id'];
		
		update_usermeta( $userdata->ID, 'closed_notices', $notice_ids );
	}
}
add_action( 'wp_ajax_messages_close_notice', 'bp_dtheme_ajax_close_notice' );

function bp_dtheme_ajax_get_wire_posts() {
	global $bp; ?>

	<?php if ( bp_has_wire_posts( 'item_id=' . $_POST['bp_wire_item_id'] . '&can_post=1' ) ) : ?>
		<div class="pagination">
			<div id="wire-count" class="pag-count">
				<?php bp_wire_pagination_count() ?> &nbsp;
				<span class="ajax-loader"></span>
			</div>
			
			<div id="wire-pagination" class="pagination-links">
				<?php bp_wire_pagination() ?>
			</div>
		</div>
		
		<ul id="wire-post-list" class="item-list">
		<?php $counter = 0; ?>
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<li<?php if ( $counter % 2 != 1 ) : ?> class="alt"<?php endif; ?>>
				<div class="wire-post-metadata">
					<?php bp_wire_post_author_avatar() ?>
					<?php _e( 'On', 'buddypress' ) ?> <?php bp_wire_post_date() ?> 
					<?php bp_wire_post_author_name() ?> <?php _e( 'said:', 'buddypress' ) ?>
					<?php bp_wire_delete_link() ?>
				</div>
				
				<div class="wire-post-content">
					<?php bp_wire_post_content() ?>
				</div>
			</li>
			<?php $counter++ ?>
		<?php endwhile; ?>
		</ul>
	
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'No wire posts were found', 'buddypress' )?></p>
		</div>

	<?php endif; ?>
	
	<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php echo attribute_escape( $_POST['bp_wire_item_id'] ) ?>" />
	<?php
}
add_action( 'wp_ajax_get_wire_posts', 'bp_dtheme_ajax_get_wire_posts' );

function bp_dtheme_ajax_show_form() {
	locate_template( array( 'status/post-form.php' ), true );
}
add_action( 'wp_ajax_status_show_form', 'bp_dtheme_ajax_show_form' );

function bp_dtheme_ajax_show_status() {
	$args = apply_filters( 'bp_status_ajax_show_status_args', $args );
 	bp_the_status( $args );
}
add_action( 'wp_ajax_status_show_status', 'bp_dtheme_ajax_show_status' );

function bp_dtheme_ajax_new_status() {
	global $bp;
	
	if ( !check_ajax_referer( 'bp_status_add_status' ) )
		return false;
		
	if ( bp_status_add_status( $bp->loggedin_user->id, $_POST['status-update-input'] ) )
		echo "1";
	else
		echo "-1";
}
add_action( 'wp_ajax_status_new_status', 'bp_dtheme_ajax_new_status' );

function bp_dtheme_ajax_clear_status( $new_text = false ) {
	global $bp;
	
	bp_status_clear_status( $bp->loggedin_user->id );
	
	$args = apply_filters( 'bp_status_ajax_show_status_args', $args );
 	bp_the_status( $args );
}
add_action( 'wp_ajax_status_clear_status', 'bp_dtheme_ajax_clear_status' );

?>