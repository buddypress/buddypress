<?php

function bp_groups_header_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( !isset($bp->action_variables[0]) || 'recently-active' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/recently-active"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( 'recently-joined' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/recently-joined"><?php _e( 'Recently Joined', 'buddypress' ) ?></a></li>
	<li<?php if ( 'most-popular' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/most-popular""><?php _e( 'Most Popular', 'buddypress' ) ?></a></li>
	<li<?php if ( 'admin-of' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/admin-of""><?php _e( 'Administrator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( 'mod-of' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/mod-of""><?php _e( 'Moderator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( 'alphabetically' == $bp->action_variables[0] ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/my-groups/alphabetically""><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>
		
<?php
	do_action( 'groups_header_tabs' );
}

function bp_group_creation_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( 1 == (int)$create_group_step ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/1">1. <?php _e('Group Details', 'buddypress') ?></a></li>
	<li<?php if ( 2 == (int)$create_group_step ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 0 ) { ?><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/2">2. <?php _e('Group Settings', 'buddypress') ?></a><?php } else { ?><span>2. <?php _e('Group Settings', 'buddypress') ?></span><?php } ?></li>
	<li<?php if ( 3 == (int)$create_group_step ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 1 ) { ?><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/3">3. <?php _e('Group Avatar', 'buddypress') ?></a><?php } else { ?><span>3. <?php _e('Group Avatar', 'buddypress') ?></span><?php } ?></li>
	<li<?php if ( 4 == (int)$create_group_step ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 2 ) { ?><a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/4">4. <?php _e('Invite Members', 'buddypress') ?></a><?php } else { ?><span>4. <?php _e('Invite Members', 'buddypress') ?></span><?php } ?></li>
<?php
	do_action( 'groups_creation_tabs' );
}

function bp_group_creation_stage_title() {
	global $create_group_step;
	
	switch( (int) $create_group_step ) {
		case 1:
			echo '<span>&mdash; ' . __('Group Details', 'buddypress') . '</span>';
		break;
		
		case 2:
			echo '<span>&mdash; ' . __('Group Settings', 'buddypress') . '</span>';		
		break;
		
		case 3:
			echo '<span>&mdash; ' . __('Group Avatar', 'buddypress') . '</span>';
		break;
		
		case 4:
			echo '<span>&mdash; ' . __('Invite Members', 'buddypress') . '</span>';
		break;
	}
}

function bp_group_create_form() {
	global $bp, $create_group_step, $completed_to_step;
	global $group_obj, $invites;

?>
	<form action="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/<?php echo $create_group_step ?>" method="post" id="create-group-form" class="standard-form" enctype="multipart/form-data">
	<?php switch( (int) $create_group_step ) {
		case 1: ?>
			<label for="group-name">* <?php _e('Group Name', 'buddypress') ?></label>
			<input type="text" name="group-name" id="group-name" value="<?php echo attribute_escape( ( $group_obj ) ? $group_obj->name : $_POST['group-name'] ); ?>" />
		
			<label for="group-desc">* <?php _e('Group Description', 'buddypress') ?></label>
			<textarea name="group-desc" id="group-desc"><?php echo htmlspecialchars( ( $group_obj ) ? $group_obj->description : $_POST['group-desc'] ); ?></textarea>
		
			<label for="group-news"><?php _e('Recent News', 'buddypress') ?></label>
			<textarea name="group-news" id="group-news"><?php echo htmlspecialchars( ( $group_obj ) ? $group_obj->news : $_POST['group-news'] ); ?></textarea>
			
			<?php do_action( 'groups_custom_group_fields_editable' ) ?>
			
			<p><input type="submit" value="<?php _e('Create Group and Continue', 'buddypress') ?> &raquo;" id="save" name="save"/></p>
			
			<?php wp_nonce_field( 'groups_step1_save' ) ?>
		<?php break; ?>
		
		<?php case 2: ?>
			<?php if ( $completed_to_step > 0 ) { ?>
				<?php if ( function_exists('bp_wire_install') ) : ?>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php if ( $group_obj->enable_wire ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
				</div>
				<?php endif; ?>
				
				<?php if ( function_exists('bp_forums_setup') ) : ?>
					<?php if ( bp_forums_is_installed_correctly() ) { ?>
						<div class="checkbox">
							<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php if ( $group_obj->enable_forum ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
						</div>
					<?php } else {
						if ( is_site_admin() ) {
							?>
							<div class="checkbox">
								<label><input type="checkbox" disabled="disabled" name="disabled" id="disabled" value="0" /> <?php printf( __('<strong>Attention Site Admin:</strong> Group forums require the <a href="%s">correct setup and configuration</a> of a bbPress installation.', 'buddypress' ), $bp->root_domain . '/wp-admin/admin.php?page=' . BP_PLUGIN_DIR . '/bp-forums/bp-forums-admin.php' ) ?></label>
							</div>
							<?php
						}
					}?>
				<?php endif; ?>
				
				<?php if ( function_exists('bp_albums_install') ) : ?>
				<div class="checkbox with-suboptions">
					<label><input type="checkbox" name="group-show-photos" id="group-show-photos" value="1"<?php if ( $group_obj->enable_photos ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable photo gallery', 'buddypress') ?></label>
					<div class="sub-options"<?php if ( !$group_obj->enable_photos ) { ?> style="display: none;"<?php } ?>>
						<label><input type="radio" name="group-photos-status" value="all"<?php if ( !$group_obj->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('All members can upload photos', 'buddypress') ?></label>
						<label><input type="radio" name="group-photos-status" value="admins"<?php if ( $group_obj->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('Only group admins can upload photos', 'buddypress') ?></label>
					</div>
				</div>
				<?php endif; ?>
			
				<h3><?php _e( 'Privacy Options', 'buddypress' ); ?></h3>
			
				<div class="radio">
					<label><input type="radio" name="group-status" value="public"<?php if ( 'public' == $group_obj->status ) { ?> checked="checked"<?php } ?> /> 
						<strong><?php _e( 'This is a public group', 'buddypress' ) ?></strong>
						<ul>
							<li><?php _e( 'Any site member can join this group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will be visible to any site member.', 'buddypress' ) ?></li>
						</ul>
					</label>
					
					<label><input type="radio" name="group-status" value="private"<?php if ( 'private' == $group_obj->status ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e( 'This is a private group', 'buddypress' ) ?></strong>
						<ul>
							<li><?php _e( 'Only users who request membership and are accepted can join the group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
						</ul>
					</label>
					
					<label><input type="radio" name="group-status" value="hidden"<?php if ( 'hidden' == $group_obj->status ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e('This is a hidden group', 'buddypress') ?></strong>
						<ul>
							<li><?php _e( 'Only users who are invited can join the group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
						</ul>
					</label>
				</div>

				<p><input type="submit" value="<?php _e('Save and Continue', 'buddypress') ?> &raquo;" id="save" name="save"/></p>

				<?php wp_nonce_field( 'groups_step2_save' ) ?>
			<?php } else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
			<?php } ?>
		<?php break; ?>
		
		<?php case 3: ?>
			<?php if ( $completed_to_step > 1 ) { ?>
				<div class="left-menu">
					<?php bp_group_current_avatar() ?>
				</div>
				
				<div class="main-column">
					<p><?php _e("Upload an image to use as an avatar for this group. The image will be shown on the main group page, and in search results.", 'buddypress') ?></p>
					
					<?php
					if ( !empty($_FILES) || ( isset($_POST['orig']) && isset($_POST['canvas']) ) ) {
						groups_avatar_upload($_FILES);
					} else {
						bp_core_render_avatar_upload_form( '', true );		
					}
					?>
					
					<div id="skip-continue">
						<input type="submit" value="<?php _e('Skip', 'buddypress') ?> &raquo;" id="skip" name="skip"/>
					</div>
				</div>
				
				<?php wp_nonce_field( 'groups_step3_save' ) ?>
			<?php } else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
			<?php } ?>
		<?php break; ?>
		<?php case 4: ?>
			<?php 
			if ( $completed_to_step > 2 ) {
				$group_link = bp_get_group_permalink( $group_obj );
				
				if ( function_exists('friends_install') ) {
					if ( friends_get_friend_count_for_user( $bp->loggedin_user->id ) ) {
						bp_group_send_invite_form( $group_obj );
					} else {
						?>
						<div id="message" class="info">
							<p><?php _e( 'Once you build up your friends list you will be able to invite friends to join your group.', 'buddypress' ) ?></p>
						</div>
						<?php
					}
				} ?>
				
				<p class="clear"><input type="submit" value="<?php _e('Finish', 'buddypress') ?> &raquo;" id="save" name="save" /></p>
				
				<?php wp_nonce_field( 'groups_step4_save' ) ?>
				
				<?php
			} else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
		<?php } ?>
		<?php break; ?>
	<?php } ?>
	</form>
<?php
}
function bp_group_list_invite_friends() {
	global $bp, $group_obj, $invites;
	
	if ( !function_exists('friends_install') )
		return false;

		$friends = friends_get_friends_invite_list( $bp->loggedin_user->id, $group_obj->id );

		if ( $friends ) {
			$invites = groups_get_invites_for_group( $bp->loggedin_user->id, $group_obj->id );

	?>
			<div id="invite-list">
				<ul>
					<?php 
						for ( $i = 0; $i < count( $friends ); $i++ ) {
							if ( $invites ) {
								if ( in_array( $friends[$i]['id'], $invites ) ) {
									$checked = ' checked="checked"';
								} else {
									$checked = '';
								} 
							}
					?>
					
					<li><input<?php echo $checked ?> type="checkbox" name="friends[]" id="f-<?php echo $friends[$i]['id'] ?>" value="<?php echo attribute_escape( $friends[$i]['id'] ); ?>" /> <?php echo $friends[$i]['full_name']; ?></li>
					<?php } ?>
				</ul>
			</div>
	<?php
		} else {
			_e( 'No friends to invite.', 'buddypress' );
		}
}

function bp_groups_filter_title() {
	global $bp;
	
	$current_filter = $bp->action_variables[0];
	
	switch ( $current_filter ) {
		case 'recently-active': default:
			_e( 'Recently Active', 'buddypress' );
			break;
		case 'recently-joined':
			_e( 'Recently Joined', 'buddypress' );
			break;
		case 'most-popular':
			_e( 'Most Popular', 'buddypress' );
			break;
		case 'admin-of':
			_e( 'Administrator Of', 'buddypress' );
			break;
		case 'mod-of':
			_e( 'Moderator Of', 'buddypress' );
			break;
		case 'alphabetically':
			_e( 'Alphabetically', 'buddypress' );
		break;
	}
}

function bp_group_current_avatar() {
	global $group_obj;
	
	if ( $group_obj->avatar_full ) { ?>
		<img src="<?php echo attribute_escape( $group_obj->avatar_full ) ?>" alt="<?php _e( 'Group Avatar', 'buddypress' ) ?>" class="avatar" />
	<?php } else { ?>
		<img src="<?php echo $bp->groups->image_base . '/none.gif' ?>" alt="<?php _e( 'No Group Avatar', 'buddypress' ) ?>" class="avatar" />
	<?php }
}

function bp_group_avatar_edit_form() {
	if ( !empty($_FILES) || ( isset($_POST['orig']) && isset($_POST['canvas']) ) ) {
		groups_avatar_upload($_FILES);
	} else {
		bp_core_render_avatar_upload_form( '', true );		
	}
}

function bp_groups_random_selection( $total_groups = 5 ) {
	global $bp;
	
	if ( !$group_ids = wp_cache_get( 'groups_random_groups', 'bp' ) ) {
		$group_ids = BP_Groups_Group::get_random( $total_groups, 1 );
		wp_cache_set( 'groups_random_groups', $group_ids, 'bp' );
	}
?>	
	<?php if ( $group_ids['groups'] ) { ?>
		<ul class="item-list" id="random-groups-list">
		<?php 
			for ( $i = 0; $i < count( $group_ids['groups'] ); $i++ ) { 
				if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_ids['groups'][$i]->group_id, 'bp' ) ) {
					$group = new BP_Groups_Group( $group_ids['groups'][$i]->group_id, false, false );
					wp_cache_set( 'groups_group_nouserdata_' . $group_ids['groups'][$i]->group_id, $group, 'bp' );
				}
			?>	
			<li>
				<div class="item-avatar">
					<a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_avatar_thumb( $group ) ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_name( $group ) ?></a></div>
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
		<?php } ?>
		</ul>
	<?php } else { ?>
		<div id="message" class="info">
			<p><?php _e( "There aren't enough groups to show a random sample just yet.", 'buddypress' ) ?></p>
		</div>		
	<?php } ?>
<?php
}

function bp_groups_random_groups( $total_groups = 5 ) {
	global $bp;
	
	if ( !$group_ids = wp_cache_get( 'groups_random_user_groups_' . $bp->displayed_user->id . '_' . $total_groups, 'bp' ) ) {
		$group_ids = groups_get_random_groups_for_user( $bp->displayed_user->id, $total_groups, 1 );
		wp_cache_set( 'groups_random_user_groups_' . $bp->displayed_user->id . '_' . $total_groups, $group_ids, 'bp' );
	}
	
?>	
	<div class="info-group">
		<h4><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> (<?php echo BP_Groups_Member::total_group_count() ?>) <a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></h4>
		<?php if ( $group_ids ) { ?>
			<ul class="horiz-gallery">
			<?php 
			for ( $i = 0; $i < count( $group_ids ); $i++ ) {
				if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_ids[$i], 'bp' ) ) {
					$group = new BP_Groups_Group( $group_ids[$i], false, false );
					wp_cache_set( 'groups_group_nouserdata_' . $group_ids[$i], $group, 'bp' );
				}
			?>				<li>
					<a href="<?php echo bp_get_group_permalink( $group ) ?>"><img src="<?php echo attribute_escape( $group->avatar_thumb ); ?>" class="avatar" alt="<?php _e( 'Group Avatar', 'buddypress' ) ?>" /></a>
					<h5><a href="<?php echo bp_get_group_permalink( $group ) ?>"><?php echo attribute_escape( $group->name ) ?></a></h5>
				</li>
			<?php } ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "You haven't joined any groups yet.", 'buddypress' ), __( "%s hasn't joined any groups yet.", 'buddypress' ) ) ?></p>
			</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
<?php
}

function bp_custom_group_boxes() {
	do_action( 'groups_custom_group_boxes' );
}

function bp_custom_group_admin_tabs() {
	do_action( 'groups_custom_group_admin_tabs' );
}

function bp_custom_group_fields_editable() {
	do_action( 'groups_custom_group_fields_editable' );
}

function bp_custom_group_fields() {
	do_action( 'groups_custom_group_fields' );
}


/*****************************************************************************
 * User Groups Template Class/Tags
 **/

class BP_Groups_User_Groups_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_group_count;
	
	var $single_group = false;
	
	var $sort_by;
	var $order;
	
	function bp_groups_user_groups_template( $user_id, $type, $per_page, $max, $slug, $filter ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		$this->pag_page = isset( $_REQUEST['grpage'] ) ? intval( $_REQUEST['grpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		switch ( $type ) {
			case 'recently-joined':
				$this->groups = groups_get_recently_joined_for_user( $user_id, $this->pag_num, $this->pag_page, $filter );
				break;

			case 'popular':
				$this->groups = groups_get_most_popular_for_user( $user_id, $this->pag_num, $this->pag_page, $filter );				
				break;

			case 'admin-of':
				$this->groups = groups_get_user_is_admin_of( $user_id, $this->pag_num, $this->pag_page, $filter );				
				break;	

			case 'mod-of':
				$this->groups = groups_get_user_is_mod_of( $user_id, $this->pag_num, $this->pag_page, $filter );				
				break;

			case 'alphabetical':
				$this->groups = groups_get_alphabetically_for_user( $user_id, $this->pag_num, $this->pag_page, $filter );	
				break;

			case 'invites':
				$this->groups = groups_get_invites_for_user();
				break;

			case 'single-group':
				$group = new stdClass;
				$group->group_id = BP_Groups_Group::get_id_from_slug($slug);			
				$this->groups = array( $group );
				break;

			case 'active': default:
				$this->groups = groups_get_recently_active_for_user( $user_id, $this->pag_num, $this->pag_page, $filter );
				break;
		}

		if ( 'invites' == $type ) {
			$this->total_group_count = count($this->groups);
			$this->group_count = count($this->groups);
		} else if ( 'single-group' == $type ) {
			$this->single_group = true;
			$this->total_group_count = 1;
			$this->group_count = 1;
		} else {
			if ( !$max || $max >= (int)$this->groups['total'] )
				$this->total_group_count = (int)$this->groups['total'];
			else
				$this->total_group_count = (int)$max;

			$this->groups = $this->groups['groups'];

			if ( $max ) {
				if ( $max >= count($this->groups) )
					$this->group_count = count($this->groups);
				else
					$this->group_count = (int)$max;
			} else {
				$this->group_count = count($this->groups);
			}
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'grpage' => '%#%', 'num' => $this->pag_num, 's' => $_REQUEST['s'], 'sortby' => $this->sort_by, 'order' => $this->order ) ),
			'format' => '',
			'total' => ceil($this->total_group_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}

	function has_groups() {
		if ( $this->group_count )
			return true;
		
		return false;
	}
	
	function next_group() {
		$this->current_group++;
		$this->group = $this->groups[$this->current_group];
			
		return $this->group;
	}
	
	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}
	
	function user_groups() { 
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_group() {
		global $group;

		$this->in_the_loop = true;
		$this->group = $this->next_group();
		
		// If this is a single group then instantiate group meta when creating the object.
		if ( $this->single_group ) {
			if ( !$group = wp_cache_get( 'groups_group_' . $this->group->group_id, 'bp' ) ) {
				$group = new BP_Groups_Group( $this->group->group_id, true );
				wp_cache_set( 'groups_group_' . $this->group->group_id, $group, 'bp' );
			}
		} else {
			if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $this->group->group_id, 'bp' ) ) {
				$group = new BP_Groups_Group( $this->group->group_id, false, false );
				wp_cache_set( 'groups_group_nouserdata_' . $this->group->group_id, $group, 'bp' );
			}
		}
		
		$this->group = $group;
		
		if ( 0 == $this->current_group ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_groups( $args = '' ) {
	global $groups_template, $bp;
	global $group_obj;
	
	$defaults = array(
		'type' => 'active',
		'user_id' => false,
		'per_page' => 10,
		'max' => false,
		'slug' => false,
		'filter' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	/* The following code will auto set parameters based on the page being viewed.
	 * for example on example.com/members/andy/groups/my-groups/most-popular/
	 * $type = 'most-popular'
	 */
	if ( 'my-groups' == $bp->current_action ) {
		$order = $bp->action_variables[0];
		if ( 'recently-joined' == $order )
			$type = 'recently-joined';
		else if ( 'most-popular' == $order )
			$type = 'popular';
		else if ( 'admin-of' == $order )
			$type = 'admin-of';
		else if ( 'mod-of' == $order )
			$type = 'mod-of';
		else if ( 'alphabetically' == $order )
			$type = 'alphabetical';
	} else if ( 'invites' == $bp->current_action ) {
		$type = 'invites';
	} else if ( $group_obj->slug ) {
		$type = 'single-group';
		$slug = $group_obj->slug;
	}
	
	if ( isset( $_REQUEST['group-filter-box'] ) )
		$filter = $_REQUEST['group-filter-box'];
	
	$groups_template = new BP_Groups_User_Groups_Template( $user_id, $type, $per_page, $max, $slug, $filter );
	return $groups_template->has_groups();
}

function bp_groups() {
	global $groups_template;
	return $groups_template->user_groups();
}

function bp_the_group() {
	global $groups_template;
	return $groups_template->the_group();
}

function bp_group_is_visible( $group = false ) {
	global $bp, $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
		
	if ( 'public' == $group->status ) {
		return true;
	} else {
		if ( groups_is_user_member( $bp->loggedin_user->id, $group->id ) ) {
			return true;
		}
	}
	
	return false;
}

function bp_group_has_news( $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	if ( empty( $group->news ) )
		return false;
	
	return true;
}

function bp_group_id( $deprecated = true, $deprecated2 = false ) {
	global $groups_template;

	if ( !$deprecated )
		return bp_get_group_id();
	else
		echo bp_get_group_id();
}
	function bp_get_group_id( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_id', $group->id );
	}
	
function bp_group_name( $deprecated = true, $deprecated2 = false ) {
	global $groups_template;

	if ( !$deprecated )
		return bp_get_group_name();
	else
		echo bp_get_group_name();
}
	function bp_get_group_name( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_name', $group->name );
	}

function bp_group_type() {
	echo bp_get_group_type();
}
	function bp_get_group_type( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( 'public' == $group->status ) {
			$type = __( "Public Group", "buddypress" );
		} else if ( 'hidden' == $group->status ) {	
			$type = __( "Hidden Group", "buddypress" );
		} else if ( 'private' == $group->status ) {
			$type = __( "Private Group", "buddypress" );
		} else {
			$type = ucwords( $group->status ) . ' ' . __( 'Group', 'buddypress' );
		}

		return apply_filters( 'bp_get_group_type', $type );	
	}

function bp_group_avatar() {
	echo bp_get_group_avatar();
}
	function bp_get_group_avatar( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_avatar', '<img src="' . attribute_escape( $group->avatar_full ) . '" class="avatar" alt="' . attribute_escape( $group->name ) . '" />', $group->avatar_full, $group->avatar_name );
	}

function bp_group_avatar_thumb() {
	echo bp_get_group_avatar_thumb();
}
	function bp_get_group_avatar_thumb( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_avatar_thumb', '<img src="' . attribute_escape( $group->avatar_thumb ) . '" class="avatar" alt="' . attribute_escape( $group->name ) . '" />', $group->avatar_thumb, $group->avatar_name );
	}

function bp_group_avatar_mini() {
	echo bp_get_group_avatar_mini();
}
	function bp_get_group_avatar_mini( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_avatar_mini', '<img src="' . attribute_escape( $group->avatar_thumb ) . '" class="avatar" width="30" height="30" alt="' . attribute_escape( $group->name ) . '" />', $group->avatar_thumb, $group->avatar_name );
	}

function bp_group_last_active( $deprecated = true, $deprecated2 = false ) {
	if ( !$deprecated )
		return bp_get_group_last_active();
	else
		echo bp_get_group_last_active();			
}
	function bp_get_group_last_active( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		$last_active = groups_get_groupmeta( $group->id, 'last_activity' );

		if ( empty( $last_active ) ) {
			return __( 'not yet active', 'buddypress' );
		} else {
			return apply_filters( 'bp_get_group_last_active', bp_core_time_since( $last_active ) );			
		}
	}
	
function bp_group_permalink( $deprecated = false, $deprecated2 = true ) {
	if ( !$deprecated2 )
		return bp_get_group_permalink();
	else
		echo bp_get_group_permalink();
}
	function bp_get_group_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_permalink', $bp->root_domain . '/' . $bp->groups->slug . '/' . $group->slug );
	}

function bp_group_admin_permalink( $deprecated = true, $deprecated2 = false ) {
	if ( !$deprecated )
		return bp_get_group_admin_permalink();
	else
		echo bp_get_group_admin_permalink();
}
	function bp_get_group_admin_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_admin_permalink', $bp->root_domain . '/' . $bp->groups->slug . '/' . $group->slug . '/admin' );	
	}

function bp_group_slug() {
	echo bp_get_group_slug();
}
	function bp_get_group_slug( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_slug', $group->slug );
	}

function bp_group_description( $deprecated = false, $deprecated2 = true ) {
	if ( !$deprecated2 )
		return bp_get_group_description();
	else
		echo bp_get_group_description();
}
	function bp_get_group_description( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description', stripslashes($group->description) );
	}

function bp_group_description_editable( $deprecated = false ) {
	echo bp_get_group_description_editable();
}
	function bp_get_group_description_editable( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description_editable', $group->description );
	}

function bp_group_description_excerpt( $deprecated = false ) {
	echo bp_get_group_description_excerpt();
}
	function bp_get_group_description_excerpt( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description_excerpt', bp_create_excerpt( $group->description, 20 ) );	
	}

function bp_group_news( $deprecated = false ) {
	echo bp_get_group_news();
}
	function bp_get_group_news( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_news', stripslashes($groups_template->group->news) );
	}

function bp_group_news_editable( $deprecated = false ) {
	echo bp_get_group_news_editable();
}
	function bp_get_group_news_editable( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_news_editable', $group->news );
	}

function bp_group_public_status( $deprecated = false ) {
	echo bp_get_group_public_status();
}
	function bp_get_group_public_status( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( $group->is_public ) {
			return __( 'Public', 'buddypress' );
		} else {
			return __( 'Private', 'buddypress' );
		}
	}
	
function bp_group_is_public( $deprecated = false ) {
	echo bp_get_group_is_public();
}
	function bp_get_group_is_public( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_is_public', $group->is_public );
	}

function bp_group_date_created( $deprecated = false ) {
	echo bp_get_group_date_created();
}
	function bp_get_group_date_created( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_date_created', date( get_option( 'date_format' ), $group->date_created ) );
	}

function bp_group_list_admins( $full_list = true, $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	if ( !$admins = &$group->admins )
		$admins = $group->get_administrators();

	if ( $admins ) {
		if ( $full_list ) { ?>
			<ul id="group-admins">
			<?php for ( $i = 0; $i < count($admins); $i++ ) { ?>
				<li>
					<a href="<?php echo $admins[$i]->user->user_url ?>" title="<?php echo $admins[$i]->user->fullname ?>"><?php echo $admins[$i]->user->avatar_thumb ?></a>
					<h5><?php echo $admins[$i]->user->user_link ?></h5>
					<span class="activity"><?php echo $admins[$i]->user_title ?></span>
					<hr />
				</li>
			<?php } ?>
			</ul>
		<?php } else { ?>
			<?php for ( $i = 0; $i < count($admins); $i++ ) { ?>
				<?php echo $admins[$i]->user->user_link ?>
			<?php } ?>
		<?php } ?>
	<?php } else { ?>
		<span class="activity"><?php _e( 'No Admins', 'buddypress' ) ?></span>
	<?php } ?>
	
<?php
}

function bp_group_list_mods( $full_list = true, $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	$group_mods = groups_get_group_mods( $group->id );
	
	if ( $group_mods ) {
		if ( $full_list ) { ?>
			<ul id="group-mods" class="mods-list">
			<?php for ( $i = 0; $i < count($group_mods); $i++ ) { ?>
				<li>
					<a href="<?php echo bp_core_get_userlink( $group_mods[$i]->user_id, false, true ) ?>" title="<?php echo bp_core_get_user_displayname( $group_mods[$i]->user->user_id ) ?>"><?php echo bp_core_get_avatar( $group_mods[$i]->user_id, 1, 50, 50 ) ?></a>
					<h5><?php echo bp_core_get_userlink( $group_mods[$i]->user_id ) ?></h5>
					<span class="activity"><?php _e( 'Group Mod', 'buddypress' ) ?></span>
					<div class="clear"></div>
				</li>
			<?php } ?>
			</ul>
		<?php } else { ?>
			<?php for ( $i = 0; $i < count($admins); $i++ ) { ?>
				<?php echo bp_core_get_userlink( $group_mods[$i]->user_id ) . ' ' ?>
			<?php } ?>
		<?php } ?>
	<?php } else { ?>
		<span class="activity"><?php _e( 'No Mods', 'buddypress' ) ?></span>
	<?php } ?>
	
<?php
}

function bp_group_all_members_permalink( $deprecated = true, $deprecated2 = false ) {
	global $groups_template, $bp;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( !$deprecated )
		return bp_get_group_all_members_permalink();
	else
		echo bp_get_group_all_members_permalink();
}
	function bp_get_group_all_members_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_all_members_permalink', bp_get_group_permalink( $group ) . '/members' );
	}

function bp_group_random_members( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	$members = &$group->random_members;
?>	
	<ul class="horiz-gallery">
	<?php for ( $i = 0; $i < count( $members ); $i++ ) { ?>
		<li>
			<a href="<?php echo $members[$i]->user->user_url ?>"><?php echo $members[$i]->user->avatar_thumb ?></a>
			<h5><?php echo $members[$i]->user->user_link ?></h5>
		</li>
	<?php } ?>
	</ul>
	<div class="clear"></div>
<?php
}

function bp_group_active_forum_topics( $total_topics = 3, $group = false ) {
	global $groups_template, $forum_template;

	if ( !$group )
		$group =& $groups_template->group;

	$forum_id = groups_get_groupmeta( $group->id, 'forum_id' );

	if ( $forum_id && $forum_id != '' ) {
		if ( function_exists( 'bp_forums_setup' ) ) {
			$latest_topics = bp_forums_get_topics( $forum_id, $total_topics, 1 );
		
			if ( $latest_topics ) { ?>
				<ul class="item-list" id="recent-forum-topics"><?php
				
				$counter = 0;
				
				foreach( $latest_topics as $topic ) {
					$alt = ( $counter % 2 == 1 ) ? ' class="alt"' : '';
					$forum_template->topic = (object)$topic; ?>
					
					<li<?php echo $alt ?>>
						<div class="avatar">
							<?php bp_the_topic_poster_avatar() ?>
						</div>

						<a href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_title() ?></a> 
						<span class="small">- <?php bp_the_topic_total_post_count() ?> </span>
						<p><span class="activity"><?php echo sprintf( __( 'updated %s ago', 'buddypress' ), bp_the_topic_time_since_last_post( false ) ) ?></span></p>
				
						<div class="latest-post">
							<?php _e( 'Latest by', 'buddypress' ) ?> <?php bp_the_topic_last_poster_name() ?>:
							<?php bp_the_topic_latest_post_excerpt() ?>
						</div>
					</li>
					<?php $counter++ ?>
					
				<?php } ?>
				</ul>
				<?php
			} else {
			?>
				<div id="message" class="info">
					<p><?php _e( 'There are no active forum topics for this group', 'buddypress' ) ?></p>
				</div>
			<?php
			}
		}
	}
}

function bp_group_search_form() {
	global $groups_template, $bp;

	$action = $bp->loggedin_user->domain . $bp->groups->slug . '/my-groups/search/';
	$label = __('Filter Groups', 'buddypress');
	$name = 'group-filter-box';
?>
	<form action="<?php echo $action ?>" id="group-search-form" method="post">
		<label for="<?php echo $name ?>" id="<?php echo $name ?>-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" /></label>
		<input type="search" name="<?php echo $name ?>" id="<?php echo $name ?>" value="<?php echo $value ?>"<?php echo $disabled ?> />
	
		<?php wp_nonce_field( 'group-filter-box', '_wpnonce_group_filter' ) ?>
	</form>
<?php
}

function bp_group_show_no_groups_message() {
	global $bp;
	
	if ( !groups_total_groups_for_user( $bp->displayed_user->id ) )
		return true;
		
	return false;
}

function bp_group_pagination() {
	echo bp_get_group_pagination();
}
	function bp_get_group_pagination() {
		global $groups_template;
		
		return apply_filters( 'bp_get_group_pagination', $groups_template->pag_links );
	}

function bp_group_pagination_count() {
	global $bp, $groups_template;

	$from_num = intval( ( $groups_template->pag_page - 1 ) * $groups_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $groups_template->pag_num - 1 ) > $groups_template->total_group_count ) ? $groups_template->total_group_count : $from_num + ( $groups_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing group %d to %d (of %d groups)', 'buddypress' ), $from_num, $to_num, $groups_template->total_group_count ); ?> &nbsp;
	<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" /><?php
}

function bp_total_group_count() {
	echo bp_get_total_group_count();
}
	function bp_get_total_group_count() {
		global $groups_template;

		return apply_filters( 'bp_get_total_group_count', $groups_template->total_group_count );
	}

function bp_group_total_members( $deprecated = true, $deprecated2 = false ) {
	if ( !$deprecated )
		return bp_get_group_total_members();
	else
		echo bp_get_group_total_members();
}
	function bp_get_group_total_members( $echo = true, $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_total_members', $group->total_member_count );
	}

function bp_group_show_wire_setting( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	if ( $group->enable_wire )
		echo ' checked="checked"';
}

function bp_group_is_wire_enabled( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( $group->enable_wire )
		return true;
	
	return false;
}

function bp_group_forum_permalink( $deprecated = false ) {
	echo bp_get_group_forum_permalink();
}
	function bp_get_group_forum_permalink( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_forum_permalink', bp_get_group_permalink( $group ) . '/forum' );
	}

function bp_group_is_forum_enabled( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	if ( function_exists( 'bp_forums_is_installed_correctly' ) ) {
		if ( bp_forums_is_installed_correctly() ) {
			if ( $group->enable_forum )
				return true;
			
			return false;
		} else {
			return false;
		}
	}
	
	return false;	
}

function bp_group_show_forum_setting( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( $group->enable_forum )
		echo ' checked="checked"';
}

function bp_group_show_status_setting( $setting, $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( $setting == $group->status )
		echo ' checked="checked"';
}

function bp_group_admin_memberlist( $admin_list = false, $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	$admins = groups_get_group_admins( $group->id );
?>
	<?php if ( $admins ) { ?>
		<ul id="admins-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">
		<?php foreach ( $admins as $admin ) { ?>
			<?php if ( $admin_list ) { ?>
			<li>
				<?php echo bp_core_get_avatar( $admin->user_id, 1, 30, 30 ) ?>
				<h5><?php echo bp_core_get_userlink( $admin->user_id ) ?></h5>
			</li>
			<?php } else { ?>
			<li>
				<?php echo bp_core_get_avatar( $admin->user_id, 1 ) ?>
				<h5><?php echo bp_core_get_userlink( $admin->user_id ) ?></h5>
				<span class="activity"><?php echo bp_core_get_last_activity( strtotime( $admin->date_modified ), __( 'joined %s ago', 'buddypress') ); ?></span>
				
				<?php if ( function_exists( 'friends_install' ) ) : ?>
					<div class="action">
						<?php bp_add_friend_button( $admin->user_id ) ?>
					</div>
				<?php endif; ?>
			</li>		
			<?php } ?>
		<?php } ?>
		</ul>
	<?php } else { ?>
		<div id="message" class="info">
			<p><?php _e( 'This group has no administrators', 'buddypress' ); ?></p>
		</div>
	<?php }
}

function bp_group_mod_memberlist( $admin_list = false, $group = false ) {
	global $groups_template, $group_mods;	

	if ( !$group )
		$group =& $groups_template->group;
	
	$group_mods = groups_get_group_mods( $group->id );
	?>
		<?php if ( $group_mods ) { ?>
			<ul id="mods-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">
			<?php foreach ( $group_mods as $mod ) { ?>
				<?php if ( $admin_list ) { ?>
				<li>
					<?php echo bp_core_get_avatar( $mod->user_id, 1, 30, 30 ) ?>
					<h5><?php echo bp_core_get_userlink( $mod->user_id ) ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ) ?></a> | <a href="<?php bp_group_member_demote_link($mod->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a></span></h5>
				</li>
				<?php } else { ?>
				<li>
					<?php echo bp_core_get_avatar( $mod->user_id, 1 ) ?>
					<h5><?php echo bp_core_get_userlink( $mod->user_id ) ?></h5>
					<span class="activity"><?php echo bp_core_get_last_activity( strtotime( $mod->date_modified ), __( 'joined %s ago', 'buddypress') ); ?></span>
					
					<?php if ( function_exists( 'friends_install' ) ) : ?>
						<div class="action">
							<?php bp_add_friend_button( $mod->user_id ) ?>
						</div>
					<?php endif; ?>
				</li>		
				<?php } ?>			
			<?php } ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php _e( 'This group has no moderators', 'buddypress' ); ?></p>
			</div>
		<?php }
}

function bp_group_has_moderators( $group = false ) {
	global $group_mods, $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	return apply_filters( 'bp_group_has_moderators', groups_get_group_mods( $group->id ) );
}

function bp_group_member_promote_link( $user_id = false, $deprecated = false ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;
		
	echo bp_get_group_member_promote_link( $user_id );
}
	function bp_get_group_member_promote_link( $user_id = false, $group = false ) {
		global $members_template, $groups_template, $bp;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;
			
		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_promote_link', wp_nonce_url( bp_get_group_permalink( $group ) . '/admin/manage-members/promote/' . $user_id, 'groups_promote_member' ) );
	}

function bp_group_member_demote_link( $user_id = false, $deprecated = false ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_demote_link( $user_id );
}
	function bp_get_group_member_demote_link( $user_id = false, $group = false ) {
		global $members_template, $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;

		return apply_filters( 'bp_get_group_member_demote_link', wp_nonce_url( bp_get_group_permalink( $group ) . '/admin/manage-members/demote/' . $user_id, 'groups_demote_member' ) );
	}
	
function bp_group_member_ban_link( $user_id = false, $deprecated = false ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_ban_link( $user_id );
}
	function bp_get_group_member_ban_link( $user_id = false, $group = false ) {
		global $members_template, $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_ban_link', wp_nonce_url( bp_get_group_permalink( $group ) . '/admin/manage-members/ban/' . $user_id, 'groups_ban_member' ) );
	}

function bp_group_member_unban_link( $user_id = false, $deprecated = false ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;
	
	echo bp_get_group_member_unban_link( $user_id );	
}
	function bp_get_group_member_unban_link( $user_id = false, $group = false ) {
		global $members_template;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;
		
		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_unban_link', wp_nonce_url( bp_get_group_permalink( $group ) . '/admin/manage-members/unban/' . $user_id, 'groups_unban_member' ) );	
	}

function bp_group_admin_tabs( $group = false ) {
	global $bp, $groups_template;

	if ( !$group )
		$group =& $groups_template->group;
	
	$current_tab = $bp->action_variables[0];
?>
	<?php if ( $bp->is_item_admin || $bp->is_item_mod ) { ?>
		<li<?php if ( 'edit-details' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/edit-details"><?php _e('Edit Details', 'buddypress') ?></a></li>
	<?php } ?>

	<?php if ( $bp->is_item_admin ) { ?>	
		<li<?php if ( 'group-settings' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/group-settings"><?php _e('Group Settings', 'buddypress') ?></a></li>
	<?php } ?>
	
	<?php if ( $bp->is_item_admin ) { ?>	
		<li<?php if ( 'group-avatar' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/group-avatar"><?php _e('Group Avatar', 'buddypress') ?></a></li>
	<?php } ?>

	<?php if ( $bp->is_item_admin ) { ?>			
		<li<?php if ( 'manage-members' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/manage-members"><?php _e('Manage Members', 'buddypress') ?></a></li>
	<?php } ?>
	
	<?php if ( $bp->is_item_admin && $groups_template->group->status == 'private' ) : ?>
	<li<?php if ( 'membership-requests' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/membership-requests"><?php _e('Membership Requests', 'buddypress') ?></a></li>
	<?php endif; ?>

	<?php if ( $bp->is_item_admin ) { ?>		
		<li<?php if ( 'delete-group' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/delete-group"><?php _e('Delete Group', 'buddypress') ?></a></li>
	<?php } ?>
	
<?php
	do_action( 'groups_admin_tabs' );
}

function bp_group_form_action( $page, $deprecated = false ) {
	echo bp_get_group_form_action( $page );
}
	function bp_get_group_form_action( $page, $group = false ) {
		global $bp, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_form_action', bp_get_group_permalink( $group ) . '/' . $page );
	}
	
function bp_group_admin_form_action( $page, $deprecated = false ) {
	echo bp_get_group_admin_form_action( $page );
}
	function bp_get_group_admin_form_action( $page, $group = false ) {
		global $bp, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_admin_form_action', bp_get_group_permalink( $group ) . '/admin/' . $page );
	}

function bp_group_has_requested_membership( $group = false ) {
	global $bp, $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	if ( groups_check_for_membership_request( $bp->loggedin_user->id, $group->id ) )
		return true;
	
	return false;
}

function bp_group_is_member( $group = false ) {
	global $bp, $groups_template;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( groups_is_user_member( $bp->loggedin_user->id, $group->id ) )
		return true;
	
	return false;
}

function bp_group_accept_invite_link( $deprecated = false ) {
	echo bp_get_group_accept_invite_link();
}
	function bp_get_group_accept_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_accept_invite_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->groups->slug . '/invites/accept/' . $group->id, 'groups_accept_invite' ) );	
	}

function bp_group_reject_invite_link( $deprecated = false ) {
	echo bp_get_group_reject_invite_link();
}
	function bp_get_group_reject_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_reject_invite_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->groups->slug . '/invites/reject/' . $group->id, 'groups_reject_invite' ) );
	}

function bp_group_leave_confirm_link( $deprecated = false ) {
	echo bp_get_group_leave_confirm_link();
}
	function bp_get_group_leave_confirm_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_leave_confirm_link', wp_nonce_url( bp_get_group_permalink( $group ) . '/leave-group/yes', 'groups_leave_group' ) );	
	}

function bp_group_leave_reject_link( $deprecated = false ) {
	echo bp_get_group_leave_reject_link();
}
	function bp_get_group_leave_reject_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_leave_reject_link', bp_get_group_permalink( $group ) );
	}

function bp_group_send_invite_form_action( $deprecated = false ) {
	echo bp_get_group_send_invite_form_action();
}
	function bp_get_group_send_invite_form_action( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_send_invite_form_action', bp_get_group_permalink( $group ) . '/send-invites/send' );
	}

function bp_group_send_invite_form( $group = false ) {
	global $bp, $groups_template, $invites;
	
	if ( !$group )
		$group =& $groups_template->group;
?>
	<div class="left-menu">
		<h4><?php _e( 'Select Friends', 'buddypress' ) ?> <img id="ajax-loader" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></h4>
		<?php bp_group_list_invite_friends() ?>
		<?php wp_nonce_field( 'groups_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
		<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $group->id ) ?>" />
	</div>

	<div class="main-column">
		
		<div id="message" class="info">
			<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
		</div>

		<?php $invites = groups_get_invites_for_group( $bp->loggedin_user->id, $group_obj->id ) ?>
		
		<ul id="friend-list" class="item-list">
			<?php for ( $i = 0; $i < count($invites); $i++ ) {
				if ( !$user = wp_cache_get( 'bp_user_' . $invites[$i], 'bp' ) ) {
					$user = new BP_Core_User( $invites[$i] );
					wp_cache_set( 'bp_user_' . $invites[$i], $user, 'bp' );
				}
				?>
				<li id="uid-<?php echo $user->id ?>">
					<?php echo $user->avatar_thumb ?>
					<h4><?php echo $user->user_link ?></h4>
					<span class="activity"><?php echo $user->last_active ?></span>
					<div class="action">
						<a class="remove" href="<?php echo wp_nonce_url( site_url( $bp->groups->slug . '/' . $group->id . '/invites/remove/' . $user->id ), 'groups_invite_uninvite_user' ) ?>" id="uid-<?php echo $user->id ?>"><?php _e( 'Remove Invite', 'buddypress' ) ?></a> 
					</div>
				</li>
			<?php } // end for ?>
		</ul>
		
		<?php wp_nonce_field( 'groups_send_invites', '_wpnonce_send_invites' ) ?>
	</div>
<?php
}

function bp_has_friends_to_invite( $group = false ) {
	global $groups_template, $bp;
	
	if ( !function_exists('friends_install') )
		return false;

	if ( !$group )
		$group =& $groups_template->group;
	
	if ( !friends_check_user_has_friends( $bp->loggedin_user->id ) || !friends_count_invitable_friends( $bp->loggedin_user->id, $group->id ) )
		return false;
	
	return true;
}

function bp_group_join_button( $group = false ) {
	global $bp, $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	// If they're not logged in or are banned from the group, no join button.
	if ( !is_user_logged_in() || groups_is_user_banned( $bp->loggedin_user->id, $group->id ) )
		return false;
	
	echo '<div class="group-button ' . $group->status . '" id="groupbutton-' . $group->id . '">';
	
	switch ( $group->status ) {
		case 'public':
			if ( BP_Groups_Member::check_is_member( $bp->loggedin_user->id, $group->id ) )
				echo '<a class="leave-group" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';									
			else
				echo '<a class="join-group" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';					
		break;
		
		case 'private':
			if ( BP_Groups_Member::check_is_member( $bp->loggedin_user->id, $group->id ) ) {
				echo '<a class="leave-group" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';										
			} else {
				if ( !bp_group_has_requested_membership( $group ) )
					echo '<a class="request-membership" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/request-membership', 'groups_request_membership' ) . '">' . __('Request Membership', 'buddypress') . '</a>';		
				else
					echo '<a class="membership-requested" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Request Sent', 'buddypress' ) . '</a>';				
			}
		break;
	}
	
	echo '</div>';
}

function bp_group_status_message( $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	if ( 'private' == $group->status ) {
		if ( !bp_group_has_requested_membership() )
			if ( is_user_logged_in() )
				_e( 'This is a private group and you must request group membership in order to join.', 'buddypress' );
			else
				_e( 'This is a private group. To join you must be a registered site member and request group membership.', 'buddypress' );
		else 
			_e( 'This is a private group. Your membership request is awaiting approval from the group administrator.', 'buddypress' );			
	} else {
		_e( 'This is a hidden group and only invited members can join.', 'buddypress' );
	}
}


/***************************************************************************
 * Group Members Template Tags
 **/

class BP_Groups_Group_Members_Template {
	var $current_member = -1;
	var $member_count;
	var $members;
	var $member;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_group_count;
	
	function bp_groups_group_members_template( $group_id, $per_page, $max, $exclude_admins_mods, $exclude_banned ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		
		$this->members = BP_Groups_Member::get_all_for_group( $group_id, $this->pag_num, $this->pag_page, $exclude_admins_mods, $exclude_banned );
		
		if ( !$max || $max >= (int)$this->members['count'] )
			$this->total_member_count = (int)$this->members['count'];
		else
			$this->total_member_count = (int)$max;

		$this->members = $this->members['members'];
		
		if ( $max ) {
			if ( $max >= count($this->members) )
				$this->member_count = count($this->members);
			else
				$this->member_count = (int)$max;
		} else {
			$this->member_count = count($this->members);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'mlpage', '%#%' ),
			'format' => '',
			'total' => ceil( $this->total_member_count / $this->pag_num ),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_members() {
		if ( $this->member_count )
			return true;

		return false;
	}
	
	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];
		
		return $this->member;
	}
	
	function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}
	
	function members() { 
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_member() {
		global $member;

		$this->in_the_loop = true;
		$this->member = $this->next_member();

		if ( 0 == $this->current_member ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_members( $args = '' ) {
	global $members_template, $groups_template, $group_obj;
	
	$defaults = array(
		'group_id' => $group_obj->id,
		'per_page' => 10,
		'max' => false,
		'exclude_admins_mods' => true,
		'exclude_banned' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$groups_template )
		$groups_template->group = new BP_Groups_Group( $group_id );
		
	$members_template = new BP_Groups_Group_Members_Template( $group_id, $per_page, $max, $exclude_admins_mods, $exclude_banned );

	return $members_template->has_members();
}

function bp_group_members() {
	global $members_template;
	
	return $members_template->members();
}

function bp_group_the_member() {
	global $members_template;
	
	return $members_template->the_member();
}

function bp_group_member_avatar() {
	echo bp_get_group_member_avatar();
}
	function bp_get_group_member_avatar() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar', bp_core_get_avatar( $members_template->member->user_id, 1 ) );
	}

function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_group_member_avatar_mini( $width, $height );
}
	function bp_get_group_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar_mini', bp_core_get_avatar( $members_template->member->user_id, 1, $width, $height ) );
	}

function bp_group_member_name() {
	echo bp_get_group_member_name();
}
	function bp_get_group_member_name() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_name', bp_core_get_user_displayname( $members_template->member->user_id ) );
	}

