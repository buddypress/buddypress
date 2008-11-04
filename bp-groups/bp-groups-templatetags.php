<?php

class BP_Groups_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_group_count;
	
	function bp_groups_template( $user_id = null, $group_slug = null ) {
		global $bp;

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		if ( $bp['current_action'] == 'my-groups' || !$bp['current_action'] ) {
		
			$this->groups = groups_get_user_groups( $this->pag_page, $this->pag_num );
			$this->total_group_count = (int)$this->groups['count'];
			$this->groups = $this->groups['groups'];
			$this->group_count = count($this->groups);
		
		} else if ( $bp['current_action'] == 'invites' ) {
		
			$this->groups = groups_get_invites_for_user();
			$this->total_group_count = count($this->groups);
			$this->group_count = count($this->groups);
					
		} else if ( $group_slug ) {
		
			$this->groups = array( new BP_Groups_Group( BP_Groups_Group::get_id_from_slug($group_slug), true ) );
			$this->total_group_count = 1;
			$this->group_count = 1;
		
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
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

		if ( $this->current_group == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_groups() {
	global $groups_template, $bp;
	global $is_single_group, $group_obj;
		
	if ( !$is_single_group ) {
		$groups_template = new BP_Groups_Template( $bp['current_userid'] );
	} else {
		$groups_template = new BP_Groups_Template( $bp['current_userid'], $group_obj->slug );		
	}
	
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

function bp_group_id( $echo = true ) {
	global $groups_template;
	
	if ( $echo )
		echo $groups_template->group->id;
	else
		return $groups_template->group->id;
}

function bp_group_name( $echo = true ) {
	global $groups_template;
	
	if ( $echo )
		echo $groups_template->group->name;
	else
		return $groups_template->group->name; 
}

function bp_group_type() {
	global $groups_template;
	echo ucwords($groups_template->group->status) . ' ' . __('Group', 'buddypress');	
}

function bp_group_avatar() {
	global $groups_template;
	
	?><img src="<?php echo $groups_template->group->avatar_full ?>" class="avatar" alt="<?php echo $groups_template->group->name ?> Avatar" /><?php
}

function bp_group_avatar_thumb() {
	global $groups_template;
	
	?><img src="<?php echo $groups_template->group->avatar_thumb ?>" class="avatar" alt="<?php echo $groups_template->group->name ?> Avatar" /><?php
}


function bp_group_permalink( $group_obj = false, $echo = true ) {
	global $groups_template, $bp, $current_blog;

	if ( !$group_obj )
		$group_obj = $groups_template->group;
	
	if ( $echo )
		echo $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug;
	else
		return $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug;
}

function bp_group_admin_permalink( $group_obj = false, $echo = true ) {
	global $groups_template, $bp, $current_blog;

	if ( !$group_obj )
		$group_obj = $groups_template->group;
	
	if ( $echo )
		echo $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug . '/admin';
	else
		return $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug . '/admin';	
}

function bp_group_slug() {
	global $groups_template;
	echo $groups_template->group->slug;
}

function bp_group_description() {
	global $groups_template;
	
	$content = $groups_template->group->description;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	echo $content;
}

function bp_group_description_editable() {
	global $groups_template;
	
	echo $groups_template->group->description;
}

function bp_group_description_excerpt() {
	global $groups_template;
	echo bp_create_excerpt( $groups_template->group->description, 20 );	
}

function bp_group_news() {
	global $groups_template;

	$content = $groups_template->group->news;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	echo $content;
}

function bp_group_news_editable() {
	global $groups_template;

	echo $groups_template->group->news;
}

function bp_group_public_status() {
	global $groups_template;
	
	if ( $groups_template->group->is_public ) {
		_e('Public', 'buddypress');
	} else {
		_e('Private', 'buddypress');
	}
}
	function bp_group_is_public() {
		global $groups_template;
		return $groups_template->group->is_public;
	}

function bp_group_invitation_status() {
	global $groups_template;
	
	if ( $groups_template->group->is_invitation_only ) {
		_e('Invitation Only', 'buddypress');
	} else {
		_e('Open', 'buddypress');
	}
}
	function bp_group_is_invitation_only() {
		global $groups_template;
		return $groups_template->group->is_invitation_only;
	}

function bp_group_date_created() {
	global $groups_template;
	
	echo date( get_option( 'date_format' ), strtotime( $groups_template->group->date_created ) );
}

function bp_group_list_admins() {
	global $groups_template;

	$admins = &$groups_template->group->admins;
?>
	<ul id="group-admins">
	<?php for ( $i = 0; $i < count($admins); $i++ ) { ?>
		<li>
			<?php echo $admins[$i]->user->avatar_thumb ?>
			<h5><?php echo $admins[$i]->user->user_link ?></h5>
			<span class="activity"><?php echo $admins[$i]->user_title ?></span>
			<hr />
		</li>
	<?php } ?>
	</ul>
<?php
}

function bp_group_all_members_permalink() {
	global $groups_template, $bp;
	echo bp_group_permalink( false, true ) . '/' . MEMBERS_SLUG ;
}

function bp_group_random_members() {
	global $groups_template;
	
	$members = &$groups_template->group->random_members;
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

function bp_group_search_form() {
	global $groups_template, $bp;

	if ( $bp['current_action'] == 'my-groups' || !$bp['current_action'] ) {
		$action = $bp['loggedin_domain'] . $bp['groups']['slug'] . '/my-groups/search/';
		$label = __('Filter Groups', 'buddypress');
		$type = 'group';
	} else {
		$action = $bp['loggedin_domain'] . $bp['groups']['slug'] . '/group-finder/search/';
		$label = __('Find a Group', 'buddypress');
		$type = 'groupfinder';
		$value = $bp['action_variables'][1];
	}

	if ( !$groups_template->group_count && $bp['current_action'] != 'group-finder' ) {
		$disabled = ' disabled="disabled"';
	}
?>
	<form action="<?php echo $action ?>" id="group-search-form" method="post">
		<label for="<?php echo $type ?>-search-box" id="<?php echo $type ?>-search-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp['groups']['image_base'] ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></label>
		<input type="search" name="<?php echo $type ?>-search-box" id="<?php echo $type ?>-search-box" value="<?php echo $value ?>"<?php echo $disabled ?> />
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field( $type . '_search' );
		?>
	</form>
<?php
}

function bp_group_pagination() {
	global $groups_template;
	echo $groups_template->pag_links;
}

function bp_total_group_count() {
	global $groups_template;
	
	echo $groups_template->total_group_count;
}

function bp_group_total_members() {
	global $groups_template;
	
	echo $groups_template->group->total_member_count;
}

function bp_group_photos_enabled() {
	global $groups_template;
	
	if ( $groups_template->group->enable_photos )
		return true;
	
	return false;
}

function bp_group_show_wire_setting() {
	global $groups_template;
	
	if ( $groups_template->group->enable_wire )
		echo ' checked="checked"';
}

function bp_group_show_forum_setting() {
	global $groups_template;
	
	if ( $groups_template->group->enable_forum )
		echo ' checked="checked"';
}

function bp_group_show_photos_setting() {
	global $groups_template;
	
	if ( $groups_template->group->enable_photos )
		echo ' checked="checked"';	
}

function bp_group_show_photos_upload_setting( $permission ) {
	global $groups_template;
	
	if ( $permission == 'admin' && $groups_template->group->photos_admin_only )
		echo ' checked="checked"';
	
	if ( $permission == 'member' && !$groups_template->group->photos_admin_only )
		echo ' checked="checked"';
}

function bp_group_show_status_setting( $setting ) {
	global $groups_template;
	
	if ( $setting == $groups_template->group->status )
		echo ' checked="checked"';
}

function bp_group_admin_tabs() {
	global $bp, $groups_template;
	
	$current_tab = $bp['action_variables'][0];
?>
	<li<?php if ( $current_tab == 'edit-details' || $current_tab == '' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/edit-details"><?php _e('Edit Details', 'buddypress') ?></a></li>
	<li<?php if ( $current_tab == 'group-settings' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/group-settings"><?php _e('Group Settings', 'buddypress') ?></a></li>
	<!--<li<?php if ( $current_tab == 'manage-members' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/manage-members"><?php _e('Manage Members', 'buddypress') ?></a></li>-->
	<!--<li<?php if ( $current_tab == 'delete-group' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/delete-group"><?php _e('Delete Group', 'buddypress') ?></a></li>-->
<?php
	do_action( 'bp_groups_admin_tabs' );
}

function bp_group_admin_form_action( $page ) {
	global $bp, $groups_template;
	
	echo bp_group_permalink( $group, false ) . '/admin/' . $page;
}

function bp_group_creation_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( $create_group_step == '1' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/create/step/1">1. <?php _e('Group Details', 'buddypress') ?></a></li>
	<li<?php if ( $create_group_step == '2' ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 0 ) { ?><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/create/step/2">2. <?php _e('Group Settings', 'buddypress') ?></a><?php } else { ?><span>2. <?php _e('Group Settings', 'buddypress') ?></span><?php } ?></li>
	<li<?php if ( $create_group_step == '3' ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 1 ) { ?><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/create/step/3">3. <?php _e('Group Avatar', 'buddypress') ?></a><?php } else { ?><span>3. <?php _e('Group Avatar', 'buddypress') ?></span><?php } ?></li>
	<li<?php if ( $create_group_step == '4' ) : ?> class="current"<?php endif; ?>><?php if ( $completed_to_step > 2 ) { ?><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/create/step/4">4. <?php _e('Invite Members', 'buddypress') ?></a><?php } else { ?><span>4. <?php _e('Invite Members', 'buddypress') ?></span><?php } ?></li>
<?php
	do_action( 'bp_groups_creation_tabs' );
}

function bp_group_creation_stage_title() {
	global $create_group_step;
	
	switch( $create_group_step ) {
		case '1':
			echo '<span>&mdash; ' . __('Group Details', 'buddypress') . '</span>';
		break;
		
		case '2':
			echo '<span>&mdash; ' . __('Group Settings', 'buddypress') . '</span>';		
		break;
		
		case '3':
			echo '<span>&mdash; ' . __('Group Avatar', 'buddypress') . '</span>';
		break;
		
		case '4':
			echo '<span>&mdash; ' . __('Invite Members', 'buddypress') . '</span>';
		break;
	}
}

function bp_group_create_form() {
	global $bp, $create_group_step, $completed_to_step;
	global $group_obj, $invites;

?>
	<form action="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/create/step/<?php echo $create_group_step ?>" method="post" id="create-group-form" class="standard-form" enctype="multipart/form-data">
	<?php switch( $create_group_step ) {
		case '1': ?>
			<label for="group-name">* <?php _e('Group Name', 'buddypress') ?></label>
			<input type="text" name="group-name" id="group-name" value="<?php echo ( $group_obj ) ? $group_obj->name : $_POST['group-name']; ?>" />
		
			<label for="group-desc">* <?php _e('Group Description', 'buddypress') ?></label>
			<textarea name="group-desc" id="group-desc"><?php echo ( $group_obj ) ? $group_obj->description : $_POST['group-desc']; ?></textarea>
		
			<label for="group-news">* <?php _e('Recent News', 'buddypress') ?></label>
			<textarea name="group-news" id="group-news"><?php echo ( $group_obj ) ? $group_obj->news : $_POST['group-news']; ?></textarea>
			
			<p><input type="submit" value="<?php _e('Create Group and Continue', 'buddypress') ?> &raquo;" id="save" name="save" /></p>
		<?php break; ?>
		
		<?php case '2': ?>
			<?php if ( $completed_to_step > 0 ) { ?>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php if ( $group_obj->enable_wire ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
				</div>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php if ( $group_obj->enable_forum ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
				</div>
				<div class="checkbox with-suboptions">
					<label><input type="checkbox" name="group-show-photos" id="group-show-photos" value="1"<?php if ( $group_obj->enable_photos ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable photo gallery', 'buddypress') ?></label>
					<div class="sub-options"<?php if ( !$group_obj->enable_photos ) { ?> style="display: none;"<?php } ?>>
						<label><input type="radio" name="group-photos-status" value="all"<?php if ( !$group_obj->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('All members can upload photos', 'buddypress') ?></label>
						<label><input type="radio" name="group-photos-status" value="admins"<?php if ( $group_obj->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('Only group admins can upload photos', 'buddypress') ?></label>
					</div>
				</div>
			
				<h3><?php _e('Privacy Options', 'buddypress'); ?></h3>
			
				<div class="radio">
					<label><input type="radio" name="group-status" value="public"<?php if ( $group_obj->status == 'public' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is an open group', 'buddypress') ?></strong><br /><?php _e('This group will be free to join and will appear in group search results.', 'buddypress'); ?></label>
					<label><input type="radio" name="group-status" value="private"<?php if ( $group_obj->status == 'private' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is a closed group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join but will still appear in group search results.', 'buddypress'); ?></label>
					<label><input type="radio" name="group-status" value="hidden"<?php if ( $group_obj->status == 'hidden' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is a hidden group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join and will only be visible to invited members. It will not appear in search results or on member profiles.', 'buddypress'); ?></label>
				</div>

				<p><input type="submit" value="<?php _e('Save and Continue', 'buddypress') ?> &raquo;" id="save" name="save" /></p>
			<?php } else { ?>
				<div id="message" class="info">
					<p>Please complete all previous steps first.</p>
				</div>
			<?php } ?>
		<?php break; ?>
		
		<?php case '3': ?>
			<?php if ( $completed_to_step > 1 ) { ?>
				<div class="left-menu">
					<?php if ( $group_obj->avatar_full ) { ?>
						<img src="<?php echo $group_obj->avatar_full ?>" alt="Group Avatar" class="avatar" />
					<?php } else { ?>
						<img src="<?php echo $bp['groups']['image_base'] . '/none.gif' ?>" alt="No Group Avatar" class="avatar" />
					<?php } ?>
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
						<input type="submit" value="<?php _e('Skip', 'buddypress') ?> &raquo;" id="skip" name="skip" />
					</div>
				</div>
			<?php } else { ?>
				<div id="message" class="info">
					<p>Please complete all previous steps first.</p>
				</div>
			<?php } ?>
		<?php break; ?>
		<?php case '4': ?>
			<?php 
			if ( $completed_to_step > 2 ) {
				if ( function_exists('friends_install') ) {
					if ( friends_get_friend_count_for_user( $bp['loggedin_userid'] ) ) {
						bp_group_send_invite_form( $group_obj );
					} else {
						$group_link = bp_group_permalink( $group, false );
						?>
						<div id="message" class="info">
							<p><?php _e( 'Once you build up your friends list you will be able to invite friends to join your group.', 'buddypress' ) ?></p>
						</div>
						<p><input type="button" value="<?php _e('Finish', 'buddypress') ?> &raquo;" id="save" name="save" onclick="location.href='<?php echo $group_link ?>'" /></p>
						<?php
					}
				}
			} else { ?>
				<div id="message" class="info">
					<p>Please complete all previous steps first.</p>
				</div>
		<?php } ?>
		<?php break; ?>
	<?php } ?>
	</form>
<?php
}
function bp_group_list_friends() {
	global $bp, $group_obj, $invites;
	
	if ( bp_exists('friends') ) {
		$friends = friends_get_friends_list( $bp['loggedin_userid'] );	
		$invites = groups_get_invites_for_group($group_obj->id);
?>
		<div id="invite-list">
			<ul>
				<?php for ( $i = 0; $i < count( $friends ); $i++ ) {
					if ( in_array( $friends[$i]['id'], $invites ) ) {
						$checked = ' checked="checked"';
					} else {
						$checked = '';
					} ?>
					
				<li><input<?php echo $checked ?> type="checkbox" name="friends[]" id="f-<?php echo $friends[$i]['id'] ?>" value="<?php echo $friends[$i]['id'] ?>" /> <?php echo $friends[$i]['full_name']; ?></li>
				<?php } ?>
			</ul>
		</div>
<?php
	}
}

function bp_group_is_member() {
	global $bp, $groups_template;
	
	if ( BP_Groups_Member::check_is_member( $bp['loggedin_userid'], $groups_template->group->id ) )
		return true;
	
	return false;
}

function bp_group_list_members() {
	global $groups_template, $bp;

	for ( $i = 0; $i < count($groups_template->group->user_dataset); $i++ ) {
		$member = new BP_Groups_Member( $groups_template->group->user_dataset[$i]->user_id, $groups_template->group->id );

		?><li id="uid-<?php echo $user->id ?>">
			<?php echo $member->user->avatar_thumb ?>
			<p><?php echo $member->user->user_link ?> <?php if ( $member->user_title ) { ?><?php echo '<span class="small">- ' . $member->user_title . '</span>' ?><?php } ?></p>
			<span class="activity">joined <?php echo bp_core_time_since( strtotime($member->date_modified) ) ?> ago</span>
	<?php if ( bp_exists('friends') && function_exists('bp_add_friend_button') ) { ?>
			<div class="action">
				<?php bp_add_friend_button( $member->user->id ) ?>
			</div>
	<?php } ?>
		   </li>
		<?php
	}
}

function bp_group_accept_invite_link() {
	global $groups_template, $bp;
	
	echo $bp['loggedin_domain'] . $bp['groups']['slug'] . '/invites/accept/' . $groups_template->group->id;	
}

function bp_group_reject_invite_link() {
	global $groups_template, $bp;
	
	echo $bp['loggedin_domain'] . $bp['groups']['slug'] . '/invites/reject/' . $groups_template->group->id;
}

function bp_group_leave_confirm_link() {
	global $groups_template, $bp;
	
	echo bp_group_permalink( false, true ) . '/leave-group/yes';	
}

function bp_group_leave_reject_link() {
	global $groups_template, $bp;
	
	echo bp_group_permalink( false, true );
}

function bp_group_send_invite_form( $group_obj = null ) {
	global $bp, $groups_template, $invites;
	
	if ( !$group_obj )
		$group_obj =& $groups_template->group;
?>
	<div class="left-menu">
		<h4>Select Friends <img id="ajax-loader" src="<?php echo $bp['groups']['image_base'] ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></h4>
		<?php bp_group_list_friends() ?>
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field( 'invite_user' );
		?>
		<input type="hidden" name="group_id" id="group_id" value="<?php echo $group_obj->id ?>" />
	</div>

	<div class="main-column">
		
		<div id="message" class="info">
			<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
		</div>
				
		<ul id="friend-list">
			<?php for( $i = 0; $i < count($invites); $i++ ) {
				$user = new BP_Core_User( $invites[$i] ); ?>
		
				<li id="uid-<?php echo $user->id ?>">
					<?php echo $user->avatar_thumb ?>
					<h4><?php echo $user->user_link ?></h4>
					<span class="activity">active <?php echo $user->last_active ?> ago</span>
					<div class="action">
						<a class="remove" href="<?php echo site_url() . $bp['groups']['slug'] . '/' . $group_obj->id . '/invites/remove/' . $user->id ?>" id="uid-<?php echo $user->id ?>">Remove Invite</a> 
					</div>
				</li>
			<?php } // end for ?>
		</ul>

		<input type="submit" value="<?php _e('Finish', 'buddypress') ?> &raquo;" id="save" name="save" />

	</div>
<?php
}

function bp_group_send_invite_form_action() {
	global $groups_template, $bp;
	
	echo bp_group_permalink( false, true ) . '/send-invites/send';
}

function bp_group_join_button() {
	global $bp, $groups_template;
	
	if ( is_user_logged_in() && !BP_Groups_Member::check_is_member( $bp['loggedin_userid'], $groups_template->group->id ) ) {
		echo '<a class="join-group" href="' . bp_group_permalink( false, false ) . '/join">' . __('Join Group', 'buddypress') . '</a>';
	}
}

function bp_groups_random_groups() {
	global $bp;
	
	$group_ids = BP_Groups_Member::get_random_groups( $bp['current_userid'] );
?>	
	<div class="info-group">
		<h4><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> (<?php echo BP_Groups_Member::total_group_count() ?>) <a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></h4>
		<?php if ( $group_ids ) { ?>
			<ul class="horiz-gallery">
			<?php for ( $i = 0; $i < count( $group_ids ); $i++ ) { ?>
				<?php $group = new BP_Groups_Group( $group_ids[$i], false, false ); ?>
				<li>
					<a href="<?php echo bp_group_permalink( $group, false ) ?>"><img src="<?php echo $group->avatar_thumb; ?>" class="avatar" alt="Group Avatar" /></a>
					<h5><a href="<?php echo bp_group_permalink( $group, false ) ?>"><?php echo $group->name ?></a></h5>
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

?>