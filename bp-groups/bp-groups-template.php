<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the groups component slug
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 *
 * @uses bp_get_groups_slug()
 */
function bp_groups_slug() {
	echo bp_get_groups_slug();
}
	/**
	 * Return the groups component slug
	 *
	 * @package BuddyPress
	 * @subpackage Groups Template
	 * @since 1.5
	 */
	function bp_get_groups_slug() {
		global $bp;
		return apply_filters( 'bp_get_groups_slug', $bp->groups->slug );
	}

/**
 * Output the groups component root slug
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 *
 * @uses bp_get_groups_root_slug()
 */
function bp_groups_root_slug() {
	echo bp_get_groups_root_slug();
}
	/**
	 * Return the groups component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Groups Template
	 * @since 1.5
	 */
	function bp_get_groups_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_groups_root_slug', $bp->groups->root_slug );
	}

/**
 * Output group directory permalink
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 * @uses bp_get_groups_directory_permalink()
 */
function bp_groups_directory_permalink() {
	echo bp_get_groups_directory_permalink();
}
	/**
	 * Return group directory permalink
	 *
	 * @package BuddyPress
	 * @subpackage Groups Template
	 * @since 1.5
	 * @uses apply_filters()
	 * @uses traisingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_groups_root_slug()
	 * @return string
	 */
	function bp_get_groups_directory_permalink() {
		return apply_filters( 'bp_get_groups_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() ) );
	}

/*****************************************************************************
 * Groups Template Class/Tags
 **/

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

	function bp_groups_template( $user_id, $type, $page, $per_page, $max, $slug, $search_terms, $populate_extras, $include = false, $exclude = false, $show_hidden = false ) {
		$this->__construct( $user_id, $type, $page, $per_page, $max, $slug, $search_terms, $include, $populate_extras, $exclude, $show_hidden );
	}

	function __construct( $user_id, $type, $page, $per_page, $max, $slug, $search_terms, $populate_extras, $include = false, $exclude = false, $show_hidden = false ){

		global $bp;

		$this->pag_page = isset( $_REQUEST['grpage'] ) ? intval( $_REQUEST['grpage'] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		if ( $bp->loggedin_user->is_super_admin || ( is_user_logged_in() && $user_id == $bp->loggedin_user->id ) )
			$show_hidden = true;

		if ( 'invites' == $type ) {
			$this->groups = groups_get_invites_for_user( $user_id, $this->pag_num, $this->pag_page, $exclude );
		} else if ( 'single-group' == $type ) {
			$group           = new stdClass;
			$group->group_id = BP_Groups_Group::get_id_from_slug( $slug );
			$this->groups    = array( $group );
		} else {
			$this->groups = groups_get_groups( array(
				'type'            => $type,
				'per_page'        => $this->pag_num,
				'page'            => $this->pag_page,
				'user_id'         => $user_id,
				'search_terms'    => $search_terms,
				'include'         => $include,
				'exclude'         => $exclude,
				'populate_extras' => $populate_extras,
				'show_hidden'     => $show_hidden
			) );
		}

		if ( 'invites' == $type ) {
			$this->total_group_count = (int)$this->groups['total'];
			$this->group_count       = (int)$this->groups['total'];
			$this->groups            = $this->groups['groups'];
		} else if ( 'single-group' == $type ) {
			$this->single_group      = true;
			$this->total_group_count = 1;
			$this->group_count       = 1;
		} else {
			if ( empty( $max ) || $max >= (int)$this->groups['total'] ) {
				$this->total_group_count = (int)$this->groups['total'];
			} else {
				$this->total_group_count = (int)$max;
			}

			$this->groups = $this->groups['groups'];

			if ( !empty( $max ) ) {
				if ( $max >= count( $this->groups ) ) {
					$this->group_count = count( $this->groups );
				} else {
					$this->group_count = (int)$max;
				}
			} else {
				$this->group_count = count( $this->groups );
			}
		}

		// Build pagination links
		if ( (int)$this->total_group_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( array( 'grpage' => '%#%', 'num' => $this->pag_num, 's' => $search_terms, 'sortby' => $this->sort_by, 'order' => $this->order ) ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_group_count / (int)$this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Group pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Group pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
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
			do_action('group_loop_end');
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

		if ( $this->single_group )
			$this->group = new BP_Groups_Group( $this->group->group_id );

		if ( 0 == $this->current_group ) // loop has just started
			do_action('group_loop_start');
	}
}

function bp_has_groups( $args = '' ) {
	global $groups_template, $bp;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$slug    = false;
	$type    = 'active';
	$user_id = 0;
	$order   = '';

	// User filtering
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	// Type
	if ( 'my-groups' == $bp->current_action ) {
		if ( 'most-popular' == $order ) {
			$type = 'popular';
		} elseif ( 'alphabetically' == $order ) {
			$type = 'alphabetical';
		}
	} elseif ( 'invites' == $bp->current_action ) {
		$type = 'invites';
	} elseif ( isset( $bp->groups->current_group->slug ) && $bp->groups->current_group->slug ) {
		$type = 'single-group';
		$slug = $bp->groups->current_group->slug;
	}

	$defaults = array(
		'type'            => $type,
		'page'            => 1,
		'per_page'        => 20,
		'max'             => false,
		'show_hidden'     => false,

		'user_id'         => $user_id, // Pass a user ID to limit to groups this user has joined
		'slug'            => $slug,    // Pass a group slug to only return that group
		'search_terms'    => '',       // Pass search terms to return only matching groups
		'include'         => false,    // Pass comma separated list or array of group ID's to return only these groups
		'exclude'         => false,    // Pass comma separated list or array of group ID's to exclude these groups

		'populate_extras' => true      // Get extra meta - is_member, is_banned
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( is_null( $search_terms ) ) {
		if ( isset( $_REQUEST['group-filter-box'] ) && !empty( $_REQUEST['group-filter-box'] ) )
			$search_terms = $_REQUEST['group-filter-box'];
		elseif ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}

	$groups_template = new BP_Groups_Template( (int)$user_id, $type, (int)$page, (int)$per_page, (int)$max, $slug, $search_terms, (bool)$populate_extras, $include, $exclude, $show_hidden );
	return apply_filters( 'bp_has_groups', $groups_template->has_groups(), $groups_template );
}

function bp_groups() {
	global $groups_template;
	return $groups_template->groups();
}

function bp_the_group() {
	global $groups_template;
	return $groups_template->the_group();
}

function bp_group_is_visible( $group = false ) {
	global $bp, $groups_template;

	if ( $bp->loggedin_user->is_super_admin )
		return true;

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

function bp_group_id() {
	echo bp_get_group_id();
}
	function bp_get_group_id( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_id', $group->id );
	}

function bp_group_name() {
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

function bp_group_status() {
	echo bp_get_group_status();
}
	function bp_get_group_status( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_status', $group->status );
	}

function bp_group_avatar( $args = '' ) {
	echo bp_get_group_avatar( $args );
}
	function bp_get_group_avatar( $args = '' ) {
		global $bp, $groups_template;

		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => false,
			'alt' => __( 'Group logo of %s', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/* Fetch the avatar from the folder, if not provide backwards compat. */
		if ( !$avatar = bp_core_fetch_avatar( array( 'item_id' => $groups_template->group->id, 'object' => 'group', 'type' => $type, 'avatar_dir' => 'group-avatars', 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height ) ) )
			$avatar = '<img src="' . esc_attr( $groups_template->group->avatar_thumb ) . '" class="avatar" alt="' . esc_attr( $groups_template->group->name ) . '" />';

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

function bp_group_last_active() {
	echo bp_get_group_last_active();
}
	function bp_get_group_last_active( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		$last_active = $group->last_activity;

		if ( !$last_active )
			$last_active = groups_get_groupmeta( $group->id, 'last_activity' );

		if ( empty( $last_active ) ) {
			return __( 'not yet active', 'buddypress' );
		} else {
			return apply_filters( 'bp_get_group_last_active', bp_core_time_since( $last_active ) );
		}
	}

function bp_group_permalink() {
	echo bp_get_group_permalink();
}
	function bp_get_group_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_permalink', bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' );
	}

function bp_group_admin_permalink() {
	echo bp_get_group_admin_permalink();
}
	function bp_get_group_admin_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_admin_permalink', bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/admin' );
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

function bp_group_description() {
	echo bp_get_group_description();
}
	function bp_get_group_description( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description', stripslashes($group->description) );
	}

function bp_group_description_editable() {
	echo bp_get_group_description_editable();
}
	function bp_get_group_description_editable( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description_editable', $group->description );
	}

function bp_group_description_excerpt() {
	echo bp_get_group_description_excerpt();
}
	function bp_get_group_description_excerpt( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description_excerpt', bp_create_excerpt( $group->description ) );
	}


function bp_group_public_status() {
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

function bp_group_is_public() {
	echo bp_get_group_is_public();
}
	function bp_get_group_is_public( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_is_public', $group->is_public );
	}

function bp_group_date_created() {
	echo bp_get_group_date_created();
}
	function bp_get_group_date_created( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_date_created', bp_core_time_since( strtotime( $group->date_created ) ) );
	}

function bp_group_is_admin() {
	global $bp;

	return $bp->is_item_admin;
}

function bp_group_is_mod() {
	global $bp;

	return $bp->is_item_mod;
}

function bp_group_list_admins( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	if ( $group->admins ) { ?>
		<ul id="group-admins">
			<?php foreach( (array)$group->admins as $admin ) { ?>
				<li>
					<a href="<?php echo bp_core_get_user_domain( $admin->user_id, $admin->user_nicename, $admin->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'email' => $admin->user_email, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?></a>
				</li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<span class="activity"><?php _e( 'No Admins', 'buddypress' ) ?></span>
	<?php } ?>
<?php
}

function bp_group_list_mods( $group = false ) {
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( !empty( $group->mods ) ) : ?>

		<ul id="group-mods">

			<?php foreach( (array)$group->mods as $mod ) { ?>

				<li>
					<a href="<?php echo bp_core_get_user_domain( $mod->user_id, $mod->user_nicename, $mod->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'email' => $mod->user_email, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?></a>
				</li>

			<?php } ?>

		</ul>

<?php else : ?>

		<span class="activity"><?php _e( 'No Mods', 'buddypress' ) ?></span>

<?php endif;

}

/**
 * Return a list of user_ids for a group's admins
 *
 * @package BuddyPress
 * @since 1.5
 *
 * @param obj $group (optional) The group being queried. Defaults to the current group in the loop
 * @param str $format 'string' to get a comma-separated string, 'array' to get an array
 * @return mixed $admin_ids A string or array of user_ids
 */
function bp_group_admin_ids( $group = false, $format = 'string' ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	$admin_ids = array();

	if ( $group->admins ) {
		foreach( $group->admins as $admin ) {
			$admin_ids[] = $admin->user_id;
		}
	}

	if ( 'string' == $format )
		$admin_ids = implode( ',', $admin_ids );

	return apply_filters( 'bp_group_admin_ids', $admin_ids );
}

/**
 * Return a list of user_ids for a group's moderators
 *
 * @package BuddyPress
 * @since 1.5
 *
 * @param obj $group (optional) The group being queried. Defaults to the current group in the loop
 * @param str $format 'string' to get a comma-separated string, 'array' to get an array
 * @return mixed $mod_ids A string or array of user_ids
 */
function bp_group_mod_ids( $group = false, $format = 'string' ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	$mod_ids = array();

	if ( $group->mods ) {
		foreach( $group->mods as $mod ) {
			$mod_ids[] = $mod->user_id;
		}
	}

	if ( 'string' == $format )
		$mod_ids = implode( ',', $mod_ids );

	return apply_filters( 'bp_group_mod_ids', $mod_ids );
}

function bp_group_all_members_permalink() {
	echo bp_get_group_all_members_permalink();
}
	function bp_get_group_all_members_permalink( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_all_members_permalink', bp_get_group_permalink( $group ) . 'members' );
	}

function bp_group_search_form() {
	global $groups_template, $bp;

	$action = $bp->displayed_user->domain . bp_get_groups_slug() . '/my-groups/search/';
	$label = __('Filter Groups', 'buddypress');
	$name = 'group-filter-box';

?>
	<form action="<?php echo $action ?>" id="group-search-form" method="post">
		<label for="<?php echo $name ?>" id="<?php echo $name ?>-label"><?php echo $label ?></label>
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

function bp_group_is_activity_permalink() {

	if ( !bp_is_single_item() || !bp_is_groups_component() || !bp_is_current_action( bp_get_activity_slug() ) )
		return false;

	return true;
}

function bp_groups_pagination_links() {
	echo bp_get_groups_pagination_links();
}
	function bp_get_groups_pagination_links() {
		global $groups_template;

		return apply_filters( 'bp_get_groups_pagination_links', $groups_template->pag_links );
	}

function bp_groups_pagination_count() {
	echo bp_get_groups_pagination_count();
}
	function bp_get_groups_pagination_count() {
		global $bp, $groups_template;

		$start_num = intval( ( $groups_template->pag_page - 1 ) * $groups_template->pag_num ) + 1;
		$from_num = bp_core_number_format( $start_num );
		$to_num = bp_core_number_format( ( $start_num + ( $groups_template->pag_num - 1 ) > $groups_template->total_group_count ) ? $groups_template->total_group_count : $start_num + ( $groups_template->pag_num - 1 ) );
		$total = bp_core_number_format( $groups_template->total_group_count );

		return apply_filters( 'bp_get_groups_pagination_count', sprintf( __( 'Viewing group %1$s to %2$s (of %3$s groups)', 'buddypress' ), $from_num, $to_num, $total ) );
	}

function bp_groups_auto_join() {
	global $bp;

	return apply_filters( 'bp_groups_auto_join', (bool)$bp->groups->auto_join );
}

function bp_group_total_members( $group = false ) {
	echo bp_get_group_total_members( $group );
}
	function bp_get_group_total_members( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_total_members', $group->total_member_count );
	}

function bp_group_member_count() {
	echo bp_get_group_member_count();
}
	function bp_get_group_member_count() {
		global $groups_template;

		if ( 1 == (int) $groups_template->group->total_member_count )
			return apply_filters( 'bp_get_group_member_count', sprintf( __( '%s member', 'buddypress' ), bp_core_number_format( $groups_template->group->total_member_count ) ) );
		else
			return apply_filters( 'bp_get_group_member_count', sprintf( __( '%s members', 'buddypress' ), bp_core_number_format( $groups_template->group->total_member_count ) ) );
	}

function bp_group_forum_permalink() {
	echo bp_get_group_forum_permalink();
}
	function bp_get_group_forum_permalink( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_forum_permalink', bp_get_group_permalink( $group ) . 'forum' );
	}

function bp_group_forum_topic_count( $args = '' ) {
	echo bp_get_group_forum_topic_count( $args );
}
	function bp_get_group_forum_topic_count( $args = '' ) {
		global $groups_template;

		$defaults = array(
			'showtext' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !$forum_id = groups_get_groupmeta( $groups_template->group->id, 'forum_id' ) )
			return false;

		if ( !bp_is_active( 'forums' ) )
			return false;

		if ( !$groups_template->group->forum_counts )
			$groups_template->group->forum_counts = bp_forums_get_forum_topicpost_count( (int)$forum_id );

		if ( (bool) $showtext ) {
			if ( 1 == (int) $groups_template->group->forum_counts[0]->topics )
				$total_topics = sprintf( __( '%d topic', 'buddypress' ), (int) $groups_template->group->forum_counts[0]->topics );
			else
				$total_topics = sprintf( __( '%d topics', 'buddypress' ), (int) $groups_template->group->forum_counts[0]->topics );
		} else {
			$total_topics = (int) $groups_template->group->forum_counts[0]->topics;
		}

		return apply_filters( 'bp_get_group_forum_topic_count', $total_topics, (bool)$showtext );
	}

function bp_group_forum_post_count( $args = '' ) {
	echo bp_get_group_forum_post_count( $args );
}
	function bp_get_group_forum_post_count( $args = '' ) {
		global $groups_template;

		$defaults = array(
			'showtext' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !$forum_id = groups_get_groupmeta( $groups_template->group->id, 'forum_id' ) )
			return false;

		if ( !bp_is_active( 'forums' ) )
			return false;

		if ( !$groups_template->group->forum_counts )
			$groups_template->group->forum_counts = bp_forums_get_forum_topicpost_count( (int)$forum_id );

		if ( (bool) $showtext ) {
			if ( 1 == (int) $groups_template->group->forum_counts[0]->posts )
				$total_posts = sprintf( __( '%d post', 'buddypress' ), (int) $groups_template->group->forum_counts[0]->posts );
			else
				$total_posts = sprintf( __( '%d posts', 'buddypress' ), (int) $groups_template->group->forum_counts[0]->posts );
		} else {
			$total_posts = (int) $groups_template->group->forum_counts[0]->posts;
		}

		return apply_filters( 'bp_get_group_forum_post_count', $total_posts, (bool)$showtext );
	}

function bp_group_is_forum_enabled( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	if ( bp_is_active( 'forums' ) ) {
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

/**
 * Get the 'checked' value, if needed, for a given invite_status on the group create/admin screens
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 *
 * @param str $setting The setting you want to check against ('members', 'mods', or 'admins')
 * @param obj $group (optional) The group whose status you want to check
 */
function bp_group_show_invite_status_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$invite_status = bp_group_get_invite_status( $group_id );

	if ( $setting == $invite_status )
		echo ' checked="checked"';
}

/**
 * Get the invite status of a group
 *
 * 'invite_status' became part of BuddyPress in BP 1.5. In order to provide backward compatibility,
 * groups without a status set will default to 'members', ie all members in a group can send
 * invitations. Filter 'bp_group_invite_status_fallback' to change this fallback behavior.
 *
 * This function can be used either in or out of the loop.
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 *
 * @param int $group_id (optional) The id of the group whose status you want to check
 * @return mixed Returns false when no group can be found. Otherwise returns the group invite
 *    status, from among 'members', 'mods', and 'admins'
 */
function bp_group_get_invite_status( $group_id = false ) {
	global $bp, $groups_template;

	if ( !$group_id ) {
		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first
			$group_id = $bp->groups->current_group->id;
		} else if ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$invite_status = groups_get_groupmeta( $group_id, 'invite_status' );

	// Backward compatibility. When 'invite_status' is not set, fall back to a default value
	if ( !$invite_status ) {
		$invite_status = apply_filters( 'bp_group_invite_status_fallback', 'members' );
	}

	return apply_filters( 'bp_group_get_invite_status', $invite_status, $group_id );
}

/**
 * Can the logged-in user send invitations in the specified group?
 *
 * @package BuddyPress
 * @subpackage Groups Template
 * @since 1.5
 *
 * @param int $group_id (optional) The id of the group whose status you want to check
 * @return bool $can_send_invites
 */
function bp_groups_user_can_send_invites( $group_id = false ) {
	global $bp;

	$can_send_invites = false;
	$invite_status    = false;

	if ( is_user_logged_in() ) {
		if ( is_super_admin() ) {
			// Super admins can always send invitations
			$can_send_invites = true;

		} else {
			// If no $group_id is provided, default to the current group id
			if ( !$group_id )
				$group_id = isset( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : 0;

			// If no group has been found, bail
			if ( !$group_id )
				return false;

			$invite_status = bp_group_get_invite_status( $group_id );
			if ( !$invite_status )
				return false;

			switch ( $invite_status ) {
				case 'admins' :
					if ( groups_is_user_admin( bp_loggedin_user_id(), $group_id ) )
						$can_send_invites = true;
					break;

				case 'mods' :
					if ( groups_is_user_mod( bp_loggedin_user_id(), $group_id ) || groups_is_user_admin( bp_loggedin_user_id(), $group_id ) )
						$can_send_invites = true;
					break;

				case 'members' :
					if ( groups_is_user_member( bp_loggedin_user_id(), $group_id ) )
						$can_send_invites = true;
					break;
			}
		}
	}

	return apply_filters( 'bp_groups_user_can_send_invites', $can_send_invites, $group_id, $invite_status );
}

/**
 * Since BuddyPress 1.0, this generated the group settings admin/member screen.
 * As of BuddyPress 1.5 (r4489), and because this function outputs HTML, it was moved into /bp-default/groups/single/admin.php.
 *
 * @deprecated 1.5
 * @deprecated No longer used.
 * @since 1.0
 * @todo Remove in 1.4
 */
function bp_group_admin_memberlist( $admin_list = false, $group = false ) {
	global $groups_template;

	_deprecated_function( __FUNCTION__, '1.5', 'No longer used. See /bp-default/groups/single/admin.php' );

	if ( empty( $group ) )
		$group =& $groups_template->group;


	if ( $admins = groups_get_group_admins( $group->id ) ) : ?>

		<ul id="admins-list" class="item-list<?php if ( !empty( $admin_list ) ) : ?> single-line<?php endif; ?>">

		<?php foreach ( (array)$admins as $admin ) { ?>

			<?php if ( !empty( $admin_list ) ) : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?>

				<h5>

					<?php echo bp_core_get_userlink( $admin->user_id ); ?>

					<span class="small">
						<a class="button confirm admin-demote-to-member" href="<?php bp_group_member_demote_link($admin->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php else : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?>

				<h5><?php echo bp_core_get_userlink( $admin->user_id ) ?></h5>
				<span class="activity">
					<?php echo bp_core_get_last_activity( strtotime( $admin->date_modified ), __( 'joined %s', 'buddypress') ); ?>
				</span>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<div class="action">

						<?php bp_add_friend_button( $admin->user_id ); ?>

					</div>

				<?php endif; ?>

			</li>

			<?php endif;
		} ?>

		</ul>

	<?php else : ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no administrators', 'buddypress' ); ?></p>
		</div>

	<?php endif;
}

function bp_group_mod_memberlist( $admin_list = false, $group = false ) {
	global $groups_template, $group_mods;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( $group_mods = groups_get_group_mods( $group->id ) ) { ?>

		<ul id="mods-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">

		<?php foreach ( (array)$group_mods as $mod ) { ?>

			<?php if ( !empty( $admin_list ) ) { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?>

				<h5>
					<?php echo bp_core_get_userlink( $mod->user_id ); ?>

					<span class="small">
						<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => $mod->user_id ) ) ?>" class="button confirm mod-promote-to-admin" title="<?php _e( 'Promote to Admin', 'buddypress' ); ?>"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>
						<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link($mod->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php } else { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) ?>

				<h5><?php echo bp_core_get_userlink( $mod->user_id ) ?></h5>

				<span class="activity"><?php echo bp_core_get_last_activity( strtotime( $mod->date_modified ), __( 'joined %s', 'buddypress') ); ?></span>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

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

function bp_group_member_promote_mod_link( $args = '' ) {
	echo bp_get_group_member_promote_mod_link( $args );
}
	function bp_get_group_member_promote_mod_link( $args = '' ) {
		global $members_template, $groups_template, $bp;

		$defaults = array(
			'user_id' => $members_template->member->user_id,
			'group'   => &$groups_template->group
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_group_member_promote_mod_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/promote/mod/' . $user_id, 'groups_promote_member' ) );
	}

function bp_group_member_promote_admin_link( $args = '' ) {
	echo bp_get_group_member_promote_admin_link( $args );
}
	function bp_get_group_member_promote_admin_link( $args = '' ) {
		global $members_template, $groups_template, $bp;

		$defaults = array(
			'user_id' => !empty( $members_template->member->user_id ) ? $members_template->member->user_id : false,
			'group'   => &$groups_template->group
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_group_member_promote_admin_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/promote/admin/' . $user_id, 'groups_promote_member' ) );
	}

function bp_group_member_demote_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_demote_link( $user_id );
}
	function bp_get_group_member_demote_link( $user_id = 0, $group = false ) {
		global $members_template, $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;

		return apply_filters( 'bp_get_group_member_demote_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/demote/' . $user_id, 'groups_demote_member' ) );
	}

function bp_group_member_ban_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_ban_link( $user_id );
}
	function bp_get_group_member_ban_link( $user_id = 0, $group = false ) {
		global $members_template, $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_ban_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/ban/' . $user_id, 'groups_ban_member' ) );
	}

function bp_group_member_unban_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_unban_link( $user_id );
}
	function bp_get_group_member_unban_link( $user_id = 0, $group = false ) {
		global $members_template, $groups_template;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_unban_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/unban/' . $user_id, 'groups_unban_member' ) );
	}


function bp_group_member_remove_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_group_member_remove_link( $user_id );
}
	function bp_get_group_member_remove_link( $user_id = 0, $group = false ) {
		global $members_template, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_remove_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/remove/' . $user_id, 'groups_remove_member' ) );
	}

function bp_group_admin_tabs( $group = false ) {
	global $bp, $groups_template;

	if ( !$group )
		$group = ( $groups_template->group ) ? $groups_template->group : $bp->groups->current_group;

	$current_tab = bp_action_variable( 0 );
?>
	<?php if ( $bp->is_item_admin || $bp->is_item_mod ) { ?>
		<li<?php if ( 'edit-details' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/edit-details"><?php _e( 'Details', 'buddypress' ); ?></a></li>
	<?php } ?>

	<?php
		if ( !$bp->is_item_admin )
			return false;
	?>
	<li<?php if ( 'group-settings' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/group-settings"><?php _e( 'Settings', 'buddypress' ); ?></a></li>

	<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?>
		<li<?php if ( 'group-avatar'   == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/group-avatar"><?php _e( 'Avatar', 'buddypress' ); ?></a></li>
	<?php endif; ?>

	<li<?php if ( 'manage-members' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/manage-members"><?php _e( 'Members', 'buddypress' ); ?></a></li>

	<?php if ( $groups_template->group->status == 'private' ) : ?>
		<li<?php if ( 'membership-requests' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/membership-requests"><?php _e( 'Requests', 'buddypress' ); ?></a></li>
	<?php endif; ?>

	<?php do_action( 'groups_admin_tabs', $current_tab, $group->slug ) ?>

	<li<?php if ( 'delete-group' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug ?>/admin/delete-group"><?php _e( 'Delete', 'buddypress' ); ?></a></li>
<?php
}

function bp_group_total_for_member() {
	echo bp_get_group_total_for_member();
}
	function bp_get_group_total_for_member() {
		return apply_filters( 'bp_get_group_total_for_member', BP_Groups_Member::total_group_count() );
	}

function bp_group_form_action( $page ) {
	echo bp_get_group_form_action( $page );
}
	function bp_get_group_form_action( $page, $group = false ) {
		global $bp, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_form_action', bp_get_group_permalink( $group ) . $page );
	}

function bp_group_admin_form_action( $page = false ) {
	echo bp_get_group_admin_form_action( $page );
}
	function bp_get_group_admin_form_action( $page = false, $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !$page )
			$page = bp_action_variable( 0 );

		return apply_filters( 'bp_group_admin_form_action', bp_get_group_permalink( $group ) . 'admin/' . $page );
	}

function bp_group_has_requested_membership( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	if ( groups_check_for_membership_request( bp_loggedin_user_id(), $group->id ) )
		return true;

	return false;
}

/**
 * bp_group_is_member()
 *
 * Checks if current user is member of a group.
 *
 * @uses is_super_admin Check if current user is super admin
 * @uses apply_filters Creates bp_group_is_member filter and passes $is_member
 * @usedby groups/activity.php, groups/single/forum/edit.php, groups/single/forum/topic.php to determine template part visibility
 * @global array $bp BuddyPress Master global
 * @global object $groups_template Current Group (usually in template loop)
 * @param object $group Group to check is_member
 * @return bool If user is member of group or not
 */
function bp_group_is_member( $group = false ) {
	global $bp, $groups_template;

	// Site admins always have access
	if ( $bp->loggedin_user->is_super_admin )
		return true;

	if ( !$group )
		$group =& $groups_template->group;

	return apply_filters( 'bp_group_is_member', !empty( $group->is_member ) );
}

/**
 * Checks if a user is banned from a group.
 *
 * If this function is invoked inside the groups template loop (e.g. the group directory), then
 * check $groups_template->group->is_banned instead of making another SQL query.
 * However, if used in a single group's pages, we must use groups_is_user_banned().
 *
 * @global object $bp BuddyPress global settings
 * @global BP_Groups_Template $groups_template Group template loop object
 * @param object $group Group to check if user is banned from the group
 * @param int $user_id
 * @return bool If user is banned from the group or not
 * @since 1.5
 */
function bp_group_is_user_banned( $group = false, $user_id = 0 ) {
	global $bp, $groups_template;

	// Site admins always have access
	if ( $bp->loggedin_user->is_super_admin )
		return false;

	if ( !$group ) {
		$group =& $groups_template->group;

		if ( !$user_id && isset( $group->is_banned ) )
			return apply_filters( 'bp_group_is_user_banned', $group->is_banned );
	}

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	return apply_filters( 'bp_group_is_user_banned', groups_is_user_banned( $user_id, $group->id ) );
}

function bp_group_accept_invite_link() {
	echo bp_get_group_accept_invite_link();
}
	function bp_get_group_accept_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_accept_invite_link', wp_nonce_url( $bp->loggedin_user->domain . bp_get_groups_slug() . '/invites/accept/' . $group->id, 'groups_accept_invite' ) );
	}

function bp_group_reject_invite_link() {
	echo bp_get_group_reject_invite_link();
}
	function bp_get_group_reject_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_reject_invite_link', wp_nonce_url( $bp->loggedin_user->domain . bp_get_groups_slug() . '/invites/reject/' . $group->id, 'groups_reject_invite' ) );
	}

function bp_group_leave_confirm_link() {
	echo bp_get_group_leave_confirm_link();
}
	function bp_get_group_leave_confirm_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_leave_confirm_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group/yes', 'groups_leave_group' ) );
	}

function bp_group_leave_reject_link() {
	echo bp_get_group_leave_reject_link();
}
	function bp_get_group_leave_reject_link( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_leave_reject_link', bp_get_group_permalink( $group ) );
	}

function bp_group_send_invite_form_action() {
	echo bp_get_group_send_invite_form_action();
}
	function bp_get_group_send_invite_form_action( $group = false ) {
		global $groups_template, $bp;

		if ( !$group )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_send_invite_form_action', bp_get_group_permalink( $group ) . 'send-invites/send' );
	}

function bp_has_friends_to_invite( $group = false ) {
	global $groups_template, $bp;

	if ( !bp_is_active( 'friends' ) )
		return false;

	if ( !$group )
		$group =& $groups_template->group;

	if ( !friends_check_user_has_friends( $bp->loggedin_user->id ) || !friends_count_invitable_friends( $bp->loggedin_user->id, $group->id ) )
		return false;

	return true;
}

function bp_group_new_topic_button( $group = false ) {
	echo bp_get_group_new_topic_button();
}
	function bp_get_group_new_topic_button( $group = false ) {
		global $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !is_user_logged_in() || bp_group_is_user_banned() || !bp_is_group_forum() || bp_is_group_forum_topic() )
			return false;

		$button = bp_button( array (
			'id'                => 'new_topic',
			'component'         => 'groups',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_class'     => 'group-button',
			'link_href'         => '#post-new',
			'link_class'        => 'group-button show-hide-new',
			'link_id'           => 'new-topic-button',
			'link_text'         => __( 'New Topic', 'buddypress' ),
			'link_title'        => __( 'New Topic', 'buddypress' ),
		) );

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_group_new_topic_button', $button ) );
	}

function bp_group_join_button( $group = false ) {
	echo bp_get_group_join_button( $group );
}
	function bp_get_group_join_button( $group = false ) {
		global $bp, $groups_template;

		if ( !$group )
			$group =& $groups_template->group;

		if ( !is_user_logged_in() || bp_group_is_user_banned( $group ) )
			return false;

		// Group creation was not completed or status is unknown
		if ( !$group->status )
			return false;

		// Already a member
		if ( $group->is_member ) {

			// Stop sole admins from abandoning their group
	 		$group_admins = groups_get_group_admins( $group->id );
		 	if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == $bp->loggedin_user->id )
				return false;

			$button = array(
				'id'                => 'leave_group',
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'group-button ' . $group->status,
				'wrapper_id'        => 'groupbutton-' . $group->id,
				'link_href'         => wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ),
				'link_text'         => __( 'Leave Group', 'buddypress' ),
				'link_title'        => __( 'Leave Group', 'buddypress' ),
				'link_class'        => 'group-button leave-group',
			);

		// Not a member
		} else {

			// Show different buttons based on group status
			switch ( $group->status ) {
				case 'hidden' :
					return false;
					break;

				case 'public':
					$button = array(
						'id'                => 'join_group',
						'component'         => 'groups',
						'must_be_logged_in' => true,
						'block_self'        => false,
						'wrapper_class'     => 'group-button ' . $group->status,
						'wrapper_id'        => 'groupbutton-' . $group->id,
						'link_href'         => wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' ),
						'link_text'         => __( 'Join Group', 'buddypress' ),
						'link_title'        => __( 'Join Group', 'buddypress' ),
						'link_class'        => 'group-button join-group',
					);
					break;

				case 'private' :

					// Member has not requested membership yet
					if ( !bp_group_has_requested_membership( $group ) ) {
						$button = array(
							'id'                => 'request_membership',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => wp_nonce_url( bp_get_group_permalink( $group ) . 'request-membership', 'groups_request_membership' ),
							'link_text'         => __( 'Request Membership', 'buddypress' ),
							'link_title'        => __( 'Request Membership', 'buddypress' ),
							'link_class'        => 'group-button request-membership',
						);

					// Member has requested membership already
					} else {
						$button = array(
							'id'                => 'membership_requested',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button pending ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => bp_get_group_permalink( $group ),
							'link_text'         => __( 'Request Sent', 'buddypress' ),
							'link_title'        => __( 'Request Sent', 'buddypress' ),
							'link_class'        => 'group-button pending membership-requested',
						);
					}

					break;
			}
		}

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_group_join_button', $button ) );
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

function bp_group_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['groups_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['groups_search'] ) . '" name="search_terms" />';
	}
}

function bp_total_group_count() {
	echo bp_get_total_group_count();
}
	function bp_get_total_group_count() {
		return apply_filters( 'bp_get_total_group_count', groups_get_total_group_count() );
	}

function bp_total_group_count_for_user( $user_id = 0 ) {
	echo bp_get_total_group_count_for_user( $user_id );
}
	function bp_get_total_group_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_group_count_for_user', groups_total_groups_for_user( $user_id ) );
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

	function bp_groups_group_members_template( $group_id, $per_page, $max, $exclude_admins_mods, $exclude_banned, $exclude ) {
		$this->__construct( $group_id, $per_page, $max, $exclude_admins_mods, $exclude_banned, $exclude );
	}

	function __construct( $group_id, $per_page, $max, $exclude_admins_mods, $exclude_banned, $exclude ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		$this->members = BP_Groups_Member::get_all_for_group( $group_id, $this->pag_num, $this->pag_page, $exclude_admins_mods, $exclude_banned, $exclude );

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
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
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
		'group_id' => bp_get_current_group_id(),
		'per_page' => 20,
		'max' => false,
		'exclude' => false,
		'exclude_admins_mods' => 1,
		'exclude_banned' => 1
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$members_template = new BP_Groups_Group_Members_Template( $group_id, $per_page, $max, (int)$exclude_admins_mods, (int)$exclude_banned, $exclude );
	return apply_filters( 'bp_group_has_members', $members_template->has_members(), $members_template );
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

		return apply_filters( 'bp_get_group_member_avatar', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'full', 'email' => $members_template->member->user_email, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) );
	}

function bp_group_member_avatar_thumb() {
	echo bp_get_group_member_avatar_thumb();
}
	function bp_get_group_member_avatar_thumb() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'thumb', 'email' => $members_template->member->user_email, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) );
	}

function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_group_member_avatar_mini( $width, $height );
}
	function bp_get_group_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		return apply_filters( 'bp_get_group_member_avatar_mini', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'thumb', 'width' => $width, 'height' => $height, 'email' => $members_template->member->user_email, 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) );
	}

function bp_group_member_name() {
	echo bp_get_group_member_name();
}
	function bp_get_group_member_name() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_name', $members_template->member->display_name );
	}