function bp_group_member_url() {
	echo bp_get_group_member_url();
}
	function bp_get_group_member_url() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_url', bp_core_get_userlink( $members_template->member->user_id, false, true ) );
	}

function bp_group_member_link() {
	echo bp_get_group_member_link();
}
	function bp_get_group_member_link() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_link', bp_core_get_userlink( $members_template->member->user_id ) );
	}
	
function bp_group_member_is_banned() {
	echo bp_get_group_member_is_banned();
}
	function bp_get_group_member_is_banned() {
		global $members_template, $groups_template;

		return apply_filters( 'bp_get_group_member_is_banned', groups_is_user_banned( $members_template->member->user_id, $groups_template->group->id ) );
	}

function bp_group_member_joined_since() {
	echo bp_get_group_member_joined_since();
}
	function bp_get_group_member_joined_since() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_joined_since', bp_core_get_last_activity( strtotime( $members_template->member->date_modified ), __( 'joined %s ago', 'buddypress') ) );
	}
	

function bp_group_member_id() {
	echo bp_get_group_member_id();
}
	function bp_get_group_member_id() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_id', $members_template->member->user_id );
	}

function bp_group_member_needs_pagination() {
	global $members_template;

	if ( $members_template->total_member_count > $members_template->pag_num )
		return true;
	
	return false;
}

