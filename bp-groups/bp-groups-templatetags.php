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
	
	var $single_group = false;
	
	var $sort_by;
	var $order;
	
	function bp_groups_template( $user_id = null, $group_slug = null, $groups_per_page = 10 ) {
		global $bp, $current_user;
		
		if ( !$user_id )
			$user_id = $current_user->id;
		
		$this->pag_page = isset( $_REQUEST['fpage'] ) ? intval( $_REQUEST['fpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $groups_per_page;
		
		if ( ( $bp['current_action'] == 'my-groups' && $_REQUEST['group-filter-box'] == '' ) || ( !$bp['current_action'] && !isset($_REQUEST['page']) && $_REQUEST['group-filter-box'] == '' ) ) {

			$order = $bp['action_variables'][0];
			
			if ( $order == 'recently-joined' ) {
				$this->groups = groups_get_recently_joined_for_user( $bp['current_userid'], $this->pag_num, $this->pag_page );
			} else if ( $order == 'most-popular' ) {
				$this->groups = groups_get_most_popular_for_user( $bp['current_userid'], $this->pag_num, $this->pag_page );				
			} else if ( $order == 'admin-of' ) {
				$this->groups = groups_get_user_is_admin_of( $bp['current_userid'], $this->pag_num, $this->pag_page );				
			} else if ( $order == 'mod-of' ) {
				$this->groups = groups_get_user_is_mod_of( $bp['current_userid'], $this->pag_num, $this->pag_page );				
			} else if ( $order == 'alphabetically' ) {
				$this->groups = groups_get_alphabetically_for_user( $bp['current_userid'], $this->pag_num, $this->pag_page );	
			} else {
				$this->groups = groups_get_recently_active_for_user( $bp['current_userid'], $this->pag_num, $this->pag_page );
			}

			$this->total_group_count = (int)$this->groups['total'];
			$this->groups = $this->groups['groups'];
			$this->group_count = count($this->groups);
		
		} else if ( ( $bp['current_action'] == 'my-groups' && $_REQUEST['group-filter-box'] != '' ) || ( !$bp['current_action'] && !isset($_REQUEST['page']) && $_REQUEST['group-filter-box'] != '' ) ) {

			$this->groups = groups_filter_user_groups( $_REQUEST['group-filter-box'], $this->pag_num, $this->pag_page );
			$this->total_group_count = (int)$this->groups['total'];
			$this->groups = $this->groups['groups'];
			$this->group_count = count($this->groups);
		
		} else if ( $bp['current_action'] == 'invites' ) {
		
			$this->groups = groups_get_invites_for_user();
			$this->total_group_count = count($this->groups);
			$this->group_count = count($this->groups);
		
		} else if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'groups_admin_settings' ) {
			
			$this->sort_by = $_REQUEST['sortby'];
			$this->order = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'ASC';
			
			if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] != '' ) {
				$this->groups = groups_search_groups( $_REQUEST['s'], $this->pag_num, $this->pag_page, $this->sort_by, $this->order );
				$this->total_group_count = (int)$this->groups['total'];
				$this->groups = $this->groups['groups'];
				$this->group_count = count($this->groups);
			} else {
				$this->groups = BP_Groups_Group::get_all( $this->pag_num, $this->pag_page, false, $this->sort_by, $this->order );
				$this->total_group_count = count(BP_Groups_Group::get_all( false )); // TODO: not ideal
				$this->group_count = count($this->groups);
			}
			
		} else if ( $group_slug ) {
			
			$this->single_group = true;
			
			$group = new stdClass();
			$group->group_id = BP_Groups_Group::get_id_from_slug($group_slug);
			
			$this->groups = array( $group );
			$this->total_group_count = 1;
			$this->group_count = 1;
		
		} else {
			
			$this->groups = groups_get_user_groups( $bp['current_userid'], $this->pag_num, $this->pag_page );
			$this->total_group_count = (int)$this->groups['total'];
			$this->groups = $this->groups['groups'];
			$this->group_count = count($this->groups);	
					
		}
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'fpage' => '%#%', 'num' => $this->pag_num, 's' => $_REQUEST['s'], 'sortby' => $this->sort_by, 'order' => $this->order ) ),
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
		if ( $this->single_group )
			$this->group = new BP_Groups_Group( $this->group->group_id, true );
		else
			$this->group = new BP_Groups_Group( $this->group->group_id, false );

		if ( $this->current_group == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_groups( $groups_per_page = 10 ) {
	global $groups_template, $bp;
	global $is_single_group, $group_obj;
	
	if ( !$is_single_group ) {
		$groups_template = new BP_Groups_Template( $bp['current_userid'], false, $groups_per_page );
	} else {
		$groups_template = new BP_Groups_Template( $bp['current_userid'], $group_obj->slug, $groups_per_page );		
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

function bp_group_is_visible() {
	global $bp, $groups_template;
	
	if ( $groups_template->group->status == 'public' ) {
		return true;
	} else {
		if ( groups_is_user_member( $bp['loggedin_userid'], $groups_template->group->id ) ) {
			return true;
		}
	}
	
	return false;
}

function bp_group_id( $echo = true ) {
	global $groups_template;
	
	if ( $echo )
		echo apply_filters( 'bp_group_id', $groups_template->group->id );
	else
		return apply_filters( 'bp_group_id', $groups_template->group->id );
}

function bp_group_name( $echo = true ) {
	global $groups_template;
	
	if ( $echo )
		echo apply_filters( 'bp_group_name', $groups_template->group->name );
	else
		return apply_filters( 'bp_group_name', $groups_template->group->name ); 
}

function bp_group_type() {
	global $groups_template;
	echo apply_filters( 'bp_group_type', ucwords($groups_template->group->status) . ' ' . __('Group', 'buddypress') );	
}

function bp_group_avatar() {
	global $groups_template;
	
	?><img src="<?php echo $groups_template->group->avatar_full ?>" class="avatar" alt="<?php echo $groups_template->group->name ?> Avatar" /><?php
}

function bp_group_avatar_thumb() {
	global $groups_template;

	?><img src="<?php echo $groups_template->group->avatar_thumb ?>" class="avatar" alt="<?php echo $groups_template->group->name ?> Avatar" /><?php
}

function bp_group_avatar_mini() {
	global $groups_template;
	
	?><img src="<?php echo $groups_template->group->avatar_thumb ?>" width="30" height="30" class="avatar" alt="<?php echo $groups_template->group->name ?> Avatar" /><?php
}

function bp_group_last_active( $echo = true ) {
	global $groups_template;
	
	$last_active = groups_get_groupmeta( $groups_template->group->id, 'last_activity' );
	
	if ( $last_active == '' )
		_e( 'not yet active', 'buddypress' );
	else
		echo apply_filters( 'bp_group_last_active', bp_core_time_since( $last_active ) );
}

function bp_group_permalink( $group_obj = false, $echo = true ) {
	global $groups_template, $bp, $current_blog;

	if ( !$group_obj )
		$group_obj = $groups_template->group;
	
	if ( $echo )
		echo apply_filters( 'bp_group_permalink', $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug );
	else
		return apply_filters( 'bp_group_permalink', $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug );
}

function bp_group_admin_permalink( $group_obj = false, $echo = true ) {
	global $groups_template, $bp, $current_blog;

	if ( !$group_obj )
		$group_obj = $groups_template->group;
	
	if ( $echo )
		echo apply_filters( 'bp_group_admin_permalink', $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug . '/admin' );
	else
		return apply_filters( 'bp_group_admin_permalink', $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $group_obj->slug . '/admin' );	
}

function bp_group_slug() {
	global $groups_template;
	echo apply_filters( 'bp_group_slug', $groups_template->group->slug );
}

function bp_group_description() {
	global $groups_template;

	echo apply_filters( 'bp_group_description', stripslashes($groups_template->group->description) );
}

function bp_group_description_editable() {
	global $groups_template;
	
	echo apply_filters( 'bp_group_description_editable', $groups_template->group->description );
}

function bp_group_description_excerpt() {
	global $groups_template;
	
	echo apply_filters( 'bp_group_description_excerpt', bp_create_excerpt( $groups_template->group->description, 20 ) );	
}

function bp_group_news() {
	global $groups_template;

	echo apply_filters( 'bp_group_news', stripslashes($groups_template->group->news) );
}

function bp_group_news_editable() {
	global $groups_template;

	echo apply_filters( 'bp_group_news_editable', $groups_template->group->news );
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
		return apply_filters( 'bp_group_is_public', $groups_template->group->is_public );
	}


function bp_group_date_created() {
	global $groups_template;
	
	echo apply_filters( 'bp_group_date_created', date( get_option( 'date_format' ), $groups_template->group->date_created ) );
}

function bp_group_list_admins( $full_list = true ) {
	global $groups_template;
	
	if ( !$admins = &$groups_template->group->admins )
		$admins = $groups_template->group->get_administrators();

	if ( $admins ) {
		if ( $full_list ) { ?>
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
		<?php } else { ?>
			<?php for ( $i = 0; $i < count($admins); $i++ ) { ?>
				<?php echo $admins[$i]->user->user_link ?>
			<?php } ?>
		<?php } ?>
	<?php } else { ?>
		<span class="activity">No Admin</span>
	<?php } ?>
	
<?php
}

function bp_group_list_mods( $full_list = true ) {
	global $groups_template;
	
	$group_mods = groups_get_group_mods( $groups_template->group->id );
	
	if ( $group_mods ) {
		if ( $full_list ) { ?>
			<ul id="group-mods" class="mods-list">
			<?php for ( $i = 0; $i < count($group_mods); $i++ ) { ?>
				<li>
					<?php echo bp_core_get_avatar( $group_mods[$i]->user_id, 1, 50, 50 ) ?>
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
		<span class="activity">No Admin</span>
	<?php } ?>
	
<?php
}

function bp_group_all_members_permalink( $echo = true ) {
	global $groups_template, $bp;
	
	if ( $echo )
		echo apply_filters( 'bp_group_all_members_permalink', bp_group_permalink( false, true ) . '/' . MEMBERS_SLUG );
	else
		return apply_filters( 'bp_group_all_members_permalink', bp_group_permalink( false, false ) . '/' . MEMBERS_SLUG );
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

function bp_group_active_forum_topics( $total_topics = 3 ) {
	global $groups_template, $forum_template;
	
	$forum_id = groups_get_groupmeta( $groups_template->group->id, 'forum_id' );

	if ( $forum_id && $forum_id != '' ) {
		if ( function_exists( 'bp_forums_setup' ) ) {
			$latest_topics = bp_forums_get_topics( $forum_id, $total_topics, 1 );
		
			if ( $latest_topics ) { ?>
				<ul class="item-list" id="recent-forum-topics"><?php
				foreach( $latest_topics as $topic ) {
					$forum_template->topic = (object)$topic; ?>
					<li>
						<div class="avatar">
							<?php bp_the_topic_poster_avatar() ?>
						</div>
				
						<a href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_title() ?></a> 
						<span class="small">- <?php bp_the_topic_total_post_count() ?> </span>
						<p><span class="activity"><?php echo sprintf( __( 'updated %s ago', 'buddypress' ), bp_the_topic_time_since_last_post( false ) ) ?><span></p>
				
						<div class="latest-post">
							<?php _e( 'Latest by', 'buddypress' ) ?> <?php bp_the_topic_last_poster_name() ?>:
							<?php bp_the_topic_latest_post_excerpt() ?>
						</div>
					</li>
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

	if ( $bp['current_action'] == 'my-groups' || !$bp['current_action'] ) {
		$action = $bp['loggedin_domain'] . $bp['groups']['slug'] . '/my-groups/search/';
		$label = __('Filter Groups', 'buddypress');
		$name = 'group-filter-box';
	} else {
		$action = $bp['loggedin_domain'] . $bp['groups']['slug'] . '/group-finder/search/';
		$label = __('Find a Group', 'buddypress');
		$name = 'groupfinder-search-box';
		$value = $bp['action_variables'][0];
	}
?>
	<form action="<?php echo $action ?>" id="group-search-form" method="post">
		<label for="<?php echo $name ?>" id="<?php echo $name ?>-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp['groups']['image_base'] ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></label>
		<input type="search" name="<?php echo $name ?>" id="<?php echo $name ?>" value="<?php echo $value ?>"<?php echo $disabled ?> />
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field( $name );
		?>
	</form>
<?php
}

function bp_group_show_no_groups_message() {
	global $bp;
	
	if ( !groups_total_groups_for_user( $bp['current_userid'] ) )
		return true;
		
	return false;
}

function bp_group_pagination() {
	global $groups_template;
	echo apply_filters( 'bp_group_pagination', $groups_template->pag_links );
}

function bp_total_group_count() {
	global $groups_template;
	
	echo apply_filters( 'bp_total_group_count', $groups_template->total_group_count );
}

function bp_group_total_members( $echo = true ) {
	global $groups_template;
	
	if ( $echo )
		echo apply_filters( 'groups_template', $groups_template->group->total_member_count );
	else
		return apply_filters( 'groups_template', $groups_template->group->total_member_count );
}

function bp_group_is_photos_enabled() {
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

function bp_group_is_wire_enabled() {
	global $groups_template;
	
	if ( $groups_template->group->enable_wire )
		return true;
	
	return false;
}

function bp_group_forum_permalink() {
	global $groups_template;
	
	echo bp_group_permalink( $groups_template->group, false ) . '/forum';
}

function bp_group_is_forum_enabled() {
	global $groups_template;
	
	if ( $groups_template->group->enable_forum )
		return true;
	
	return false;	
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

function bp_group_admin_memberlist( $admin_list = false ) {
	global $groups_template;
	
	$admins = groups_get_group_admins( $groups_template->group->id );
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

function bp_group_mod_memberlist( $admin_list = false ) {
	global $groups_template, $group_mods;	
	
	$group_mods = groups_get_group_mods( $groups_template->group->id );
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

function bp_group_has_moderators() {
	global $group_mods, $groups_template;

	return apply_filters( 'bp_group_has_moderators', groups_get_group_mods( $groups_template->group->id ) );
}

function bp_group_member_promote_link() {
	global $members_template, $groups_template, $bp;

	echo apply_filters( 'bp_group_member_promote_link', bp_group_permalink( $groups_template->group, false ) . '/admin/manage-members/promote/' . $members_template->member->user_id );
}

function bp_group_member_demote_link( $user_id = false) {
	global $members_template, $groups_template, $bp;
	
	if ( !$user_id )
		$user_id = $members_template->member->user_id;
	
	echo apply_filters( 'bp_group_member_demote_link', bp_group_permalink( $groups_template->group, false ) . '/admin/manage-members/demote/' . $user_id );
}

function bp_group_member_ban_link() {
	global $members_template, $groups_template, $bp;
	
	echo apply_filters( 'bp_group_member_ban_link', bp_group_permalink( $groups_template->group, false ) . '/admin/manage-members/ban/' . $members_template->member->user_id );
}

function bp_group_member_unban_link() {
	global $members_template, $groups_template, $bp;
	
	echo apply_filters( 'bp_group_member_unban_link', bp_group_permalink( $groups_template->group, false ) . '/admin/manage-members/unban/' . $members_template->member->user_id );	
}

function bp_group_admin_tabs() {
	global $bp, $groups_template;
	
	$current_tab = $bp['action_variables'][0];
?>
	<?php if ( $bp['is_item_admin'] || $bp['is_item_mod'] ) { ?>
		<li<?php if ( $current_tab == 'edit-details' || $current_tab == '' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/edit-details"><?php _e('Edit Details', 'buddypress') ?></a></li>
	<?php } ?>

	<?php if ( $bp['is_item_admin'] ) { ?>	
		<li<?php if ( $current_tab == 'group-settings' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/group-settings"><?php _e('Group Settings', 'buddypress') ?></a></li>
	<?php } ?>

	<?php if ( $bp['is_item_admin'] ) { ?>			
		<li<?php if ( $current_tab == 'manage-members' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/manage-members"><?php _e('Manage Members', 'buddypress') ?></a></li>
	<?php } ?>
	
	<?php if ( $bp['is_item_admin'] && $groups_template->group->status == 'private' ) : ?>
	<li<?php if ( $current_tab == 'membership-requests' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/membership-requests"><?php _e('Membership Requests', 'buddypress') ?></a></li>
	<?php endif; ?>

	<?php if ( $bp['is_item_admin'] ) { ?>		
		<li<?php if ( $current_tab == 'delete-group' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['root_domain'] . '/' . $bp['groups']['slug'] ?>/<?php echo $groups_template->group->slug ?>/admin/delete-group"><?php _e('Delete Group', 'buddypress') ?></a></li>
	<?php } ?>
	
<?php
	do_action( 'bp_groups_admin_tabs' );
}

function bp_group_form_action( $page ) {
	global $bp, $groups_template;
	
	echo apply_filters( 'bp_group_form_action', bp_group_permalink( $group, false ) . '/' . $page );
}

function bp_group_admin_form_action( $page ) {
	global $bp, $groups_template;
	
	echo apply_filters( 'bp_group_admin_form_action', bp_group_permalink( $group, false ) . '/admin/' . $page );
}

function bp_group_has_requested_membership( $group = false ) {
	global $bp, $groups_template;
	
	if ( !$group )
		$group = $groups_template->group;
	
	if ( groups_check_for_membership_request( $bp['loggedin_userid'], $group->id ) )
		return true;
	
	return false;
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
			
			<p><input type="submit" value="<?php _e('Create Group and Continue', 'buddypress') ?> &raquo;" id="save" name="save"/></p>
		<?php break; ?>
		
		<?php case '2': ?>
			<?php if ( $completed_to_step > 0 ) { ?>
				<?php if ( function_exists('bp_wire_install') ) : ?>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php if ( $group_obj->enable_wire ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
				</div>
				<?php endif; ?>
				
				<?php if ( function_exists('bp_forums_setup') ) : ?>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php if ( $group_obj->enable_forum ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
				</div>
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
			
				<h3><?php _e('Privacy Options', 'buddypress'); ?></h3>
			
				<div class="radio">
					<label><input type="radio" name="group-status" value="public"<?php if ( $group_obj->status == 'public' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is an open group', 'buddypress') ?></strong><br /><?php _e('This group will be free to join and will appear in group search results.', 'buddypress'); ?></label>
					<label><input type="radio" name="group-status" value="private"<?php if ( $group_obj->status == 'private' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is an private group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join but will still appear in group search results.', 'buddypress'); ?></label>
					<label><input type="radio" name="group-status" value="hidden"<?php if ( $group_obj->status == 'hidden' ) { ?> checked="checked"<?php } ?> /> <strong><?php _e('This is a hidden group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join and will only be visible to invited members. It will not appear in search results or on member profiles.', 'buddypress'); ?></label>
				</div>

				<p><input type="submit" value="<?php _e('Save and Continue', 'buddypress') ?> &raquo;" id="save" name="save"/></p>
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
						<input type="submit" value="<?php _e('Skip', 'buddypress') ?> &raquo;" id="skip" name="skip"/>
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
						$group_link = bp_group_permalink( $group_obj, false );
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
function bp_group_list_invite_friends() {
	global $bp, $group_obj, $invites;
	
	if ( !function_exists('friends_install') )
		return false;

		$friends = friends_get_friends_invite_list( $bp['loggedin_userid'], $group_obj->id );

		if ( $friends ) {
			$invites = groups_get_invites_for_group( $bp['loggedin_userid'], $group_obj->id );

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
					
					<li><input<?php echo $checked ?> type="checkbox" name="friends[]" id="f-<?php echo $friends[$i]['id'] ?>" value="<?php echo $friends[$i]['id'] ?>" /> <?php echo $friends[$i]['full_name']; ?></li>
					<?php } ?>
				</ul>
			</div>
	<?php
		} else {
			_e( 'No friends to invite.', 'buddypress' );
		}
}

function bp_groups_header_tabs() {
	global $bp, $create_group_step, $completed_to_step;
?>
	<li<?php if ( !isset($bp['action_variables'][0]) || $bp['action_variables'][0] == 'recently-active' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/recently-active"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( $bp['action_variables'][0] == 'recently-joined' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/recently-joined"><?php _e( 'Recently Joined', 'buddypress' ) ?></a></li>
	<li<?php if ( $bp['action_variables'][0] == 'most-popular' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/most-popular""><?php _e( 'Most Popular', 'buddypress' ) ?></a></li>
	<li<?php if ( $bp['action_variables'][0] == 'admin-of' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/admin-of""><?php _e( 'Administrator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( $bp['action_variables'][0] == 'mod-of' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/mod-of""><?php _e( 'Moderator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( $bp['action_variables'][0] == 'alphabetically' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['groups']['slug'] ?>/my-groups/alphabetically""><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>
		
<?php
	do_action( 'bp_friends_header_tabs' );
}

function bp_groups_filter_title() {
	global $bp;
	
	$current_filter = $bp['action_variables'][0];
	
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

function bp_group_is_member() {
	global $bp, $groups_template;
	
	if ( groups_is_user_member( $bp['loggedin_userid'], $groups_template->group->id ) )
		return true;
	
	return false;
}

function bp_group_accept_invite_link() {
	global $groups_template, $bp;
	
	echo apply_filters( 'bp_group_accept_invite_link', $bp['loggedin_domain'] . $bp['groups']['slug'] . '/invites/accept/' . $groups_template->group->id );	
}

function bp_group_reject_invite_link() {
	global $groups_template, $bp;
	
	echo apply_filters( 'bp_group_reject_invite_link', $bp['loggedin_domain'] . $bp['groups']['slug'] . '/invites/reject/' . $groups_template->group->id );
}

function bp_has_friends_to_invite() {
	global $groups_template, $bp;
	
	if ( !function_exists('friends_install') )
		return false;
	
	if ( !friends_check_user_has_friends( $bp['loggedin_userid'] ) || !friends_count_invitable_friends( $bp['loggedin_userid'], $groups_template->group->id ) )
		return false;
	
	return true;
}

function bp_group_leave_confirm_link() {
	global $groups_template, $bp;
	
	echo apply_filters( 'bp_group_leave_confirm_link', bp_group_permalink( false, true ) . '/leave-group/yes' );	
}

function bp_group_leave_reject_link() {
	global $groups_template, $bp;
	
	echo apply_filters( 'bp_group_leave_reject_link', bp_group_permalink( false, true ) );
}

function bp_group_send_invite_form( $group_obj = null ) {
	global $bp, $groups_template, $invites;
	
	if ( !$group_obj )
		$group_obj =& $groups_template->group;
?>
	<div class="left-menu">
		<h4><?php _e( 'Select Friends', 'buddypress' ) ?> <img id="ajax-loader" src="<?php echo $bp['groups']['image_base'] ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></h4>
		<?php bp_group_list_invite_friends() ?>
		<?php wp_nonce_field( 'invite_user' ) ?>
		<input type="hidden" name="group_id" id="group_id" value="<?php echo $group_obj->id ?>" />
	</div>

	<div class="main-column">
		
		<div id="message" class="info">
			<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
		</div>

		<?php $invites = groups_get_invites_for_group( $bp['loggedin_userid'], $group_obj->id ) ?>
		
		<ul id="friend-list" class="item-list">
			<?php for( $i = 0; $i < count($invites); $i++ ) {
				$user = new BP_Core_User( $invites[$i] ); ?>
	
				<li id="uid-<?php echo $user->id ?>">
					<?php echo $user->avatar_thumb ?>
					<h4><?php echo $user->user_link ?></h4>
					<span class="activity"><?php echo $user->last_active ?></span>
					<div class="action">
						<a class="remove" href="<?php echo site_url() . $bp['groups']['slug'] . '/' . $group_obj->id . '/invites/remove/' . $user->id ?>" id="uid-<?php echo $user->id ?>">Remove Invite</a> 
					</div>
				</li>
			<?php } // end for ?>
		</ul>

		<input type="submit" value="<?php _e('Finish', 'buddypress') ?> &raquo;" id="save" name="save"/>

	</div>
<?php
}

function bp_group_send_invite_form_action() {
	global $groups_template, $bp;
	
	echo apply_filters( 'bp_group_send_invite_form_action', bp_group_permalink( false, true ) . '/send-invites/send' );
}

function bp_group_join_button( $group = false ) {
	global $bp, $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;
	
	// If they're not logged in or are banned from the group, no join button.
	if ( !is_user_logged_in() || groups_is_user_banned( $bp['loggedin_userid'], $group->id ) )
		return false;
	
	echo '<div class="group-button ' . $group->status . '" id="groupbutton-' . $group->id . '">';
	
	switch ( $group->status ) {
		case 'public':
			if ( BP_Groups_Member::check_is_member( $bp['loggedin_userid'], $group->id ) )
				echo '<a class="leave-group" href="' . bp_group_permalink( $group, false ) . '/leave-group">' . __('Leave Group', 'buddypress') . '</a>';									
			else
				echo '<a class="join-group" href="' . bp_group_permalink( $group, false ) . '/join">' . __('Join Group', 'buddypress') . '</a>';					
		break;
		
		case 'private':
			if ( BP_Groups_Member::check_is_member( $bp['loggedin_userid'], $group->id ) ) {
				echo '<a class="leave-group" href="' . bp_group_permalink( $group, false ) . '/leave-group">' . __('Leave Group', 'buddypress') . '</a>';										
			} else {
				if ( !bp_group_has_requested_membership( $group ) )
					echo '<a class="request-membership" href="' . bp_group_permalink( $group, false ) . '/request-membership">' . __('Request Membership', 'buddypress') . '</a>';		
				else
					echo '<a class="membership-requested" href="' . bp_group_permalink( $group, false ) . '">' . __('Membership Requested', 'buddypress') . '</a>';				
			}
		break;
	}
	
	echo '</div>';
}

function bp_group_status_message() {
	global $groups_template;
	
	if ( $groups_template->group->status == 'private' ) {
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

function bp_groups_random_selection( $total_groups = 5 ) {
	global $bp;
	
	$group_ids = BP_Groups_Group::get_random( $total_groups, 1 );
?>	
	<?php if ( $group_ids['groups'] ) { ?>
		<ul class="item-list" id="random-groups-list">
		<?php for ( $i = 0; $i < count( $group_ids['groups'] ); $i++ ) { ?>
			<?php $group = new BP_Groups_Group( $group_ids['groups'][$i]->group_id, false, false ); ?>
			<li>
				<div class="item-avatar">
					<a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><img src="<?php echo $group->avatar_thumb ?>" class="avatar" alt="<?php echo $group->name ?> Avatar" /></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><?php echo $group->name ?></a></div>
					<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __('active %s ago') ) ?></span></div>
					<div class="item-meta desc"><?php echo bp_create_excerpt( $group->description ) ?></div>
				</div>
				
				<div class="action">
					<?php bp_group_join_button( $group ) ?>
					<div class="meta">
						<?php $member_count = groups_get_groupmeta( $group->id, 'total_member_count' ) ?>
						<?php echo ucwords($group->status) ?> <?php _e( 'Group', 'buddypress' ) ?> / 
						<?php if ( $member_count == 1 ) : ?>
							<?php _e( sprintf( '%d member', $member_count ), 'buddypress' ) ?>
						<?php else : ?>
							<?php _e( sprintf( '%d members', $member_count ), 'buddypress' ) ?>
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

/****
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
	var $total_member_count;
	
	function bp_groups_group_members_template( $group_id, $num_per_page, $exclude_admins_mods, $exclude_banned ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $num_per_page;
		
		$members = BP_Groups_Member::get_all_for_group( $group_id, $this->pag_num, $this->pag_page, $exclude_admins_mods, $exclude_banned );
		
		$this->total_member_count = $members['count'];
		$this->members = $members['members'];
		
		$this->member_count = count($this->members);
		
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

		if ( $this->current_member == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_members( $group_id = false, $num_per_page = 5, $exclude_admins_mods = true, $exclude_banned = true ) {
	global $members_template, $groups_template;
	
	if ( !$groups_template )
		$groups_template->group = new BP_Groups_Group( $group_id );
	
	$members_template = new BP_Groups_Group_Members_Template( $groups_template->group->id, $num_per_page, $exclude_admins_mods, $exclude_banned );

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
	global $members_template;
	
	echo apply_filters( 'bp_group_member_avatar', bp_core_get_avatar( $members_template->member->user_id, 1 ) );
}

function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	global $members_template;
	
	echo apply_filters( 'bp_group_member_avatar_mini', bp_core_get_avatar( $members_template->member->user_id, 1, $width, $height ) );
}

function bp_group_member_link() {
	global $members_template;
	
	echo apply_filters( 'bp_group_member_link', bp_core_get_userlink( $members_template->member->user_id ) );
}

function bp_group_member_is_banned() {
	global $members_template, $groups_template;

	return apply_filters( 'bp_group_member_is_banned', groups_is_user_banned( $members_template->member->user_id, $groups_template->group->id ) );
}

function bp_group_member_joined_since() {
	global $members_template;
	
	echo apply_filters( 'bp_group_member_joined_since', bp_core_get_last_activity( strtotime( $members_template->member->date_modified ), __( 'joined %s ago', 'buddypress') ) );
}

function bp_group_member_id() {
	global $members_template;
	return apply_filters( 'bp_group_member_id', $members_template->member->user_id );
}

function bp_group_member_needs_pagination() {
	global $members_template;

	if ( $members_template->total_member_count > $members_template->pag_num )
		return true;
	
	return false;
}

function bp_group_pag_id() {
	global $bp;
	
	if ( $bp['current_action'] == 'group-finder' )
		echo apply_filters( 'bp_group_reject_invite_link', 'groupfinder-pag' );
	else
		echo apply_filters( 'bp_group_reject_invite_link', 'pag' );
}


function bp_group_member_pagination() {
	global $members_template;
	echo $members_template->pag_links;
	wp_nonce_field( 'bp_groups_member_list', '_member_pag_nonce' );
}

function bp_group_member_pagination_count() {
	global $members_template;
	
	$from_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
	$to_num = ( $from_num + 4 > $members_template->total_member_count ) ? $members_template->total_member_count : $from_num + 4; 

	echo apply_filters( 'bp_group_reject_invite_link', sprintf( __( 'Viewing members %d to %d (%d total members)', 'buddypress' ), $from_num, $to_num, $members_template->total_member_count ) );  
}

function bp_group_member_admin_pagination() {
	global $members_template;
	echo $members_template->pag_links;
	wp_nonce_field( 'bp_groups_member_admin_list', '_member_admin_pag_nonce' );
}


/****
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
	
	function bp_groups_membership_requests_template( $group_id, $num_per_page ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['mrpage'] ) ? intval( $_REQUEST['mrpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $num_per_page;
		
		$this->requests = BP_Groups_Group::get_membership_requests( $group_id, $this->pag_num, $this->pag_page );		

		$this->total_request_count = $this->requests['total'];
		$this->requests = $this->requests['requests'];
		$this->request_count = count($this->requests);

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

		if ( $this->current_request == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_membership_requests( $num_per_page = 5 ) {
	global $requests_template, $groups_template;
	
	$requests_template = new BP_Groups_Membership_Requests_Template( $groups_template->group->id, $num_per_page );

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

	echo apply_filters( 'bp_group_request_reject_link', bp_group_permalink( $groups_template->group, false ) . '/admin/membership-requests/reject/' . $requests_template->request->id );
}

function bp_group_request_accept_link() {
	global $requests_template, $groups_template;	

	echo apply_filters( 'bp_group_request_accept_link', bp_group_permalink( $groups_template->group, false ) . '/admin/membership-requests/accept/' . $requests_template->request->id );
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