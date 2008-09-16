<?php
function groups_ajax_invite_user() {
	global $bp;

	check_ajax_referer('invite_user');

	if ( !$bp ) {
		bp_core_setup_globals();
		groups_setup_globals();
		
		if ( function_exists('friends_setup_globals') )
			friends_setup_globals();
		
		if ( function_exists('xprofile_setup_globals') )
			xprofile_setup_globals();
	}

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

	if ( !$bp ) {
		bp_core_setup_globals();
		groups_setup_globals();
	}
	
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
					<a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] . '/' . $group->slug ?>"><?php echo $group->name ?></a>
					<span class="small"> - <?php echo count($group->user_dataset) ?> members</span>
				</h4>
				<p class="desc"><?php echo bp_create_excerpt( $group->description, 20 ) ?></p>
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['groups']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No groups matched your search.");
	}
}
add_action( 'wp_ajax_group_search', 'groups_ajax_group_search' );

function groups_ajax_group_finder_search() {
	global $bp;

	check_ajax_referer('groupfinder_search');

	if ( !$bp ) {
		bp_core_setup_globals();
		groups_setup_globals();
	}
	
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
					<a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] . '/' . $group->slug ?>"><?php echo $group->name ?></a>
					<span class="small"> - <?php echo count($group->user_dataset) ?> members</span>
				</h4>
				<p class="desc"><?php echo bp_create_excerpt( $group->description, 20 ) ?></p>
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['groups']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No groups matched your search.");
	}
}
add_action( 'wp_ajax_group_finder_search', 'groups_ajax_group_finder_search' );
?>