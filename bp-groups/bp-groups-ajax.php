<?php
function groups_ajax_invite_user() {
	global $bp;

	check_ajax_referer( 'groups_invite_uninvite_user' );

	if ( !$_POST['friend_id'] || !$_POST['friend_action'] || !$_POST['group_id'] )
		return false;
	
	if ( !groups_is_user_admin( $bp->loggedin_user->id, $_POST['group_id'] ) )
		return false;
	
	if ( !friends_check_friendship( $bp->loggedin_user->id, $_POST['friend_id'] ) )
		return false;
	
	if ( 'invite' == $_POST['friend_action'] ) {
				
		if ( !groups_invite_user( $_POST['friend_id'], $_POST['group_id'] ) )
			return false;
		
		$user = new BP_Core_User( $_POST['friend_id'] );
		
		echo '<li id="uid-' . $user->id . '">';
		echo $user->avatar_thumb;
		echo '<h4>' . $user->user_link . '</h4>';
		echo '<span class="activity">' . $user->last_active . '</span>';
		echo '<div class="action">
				<a class="remove" href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->groups->slug . '/' . $_POST['group_id'] . '/invites/remove/' . $user->id, 'groups_invite_uninvite_user' ) . '" id="uid-' . $user->id . '">' . __( 'Remove Invite', 'buddypress' ) . '</a> 
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
add_action( 'wp_ajax_groups_invite_user', 'groups_ajax_invite_user' );

function groups_ajax_group_filter() {
	global $bp;

	check_ajax_referer( 'group-filter-box' );
	
	load_template( TEMPLATEPATH . '/groups/group-loop.php' );
}
add_action( 'wp_ajax_group_filter', 'groups_ajax_group_filter' );

function groups_ajax_widget_groups_list() {
	global $bp;

	check_ajax_referer('groups_widget_groups_list');

	switch ( $_POST['filter'] ) {
		case 'newest-groups':
			if ( !$groups = wp_cache_get( 'newest_groups', 'bp' ) ) {
				$groups = groups_get_newest( $_POST['max-groups'], 1 );
				wp_cache_set( 'newest_groups', $groups, 'bp' );
			}
		break;
		case 'recently-active-groups':
			if ( !$groups = wp_cache_get( 'active_groups', 'bp' ) ) {
				$groups = groups_get_active( $_POST['max-groups'], 1 );
				wp_cache_set( 'active_groups', $groups, 'bp' );
			}
		break;
		case 'popular-groups':
			if ( !$groups = wp_cache_get( 'popular_groups', 'bp' ) ) {
				$groups = groups_get_popular( $_POST['max-groups'], 1 );
				wp_cache_set( 'popular_groups', $groups, 'bp' );
			}
		break;
	}

	if ( $groups['groups'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $groups['groups'] as $group_id ) {
			if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_id->group_id, 'bp' ) ) {
				$group = new BP_Groups_Group( $group_id->group_id, false, false );
				wp_cache_set( 'groups_group_nouserdata_' . $group_id->group_id, $group, 'bp' );
			}	
		?>
			<li>
				<div class="item-avatar">
					<img src="<?php echo $group->avatar_thumb ?>" class="avatar" alt="<?php echo $group->name ?> Avatar" />
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php echo bp_group_permalink( $group, true ) ?>" title="<?php echo $group->name ?>"><?php echo $group->name ?></a></div>
					<div class="item-meta">
						<span class="activity">
							<?php 
							if ( 'newest-groups' == $_POST['filter'] ) {
								echo bp_core_get_last_activity( $group->date_created, __('created %s ago', 'buddypress') );
							} else if ( 'recently-active-groups' == $_POST['filter'] ) {
								echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __('active %s ago', 'buddypress') );
							} else if ( 'popular-groups' == $_POST['filter'] ) {
								if ( $group->total_member_count == 1 )
									echo $group->total_member_count . __(' member', 'buddypress');
								else
									echo $group->total_member_count . __(' members', 'buddypress');
							}
							?>
						</span>
					</div>	
				</div>
			</li>
			<?php	
		}
	} else {
		echo "-1[[SPLIT]]<li>" . __("No groups matched the current filter.", 'buddypress');
	}
}
add_action( 'wp_ajax_widget_groups_list', 'groups_ajax_widget_groups_list' );

function groups_ajax_member_list() {
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
				<?php bp_group_member_avatar() ?>
				<h5><?php bp_group_member_link() ?></h5>
				<span class="activity"><?php bp_group_member_joined_since() ?></span>
				
				<?php if ( function_exists( 'friends_install' ) ) : ?>
					<div class="action">
						<?php bp_add_friend_button( bp_group_member_id() ) ?>
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
	<input type="hidden" name="group_id" id="group_id" value="<?php echo $_REQUEST['group_id'] ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members', 'groups_ajax_member_list' );


function groups_ajax_member_admin_list() {
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
			<?php if ( bp_group_member_is_banned() ) : ?>
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
	<input type="hidden" name="group_id" id="group_id" value="<?php echo $_REQUEST['group_id'] ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members_admin', 'groups_ajax_member_admin_list' );

function bp_core_ajax_directory_groups() {
	global $bp;

	check_ajax_referer('directory_groups');

	load_template( TEMPLATEPATH . '/directories/groups/groups-loop.php' );
}
add_action( 'wp_ajax_directory_groups', 'bp_core_ajax_directory_groups' );

function groups_ajax_joinleave_group() {
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
				echo '<a id="group-' . $group->id . '" class="leave-group" rel="leave" title="' . __( 'Leave Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_group_permalink( $group, false ) . '/leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
			}	
					
		} else if ( 'private' == $group->status ) {
			
			check_ajax_referer( 'groups_request_membership' );
			
			if ( !groups_send_membership_request( $bp->loggedin_user->id, $group->id ) ) {
				_e( 'Error requesting membership', 'buddypress' );	
			} else {
				echo '<a id="group-' . $group->id . '" class="membership-requested" rel="membership-requested" title="' . __( 'Membership Requested', 'buddypress' ) . '" href="' . bp_group_permalink( $group, false ) . '">' . __( 'Membership Requested', 'buddypress' ) . '</a>';				
			}		
		}
		
	} else {

		check_ajax_referer( 'groups_leave_group' );
		
		if ( !groups_leave_group( $group->id ) ) {
			_e( 'Error leaving group', 'buddypress' );
		} else {
			if ( 'public' == $group->status ) {
				echo '<a id="group-' . $group->id . '" class="join-group" rel="join" title="' . __( 'Join Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_group_permalink( $group, false ) . '/join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';				
			} else if ( 'private' == $group->status ) {
				echo '<a id="group-' . $group->id . '" class="request-membership" rel="join" title="' . __( 'Request Membership', 'buddypress' ) . '" href="' . wp_nonce_url( bp_group_permalink( $group, false ) . '/request-membership', 'groups_send_membership_request' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
			}
		}
	}
}
add_action( 'wp_ajax_joinleave_group', 'groups_ajax_joinleave_group' );

?>