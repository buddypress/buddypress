<?php

function bp_core_add_ajax_hook() {
	do_action( 'wp_ajax_' . $_REQUEST['action'] );
}
add_action( 'init', 'bp_core_add_ajax_hook' );

function bp_core_ajax_widget_members() {
	global $bp;

	check_ajax_referer('bp_core_widget_members');
	
	switch ( $_POST['filter'] ) {
		case 'newest-members':
			if ( !$users = wp_cache_get( 'newest_users', 'bp' ) ) {
				$users = BP_Core_User::get_newest_users( $_POST['max-members'], 1 );
				wp_cache_set( 'newest_users', $users, 'bp' );
			}
		break;
		case 'recently-active-members':
			if ( !$users = wp_cache_get( 'active_users', 'bp' ) ) {
				$users = BP_Core_User::get_active_users( $_POST['max-members'], 1 );
				wp_cache_set( 'active_users', $users, 'bp' );
			}
		break;
		case 'popular-members':
			if ( !$users = wp_cache_get( 'popular_users', 'bp' ) ) {
				$users = BP_Core_User::get_popular_users( $_POST['max-members'], 1 );
				wp_cache_set( 'popular_users', $users, 'bp' );
			}
		break;
	}
	
	if ( $users['users'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $users['users'] as $user ) {
		?>
			<li class="vcard">
				<div class="item-avatar">
					<a href="<?php echo bp_core_get_userlink( $user->user_id, false, true ) ?>"><?php echo bp_core_get_avatar( $user->user_id, 1 ) ?></a>
				</div>

				<div class="item">
					<div class="item-title"><?php echo bp_core_get_userlink( $user->user_id ) ?></div>
					<div class="item-meta">
						<span class="activity">
							<?php 
							if ( 'newest-members' == $_POST['filter'] ) {
								echo bp_core_get_last_activity( $user->user_registered, __( 'registered %s ago', 'buddypress' ) );
							} else if ( 'recently-active-members' == $_POST['filter'] ) {
								echo bp_core_get_last_activity( get_usermeta( $user->user_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) );
							} else if ( 'popular-members' == $_POST['filter'] ) {
								if ( 1 == get_usermeta( $user->user_id, 'total_friend_count' ) )
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
	check_ajax_referer('directory_members');
	
	load_template( TEMPLATEPATH . '/directories/members/members-loop.php' );
}
add_action( 'wp_ajax_directory_members', 'bp_core_ajax_directory_members' );


?>