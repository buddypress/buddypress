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
	do_action( 'bp_groups_filter_title' );
}

function bp_is_group_admin_screen( $slug ) {
	global $bp;
	
	if ( $bp->current_component != BP_GROUPS_SLUG || 'admin' != $bp->current_action )
		return false;
	
	if ( $bp->action_variables[0] == $slug )
		return true;
	
	return false;
}

function bp_group_current_avatar() {
	global $bp;
	
	if ( $bp->groups->current_group->avatar_full ) { ?>
		<img src="<?php echo attribute_escape( $bp->groups->current_group->avatar_full ) ?>" alt="<?php _e( 'Group Avatar', 'buddypress' ) ?>" class="avatar" />
	<?php } else { ?>
		<img src="<?php echo $bp->groups->image_base . '/none.gif' ?>" alt="<?php _e( 'No Group Avatar', 'buddypress' ) ?>" class="avatar" />
	<?php }
}


function bp_get_group_has_avatar() {
	global $bp;

	if ( !empty( $_FILES ) || !bp_core_fetch_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group', 'no_grav' => true ) ) )
		return false;
	
	return true;
}

function bp_group_avatar_delete_link() {
	echo bp_get_group_avatar_delete_link();
}
	function bp_get_group_avatar_delete_link() {
		global $bp;
		
		return apply_filters( 'bp_get_group_avatar_delete_link', wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/group-avatar/delete', 'bp_group_avatar_delete' ) );
	}

function bp_group_avatar_edit_form() {
	groups_avatar_upload();
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
	} else if ( $bp->groups->current_group->slug ) {
		$type = 'single-group';
		$slug = $bp->groups->current_group->slug;
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
	function bp_get_group_avatar( $args = '' ) {
		global $bp, $groups_template;

		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => false,
			'alt' => __( 'Group avatar', 'buddypress' ) 
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		/* Fetch the avatar from the folder, if not provide backwards compat. */
		if ( !$avatar = bp_core_fetch_avatar( array( 'item_id' => $groups_template->group->id, 'object' => 'group', 'type' => $type, 'avatar_dir' => 'group-avatars', 'alt' => $alt, 'css_id' => $id, 'class' => $class ) ) )
			$avatar = '<img src="' . attribute_escape( $groups_template->group->avatar_thumb ) . '" class="avatar" alt="' . attribute_escape( $groups_template->group->name ) . '" />';

		return apply_filters( 'bp_get_group_avatar', $avatar );
	}

function bp_group_avatar_thumb() {
	echo bp_get_group_avatar_thumb();
}
	function bp_get_group_avatar_thumb( $group = false ) {
		return bp_get_group_avatar( 'type=thumb' );
	}

function bp_group_avatar_mini() {
	echo bp_get_group_avatar_mini();
}
	function bp_get_group_avatar_mini( $group = false ) {
		return bp_get_group_avatar( 'type=thumb&width=30&height=30' );
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

		return apply_filters( 'bp_get_group_date_created', date_i18n( get_option( 'date_format' ), $group->date_created ) );
	}

function bp_group_is_admin() {
	global $bp;
	
	return $bp->is_item_admin;
}

function bp_group_is_mod() {
	global $bp;
	
	return $bp->is_item_mod;
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
					<a href="<?php echo bp_core_get_userlink( $group_mods[$i]->user_id, false, true ) ?>" title="<?php echo bp_core_get_user_displayname( $group_mods[$i]->user->user_id ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $group_mods[$i]->user_id, 'type' => 'thumb' ) ) ?></a>
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

function bp_group_active_forum_topics( $total_topics = 3, $group = false ) {
	global $groups_template, $forum_template;

	if ( !$group )
		$group =& $groups_template->group;

	$forum_id = groups_get_groupmeta( $group->id, 'forum_id' );

	if ( $forum_id && $forum_id != '' ) {
		if ( function_exists( 'bp_forums_setup' ) ) {
			$latest_topics = bp_forums_get_forum_topics( $forum_id );
		
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

	$action = $bp->displayed_user->domain . $bp->groups->slug . '/my-groups/search/';
	$label = __('Filter Groups', 'buddypress');
	$name = 'group-filter-box';
?>
	<form action="<?php echo $action ?>" id="group-search-form" method="post">
		<label for="<?php echo $name ?>" id="<?php echo $name ?>-label"><?php echo $label ?> <span class="ajax-loader"></span></label>
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
	<span class="ajax-loader"></span><?php 
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
				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30 ) ) ?>
				<h5><?php echo bp_core_get_userlink( $admin->user_id ) ?></h5>
			</li>
			<?php } else { ?>
			<li>
				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb' ) ) ?>
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
					<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30 ) ) ?>
					<h5><?php echo bp_core_get_userlink( $mod->user_id ) ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ) ?></a> | <a href="<?php bp_group_member_demote_link($mod->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a></span></h5>
				</li>
				<?php } else { ?>
				<li>
					<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb' ) ) ?>
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
		$group = ( $groups_template->group ) ? $groups_template->group : $bp->groups->current_group;
	
	$current_tab = $bp->action_variables[0];
?>
	<?php if ( $bp->is_item_admin || $bp->is_item_mod ) { ?>
		<li<?php if ( 'edit-details' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/edit-details"><?php _e('Edit Details', 'buddypress') ?></a></li>
	<?php } ?>
	
	<?php
		if ( !$bp->is_item_admin )
			return false;
	?>
	<li<?php if ( 'group-settings' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/group-settings"><?php _e('Group Settings', 'buddypress') ?></a></li>
	<li<?php if ( 'group-avatar' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/group-avatar"><?php _e('Group Avatar', 'buddypress') ?></a></li>
	<li<?php if ( 'manage-members' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/manage-members"><?php _e('Manage Members', 'buddypress') ?></a></li>

	<?php if ( $groups_template->group->status == 'private' ) : ?>
		<li<?php if ( 'membership-requests' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/membership-requests"><?php _e('Membership Requests', 'buddypress') ?></a></li>
	<?php endif; ?>
	
	<?php do_action( 'groups_admin_tabs', $current_tab, $group->slug ) ?>
	
	<li<?php if ( 'delete-group' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug ?>/<?php echo $group->slug ?>/admin/delete-group"><?php _e('Delete Group', 'buddypress') ?></a></li>
<?php
}

function bp_group_total_for_member() {
	echo bp_get_group_total_for_member();
}
	function bp_get_group_total_for_member() {
		return apply_filters( 'bp_get_group_total_for_member', BP_Groups_Member::total_group_count() );
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
	
function bp_group_admin_form_action( $page = false, $deprecated = false ) {
	echo bp_get_group_admin_form_action( $page );
}
	function bp_get_group_admin_form_action( $page = false, $group = false ) {
		global $bp, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !$page )
			$page = $bp->action_variables[0];

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

	if ( !$group->status )
		return false;
	
	echo '<div class="generic-button group-button ' . $group->status . '" id="groupbutton-' . $group->id . '">';
	
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
	global $bp, $members_template;
	
	$defaults = array(
		'group_id' => $bp->groups->current_group->id,
		'per_page' => 10,
		'max' => false,
		'exclude_admins_mods' => 1,
		'exclude_banned' => 1
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$members_template = new BP_Groups_Group_Members_Template( $group_id, $per_page, $max, (int)$exclude_admins_mods, (int)$exclude_banned );
	
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

		return apply_filters( 'bp_get_group_member_avatar', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'thumb' ) ) );
	}

function bp_group_member_avatar_thumb() {
	echo bp_get_group_member_avatar_thumb();
}
	function bp_get_group_member_avatar_thumb() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'full' ) ) );
	}

function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_group_member_avatar_mini( $width, $height );
}
	function bp_get_group_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar_mini', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'full', 'width' => $width, 'height' => $height ) ) );
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


/***************************************************************************
 * Group Creation Process Template Tags
 **/

function bp_group_creation_tabs() {
	global $bp;
	
	if ( !is_array( $bp->groups->group_creation_steps ) )
		return false;
	
	if ( !$bp->groups->current_create_step )
		$bp->groups->current_create_step = array_shift( array_keys( $bp->groups->group_creation_steps ) );

	$counter = 1;
	foreach ( $bp->groups->group_creation_steps as $slug => $step ) {
		$is_enabled = bp_are_previous_group_creation_steps_complete( $slug ); ?>
		
		<li<?php if ( $bp->groups->current_create_step == $slug ) : ?> class="current"<?php endif; ?>><?php if ( $is_enabled ) : ?><a href="<?php echo $bp->loggedin_user->domain . $bp->groups->slug ?>/create/step/<?php echo $slug ?>"><?php endif; ?><?php echo $counter ?>. <?php echo $step['name'] ?><?php if ( $is_enabled ) : ?></a><?php endif; ?></li><?php
		$counter++;
	}
	
	unset( $is_enabled );
	
	do_action( 'groups_creation_tabs' );
}

function bp_group_creation_stage_title() {
	global $bp;
	
	echo apply_filters( 'bp_group_creation_stage_title', '<span>&mdash; ' . $bp->groups->group_creation_steps[$bp->groups->current_create_step]['name'] . '</span>' );
}

function bp_group_creation_form_action() {
	echo bp_get_group_creation_form_action();
}
	function bp_get_group_creation_form_action() {
		global $bp;
		
		if ( empty( $bp->action_variables[1] ) )
			$bp->action_variables[1] = array_shift( array_keys( $bp->groups->group_creation_steps ) );
		
		return apply_filters( 'bp_get_group_creation_form_action', $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->action_variables[1] );
	}

function bp_is_group_creation_step( $step_slug ) {
	global $bp;
	
	/* Make sure we are in the groups component */
	if ( $bp->current_component != BP_GROUPS_SLUG || 'create' != $bp->current_action )
		return false;
	
	/* If this the first step, we can just accept and return true */
	if ( !$bp->action_variables[1] && array_shift( array_keys( $bp->groups->group_creation_steps ) ) == $step_slug )
		return true;
	
	/* Before allowing a user to see a group creation step we must make sure previous steps are completed */
	if ( !bp_is_first_group_creation_step() ) {
		if ( !bp_are_previous_group_creation_steps_complete( $step_slug ) )
			return false;
	}
	
	/* Check the current step against the step parameter */
	if ( $bp->action_variables[1] == $step_slug )
		return true;
	
	return false;
}

function bp_is_group_creation_step_complete( $step_slugs ) {
	global $bp;
	
	if ( !$bp->groups->completed_create_steps )
		return false;

	if ( is_array( $step_slugs ) ) {
		$found = true;
		
		foreach ( $step_slugs as $step_slug ) {
			if ( !in_array( $step_slug, $bp->groups->completed_create_steps ) )
				$found = false;
		}
		
		return $found;
	} else {
		return in_array( $step_slugs, $bp->groups->completed_create_steps );	
	}

	return true;
}

function bp_are_previous_group_creation_steps_complete( $step_slug ) {
	global $bp;
	
	/* If this is the first group creation step, return true */
	if ( array_shift( array_keys( $bp->groups->group_creation_steps ) ) == $step_slug )
		return true;
	
	reset( $bp->groups->group_creation_steps );
	unset( $previous_steps );
		
	/* Get previous steps */
	foreach ( $bp->groups->group_creation_steps as $slug => $name ) {
		if ( $slug == $step_slug )
			break;
	
		$previous_steps[] = $slug;
	}
	
	return bp_is_group_creation_step_complete( $previous_steps );
}

function bp_new_group_id() {
	echo bp_get_new_group_id();
}
	function bp_get_new_group_id() {
		global $bp;
		return apply_filters( 'bp_get_new_group_id', $bp->groups->new_group_id );
	}
	
function bp_new_group_name() {
	echo bp_get_new_group_name();
}
	function bp_get_new_group_name() {
		global $bp;
		return apply_filters( 'bp_get_new_group_name', $bp->groups->current_group->name );
	}

function bp_new_group_description() {
	echo bp_get_new_group_description();
}
	function bp_get_new_group_description() {
		global $bp;
		return apply_filters( 'bp_get_new_group_description', $bp->groups->current_group->name );
	}

function bp_new_group_news() {
	echo bp_get_new_group_news();
}
	function bp_get_new_group_news() {
		global $bp;
		return apply_filters( 'bp_get_new_group_news', $bp->groups->current_group->name );
	}

function bp_new_group_enable_wire() {
	echo bp_get_new_group_enable_wire();
}
	function bp_get_new_group_enable_wire() {
		global $bp;
		return (int) apply_filters( 'bp_get_new_group_enable_wire', $bp->groups->current_group->enable_wire );
	}

function bp_new_group_enable_forum() {
	echo bp_get_new_group_enable_forum();
}
	function bp_get_new_group_enable_forum() {
		global $bp;
		return (int) apply_filters( 'bp_get_new_group_enable_forum', $bp->groups->current_group->enable_forum );
	}

function bp_new_group_status() {
	echo bp_get_new_group_status();
}
	function bp_get_new_group_status() {
		global $bp;
		return apply_filters( 'bp_get_new_group_status', $bp->groups->current_group->status );
	}

function bp_new_group_avatar( $args = '' ) {
	echo bp_get_new_group_avatar( $args );
}
	function bp_get_new_group_avatar( $args = '' ) {
		global $bp;
			
		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => 'avatar-crop-preview',
			'alt' => __( 'Group avatar', 'buddypress' ) 
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		return apply_filters( 'bp_get_new_group_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group', 'type' => $type, 'avatar_dir' => 'group-avatars', 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class ) ) );
	}

