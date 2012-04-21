<?php

/**
 * BuddyPress Groups Loader
 *
 * A groups component, for users to group themselves together. Includes a
 * robust sub-component API that allows Groups to be extended.
 * Comes preconfigured with an activity stream, discussion forums, and settings.
 *
 * @package BuddyPress
 * @subpackage GroupsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Groups_Component extends BP_Component {

	/**
	 * Auto join group when non group member performs group activity
	 *
	 * @since BuddyPress (1.5)
	 * @var bool
	 */
	public $auto_join;

	/**
	 * The group being currently accessed
	 *
	 * @since BuddyPress (1.5)
	 * @var BP_Groups_Group
	 */
	public $current_group;

	/**
	 * Default group extension
	 *
	 * @since BuddyPress (1.6)
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 */
	var $default_component;

	/**
	 * Default group extension
	 *
	 * @since BuddyPress (1.6)
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal group names/slugs
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * Group creation/edit steps (e.g. Details, Settings, Avatar, Invites)
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $group_creation_steps;

	/**
	 * Types of group statuses (Public, Private, Hidden)
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $valid_status;

	/**
	 * Start the groups component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	function __construct() {
		parent::start(
			'groups',
			__( 'User Groups', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function includes() {
		$includes = array(
			'cache',
			'forums',
			'actions',
			'filters',
			'screens',
			'classes',
			'widgets',
			'activity',
			'template',
			'buddybar',
			'adminbar',
			'functions',
			'notifications'
		);
		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_GROUPS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_GROUPS_SLUG' ) )
			define( 'BP_GROUPS_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name'           => $bp->table_prefix . 'bp_groups',
			'table_name_members'   => $bp->table_prefix . 'bp_groups_members',
			'table_name_groupmeta' => $bp->table_prefix . 'bp_groups_groupmeta'
		);

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		$globals = array(
			'slug'                  => BP_GROUPS_SLUG,
			'root_slug'             => isset( $bp->pages->groups->slug ) ? $bp->pages->groups->slug : BP_GROUPS_SLUG,
			'has_directory'         => true,
			'notification_callback' => 'groups_format_notifications',
			'search_string'         => __( 'Search Groups...', 'buddypress' ),
			'global_tables'         => $global_tables
		);

		parent::setup_globals( $globals );

		/** Single Group Globals **********************************************/

		// Are we viewing a single group?
		if ( bp_is_groups_component() && $group_id = BP_Groups_Group::group_exists( bp_current_action() ) ) {

			$bp->is_single_item  = true;
			$current_group_class = apply_filters( 'bp_groups_current_group_class', 'BP_Groups_Group' );
			$this->current_group = apply_filters( 'bp_groups_current_group_object', new $current_group_class( $group_id ) );

			// When in a single group, the first action is bumped down one because of the
			// group name, so we need to adjust this and set the group name to current_item.
			$bp->current_item   = bp_current_action();
			$bp->current_action = bp_action_variable( 0 );
			array_shift( $bp->action_variables );

			// Using "item" not "group" for generic support in other components.
			if ( bp_current_user_can( 'bp_moderate' ) )
				bp_update_is_item_admin( true, 'groups' );
			else
				bp_update_is_item_admin( groups_is_user_admin( bp_loggedin_user_id(), $this->current_group->id ), 'groups' );

			// If the user is not an admin, check if they are a moderator
			if ( !bp_is_item_admin() )
				bp_update_is_item_mod  ( groups_is_user_mod  ( bp_loggedin_user_id(), $this->current_group->id ), 'groups' );

			// Is the logged in user a member of the group?
			if ( ( is_user_logged_in() && groups_is_user_member( bp_loggedin_user_id(), $this->current_group->id ) ) )
				$this->current_group->is_user_member = true;
			else
				$this->current_group->is_user_member = false;

			// Should this group be visible to the logged in user?
			if ( 'public' == $this->current_group->status || $this->current_group->is_user_member )
				$this->current_group->is_visible = true;
			else
				$this->current_group->is_visible = false;

			// If this is a private or hidden group, does the user have access?
			if ( 'private' == $this->current_group->status || 'hidden' == $this->current_group->status ) {
				if ( $this->current_group->is_user_member && is_user_logged_in() || bp_current_user_can( 'bp_moderate' ) )
					$this->current_group->user_has_access = true;
				else
					$this->current_group->user_has_access = false;
			} else {
				$this->current_group->user_has_access = true;
			}

		// Set current_group to 0 to prevent debug errors
		} else {
			$this->current_group = 0;
		}

		// Illegal group names/slugs
		$this->forbidden_names = apply_filters( 'groups_forbidden_names', array(
			'my-groups',
			'create',
			'invites',
			'send-invites',
			'forum',
			'delete',
			'add',
			'admin',
			'request-membership',
			'members',
			'settings',
			'avatar',
			$this->slug,
			$this->root_slug,
		) );

		// If the user was attempting to access a group, but no group by that name was found, 404
		if ( bp_is_groups_component() && empty( $this->current_group ) && bp_current_action() && !in_array( bp_current_action(), $this->forbidden_names ) ) {
			bp_do_404();
			return;
		}

		if ( bp_is_groups_component() && !empty( $this->current_group ) ) {

			$this->default_extension = apply_filters( 'bp_groups_default_extension', defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ? BP_GROUPS_DEFAULT_EXTENSION : 'home' );

			if ( !bp_current_action() ) {
				$bp->current_action = $this->default_extension;
			}

			// Prepare for a redirect to the canonical URL
			$bp->canonical_stack['base_url'] = bp_get_group_permalink( $this->current_group );

			if ( bp_current_action() ) {
				$bp->canonical_stack['action'] = bp_current_action();
			}

			if ( !empty( $bp->action_variables ) ) {
				$bp->canonical_stack['action_variables'] = bp_action_variables();
			}

			// When viewing the default extension, the canonical URL should not have
			// that extension's slug, unless more has been tacked onto the URL via
			// action variables
			if ( bp_is_current_action( $this->default_extension ) && empty( $bp->action_variables ) )  {
				unset( $bp->canonical_stack['action'] );
			}

		}

		// Group access control
		if ( bp_is_groups_component() && !empty( $this->current_group ) ) {
			if ( !$this->current_group->user_has_access ) {

				// Hidden groups should return a 404 for non-members.
				// Unset the current group so that you're not redirected
				// to the default group tab
				if ( 'hidden' == $this->current_group->status ) {
					$this->current_group = 0;
					$bp->is_single_item  = false;
					bp_do_404();
					return;

				// Skip the no_access check on home and membership request pages
				} elseif ( !bp_is_current_action( 'home' ) && !bp_is_current_action( 'request-membership' ) ) {

					// Off-limits to this user. Throw an error and redirect to the group's home page
					if ( is_user_logged_in() ) {
						bp_core_no_access( array(
							'message'  => __( 'You do not have access to this group.', 'buddypress' ),
							'root'     => bp_get_group_permalink( $bp->groups->current_group ),
							'redirect' => false
						) );

					// User does not have access, and does not get a message
					} else {
						bp_core_no_access();
					}
				}
			}

			// Protect the admin tab from non-admins
			if ( bp_is_current_action( 'admin' ) && !bp_is_item_admin() ) {
				bp_core_no_access( array(
					'message'  => __( 'You are not an admin of this group.', 'buddypress' ),
					'root'     => bp_get_group_permalink( $bp->groups->current_group ),
					'redirect' => false
				) );
			}
		}

		// Preconfigured group creation steps
		$this->group_creation_steps = apply_filters( 'groups_create_group_steps', array(
			'group-details'  => array(
				'name'       => __( 'Details',  'buddypress' ),
				'position'   => 0
			),
			'group-settings' => array(
				'name'       => __( 'Settings', 'buddypress' ),
				'position'   => 10
			)
		) );

		// If avatar uploads are not disabled, add avatar option
		if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) {
			$this->group_creation_steps['group-avatar'] = array(
				'name'     => __( 'Avatar',   'buddypress' ),
				'position' => 20
			);
		}

		// If friends component is active, add invitations
		if ( bp_is_active( 'friends' ) ) {
			$this->group_creation_steps['group-invites'] = array(
				'name'     => __( 'Invites', 'buddypress' ),
				'position' => 30
			);
		}

		// Groups statuses
		$this->valid_status = apply_filters( 'groups_valid_status', array(
			'public',
			'private',
			'hidden'
		) );

		// Auto join group when non group member performs group activity
		$this->auto_join = defined( 'BP_DISABLE_AUTO_GROUP_JOIN' ) && BP_DISABLE_AUTO_GROUP_JOIN ? false : true;
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_nav() {

		// Define local variables
		$sub_nav = array();

		// Add 'Groups' to the main navigation
		$main_nav = array(
			'name'                => sprintf( __( 'Groups <span>%d</span>', 'buddypress' ), groups_total_groups_for_user() ),
			'slug'                => $this->slug,
			'position'            => 70,
			'screen_function'     => 'groups_screen_my_groups',
			'default_subnav_slug' => 'my-groups',
			'item_css_id'         => $this->id
		);

		$groups_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

		// Add the My Groups nav item
		$sub_nav[] = array(
			'name'            => __( 'Memberships', 'buddypress' ),
			'slug'            => 'my-groups',
			'parent_url'      => $groups_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'groups_screen_my_groups',
			'position'        => 10,
			'item_css_id'     => 'groups-my-groups'
		);

		// Add the Group Invites nav item
		$sub_nav[] = array(
			'name'            => __( 'Invitations', 'buddypress' ),
			'slug'            => 'invites',
			'parent_url'      => $groups_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'groups_screen_group_invites',
			'user_has_access' =>  bp_is_my_profile(),
			'position'        => 30
		);

		parent::setup_nav( $main_nav, $sub_nav );

		if ( bp_is_groups_component() && bp_is_single_item() ) {

			// Reset sub nav
			$sub_nav = array();

			// Add 'Groups' to the main navigation
			$main_nav = array(
				'name'                => __( 'Memberships', 'buddypress' ),
				'slug'                => $this->current_group->slug,
				'position'            => -1, // Do not show in BuddyBar
				'screen_function'     => 'groups_screen_group_home',
				'default_subnav_slug' => $this->default_extension,
				'item_css_id'         => $this->id
			);

			$group_link = bp_get_group_permalink( $this->current_group );

			// Add the "Home" subnav item, as this will always be present
			$sub_nav[] = array(
				'name'            =>  _x( 'Home', 'Group home navigation title', 'buddypress' ),
				'slug'            => 'home',
				'parent_url'      => $group_link,
				'parent_slug'     => $this->current_group->slug,
				'screen_function' => 'groups_screen_group_home',
				'position'        => 10,
				'item_css_id'     => 'home'
			);

			// If this is a private group, and the user is not a member, show a "Request Membership" nav item.
			if ( is_user_logged_in() &&
				 !bp_current_user_can( 'bp_moderate' ) &&
				 !$this->current_group->is_user_member &&
				 !groups_check_for_membership_request( bp_loggedin_user_id(), $this->current_group->id ) &&
				 $this->current_group->status == 'private'
				) {
				$sub_nav[] = array(
					'name'               => __( 'Request Membership', 'buddypress' ),
					'slug'               => 'request-membership',
					'parent_url'         => $group_link,
					'parent_slug'        => $this->current_group->slug,
					'screen_function'    => 'groups_screen_group_request_membership',
					'position'           => 30
				);
			}

			// Forums are enabled and turned on
			if ( $this->current_group->enable_forum && bp_is_active( 'forums' ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Forum', 'buddypress' ),
					'slug'            => 'forum',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_forum',
					'position'        => 40,
					'user_has_access' => $this->current_group->user_has_access,
					'item_css_id'     => 'forums'
				);
			}

			$sub_nav[] = array(
				'name'            => sprintf( __( 'Members <span>%s</span>', 'buddypress' ), number_format( $this->current_group->total_member_count ) ),
				'slug'            => 'members',
				'parent_url'      => $group_link,
				'parent_slug'     => $this->current_group->slug,
				'screen_function' => 'groups_screen_group_members',
				'position'        => 60,
				'user_has_access' => $this->current_group->user_has_access,
				'item_css_id'     => 'members'
			);

			if ( bp_is_active( 'friends' ) && bp_groups_user_can_send_invites() ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Invites', 'buddypress' ),
					'slug'            => 'send-invites',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_invite',
					'item_css_id'     => 'invite',
					'position'        => 70,
					'user_has_access' => $this->current_group->user_has_access
				);
			}

			// If the user is a group mod or more, then show the group admin nav item
			if ( bp_is_item_admin() || bp_is_item_mod() ) {
				$sub_nav[] = array(
					'name'            => __( 'Admin', 'buddypress' ),
					'slug'            => 'admin',
					'parent_url'      => $group_link,
					'parent_slug'     => $this->current_group->slug,
					'screen_function' => 'groups_screen_group_admin',
					'position'        => 1000,
					'user_has_access' => true,
					'item_css_id'     => 'admin'
				);
			}

			parent::setup_nav( $main_nav, $sub_nav );
		}

		if ( isset( $this->current_group->user_has_access ) )
			do_action( 'groups_setup_nav', $this->current_group->user_has_access );
		else
			do_action( 'groups_setup_nav');
	}

	/**
	 * Set up the Toolbar
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain = bp_loggedin_user_domain();
			$groups_link = trailingslashit( $user_domain . $this->slug );

			// Pending group invites
			$count   = groups_get_invites_for_user( bp_loggedin_user_id() );
			$title   = __( 'Groups',             'buddypress' );
			$pending = __( 'No Pending Invites', 'buddypress' );

			if ( !empty( $count->total ) ) {
				$title   = sprintf( __( 'Groups <span class="count">%s</span>',          'buddypress' ), $count->total );
				$pending = sprintf( __( 'Pending Invites <span class="count">%s</span>', 'buddypress' ), $count->total );
			}

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => trailingslashit( $groups_link )
			);

			// My Groups
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-memberships',
				'title'  => __( 'Memberships', 'buddypress' ),
				'href'   => trailingslashit( $groups_link )
			);

			// Invitations
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-invites',
				'title'  => $pending,
				'href'   => trailingslashit( $groups_link . 'invites' )
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_title() {
		global $bp;

		if ( bp_is_groups_component() ) {

			if ( bp_is_my_profile() && !bp_is_single_item() ) {

				$bp->bp_options_title = __( 'Memberships', 'buddypress' );

			} else if ( !bp_is_my_profile() && !bp_is_single_item() ) {

				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();

			// We are viewing a single group, so set up the
			// group navigation menu using the $this->current_group global.
			} else if ( bp_is_single_item() ) {
				$bp->bp_options_title  = $this->current_group->name;
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id'    => $this->current_group->id,
					'object'     => 'group',
					'type'       => 'thumb',
					'avatar_dir' => 'group-avatars',
					'alt'        => __( 'Group Avatar', 'buddypress' )
				) );
				if ( empty( $bp->bp_options_avatar ) ) {
					$bp->bp_options_avatar = '<img src="' . esc_attr( $group->avatar_full ) . '" class="avatar" alt="' . esc_attr( $group->name ) . '" />';
				}
			}
		}

		parent::setup_title();
	}
}


function bp_setup_groups() {
	global $bp;

	$bp->groups = new BP_Groups_Component();
}
add_action( 'bp_setup_components', 'bp_setup_groups', 6 );

?>