function bp_group_pag_id() {
	echo bp_get_group_pag_id();
}
	function bp_get_group_pag_id() {
		global $bp;

		return apply_filters( 'bp_get_group_pag_id', 'pag' );
	}


function bp_group_member_pagination() {
	echo bp_get_group_member_pagination();
	wp_nonce_field( 'bp_groups_member_list', '_member_pag_nonce' );
}
	function bp_get_group_member_pagination() {
		global $members_template;
		return apply_filters( 'bp_get_group_member_pagination', $members_template->pag_links );
	}

function bp_group_member_pagination_count() {
	echo bp_get_group_member_pagination_count();
}
	function bp_get_group_member_pagination_count() {
		global $members_template;

		$from_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$to_num = ( $from_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $from_num + ( $members_template->pag_num - 1 ); 

		return apply_filters( 'bp_get_group_member_pagination_count', sprintf( __( 'Viewing members %d to %d (of %d members)', 'buddypress' ), $from_num, $to_num, $members_template->total_member_count ) );  
	}

function bp_group_member_admin_pagination() {
	echo bp_get_group_member_admin_pagination();
	wp_nonce_field( 'bp_groups_member_admin_list', '_member_admin_pag_nonce' );
}
	function bp_get_group_member_admin_pagination() {
		global $members_template;
		
		return $members_template->pag_links;
	}

/********************************************************************************
 * Site Groups Template Tags
 **/

class BP_Groups_Site_Groups_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_group_count;
	
	function bp_groups_site_groups_template( $type, $per_page, $max ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['gpage'] ) ? intval( $_REQUEST['gpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
				
		if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] && $type != 'random' ) {
			$this->groups = BP_Groups_Group::search_groups( $_REQUEST['s'], $this->pag_num, $this->pag_page );
		} else if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] ) {
			$this->groups = BP_Groups_Group::get_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );
		} else {
			switch ( $type ) {
				case 'random':
					$this->groups = BP_Groups_Group::get_random( $this->pag_num, $this->pag_page );
					break;
				
				case 'newest':
					$this->groups = BP_Groups_Group::get_newest( $this->pag_num, $this->pag_page );
					break;

				case 'popular':
					$this->groups = BP_Groups_Group::get_popular( $this->pag_num, $this->pag_page );
					break;	
				
				case 'active': default:
					$this->groups = BP_Groups_Group::get_active( $this->pag_num, $this->pag_page );
					break;					
			}
		}
		
		if ( !$max || $max >= (int)$this->groups['total'] )
			$this->total_group_count = (int)$this->groups['total'];
		else
			$this->total_group_count = (int)$max;

		$this->groups = $this->groups['groups'];
		
		if ( $max ) {
			if ( $max >= count($this->groups) )
				$this->group_count = count($this->groups);
			else
				$this->group_count = (int)$max;
		} else {
			$this->group_count = count($this->groups);
		}
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'gpage', '%#%' ),
			'format' => '',
			'total' => ceil( (int) $this->total_group_count / (int) $this->pag_num ),
			'current' => (int) $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));		
	}
	
	function has_groups() {
		if ( $this->group_count )
			return true;
		
		return false;
	}
	
	function next_group() {
		$this->current_group++;
		$this->group = $this->groups[$this->current_group];
		
		return $this->group;
	}
	
	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}
	
	function groups() { 
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_group() {
		global $group;

		$this->in_the_loop = true;
		$this->group = $this->next_group();
		
		if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $this->group->group_id, 'bp' ) ) {
			$group = new BP_Groups_Group( $this->group->group_id, false, false );
			wp_cache_set( 'groups_group_nouserdata_' . $this->group->group_id, $group, 'bp' );
		}
		
		$this->group = $group;
		
		if ( 0 == $this->current_group ) // loop has just started
			do_action('loop_start');
	}
}

