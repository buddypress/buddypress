<?php
/**
 * BuddyPress Members Invitations feature Class.
 *
 * @package BuddyPress
 * @subpackage Members
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The BP Members Invitations feature is optional.
 *
 * If you want to disable it, you can use:
 * `add_filter( 'bp_is_members_invitations_active', '__return_false' );`
 *
 * @since 15.0.0
 */
class BP_Members_Invitations_Feature extends BP_Component_Feature {

	/**
	 * Invitations Feature initialization.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		parent::init( 'invitations', 'members' );
	}

	/**
	 * Set up Signups feature global variables.
	 *
	 * @since 15.0.0
	 */
	public function globals() {
		buddypress()->members->invitations = new stdClass();

		parent::globals();
	}

	/**
	 * Include Invitations feature files.
	 *
	 * @since 15.0.0
	 *
	 * @see `BP_Component_Feature::includes()` for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component_Feature::includes()}.
	 */
	public function includes( $includes = array() ) {
		parent::includes( array( 'bp-members-invitations' ) );
	}

	/**
	 * Include screen/action files later & when on specific pages.
	 *
	 * @since 15.0.0
	 */
	public function late_includes() {
		// Invitations.
		if ( is_user_logged_in() && bp_is_user_members_invitations() ) {
			$bp = buddypress();

			// Actions.
			if ( isset( $_POST['members_invitations'] ) ) {
				require_once $bp->members->path . 'bp-members/actions/invitations-bulk-manage.php';
			}

			// Screens.
			require_once $bp->members->path . 'bp-members/screens/invitations.php';
		}
	}

	/**
	 * Register Invitations feature navigation.
	 *
	 * @since 15.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		if ( bp_get_members_invitations_allowed() ) {
			// Add 'Invitations' to the main user profile navigation.
			$main_nav = array(
				'name'                     => __( 'Invitations', 'buddypress' ),
				'slug'                     => bp_get_members_invitations_slug(),
				'position'                 => 80,
				'screen_function'          => 'members_screen_send_invites',
				'default_subnav_slug'      => 'list-invites',
				'show_for_displayed_user'  => false, // Non-admin users should only see their own invites.
				'user_has_access_callback' => 'bp_members_invitations_user_can_view_screens',
			);

			// Create two subnav items for community invitations.
			$sub_nav[] = array(
				'name'                     => __( 'Send Invites', 'buddypress' ),
				'slug'                     => 'send-invites',
				'parent_slug'              => bp_get_members_invitations_slug(),
				'screen_function'          => 'members_screen_send_invites',
				'position'                 => 10,
				'user_has_access'          => false,
				'user_has_access_callback' => 'bp_members_invitations_user_can_view_send_screen',
			);

			$sub_nav[] = array(
				'name'                     => __( 'Pending Invites', 'buddypress' ),
				'slug'                     => 'list-invites',
				'parent_slug'              => bp_get_members_invitations_slug(),
				'screen_function'          => 'members_screen_list_sent_invites',
				'position'                 => 20,
				'user_has_access'          => false,
				'user_has_access_callback' => 'bp_members_invitations_user_can_view_screens',
			);
		}

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up Invitations feature navigation.
	 *
	 * @since 15.0.0 Used to customize the default subnavigation slug.
	 *
	 * @see `BP_Component::setup_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		if ( bp_is_my_profile() && bp_user_can( bp_displayed_user_id(), 'bp_members_invitations_view_send_screen' ) ) {
			$this->main_nav['default_subnav_slug'] = 'send-invites';
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Invitations feature entries in the WordPress Admin Bar.
	 *
	 * @since 15.0.0
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *                            for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		if ( bp_current_user_can( 'bp_members_invitations_view_screens' ) ) {
			$bp             = buddypress();
			$invite_slug    = bp_get_members_invitations_slug();
			$invite_menu_id = $bp->my_account_menu_id . '-invitations';

			$wp_admin_nav[] = array(
				'id'     => $invite_menu_id,
				'parent' => $bp->my_account_menu_id,
				'title'  => __( 'Invitations', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug ) ) ),
			);

			if ( bp_current_user_can( 'bp_members_invitations_view_send_screen' ) ) {
				$wp_admin_nav[] = array(
					'id'     => $bp->my_account_menu_id . '-invitations-send',
					'parent' => $invite_menu_id,
					'title'  => __( 'Send Invites', 'buddypress' ),
					'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug, 'send-invites' ) ) ),
				);
			}

			$wp_admin_nav[] = array(
				'id'     => $bp->my_account_menu_id . '-invitations-list',
				'parent' => $invite_menu_id,
				'title'  => __( 'Pending Invites', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug, 'list-invites' ) ) ),
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}
}
