<?php
/**
 * Functions of BuddyPress's "Nouveau" template pack.
 *
 * @since 3.0.0
 * @package BuddyPress
 * @version 12.0.0
 *
 * @buddypress-template-pack {
 *   Template Pack ID:       nouveau
 *   Template Pack Name:     BP Nouveau
 *   Version:                1.0.0
 *   WP required version:    4.5.0
 *   BP required version:    3.0.0
 *   Description:            A new template pack for BuddyPress!
 *   Text Domain:            bp-nouveau
 *   Domain Path:            /languages/
 *   Author:                 The BuddyPress community
 *   Template Pack Supports: activity, blogs, friends, groups, messages, notifications, settings, xprofile
 * }}
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Theme Setup ***************************************************************/

/**
 * Loads BuddyPress Nouveau Template pack functionality.
 *
 * See @link BP_Theme_Compat() for more.
 *
 * @since 3.0.0
 */
class BP_Nouveau extends BP_Theme_Compat {

	/**
	 * Instance of this class.
	 *
	 * @var BP_Nouveau|null
	 */
	protected static $instance = null;

	/**
	 * Return the instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @return BP_Nouveau
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * The BP Nouveau constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		parent::start();

		$this->includes();
		$this->setup_support();
	}

	/**
	 * BP Nouveau global variables.
	 *
	 * @since 3.0.0
	 */
	protected function setup_globals() {
		$bp = buddypress();

		foreach ( $bp->theme_compat->packages['nouveau'] as $property => $value ) {
			$this->{$property} = $value;
		}

		$this->includes_dir   = trailingslashit( $this->dir ) . 'includes/';
		$this->directory_nav  = new BP_Core_Nav( bp_get_root_blog_id() );
		$this->is_block_theme = false;

		if ( bp_is_running_wp( '5.9.0', '>=' ) ) {
			$this->is_block_theme = wp_is_block_theme();
		}
	}