function bp_rewind_site_groups() {
	global $site_groups_template;
	
	$site_groups_template->rewind_groups();	
}

function bp_has_site_groups( $args = '' ) {
	global $site_groups_template;

	$defaults = array(
		'type' => 'active',
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	// type: active ( default ) | random | newest | popular
	
	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}
		
	$site_groups_template = new BP_Groups_Site_Groups_Template( $type, $per_page, $max );

	return $site_groups_template->has_groups();
}

function bp_site_groups() {
	global $site_groups_template;
	
	return $site_groups_template->groups();
}

function bp_the_site_group() {
	global $site_groups_template;
	
	return $site_groups_template->the_group();
}

function bp_site_groups_pagination_count() {
	global $bp, $site_groups_template;
	
	$from_num = intval( ( $site_groups_template->pag_page - 1 ) * $site_groups_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $site_groups_template->pag_num - 1 ) > $site_groups_template->total_group_count ) ? $site_groups_template->total_group_count : $from_num + ( $site_groups_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing group %d to %d (of %d groups)', 'buddypress' ), $from_num, $to_num, $site_groups_template->total_group_count ); ?> &nbsp;
	<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" /><?php
}

function bp_site_groups_pagination_links() {
	echo bp_get_site_groups_pagination_links();
}
	function bp_get_site_groups_pagination_links() {
		global $site_groups_template;
		
		return apply_filters( 'bp_get_site_groups_pagination_links', $site_groups_template->pag_links );
	}

