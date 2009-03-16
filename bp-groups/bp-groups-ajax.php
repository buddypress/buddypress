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
	
	load_template( get_template_directory() . '/groups/group-loop.php' );
}
add_action( 'wp_ajax_group_filter', 'groups_ajax_group_filter' );

function groups_ajax_widget_groups_list() {
	global $bp;

	check_ajax_referer('groups_widget_groups_list');

	switch ( $_POST['filter'] ) {
		case 'newest-groups':
			$groups = groups_get_newest( $_POST['max-groups'], 1 );
		break;
		case 'recently-active-groups':
			$groups = groups_get_active( $_POST['max-groups'], 1 );
		break;
		case 'popular-groups':
			$groups = groups_get_popular( $_POST['max-groups'], 1 );
		break;
	}

	if ( $groups['groups'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $groups['groups'] as $group ) {
			$group = new BP_Groups_Group( $group->group_id, false, false );
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
	
	<?php if ( bp_group_has_members( $_REQUEST['group_id'] ) ) : ?>
		
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
	
	<?php if ( bp_group_has_members( $_REQUEST['group_id'], $_REQUEST['num'] ) ) : ?>
	
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
	
	$pag_page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 10;
	
	if ( isset( $_POST['letter'] ) && $_POST['letter'] != '' ) {
		$groups = BP_Groups_Group::get_by_letter( $_POST['letter'], $pag_num, $pag_page );
	} else if ( isset ( $_POST['groups_search'] ) && $_POST['groups_search'] != '' ) {
		$groups = BP_Groups_Group::search_groups( $_POST['groups_search'], $pag_num, $pag_page );
	} else {
		$groups = BP_Groups_Group::get_active( $pag_num, $pag_page );
	}
	
	$pag_links = paginate_links( array(
		'base' => add_query_arg( 'page', '%#%' ),
		'format' => '',
		'total' => ceil( $groups['total'] / $pag_num ),
		'current' => $pag_page,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'mid_size' => 1
	));
	
	$from_num = intval( ( $pag_page - 1 ) * $pag_num ) + 1;
	$to_num = ( $from_num + 9 > $groups['total'] ) ? $groups['total'] : $from_num + 9; 

	if ( $groups['groups'] ) {
		echo '0[[SPLIT]]'; // return valid result.
		
		?>
		<div id="group-dir-count" class="pag-count">
			<?php echo sprintf( __( 'Viewing group %d to %d (%d total active groups)', 'buddypress' ), $from_num, $to_num, $groups['total'] ); ?> &nbsp;
			<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
		</div>
	
		<div class="pagination-links" id="group-dir-pag">
			<?php echo $pag_links ?>
		</div>
		
		<?php $counter = 0; ?>
		<ul id="groups-list" class="item-list">
		<?php foreach ( $groups['groups'] as $group ) : ?>
			
			<?php $alt = ( $counter % 2 == 1 ) ? ' class="alt"' : ''; ?>
			<?php $group = new BP_Groups_Group( $group->group_id, false, false ); ?>
			
			<li<?php echo $alt ?>>
				<div class="item-avatar">
					<img src="<?php echo $group->avatar_thumb ?>" class="avatar" alt="<?php echo $group->name ?> <?php _e( 'Avatar', 'buddypress' ) ?>" />
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><?php echo $group->name ?></a></div>
					<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) ?></span></div>
					<div class="item-meta desc"><?php echo bp_create_excerpt( $group->description ) ?></div>
				</div>
			
				<div class="action">
					<?php bp_group_join_button( $group ) ?>
					<div class="meta">
						<?php $member_count = groups_get_groupmeta( $group->id, 'total_member_count' ) ?>
						<?php echo ucwords($group->status) ?> <?php _e( 'Group', 'buddypress' ) ?> / 
						<?php if ( 1 == $member_count ) : ?>
							<?php printf( __( '%d member', 'buddypress' ), $member_count ) ?>
						<?php else : ?>
							<?php printf( __( '%d members', 'buddypress' ), $member_count ) ?>
						<?php endif; ?>
					</div>
				</div>
			
				<div class="clear"></div>
			</li>
			
			<?php $counter++ ?>
		<?php endforeach; ?>
		</ul>	
	<?php
	} else {
		echo "-1[[SPLIT]]<div id='message' class='error'><p>" . __("No groups matched the current filter.", 'buddypress') . '</p></div>';
	}
	
	if ( isset( $_POST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . $_POST['letter'] . '" name="selected_letter" />';
	}
	
	if ( isset( $_POST['groups_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . $_POST['groups_search'] . '" name="search_terms" />';
	}

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