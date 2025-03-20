<?php
/**
 * BuddyPress Blogs Loader
 *
 * The blogs component tracks posts and comments to member activity streams,
 * shows blogs the member can post to in their profiles, and caches useful
 * information from those blogs to make querying blogs in bulk more performant.
 *
 * @package BuddyPress
 * @subpackage BlogsCore
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Blogs component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Blogs_Component extends BP_Component {

	/**
	 * Start the blogs component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'blogs',
			'Site Directory',
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30,
				'search_query_arg' => 'sites_search',
				'features' => array( 'site-icon' )
			)
		);
	}

	/**
	 * Set up global settings for the blogs component.
	 *
	 * The BP_BLOGS_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp           = buddypress();
		$default_slug = $this->id;

		// @deprecated.
		if ( defined( 'BP_BLOGS_SLUG' ) ) {
			_doing_it_wrong( 'BP_BLOGS_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_BLOGS_SLUG;
		}

		// Global tables for messaging component.
		$global_tables = array(
			'table_name'          => $bp->table_prefix . 'bp_user_blogs',
			'table_name_blogmeta' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		$meta_tables = array(
			'bp_blog' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// All globals for blogs component.
		$args = array(
			'slug'                  => $default_slug,
			'root_slug'             => isset( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : $default_slug,
			'has_directory'         => is_multisite(), // Non-multisite installs don't need a top-level Sites directory, since there's only one site.
			'rewrite_ids'           => array(
				'directory'                  => 'blogs',
				'create_single_item'         => 'blog_create',
				'directory_action'           => 'blogs_action',
				'directory_action_variables' => 'blogs_action_variables'
			),
			'directory_title'       => isset( $bp->pages->blogs->title ) ? $bp->pages->blogs->title : $default_directory_title,
			'notification_callback' => 'bp_blogs_format_notifications',
			'search_string'         => __( 'Search sites...', 'buddypress' ),
			'autocomplete_all'      => defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
			'block_globals'         => array(
				'bp/recent-posts' => array(
					'widget_classnames' => array( 'widget_bp_blogs_widget', 'buddypress' ),
				),
			),
		);

		// Setup the globals.
		parent::setup_globals( $args );

		/**
		 * Filters if a blog is public.
		 *
		 * In case the config is not multisite, the blog_public option is ignored.
		 *
		 * @since 2.3.0
		 *
		 * @param int $value Whether or not the blog is public.
		 */
		if ( 0 !== apply_filters( 'bp_is_blog_public', (int) get_option( 'blog_public' ) ) || ! is_multisite() ) {

			/**
			 * Filters the post types to track for the Blogs component.
			 *
			 * @since 1.5.0
			 * @deprecated 2.3.0
			 *
			 * @param array $value Array of post types to track.
			 */
			$post_types = apply_filters( 'bp_blogs_record_post_post_types', array( 'post' ) );

			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, 'buddypress-activity' );
			}
		}
	}

	/**
	 * Include bp-blogs files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'cache',
			'template',
			'filters',
			'functions',
		);

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';

			if ( is_multisite() ) {
				$includes[] = 'blocks';
			}
		}

		// Include the files.
		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Bail if not on a blogs page or not multisite.
		if ( ! bp_is_blogs_component() || ! is_multisite() ) {
			return;
		}

		// Actions.
		if ( isset( $_GET['random-blog'] ) ) {
			require_once $this->path . 'bp-blogs/actions/random.php';
		}

		// Screens.
		if ( bp_is_user() ) {
			require_once $this->path . 'bp-blogs/screens/my-blogs.php';
		} else {
			if ( bp_is_blogs_directory() ) {
				require_once $this->path . 'bp-blogs/screens/directory.php';
			}

			if ( is_user_logged_in() && bp_is_current_action( 'create' ) ) {
				require_once $this->path . 'bp-blogs/screens/create.php';
			}

			// Theme compatibility.
			new BP_Blogs_Theme_Compat();
		}
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity stream.
		 */
		if ( ! is_multisite() ) {
			return false;
		}

		$slug = bp_get_blogs_slug();

		$main_nav = array(
			'name'                => __( 'Sites', 'buddypress' ),
			'slug'                => $slug,
			'position'            => 30,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'my-sites',
			'item_css_id'         => $this->id
		);

		$sub_nav[] = array(
			'name'            => __( 'My Sites', 'buddypress' ),
			'slug'            => 'my-sites',
			'parent_slug'     => $slug,
			'screen_function' => 'bp_blogs_screen_my_blogs',
			'position'        => 10
		);

		// Setup navigation.
		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.5.0
	 * @since 12.0.0 Used to customize the main navigation name.
	 *
	 * @see `BP_Component::setup_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Only grab count if we're on a user page.
		if ( is_multisite() && bp_is_user() && isset( $this->main_nav['name'] ) ) {
			// Add the number of sites to the main nav.
			$count                  = (int) bp_get_total_blog_count_for_user();
			$class                  = ( 0 === $count ) ? 'no-count' : 'count';
			$this->main_nav['name'] = sprintf(
				/* translators: %s: Site count for the displayed user */
				__( 'Sites %s', 'buddypress' ),
				sprintf(
					'<span class="%s">%s</span>',
					esc_attr( $class ),
					bp_core_number_format( $count )
				)
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-blogs integration with the WordPress admin bar.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *                            for description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		/**
		 * Site/post/comment menus should not appear on single WordPress setups.
		 *
		 * Comments and posts made by users will still show in their activity.
		 */
		if ( ! is_multisite() ) {
			return;
		}

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$blogs_slug = bp_get_blogs_slug();

			// Add the "Sites" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Sites', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $blogs_slug ) ) ),
			);

			// My Sites.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-sites',
				'title'    => __( 'My Sites', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $blogs_slug, 'my-sites' ) ) ),
				'position' => 10,
			);

			// Create a Site.
			if ( bp_blog_signup_enabled() ) {
				$url = bp_get_blogs_directory_url(
					array(
						'create_single_item' => 1,
					)
				);

				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-create',
					'title'    => __( 'Create a Site', 'buddypress' ),
					'href'     => $url,
					'position' => 99
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 */
	public function setup_title() {

		// Set up the component options navigation for Site.
		if ( bp_is_blogs_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				if ( bp_is_active( 'xprofile' ) ) {
					$bp->bp_options_title = __( 'My Sites', 'buddypress' );
				}

			// If we are not viewing the logged in user, set up the current
			// users avatar and name.
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf(
						/* translators: %s: member name */
						__( 'Profile picture of %s', 'buddypress' ),
						bp_get_displayed_user_fullname()
					),
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_blog_meta'
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Add the Blog Create rewrite tags.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_tags Optional. See BP_Component::add_rewrite_tags() for
	 *                            description.
	 */
	public function add_rewrite_tags( $rewrite_tags = array() ) {
		$rewrite_tags = array(
			'create_single_item'         => '([1]{1,})',
			'directory_action'           => '([^/]+)',
			'directory_action_variables' => '(.+?)',
		);

		parent::add_rewrite_tags( $rewrite_tags );
	}

	/**
	 * Add the Registration and Activation rewrite rules.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_rules Optional. See BP_Component::add_rewrite_rules() for
	 *                             description.
	 */
	public function add_rewrite_rules( $rewrite_rules = array() ) {
		$create_slug = bp_rewrites_get_slug( 'blogs', 'blog_create', 'create' );

		$rewrite_rules = array(
			'directory_action_variables' => array(
				'regex' => $this->root_slug . '/([^/]+)/(.+?)/?$',
				'order' => 70,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['directory_action'] . '=$matches[1]&' . $this->rewrite_ids['directory_action_variables'] . '=$matches[2]',
			),
			'directory_action'           => array(
				'regex' => $this->root_slug . '/([^/]+)/?$',
				'order' => 60,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['directory_action'] . '=$matches[1]',
			),
			'create_single_item' => array(
				'regex' => $this->root_slug . '/' . $create_slug . '/?$',
				'order' => 50,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['create_single_item'] . '=1',
			),
		);

		parent::add_rewrite_rules( $rewrite_rules );
	}

	/**
	 * Parse the WP_Query and eventually display the component's directory or single item.
	 *
	 * @since 12.0.0
	 *
	 * @param WP_Query $query Required. See BP_Component::parse_query() for
	 *                        description.
	 */
	public function parse_query( $query ) {
		/*
		 * Only Multisite configs have a Sites directory.
		 * If BP Rewrites are not in use, no need to parse BP URI globals another time.
		 * Legacy Parser should have already set these.
		 */
		if ( ! is_multisite() || 'rewrites' !== bp_core_get_query_parser() ) {
			return parent::parse_query( $query );
		}

		// Get the BuddyPress main instance.
		$bp = buddypress();

		if ( home_url( '/' ) === bp_get_requested_url() && bp_is_directory_homepage( $this->id ) ) {
			$query->set( $this->rewrite_ids['directory'], 1 );
		}

		if ( 1 === (int) $query->get( $this->rewrite_ids['directory'] ) ) {
			$bp->current_component = 'blogs';
			$is_blog_create        = 1 === (int) $query->get( $this->rewrite_ids['create_single_item'] );

			if ( $is_blog_create ) {
				$bp->current_action = 'create';
			} else {
				$current_action = $query->get( $this->rewrite_ids['directory_action'] );
				if ( $current_action ) {
					$bp->current_action = $current_action;
				}

				$action_variables = $query->get( $this->rewrite_ids['directory_action_variables'] );
				if ( $action_variables ) {
					if ( ! is_array( $action_variables ) ) {
						$bp->action_variables = explode( '/', ltrim( $action_variables, '/' ) );
					} else {
						$bp->action_variables = $action_variables;
					}
				}
			}

			// Set the BuddyPress queried object.
			if ( isset( $bp->pages->blogs->id ) && ( ! bp_current_action() || bp_is_current_action( 'create' ) ) ) {
				$query->queried_object    = get_post( $bp->pages->blogs->id );
				$query->queried_object_id = $query->queried_object->ID;
			}
		}

		parent::parse_query( $query );
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 6.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( is_multisite() ) {
			$controllers = array(
				'BP_REST_Blogs_Endpoint',
			);

			// Support to Blog Avatar.
			if ( bp_is_active( 'blogs', 'site-icon' ) ) {
				$controllers[] = 'BP_REST_Attachments_Blog_Avatar_Endpoint';
			}
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the BP Blogs Blocks.
	 *
	 * @since 9.0.0
	 * @since 12.0.0 Use the WP Blocks API v2.
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		$blocks = array();

		if ( is_multisite() && bp_is_active( 'activity' ) ) {
			$blocks['bp/recent-posts'] = array(
				'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-blogs/blocks/recent-posts',
				'render_callback' => 'bp_blogs_render_recent_posts_block',
			);
		}

		parent::blocks_init( $blocks );
	}
}