function bp_the_site_group_id() {
	echo bp_get_the_site_group_id();
}
	function bp_get_the_site_group_id() {
		global $site_groups_template;
		
		return apply_filters( 'bp_get_the_site_group_id', $site_groups_template->group->id );
	}

function bp_the_site_group_avatar() {
	echo bp_get_the_site_group_avatar();
}
	function bp_get_the_site_group_avatar() {
		global $site_groups_template;

		return apply_filters( 'bp_the_site_group_avatar', bp_get_group_avatar( $site_groups_template->group ) );
	}

function bp_the_site_group_avatar_thumb() {
	echo bp_get_the_site_group_avatar_thumb();
}
	function bp_get_the_site_group_avatar_thumb() {
		global $site_groups_template;
		
		return apply_filters( 'bp_get_the_site_group_avatar_thumb', bp_get_group_avatar_thumb( $site_groups_template->group ) );
	}

function bp_the_site_group_avatar_mini() {
	echo bp_get_the_site_group_avatar_mini();
}
	function bp_get_the_site_group_avatar_mini() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_avatar_mini', bp_get_group_avatar_mini( $site_groups_template->group ) );
	}

function bp_the_site_group_link() {
	echo bp_get_the_site_group_link();
}
	function bp_get_the_site_group_link() {
		global $site_groups_template;
		
		return apply_filters( 'bp_get_the_site_group_link', bp_get_group_permalink( $site_groups_template->group ) );
	}

