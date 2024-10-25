<?php
/**
 * BuddyPress Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Core component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Core extends BP_Component {

	/**
	 * Start the members component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'core',
			'BuddyPress Core',
			buddypress()->plugin_dir
		);

		$this->bootstrap();
	}

	/**
	 * Magic getter.
	 *
	 * This exists specifically for supporting deprecated object vars.
	 *
	 * @since 7.0.0
	 *
	 * @param string $key The object var to get.
	 * @return mixed
	 */
	public function __get( $key = '' ) {

		// Backwards compatibility for the original Notifications table var.
		if ( 'table_name_notifications' === $key ) {
			return bp_is_active( 'notifications' )
				? buddypress()->notifications->table_name
				: buddypress()->table_prefix . 'bp_notifications';
		}

		// Return object var if set, else null.
		return isset( $this->{$key} )
			? $this->{$key}
			: null;
	}

	/**
	 * Populate the global data needed before BuddyPress can continue.
	 *
	 * This involves figuring out the currently required, activated, deactivated,
	 * and optional components.
	 *
	 * @since 1.5.0
	 */
	private function bootstrap() {
		$bp = buddypress();

		/**
		 * Fires before the loading of individual components and after BuddyPress Core.
		 *
		 * Allows plugins to run code ahead of the other components.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_core_loaded' );

		/** Components *******************************************************
		 */

		/**
		 * Filters the included and optional components.
		 *
		 * @since 1.5.0
		 *
		 * @param array $optional_components Array of included and optional components.
		 */
		$bp->optional_components = apply_filters( 'bp_optional_components', array( 'activity', 'blogs', 'friends', 'groups', 'messages', 'notifications', 'settings', 'xprofile' ) );

		/**
		 * Filters the required components.
		 *
		 * @since 1.5.0
		 *
		 * @param array $value Array of required components.
		 */
		$bp->required_components = apply_filters( 'bp_required_components', array( 'members' ) );

		// Get a list of activated components.
		$active_components = bp_get_option( 'bp-active-components' );
		if ( $active_components ) {

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components = apply_filters( 'bp_active_components', $active_components );

			/**
			 * Filters the deactivated components.
			 *
			 * @since 1.0.0
			 *
			 * @param array $value Array of deactivated components.
			 */
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', array_values( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_keys( $bp->active_components ) ) ) );

			// Pre 1.5 Backwards compatibility.
		} elseif ( $deactivated_components = bp_get_option( 'bp-deactivated-components' ) ) {

			// Trim off namespace and filename.
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
			}

			/** This filter is documented in bp-core/bp-core-loader.php */
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', $trimmed );

			// Setup the active components.
			$active_components = array_fill_keys( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_values( $bp->deactivated_components ) ), '1' );

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );

			// Default to all components active.
		} else {

			// Set globals.
			$bp->deactivated_components = array();

			// Setup the active components.
			$active_components = array_fill_keys( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), '1' );

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );
		}

		// Loop through optional components.
		foreach ( $bp->optional_components as $component ) {
			if ( bp_is_active( $component ) && file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php';
			}
		}

		// Loop through required components.
		foreach ( $bp->required_components as $component ) {
			if ( file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php';
			}
		}

		// Add Core to required components.
		$bp->required_components[] = 'core';

		/**
		 * Fires after the loading of individual components.
		 *
		 * @since 2.0.0
		 */
		do_action( 'bp_core_components_included' );
	}

	/**
	 * Include bp-core files.
	 *
	 * @since 1.6.0
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		if ( ! is_admin() ) {
			return;
		}

		$includes = array(
			'admin',
		);

		parent::includes( $includes );
	}

	/**
	 * Set up bp-core global settings.
	 *
	 * Sets up a majority of the BuddyPress globals that require a minimal
	 * amount of processing, meaning they cannot be set in the BuddyPress class.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		/** Database *********************************************************
		 */

		// Get the base database prefix.
		if ( empty( $bp->table_prefix ) ) {
			$bp->table_prefix = bp_core_get_table_prefix();
		}

		// The URL for the root of the site where the main blog resides.
		if ( empty( $bp->root_url ) ) {
			$bp->root_url = bp_rewrites_get_root_url();
		}

		// Fetches all of the core BuddyPress settings in one fell swoop.
		if ( empty( $bp->site_options ) ) {
			$bp->site_options = bp_core_get_root_options();
		}

		// The names of the core WordPress pages used to display BuddyPress content.
		if ( empty( $bp->pages ) ) {
			$bp->pages = bp_core_get_directory_pages();
		}

		/** Basic current user data ******************************************
		 */

		// Logged in user is the 'current_user'.
		$current_user = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$bp->loggedin_user     = new stdClass();
		$bp->loggedin_user->id = isset( $current_user->ID ) ? $current_user->ID : 0;

		/** Avatars **********************************************************
		 */

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar.
		$bp->grav_default = new stdClass();

		/**
		 * Filters the default user Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default user Gravatar.
		 */
		$bp->grav_default->user = apply_filters( 'bp_user_gravatar_default', $bp->site_options['avatar_default'] );

		/**
		 * Filters the default group Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default group Gravatar.
		 */
		$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );

		/**
		 * Filters the default blog Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $gravatar_default Default blog Gravatar.
		 */
		$bp->grav_default->blog = apply_filters( 'bp_blog_gravatar_default', $bp->grav_default->user );

		// Only fully deprecate the legacy navigation globals if BP Classic is not active.
		if ( ! function_exists( 'bp_classic' ) ) {
			// Backward compatibility for plugins modifying the legacy bp_nav and bp_options_nav global properties.
			$bp->bp_nav         = new BP_Core_BP_Nav_BackCompat();
			$bp->bp_options_nav = new BP_Core_BP_Options_Nav_BackCompat();
		}

		/**
		 * Used to determine if user has admin rights on current content. If the
		 * logged in user is viewing their own profile and wants to delete
		 * something, is_item_admin is used. This is a generic variable so it
		 * can be used by other components. It can also be modified, so when
		 * viewing a group 'is_item_admin' would be 'true' if they are a group
		 * admin, and 'false' if they are not.
		 */
		bp_update_is_item_admin( bp_user_has_access(), 'core' );

		// Is the logged in user is a mod for the current item?
		bp_update_is_item_mod( false, 'core' );

		/*
		 * As the BP Core component is excluded from the BP Component code
		 * used to set the Rewrite IDs, we need to set it here. As the `search`
		 * word is already a WordPress rewrite tag, we are not adding a custom
		 * rule for this component to avoid messing with it.
		 */
		$this->rewrite_ids = array(
			'community_search' => 'bp_search',
		);

		parent::setup_globals(
			array(
				'block_globals' => array(
					'bp/login-form' => array(
						'widget_classnames' => array( 'widget_bp_core_login_widget', 'buddypress' ),
					),
				),
			)
		);
	}

	/**
	 * Setup cache groups
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp',
				'bp_pages',
				'bp_invitations',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Set up post types.
	 *
	 * @since 2.4.0
	 * @since 12.0.0 Registers the 'buddypress' post type for component directories.
	 */
	public function register_post_types() {
		// Component directories.
		if ( (int) get_current_blog_id() === bp_get_post_type_site_id() ) {
			register_post_type(
				'buddypress',
				array(
					'label'               => _x( 'BuddyPress Directories', 'Post Type label', 'buddypress' ),
					'labels'              => array(
						'singular_name' => _x( 'BuddyPress Directory', 'Post Type singular name', 'buddypress' ),
					),
					'description'         => __( 'The BuddyPress Post Type used for component directories.', 'buddypress' ),
					'public'              => true,
					'hierarchical'        => true,
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => false,
					'show_in_nav_menus'   => true,
					'show_in_rest'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
					'query_var'           => false,
					'delete_with_user'    => false,
				)
			);
		}

		// Emails.
		if ( bp_is_root_blog() && ! is_network_admin() ) {
			register_post_type(
				bp_get_email_post_type(),
				apply_filters(
					'bp_register_email_post_type',
					array(
						'description'        => _x( 'BuddyPress emails', 'email post type description', 'buddypress' ),
						'capabilities'       => array(
							'edit_posts'          => 'bp_moderate',
							'edit_others_posts'   => 'bp_moderate',
							'publish_posts'       => 'bp_moderate',
							'read_private_posts'  => 'bp_moderate',
							'delete_posts'        => 'bp_moderate',
							'delete_others_posts' => 'bp_moderate',
						),
						'map_meta_cap'       => true,
						'labels'             => bp_get_email_post_type_labels(),
						'menu_icon'          => 'dashicons-email',
						'public'             => false,
						'publicly_queryable' => bp_current_user_can( 'bp_moderate' ),
						'query_var'          => false,
						'rewrite'            => false,
						'show_in_admin_bar'  => false,
						'show_ui'            => bp_current_user_can( 'bp_moderate' ),
						'supports'           => bp_get_email_post_type_supports(),
					)
				)
			);
		}

		parent::register_post_types();
	}

	/**
	 * Parse the WP_Query and eventually set the BP Search mechanism.
	 *
	 * Search doesn't have an associated page, so we check for it separately.
	 *
	 * @since 12.0.0
	 *
	 * @param WP_Query $query Required. See BP_Component::parse_query() for
	 *                        description.
	 */
	public function parse_query( $query ) {
		/*
		 * If BP Rewrites are not in use, no need to parse BP URI globals another time.
		 * Legacy Parser should have already set these.
		 */
		if ( 'rewrites' !== bp_core_get_query_parser() ) {
			return parent::parse_query( $query );
		}

		$is_search = $query->get( 'pagename' ) === bp_get_search_slug() || ( isset( $_GET['bp_search'] ) && 1 === (int) $_GET['bp_search'] );

		if ( isset( $_POST['search-terms'] ) && $is_search ) {
			buddypress()->current_component = bp_get_search_slug();
		}

		parent::parse_query( $query );
	}

	/**
	 * Init the Core controllers of the BP REST API.
	 *
	 * @since 9.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BP_REST_Components_Endpoint',
		);

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the BP Core Blocks.
	 *
	 * @since 9.0.0
	 * @since 12.0.0 Use the WP Blocks API v2.
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init(
			array(
				'bp/login-form' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-core/blocks/login-form',
					'render_callback' => 'bp_block_render_login_form_block',
				),
			)
		);
	}
}
