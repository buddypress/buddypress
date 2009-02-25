<?php

function bp_core_ajax_widget_members() {
	global $bp;

	check_ajax_referer('bp_core_widget_members');
	
	switch ( $_POST['filter'] ) {
		case 'newest-members':
			$users = BP_Core_User::get_newest_users( $_POST['max-members'], 1 );
		break;
		case 'recently-active-members':
			$users = BP_Core_User::get_active_users( $_POST['max-members'], 1 );
		break;
		case 'popular-members':
			$users = BP_Core_User::get_popular_users( $_POST['max-members'], 1 );
		break;
	}
	
	if ( $users['users'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		foreach ( (array) $users['users'] as $user ) {
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
	global $bp;

	check_ajax_referer('directory_members');
	
	$pag_page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 10;
	
	if ( isset( $_POST['letter'] ) && $_POST['letter'] != '' ) {
		$users = BP_Core_User::get_users_by_letter( $_POST['letter'], $pag_num, $pag_page );
	} else if ( isset ( $_POST['members_search'] ) && $_POST['members_search'] != '' ) {
		$users = BP_Core_User::search_users( $_POST['members_search'], $pag_num, $pag_page );
	} else {
		$users = BP_Core_User::get_active_users( $pag_num, $pag_page );
	}

	$pag_links = paginate_links( array(
		'base' => add_query_arg( 'page', '%#%' ),
		'format' => '',
		'total' => ceil( $users['total'] / $pag_num ),
		'current' => $pag_page,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'mid_size' => 1
	));
	
	$from_num = intval( ( $pag_page - 1 ) * $pag_num ) + 1;
	$to_num = ( $from_num + 9 > $users['total'] ) ? $users['total'] : $from_num + 9; 
	
	if ( $users['users'] ) {
		echo '0[[SPLIT]]'; // return valid result.
		
		?>
		<div id="member-dir-count" class="pag-count">
			<?php echo sprintf( __( 'Viewing member %d to %d (%d total active members)', 'buddypress' ), $from_num, $to_num, $users['total'] ); ?> &nbsp;
			<img id="ajax-loader-members" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
		</div>
	
		<div class="pagination-links" id="member-dir-pag">
			<?php echo $pag_links ?>
		</div>
		<?php
	
		echo '<ul id="members-list" class="item-list">';
		foreach ( (array) $users['users'] as $user ) {
		?>
			<li>
				<div class="item-avatar">
					<?php echo bp_core_get_avatar( $user->user_id, 1 ) ?>
				</div>

				<div class="item">
					<div class="item-title"><?php echo bp_core_get_userlink( $user->user_id ) ?></div>
					<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( get_usermeta( $user->user_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) ?></span></div>
				</div>
				
				<div class="action">
					<?php bp_add_friend_button( $user->user_id ) ?>
					<div class="meta">
						<?php if ( $user_obj->total_friends ) echo $user_obj->total_friends ?><?php if ( $user_obj->total_blogs ) echo ', ' . $user_obj->total_blogs ?><?php if ( $user_obj->total_groups ) echo ', ' . $user_obj->total_groups ?>
					</div>
				</div>
				
				<div class="clear"></div>
			</li>
		<?php	
		}
		echo '</ul>';
	} else {
		echo "-1[[SPLIT]]<div id='message' class='error'><p>" . __("No members matched the current filter.", 'buddypress') . '</p></div>';
	}
	
	if ( isset( $_POST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . $_POST['letter'] . '" name="selected_letter" />';
	}
	
	if ( isset( $_POST['members_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . $_POST['members_search'] . '" name="search_terms" />';
	}

}
add_action( 'wp_ajax_directory_members', 'bp_core_ajax_directory_members' );


?>