function bp_group_member_url() {
	echo bp_get_group_member_url();
}
	function bp_get_group_member_url() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_url', bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}

function bp_group_member_link() {
	echo bp_get_group_member_link();
}
	function bp_get_group_member_link() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_link', '<a href="' . bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) . '">' . $members_template->member->display_name . '</a>' );
	}

function bp_group_member_domain() {
	echo bp_get_group_member_domain();
}
	function bp_get_group_member_domain() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_domain', bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}

function bp_group_member_is_friend() {
	echo bp_get_group_member_is_friend();
}
	function bp_get_group_member_is_friend() {
		global $members_template;

		if ( !isset( $members_template->member->is_friend ) )
			$friend_status = 'not_friends';
		else
			$friend_status = ( 0 == $members_template->member->is_friend ) ? 'pending' : 'is_friend';

		return apply_filters( 'bp_get_group_member_is_friend', $friend_status );
	}

function bp_group_member_is_banned() {
	echo bp_get_group_member_is_banned();
}
	function bp_get_group_member_is_banned() {
		global $members_template, $groups_template;

		return apply_filters( 'bp_get_group_member_is_banned', $members_template->member->is_banned );
	}

function bp_group_member_css_class() {
	global $members_template;

	if ( $members_template->member->is_banned )
		echo apply_filters( 'bp_group_member_css_class', 'banned-user' );
}

