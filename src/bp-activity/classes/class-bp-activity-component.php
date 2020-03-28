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
			'cache'
		);

		// Notifications support.
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}

		// Load Akismet support if Akismet is configured.
		$akismet_key = bp_get_option( 'wordpress_api_key' );

		/** This filter is documented in bp-activity/bp-activity-akismet.php */
		if ( defined( 'AKISMET_VERSION' ) && class_exists( 'Akismet' ) && ( ! empty( $akismet_key ) || defined( 'WPCOM_API_KEY' ) ) && apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) ) {
			$includes[] = 'akismet';
		}

		// Embeds.
		if ( bp_is_active( $this->id, 'embeds' ) ) {
			$includes[] = 'embeds';
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
				require $this->path . 'bp-activity/actions/' . bp_current_action() . '.php';
			}

			// RSS feeds.
			if ( bp_is_current_action( 'feed' ) || bp_is_action_variable( 'feed', 0 ) ) {
				require $this->path . 'bp-activity/actions/feeds.php';
			}

			// Screens - Directory.
			if ( bp_is_activity_directory() ) {
				require $this->path . 'bp-activity/screens/directory.php';
			}

			// Screens - User main nav.
			if ( bp_is_user() ) {
				require $this->path . 'bp-activity/screens/just-me.php';
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
				require $this->path . 'bp-activity/screens/' . $filenames[ $slug ] . '.php';
			}

			// Screens - Single permalink.
			if ( bp_is_current_action( 'p' ) || is_numeric( bp_current_action() ) ) {
				require $this->path . 'bp-activity/screens/permalink.php';
			}

			// Theme compatibility.
			new BP_Activity_Theme_Compat();
		}
	}

	/**
	 * Set up component global variables.
	 *
	 * The BP_ACTIVITY_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_ACTIVITY_SLUG' ) ) {
			define( 'BP_ACTIVITY_SLUG', $this->id );
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
			'slug'                  => BP_ACTIVITY_SLUG,
			'root_slug'             => isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : BP_ACTIVITY_SLUG,
			'has_directory'         => true,
			'directory_title'       => isset( $bp->pages->activity->title ) ? $bp->pages->activity->title : $default_directory_title,
			'notification_callback' => 'bp_activity_format_notifications',
			'search_string'         => __( 'Search Activity...', 'buddypress' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Stop if there is no user displayed or logged in.
		if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
			return;
		}

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$slug          = bp_get_activity_slug();
		$activity_link = trailingslashit( $user_domain . $slug );

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
			'parent_url'      => $activity_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		);

		// Check @mentions.
		if ( bp_activity_do_mentions() ) {
			$sub_nav[] = array(
				'name'            => _x( 'Mentions', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => 'mentions',
				'parent_url'      => $activity_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_mentions',
				'position'        => 20,
				'item_css_id'     => 'activity-mentions'
			);
		}

		// Favorite activity items.
		if ( bp_activity_can_favorite() ) {
			$sub_nav[] = array(
				'name'            => _x( 'Favorites', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => 'favorites',
				'parent_url'      => $activity_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_favorites',
				'position'        => 30,
				'item_css_id'     => 'activity-favs'
			);
		}

		// Additional menu if friends is active.
		if ( bp_is_active( 'friends' ) ) {
			$sub_nav[] = array(
				'name'            => _x( 'Friends', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => bp_get_friends_slug(),
				'parent_url'      => $activity_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 40,
				'item_css_id'     => 'activity-friends'
			) ;
		}

		// Additional menu if groups is active.
		if ( bp_is_active( 'groups' ) ) {
			$sub_nav[] = array(
				'name'            => _x( 'Groups', 'Profile activity screen sub nav', 'buddypress' ),
				'slug'            => bp_get_groups_slug(),
				'parent_url'      => $activity_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 50,
				'item_css_id'     => 'activity-groups'
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
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
			$activity_link = trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() );

			// Unread message count.
			if ( bp_activity_do_mentions() ) {
				$count = bp_get_total_mention_count_for_user( bp_loggedin_user_id() );
				if ( !empty( $count ) ) {
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
				'href'   => $activity_link
			);

			// Personal.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-personal',
				'title'    => _x( 'Personal', 'My Account Activity sub nav', 'buddypress' ),
				'href'     => $activity_link,
				'position' => 10
			);

			// Mentions.
			if ( bp_activity_do_mentions() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-mentions',
					'title'    => $title,
					'href'     => trailingslashit( $activity_link . 'mentions' ),
					'position' => 20
				);
			}

			// Favorite activity items.
			if ( bp_activity_can_favorite() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-favorites',
					'title'    => _x( 'Favorites', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => trailingslashit( $activity_link . 'favorites' ),
					'position' => 30
				);
			}

			// Friends?
			if ( bp_is_active( 'friends' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-friends',
					'title'    => _x( 'Friends', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => trailingslashit( $activity_link . bp_get_friends_slug() ),
					'position' => 40
				);
			}

			// Groups?
			if ( bp_is_active( 'groups' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-groups',
					'title'    => _x( 'Groups', 'My Account Activity sub nav', 'buddypress' ),
					'href'     => trailingslashit( $activity_link . bp_get_groups_slug() ),
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
}
