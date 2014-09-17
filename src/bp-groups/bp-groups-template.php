<?php

/**
 * BuddyPress Groups Template Functions.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the groups component slug.
 *
 * @since BuddyPress (1.5.0)
 */
function bp_groups_slug() {
	echo bp_get_groups_slug();
}
	/**
	 * Return the groups component slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_groups_slug() {
		return apply_filters( 'bp_get_groups_slug', buddypress()->groups->slug );
	}

/**
 * Output the groups component root slug
 *
 * @since BuddyPress (1.5.0)
 */
function bp_groups_root_slug() {
	echo bp_get_groups_root_slug();
}
	/**
	 * Return the groups component root slug
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_groups_root_slug() {
		return apply_filters( 'bp_get_groups_root_slug', buddypress()->groups->root_slug );
	}

/**
 * Output group directory permalink
 *
 * @since BuddyPress (1.5.0)
 */
function bp_groups_directory_permalink() {
	echo bp_get_groups_directory_permalink();
}
	/**
	 * Return group directory permalink
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_groups_directory_permalink() {
		return apply_filters( 'bp_get_groups_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() ) );
	}

/**
 * The main Groups template loop class.
 *
 * Responsible for loading a group of groups into a loop for display.
 */
class BP_Groups_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	var $current_group = -1;

	/**
	 * The number of groups returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	var $group_count;

	/**
	 * Array of groups located by the query.
	 *
	 * @access public
	 * @var array
	 */
	var $groups;

	/**
	 * The group object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	var $group;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	var $in_the_loop;

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	var $pag_links;

	/**
	 * The total number of groups matching the query parameters.
	 *
	 * @access public
	 * @var int
	 */
	var $total_group_count;

	/**
	 * Whether the template loop is for a single group page.
	 *
	 * @access public
	 * @var bool
	 */
	var $single_group = false;

	/**
	 * Field to sort by.
	 *
	 * @access public
	 * @var string
	 */
	var $sort_by;

	/**
	 * Sort order.
	 *
	 * @access public
	 * @var string
	 */
	var $order;

	/**
	 * Constructor method.
	 *
	 * @see BP_Groups_Group::get() for an in-depth description of arguments.
	 *
	 * @param array $args {
	 *     Array of arguments. Accepts all arguments accepted by
	 *     {@link BP_Groups_Group::get()}. In cases where the default
	 *     values of the params differ, they have been discussed below.
	 *     @type int $per_page Default: 20.
	 *     @type int $page Default: 1.
	 * }
	 */
	function __construct( $args = array() ){

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.7', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'type',
				2  => 'page',
				3  => 'per_page',
				4  => 'max',
				5  => 'slug',
				6  => 'search_terms',
				7  => 'populate_extras',
				8  => 'include',
				9  => 'exclude',
				10 => 'show_hidden',
				11 => 'page_arg',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'type'            => 'active',
			'page'            => 1,
			'per_page'        => 20,
			'max'             => false,
			'show_hidden'     => false,
			'page_arg'        => 'grpage',
			'user_id'         => 0,
			'slug'            => false,
			'include'         => false,
			'exclude'         => false,
			'search_terms'    => '',
			'meta_query'      => false,
			'populate_extras' => true,
			'update_meta_cache' => true,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_page = isset( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		if ( bp_current_user_can( 'bp_moderate' ) || ( is_user_logged_in() && $user_id == bp_loggedin_user_id() ) )
			$show_hidden = true;

		if ( 'invites' == $type ) {
			$this->groups = groups_get_invites_for_user( $user_id, $this->pag_num, $this->pag_page, $exclude );
		} else if ( 'single-group' == $type ) {
			$this->single_group = true;

			if ( groups_get_current_group() ) {
				$group = groups_get_current_group();

			} else {
				$group = groups_get_group( array(
					'group_id'        => BP_Groups_Group::get_id_from_slug( $r['slug'] ),
					'populate_extras' => $r['populate_extras'],
				) );
			}

			// backwards compatibility - the 'group_id' variable is not part of the
			// BP_Groups_Group object, but we add it here for devs doing checks against it
			//
			// @see https://buddypress.trac.wordpress.org/changeset/3540
			//
			// this is subject to removal in a future release; devs should check against
			// $group->id instead
			$group->group_id = $group->id;

			$this->groups = array( $group );

		} else {
			$this->groups = groups_get_groups( array(
				'type'              => $type,
				'order'             => $order,
				'orderby'           => $orderby,
				'per_page'          => $this->pag_num,
				'page'              => $this->pag_page,
				'user_id'           => $user_id,
				'search_terms'      => $search_terms,
				'meta_query'        => $meta_query,
				'include'           => $include,
				'exclude'           => $exclude,
				'populate_extras'   => $populate_extras,
				'update_meta_cache' => $update_meta_cache,
				'show_hidden'       => $show_hidden
			) );
		}

		if ( 'invites' == $type ) {
			$this->total_group_count = (int) $this->groups['total'];
			$this->group_count       = (int) $this->groups['total'];
			$this->groups            = $this->groups['groups'];
		} else if ( 'single-group' == $type ) {
			if ( empty( $group->id ) ) {
				$this->total_group_count = 0;
				$this->group_count       = 0;
			} else {
				$this->total_group_count = 1;
				$this->group_count       = 1;
			}
		} else {
			if ( empty( $max ) || $max >= (int) $this->groups['total'] ) {
				$this->total_group_count = (int) $this->groups['total'];
			} else {
				$this->total_group_count = (int) $max;
			}

			$this->groups = $this->groups['groups'];

			if ( !empty( $max ) ) {
				if ( $max >= count( $this->groups ) ) {
					$this->group_count = count( $this->groups );
				} else {
					$this->group_count = (int) $max;
				}
			} else {
				$this->group_count = count( $this->groups );
			}
		}

		// Build pagination links
		if ( (int) $this->total_group_count && (int) $this->pag_num ) {
			$pag_args = array(
				$page_arg => '%#%',
				'num'     => $this->pag_num,
				'sortby'  => $this->sort_by,
				'order'   => $this->order,
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			if ( ! empty( $search_terms ) ) {
				$pag_args['s'] = $search_terms;
			}

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $pag_args, $base ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_group_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Group pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Group pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	/**
	 * Whether there are groups available in the loop.
	 *
	 * @see bp_has_groups()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_groups() {
		if ( $this->group_count )
			return true;

		return false;
	}

	/**
	 * Set up the next group and iterate index.
	 *
	 * @return object The next group to iterate over.
	 */
	function next_group() {
		$this->current_group++;
		$this->group = $this->groups[$this->current_group];

		return $this->group;
	}

	/**
	 * Rewind the groups and reset member index.
	 */
	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}

	/**
	 * Whether there are groups left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_groups()} as part of the while loop
	 * that controls iteration inside the groups loop, eg:
	 *     while ( bp_groups() ) { ...
	 *
	 * @see bp_groups()
	 *
	 * @return bool True if there are more groups to show, otherwise false.
	 */
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

	/**
	 * Set up the current group inside the loop.
	 *
	 * Used by {@link bp_the_group()} to set up the current group data
	 * while looping, so that template tags used during that iteration make
	 * reference to the current member.
	 *
	 * @see bp_the_group()
	 */
	function the_group() {
		$this->in_the_loop = true;
		$this->group       = $this->next_group();

		if ( 0 == $this->current_group ) // loop has just started
			do_action('group_loop_start');
	}
}

/**
 * Start the Groups Template Loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {
 *     Array of parameters. All items are optional.
 *     @type string $type Optional. Shorthand for certain orderby/
 *           order combinations. 'newest', 'active', 'popular',
 *           'alphabetical', 'random'. When present, will override
 *           orderby and order params. Default: null.
 *     @type string $orderby Optional. Property to sort by.
 *           'date_created', 'last_activity', 'total_member_count',
 *           'name', 'random'. Default: 'date_created'.
 *     @type string $order Optional. Sort order. 'ASC' or 'DESC'.
 *           Default: 'DESC'.
 *     @type int $per_page Optional. Number of items to return per page
 *           of results. Default: null (no limit).
 *     @type int $page Optional. Page offset of results to return.
 *           Default: null (no limit).
 *     @type int $user_id Optional. If provided, results will be limited
 *           to groups of which the specified user is a member. Default:
 *           null.
 *     @type string $search_terms Optional. If provided, only groups
 *           whose names or descriptions match the search terms will be
 *           returned. Default: false.
 *     @type array $meta_query Optional. An array of meta_query
 *           conditions. See {@link WP_Meta_Query::queries} for
 *           description.
 *     @type array|string Optional. Array or comma-separated list of
 *           group IDs. Results will be limited to groups within the
 *           list. Default: false.
 *     @type bool $populate_extras Whether to fetch additional
 *           information (such as member count) about groups. Default:
 *           true.
 *     @type array|string Optional. Array or comma-separated list of
 *           group IDs. Results will exclude the listed groups.
 *           Default: false.
 *     @type bool $show_hidden Whether to include hidden groups in
 *           results. Default: false.
 * }
 * @return bool True if there are groups to display that match the params
 */
function bp_has_groups( $args = '' ) {
	global $groups_template;

	/***
	 * Defaults based on the current page & overridden by parsed $args
	 */
	$slug         = false;
	$type         = '';
	$search_terms = false;

	// When looking your own groups, check for two action variables
	if ( bp_is_current_action( 'my-groups' ) ) {
		if ( bp_is_action_variable( 'most-popular', 0 ) ) {
			$type = 'popular';
		} elseif ( bp_is_action_variable( 'alphabetically', 0 ) ) {
			$type = 'alphabetical';
		}

	// When looking at invites, set type to invites
	} elseif ( bp_is_current_action( 'invites' ) ) {
		$type = 'invites';

	// When looking at a single group, set the type and slug
	} elseif ( bp_get_current_group_slug() ) {
		$type = 'single-group';
		$slug = bp_get_current_group_slug();
	}

	// Default search string (too soon to escape here)
	if ( ! empty( $_REQUEST['group-filter-box'] ) ) {
		$search_terms = $_REQUEST['group-filter-box'];
	} elseif ( !empty( $_REQUEST['s'] ) ) {
		$search_terms = $_REQUEST['s'];
	}

	// Parse defaults and requested arguments
	$r = bp_parse_args( $args, array(
		'type'              => $type,
		'order'             => 'DESC',
		'orderby'           => 'last_activity',
		'page'              => 1,
		'per_page'          => 20,
		'max'               => false,
		'show_hidden'       => false,
		'page_arg'          => 'grpage',
		'user_id'           => bp_displayed_user_id(),
		'slug'              => $slug,
		'search_terms'      => $search_terms,
		'meta_query'        => false,
		'include'           => false,
		'exclude'           => false,
		'populate_extras'   => true,
		'update_meta_cache' => true,
	), 'has_groups' );

	// Setup the Groups template global
	$groups_template = new BP_Groups_Template( array(
		'type'              => $r['type'],
		'order'             => $r['order'],
		'orderby'           => $r['orderby'],
		'page'              => (int) $r['page'],
		'per_page'          => (int) $r['per_page'],
		'max'               => (int) $r['max'],
		'show_hidden'       => $r['show_hidden'],
		'page_arg'          => $r['page_arg'],
		'user_id'           => (int) $r['user_id'],
		'slug'              => $r['slug'],
		'search_terms'      => $r['search_terms'],
		'meta_query'        => $r['meta_query'],
		'include'           => $r['include'],
		'exclude'           => $r['exclude'],
		'populate_extras'   => (bool) $r['populate_extras'],
		'update_meta_cache' => (bool) $r['update_meta_cache'],
	) );

	// Filter and return whether or not the groups loop has groups in it
	return apply_filters( 'bp_has_groups', $groups_template->has_groups(), $groups_template, $r );
}

/**
 * Check whether there are more groups to iterate over.
 *
 * @return bool
 */
function bp_groups() {
	global $groups_template;
	return $groups_template->groups();
}

/**
 * Set up the current group inside the loop.
 *
 * @return object
 */
function bp_the_group() {
	global $groups_template;
	return $groups_template->the_group();
}

/**
 * Is the group visible to the currently logged-in user?
 *
 * @param object $group Optional. Group object. Default: current group in loop.
 * @return bool
 */
function bp_group_is_visible( $group = false ) {
	global $groups_template;

	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( 'public' == $group->status ) {
		return true;
	} else {
		if ( groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Output the ID of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current group in loop.
 */
function bp_group_id( $group = false ) {
	echo bp_get_group_id( $group );
}
	/**
	 * Get the ID of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return int
	 */
	function bp_get_group_id( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_id', $group->id );
	}

/**
 * Output the row class of the current group in the loop.
 *
 * @since BuddyPress (1.7.0)
 */
function bp_group_class() {
	echo bp_get_group_class();
}
	/**
	 * Get the row class of the current group in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @return string Row class of the group.
	 */
	function bp_get_group_class() {
		global $groups_template;

		$classes      = array();
		$pos_in_loop  = (int) $groups_template->current_group;

		// If we've only one group in the loop, don't both with odd and even.
		if ( $groups_template->group_count > 1 )
			$classes[] = ( $pos_in_loop % 2 ) ? 'even' : 'odd';
		else
			$classes[] = 'bp-single-group';

		// Group type - public, private, hidden.
		$classes[] = esc_attr( $groups_template->group->status );

		// User's group status
		if ( bp_is_user_active() ) {
			if ( bp_group_is_admin() )
				$classes[] = 'is-admin';

			if ( bp_group_is_member() )
				$classes[] = 'is-member';

			if ( bp_group_is_mod() )
				$classes[] = 'is-mod';
		}

		$classes = apply_filters( 'bp_get_group_class', $classes );
		$classes = array_merge( $classes, array() );
		$retval = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output the name of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_name( $group = false ) {
	echo bp_get_group_name( $group );
}
	/**
	 * Get the name of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_name( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_name', $group->name );
	}

/**
 * Output the type of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_type( $group = false ) {
	echo bp_get_group_type( $group );
}
	/**
	 * Get the type of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_type( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
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

/**
 * Output the status of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_status( $group = false ) {
	echo bp_get_group_status( $group );
}
	/**
	 * Get the status of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_status( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_status', $group->status );
	}

/**
 * Output the group avatar while in the groups loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {
 *      See {@link bp_get_group_avatar()} for description of arguments.
 * }
 */
function bp_group_avatar( $args = '' ) {
	echo bp_get_group_avatar( $args );
}
	/**
	 * Return the group avatar while in the groups loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {
	 *     Array of arguments. See {@link bp_core_fetch_avatar()} for
	 *     detailed description. Default values that differ from that
	 *     function are described below.
	 *     @type string $type Default: 'full'.
	 *     @type string $id Passed to $css_id parameter.
	 * }
	 * @return string
	 */
	function bp_get_group_avatar( $args = '' ) {
		global $groups_template;

		// Bail if avatars are turned off
		// @todo Should we maybe still filter this?
		if ( ! buddypress()->avatar->show_avatars ) {
			return false;
		}

		// Parse the arguments
		$r = bp_parse_args( $args, array(
			'type'   => 'full',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => sprintf( __( 'Group logo of %s', 'buddypress' ), $groups_template->group->name )
		) );

		// Fetch the avatar from the folder
		$avatar = bp_core_fetch_avatar( array(
			'item_id'    => $groups_template->group->id,
			'title'      => $groups_template->group->name,
			'avatar_dir' => 'group-avatars',
			'object'     => 'group',
			'type'       => $r['type'],
			'alt'        => $r['alt'],
			'css_id'     => $r['id'],
			'class'      => $r['class'],
			'width'      => $r['width'],
			'height'     => $r['height']
		) );

		// If No avatar found, provide some backwards compatibility
		if ( empty( $avatar ) ) {
			$avatar = '<img src="' . esc_url( $groups_template->group->avatar_thumb ) . '" class="avatar" alt="' . esc_attr( $groups_template->group->name ) . '" />';
		}

		return apply_filters( 'bp_get_group_avatar', $avatar, $r );
	}

/**
 * Output the group avatar thumbnail while in the groups loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_avatar_thumb( $group = false ) {
	echo bp_get_group_avatar_thumb( $group );
}
	/**
	 * Return the group avatar thumbnail while in the groups loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_avatar_thumb( $group = false ) {
		return bp_get_group_avatar( array(
			'type' => 'thumb',
			'id'   => ! empty( $group->id ) ? $group->id : false
		) );
	}

/**
 * Output the miniature group avatar thumbnail while in the groups loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_avatar_mini( $group = false ) {
	echo bp_get_group_avatar_mini( $group );
}
	/**
	 * Return the miniature group avatar thumbnail while in the groups loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 */
	function bp_get_group_avatar_mini( $group = false ) {
		return bp_get_group_avatar( array(
			'type'   => 'thumb',
			'width'  => 30,
			'height' => 30,
			'id'     => ! empty( $group->id ) ? $group->id : false
		) );
	}

/**
 * Output the 'last active' string for the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_last_active( $group = false ) {
	echo bp_get_group_last_active( $group );
}
	/**
	 * Return the 'last active' string for the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_last_active( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
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

/**
 * Output the permalink for the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_permalink( $group = false ) {
	echo bp_get_group_permalink( $group );
}
	/**
	 * Return the permalink for the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_permalink( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' ) );
	}

/**
 * Output the permalink for the admin section of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_admin_permalink( $group = false ) {
	echo bp_get_group_admin_permalink( $group );
}
	/**
	 * Return the permalink for the admin section of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_admin_permalink( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_admin_permalink', trailingslashit( bp_get_group_permalink( $group ) . 'admin' ) );
	}

/**
 * Return the slug for the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_slug( $group = false ) {
	echo bp_get_group_slug( $group );
}
	/**
	 * Return the slug for the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_slug( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_slug', $group->slug );
	}

/**
 * Output the description for the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_description( $group = false ) {
	echo bp_get_group_description( $group );
}
	/**
	 * Return the description for the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_description( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description', stripslashes($group->description) );
	}

/**
 * Output the description for the current group in the loop, for use in a textarea.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_description_editable( $group = false ) {
	echo bp_get_group_description_editable( $group );
}
	/**
	 * Return the permalink for the current group in the loop, for use in a textarea.
	 *
	 * 'bp_get_group_description_editable' does not have the formatting
	 * filters that 'bp_get_group_description' has, which makes it
	 * appropriate for "raw" editing.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_description_editable( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_description_editable', $group->description );
	}

/**
 * Output an excerpt of the group description.
 *
 * @param object $group Optional. The group being referenced. Defaults to the
 *        group currently being iterated on in the groups loop.
 */
function bp_group_description_excerpt( $group = false ) {
	echo bp_get_group_description_excerpt( $group );
}
	/**
	 * Get an excerpt of a group description.
	 *
	 * @param object $group Optional. The group being referenced. Defaults
	 *        to the group currently being iterated on in the groups loop.
	 * @return string Excerpt.
	 */
	function bp_get_group_description_excerpt( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		return apply_filters( 'bp_get_group_description_excerpt', bp_create_excerpt( $group->description ), $group );
	}

/**
 * Output the status of the current group in the loop.
 *
 * Either 'Public' or 'Private'.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_public_status( $group = false ) {
	echo bp_get_group_public_status( $group );
}
	/**
	 * Return the status of the current group in the loop.
	 *
	 * Either 'Public' or 'Private'.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_public_status( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		if ( $group->is_public ) {
			return __( 'Public', 'buddypress' );
		} else {
			return __( 'Private', 'buddypress' );
		}
	}

/**
 * Output whether the current group in the loop is public.
 *
 * No longer used in BuddyPress.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_is_public( $group = false ) {
	echo bp_get_group_is_public( $group );
}
	/**
	 * Return whether the current group in the loop is public.
	 *
	 * No longer used in BuddyPress.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return unknown
	 */
	function bp_get_group_is_public( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_is_public', $group->is_public );
	}

/**
 * Output the created date of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_date_created( $group = false ) {
	echo bp_get_group_date_created( $group );
}
	/**
	 * Return the created date of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_date_created( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_date_created', bp_core_time_since( strtotime( $group->date_created ) ) );
	}

/**
 * Output the username of the creator of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_creator_username( $group = false ) {
	echo bp_get_group_creator_username( $group );
}
	/**
	 * Return the username of the creator of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_creator_username( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_creator_username', bp_core_get_user_displayname( $group->creator_id ) );
	}

/**
 * Output the user ID of the creator of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_creator_id( $group = false ) {
	echo bp_get_group_creator_id( $group );
}
	/**
	 * Return the user ID of the creator of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return int
	 */
	function bp_get_group_creator_id( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_creator_id', $group->creator_id );
	}

/**
 * Output the permalink of the creator of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_creator_permalink( $group = false ) {
	echo bp_get_group_creator_permalink( $group );
}
	/**
	 * Return the permalink of the creator of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_creator_permalink( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_creator_permalink', bp_core_get_user_domain( $group->creator_id ) );
	}

/**
 * Determine whether a user is the creator of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 * @param int $user_id ID of the user.
 * @return bool
 */
function bp_is_group_creator( $group = false, $user_id = 0 ) {
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	return (bool) ( $group->creator_id == $user_id );
}

/**
 * Output the avatar of the creator of the current group in the loop.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 * @param array $args {
 *     Array of optional arguments. See {@link bp_get_group_creator_avatar()}
 *     for description.
 * }
 */
function bp_group_creator_avatar( $group = false, $args = array() ) {
	echo bp_get_group_creator_avatar( $group, $args );
}
	/**
	 * Return the avatar of the creator of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @param array $args {
	 *     Array of optional arguments. See {@link bp_core_fetch_avatar()}
	 *     for detailed description of arguments.
	 *     @type string $type Default: 'full'.
	 *     @type int $width Default: false.
	 *     @type int $height Default: false.
	 *     @type int $class Default: 'avatar'.
	 *     @type string $id Passed to 'css_id'. Default: false.
	 *     @type string $alt Alt text. Default: 'Group creator profile
	 *           photo of [user display name]'.
	 * }
	 * @return string
	 */
	function bp_get_group_creator_avatar( $group = false, $args = array() ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		$defaults = array(
			'type'   => 'full',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => sprintf( __( 'Group creator profile photo of %s', 'buddypress' ),  bp_core_get_user_displayname( $group->creator_id ) )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		$avatar = bp_core_fetch_avatar( array( 'item_id' => $group->creator_id, 'type' => $type, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'alt' => $alt ) );

		return apply_filters( 'bp_get_group_creator_avatar', $avatar );
	}

/**
 * Determine whether the current user is the admin of the current group.
 *
 * Alias of {@link bp_is_item_admin()}.
 *
 * @return bool
 */
function bp_group_is_admin() {
	return bp_is_item_admin();
}

/**
 * Determine whether the current user is a mod of the current group.
 *
 * Alias of {@link bp_is_item_mod()}.
 *
 * @return bool
 */
function bp_group_is_mod() {
	return bp_is_item_mod();
}

/**
 * Output markup listing group admins.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_list_admins( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	// fetch group admins if 'populate_extras' flag is false
	if ( empty( $group->args['populate_extras'] ) ) {
		$query = new BP_Group_Member_Query( array(
			'group_id'   => $group->id,
			'group_role' => 'admin',
			'type'       => 'first_joined',
		) );

		if ( ! empty( $query->results ) ) {
			$group->admins = $query->results;
		}
	}

	if ( ! empty( $group->admins ) ) { ?>
		<ul id="group-admins">
			<?php foreach( (array) $group->admins as $admin ) { ?>
				<li>
					<a href="<?php echo bp_core_get_user_domain( $admin->user_id, $admin->user_nicename, $admin->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'email' => $admin->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?></a>
				</li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<span class="activity"><?php _e( 'No Admins', 'buddypress' ) ?></span>
	<?php } ?>
<?php
}

/**
 * Output markup listing group mod.
 *
 * @param object $group Optional. Group object. Default: current
 *        group in loop.
 */
function bp_group_list_mods( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	// fetch group mods if 'populate_extras' flag is false
	if ( empty( $group->args['populate_extras'] ) ) {
		$query = new BP_Group_Member_Query( array(
			'group_id'   => $group->id,
			'group_role' => 'mod',
			'type'       => 'first_joined',
		) );

		if ( ! empty( $query->results ) ) {
			$group->mods = $query->results;
		}
	}

	if ( ! empty( $group->mods ) ) : ?>

		<ul id="group-mods">

			<?php foreach( (array) $group->mods as $mod ) { ?>

				<li>
					<a href="<?php echo bp_core_get_user_domain( $mod->user_id, $mod->user_nicename, $mod->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'email' => $mod->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?></a>
				</li>

			<?php } ?>

		</ul>

<?php else : ?>

		<span class="activity"><?php _e( 'No Mods', 'buddypress' ) ?></span>

<?php endif;

}

/**
 * Return a list of user IDs for a group's admins.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param BP_Groups_Group $group Optional. The group being queried. Defaults
 *        to the current group in the loop.
 * @param string $format Optional. 'string' to get a comma-separated string,
 *        'array' to get an array.
 * @return mixed $admin_ids A string or array of user IDs.
 */
function bp_group_admin_ids( $group = false, $format = 'string' ) {
	global $groups_template;

	if ( empty( $group ) )
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
 * Return a list of user IDs for a group's moderators.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param BP_Groups_Group $group Optional. The group being queried. Defaults
 *        to the current group in the loop.
 * @param string $format Optional. 'string' to get a comma-separated string,
 *        'array' to get an array.
 * @return mixed $mod_ids A string or array of user IDs.
 */
function bp_group_mod_ids( $group = false, $format = 'string' ) {
	global $groups_template;

	if ( empty( $group ) )
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

/**
 * Output the permalink of the current group's Members page.
 */
function bp_group_all_members_permalink() {
	echo bp_get_group_all_members_permalink();
}
	/**
	 * Return the permalink of the Members page of the current group in the loop.
	 *
	 * @param object $group Optional. Group object. Default: current
	 *        group in loop.
	 * @return string
	 */
	function bp_get_group_all_members_permalink( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_all_members_permalink', bp_get_group_permalink( $group ) . 'members' );
	}

/**
 * Display a Groups search form.
 *
 * No longer used in BuddyPress.
 *
 * @todo Deprecate
 */
function bp_group_search_form() {
	global $bp;

	$action = bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/search/';
	$label = __('Filter Groups', 'buddypress');
	$name = 'group-filter-box';

	$search_form_html = '<form action="' . $action . '" id="group-search-form" method="post">
		<label for="'. $name .'" id="'. $name .'-label">'. $label .'</label>
		<input type="search" name="'. $name . '" id="'. $name .'" value="'. $value .'"'.  $disabled .' />

		'. wp_nonce_field( 'group-filter-box', '_wpnonce_group_filter', true, false ) .'
		</form>';

	echo apply_filters( 'bp_group_search_form', $search_form_html );
}

function bp_group_show_no_groups_message() {
	if ( !groups_total_groups_for_user( bp_displayed_user_id() ) )
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
		global $groups_template;

		$start_num = intval( ( $groups_template->pag_page - 1 ) * $groups_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $groups_template->pag_num - 1 ) > $groups_template->total_group_count ) ? $groups_template->total_group_count : $start_num + ( $groups_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $groups_template->total_group_count );

		return apply_filters( 'bp_get_groups_pagination_count', sprintf( _n( 'Viewing 1', 'Viewing %1$s - %2$s of %3$s groups', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
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

		if ( empty( $group ) )
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

		if ( empty( $group ) )
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
			$groups_template->group->forum_counts = bp_forums_get_forum_topicpost_count( (int) $forum_id );

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
			$groups_template->group->forum_counts = bp_forums_get_forum_topicpost_count( (int) $forum_id );

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

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( ! empty( $group->enable_forum ) )
		return true;

	return false;
}

function bp_group_show_forum_setting( $group = false ) {
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( $group->enable_forum )
		echo ' checked="checked"';
}

function bp_group_show_status_setting( $setting, $group = false ) {
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( $setting == $group->status )
		echo ' checked="checked"';
}

/**
 * Get the 'checked' value, if needed, for a given invite_status on the group create/admin screens
 *
 * @since BuddyPress (1.5.0)
 *
 * @param string $setting The setting you want to check against ('members', 'mods', or 'admins')
 * @param BP_Groups_Group $group (optional) The group whose status you want to check
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
 * @since BuddyPress (1.5.0)
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
 * @since BuddyPress (1.5.0)
 *
 * @param int $group_id (optional) The id of the group whose status you want to check
 * @return bool $can_send_invites
 */
function bp_groups_user_can_send_invites( $group_id = false ) {
	global $bp;

	$can_send_invites = false;
	$invite_status    = false;

	if ( is_user_logged_in() ) {
		if ( bp_current_user_can( 'bp_moderate' ) ) {
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
 * @since BuddyPress (1.0.0)
 * @todo Remove in 1.4
 */
function bp_group_admin_memberlist( $admin_list = false, $group = false ) {
	global $groups_template;

	_deprecated_function( __FUNCTION__, '1.5', 'No longer used. See /bp-default/groups/single/admin.php' );

	if ( empty( $group ) )
		$group =& $groups_template->group;


	if ( $admins = groups_get_group_admins( $group->id ) ) : ?>

		<ul id="admins-list" class="item-list<?php if ( !empty( $admin_list ) ) : ?> single-line<?php endif; ?>">

		<?php foreach ( (array) $admins as $admin ) { ?>

			<?php if ( !empty( $admin_list ) ) : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?>

				<h5>

					<?php echo bp_core_get_userlink( $admin->user_id ); ?>

					<span class="small">
						<a class="button confirm admin-demote-to-member" href="<?php bp_group_member_demote_link($admin->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php else : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?>

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
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( $group_mods = groups_get_group_mods( $group->id ) ) { ?>

		<ul id="mods-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">

		<?php foreach ( (array) $group_mods as $mod ) { ?>

			<?php if ( !empty( $admin_list ) ) { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?>

				<h5>
					<?php echo bp_core_get_userlink( $mod->user_id ); ?>

					<span class="small">
						<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => $mod->user_id ) ) ?>" class="button confirm mod-promote-to-admin" title="<?php esc_attr_e( 'Promote to Admin', 'buddypress' ); ?>"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>
						<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link($mod->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php } else { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?>

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
	global $groups_template;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	return apply_filters( 'bp_group_has_moderators', groups_get_group_mods( $group->id ) );
}

function bp_group_member_promote_mod_link( $args = '' ) {
	echo bp_get_group_member_promote_mod_link( $args );
}
	function bp_get_group_member_promote_mod_link( $args = '' ) {
		global $members_template, $groups_template;

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
		global $members_template, $groups_template;

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
		global $members_template, $groups_template;

		if ( empty( $group ) )
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
		global $groups_template;

		if ( empty( $group ) )
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

		if ( empty( $group ) )
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
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_member_remove_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'admin/manage-members/remove/' . $user_id, 'groups_remove_member' ) );
	}

function bp_group_admin_tabs( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group = ( $groups_template->group ) ? $groups_template->group : groups_get_current_group();
	}

	$current_tab = bp_get_group_current_admin_tab();

	if ( bp_is_item_admin() ) : ?>

		<li<?php if ( 'edit-details' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/edit-details' ) ?>"><?php _e( 'Details', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<?php if ( ! bp_is_item_admin() )
			return false; ?>

	<li<?php if ( 'group-settings' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/group-settings' ) ?>"><?php _e( 'Settings', 'buddypress' ); ?></a></li>

	<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) && buddypress()->avatar->show_avatars ) : ?>

		<li<?php if ( 'group-avatar'   == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/group-avatar' ) ?>"><?php _e( 'Photo', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<li<?php if ( 'manage-members' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/manage-members' ) ?>"><?php _e( 'Members', 'buddypress' ); ?></a></li>

	<?php if ( $groups_template->group->status == 'private' ) : ?>

		<li<?php if ( 'membership-requests' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/membership-requests' ) ?>"><?php _e( 'Requests', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<?php do_action( 'groups_admin_tabs', $current_tab, $group->slug ) ?>

	<li<?php if ( 'delete-group' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_group_permalink( $group ) . 'admin/delete-group' ) ?>"><?php _e( 'Delete', 'buddypress' ); ?></a></li>

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
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_form_action', bp_get_group_permalink( $group ) . $page );
	}

function bp_group_admin_form_action( $page = false ) {
	echo bp_get_group_admin_form_action( $page );
}
	function bp_get_group_admin_form_action( $page = false, $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		if ( empty( $page ) )
			$page = bp_action_variable( 0 );

		return apply_filters( 'bp_group_admin_form_action', bp_get_group_permalink( $group ) . 'admin/' . $page );
	}

function bp_group_has_requested_membership( $group = false ) {
	global $groups_template;

	if ( empty( $group ) )
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
 * @uses bp_current_user_can() Check if current user is super admin
 * @uses apply_filters Creates bp_group_is_member filter and passes $is_member
 * @usedby groups/activity.php, groups/single/forum/edit.php, groups/single/forum/topic.php to determine template part visibility
 * @global object $groups_template Current Group (usually in template loop)
 * @param object $group Group to check is_member
 * @return bool If user is member of group or not
 */
function bp_group_is_member( $group = false ) {
	global $groups_template;

	// Site admins always have access
	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	return apply_filters( 'bp_group_is_member', !empty( $group->is_member ) );
}

/**
 * Check whether the current user has an outstanding invite to the current group in the loop.
 *
 * @param object $group Optional. Group data object. Defaults to the current
 *        group in the groups loop.
 * @return bool True if the user has an outstanding invite, otherwise false.
 */
function bp_group_is_invited( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	return apply_filters( 'bp_group_is_invited', ! empty( $group->is_invited ) );
}

/**
 * Check if a user is banned from a group.
 *
 * If this function is invoked inside the groups template loop, then we check
 * $groups_template->group->is_banned instead of using {@link groups_is_user_banned()}
 * and making another SQL query.
 *
 * In BuddyPress 2.1, to standardize this function, we are defaulting the
 * return value to a boolean.  In previous versions, using this function would
 * return either a string of the integer (0 or 1) or null if a result couldn't
 * be found from the database.  If the logged-in user had the 'bp_moderate'
 * capability, the return value would be boolean false.
 *
 * @since BuddyPress (1.5.0)
 *
 * @global BP_Groups_Template $groups_template Group template loop object
 * @param BP_Groups_Group $group Group to check if user is banned
 * @param int $user_id The user ID to check
 * @return bool True if user is banned.  False if user isn't banned.
 */
function bp_group_is_user_banned( $group = false, $user_id = 0 ) {
	global $groups_template;

	// Site admins always have access
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// check groups loop first
	// @see BP_Groups_Group::get_group_extras()
	if ( ! empty( $groups_template->in_the_loop ) && isset( $groups_template->group->is_banned ) ) {
		$retval = $groups_template->group->is_banned;

	// not in loop
	} else {
		// Default to not banned
		$retval = false;

		if ( empty( $group ) ) {
			$group = $groups_template->group;
		}

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		if ( ! empty( $user_id ) && ! empty( $group->id ) ) {
			$retval = groups_is_user_banned( $user_id, $group->id );
		}
	}

	return (bool) apply_filters( 'bp_group_is_user_banned', $retval );
}

function bp_group_accept_invite_link() {
	echo bp_get_group_accept_invite_link();
}
	function bp_get_group_accept_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_accept_invite_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/accept/' . $group->id ), 'groups_accept_invite' ) );
	}

function bp_group_reject_invite_link() {
	echo bp_get_group_reject_invite_link();
}
	function bp_get_group_reject_invite_link( $group = false ) {
		global $groups_template, $bp;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_reject_invite_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/reject/' . $group->id ), 'groups_reject_invite' ) );
	}

function bp_group_leave_confirm_link() {
	echo bp_get_group_leave_confirm_link();
}
	function bp_get_group_leave_confirm_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_leave_confirm_link', wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group/yes', 'groups_leave_group' ) );
	}

function bp_group_leave_reject_link() {
	echo bp_get_group_leave_reject_link();
}
	function bp_get_group_leave_reject_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_get_group_leave_reject_link', bp_get_group_permalink( $group ) );
	}

function bp_group_send_invite_form_action() {
	echo bp_get_group_send_invite_form_action();
}
	function bp_get_group_send_invite_form_action( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		return apply_filters( 'bp_group_send_invite_form_action', bp_get_group_permalink( $group ) . 'send-invites/send' );
	}

function bp_has_friends_to_invite( $group = false ) {
	global $groups_template;

	if ( !bp_is_active( 'friends' ) )
		return false;

	if ( empty( $group ) )
		$group =& $groups_template->group;

	if ( !friends_check_user_has_friends( bp_loggedin_user_id() ) || !friends_count_invitable_friends( bp_loggedin_user_id(), $group->id ) )
		return false;

	return true;
}

/**
 * Outputs a 'New Topic' button for a group.
 *
 * @since BuddyPress (1.2.7)
 *
 * @param BP_Groups_Group|bool $group The BP Groups_Group object if passed, boolean false if not passed.
 * @uses bp_get_group_new_topic_button() Returns the 'New Topic' button
 */
function bp_group_new_topic_button( $group = false ) {
	echo bp_get_group_new_topic_button( $group );
}
	/**
	 * Returns a 'New Topic' button for a group.
	 *
	 * @since BuddyPress (1.2.7)
	 *
	 * @param BP_Groups_Group|bool $group The BP Groups_Group object if passed, boolean false if not passed.
	 * @uses is_user_logged_in() Is there a user logged in?
	 * @uses bp_group_is_user_banned() Is the current user banned from the current group?
	 * @uses bp_is_group_forum() Are we on a group forum page?
	 * @uses bp_is_group_forum_topic() Are we on a group topic page?
	 * @uses bp_get_button() Renders a button
	 * @return string HTML code for the button
	 */
	function bp_get_group_new_topic_button( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		if ( !is_user_logged_in() || bp_group_is_user_banned() || !bp_is_group_forum() || bp_is_group_forum_topic() )
			return false;

		$button = array(
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
		);

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_group_new_topic_button', $button ) );
	}

function bp_group_join_button( $group = false ) {
	echo bp_get_group_join_button( $group );
}
	function bp_get_group_join_button( $group = false ) {
		global $groups_template;

		if ( empty( $group ) )
			$group =& $groups_template->group;

		if ( !is_user_logged_in() || bp_group_is_user_banned( $group ) )
			return false;

		// Group creation was not completed or status is unknown
		if ( !$group->status )
			return false;

		// Already a member
		if ( isset( $group->is_member ) && $group->is_member ) {

			// Stop sole admins from abandoning their group
	 		$group_admins = groups_get_group_admins( $group->id );
		 	if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == bp_loggedin_user_id() )
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

					// Member has outstanding invitation -
					// show an "Accept Invitation" button
					if ( $group->is_invited ) {
						$button = array(
							'id'                => 'accept_invite',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => add_query_arg( 'redirect_to', bp_get_group_permalink( $group ), bp_get_group_accept_invite_link( $group ) ),
							'link_text'         => __( 'Accept Invitation', 'buddypress' ),
							'link_title'        => __( 'Accept Invitation', 'buddypress' ),
							'link_class'        => 'group-button accept-invite',
						);

					// Member has requested membership but request is pending -
					// show a "Request Sent" button
					} elseif ( $group->is_pending ) {
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

					// Member has not requested membership yet -
					// show a "Request Membership" button
					} else {
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
					}

					break;
			}
		}

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_group_join_button', $button ) );
	}

/**
 * Output the Create a Group button.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_group_create_button() {
	echo bp_get_group_create_button();
}
	/**
	 * Get the Create a Group button.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_group_create_button() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! bp_user_can_create_groups() ) {
			return false;
		}

		$button_args = array(
			'id'         => 'create_group',
			'component'  => 'groups',
			'link_text'  => __( 'Create a Group', 'buddypress' ),
			'link_title' => __( 'Create a Group', 'buddypress' ),
			'link_class' => 'button group-create bp-title-button',
			'link_href'  => trailingslashit( bp_get_root_domain() ) . trailingslashit( bp_get_groups_root_slug() ) . trailingslashit( 'create' ),
			'wrapper'    => false,
		);

		return bp_get_button( apply_filters( 'bp_get_group_create_button', $button_args ) );
	}

/**
 * Prints a message if the group is not visible to the current user (it is a
 * hidden or private group, and the user does not have access).
 *
 * @global BP_Groups_Template $groups_template Groups template object
 * @param object $group Group to get status message for. Optional; defaults to current group.
 * @since BuddyPress (1.0.0)
 */
function bp_group_status_message( $group = null ) {
	global $groups_template;

	if ( ! $group )
		$group =& $groups_template->group;

	if ( 'private' == $group->status ) {
 		if ( ! bp_group_has_requested_membership() ) {
			if ( is_user_logged_in() && bp_group_is_invited() ) {
				$message = __( 'You must accept your pending invitation before you can access this private group.', 'buddypress' );
			} else if ( is_user_logged_in() ) {
				$message = __( 'This is a private group and you must request group membership in order to join.', 'buddypress' );
			} else {
				$message = __( 'This is a private group. To join you must be a registered site member and request group membership.', 'buddypress' );
			}

		} else {
			$message = __( 'This is a private group. Your membership request is awaiting approval from the group administrator.', 'buddypress' );
		}

	} else {
		$message = __( 'This is a hidden group and only invited members can join.', 'buddypress' );
	}

	echo apply_filters( 'bp_group_status_message', $message, $group );
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
		return apply_filters( 'bp_get_total_group_count_for_user', groups_total_groups_for_user( $user_id ), $user_id );
	}
	add_filter( 'bp_get_total_group_count_for_user', 'bp_core_number_format' );


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

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *     An array of optional arguments.
	 *     @type int $group_id ID of the group whose members are being
	 *	     queried. Default: current group ID.
	 *     @type int $page Page of results to be queried. Default: 1.
	 *     @type int $per_page Number of items to return per page of
	 *           results. Default: 20.
	 *     @type int $max Optional. Max number of items to return.
	 *     @type array $exclude Optional. Array of user IDs to exclude.
	 *     @type bool|int True (or 1) to exclude admins and mods from
	 *           results. Default: 1.
	 *     @type bool|int True (or 1) to exclude banned users from results.
	 *           Default: 1.
	 *     @type array $group_role Optional. Array of group roles to include.
	 *     @type string $search_terms Optional. Search terms to match.
	 * }
	 */
	function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'group_id',
				1 => 'per_page',
				2 => 'max',
				3 => 'exclude_admins_mods',
				4 => 'exclude_banned',
				5 => 'exclude',
				6 => 'group_role',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'group_id'            => bp_get_current_group_id(),
			'page'                => 1,
			'per_page'            => 20,
			'max'                 => false,
			'exclude'             => false,
			'exclude_admins_mods' => 1,
			'exclude_banned'      => 1,
			'group_role'          => false,
			'search_terms'        => false,
			'type'                => 'last_joined',
		) );

		// @todo No
		extract( $r );

		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : $r['page'];
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		/**
		 * Check the current group is the same as the supplied group ID.
		 * It can differ when using {@link bp_group_has_members()} outside the Groups screens.
		 */
		$current_group = groups_get_current_group();
		if ( ! $current_group || $current_group && $current_group->id !== bp_get_current_group_id() ) {
			$current_group = groups_get_group( array( 'group_id' => $r['group_id'] ) );
		}

		// Assemble the base URL for pagination
		$base_url = trailingslashit( bp_get_group_permalink( $current_group ) . bp_current_action() );
		if ( bp_action_variable() ) {
			$base_url = trailingslashit( $base_url . bp_action_variable() );
		}

		$members_args = $r;

		$members_args['page']     = $this->pag_page;
		$members_args['per_page'] = $this->pag_num;

		$this->members = groups_get_group_members( $members_args );

		if ( !$max || $max >= (int) $this->members['count'] )
			$this->total_member_count = (int) $this->members['count'];
		else
			$this->total_member_count = (int) $max;

		$this->members = $this->members['members'];

		if ( $max ) {
			if ( $max >= count($this->members) )
				$this->member_count = count($this->members);
			else
				$this->member_count = (int) $max;
		} else {
			$this->member_count = count($this->members);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'mlpage' => '%#%' ), $base_url ),
			'format' => '',
			'total' => !empty( $this->pag_num ) ? ceil( $this->total_member_count / $this->pag_num ) : $this->total_member_count,
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

		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		if ( 0 == $this->current_member ) // loop has just started
			do_action('loop_start');
	}
}

/**
 * Initialize a group member query loop.
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int $group_id ID of the group whose members are being queried.
 *           Default: current group ID.
 *     @type int $page Page of results to be queried. Default: 1.
 *     @type int $per_page Number of items to return per page of results.
 *           Default: 20.
 *     @type int $max Optional. Max number of items to return.
 *     @type array $exclude Optional. Array of user IDs to exclude.
 *     @type bool|int True (or 1) to exclude admins and mods from results.
 *           Default: 1.
 *     @type bool|int True (or 1) to exclude banned users from results.
 *           Default: 1.
 *     @type array $group_role Optional. Array of group roles to include.
 *     @type string $type Optional. Sort order of results. 'last_joined',
 *           'first_joined', or any of the $type params available in
 *           {@link BP_User_Query}. Default: 'last_joined'.
 *     @type string $search_terms Optional. Search terms to match. Pass an
 *           empty string to force-disable search, even in the presence of
 *           $_REQUEST['s']. Default: null.
 * }
 */
function bp_group_has_members( $args = '' ) {
	global $members_template;

	$exclude_admins_mods = 1;

	if ( bp_is_group_members() ) {
		$exclude_admins_mods = 0;
	}

	$r = wp_parse_args( $args, array(
		'group_id'            => bp_get_current_group_id(),
		'page'                => 1,
		'per_page'            => 20,
		'max'                 => false,
		'exclude'             => false,
		'exclude_admins_mods' => $exclude_admins_mods,
		'exclude_banned'      => 1,
		'group_role'          => false,
		'search_terms'        => null,
		'type'                => 'last_joined',
	) );

	if ( is_null( $r['search_terms'] ) && ! empty( $_REQUEST['s'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	$members_template = new BP_Groups_Group_Members_Template( $r );
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

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {@see bp_core_fetch_avatar()}
 */
function bp_group_member_avatar( $args = '' ) {
	echo bp_get_group_member_avatar( $args );
}
	/**
	 * Return the group member avatar while in the groups members loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {@see bp_core_fetch_avatar()}
	 */
	function bp_get_group_member_avatar( $args = '' ) {
		global $members_template;

		$r = bp_parse_args( $args, array(
			'item_id' => $members_template->member->user_id,
			'type'    => 'full',
			'email'   => $members_template->member->user_email,
			'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name )
		) );

		return apply_filters( 'bp_get_group_member_avatar', bp_core_fetch_avatar( $r ), $r );
	}

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {@see bp_core_fetch_avatar()}
 */

function bp_group_member_avatar_thumb( $args = '' ) {
	echo bp_get_group_member_avatar_thumb( $args );
}
	/**
	 * Return the group member avatar while in the groups members loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {@see bp_core_fetch_avatar()}
	 */
	function bp_get_group_member_avatar_thumb( $args = '' ) {
		global $members_template;

		$r = bp_parse_args( $args, array(
			'item_id' => $members_template->member->user_id,
			'type'    => 'thumb',
			'email'   => $members_template->member->user_email,
			'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name )
		) );

		return apply_filters( 'bp_get_group_member_avatar_thumb', bp_core_fetch_avatar( $r ), $r );
	}

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {@see bp_core_fetch_avatar()}
 */
function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_group_member_avatar_mini( $width, $height );
}
	/**
	 * Output the group member avatar while in the groups members loop.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {@see bp_core_fetch_avatar()}
	 */
	function bp_get_group_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		$r = bp_parse_args( array(), array(
			'item_id' => $members_template->member->user_id,
			'type'    => 'thumb',
			'email'   => $members_template->member->user_email,
			'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name ),
			'width'   => absint( $width ),
			'height'  => absint( $height )
		) );

		return apply_filters( 'bp_get_group_member_avatar_mini', bp_core_fetch_avatar( $r ), $r );
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
		global $members_template;

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

		return apply_filters( 'bp_get_group_member_pagination_count', sprintf( _n( 'Viewing 1 member', 'Viewing %1$s - %2$s of %3$s members', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

function bp_group_member_admin_pagination() {
	echo bp_get_group_member_admin_pagination();
	wp_nonce_field( 'bp_groups_member_admin_list', '_member_admin_pag_nonce' );
}
	function bp_get_group_member_admin_pagination() {
		global $members_template;

		return $members_template->pag_links;
	}

/**
 * Output the Group members template
 *
 * @since BuddyPress (?)
 *
 * @return string html output
 */
function bp_groups_members_template_part() {
	?>
	<div class="item-list-tabs" id="subnav" role="navigation">
		<ul>
			<li class="groups-members-search" role="search">
				<?php bp_directory_members_search_form(); ?>
			</li>

			<?php bp_groups_members_filter(); ?>
			<?php do_action( 'bp_members_directory_member_sub_types' ); ?>

		</ul>
	</div>

	<div id="members-group-list" class="group_members dir-list">

		<?php bp_get_template_part( 'groups/single/members' ); ?>

	</div>
	<?php
}

/**
 * Output the Group members filters
 *
 * @since BuddyPress (?)
 *
 * @return string html output
 */
function bp_groups_members_filter() {
	?>
	<li id="group_members-order-select" class="last filter">
		<label for="group_members-order-by"><?php _e( 'Order By:', 'buddypress' ); ?></label>
		<select id="group_members-order-by">
			<option value="last_joined"><?php _e( 'Newest', 'buddypress' ); ?></option>
			<option value="first_joined"><?php _e( 'Oldest', 'buddypress' ); ?></option>

			<?php if ( bp_is_active( 'activity' ) ) : ?>
				<option value="group_activity"><?php _e( 'Group Activity', 'buddypress' ); ?></option>
			<?php endif; ?>

			<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ); ?></option>

			<?php do_action( 'bp_groups_members_order_options' ); ?>

		</select>
	</li>
	<?php
}

/***************************************************************************
 * Group Creation Process Template Tags
 **/

/**
 * Determine if the current logged in user can create groups.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses apply_filters() To call 'bp_user_can_create_groups'.
 * @uses bp_get_option() To retrieve value of 'bp_restrict_group_creation'. Defaults to 0.
 * @uses bp_current_user_can() To determine if current user if super admin.
 *
 * @return bool True if user can create groups. False otherwise.
 */
function bp_user_can_create_groups() {
	// Super admin can always create groups
	if ( bp_current_user_can( 'bp_moderate' ) )
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

	if ( !bp_get_groups_current_create_step() ) {
		$keys = array_keys( $bp->groups->group_creation_steps );
		$bp->groups->current_create_step = array_shift( $keys );
	}

	$counter = 1;

	foreach ( (array) $bp->groups->group_creation_steps as $slug => $step ) {
		$is_enabled = bp_are_previous_group_creation_steps_complete( $slug ); ?>

		<li<?php if ( bp_get_groups_current_create_step() == $slug ) : ?> class="current"<?php endif; ?>><?php if ( $is_enabled ) : ?><a href="<?php echo bp_get_root_domain() . '/' . bp_get_groups_root_slug() ?>/create/step/<?php echo $slug ?>/"><?php else: ?><span><?php endif; ?><?php echo $counter ?>. <?php echo $step['name'] ?><?php if ( $is_enabled ) : ?></a><?php else: ?></span><?php endif ?></li><?php
		$counter++;
	}

	unset( $is_enabled );

	do_action( 'groups_creation_tabs' );
}

function bp_group_creation_stage_title() {
	global $bp;

	echo apply_filters( 'bp_group_creation_stage_title', '<span>&mdash; ' . $bp->groups->group_creation_steps[bp_get_groups_current_create_step()]['name'] . '</span>' );
}

function bp_group_creation_form_action() {
	echo bp_get_group_creation_form_action();
}
	function bp_get_group_creation_form_action() {
		global $bp;

		if ( !bp_action_variable( 1 ) ) {
			$keys = array_keys( $bp->groups->group_creation_steps );
			$bp->action_variables[1] = array_shift( $keys );
		}

		return apply_filters( 'bp_get_group_creation_form_action', trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/' . bp_action_variable( 1 ) ) );
	}

function bp_is_group_creation_step( $step_slug ) {
	global $bp;

	/* Make sure we are in the groups component */
	if ( !bp_is_groups_component() || !bp_is_current_action( 'create' ) )
		return false;

	/* If this the first step, we can just accept and return true */
	$keys = array_keys( $bp->groups->group_creation_steps );
	if ( !bp_action_variable( 1 ) && array_shift( $keys ) == $step_slug )
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

		foreach ( (array) $step_slugs as $step_slug ) {
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
	$keys = array_keys( $bp->groups->group_creation_steps );
	if ( array_shift( $keys ) == $step_slug )
		return true;

	reset( $bp->groups->group_creation_steps );
	unset( $previous_steps );

	/* Get previous steps */
	foreach ( (array) $bp->groups->group_creation_steps as $slug => $name ) {
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

		foreach ( (array) $bp->groups->group_creation_steps as $slug => $name ) {
			if ( bp_is_action_variable( $slug ) )
				break;

			$previous_steps[] = $slug;
		}

		return apply_filters( 'bp_get_group_creation_previous_link', trailingslashit( bp_get_root_domain() ) . bp_get_groups_root_slug() . '/create/step/' . array_pop( $previous_steps ) );
	}

/**
 * Echoes the current group creation step
 *
 * @since BuddyPress (1.6.0)
 */
function bp_groups_current_create_step() {
	echo bp_get_groups_current_create_step();
}
	/**
	 * Returns the current group creation step. If none is found, returns an empty string
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @uses apply_filters() Filter bp_get_groups_current_create_step to modify
	 * @return string $current_create_step
	 */
	function bp_get_groups_current_create_step() {
		global $bp;

		if ( !empty( $bp->groups->current_create_step ) ) {
			$current_create_step = $bp->groups->current_create_step;
		} else {
			$current_create_step = '';
		}

		return apply_filters( 'bp_get_groups_current_create_step', $current_create_step );
	}

function bp_is_last_group_creation_step() {
	global $bp;

	$keys      = array_keys( $bp->groups->group_creation_steps );
	$last_step = array_pop( $keys );

	if ( $last_step == bp_get_groups_current_create_step() )
		return true;

	return false;
}

function bp_is_first_group_creation_step() {
	global $bp;

	$keys       = array_keys( $bp->groups->group_creation_steps );
	$first_step = array_shift( $keys );

	if ( $first_step == bp_get_groups_current_create_step() )
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

		if ( $friends = friends_get_friends_invite_list( bp_loggedin_user_id(), $group_id ) ) {
			$invites = groups_get_invites_for_group( bp_loggedin_user_id(), $group_id );

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
			return implode( "\n", (array) $items );

		return false;
	}

function bp_directory_groups_search_form() {

	$default_search_value = bp_get_search_default_text( 'groups' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;

	$search_form_html = '<form action="" method="get" id="search-groups-form">
		<label><input type="text" name="s" id="groups_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="groups_search_submit" name="groups_search_submit" value="'. __( 'Search', 'buddypress' ) .'" />
	</form>';

	echo apply_filters( 'bp_directory_groups_search_form', $search_form_html );

}

/**
 * Displays group header tabs
 *
 * @todo Deprecate?
 */
function bp_groups_header_tabs() {
	global $bp;?>

	<li<?php if ( !bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/recently-active' ) ?>"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'recently-joined', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/recently-joined' ) ?>"><?php _e( 'Recently Joined', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'most-popular', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/most-popular' ) ?>"><?php _e( 'Most Popular', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'admin-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/admin-of' ) ?>"><?php _e( 'Administrator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'mod-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/mod-of' ) ?>"><?php _e( 'Moderator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'alphabetically' ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_groups_slug() . '/my-groups/alphabetically' ) ?>"><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>

<?php
	do_action( 'groups_header_tabs' );
}

/**
 * Displays group filter titles
 *
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

/**
 * Is the current page a specific group admin screen?
 *
 * @since BuddyPress (1.1.0)
 *
 * @param string $slug
 * @return bool
 */
function bp_is_group_admin_screen( $slug = '' ) {
	return (bool) ( bp_is_group_admin_page() && bp_is_action_variable( $slug ) );
}

/**
 * Echoes the current group admin tab slug
 *
 * @since BuddyPress (1.6.0)
 */
function bp_group_current_admin_tab() {
	echo bp_get_group_current_admin_tab();
}
	/**
	 * Returns the current group admin tab slug
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @uses apply_filters() Filter bp_get_current_group_admin_tab to modify return value
	 * @return string $tab The current tab's slug
	 */
	function bp_get_group_current_admin_tab() {
		if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) ) {
			$tab = bp_action_variable( 0 );
		} else {
			$tab = '';
		}

		return apply_filters( 'bp_get_current_group_admin_tab', $tab );
	}

/************************************************************************************
 * Group Avatar Template Tags
 **/

/**
 * Outputs the current group avatar
 *
 * @since BuddyPress (1.0.0)
 * @param string $type thumb or full ?
 * @uses bp_get_group_current_avatar() to get the avatar of the current group
 */
function bp_group_current_avatar( $type = 'thumb' ) {
	echo bp_get_group_current_avatar( $type );
}
	/**
	 * Returns the current group avatar
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param string $type thumb or full ?
	 * @return string $tab The current tab's slug.
	 */
	function bp_get_group_current_avatar( $type = 'thumb' ) {

		$group_avatar = bp_core_fetch_avatar( array(
			'item_id'    => bp_get_current_group_id(),
			'object'     => 'group',
			'type'       => $type,
			'avatar_dir' => 'group-avatars',
			'alt'        => __( 'Group avatar', 'buddypress' ),
			'class'      => 'avatar'
		) );

		return apply_filters( 'bp_get_group_current_avatar', $group_avatar );
	}

function bp_get_group_has_avatar( $group_id = false ) {
	global $bp;

	if ( false === $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	// Todo - this looks like an overgeneral check
	if ( ! empty( $_FILES ) ) {
		return false;
	}

	$group_avatar = bp_core_fetch_avatar( array(
		'item_id' => $group_id,
		'object' => 'group',
		'no_grav' => true,
		'html' => false,
	) );

	if ( bp_core_avatar_default( 'local' ) === $group_avatar ) {
		return false;
	}

	return true;
}

function bp_group_avatar_delete_link() {
	echo bp_get_group_avatar_delete_link();
}
	function bp_get_group_avatar_delete_link() {
		global $bp;

		return apply_filters( 'bp_get_group_avatar_delete_link', wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/group-avatar/delete', 'bp_group_avatar_delete' ) );
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

	/**
	 * Constructor method.
	 *
	 * @param array $args {
	 *     @type int $group_id ID of the group whose membership requests
	 *           are being queried. Default: current group id.
	 *     @type int $per_page Number of records to return per page of
	 *           results. Default: 10.
	 *     @type int $page Page of results to show. Default: 1.
	 *     @type int $max Max items to return. Default: false (show all)
	 * }
	 */
	function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'group_id',
				1 => 'per_page',
				2 => 'max',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'group_id' => bp_get_current_group_id(),
			'per_page' => 10,
			'page'     => 1,
			'max'      => false,
			'type'     => 'first_joined',
		) );

		$this->pag_page = isset( $_REQUEST['mrpage'] ) ? intval( $_REQUEST['mrpage'] ) : $r['page'];
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $r['per_page'];

		$mquery = new BP_Group_Member_Query( array(
			'group_id' => $r['group_id'],
			'type'     => $r['type'],
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we only get pending requests
			'is_confirmed' => false,
			'inviter_id'   => 0,
		) );

		$this->requests      = array_values( $mquery->results );
		$this->request_count = count( $this->requests );

		// Compatibility with legacy format of request data objects
		foreach ( $this->requests as $rk => $rv ) {
			// For legacy reasons, the 'id' property of each
			// request must match the membership id, not the ID of
			// the user (as it's returned by BP_Group_Member_Query)
			$this->requests[ $rk ]->user_id = $rv->ID;
			$this->requests[ $rk ]->id      = $rv->membership_id;

			// Miscellaneous values
			$this->requests[ $rk ]->group_id   = $r['group_id'];
		}

		if ( !$r['max'] || $r['max'] >= (int) $mquery->total_users )
			$this->total_request_count = (int) $mquery->total_users;
		else
			$this->total_request_count = (int) $r['max'];

		if ( $r['max'] ) {
			if ( $r['max'] >= count($this->requests) )
				$this->request_count = count($this->requests);
			else
				$this->request_count = (int) $r['max'];
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
		$this->in_the_loop = true;
		$this->request     = $this->next_request();

		if ( 0 == $this->current_request ) // loop has just started
			do_action('group_request_loop_start');
	}
}

/**
 * Initialize a group membership request template loop.
 *
 * @param array $args {
 *     @type int $group_id ID of the group. Defaults to current group.
 *     @type int $per_page Number of records to return per page. Default: 10.
 *     @type int $page Page of results to return. Default: 1.
 *     @type int $max Max number of items to return. Default: false.
 * }
 * @return bool True if there are requests, otherwise false.
 */
function bp_group_has_membership_requests( $args = '' ) {
	global $requests_template;

	$defaults = array(
		'group_id' => bp_get_current_group_id(),
		'per_page' => 10,
		'page'     => 1,
		'max'      => false
	);

	$r = wp_parse_args( $args, $defaults );

	$requests_template = new BP_Groups_Membership_Requests_Template( $r );
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

	echo apply_filters( 'bp_group_request_user_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $requests_template->request->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $requests_template->request->user_id ) ) ) ) );
}

function bp_group_request_reject_link() {
	echo bp_get_group_request_reject_link();
}
	function bp_get_group_request_reject_link() {
		global $requests_template;

		return apply_filters( 'bp_get_group_request_reject_link', wp_nonce_url( bp_get_group_permalink( groups_get_current_group() ) . 'admin/membership-requests/reject/' . $requests_template->request->membership_id, 'groups_reject_membership_request' ) );
	}

function bp_group_request_accept_link() {
	echo bp_get_group_request_accept_link();
}
	function bp_get_group_request_accept_link() {
		global $requests_template;

		return apply_filters( 'bp_get_group_request_accept_link', wp_nonce_url( bp_get_group_permalink( groups_get_current_group() ) . 'admin/membership-requests/accept/' . $requests_template->request->membership_id, 'groups_accept_membership_request' ) );
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

/**
 * Output pagination links for group membership requests.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_group_requests_pagination_links() {
	echo bp_get_group_requests_pagination_links();
}
	/**
	 * Get pagination links for group membership requests.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_group_requests_pagination_links() {
		global $requests_template;
		return apply_filters( 'bp_get_group_requests_pagination_links', $requests_template->pag_links );
	}

/**
 * Output pagination count text for group membership requests.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_group_requests_pagination_count() {
	echo bp_get_group_requests_pagination_count();
}
	/**
	 * Get pagination count text for group membership requests.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_group_requests_pagination_count() {
		global $requests_template;

		$start_num = intval( ( $requests_template->pag_page - 1 ) * $requests_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $requests_template->pag_num - 1 ) > $requests_template->total_request_count ) ? $requests_template->total_request_count : $start_num + ( $requests_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $requests_template->total_request_count );

		return apply_filters( 'bp_get_group_requests_pagination_count', sprintf( _n( 'Viewing 1 request', 'Viewing %1$s - %2$s of %3$s requests', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
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

	public function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'group_id',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'user_id'  => bp_loggedin_user_id(),
			'group_id' => bp_get_current_group_id(),
			'page'     => 1,
			'per_page' => 10,
		) );

		$this->pag_num  = intval( $r['per_page'] );
		$this->pag_page = isset( $_REQUEST['invitepage'] ) ? intval( $_REQUEST['invitepage'] ) : $r['page'];

		$iquery = new BP_Group_Member_Query( array(
			'group_id' => $r['group_id'],
			'type'     => 'first_joined',
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we get only pending invites
			'is_confirmed' => false,
			'inviter_id'   => $r['user_id'],
		) );
		$this->invite_data = $iquery->results;

		$this->total_invite_count = $iquery->total_users;
		$this->invites		  = array_values( wp_list_pluck( $this->invite_data, 'ID' ) );
		$this->invite_count       = count( $this->invites );

		// If per_page is set to 0 (show all results), don't generate
		// pag_links
		if ( ! empty( $this->pag_num ) ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'invitepage', '%#%' ),
				'format'    => '',
				'total'     => ceil( $this->total_invite_count / $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1,
			) );
		} else {
			$this->pag_links = '';
		}
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
		global $group_id;
		$this->in_the_loop      = true;
		$user_id                = $this->next_invite();

		$this->invite           = new stdClass;
		$this->invite->user     = $this->invite_data[ $user_id ];

		// This method previously populated the user object with
		// BP_Core_User. We manually configure BP_Core_User data for
		// backward compatibility.
		if ( bp_is_active( 'xprofile' ) ) {
			$this->invite->user->profile_data = BP_XProfile_ProfileData::get_all_for_user( $user_id );
		}

		$this->invite->user->avatar       = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_thumb = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_mini  = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ), 'width' => 30, 'height' => 30 ) );
		$this->invite->user->email        = $this->invite->user->user_email;
		$this->invite->user->user_url     = bp_core_get_user_domain( $user_id, $this->invite->user->user_nicename, $this->invite->user->user_login );
		$this->invite->user->user_link    = "<a href='{$this->invite->user->user_url}' title='{$this->invite->user->fullname}'>{$this->invite->user->fullname}</a>";
		$this->invite->user->last_active  = bp_core_get_last_activity( $this->invite->user->last_activity, __( 'active %s', 'buddypress' ) );

		if ( bp_is_active( 'groups' ) ) {
			$total_groups = BP_Groups_Member::total_group_count( $user_id );
			$this->invite->user->total_groups = sprintf( _n( '%d group', '%d groups', $total_groups, 'buddypress' ), $total_groups );
		}

		if ( bp_is_active( 'friends' ) ) {
			$this->invite->user->total_friends = BP_Friends_Friendship::total_friend_count( $user_id );
		}

		if ( bp_is_active( 'friends' ) ) {
			$this->invite->user->total_friends = BP_Friends_Friendship::total_friend_count( $user_id );
		}

		$this->invite->user->total_blogs = null;

		$this->invite->group_id = $group_id; // Globaled in bp_group_has_invites()

		if ( 0 == $this->current_invite ) // loop has just started
			do_action('loop_start');
	}
}

function bp_group_has_invites( $args = '' ) {
	global $invites_template, $group_id;

	$r = wp_parse_args( $args, array(
		'group_id' => false,
		'user_id'  => bp_loggedin_user_id(),
		'per_page' => false,
		'page'     => 1,
	) );

	if ( empty( $r['group_id'] ) ) {
		if ( groups_get_current_group() ) {
			$r['group_id'] = bp_get_current_group_id();
		} elseif ( ! empty( buddypress()->groups->new_group_id ) ) {
			$r['group_id'] = buddypress()->groups->new_group_id;
		}
	}

	// Set the global (for use in BP_Groups_Invite_Template::the_invite())
	if ( empty( $group_id ) ) {
		$group_id = $r['group_id'];
	}

	if ( ! $group_id ) {
		return false;
	}

	$invites_template = new BP_Groups_Invite_Template( $r );
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

		$user_id = intval( $invites_template->invite->user->id );

		if ( bp_is_current_action( 'create' ) ) {
			$uninvite_url = bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/group-invites/?user_id=' . $user_id;
		} else {
			$uninvite_url = bp_get_group_permalink( groups_get_current_group() ) . 'send-invites/remove/' . $user_id;
		}

		return wp_nonce_url( $uninvite_url, 'groups_invite_uninvite_user' );
	}

/**
 * Output pagination links for group invitations.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_group_invite_pagination_links() {
	echo bp_get_group_invite_pagination_links();
}
	/**
	 * Get pagination links for group invitations.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_group_invite_pagination_links() {
		global $invites_template;
		return apply_filters( 'bp_get_group_invite_pagination_links', $invites_template->pag_links );
	}

/**
 * Output pagination count text for group invitations.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_group_invite_pagination_count() {
	echo bp_get_group_invite_pagination_count();
}
	/**
	 * Get pagination count text for group invitations.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_group_invite_pagination_count() {
		global $invites_template;

		$start_num = intval( ( $invites_template->pag_page - 1 ) * $invites_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $invites_template->pag_num - 1 ) > $invites_template->total_invite_count ) ? $invites_template->total_invite_count : $start_num + ( $invites_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $invites_template->total_invite_count );

		return apply_filters( 'bp_get_groups_pagination_count', sprintf( _n( 'Viewing 1 invitation', 'Viewing %1$s - %2$s of %3$s invitations', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

/***
 * Groups RSS Feed Template Tags
 */

/**
 * Hook group activity feed to <head>
 *
 * @since BuddyPress (1.5.0)
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
		return apply_filters( 'bp_get_group_activity_feed_link', bp_get_group_permalink( groups_get_current_group() ) . 'feed/' );
	}

/**
 * Echoes the output of bp_get_current_group_id()
 *
 * @since BuddyPress (1.5.0)
 */
function bp_current_group_id() {
	echo bp_get_current_group_id();
}
	/**
	 * Returns the ID of the current group
	 *
	 * @since BuddyPress (1.5.0)
	 * @uses apply_filters() Filter bp_get_current_group_id to modify this output
	 *
	 * @return int $current_group_id The id of the current group, if there is one
	 */
	function bp_get_current_group_id() {
		$current_group    = groups_get_current_group();
		$current_group_id = isset( $current_group->id ) ? (int) $current_group->id : 0;

		return apply_filters( 'bp_get_current_group_id', $current_group_id, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_slug()
 *
 * @since BuddyPress (1.5.0)
 */
function bp_current_group_slug() {
	echo bp_get_current_group_slug();
}
	/**
	 * Returns the slug of the current group
	 *
	 * @since BuddyPress (1.5.0)
	 * @uses apply_filters() Filter bp_get_current_group_slug to modify this output
	 *
	 * @return string $current_group_slug The slug of the current group, if there is one
	 */
	function bp_get_current_group_slug() {
		$current_group      = groups_get_current_group();
		$current_group_slug = isset( $current_group->slug ) ? $current_group->slug : '';

		return apply_filters( 'bp_get_current_group_slug', $current_group_slug, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_name()
 *
 */
function bp_current_group_name() {
	echo bp_get_current_group_name();
}
	/**
	 * Returns the name of the current group
	 *
	 * @since BuddyPress (1.5.0)
	 * @uses apply_filters() Filter bp_get_current_group_name to modify this output
	 *
	 * @return string The name of the current group, if there is one
	 */
	function bp_get_current_group_name() {
		$current_group      = groups_get_current_group();
		$current_group_name = isset( $current_group->name ) ? $current_group->name : '';
		$name               = apply_filters( 'bp_get_group_name', $current_group_name );

		return apply_filters( 'bp_get_current_group_name', $name, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_description()
 *
 * @since BuddyPress (2.1.0)
 */
function bp_current_group_description() {
	echo bp_get_current_group_description();
}
	/**
	 * Returns the description of the current group
	 *
	 * @since BuddyPress (2.1.0)
	 * @uses apply_filters() Filter bp_get_current_group_description to modify
	 *                       this output
	 *
	 * @return string The description of the current group, if there is one
	 */
	function bp_get_current_group_description() {
		$current_group      = groups_get_current_group();
		$current_group_desc = isset( $current_group->description ) ? $current_group->description : '';
		$desc               = apply_filters( 'bp_get_group_description', $current_group_desc );

		return apply_filters( 'bp_get_current_group_description', $desc );
	}

/**
 * Output a URL for a group component action
 *
 * @since BuddyPress (1.2.0)
 *
 * @param string $action
 * @param string $query_args
 * @param bool $nonce
 * @return string
 */
function bp_groups_action_link( $action = '', $query_args = '', $nonce = false ) {
	echo bp_get_groups_action_link( $action, $query_args, $nonce );
}
	/**
	 * Get a URL for a group component action
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @param string $action
	 * @param string $query_args
	 * @param bool $nonce
	 * @return string
	 */
	function bp_get_groups_action_link( $action = '', $query_args = '', $nonce = false ) {

		$current_group = groups_get_current_group();
		$url           = '';

		// Must be a group
		if ( ! empty( $current_group->id ) ) {

			// Append $action to $url if provided
			if ( !empty( $action ) ) {
				$url = bp_get_group_permalink( $current_group ) . $action;
			} else {
				$url = bp_get_group_permalink( $current_group );
			}

			// Add a slash at the end of our user url
			$url = trailingslashit( $url );

			// Add possible query args
			if ( !empty( $query_args ) && is_array( $query_args ) ) {
				$url = add_query_arg( $query_args, $url );
			}

			// To nonce, or not to nonce...
			if ( true === $nonce ) {
				$url = wp_nonce_url( $url );
			} elseif ( is_string( $nonce ) ) {
				$url = wp_nonce_url( $url, $nonce );
			}
		}

		// Return the url
		return apply_filters( 'bp_get_groups_action_link', $url, $action, $query_args, $nonce );
	}

/** Stats **********************************************************************/

/**
 * Display the number of groups in user's profile.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $args before|after|user_id
 * @uses bp_groups_get_profile_stats() to get the stats
 */
function bp_groups_profile_stats( $args = '' ) {
	echo bp_groups_get_profile_stats( $args );
}
add_action( 'bp_members_admin_user_stats', 'bp_groups_profile_stats', 8, 1 );

/**
 * Return the number of groups in user's profile.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $args before|after|user_id
 * @return string HTML for stats output.
 */
function bp_groups_get_profile_stats( $args = '' ) {

	// Parse the args
	$r = bp_parse_args( $args, array(
		'before'  => '<li class="bp-groups-profile-stats">',
		'after'   => '</li>',
		'user_id' => bp_displayed_user_id(),
		'groups'  => 0,
		'output'  => ''
	), 'groups_get_profile_stats' );

	// Allow completely overloaded output
	if ( empty( $r['output'] ) ) {

		// Only proceed if a user ID was passed
		if ( ! empty( $r['user_id'] ) ) {

			// Get the user groups
			if ( empty( $r['groups'] ) ) {
				$r['groups'] = absint( bp_get_total_group_count_for_user( $r['user_id'] ) );
			}

			// If groups exist, show some formatted output
			$r['output'] = $r['before'] . sprintf( _n( '%s group', '%s groups', $r['groups'], 'buddypress' ), '<strong>' . $r['groups'] . '</strong>' ) . $r['after'];
		}
	}

	// Filter and return
	return apply_filters( 'bp_groups_get_profile_stats', $r['output'], $r );
}
