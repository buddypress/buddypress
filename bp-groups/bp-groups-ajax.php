<?php
function groups_ajax_invite_user() {
	global $bp;

	check_ajax_referer('invite_user');

	if ( !$_POST['friend_id'] || !$_POST['friend_action'] || !$_POST['group_id'] )
		return false;
	
	if ( !groups_is_user_admin( $bp['loggedin_userid'], $_POST['group_id'] ) )
		return false;
	
	if ( !friends_check_friendship( $bp['loggedin_userid'], $_POST['friend_id'] ) )
		return false;
	
	if ( $_POST['friend_action'] == 'invite' ) {
		if ( !groups_invite_user( $_POST['friend_id'], $_POST['group_id'] ) )
			return false;
		
		$user = new BP_Core_User( $_POST['friend_id'] );
		
		echo '<li id="uid-' . $user->id . '">';
		echo $user->avatar_thumb;
		echo '<h4>' . $user->user_link . '</h4>';
		echo '<span class="activity">active ' . $user->last_active . ' ago</span>';
		echo '<div class="action">
				<a class="remove" href="' . $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $_POST['group_id'] . '/invites/remove/' . $user->id . '" id="uid-' . $user->id . '">Remove Invite</a> 
			  </div>';
		echo '</li>';
		
	} else if ( $_POST['friend_action'] == 'uninvite' ) {
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

	check_ajax_referer('group-filter-box');
	
	load_template( get_template_directory() . '/groups/group-loop.php' );
}
add_action( 'wp_ajax_group_filter', 'groups_ajax_group_filter' );

function groups_ajax_group_finder_search() {
	global $bp;

	check_ajax_referer('groupfinder-search-box');

	load_template( get_template_directory() . '/groups/group-loop.php' );
}
add_action( 'wp_ajax_group_finder_search', 'groups_ajax_group_finder_search' );


function groups_ajax_widget_groups_list() {
	global $bp;

	check_ajax_referer('groups_widget_groups_list');

	switch ( $_POST['filter'] ) {
		case 'newest-groups':
			$groups = groups_get_newest($_POST['max-groups']);
		break;
		case 'recently-active-groups':
			$groups = groups_get_active($_POST['max-groups']);
		break;
		case 'popular-groups':
			$groups = groups_get_popular($_POST['max-groups']);
		break;
	}

	if ( $groups ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $groups as $group ) {
			$group = new BP_Groups_Group( $group->group_id, false );
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
							if ( $_POST['filter'] == 'newest-groups') {
								echo bp_core_get_last_activity( $group->date_created, __('created %s ago', 'buddypress') );
							} else if ( $_POST['filter'] == 'recently-active-groups') {
								echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __('active %s ago', 'buddypress') );
							} else if ( $_POST['filter'] == 'popular-groups') {
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

	check_ajax_referer('bp_groups_member_list'); ?>
	
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
			<p>This group has no members.</p>
		</div>

	<?php endif; ?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo $_REQUEST['group_id'] ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members', 'groups_ajax_member_list' );


function groups_ajax_member_admin_list() {
	global $bp;

	check_ajax_referer('bp_groups_member_admin_list'); ?>
	
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

					<h5><?php bp_group_member_link() ?> (banned) <span class="small"> &mdash; <a href="<?php bp_group_member_unban_link() ?>" title="Kick and ban this member">Remove Ban</a> </h5>
			<?php else : ?>
				<li>
					<?php bp_group_member_avatar_mini() ?>
					<h5><?php bp_group_member_link() ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>" title="Kick and ban this member">Kick &amp; Ban</a> | <a href="<?php bp_group_member_promote_link() ?>" title="Promote this member">Promote to Moderator</a></span></h5>

			<?php endif; ?>
				</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p>This group has no members.</p>
		</div>

	<?php endif;?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo $_REQUEST['group_id'] ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members_admin', 'groups_ajax_member_admin_list' );

fuction 

?>