function bp_group_creation_previous_link() {
	echo bp_get_group_creation_previous_link();
}
	function bp_get_group_creation_previous_link() {
		global $bp;
		
		foreach ( $bp->groups->group_creation_steps as $slug => $name ) {
			if ( $slug == $bp->action_variables[1] )
				break;
	
			$previous_steps[] = $slug;
		}

		return apply_filters( 'bp_get_group_creation_previous_link', $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . array_pop( $previous_steps ) );
	}

function bp_is_last_group_creation_step() {
	global $bp;
	
	$last_step = array_pop( array_keys( $bp->groups->group_creation_steps ) );

	if ( $last_step == $bp->groups->current_create_step )
		return true;
	
	return false;
}

function bp_is_first_group_creation_step() {
	global $bp;
	
	$first_step = array_shift( array_keys( $bp->groups->group_creation_steps ) );

	if ( $first_step == $bp->groups->current_create_step )
		return true;
	
	return false;
}

function bp_new_group_invite_friend_list() {
	echo bp_get_new_group_invite_friend_list();
}
	function bp_get_new_group_invite_friend_list( $args = '' ) {
		global $bp;

		if ( !function_exists('friends_install') )
			return false;
		
		$defaults = array(
			'group_id' => false,
			'separator' => 'li'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		if ( !$group_id )
			$group_id = ( $bp->groups->new_group_id ) ? $bp->groups->new_group_id : $bp->groups->current_group->id;
			
		$friends = friends_get_friends_invite_list( $bp->loggedin_user->id, $group_id );
			
		if ( $friends ) {
			$invites = groups_get_invites_for_group( $bp->loggedin_user->id, $group_id );

			for ( $i = 0; $i < count( $friends ); $i++ ) {
				if ( $invites ) {
					if ( in_array( $friends[$i]['id'], $invites ) ) {
						$checked = ' checked="checked"';
					} else {
						$checked = '';
					} 
				}
				
				$items[] = '<' . $separator . '><input' . $checked . ' type="checkbox" name="friends[]" id="f-' . $friends[$i]['id'] . '" value="' . attribute_escape( $friends[$i]['id'] ) . '" /> ' . $friends[$i]['full_name'] . '</' . $separator . '>';
			}
		}
		
		return implode( "\n", (array)$items );
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
		
		if ( (int) $this->total_group_count && (int) $this->pag_num ) {
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
	<span class="ajax-loader"></span><?php 
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

		return apply_filters( 'bp_the_site_group_avatar', bp_core_fetch_avatar( array( 'item_id' => $site_groups_template->group->id, 'object' => 'group', 'type' => 'full', 'avatar_dir' => 'group-avatars', 'alt' => __( 'Group Avatar', 'buddypress' ) ) ) );
	}

function bp_the_site_group_avatar_thumb() {
	echo bp_get_the_site_group_avatar_thumb();
}
	function bp_get_the_site_group_avatar_thumb() {
		global $site_groups_template;
		
		return apply_filters( 'bp_get_the_site_group_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $site_groups_template->group->id, 'object' => 'group', 'type' => 'thumb', 'avatar_dir' => 'group-avatars', 'alt' => __( 'Group Avatar', 'buddypress' ) ) ) );
	}

function bp_the_site_group_avatar_mini() {
	echo bp_get_the_site_group_avatar_mini();
}
	function bp_get_the_site_group_avatar_mini() {
		global $site_groups_template;

		return apply_filters( 'bp_get_the_site_group_avatar_mini', bp_core_fetch_avatar( array( 'item_id' => $site_groups_template->group->id, 'object' => 'group', 'type' => 'thumb', 'width' => 30, 'height' => 30, 'avatar_dir' => 'group-avatars', 'alt' => __( 'Group Avatar', 'buddypress' ) ) ) );
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

		return apply_filters( 'bp_get_the_site_group_date_created', bp_core_time_since( $site_groups_template->group->date_created ) );	
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
	<form action="" method="get" id="search-groups-form">
		<label><input type="text" name="s" id="groups_search" value="<?php if ( isset( $_GET['s'] ) ) { echo attribute_escape( $_GET['s'] ); } else { _e( 'Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e( 'Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Search anything...', 'buddypress' ) ?>';}" /></label>
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
	
	echo apply_filters( 'bp_group_request_user_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $requests_template->request->user_id, 'type' => 'thumb' ) ) );
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


/************************************************************************************
 * Invite Friends Template Tags
 **/

class BP_Groups_Invite_Template {
	var $current_invite = -1;
	var $invite_count;
	var $invites;
	var $invite;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_invite_count;
	
	function bp_groups_invite_template( $user_id, $group_id ) {
		global $bp;

		$this->invites = groups_get_invites_for_group( $user_id, $group_id );
		$this->invite_count = count( $this->invites ); 
	}
	
	function has_invites() {
		if ( $this->invite_count )
			return true;
		
		return false;
	}
	
	function next_invite() {
		$this->current_invite++;
		$this->invite = $this->invites[$this->current_invite];
		
		return $this->invite;
	}
	
	function rewind_invites() {
		$this->current_invite = -1;
		if ( $this->invite_count > 0 ) {
			$this->invite = $this->invites[0];
		}
	}
	
	function invites() { 
		if ( $this->current_invite + 1 < $this->invite_count ) {
			return true;
		} elseif ( $this->current_invite + 1 == $this->invite_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_invites();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_invite() {
		global $invite;

		$this->in_the_loop = true;
		$user_id = $this->next_invite();
		
		$this->invite = new stdClass;
		$this->invite->user = new BP_Core_User( $user_id );
		$this->invite->group_id = $group_id; // Globaled in bp_group_has_invites()
		
		if ( 0 == $this->current_invite ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_invites( $args = '' ) {
	global $bp, $invites_template, $group_id;
	
	$defaults = array(
		'group_id' => false,
		'user_id' => $bp->loggedin_user->id
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$group_id ) {
		/* Backwards compatibility */
		if ( $bp->groups->current_group ) $group_id = $bp->groups->current_group->id;
		if ( $bp->groups->new_group_id ) $group_id = $bp->groups->new_group_id;
	}
	
	if ( !$group_id )
		return false;

	$invites_template = new BP_Groups_Invite_Template( $user_id, $group_id );
	return $invites_template->has_invites();
}

function bp_group_invites() {
	global $invites_template;
	
	return $invites_template->invites();
}

function bp_group_the_invite() {
	global $invites_template;
	
	return $invites_template->the_invite();
}

function bp_group_invite_item_id() {
	echo bp_get_group_invite_item_id();
}
	function bp_get_group_invite_item_id() {
		global $invites_template;

		return apply_filters( 'bp_get_group_invite_item_id', 'uid-' . $invites_template->invite->user->id );
	}

function bp_group_invite_user_avatar() {
	echo bp_get_group_invite_user_avatar();
}
	function bp_get_group_invite_user_avatar() {
		global $invites_template;

		return apply_filters( 'bp_get_group_invite_user_avatar', $invites_template->invite->user->avatar_thumb );
	}

function bp_group_invite_user_link() {
	echo bp_get_group_invite_user_link();
}
	function bp_get_group_invite_user_link() {
		global $invites_template;

		return apply_filters( 'bp_get_group_invite_user_link', bp_core_get_userlink( $invites_template->invite->user->id ) );
	}

function bp_group_invite_user_last_active() {
	echo bp_get_group_invite_user_last_active();
}
	function bp_get_group_invite_user_last_active() {
		global $invites_template;

		return apply_filters( 'bp_get_group_invite_user_last_active', $invites_template->invite->user->last_active );
	}

function bp_group_invite_user_remove_invite_url() {
	echo bp_get_group_invite_user_remove_invite_url();
}
	function bp_get_group_invite_user_remove_invite_url() {
		global $invites_template;
		
		return wp_nonce_url( site_url( BP_GROUPS_SLUG . '/' . $invites_template->invite->group_id . '/invites/remove/' . $invites_template->invite->user->id ), 'groups_invite_uninvite_user' );
	}

?>