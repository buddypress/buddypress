<?php

function bp_core_ajax_widget_members() {
	global $bp;

	check_ajax_referer('bp_core_widget_members');
	
	switch ( $_POST['filter'] ) {
		case 'newest-members':
			$users = BP_Core_User::get_newest_users($_POST['max-members']);
		break;
		case 'recently-active-members':
			$users = BP_Core_User::get_active_users($_POST['max-members']);
		break;
		case 'popular-members':
			$users = BP_Core_User::get_popular_users($_POST['max-members']);
		break;
	}
	
	if ( $users ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $users as $user ) {
		?>
			<li>
				<div class="item-avatar">
					<?php echo bp_core_get_avatar( $user->user_id, 1 ) ?>
				</div>

				<div class="item">
					<div class="item-title"><?php echo bp_core_get_userlink( $user->user_id ) ?></div>
					<div class="item-meta">
						<span class="activity">
							<?php 
							if ( $_POST['filter'] == 'newest-members') {
								echo bp_core_get_last_activity( $user->user_registered, __( 'registered %s ago', 'buddypress' ) );
							} else if ( $_POST['filter'] == 'recently-active-members') {
								echo bp_core_get_last_activity( get_usermeta( $user->user_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) );
							} else if ( $_POST['filter'] == 'popular-members') {
								if ( get_usermeta( $user->user_id, 'total_friend_count' ) == 1 )
									echo get_usermeta( $user->user_id, 'total_friend_count' ) . __(' friend', 'buddypress');
								else
									echo get_usermeta( $user->user_id, 'total_friend_count' ) . __(' friends', 'buddypress');
							}
							?>
						</span>
					</div>
				</div>
			</li>
			<?php	
		}
	} else {
		echo "-1[[SPLIT]]<li>" . __("No members matched the current filter.", 'buddypress');
	}
}
add_action( 'wp_ajax_widget_members', 'bp_core_ajax_widget_members' );


function bp_core_ajax_directory_members() {
	global $bp;

	check_ajax_referer('directory_members');

	$users = BP_Core_User::get_users_by_letter( $_POST['letter'] );

	if ( $users ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $users as $user ) {
		?>
			<?php $user_obj = new BP_Core_User( $user->user_id, true ); ?>
			<li>
				<div class="item-avatar">
					<?php echo $user_obj->avatar_thumb ?>
				</div>

				<div class="item">
					<div class="item-title"><?php echo $user_obj->user_link ?></div>
					<div class="item-meta"><span class="activity"><?php echo $user_obj->last_active ?></span></div>
				</div>
				
				<div class="action">
					<?php bp_add_friend_button( $user_obj->id ) ?>
					<div class="meta">
						<?php if ( $user_obj->total_friends ) echo $user_obj->total_friends ?><?php if ( $user_obj->total_blogs ) echo ', ' . $user_obj->total_blogs ?><?php if ( $user_obj->total_groups ) echo ', ' . $user_obj->total_groups ?>
					</div>
				</div>
				
				<div class="clear"></div>
			</li>
			<?php	
		}
	} else {
		echo "-1[[SPLIT]]<li>" . __("No members matched the current filter.");
	}
}
add_action( 'wp_ajax_directory_members', 'bp_core_ajax_directory_members' );


?>