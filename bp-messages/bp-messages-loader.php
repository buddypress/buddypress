<?php

/**
 * BuddyPress Private Messages Loader
 *
 * A private messages component, for users to send messages to each other
 *
 * @package BuddyPress
 * @subpackage Messages Core
 */

class BP_Messages_Component extends BP_Component {

	/**
	 * Start the messages component creation process
	 *
	 * @since BuddyPress {unknown}
	 */
	function BP_Messages_Component() {
		parent::start(
			'messages',
			__( 'Private Messages', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function _includes() {
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
		);

		parent::_includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_MESSAGES_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress {unknown}
	 * @global obj $bp
	 */
	function _setup_globals() {
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
			'root_slug'             => isset( $bp->pages->messages->slug ) ? $bp->pages->messages->slug : BP_MESSAGES_SLUG,
			'notification_callback' => 'messages_format_notifications',
			'search_string'         => __( 'Search Messages...', 'buddypress' ),
			'global_tables'         => $global_tables,
		);

		$this->autocomplete_all = defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' );

		parent::_setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		if ( $count = messages_get_unread_count() )
			$name = sprintf( __( 'Messages <strong>(%s)</strong>', 'buddypress' ), $count );
		else
			$name = __( 'Messages <strong></strong>', 'buddypress' );

		// Add 'Messages' to the main navigation
		$main_nav = array(
			'name'                    => $name,
			'slug'                    => $this->slug,
			'root_slug'               => $this->root_slug,
			'position'                => 50,
			'show_for_displayed_user' => false,
			'screen_function'         => 'messages_screen_inbox',
			'default_subnav_slug'     => 'inbox',
			'item_css_id'             => $this->id
		);

		// Link to user messages
		$messages_link = trailingslashit( $bp->loggedin_user->domain . $this->slug );

		// Add the subnav items to the profile
		$sub_nav[] = array(
			'name'            => __( 'Inbox', 'buddypress' ),
			'slug'            => 'inbox',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_inbox',
			'position'        => 10,
			'user_has_access' => bp_is_my_profile()
		);

		$sub_nav[] = array(
			'name'            => __( 'Sent Messages', 'buddypress' ),
			'slug'            => 'sentbox',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_sentbox',
			'position'        => 20,
			'user_has_access' => bp_is_my_profile()
		);

		$sub_nav[] = array(
			'name'            => __( 'Compose', 'buddypress' ),
			'slug'            => 'compose',
			'parent_url'      => $messages_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'messages_screen_compose',
			'position'        => 30,
			'user_has_access' => bp_is_my_profile()
		);

		if ( is_super_admin() ) {
			$sub_nav[] = array(
				'name'            => __( 'Notices', 'buddypress' ),
				'slug'            => 'notices',
				'parent_url'      => $messages_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'messages_screen_notices',
				'position'        => 90,
				'user_has_access' => is_super_admin()
			);
		}

		if ( bp_is_messages_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Messages', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title = $bp->displayed_user->fullname;
			}
		}

		parent::_setup_nav( $main_nav, $sub_nav );
	}
}
// Create the messages component
$bp->messages = new BP_Messages_Component();

?>