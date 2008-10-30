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

function groups_ajax_group_search() {
	global $bp;

	check_ajax_referer('group_search');

	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_group_count = 0;

	if ( $_POST['group-search-box'] == "" ) {
		$groups = groups_get_user_groups( $pag_page, $pag_num );
	} else {
		$groups = BP_Groups_Group::search_user_groups( $_POST['group-search-box'], $pag_num, $pag_page );
	}
	
	$total_group_count = (int)$groups['count'];

	if ( $total_group_count ) {
		$pag_links = paginate_links( array(
			'base' => $bp['current_domain'] . $bp['groups']['slug'] . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_group_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $groups['groups'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($groups['groups']); $i++ ) {
			$group = $groups['groups'][$i];
			?>
			<li>
				<img class="avatar" alt="Group Avatar" src="<?php echo $group->avatar_thumb ?>"/>
				<h4>
					<a href="<?php bp_group_permalink( $group ) ?>"><?php echo $group->name ?></a>
					<span class="small"> - <?php echo $group->total_member_count . ' ' . __('members', 'buddypress') ?></span>
				</h4>
				<p class="desc"><?php echo bp_create_excerpt( $group->description, 20 ) ?></p>
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['groups']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No groups matched your search.", 'buddypress');
	}
}
add_action( 'wp_ajax_group_search', 'groups_ajax_group_search' );

function groups_ajax_group_finder_search() {
	global $bp;

	check_ajax_referer('groupfinder_search');

	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_group_count = 0;

	if ( $_POST['groupfinder-search-box'] != "" ) {
		$groups = BP_Groups_Group::search_groups( $_POST['groupfinder-search-box'], $pag_num, $pag_page );
	}
	
	$total_group_count = (int)$groups['count'];

	if ( $total_group_count ) {
		$pag_links = paginate_links( array(
			'base' => $bp['current_domain'] . $bp['groups']['slug'] . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_group_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $groups['groups'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($groups['groups']); $i++ ) {
			$group = $groups['groups'][$i];
			?>
			<li>
				<img class="avatar" alt="Group Avatar" src="<?php echo $group->avatar_thumb ?>"/>
				<h4>
					<a href="<?php bp_group_permalink( $group ) ?>"><?php echo $group->name ?></a>
					<span class="small"> - <?php echo $group->total_member_count . ' ' . __('members', 'buddypress') ?></span>
				</h4>
				<p class="desc"><?php echo bp_create_excerpt( $group->description, 20 ) ?></p>
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['groups']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No groups matched your search.", 'buddypress');
	}
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