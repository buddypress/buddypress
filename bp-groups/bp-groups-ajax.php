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

?>