<?php

/**
 * BuddyPress Messages Loader
 *
 * A private messages component, for users to send messages to each other
 *
 * @package BuddyPress
 * @subpackage MessagesLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Messages_Component extends BP_Component {
	/**
	 * If this is true, the Message autocomplete will return friends only, unless
	 * this is set to false, in which any matching users will be returned.
	 *
	 * @since BuddyPress (1.5)
	 * @var bool
	 */
	public $autocomplete_all;

	/**
	 * Start the messages component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	function __construct() {
		parent::start(
			'messages',
			__( 'Private Messages', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function includes() {
		// Files to include
		$includes = array(
			'cssjs',
			'cache',
			'actions',
			'screens',
			'classes',
			'filters',
			'template',
			'functions',
			'notifications'
		);

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_MESSAGES_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_MESSAGES_SLUG' ) )
			define( 'BP_MESSAGES_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name_notices'    => $bp->table_prefix . 'bp_messages_notices',
			'table_name_messages'   => $bp->table_prefix . 'bp_messages_messages',
			'table_name_recipients' => $bp->table_prefix . 'bp_messages_recipients'
		);

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		$globals = array(
			'slug'                  => BP_MESSAGES_SLUG,
			'has_directory'         => false,
			'notification_callback' => 'messages_format_notifications',
			'search_string'         => __( 'Search Messages...', 'buddypress' ),
			'global_tables'         => $global_tables
		);

		$this->autocomplete_all = defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' );

		parent::setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	function setup_nav() {

		$sub_nav = array();
		$name    = sprintf( __( 'Messages <span>%s</span>', 'buddypress' ), bp_get_total_unread_messages_count() );

		// Add 'Messages' to the main navigation
		$main_nav = array(
			'name'                    => $name,
			'slug'                    => $this->slug,
			'position'                => 50,
			'show_for_displayed_user' => false,
			'screen_function'         => 'messages_screen_inbox',
			'default_subnav_slug'     => 'inbox',
			'item_css_id'             => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		// Link to user messages
		$messages_link = trailingslashit( $user_domain . $this->slug );

		// Add the subnav items to the profile
		$sub_nav[] = array(
			'name'            => __( 'Inbox', 'buddypress' ),
			'slug'            => 'inbox',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_inbox',
			'position'        => 10,
			'user_has_access' => bp_core_can_edit_settings()
		);

		$sub_nav[] = array(
			'name'            => __( 'Sent', 'buddypress' ),
			'slug'            => 'sentbox',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_sentbox',
			'position'        => 20,
			'user_has_access' => bp_core_can_edit_settings()
		);

		$sub_nav[] = array(
			'name'            => __( 'Compose', 'buddypress' ),
			'slug'            => 'compose',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_compose',
			'position'        => 30,
			'user_has_access' => bp_core_can_edit_settings()
		);

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Notices', 'buddypress' ),
				'slug'            => 'notices',
				'parent_url'      => $messages_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'messages_screen_notices',
				'position'        => 90,
				'user_has_access' => bp_current_user_can( 'bp_moderate' )
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
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
			$user_domain   = bp_loggedin_user_domain();
			$messages_link = trailingslashit( $user_domain . $this->slug );

			// Unread message count
			$count = messages_get_unread_count();
			if ( !empty( $count ) ) {
				$title = sprintf( __( 'Messages <span class="count">%s</span>', 'buddypress' ), number_format_i18n( $count ) );
				$inbox = sprintf( __( 'Inbox <span class="count">%s</span>',    'buddypress' ), number_format_i18n( $count ) );
			} else {
				$title = __( 'Messages', 'buddypress' );
				$inbox = __( 'Inbox',    'buddypress' );
			}

			// Add main Messages menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => trailingslashit( $messages_link )
			);

			// Inbox
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-inbox',
				'title'  => $inbox,
				'href'   => trailingslashit( $messages_link . 'inbox' )
			);

			// Sent Messages
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-sentbox',
				'title'  => __( 'Sent', 'buddypress' ),
				'href'   => trailingslashit( $messages_link . 'sentbox' )
			);

			// Compose Message
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-compose',
				'title'  => __( 'Compose', 'buddypress' ),
				'href'   => trailingslashit( $messages_link . 'compose' )
			);

			// Site Wide Notices
			if ( bp_current_user_can( 'bp_moderate' ) ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-notices',
					'title'  => __( 'All Member Notices', 'buddypress' ),
					'href'   => trailingslashit( $messages_link . 'notices' )
				);
			}
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

		if ( bp_is_messages_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Messages', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
}

function bp_setup_messages() {
	global $bp;
	$bp->messages = new BP_Messages_Component();
}
add_action( 'bp_setup_components', 'bp_setup_messages', 6 );