function bp_the_site_group_name() {
	echo bp_get_the_site_group_name();
}
	function bp_get_the_site_group_name() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_name', bp_get_group_name( $site_groups_template->group ) );
	}

function bp_the_site_group_last_active() {
	echo bp_get_the_site_group_last_active();
}
	function bp_get_the_site_group_last_active() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_last_active', sprintf( __( 'active %s ago', 'buddypress' ), bp_get_group_last_active( $site_groups_template->group ) ) );
	}

function bp_the_site_group_join_button() {
	global $site_groups_template;
	
	echo bp_group_join_button( $site_groups_template->group );
}

function bp_the_site_group_description() {
	echo bp_get_the_site_group_description();
}
	function bp_get_the_site_group_description() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_description', bp_get_group_description( $site_groups_template->group ) );	
	}

function bp_the_site_group_description_excerpt() {
	echo bp_get_the_site_group_description_excerpt();
}
	function bp_get_the_site_group_description_excerpt() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_description_excerpt', bp_create_excerpt( bp_get_group_description( $site_groups_template->group, false ), 35 ) );	
	}

function bp_the_site_group_date_created() {
	echo bp_get_the_site_group_date_created();	
}
	function bp_get_the_site_group_date_created() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_date_created', date( get_option( 'date_format' ), $site_groups_template->group->date_created ) );	
	}

