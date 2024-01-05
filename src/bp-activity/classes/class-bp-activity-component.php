<?php
/**
 * BuddyPress Activity Streams Loader.
 *
 * An activity stream component, for users, groups, and site tracking.
 *
 * @package BuddyPress
 * @subpackage ActivityCore
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Activity Class.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Activity_Component extends BP_Component {

	/**
	 * Start the activity component setup process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'activity',
			__( 'Activity Streams', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 10,
				'search_query_arg' => 'activity_search',
				'features' => array( 'embeds' )
			)
		);
	}

	/**
	 * Include component files.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'cssjs',
			'filters',
			'adminbar',
			'template',
			'functions',
			'cache',
			'blocks',
		);

		// Notifications support.
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}

		// Load Akismet support if Akismet is configured.
		if ( defined( 'AKISMET_VERSION' ) && class_exists( 'Akismet' ) ) {
			$akismet_key = bp_get_option( 'wordpress_api_key' );

			/** This filter is documented in bp-activity/bp-activity-akismet.php */
			if ( ( ! empty( $akismet_key ) || defined( 'WPCOM_API_KEY' ) ) && apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) ) {
				$includes[] = 'akismet';
			}
		}

		// Embeds.
		if ( bp_is_active( $this->id, 'embeds' ) ) {
			$includes[] = 'embeds';
		}

		/*
		 * Activity blocks feature.
		 *
		 * By default this feature is inactive. We're including specific block functions
		 * in version 11.0.0 so these can be used by BuddyPress Add-ons such as BP Attachments
		 * or BP Activity Block Editor.
		 */
		if ( bp_is_active( $this->id, 'blocks' ) ) {
			$includes[] = 'block-functions';
		}

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

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

		/*
		 * Load activity action and screen code if PHPUnit isn't running.
		 *
		 * For PHPUnit, we load these files in tests/phpunit/includes/install.php.
		 */
		if ( bp_is_current_component( 'activity' ) ) {
			// Authenticated actions - Only fires when JS is disabled.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'delete', 'spam', 'post', 'reply', 'favorite', 'unfavorite' ), true )
			) {
				require_once $this->path . 'bp-activity/actions/' . bp_current_action() . '.php';
			}

			// RSS feeds.
			if ( bp_is_current_action( 'feed' ) || bp_is_action_variable( 'feed', 0 ) ) {
				require_once $this->path . 'bp-activity/actions/feeds.php';
			}

			// Screens - Directory.
			if ( bp_is_activity_directory() ) {
				require_once $this->path . 'bp-activity/screens/directory.php';
			}

			// Screens - User main nav.
			if ( bp_is_user() ) {
				require_once $this->path . 'bp-activity/screens/just-me.php';
			}

			/**
			 * Screens - User secondary nav.
			 *
			 * For these specific actions, slugs can be customized using `BP_{COMPONENT}_SLUGS`.
			 * As a result, we need to map filenames with slugs.
			 */
			$filenames = array(
				'favorites' => 'favorites',
				'mentions'  => 'mentions',
			);

			if ( bp_is_active( 'friends' ) ) {
				$filenames[bp_get_friends_slug()] = 'friends';
			}

			if ( bp_is_active( 'groups' ) ) {
				$filenames[bp_get_groups_slug()] = 'groups';
			}

			// The slug is the current action requested.
			$slug = bp_current_action();

			if ( bp_is_user() && isset( $filenames[ $slug ] ) ) {
				require_once $this->path . 'bp-activity/screens/' . $filenames[ $slug ] . '.php';
			}

			// Screens - Single permalink.
			if ( bp_is_current_action( 'p' ) || is_numeric( bp_current_action() ) ) {
				require_once $this->path . 'bp-activity/screens/permalink.php';
			}

			// Theme compatibility.
			new BP_Activity_Theme_Compat();
		}
	}

	/**
	 * Set up component global variables.
	 *
	 * The BP_ACTIVITY_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp           = buddypress();
		$default_slug = $this->id;

		// @deprecated.
		if ( defined( 'BP_ACTIVITY_SLUG' ) ) {
			_doing_it_wrong( 'BP_ACTIVITY_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_ACTIVITY_SLUG;
		}

		// Global tables for activity component.
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_activity',
			'table_name_meta' => $bp->table_prefix . 'bp_activity_meta',
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'activity' => $bp->table_prefix . 'bp_activity_meta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// All globals for activity component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => $default_slug,
			'root_slug'             => isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : $default_slug,
			'has_directory'         => true,
			'rewrite_ids'           => array(
				'directory'                    => 'activities',
				'single_item_action'           => 'activity_action',
				'single_item_action_variables' => 'activity_action_variables',
			),
			'directory_title'       => isset( $bp->pages->activity->title ) ? $bp->pages->activity->title : $default_directory_title,
			'notification_callback' => 'bp_activity_format_notifications',
			'search_string'         => __( 'Search Activity...', 'buddypress' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
			'block_globals'         => array(
				'bp/latest-activities' => array(
					'widget_classnames' => array( 'wp-block-bp-latest-activities', 'buddypress' ),
				)
			),
		);

		parent::setup_globals( $args );
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		$slug = bp_get_activity_slug();

		// Add 'Activity' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Activity', 'Profile activity screen nav', 'buddypress' ),
			'slug'                => $slug,
			'position'            => 10,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $this->id
		);

		// Add the subnav items to the activity nav item if we are using a theme that supports this.
		$sub_nav[] = array(
			'name'            => _x( 'Personal', 'Profile activity screen sub nav', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		);

		// Check @mentions.
		$sub_nav[] = array(
			'name'            => _x( 'Mentions', 'Profile activity screen sub nav', 'buddypress' ),
			'slug'            => 'mentions',
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_mentions',
			'position'        => 20,
			'item_css_id'     => 'activity-mentions',
			'generate'        => bp_activity_do_mentions(),
		);

		// Favorite activity items.
		$sub_nav[] = array(
			'name'            => _x( 'Favorites', 'Profile activity screen sub nav', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_favorites',
			'position'        => 30,
			'item_css_id'     => 'activity-favs',
			'generate'        => bp_activity_can_favorite(),
		);

		// Additional menu if friends is active.
		if ( bp_is_active( 'friends' ) ) {
			$sub_nav[] = array(
				'name'            => _x( 'Friends', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => bp_get_friends_slug(),
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 40,
				'item_css_id'     => 'activity-friends',
			);
		}

		// Additional menu if groups is active.
		if ( bp_is_active( 'groups' ) ) {
			$sub_nav[] = array(
				'name'            => _x( 'Groups', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => bp_get_groups_slug(),
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 50,
				'item_css_id'     => 'activity-groups'
			);
		}

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$activity_slug = bp_get_activity_slug();

			// Unread message count.
			if ( bp_activity_do_mentions() ) {
				$count = bp_get_total_mention_count_for_user( bp_loggedin_user_id() );
				if ( ! empty( $count ) ) {
					$title = sprintf(
						/* translators: %s: Unread mention count for the current user */
						_x( 'Mentions %s', 'Toolbar Mention logged in user', 'buddypress' ),
						'<span class="count">' . bp_core_number_format( $count ) . '</span>'
					);
				} else {
					$title = _x( 'Mentions', 'Toolbar Mention logged in user', 'buddypress' );
				}
			}

			// Add the "Activity" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => _x( 'Activity', 'My Account Activity sub nav', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug ) ) ),
			);

			// Personal.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-personal',
				'title'    => _x( 'Personal', 'My Account Activity sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug, 'just-me' ) ) ),
				'position' => 10,
			);

			// Mentions.
			if ( bp_activity_do_mentions() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-mentions',
					'title'    => $title,
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug, 'mentions' ) ) ),
					'position' => 20,
				);
			}

			// Favorite activity items.
			if ( bp_activity_can_favorite() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-favorites',
					'title'    => _x( 'Favorites', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug, 'favorites' ) ) ),
					'position' => 30,
				);
			}

			// Friends?
			if ( bp_is_active( 'friends' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-friends',
					'title'    => _x( 'Friends', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug, bp_get_friends_slug() ) ) ),
					'position' => 40,
				);
			}

			// Groups?
			if ( bp_is_active( 'groups' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-groups',
					'title'    => _x( 'Groups', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $activity_slug, bp_get_groups_slug() ) ) ),
					'position' => 50
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 *
	 */
	public function setup_title() {

		// Adjust title based on view.
		if ( bp_is_activity_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = _x( 'My Activity', 'Page and <title>', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'	  => sprintf(
						/* translators: %s: member name */
						__( 'Profile picture of %s', 'buddypress' ),
						bp_get_displayed_user_fullname()
					),
				) );
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_activity',
			'bp_activity_comments',
			'activity_meta'
		) );

		parent::setup_cache_groups();
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
		 * If BP Rewrites are not in use, no need to parse BP URI globals another time.
		 * Legacy Parser should have already set these.
		 */
		if ( 'rewrites' !== bp_core_get_query_parser() ) {
			return parent::parse_query( $query );
		}

		if ( bp_is_site_home() && bp_is_directory_homepage( $this->id ) ) {
			$query->set( $this->rewrite_ids['directory'], 1 );
		}

		if ( 1 === (int) $query->get( $this->rewrite_ids['directory'] ) ) {
			$bp = buddypress();

			// Set the Activity component as current.
			$bp->current_component = 'activity';

			$current_action = $query->get( $this->rewrite_ids['single_item_action'] );
			if ( $current_action ) {
				$bp->current_action = $current_action;
			}

			$action_variables = $query->get( $this->rewrite_ids['single_item_action_variables'] );
			if ( $action_variables ) {
				if ( ! is_array( $action_variables ) ) {
					$bp->action_variables = explode( '/', ltrim( $action_variables, '/' ) );
				} else {
					$bp->action_variables = $action_variables;
				}
			}

			// Set the BuddyPress queried object.
			if ( isset( $bp->pages->activity->id ) ) {
				$query->queried_object    = get_post( $bp->pages->activity->id );
				$query->queried_object_id = $query->queried_object->ID;
			}
		}

		parent::parse_query( $query );
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array( 'BP_REST_Activity_Endpoint' ) );
	}

	/**
	 * Register the BP Activity Blocks.
	 *
	 * @since 7.0.0
	 * @since 12.0.0 Use the WP Blocks API v2.
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		$blocks = array(
			'bp/latest-activities' => array(
				'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-activity/blocks/latest-activities',
				'render_callback' => 'bp_activity_render_latest_activities_block',
			),
		);

		if ( bp_is_active( $this->id, 'embeds' ) ) {
			$blocks['bp/embed-activity'] = array(
				'metadata' => trailingslashit( buddypress()->plugin_dir ) . 'bp-activity/blocks/embed-activity',

			);
		}

		parent::blocks_init( $blocks );
	}
}