	/**
	 * Includes!
	 *
	 * @since 3.0.0
	 */
	protected function includes() {
		require $this->includes_dir . 'functions.php';
		require $this->includes_dir . 'classes.php';
		require $this->includes_dir . 'template-tags.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->includes_dir . 'ajax.php';

		// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) {
					require bp_nouveau()->includes_dir . 'ajax.php';
				}
			}, 0 );
		}

		// The customizer is only used by classic themes.
		if ( ! $this->is_block_theme ) {
			add_action(
				'bp_customize_register',
				function() {
					if ( bp_is_root_blog() && current_user_can( 'customize' ) ) {
						require bp_nouveau()->includes_dir . 'customizer.php';
					}
				},
				0
			);
		} elseif ( wp_using_themes() && ! isset( $_GET['bp_customizer'] ) ) {
			remove_action( 'customize_register', 'bp_customize_register', 20 );
		}

		foreach ( bp_core_get_packaged_component_ids() as $component ) {
			$component_loader = trailingslashit( $this->includes_dir ) . $component . '/loader.php';

			if ( ! bp_is_active( $component ) || ! file_exists( $component_loader ) ) {
				continue;
			}

			require( $component_loader );
		}

		/**
		 * Fires after all of the BuddyPress Nouveau includes have been loaded. Passed by reference.
		 *
		 * @since 3.0.0
		 *
		 * @param BP_Nouveau $template_pack Current Template Pack instance.
		 */
		do_action_ref_array( 'bp_nouveau_includes', array( &$this ) );
	}

	/**
	 * Setup the Template Pack features support.
	 *
	 * @since 3.0.0
	 */
	protected function setup_support() {
		$width      = 1300;
		$top_offset = 150;

		/** This filter is documented in bp-core/bp-core-avatars.php. */
		$avatar_height = apply_filters( 'bp_core_avatar_full_height', $top_offset );

		if ( $avatar_height > $top_offset ) {
			$top_offset = $avatar_height;
		}

		if ( $this->is_block_theme ) {
			$width = (int) wp_get_global_settings( array( 'layout', 'contentSize' ) );
		}

		bp_set_theme_compat_feature(
			$this->id,
			array(
				'name'     => 'cover_image',
				'settings' => array(
					'components'   => array( 'members', 'groups' ),
					'width'        => $width,
					'height'       => $top_offset + round( $avatar_height / 2 ),
					'callback'     => 'bp_nouveau_theme_cover_image',
					'theme_handle' => 'bp-nouveau',
				),
			)
		);

		bp_set_theme_compat_feature(
			$this->id,
			array(
				'name'     => 'priority_item_nav',
				'settings' => array(
					'single_items' => $this->is_block_theme ? array( 'member', 'group' ) : array(),
				),
			)
		);
	}

	/**
	 * Setup the Template Pack common actions.
	 *
	 * @since 3.0.0
	 */
	protected function setup_actions() {
		// Filter BuddyPress template hierarchy and look for page templates.
		add_filter( 'bp_get_buddypress_template', array( $this, 'theme_compat_page_templates' ), 10, 1 );

		// Add our "buddypress" div wrapper to theme compat template parts.
		add_filter( 'bp_replace_the_content', array( $this, 'theme_compat_wrapper' ), 999 );

		// We need to neutralize the BuddyPress core "bp_core_render_message()" once it has been added.
		add_action( 'bp_actions', array( $this, 'neutralize_core_template_notices' ), 6 );

		// Register scripts & styles.
		add_action( 'bp_enqueue_community_scripts', array( $this, 'register_scripts' ), 2 );

		// Enqueue theme CSS.
		add_action( 'bp_enqueue_community_scripts', array( $this, 'enqueue_styles' ) );

		// Enqueue theme JS.
		add_action( 'bp_enqueue_community_scripts', array( $this, 'enqueue_scripts' ) );

		// Enqueue theme script localization.
		add_action( 'bp_enqueue_community_scripts', array( $this, 'localize_scripts' ) );
		remove_action( 'bp_enqueue_community_scripts', 'bp_core_confirmation_js' );

		/** This filter is documented in bp-core/bp-core-dependency.php */
		if ( is_buddypress() || ! apply_filters( 'bp_enqueue_assets_in_bp_pages_only', true ) ) {
			// Body no-js class.
			add_filter( 'body_class', array( $this, 'add_nojs_body_class' ), 20, 1 );
		}

		// Ajax querystring.
		add_filter( 'bp_ajax_querystring', 'bp_nouveau_ajax_querystring', 10, 2 );

		// Register directory nav items.
		add_action( 'bp_screens', array( $this, 'setup_directory_nav' ), 15 );

		// Register the Default front pages Dynamic Sidebars.
		add_action( 'widgets_init', 'bp_nouveau_register_sidebars', 11 );

		// Modify "registration disabled" and welcome message if invitations are enabled.
		add_action( 'bp_nouveau_feedback_messages', array( $this, 'filter_registration_messages' ), 99 );

		/** Override **********************************************************/

		/**
		 * Fires after all of the BuddyPress theme compat actions have been added.
		 *
		 * @since 3.0.0
		 *
		 * @param BP_Nouveau $template_pack Current Template Pack instance.
		 */
		do_action_ref_array( 'bp_theme_compat_actions', array( &$this ) );
	}

	/**
	 * Enqueue the template pack css files
	 *
	 * @since 3.0.0
	 */
	public function enqueue_styles() {
		$min = bp_core_get_minified_asset_suffix();
		$rtl = '';

		if ( is_rtl() ) {
			$rtl = '-rtl';
		}

		/**
		 * Filters the BuddyPress Nouveau CSS dependencies.
		 *
		 * @since 3.0.0
		 *
		 * @param array $value Array of style dependencies. Default Dashicons.
		 */
		$css_dependencies = apply_filters( 'bp_nouveau_css_dependencies', array( 'dashicons', 'bp-tooltips' ) );

		/**
		 * Filters the styles to enqueue for BuddyPress Nouveau.
		 *
		 * This filter provides a multidimensional array that will map to arguments used for wp_enqueue_style().
		 * The primary index should have the stylesheet handle to use, and be assigned an array that has indexes for
		 * file location, dependencies, and version.
		 *
		 * @since 3.0.0
		 *
		 * @param array $value Array of styles to enqueue.
		 */
		$styles = apply_filters( 'bp_nouveau_enqueue_styles', array(
			'bp-nouveau' => array(
				'file' => 'css/buddypress%1$s%2$s.css', 'dependencies' => $css_dependencies, 'version' => $this->version,
			),
			'bp-nouveau-priority-nav' => array(
				'file' => 'css/priority-nav%1$s%2$s.css', 'dependencies' => array( 'dashicons' ), 'version' => $this->version,
			),
		) );

		if ( $styles ) {

			foreach ( $styles as $handle => $style ) {
				if ( ! isset( $style['file'] ) ) {
					continue;
				}

				$file = sprintf( $style['file'], $rtl, $min );

				// Locate the asset if needed.
				if ( false === strpos( $style['file'], '://' ) ) {
					$asset = bp_locate_template_asset( $file );

					if ( empty( $asset['uri'] ) || false === strpos( $asset['uri'], '://' ) ) {
						continue;
					}

					$file = $asset['uri'];
				}

				$data = bp_parse_args(
					$style,
					array(
						'dependencies' => array(),
						'version'      => $this->version,
						'type'         => 'screen',
					),
					'nouveau_enqueue_styles'
				);

				wp_enqueue_style( $handle, $file, $data['dependencies'], $data['version'], $data['type'] );

				if ( $min ) {
					wp_style_add_data( $handle, 'suffix', $min );
				}
			}
		}

		// Compatibility stylesheets for specific themes.
		$theme                = get_template();
		$companion_stylesheet = bp_locate_template_asset( sprintf( 'css/%1$s%2$s.css', $theme, $min ) );
		$companion_handle     = 'bp-' . $theme;

		if ( ! is_rtl() && isset( $companion_stylesheet['uri'] ) && $companion_stylesheet['uri'] ) {
			wp_enqueue_style( $companion_handle, $companion_stylesheet['uri'], array(), $this->version, 'screen' );

			if ( $min ) {
				wp_style_add_data( $companion_handle, 'suffix', $min );
			}
		}

		// Compatibility stylesheet for specific themes, RTL-version.
		if ( is_rtl() ) {
			$rtl_companion_stylesheet = bp_locate_template_asset( sprintf( 'css/%1$s-rtl%2$s.css', $theme, $min ) );

			if ( isset( $rtl_companion_stylesheet['uri'] ) ) {
				$companion_handle .= '-rtl';
				wp_enqueue_style( $companion_handle, $rtl_companion_stylesheet['uri'], array(), $this->version, 'screen' );

				if ( $min ) {
					wp_style_add_data( $companion_handle, 'suffix', $min );
				}
			}
		}
	}

	/**
	 * Register Template Pack JavaScript files
	 *
	 * @since 3.0.0
	 */
	public function register_scripts() {
		$min          = bp_core_get_minified_asset_suffix();
		$dependencies = bp_core_get_js_dependencies();
		$bp_confirm   = array_search( 'bp-confirm', $dependencies );

		unset( $dependencies[ $bp_confirm ] );

		/**
		 * Filters the scripts to enqueue for BuddyPress Nouveau.
		 *
		 * This filter provides a multidimensional array that will map to arguments used for wp_register_script().
		 * The primary index should have the script handle to use, and be assigned an array that has indexes for
		 * file location, dependencies, version and if it should load in the footer or not.
		 *
		 * @since 3.0.0
		 *
		 * @param array $value Array of scripts to register.
		 */
		$scripts = apply_filters(
			'bp_nouveau_register_scripts',
			array(
				'bp-nouveau' => array(
					'file'         => 'js/buddypress-nouveau%s.js',
					'dependencies' => $dependencies,
					'version'      => $this->version,
					'footer'       => true,
				),
				'bp-nouveau-priority-menu' => array(
					'file'         => 'js/buddypress-priority-menu%s.js',
					'dependencies' => array(),
					'version'      => $this->version,
					'footer'       => true,
				)
			)
		);

		// Bail if no scripts.
		if ( empty( $scripts ) ) {
			return;
		}

		foreach ( $scripts as $handle => $script ) {
			if ( ! isset( $script['file'] ) ) {
				continue;
			}

			$file = sprintf( $script['file'], $min );

			// Locate the asset if needed.
			if ( false === strpos( $script['file'], '://' ) ) {
				$asset = bp_locate_template_asset( $file );

				if ( empty( $asset['uri'] ) || false === strpos( $asset['uri'], '://' ) ) {
					continue;
				}

				$file = $asset['uri'];
			}

			$data = bp_parse_args(
				$script,
				array(
					'dependencies' => array(),
					'version'      => $this->version,
					'footer'       => false,
				),
				'nouveau_register_scripts'
			);

			wp_register_script( $handle, $file, $data['dependencies'], $data['version'], $data['footer'] );
		}
	}

	/**
	 * Enqueue the required JavaScript files
	 *
	 * @since 3.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'bp-nouveau' );

		if ( bp_is_register_page() || bp_is_user_settings_general() ) {
			wp_enqueue_script( 'user-profile' );
		}

		if ( is_singular() && bp_is_blog_page() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		/**
		 * Fires after all of the BuddyPress Nouveau scripts have been enqueued.
		 *
		 * @since 3.0.0
		 */
		do_action( 'bp_nouveau_enqueue_scripts' );
	}

	/**
	 * Adds the no-js class to the body tag.
	 *
	 * This function ensures that the <body> element will have the 'no-js' class by default. If you're
	 * using JavaScript for some visual functionality in your theme, and you want to provide noscript
	 * support, apply those styles to body.no-js.
	 *
	 * The no-js class is removed by the JavaScript created in buddypress.js.
	 *
	 * @since 3.0.0
	 *
	 * @param array $classes Array of classes to append to body tag.
	 *
	 * @return array $classes
	 */
	public function add_nojs_body_class( $classes ) {
		$classes[] = 'no-js';
		return array_unique( $classes );
	}

	/**
	 * Load localizations for topic script.
	 *
	 * These localizations require information that may not be loaded even by init.
	 *
	 * @since 3.0.0
	 */
	public function localize_scripts() {
		$params = array(
			'ajaxurl'             => bp_core_ajax_url(),
			'confirm'             => __( 'Are you sure?', 'buddypress' ),

			/* translators: %s: number of activity comments */
			'show_x_comments'     => __( 'Show all %d comments', 'buddypress' ),
			'unsaved_changes'     => __( 'Your profile has unsaved changes. If you leave the page, the changes will be lost.', 'buddypress' ),
			'object_nav_parent'   => '#buddypress',
		);

		// If the Object/Item nav are in the sidebar.
		if ( bp_nouveau_is_object_nav_in_sidebar() ) {
			$params['object_nav_parent'] = '.buddypress_object_nav';
		}

		/**
		 * Filters the supported BuddyPress Nouveau components.
		 *
		 * @since 3.0.0
		 *
		 * @param array $value Array of supported components.
		 */
		$supported_objects = (array) apply_filters( 'bp_nouveau_supported_components', bp_core_get_packaged_component_ids() );
		$object_nonces     = array();

		foreach ( $supported_objects as $key_object => $object ) {
			if ( ! bp_is_active( $object ) || 'forums' === $object ) {
				unset( $supported_objects[ $key_object ] );
				continue;
			}

			$object_nonces[ $object ] = wp_create_nonce( 'bp_nouveau_' . $object );
		}

		// Groups require some additional objects.
		if ( bp_is_active( 'groups' ) ) {
			$supported_objects = array_merge( $supported_objects, array( 'group_members', 'group_requests' ) );
		}

		// Add components & nonces.
		$params['objects'] = $supported_objects;
		$params['nonces']  = $object_nonces;

		// Used to transport the settings inside the Ajax requests.
		if ( is_customize_preview() ) {
			$params['customizer_settings'] = bp_nouveau_get_temporary_setting( 'any' );
		}

		$required_password_strength = bp_members_user_pass_required_strength();
		if ( $required_password_strength ) {
			$params['bpPasswordVerify'] = array(
				'tooWeakPasswordWarning' => __( 'Your password is too weak, please use a stronger password.', 'buddypress' ),
				'requiredPassStrength'   => bp_members_user_pass_required_strength(),
			);
		}

		/**
		 * Filters core JavaScript strings for internationalization before AJAX usage.
		 *
		 * @since 3.0.0
		 *
		 * @param array $params Array of key/value pairs for AJAX usage.
		 */
		wp_localize_script( 'bp-nouveau', 'BP_Nouveau', apply_filters( 'bp_core_get_js_strings', $params ) );
	}

	/**
	 * Filter the default theme compatibility root template hierarchy, and prepend
	 * a page template to the front if it's set.
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/6065
	 *
	 * @since 3.0.0
	 *
	 * @param array $templates Array of templates.
	 *
	 * @return array
	 */
	public function theme_compat_page_templates( $templates = array() ) {
		/**
		 * Filters whether or not we are looking at a directory to determine if to return early.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $value Whether or not we are viewing a directory.
		 */
		if ( true === (bool) apply_filters( 'bp_nouveau_theme_compat_page_templates_directory_only', ! bp_is_directory() ) ) {
			return $templates;
		}

		// No page ID yet.
		$page_id = 0;

		// Get the WordPress Page ID for the current view.
		foreach ( (array) buddypress()->pages as $component => $bp_page ) {

			// Handles the majority of components.
			if ( bp_is_current_component( $component ) ) {
				$page_id = (int) $bp_page->id;
			}

			// Stop if not on a user page.
			if ( ! bp_is_user() && ! empty( $page_id ) ) {
				break;
			}

			// The Members component requires an explicit check due to overlapping components.
			if ( bp_is_user() && ( 'members' === $component ) ) {
				$page_id = (int) $bp_page->id;
				break;
			}
		}

		// Bail if no directory page set.
		if ( 0 === $page_id ) {
			return $templates;
		}

		// Check for page template.
		$page_template = get_page_template_slug( $page_id );

		// Add it to the beginning of the templates array so it takes precedence over the default hierarchy.
		if ( ! empty( $page_template ) ) {

			/**
			 * Check for existence of template before adding it to template
			 * stack to avoid accidentally including an unintended file.
			 *
			 * @see https://buddypress.trac.wordpress.org/ticket/6190
			 */
			if ( '' !== locate_template( $page_template ) ) {
				array_unshift( $templates, $page_template );
			}
		}

		return $templates;
	}

	/**
	 * Add our special 'buddypress' div wrapper to the theme compat template part.
	 *
	 * @since 3.0.0
	 *
	 * @see bp_buffer_template_part()
	 *
	 * @param string $retval Current template part contents.
	 *
	 * @return string
	 */
	public function theme_compat_wrapper( $retval ) {
		if ( false !== strpos( $retval, '<div id="buddypress"' ) ) {
			return $retval;
		}

		// Add our 'buddypress' div wrapper.
		return sprintf(
			'<div id="buddypress" class="%1$s">%2$s</div><!-- #buddypress -->%3$s',
			esc_attr( bp_nouveau_get_container_classes() ),
			$retval,  // Constructed HTML.
			"\n"
		);
	}

	/**
	 * Define the directory nav items
	 *
	 * @since 3.0.0
	 */
	public function setup_directory_nav() {
		$nav_items = array();

		if ( bp_is_members_directory() ) {
			$nav_items = bp_nouveau_get_members_directory_nav_items();
		} elseif ( bp_is_activity_directory() ) {
			$nav_items = bp_nouveau_get_activity_directory_nav_items();
		} elseif ( bp_is_groups_directory() ) {
			$nav_items = bp_nouveau_get_groups_directory_nav_items();
		} elseif ( bp_is_blogs_directory() ) {
			$nav_items = bp_nouveau_get_blogs_directory_nav_items();
		}

		if ( empty( $nav_items ) ) {
			return;
		}

		foreach ( $nav_items as $nav_item ) {
			if ( empty( $nav_item['component'] ) || $nav_item['component'] !== bp_current_component() ) {
				continue;
			}

			// Define the primary nav for the current component's directory.
			$this->directory_nav->add_nav( $nav_item );
		}
	}

	/**
	 * We'll handle template notices from BP Nouveau.
	 *
	 * @since 3.0.0
	 */
	public function neutralize_core_template_notices() {
		remove_action( 'template_notices', 'bp_core_render_message' );
	}

	/**
	 * Set the BP Uri for the customizer in case of Ajax requests.
	 *
	 * @since 3.0.0
	 * @deprecated 12.0.0
	 *
	 * @param  string $path The BP Uri.
	 * @return string       The BP Uri.
	 */
	public function customizer_set_uri( $path ) {
		_deprecated_function( __METHOD__, '12.0.0' );

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $path;
		}

		$uri = parse_url( $path );

		if ( false === strpos( $uri['path'], 'customize.php' ) ) {
			return $path;
		} else {
			$vars = bp_parse_args(
				$uri['query'],
				array(),
				'customizer_set_uri'
			);

			if ( ! empty( $vars['url'] ) ) {
				$path = str_replace( get_site_url(), '', urldecode( $vars['url'] ) );
			}
		}

		return $path;
	}

	/**
	 * Modify "registration disabled" message in Nouveau template pack.
	 * Modify welcome message in Nouveau template pack.
	 *
	 * @since 8.0.0
	 *
	 * @param array $messages The list of feedback messages.
	 *
	 * @return array $messages
	 */
	public function filter_registration_messages( $messages ) {
		// Change the "registration is disabled" message.
		$disallowed_message = bp_members_invitations_get_modified_registration_disabled_message();
		if ( $disallowed_message ) {
			$messages['registration-disabled']['message'] = $disallowed_message;
		}

		// Add information about invitations to the welcome block.
		$welcome_message = bp_members_invitations_get_registration_welcome_message();
		if ( $welcome_message ) {
			$messages['request-details']['message'] = $welcome_message . ' ' . $messages['request-details']['message'];
		}

		return $messages;
	}
}

/**
 * Get a unique instance of BP Nouveau
 *
 * @since 3.0.0
 *
 * @return BP_Nouveau the main instance of the class
 */
function bp_nouveau() {
	return BP_Nouveau::get_instance();
}

/**
 * Launch BP Nouveau!
 */
bp_nouveau();