function bp_the_site_group_member_count() {
	echo bp_get_the_site_group_member_count();
}
	function bp_get_the_site_group_member_count() {
		global $site_groups_template;

		if ( 1 == (int) $site_groups_template->group->total_member_count )
			return apply_filters( 'bp_get_the_site_group_member_count', sprintf( __( '%d member', 'buddypress' ), (int) $site_groups_template->group->total_member_count ) );
		else
			return apply_filters( 'bp_get_the_site_group_member_count', sprintf( __( '%d members', 'buddypress' ), (int) $site_groups_template->group->total_member_count ) );		
	}

function bp_the_site_group_type() {
	echo bp_get_the_site_group_type();
}
	function bp_get_the_site_group_type() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_type', bp_get_group_type( $site_groups_template->group ) );
	}

function bp_the_site_group_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['s'] ) . '" name="search_terms" />';
	}
	
	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . attribute_escape( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}
	
	if ( isset( $_REQUEST['groups_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['groups_search'] ) . '" name="search_terms" />';
	}
}

function bp_directory_groups_search_form() {
	global $bp; ?>
	<form action="<?php echo $bp->root_domain . '/' . groups_SLUG  . '/search/' ?>" method="post" id="search-groups-form">
		<label><input type="text" name="groups_search" id="groups_search" value="<?php if ( isset( $_GET['s'] ) ) { echo attribute_escape( $_GET['s'] ); } else { _e( 'Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e( 'Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Search anything...', 'buddypress' ) ?>';}" /></label>
		<input type="submit" id="groups_search_submit" name="groups_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>
<?php
}

/************************************************************************************
 * Membership Requests Template Tags
 **/

class BP_Groups_Membership_Requests_Template {
	var $current_request = -1;
	var $request_count;
	var $requests;
	var $request;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_request_count;
	
	function bp_groups_membership_requests_template( $group_id, $per_page, $max ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['mrpage'] ) ? intval( $_REQUEST['mrpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		
		$this->requests = BP_Groups_Group::get_membership_requests( $group_id, $this->pag_num, $this->pag_page );		

		if ( !$max || $max >= (int)$this->requests['total'] )
			$this->total_request_count = (int)$this->requests['total'];
		else
			$this->total_request_count = (int)$max;

		$this->requests = $this->requests['requests'];
		
		if ( $max ) {
			if ( $max >= count($this->requests) )
				$this->request_count = count($this->requests);
			else
				$this->request_count = (int)$max;
		} else {
			$this->request_count = count($this->requests);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'mrpage', '%#%' ),
			'format' => '',
			'total' => ceil( $this->total_request_count / $this->pag_num ),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_requests() {
		if ( $this->request_count )
			return true;
		
		return false;
	}
	
	function next_request() {
		$this->current_request++;
		$this->request = $this->requests[$this->current_request];
		
		return $this->request;
	}
	
	function rewind_requests() {
		$this->current_request = -1;
		if ( $this->request_count > 0 ) {
			$this->request = $this->requests[0];
		}
	}
	
	function requests() { 
		if ( $this->current_request + 1 < $this->request_count ) {
			return true;
		} elseif ( $this->current_request + 1 == $this->request_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_requests();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_request() {
		global $request;

		$this->in_the_loop = true;
		$this->request = $this->next_request();

		if ( 0 == $this->current_request ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_membership_requests( $args = '' ) {
	global $requests_template, $groups_template;

	$defaults = array(
		'group_id' => $groups_template->group->id,
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$requests_template = new BP_Groups_Membership_Requests_Template( $group_id, $per_page, $max );
	return $requests_template->has_requests();
}

function bp_group_membership_requests() {
	global $requests_template;
	
	return $requests_template->requests();
}

function bp_group_the_membership_request() {
	global $requests_template;
	
	return $requests_template->the_request();
}

function bp_group_request_user_avatar_thumb() {
	global $requests_template;
	
	echo apply_filters( 'bp_group_request_user_avatar_thumb', bp_core_get_avatar( $requests_template->request->user_id, 1 ) );
}

function bp_group_request_reject_link() {
	global $requests_template, $groups_template;	

	echo apply_filters( 'bp_group_request_reject_link', wp_nonce_url( bp_get_group_permalink( $groups_template->group ) . '/admin/membership-requests/reject/' . $requests_template->request->id, 'groups_reject_membership_request' ) );
}

function bp_group_request_accept_link() {
	global $requests_template, $groups_template;	

	echo apply_filters( 'bp_group_request_accept_link', wp_nonce_url( bp_get_group_permalink( $groups_template->group ) . '/admin/membership-requests/accept/' . $requests_template->request->id, 'groups_accept_membership_request' ) );
}

function bp_group_request_time_since_requested() {
	global $requests_template;	

	echo apply_filters( 'bp_group_request_time_since_requested', sprintf( __( 'requested %s ago', 'buddypress' ), bp_core_time_since( strtotime( $requests_template->request->date_modified ) ) ) );
}

function bp_group_request_comment() {
	global $requests_template;
	
	echo apply_filters( 'bp_group_request_comment', strip_tags( stripslashes( $requests_template->request->comments ) ) );
}

function bp_group_request_user_link() {
	global $requests_template;
	
	echo apply_filters( 'bp_group_request_user_link', bp_core_get_userlink( $requests_template->request->user_id ) );
}

?>