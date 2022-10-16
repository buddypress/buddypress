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
class BP_Core extends BP_Component {

	/**
	 * Start the members component creation process.
	 *
	 * @since 1.5.0
	 *
	 */
	public function __construct() {
		parent::start(
			'core',
			__( 'BuddyPress Core', 'buddypress' ),
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
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key = '' ) {

		// Backwards compatibility for the original Notifications table var
		if ( 'table_name_notifications' === $key ) {
			return bp_is_active( 'notifications' )
				? buddypress()->notifications->table_name
				: buddypress()->table_prefix . 'bp_notifications';
		}

		// Return object var if set, else null
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
		 * @param array $value Array of included and optional components.
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
		if ( $active_components = bp_get_option( 'bp-active-components' ) ) {

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components      = apply_filters( 'bp_active_components', $active_components );

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
			$active_components     = array_fill_keys( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_values( $bp->deactivated_components ) ), '1' );

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );

		// Default to all components active.
		} else {

			// Set globals.
			$bp->deactivated_components = array();

			// Setup the active components.
			$active_components     = array_fill_keys( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), '1' );

			/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );
		}

		// Loop through optional components.
		foreach( $bp->optional_components as $component ) {
			if ( bp_is_active( $component ) && file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' );
			}
		}

		// Loop through required components.
		foreach( $bp->required_components as $component ) {
			if ( file_exists( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include( $bp->plugin_dir . '/bp-' . $component . '/bp-' . $component . '-loader.php' );
			}
		}

		// Add Core to required components.
		$bp->required_components[] = 'core';

		// Hook to `bp_screens` to eventually load the community gate template.
		add_action( 'bp_screens', array( $this, 'screen_index' ) );

		// Hook to `bp_setup_theme_compat` to set theme compat for the community gate page.
		add_action( 'bp_setup_theme_compat', array( $this, 'is_gate' ) );

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
			'admin'
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

		// The domain for the root of the site where the main blog resides.
		if ( empty( $bp->root_domain ) ) {
			$bp->root_domain = bp_core_get_root_domain();
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
		$current_user            = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$bp->loggedin_user       = new stdClass;
		$bp->loggedin_user->id   = isset( $current_user->ID ) ? $current_user->ID : 0;

		/** Avatars **********************************************************
		 */

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar.
		$bp->grav_default        = new stdClass;

		/**
		 * Filters the default user Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default user Gravatar.
		 */
		$bp->grav_default->user  = apply_filters( 'bp_user_gravatar_default',  $bp->site_options['avatar_default'] );

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
		 * @param string $value Default blog Gravatar.
		 */
		$bp->grav_default->blog  = apply_filters( 'bp_blog_gravatar_default',  $bp->grav_default->user );

		// Backward compatibility for plugins modifying the legacy bp_nav and bp_options_nav global properties.
		$bp->bp_nav         = new BP_Core_BP_Nav_BackCompat();
		$bp->bp_options_nav = new BP_Core_BP_Options_Nav_BackCompat();

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

		// Set the community gate page title.
		$this->directory_title = __( 'Restricted Access', 'buddypress' );

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
	 * Set up the canonical URL stack for the core component.
	 *
	 * @since 11.0.0
	 */
	public function setup_canonical_stack() {
		$bp = buddypress();

		if ( bp_is_current_component( $this->id ) ) {
			$slug = bp_core_get_community_gate_slug();

			if ( ! in_array( $slug, $bp->unfiltered_uri, true ) ) {
				$redirect_after_login                 = home_url( implode( '/', $bp->unfiltered_uri ) . '/' );
				$redirect_now_url                     = home_url( $slug . '/' );
				$bp->canonical_stack['base_url']      = $redirect_now_url;
				$bp->canonical_stack['canonical_url'] = $redirect_now_url;
				$bp->canonical_stack['requested_url'] = add_query_arg( 'redirect_to', $redirect_after_login );
			}
		}
	}

	/**
	 * Load the template for BuddyPress standalone themes.
	 *
	 * @since 11.0.0
	 */
	public function screen_index() {
		if ( bp_is_current_component( $this->id ) && ! bp_current_action() ) {
			/**
			 * Fires right before the loading of the community gate screen template file.
			 *
			 * @since 11.0.0
			 */
			do_action( 'bp_core_screen_index' );

			if ( ! bp_use_theme_compat_with_current_theme() ) {
				$theme_has_template = (bool) locate_template( array( 'members/gate.php' ), false );

				// No theme template were found: use the /wp-login.php redirection in this case.
				if ( false === $theme_has_template ) {
					bp_core_user_has_no_community_visibility();
				}
			}

			/**
			 * Filters the template to load for the community gate screen.
			 *
			 * @since 11.0.0
			 *
			 * @param string $template Path to the community gate template to load.
			 */
			bp_core_load_template( apply_filters( 'bp_core_screen_template', 'members/gate' ) );
		}
	}

	/**
	 * Update the global $post with community gate data.
	 *
	 * @since 11.0.0
	 */
	public function gate_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => bp_get_directory_title( 'core' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the community gate template part.
	 *
	 * @since 11.0.0
	 */
	public function gate_content() {
		return bp_buffer_template_part( 'members/gate', null, false );
	}

	/**
	 * Set up the theme compatibility hooks, if we're looking at the community gate page.
	 *
	 * @since 11.0.0
	 */
	public function is_gate() {
		if ( ! bp_is_current_component( $this->id ) ) {
			return;
		}

		if ( ! bp_current_action() ) {
			/** This action is documented in bp-core/classes/class-bp-core.php */
			do_action( 'bp_core_screen_index' );

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'gate_dummy_post' ) );

			$template_pack_has_part = bp_locate_template( array( 'members/gate.php' ) );

			// No Template Pack parts were found: use the /wp-login.php redirection in this case.
			if ( false === $template_pack_has_part ) {
				bp_core_user_has_no_community_visibility();

				// A template part was found load it.
			} else {
				add_filter( 'bp_replace_the_content', array( $this, 'gate_content' ) );
			}
		}
	}

	/**
	 * Setup cache groups
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp',
			'bp_pages',
			'bp_invitations',
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Set up post types.
	 *
	 * @since BuddyPress (2.4.0)
	 */
	public function register_post_types() {

		// Emails
		if ( bp_is_root_blog() && ! is_network_admin() ) {
			register_post_type(
				bp_get_email_post_type(),
				apply_filters( 'bp_register_email_post_type', array(
					'description'       => _x( 'BuddyPress emails', 'email post type description', 'buddypress' ),
					'capabilities'      => array(
						'edit_posts'          => 'bp_moderate',
						'edit_others_posts'   => 'bp_moderate',
						'publish_posts'       => 'bp_moderate',
						'read_private_posts'  => 'bp_moderate',
						'delete_posts'        => 'bp_moderate',
						'delete_others_posts' => 'bp_moderate',
					),
					'map_meta_cap'      => true,
					'labels'            => bp_get_email_post_type_labels(),
					'menu_icon'         => 'dashicons-email',
					'public'            => false,
					'publicly_queryable' => bp_current_user_can( 'bp_moderate' ),
					'query_var'         => false,
					'rewrite'           => false,
					'show_in_admin_bar' => false,
					'show_ui'           => bp_current_user_can( 'bp_moderate' ),
					'supports'          => bp_get_email_post_type_supports(),
				) )
			);
		}

		parent::register_post_types();
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
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init(
			array(
				'bp/login-form' => array(
					'name'               => 'bp/login-form',
					'editor_script'      => 'bp-login-form-block',
					'editor_script_url'  => plugins_url( 'js/blocks/login-form.js', dirname( __FILE__ ) ),
					'editor_script_deps' => array(
						'wp-blocks',
						'wp-element',
						'wp-components',
						'wp-i18n',
						'wp-block-editor',
						'wp-server-side-render',
					),
					'style'              => 'bp-login-form-block',
					'style_url'          => plugins_url( 'css/blocks/login-form.css', dirname( __FILE__ ) ),
					'attributes'         => array(
						'title'         => array(
							'type'    => 'string',
							'default' => '',
						),
						'forgotPwdLink' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					'render_callback'    => 'bp_block_render_login_form_block',
				),
			)
		);
	}
}