function bp_group_member_joined_since() {
	echo bp_get_group_member_joined_since();
}
	function bp_get_group_member_joined_since() {
		global $members_template;

		return apply_filters( 'bp_get_group_member_joined_since', bp_core_get_last_activity( $members_template->member->date_modified, __( 'joined %s', 'buddypress') ) );
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

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num = bp_core_number_format( $start_num );
		$to_num = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total = bp_core_number_format( $members_template->total_member_count );

		return apply_filters( 'bp_get_group_member_pagination_count', sprintf( __( 'Viewing members %1$s to %2$s (of %3$s members)', 'buddypress' ), $from_num, $to_num, $total ) );
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

/**
 * Determine if the current logged in user can create groups.
 *
 * @package BuddyPress Groups
 * @since 1.5
 *
 * @uses apply_filters() To call 'bp_user_can_create_groups'.
 * @uses bp_get_option() To retrieve value of 'bp_restrict_group_creation'. Defaults to 0.
 * @uses is_super_admin() To determine if current user if super admin.
 *
 * @return bool True if user can create groups. False otherwise.
 */
function bp_user_can_create_groups() {
	// Super admin can always create groups
	if ( is_super_admin() )
		return true;

	// Get group creation option, default to 0 (allowed)
	$restricted = (int) bp_get_option( 'bp_restrict_group_creation', 0 );

	// Allow by default
	$can_create = true;

	// Are regular users restricted?
	if ( $restricted )
		$can_create = false;

	return apply_filters( 'bp_user_can_create_groups', $can_create, $restricted );
}

function bp_group_creation_tabs() {
	global $bp;

	if ( !is_array( $bp->groups->group_creation_steps ) )
		return false;

	if ( !$bp->groups->current_create_step )
		$bp->groups->current_create_step = array_shift( array_keys( $bp->groups->group_creation_steps ) );

	$counter = 1;

	foreach ( (array)$bp->groups->group_creation_steps as $slug => $step ) {
		$is_enabled = bp_are_previous_group_creation_steps_complete( $slug ); ?>

		<li<?php if ( $bp->groups->current_create_step == $slug ) : ?> class="current"<?php endif; ?>><?php if ( $is_enabled ) : ?><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() ?>/create/step/<?php echo $slug ?>/"><?php else: ?><span><?php endif; ?><?php echo $counter ?>. <?php echo $step['name'] ?><?php if ( $is_enabled ) : ?></a><?php else: ?></span><?php endif ?></li><?php
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

		if ( !bp_action_variable( 1 ) )
			$bp->action_variables[1] = array_shift( array_keys( $bp->groups->group_creation_steps ) );

		return apply_filters( 'bp_get_group_creation_form_action', bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/' . bp_action_variable( 1 ) );
	}

function bp_is_group_creation_step( $step_slug ) {
	global $bp;

	/* Make sure we are in the groups component */
	if ( !bp_is_groups_component() || !bp_is_current_action( 'create' ) )
		return false;

	/* If this the first step, we can just accept and return true */
	if ( !bp_action_variable( 1 ) && array_shift( array_keys( $bp->groups->group_creation_steps ) ) == $step_slug )
		return true;

	/* Before allowing a user to see a group creation step we must make sure previous steps are completed */
	if ( !bp_is_first_group_creation_step() ) {
		if ( !bp_are_previous_group_creation_steps_complete( $step_slug ) )
			return false;
	}

	/* Check the current step against the step parameter */
	if ( bp_is_action_variable( $step_slug ) )
		return true;

	return false;
}

function bp_is_group_creation_step_complete( $step_slugs ) {
	global $bp;

	if ( !isset( $bp->groups->completed_create_steps ) )
		return false;

	if ( is_array( $step_slugs ) ) {
		$found = true;

		foreach ( (array)$step_slugs as $step_slug ) {
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
	foreach ( (array)$bp->groups->group_creation_steps as $slug => $name ) {
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

		if ( isset( $bp->groups->new_group_id ) )
			$new_group_id = $bp->groups->new_group_id;
		else
			$new_group_id = 0;

		return apply_filters( 'bp_get_new_group_id', $new_group_id );
	}

function bp_new_group_name() {
	echo bp_get_new_group_name();
}
	function bp_get_new_group_name() {
		global $bp;

		if ( isset( $bp->groups->current_group->name ) )
			$name = $bp->groups->current_group->name;
		else
			$name = '';

		return apply_filters( 'bp_get_new_group_name', $name );
	}

function bp_new_group_description() {
	echo bp_get_new_group_description();
}
	function bp_get_new_group_description() {
		global $bp;

		if ( isset( $bp->groups->current_group->description ) )
			$description = $bp->groups->current_group->description;
		else
			$description = '';

		return apply_filters( 'bp_get_new_group_description', $description );
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
			'alt' => __( 'Group avatar', 'buddypress' ),
			'no_grav' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_new_group_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group', 'type' => $type, 'avatar_dir' => 'group-avatars', 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'no_grav' => $no_grav ) ) );
	}

function bp_group_creation_previous_link() {
	echo bp_get_group_creation_previous_link();
}
	function bp_get_group_creation_previous_link() {
		global $bp;

		foreach ( (array)$bp->groups->group_creation_steps as $slug => $name ) {
			if ( bp_is_action_variable( $slug ) )
				break;

			$previous_steps[] = $slug;
		}

		return apply_filters( 'bp_get_group_creation_previous_link', trailingslashit( bp_get_root_domain() ) . bp_get_groups_slug() . '/create/step/' . array_pop( $previous_steps ) );
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

		if ( !bp_is_active( 'friends' ) )
			return false;

		$defaults = array(
			'group_id'  => false,
			'separator' => 'li'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( empty( $group_id ) )
			$group_id = !empty( $bp->groups->new_group_id ) ? $bp->groups->new_group_id : $bp->groups->current_group->id;

		if ( $friends = friends_get_friends_invite_list( $bp->loggedin_user->id, $group_id ) ) {
			$invites = groups_get_invites_for_group( $bp->loggedin_user->id, $group_id );

			for ( $i = 0, $count = count( $friends ); $i < $count; ++$i ) {
				$checked = '';

				if ( !empty( $invites ) ) {
					if ( in_array( $friends[$i]['id'], $invites ) )
						$checked = ' checked="checked"';
				}

				$items[] = '<' . $separator . '><input' . $checked . ' type="checkbox" name="friends[]" id="f-' . $friends[$i]['id'] . '" value="' . esc_attr( $friends[$i]['id'] ) . '" /> ' . $friends[$i]['full_name'] . '</' . $separator . '>';
			}
		}

		if ( !empty( $items ) )
			return implode( "\n", (array)$items );

		return false;
	}

function bp_directory_groups_search_form() {
	global $bp;

	$default_search_value = bp_get_search_default_text( 'groups' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value; ?>

	<form action="" method="get" id="search-groups-form">
		<label><input type="text" name="s" id="groups_search" value="<?php echo esc_attr( $search_value ) ?>"  onfocus="if (this.value == '<?php echo $default_search_value ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo $default_search_value ?>';}" /></label>
		<input type="submit" id="groups_search_submit" name="groups_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>

<?php
}

/**
 * Displays group header tabs
 *
 * @package BuddyPress
 * @todo Deprecate?
 */
function bp_groups_header_tabs() {
	global $create_group_step, $completed_to_step;
?>
	<li<?php if ( !bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/recently-active"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'recently-joined', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/recently-joined"><?php _e( 'Recently Joined', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'most-popular', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/most-popular"><?php _e( 'Most Popular', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'admin-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/admin-of"><?php _e( 'Administrator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'mod-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/mod-of"><?php _e( 'Moderator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'alphabetically' ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . bp_get_groups_slug() ?>/my-groups/alphabetically"><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>
<?php
	do_action( 'groups_header_tabs' );
}

/**
 * Displays group filter titles
 *
 * @package BuddyPress
 * @todo Deprecate?
 */
function bp_groups_filter_title() {
	$current_filter = bp_action_variable( 0 );

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
	if ( !bp_is_groups_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_is_action_variable( $slug ) )
		return true;

	return false;
}

/************************************************************************************
 * Group Avatar Template Tags
 **/

function bp_group_current_avatar() {
	global $bp;

	if ( $bp->groups->current_group->avatar_full ) { ?>

		<img src="<?php echo esc_attr( $bp->groups->current_group->avatar_full ) ?>" alt="<?php _e( 'Group Avatar', 'buddypress' ) ?>" class="avatar" />

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
		$this->__construct( $group_id, $per_page, $max );
	}


	function __construct( $group_id, $per_page, $max ) {

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
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size' => 1
		) );
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

		if ( $this->request_count > 0 )
			$this->request = $this->requests[0];
	}

	function requests() {
		if ( $this->current_request + 1 < $this->request_count ) {
			return true;
		} elseif ( $this->current_request + 1 == $this->request_count ) {
			do_action('group_request_loop_end');
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
			do_action('group_request_loop_start');
	}
}

function bp_group_has_membership_requests( $args = '' ) {
	global $requests_template, $groups_template;

	$defaults = array(
		'group_id' => $groups_template->group->id,
		'per_page' => 10,
		'max'      => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$requests_template = new BP_Groups_Membership_Requests_Template( $group_id, $per_page, $max );
	return apply_filters( 'bp_group_has_membership_requests', $requests_template->has_requests(), $requests_template );
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

	echo apply_filters( 'bp_group_request_user_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $requests_template->request->user_id, 'type' => 'thumb', 'alt' => __( 'Profile picture of %s', 'buddypress' ) ) ) );
}

function bp_group_request_reject_link() {
	echo bp_get_group_request_reject_link();
}
	function bp_get_group_request_reject_link() {
		global $requests_template, $groups_template;

		return apply_filters( 'bp_get_group_request_reject_link', wp_nonce_url( bp_get_group_permalink( $groups_template->group ) . '/admin/membership-requests/reject/' . $requests_template->request->id, 'groups_reject_membership_request' ) );
	}

function bp_group_request_accept_link() {
	echo bp_get_group_request_accept_link();
}
	function bp_get_group_request_accept_link() {
		global $requests_template, $groups_template;

		return apply_filters( 'bp_get_group_request_accept_link', wp_nonce_url( bp_get_group_permalink( $groups_template->group ) . '/admin/membership-requests/accept/' . $requests_template->request->id, 'groups_accept_membership_request' ) );
	}

function bp_group_request_user_link() {
	echo bp_get_group_request_user_link();
}
	function bp_get_group_request_user_link() {
		global $requests_template;

		return apply_filters( 'bp_get_group_request_user_link', bp_core_get_userlink( $requests_template->request->user_id ) );
	}

function bp_group_request_time_since_requested() {
	global $requests_template;

	echo apply_filters( 'bp_group_request_time_since_requested', sprintf( __( 'requested %s', 'buddypress' ), bp_core_time_since( strtotime( $requests_template->request->date_modified ) ) ) );
}

function bp_group_request_comment() {
	global $requests_template;

	echo apply_filters( 'bp_group_request_comment', strip_tags( stripslashes( $requests_template->request->comments ) ) );
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
		$this->__construct( $user_id, $group_id );
	}

	function __construct( $user_id, $group_id ) {

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
		if ( $this->invite_count > 0 )
			$this->invite = $this->invites[0];
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
		global $invite, $group_id;

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
		// Backwards compatibility
		if ( !empty( $bp->groups->current_group ) )
			$group_id = $bp->groups->current_group->id;

		if ( !empty( $bp->groups->new_group_id ) )
			$group_id = $bp->groups->new_group_id;
	}

	if ( !$group_id )
		return false;

	$invites_template = new BP_Groups_Invite_Template( $user_id, $group_id );
	return apply_filters( 'bp_group_has_invites', $invites_template->has_invites(), $invites_template );
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

		return wp_nonce_url( site_url( bp_get_groups_slug() . '/' . $invites_template->invite->group_id . '/invites/remove/' . $invites_template->invite->user->id ), 'groups_invite_uninvite_user' );
	}

/***
 * Groups RSS Feed Template Tags
 */

/**
 * Hook group activity feed to <head>
 *
 * @since 1.5
 */
function bp_groups_activity_feed() {
	if ( !bp_is_active( 'groups' ) || !bp_is_active( 'activity' ) || !bp_is_group() )
		return; ?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php bp_current_group_name() ?> | <?php _e( 'Group Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_group_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_groups_activity_feed' );

function bp_group_activity_feed_link() {
	echo bp_get_group_activity_feed_link();
}
	function bp_get_group_activity_feed_link() {
		global $bp;

		return apply_filters( 'bp_get_group_activity_feed_link', bp_get_group_permalink( $bp->groups->current_group ) . 'feed/' );
	}

/**
 * Echoes the output of bp_get_current_group_id()
 *
 * @package BuddyPress
 * @since 1.5
 */
function bp_current_group_id() {
	echo bp_get_current_group_id();
}
	/**
	 * Returns the ID of the current group
	 *
	 * @package BuddyPress
	 * @since 1.5
	 * @uses apply_filters() Filter bp_get_current_group_id to modify this output
	 *
	 * @return int $current_group_id The id of the current group, if there is one
	 */
	function bp_get_current_group_id() {
		$current_group = groups_get_current_group();

		$current_group_id = isset( $current_group->id ) ? (int)$current_group->id : 0;

		return apply_filters( 'bp_get_current_group_id', $current_group_id, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_slug()
 *
 * @package BuddyPress
 * @since 1.5
 */
function bp_current_group_slug() {
	echo bp_get_current_group_slug();
}
	/**
	 * Returns the slug of the current group
	 *
	 * @package BuddyPress
	 * @since 1.5
	 * @uses apply_filters() Filter bp_get_current_group_slug to modify this output
	 *
	 * @return str $current_group_slug The slug of the current group, if there is one
	 */
	function bp_get_current_group_slug() {
		$current_group = groups_get_current_group();

		$current_group_slug = isset( $current_group->slug ) ? $current_group->slug : '';

		return apply_filters( 'bp_get_current_group_slug', $current_group_slug, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_name()
 *
 * @package BuddyPress
 */
function bp_current_group_name() {
	echo bp_get_current_group_name();
}
	/**
	 * Returns the name of the current group
	 *
	 * @package BuddyPress
	 * @since 1.5
	 * @uses apply_filters() Filter bp_get_current_group_name to modify this output
	 *
	 * @return str The name of the current group, if there is one
	 */
	function bp_get_current_group_name() {
		global $bp;

		$name = apply_filters( 'bp_get_group_name', $bp->groups->current_group->name );
		return apply_filters( 'bp_get_current_group_name', $name );
	}

function bp_groups_action_link( $action = '', $query_args = '', $nonce = false ) {
	echo bp_get_groups_action_link( $action, $query_args, $nonce );
}
	function bp_get_groups_action_link( $action = '', $query_args = '', $nonce = false ) {
		global $bp;

		// Must be displayed user
		if ( empty( $bp->groups->current_group->id ) )
			return;

		// Append $action to $url if there is no $type
		if ( !empty( $action ) )
			$url = bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $bp->groups->current_group->slug . '/' . $action;
		else
			$url = bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $bp->groups->current_group->slug;

		// Add a slash at the end of our user url
		$url = trailingslashit( $url );

		// Add possible query arg
		if ( !empty( $query_args ) && is_array( $query_args ) )
			$url = add_query_arg( $query_args, $url );

		// To nonce, or not to nonce...
		if ( true === $nonce )
			$url = wp_nonce_url( $url );
		elseif ( is_string( $nonce ) )
			$url = wp_nonce_url( $url, $nonce );

		// Return the url, if there is one
		if ( !empty( $url ) )
			return $url;
	}
?>