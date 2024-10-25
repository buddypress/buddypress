<?php
/**
 * Component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Component' ) ) {
	return;
}

/**
 * BuddyPress Component Class.
 *
 * The BuddyPress component class is responsible for simplifying the creation
 * of components that share similar behaviors and routines. It is used
 * internally by BuddyPress to create the bundled components, but can be
 * extended to create other really neat things.
 *
 * @since 1.5.0
 */
class BP_Component {

	/** Variables *************************************************************/

	/**
	 * Raw name for the component.
	 *
	 * Do not use translatable strings here as this part is set before WP's `init` hook.
	 *
	 * @since 1.5.0
	 * @since 14.3.0 Changed the variable inline documentation summary and added a description.
	 *
	 * @internal
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Unique ID for the component.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Unique slug for the component, for use in query strings and URLs.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Does the component need a top-level directory?
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $has_directory = false;

	/**
	 * Directory's permalink structure for the component.
	 *
	 * @since 12.0.0
	 *
	 * @var string
	 */
	public $directory_permastruct = '';

	/**
	 * List of available rewrite IDs for the component.
	 *
	 * @since 12.0.0
	 *
	 * @var array
	 */
	public $rewrite_ids = array();

	/**
	 * The path to the component's files.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * The WP_Query loop for this component.
	 *
	 * @since 1.5.0
	 *
	 * @var WP_Query
	 */
	public $query = false;

	/**
	 * The current ID of the queried object.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $current_id = '';

	/**
	 * Callback for formatting notifications.
	 *
	 * @since 1.5.0
	 *
	 * @var callable
	 */
	public $notification_callback = '';

	/**
	 * WordPress Toolbar links.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $admin_menu = '';

	/**
	 * Placeholder text for component directory search box.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	public $search_string = '';

	/**
	 * Root slug for the component.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	public $root_slug = '';

	/**
	 * Metadata tables for the component (if applicable).
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $meta_tables = array();

	/**
	 * Global tables for the component (if applicable).
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $global_tables = array();

	/**
	 * Table name.
	 *
	 * @since 12.0.0
	 *
	 * @var string
	 */
	public $table_name = '';

	/**
	 * Query argument for component search URLs.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $search_query_arg = 's';

	/**
	 * An array of globalized data for BP Blocks.
	 *
	 * @since 9.0.0
	 *
	 * @var array
	 */
	public $block_globals = array();

	/**
	 * Menu position of the WP Toolbar's "My Account menu".
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	public $adminbar_myaccount_order = 90;

	/**
	 * An array of feature names.
	 *
	 * @since 1.5.0
	 *
	 * @var string[]
	 */
	public $features = array();

	/**
	 * Component's directory title.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $directory_title = '';

	/**
	 * Component's main nav items.
	 *
	 * @since 12.0.0
	 *
	 * @var array
	 */
	public $main_nav = array();

	/**
	 * Component's main nav sub items.
	 *
	 * @since 12.0.0
	 *
	 * @var array
	 */
	public $sub_nav = array();

	/** Methods ***************************************************************/

	/**
	 * Component loader.
	 *
	 * @since 1.5.0
	 * @since 1.9.0 Added $params as a parameter.
	 * @since 2.3.0 Added $params['features'] as a configurable value.
	 * @since 2.4.0 Added $params['search_query_arg'] as a configurable value.
	 * @since 14.3.0 Changed the `$name` parameter's description.
	 *
	 * @param string $id   Unique ID. Letters, numbers, and underscores only.
	 * @param string $name Unique raw name for the component (do not use translatable strings).
	 * @param string $path The file path for the component's files. Used by {@link BP_Component::includes()}.
	 * @param array  $params {
	 *     Additional parameters used by the component.
	 *     @type int    $adminbar_myaccount_order Set the position for our menu under the WP Toolbar's "My Account menu".
	 *     @type array  $features                 An array of feature names. This is used to load additional files from your
	 *                                            component directory and for feature active checks. eg. array( 'awesome' )
	 *                                            would look for a file called "bp-{$this->id}-awesome.php" and you could use
	 *                                            bp_is_active( $this->id, 'awesome' ) to determine if the feature is active.
	 *     @type string $search_query_arg         String to be used as the query argument in component search URLs.
	 * }
	 */
	public function start( $id = '', $name = '', $path = '', $params = array() ) {

		// Internal identifier of component.
		$this->id = $id;

		// Internal component name.
		$this->name = $name;

		// Path for includes.
		$this->path = $path;

		// Miscellaneous component parameters that need to be set early on.
		if ( ! empty( $params ) ) {
			// Sets the position for our menu under the WP Toolbar's "My Account" menu.
			if ( ! empty( $params['adminbar_myaccount_order'] ) ) {
				$this->adminbar_myaccount_order = (int) $params['adminbar_myaccount_order'];
			}

			// Register features.
			if ( ! empty( $params['features'] ) ) {
				$this->features = array_map( 'sanitize_title', (array) $params['features'] );
			}

			if ( ! empty( $params['search_query_arg'] ) ) {
				$this->search_query_arg = sanitize_title( $params['search_query_arg'] );
			}
		}

		// Make sure the `buddypress()->active_components` global lists all active components.
		if ( 'core' !== $this->id && ! isset( buddypress()->active_components[ $this->id ] ) ) {
			buddypress()->active_components[ $this->id ] = '1';
		}

		// Move on to the next step.
		$this->setup_actions();
	}

	/**
	 * Set up component global variables.
	 *
	 * @since 1.5.0
	 * @since 2.0.0 Adds the `$directory_title` argument to the `$args` parameter.
	 * @since 9.0.0 Adds the `$block_globals` argument to the `$args` parameter.
	 * @since 12.0.0 Adds the `$rewrite_ids` argument to the `$args` parameter.
	 *
	 * @param array $args {
	 *     All values are optional.
	 *     @type string   $slug                  The component slug. Used to construct certain URLs, such as 'friends' in
	 *                                           http://example.com/members/joe/friends/. Default: the value of $this->id.
	 *     @type string   $root_slug             The component root slug. Note that this value is generally unused if the
	 *                                           component has a root directory (the slug will be overridden by the
	 *                                           post_name of the directory page). Default: the slug of the directory page
	 *                                           if one is found, otherwise an empty string.
	 *     @type bool     $has_directory         Set to true if the component requires an associated WordPress page.
	 *     @type array    $rewrite_ids           The list of rewrited IDs to use for the component.
	 *     @type string   $directory_title       The title to use for the directory page.
	 *     @type callable $notification_callback The callable function that formats the component's notifications.
	 *     @type string   $search_string         The placeholder text for the directory search box. Eg: 'Search Groups...'.
	 *     @type array    $global_tables         An array of database table names.
	 *     @type array    $meta_tables           An array of metadata table names.
	 *     @type array    $block_globals         An array of globalized data for BP Blocks.
	 * }
	 */
	public function setup_globals( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'slug'                  => $this->id,
				'root_slug'             => '',
				'has_directory'         => false,
				'rewrite_ids'           => array(),
				'directory_title'       => '',
				'notification_callback' => '',
				'search_string'         => '',
				'global_tables'         => '',
				'meta_tables'           => '',
				'block_globals'         => array(),
			)
		);

		/** Slugs ************************************************************
		 */

		// For all Components except Core.
		if ( 'core' !== $this->id ) {
			/**
			 * If a WP directory page exists for the component, it should
			 * be the default value of 'root_slug'.
			 */
			if ( isset( buddypress()->pages->{$this->id}->slug ) ) {
				$r['root_slug'] = buddypress()->pages->{$this->id}->slug;
			}

			/**
			 * Filters the slug to be used for the permalink URI chunk after root.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value Slug to use in permalink URI chunk.
			 */
			$this->slug = apply_filters( 'bp_' . $this->id . '_slug', $r['slug'] );

			/**
			 * Filters the slug used for root directory.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value Root directory slug.
			 */
			$this->root_slug = apply_filters( 'bp_' . $this->id . '_root_slug', $r['root_slug'] );

			/**
			 * Filters the component's top-level directory if available.
			 *
			 * @since 1.5.0
			 *
			 * @param bool $value Whether or not there is a top-level directory.
			 */
			$this->has_directory = apply_filters( 'bp_' . $this->id . '_has_directory', $r['has_directory'] );

			$rewrite_ids = bp_parse_args(
				/**
				 * Filters the component's rewrite IDs if available.
				 *
				 * @since 12.0.0
				 *
				 * @param array $value The list of rewrite IDs for the component.
				 */
				(array) apply_filters( 'bp_' . $this->id . '_rewrite_ids', $r['rewrite_ids'] ),
				array_fill_keys( array_keys( bp_rewrites_get_default_url_chunks() ), '' )
			);

			if ( array_filter( $rewrite_ids ) ) {
				foreach ( $rewrite_ids as $rewrite_id_key => $rewrite_id_value ) {
					if ( ! $rewrite_id_value ) {
						continue;
					}

					$this->rewrite_ids[ sanitize_key( $rewrite_id_key ) ] = 'bp_' . str_replace( 'bp_', '', sanitize_key( $rewrite_id_value ) );
				}
			}

			// Set the component's directory permastruct early so that it's available to build links.
			if ( true === $this->has_directory && isset( $this->rewrite_ids['directory'] ) ) {
				$this->directory_permastruct = $this->root_slug . '/%' . $this->rewrite_ids['directory'] . '%';
			}

			/**
			 * Filters the component's directory title.
			 *
			 * @since 2.0.0
			 *
			 * @param string $value Title to use for the directory.
			 */
			$this->directory_title = apply_filters( 'bp_' . $this->id . '_directory_title', $r['directory_title'] );

			/**
			 * Filters the placeholder text for search inputs for component.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value Name to use in search input placeholders.
			 */
			$this->search_string = apply_filters( 'bp_' . $this->id . '_search_string', $r['search_string'] );

			/**
			 * Filters the callable function that formats the component's notifications.
			 *
			 * @since 1.5.0
			 *
			 * @param string $value Function callback.
			 */
			$this->notification_callback = apply_filters( 'bp_' . $this->id . '_notification_callback', $r['notification_callback'] );

			// Set the global table names, if applicable.
			if ( ! empty( $r['global_tables'] ) ) {
				$this->register_global_tables( $r['global_tables'] );
			}

			// Set the metadata table, if applicable.
			if ( ! empty( $r['meta_tables'] ) ) {
				$this->register_meta_tables( $r['meta_tables'] );
			}

			// Register this component in the loaded components array.
			buddypress()->loaded_components[ $this->slug ] = $this->id;
		}

		/**
		 * Filters the $blocks global value.
		 *
		 * @since 9.0.0
		 *
		 * @param array $blocks a list of global properties for blocks keyed
		 *                      by their corresponding block name.
		 */
		$block_globals = apply_filters( 'bp_' . $this->id . '_block_globals', $r['block_globals'] );
		if ( is_array( $block_globals ) && array_filter( $block_globals ) ) {
			foreach ( $block_globals as $block_name => $block_props ) {
				$this->block_globals[ $block_name ] = new stdClass();

				// Initialize an `items` property for Widget Block occurrences.
				$this->block_globals[ $block_name ]->items = array();

				// Set the global properties for the Block.
				$this->block_globals[ $block_name ]->props = (array) $block_props;
			}
		}

		/**
		 * Fires at the end of the setup_globals method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_setup_globals' );
	}

	/**
	 * Include required files.
	 *
	 * Please note that, by default, this method is fired on the bp_include
	 * hook, with priority 8. This is necessary so that core components are
	 * loaded in time to be available to third-party plugins. However, this
	 * load order means that third-party plugins whose main files are
	 * loaded at bp_include with priority 10 (as recommended), will not be
	 * loaded in time for their includes() method to fire automatically.
	 *
	 * For this reason, it is recommended that your plugin has its own
	 * method or function for requiring necessary files. If you must use
	 * this method, you will have to call it manually in your constructor
	 * class, ie
	 *   $this->includes();
	 *
	 * Note that when you pass an array value like 'actions' to includes,
	 * it looks for the following three files (assuming your component is
	 * called 'my_component'):
	 *   - ./actions
	 *   - ./bp-my_component/actions
	 *   - ./bp-my_component/bp-my_component-actions.php
	 *
	 * @since 1.5.0
	 *
	 * @param array $includes An array of file names, or file name chunks,
	 *                        to be parsed and then included.
	 */
	public function includes( $includes = array() ) {

		// Bail if no files to include.
		if ( ! empty( $includes ) ) {
			$slashed_path = trailingslashit( $this->path );

			// Loop through files to be included.
			foreach ( (array) $includes as $file ) {

				$paths = array(

					// Passed with no extension.
					'bp-' . $this->id . '/bp-' . $this->id . '-' . $file . '.php',
					'bp-' . $this->id . '-' . $file . '.php',
					'bp-' . $this->id . '/' . $file . '.php',

					// Passed with extension.
					$file,
					'bp-' . $this->id . '-' . $file,
					'bp-' . $this->id . '/' . $file,
				);

				foreach ( $paths as $path ) {
					if ( @is_file( $slashed_path . $path ) ) {
						require $slashed_path . $path;
						break;
					}
				}
			}
		}

		/**
		 * Fires at the end of the includes method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_includes' );
	}

	/**
	 * Late includes method.
	 *
	 * Components should include files here only on specific pages using
	 * conditionals such as {@link bp_is_current_component()}. Intentionally left
	 * empty.
	 *
	 * @since 3.0.0
	 */
	public function late_includes() {}

	/**
	 * Set up the actions.
	 *
	 * @since 1.5.0
	 */
	public function setup_actions() {

		// Setup globals.
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' ), 10 );

		// Set up canonical stack.
		add_action( 'bp_setup_canonical_stack', array( $this, 'setup_canonical_stack' ), 10 );

		// Include required files. Called early to ensure that BP core
		// components are loaded before plugins that hook their loader functions
		// to bp_include with the default priority of 10. This is for backwards
		// compatibility; henceforth, plugins should register themselves by
		// extending this base class.
		add_action( 'bp_include', array( $this, 'includes' ), 8 );

		// Load files conditionally, based on certain pages.
		add_action( 'bp_late_include', array( $this, 'late_includes' ), 10 );

		// Generate navigation.
		add_action( 'bp_register_nav', array( $this, 'register_nav' ), 9 );

		// Setup navigation.
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 9 );

		// Setup WP Toolbar menus.
		add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), $this->adminbar_myaccount_order );

		// Setup component title.
		add_action( 'bp_setup_title', array( $this, 'setup_title' ), 10 );

		// Setup cache groups.
		add_action( 'bp_setup_cache_groups', array( $this, 'setup_cache_groups' ), 10 );

		// Register post types.
		add_action( 'bp_register_post_types', array( $this, 'register_post_types' ), 10 );

		// Register post statuses.
		add_action( 'bp_register_post_statuses', array( $this, 'register_post_statuses' ), 10 );

		// Register taxonomies.
		add_action( 'bp_register_taxonomies', array( $this, 'register_taxonomies' ), 10 );

		// Add the rewrite tags.
		add_action( 'bp_add_rewrite_tags', array( $this, 'add_rewrite_tags' ), 10, 0 );

		// Add the rewrite rules.
		add_action( 'bp_add_rewrite_rules', array( $this, 'add_rewrite_rules' ), 10, 0 );

		// Add the permalink structure.
		add_action( 'bp_add_permastructs', array( $this, 'add_permastructs' ), 10 );

		// Allow components to parse the main query.
		add_action( 'bp_parse_query', array( $this, 'parse_query' ), 10 );

		// Generate rewrite rules.
		add_action( 'bp_generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ), 10 );

		// Register BP REST Endpoints.
		if ( bp_rest_in_buddypress() && bp_rest_api_is_available() ) {
			add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
		}

		// Register BP Blocks.
		if ( bp_support_blocks() ) {
			add_action( 'bp_blocks_init', array( $this, 'blocks_init' ), 10 );
		}

		/**
		 * Fires at the end of the setup_actions method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_setup_actions' );
	}

	/**
	 * Set up the canonical URL stack for this component.
	 *
	 * @since 2.1.0
	 */
	public function setup_canonical_stack() {}

	/**
	 * Registers nav items globalizing them into `BP_Component::$main_nav` & `BP_Component::$sub_nav` properties.
	 *
	 * @since 12.0.0
	 *
	 * @param array $main_nav Optional. Passed directly to bp_core_new_nav_item().
	 *                        See that function for a description.
	 * @param array $sub_nav  Optional. Multidimensional array, each item in
	 *                        which is passed to bp_core_new_subnav_item(). See that
	 *                        function for a description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		if ( isset( $main_nav['slug'] ) ) {
			// Always set the component ID.
			$this->main_nav['component_id'] = $this->id;

			if ( ! isset( $main_nav['rewrite_id'] ) ) {
				$this->main_nav['rewrite_id'] = 'bp_member_' . str_replace( '-', '_', $main_nav['slug'] );
			} elseif ( ! $main_nav['rewrite_id'] ) {
				unset( $main_nav['rewrite_id'] );
			}

			$this->main_nav = array_merge( $this->main_nav, $main_nav );

			// Sub nav items are not required.
			if ( ! empty( $sub_nav ) ) {
				foreach ( (array) $sub_nav as $nav ) {
					if ( ! isset( $nav['slug'], $nav['parent_slug'] ) ) {
						continue;
					}

					if ( ! isset( $nav['rewrite_id'] ) ) {
						$nav['rewrite_id'] = 'bp_member_' . str_replace( '-', '_', $nav['parent_slug'] ) . '_' . str_replace( '-', '_', $nav['slug'] );
					} elseif ( ! $nav['rewrite_id'] ) {
						unset( $nav['rewrite_id'] );
					}

					$this->sub_nav[] = $nav;
				}
			}
		}
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.5.0
	 * @since 12.0.0 Uses the registered navigations to generate it.
	 *
	 * @param array $main_nav Optional. Passed directly to bp_core_new_nav_item().
	 *                        See that function for a description.
	 * @param array $sub_nav  Optional. Multidimensional array, each item in
	 *                        which is passed to bp_core_new_subnav_item(). See that
	 *                        function for a description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Use the registered navigations if available.
		if ( empty( $main_nav ) && $this->main_nav ) {
			// Don't generate navigation if there's no member.
			if ( ! is_user_logged_in() && ! bp_is_user() ) {
				return;
			}

			$generate = true;
			if ( isset( $this->main_nav['generate'] ) ) {
				$generate = is_callable( $this->main_nav['generate'] ) ? call_user_func( $this->main_nav['generate'] ) : (bool) $this->main_nav['generate'];
				unset( $this->main_nav['generate'] );
			}

			if ( bp_displayed_user_has_front_template() ) {
				bp_core_new_nav_item(
					array(
						'name'                => _x( 'Home', 'Member Home page', 'buddypress' ),
						'slug'                => 'front',
						'position'            => 5,
						'screen_function'     => 'bp_members_screen_display_profile',
						'default_subnav_slug' => 'public',
					),
					'members'
				);
			}

			if ( 'xprofile' === $this->id ) {
				$extra_subnavs = wp_list_filter(
					buddypress()->members->sub_nav,
					array(
						'slug'            => 'change-avatar',
						'screen_function' => 'bp_members_screen_change_cover_image',
					),
					'OR'
				);

				$this->sub_nav = array_merge( $this->sub_nav, $extra_subnavs );
			}

			// No sub nav items without a main nav item.
			if ( $this->main_nav && $generate ) {
				if ( isset( $this->main_nav['user_has_access_callback'] ) && is_callable( $this->main_nav['user_has_access_callback'] ) ) {
					$this->main_nav['show_for_displayed_user'] = call_user_func( $this->main_nav['user_has_access_callback'] );
					unset( $this->main_nav['user_has_access_callback'] );
				}

				bp_core_new_nav_item( $this->main_nav, 'members' );

				// Sub nav items are not required.
				if ( $this->sub_nav ) {
					foreach ( (array) $this->sub_nav as $nav ) {
						if ( isset( $nav['user_has_access_callback'] ) && is_callable( $nav['user_has_access_callback'] ) ) {
							$nav['user_has_access'] = call_user_func( $nav['user_has_access_callback'] );
							unset( $nav['user_has_access_callback'] );
						}

						if ( isset( $nav['generate'] ) ) {
							if ( is_callable( $nav['generate'] ) ) {
								$generate_sub = call_user_func( $nav['generate'] );
							} else {
								$generate_sub = (bool) $nav['generate'];
							}

							unset( $nav['generate'] );

							if ( ! $generate_sub ) {
								continue;
							}
						}

						bp_core_new_subnav_item( $nav, 'members' );
					}
				}
			}

			/*
			 * If the `$main_nav` is populated, it means a plugin is not registering its navigation using
			 * `BP_Component::register_nav()` to enjoy the BP Rewrites API slug customization. Let's simply
			 * preverve backward compatibility in this case.
			 */
		} elseif ( ! empty( $main_nav ) && ! $this->main_nav ) {
			// Always set the component ID.
			$main_nav['component_id'] = $this->id;
			$this->main_nav           = $main_nav;

			bp_core_new_nav_item( $main_nav, 'members' );

			// Sub nav items are not required.
			if ( ! empty( $sub_nav ) ) {
				$this->sub_nav = $sub_nav;

				foreach ( (array) $sub_nav as $nav ) {
					bp_core_new_subnav_item( $nav, 'members' );
				}
			}
		}

		/**
		 * Fires at the end of the setup_nav method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_setup_nav' );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Admin_Bar::add_menu() for a description of the syntax
	 *      required by each item in the $wp_admin_nav parameter array.
	 *
	 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
	 *
	 * @param array $wp_admin_nav An array of nav item arguments. Each item in this parameter
	 *                            array is passed to {@link WP_Admin_Bar::add_menu()}.
	 *                            See that method for a description of the required syntax for
	 *                            each item.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		global $wp_admin_bar;

		// Bail if this is an ajax request.
		if ( wp_doing_ajax() ) {
			return;
		}

		/**
		 * Filters the admin navigation passed into setup_admin_bar.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.9.0
		 *
		 * @param array $wp_admin_nav Array of navigation items to add.
		 */
		$wp_admin_nav = apply_filters( 'bp_' . $this->id . '_admin_nav', $wp_admin_nav );

		// Do we have Toolbar menus to add?
		if ( ! empty( $wp_admin_nav ) ) {
			// Fill in position if one wasn't passed for backpat.
			$pos         = 0;
			$not_set_pos = 1;
			foreach ( $wp_admin_nav as $key => $nav ) {
				if ( ! isset( $nav['position'] ) ) {
					$wp_admin_nav[ $key ]['position'] = $pos + $not_set_pos;

					if ( 9 !== $not_set_pos ) {
						++$not_set_pos;
					}
				} else {
					$pos = $nav['position'];

					// Reset not set pos to 1.
					if ( $pos % 10 === 0 ) {
						$not_set_pos = 1;
					}
				}
			}

			// Sort admin nav by position.
			$wp_admin_nav = bp_sort_by_key( $wp_admin_nav, 'position', 'num' );

			// Set this objects menus.
			$this->admin_menu = $wp_admin_nav;

			// Add each admin menu.
			foreach ( $this->admin_menu as $admin_menu ) {
				$wp_admin_bar->add_node( $admin_menu );
			}
		}

		/**
		 * Fires at the end of the setup_admin_bar method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_setup_admin_bar' );
	}

	/**
	 * Set up the component title.
	 *
	 * @since 1.5.0
	 */
	public function setup_title() {

		/**
		 * Fires in the setup_title method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_setup_title' );
	}

	/**
	 * Setup component-specific cache groups.
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		/**
		 * Fires in the setup_cache_groups method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 2.2.0
		 */
		do_action( 'bp_' . $this->id . '_setup_cache_groups' );
	}

	/**
	 * Register global tables for the component, so that it may use WordPress's database API.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tables Table names to register.
	 */
	public function register_global_tables( $tables = array() ) {

		/**
		 * Filters the global tables for the component, so that it may use WordPress' database API.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 * It allows for component-specific filtering of table names. To filter
		 * *all* tables, use the 'bp_core_get_table_prefix' filter instead.
		 *
		 * @since 1.6.0
		 */
		$tables = apply_filters( 'bp_' . $this->id . '_global_tables', $tables );

		// Add to the BuddyPress global object.
		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ( $tables as $global_name => $table_name ) {
				$this->{$global_name} = $table_name;
			}

			// Keep a record of the metadata tables in the component.
			$this->global_tables = $tables;
		}

		/**
		 * Fires at the end of the register_global_tables method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 2.0.0
		 */
		do_action( 'bp_' . $this->id . '_register_global_tables' );
	}

	/**
	 * Register component metadata tables.
	 *
	 * Metadata tables are registered in the $wpdb global, for
	 * compatibility with the WordPress metadata API.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tables Table names to register.
	 */
	public function register_meta_tables( $tables = array() ) {
		global $wpdb;

		/**
		 * Filters the global meta_tables for the component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 * It allows for component-specific filtering of table names. To filter
		 * *all* tables, use the 'bp_core_get_table_prefix' filter instead.
		 *
		 * @since 2.0.0
		 */
		$tables = apply_filters( 'bp_' . $this->id . '_meta_tables', $tables );

		/**
		 * Add the name of each metadata table to WPDB to allow BuddyPress
		 * components to play nicely with the WordPress metadata API.
		 */
		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ( $tables as $meta_prefix => $table_name ) {
				$wpdb->{$meta_prefix . 'meta'} = $table_name;
			}

			// Keep a record of the metadata tables in the component.
			$this->meta_tables = $tables;
		}

		/**
		 * Fires at the end of the register_meta_tables method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 2.0.0
		 */
		do_action( 'bp_' . $this->id . '_register_meta_tables' );
	}

	/**
	 * Set up the component post types.
	 *
	 * @since 1.5.0
	 */
	public function register_post_types() {

		/**
		 * Fires in the register_post_types method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_register_post_types' );
	}

	/**
	 * Set up the component post statuses.
	 *
	 * @since 12.0.0
	 */
	public function register_post_statuses() {

		/**
		 * Fires in the `register_post_statuses` method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 12.0.0
		 */
		do_action( 'bp_' . $this->id . '_register_post_statuses' );
	}

	/**
	 * Register component-specific taxonomies.
	 *
	 * @since 1.5.0
	 */
	public function register_taxonomies() {

		/**
		 * Fires in the register_taxonomies method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_register_taxonomies' );
	}

	/**
	 * Add Component's additional rewrite tags.
	 *
	 * @since 1.5.0
	 * @since 12.0.0 Adds the `$rewrite_tags` parameter.
	 *
	 * @param array $rewrite_tags Array of arguments list used to add WordPress rewrite tags.
	 *                            Each argument key needs to match one of `$this->rewrite_ids` keys.
	 */
	public function add_rewrite_tags( $rewrite_tags = array() ) {
		if ( 'rewrites' === bp_core_get_query_parser() && array_filter( $this->rewrite_ids ) ) {
			$chunks = bp_rewrites_get_default_url_chunks();

			foreach ( $this->rewrite_ids as $rewrite_id_key => $rewrite_id_value ) {
				$rewrite_tag   = '%' . $rewrite_id_value . '%';
				$rewrite_regex = '';

				if ( isset( $rewrite_tags[ $rewrite_id_key ] ) ) {
					$rewrite_regex = $rewrite_tags[ $rewrite_id_key ];
				} elseif ( isset( $chunks[ $rewrite_id_key ]['regex'] ) ) {
					$rewrite_regex = $chunks[ $rewrite_id_key ]['regex'];
				}

				if ( ! $rewrite_regex ) {
					continue;
				}

				add_rewrite_tag( $rewrite_tag, $rewrite_regex );
			}
		}

		/**
		 * Fires in the add_rewrite_tags method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_add_rewrite_tags' );
	}

	/**
	 * Add Component's additional rewrite rules.
	 *
	 * @since 1.9.0
	 * @since 12.0.0 Adds the `$rewrite_rules` parameter.
	 *
	 * @param array $rewrite_rules {
	 *     Array of associative arrays of arguments list used to add WordPress rewrite rules.
	 *     Each associative array needs to include the following keys.
	 *
	 *     @type string $regex    Regular expression to match request against. Required.
	 *     @type string $query    The corresponding query vars for this rewrite rule. Required.
	 *     @type int    $order    The insertion order for the rewrite rule. Required.
	 *     @type string $priority The Priority of the new rule. Accepts 'top' or 'bottom'. Optional.
	 *                            Default 'top'.
	 * }
	 */
	public function add_rewrite_rules( $rewrite_rules = array() ) {
		if ( 'rewrites' === bp_core_get_query_parser() && array_filter( $this->rewrite_ids ) ) {
			$priority = 'top';
			$chunks   = array_merge( bp_rewrites_get_default_url_chunks(), $rewrite_rules );

			$rules          = bp_sort_by_key( $chunks, 'order', 'num', true );
			$reversed_rules = array_reverse( $rules, true );

			$regex = '';
			$query = '';
			$match = 1;

			// Build rewrite rules for the component.
			foreach ( $reversed_rules as $rule_key => $rule_information ) {
				if ( ! isset( $this->rewrite_ids[ $rule_key ] ) ) {
					unset( $rules[ $rule_key ] );
					continue;
				}

				// The query is already set, use it.
				if ( isset( $rule_information['query'] ) ) {
					$rules[ $rule_key ]['regex'] = $rule_information['regex'];
					$rules[ $rule_key ]['query'] = $rule_information['query'];
				} elseif ( 'directory' === $rule_key ) {
					$regex = $this->root_slug;
					$query = 'index.php?' . $this->rewrite_ids['directory'] . '=1';

					$rules[ $rule_key ]['regex'] = $regex . '/?$';
					$rules[ $rule_key ]['query'] = $query;
				} else {
					$regex  = trailingslashit( $regex ) . $rule_information['regex'];
					$query .= '&' . $this->rewrite_ids[ $rule_key ] . '=$matches[' . $match . ']';
					++$match;

					$rules[ $rule_key ]['regex'] = $regex . '/?$';
					$rules[ $rule_key ]['query'] = $query;
				}
			}

			// Then register the rewrite rules.
			if ( $rules ) {
				foreach ( $rules as $rewrite_rule ) {
					if ( ! isset( $rewrite_rule['regex'] ) || ! isset( $rewrite_rule['query'] ) ) {
						continue;
					}

					if ( ! isset( $rewrite_rule['priority'] ) || ! $rewrite_rule['priority'] ) {
						$rewrite_rule['priority'] = $priority;
					}

					add_rewrite_rule( $rewrite_rule['regex'], $rewrite_rule['query'], $rewrite_rule['priority'] );
				}
			}
		}

		/**
		 * Fires in the add_rewrite_rules method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.9.0
		 */
		do_action( 'bp_' . $this->id . '_add_rewrite_rules' );
	}

	/**
	 * Add Component's permalink structures.
	 *
	 * @since 1.9.0
	 * @since 12.0.0 Adds the `$permastructs` parameter.
	 *
	 * @param array $permastructs {
	 *      Array of associative arrays of arguments list used to register WordPress additional permalink structures.
	 *      Each array enty is keyed with the permalink structure.
	 *      Each associative array needs to include the following keys.
	 *
	 *      @type string $permastruct The permalink structure. Required.
	 *      @type array  $args        The permalink structure arguments. Optional.
	 * }
	 */
	public function add_permastructs( $permastructs = array() ) {
		// Always include the directory permastruct when the component has a directory.
		if ( isset( $this->rewrite_ids['directory'] ) ) {
			$directory_permastruct = array(
				$this->rewrite_ids['directory'] => array(
					'permastruct' => $this->directory_permastruct,
					'args'        => array(),
				),
			);

			$permastructs = array_merge( $directory_permastruct, (array) $permastructs );
		}

		if ( 'rewrites' === bp_core_get_query_parser() && $permastructs ) {
			foreach ( $permastructs as $name => $params ) {
				if ( ! $name || ! isset( $params['permastruct'] ) || ! $params['permastruct'] ) {
					continue;
				}

				if ( ! $params['args'] ) {
					$params['args'] = array();
				}

				$args = wp_parse_args(
					$params['args'],
					array(
						'with_front'  => false,
						'ep_mask'     => EP_NONE,
						'paged'       => true,
						'feed'        => false,
						'forcomments' => false,
						'walk_dirs'   => true,
						'endpoints'   => false,
					)
				);

				// Add the permastruct.
				add_permastruct( $name, $params['permastruct'], $args );
			}
		}

		/**
		 * Fires in the add_permastructs method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.9.0
		 */
		do_action( 'bp_' . $this->id . '_add_permastructs' );
	}

	/**
	 * Allow components to parse the main query.
	 *
	 * @since 1.9.0
	 *
	 * @param object $query The main WP_Query.
	 */
	public function parse_query( $query ) {
		if ( is_buddypress() && 'rewrites' === bp_core_get_query_parser() ) {
			add_filter( 'posts_pre_query', array( $this, 'pre_query' ), 10, 2 );
		}

		/**
		 * Fires in the parse_query method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.9.0
		 *
		 * @param object $query Main WP_Query object. Passed by reference.
		 */
		do_action_ref_array( 'bp_' . $this->id . '_parse_query', array( &$query ) );
	}

	/**
	 * Make sure to avoid querying for regular posts when displaying a BuddyPress page.
	 *
	 * @since 12.0.0
	 *
	 * @param  null     $posts A null value to use the regular WP Query.
	 * @param  WP_Query $query The WP Query object.
	 * @return null|array Null if not displaying a BuddyPress page.
	 *                    An array containing the BuddyPress directory page otherwise.
	 */
	public function pre_query( $posts = null, $query = null ) {
		remove_filter( 'posts_pre_query', array( $this, 'pre_query' ), 10 );

		$queried_object = $query->get_queried_object();

		if ( $queried_object instanceof WP_Post && 'buddypress' === get_post_type( $queried_object ) ) {
			$component = bp_core_get_component_from_directory_page_id( $queried_object->ID );
			if ( bp_current_user_can( 'bp_view', array( 'bp_component' => $component ) ) ) {
				// Only include the queried directory post into returned posts.
				$posts = array( $queried_object );

				// Reset some query flags.
				$query->is_home       = false;
				$query->is_front_page = false;
				$query->is_page       = false;
				$query->is_archive    = false;
				$query->is_tax        = false;

				if ( ! is_embed() ) {
					$query->is_single = true;
				}
			} else {

				/**
				 * Use this filter to send the user to the site login screen when the user does
				 * not have the `bp_view` capability for the current screen or situation.
				 * The default behavior is for the user to be shown the content in the
				 * `assets/utils/restricted-access-message.php` file.
				 *
				 * Only users that are not logged in will be sent to the login screen,
				 * else we can cause a redirect loop if the `bp_view` capability is not met
				 * for a logged-in user.
				 *
				 * @since 12.0.0
				 *
				 * @param false Whether the user should be redirected to the site login screen.
				 */
				$do_redirect_to_login_screen = apply_filters( 'bp_view_no_access_redirect_to_login_screen', false );
				if ( true === $do_redirect_to_login_screen && ! is_user_logged_in() ) {
					bp_core_no_access();
				}

				// The current user may not access the directory page.
				$bp                    = buddypress();
				$bp->current_component = 'core';

				// Unset other BuddyPress URI globals.
				foreach ( array( 'current_item', 'current_action', 'action_variables', 'displayed_user' ) as $global ) {
					if ( 'action_variables' === $global ) {
						$bp->{$global} = array();
					} elseif ( 'displayed_user' === $global ) {
						$bp->{$global} = new \stdClass();
					} else {
						$bp->{$global} = '';
					}
				}

				// Reset the post.
				$post = (object) array(
					'ID'             => 0,
					'post_type'      => 'buddypress',
					'post_name'      => 'restricted',
					'post_title'     => __( 'Members-only area', 'buddypress' ),
					'post_content'   => bp_buffer_template_part( 'assets/utils/restricted-access-message', null, false ),
					'comment_status' => 'closed',
					'comment_count'  => 0,
				);

				// Reset the queried object.
				$query->queried_object    = get_post( $post );
				$query->queried_object_id = $query->queried_object->ID;

				// Reset the posts.
				$posts = array( $query->queried_object );

				// Reset some WP Query properties.
				$query->found_posts   = 1;
				$query->max_num_pages = 1;
				$query->posts         = $posts;
				$query->post          = $post;
				$query->post_count    = 1;
				$query->is_home       = false;
				$query->is_front_page = false;
				$query->is_page       = true;
				$query->is_archive    = false;
				$query->is_tax        = false;

				// Make sure no comments are displayed for this page.
				add_filter( 'comments_pre_query', 'bp_comments_pre_query', 10, 2 );
			}

			return $posts;
		}
	}

	/**
	 * Generate any additional rewrite rules.
	 *
	 * @since 1.5.0
	 */
	public function generate_rewrite_rules() {

		/**
		 * Fires in the generate_rewrite_rules method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_' . $this->id . '_generate_rewrite_rules' );
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 *
	 * @param array $controllers The list of BP REST controllers to load.
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( is_array( $controllers ) && $controllers ) {
			// Built-in controllers.
			$_controllers = $controllers;

			/**
			 * Use this filter to disable all or some REST API controllers
			 * for the component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @since 5.0.0
			 *
			 * @param array $controllers The list of BP REST API controllers to load.
			 */
			$controllers = (array) apply_filters( 'bp_' . $this->id . '_rest_api_controllers', $controllers );

			foreach ( $controllers as $controller ) {
				if ( ! in_array( $controller, $_controllers, true ) ) {
					continue;
				}

				$component_controller = new $controller();
				$component_controller->register_routes();
			}
		}

		/**
		 * Fires in the rest_api_init method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 5.0.0
		 */
		do_action( 'bp_' . $this->id . '_rest_api_init' );
	}

	/**
	 * Register the BP Blocks.
	 *
	 * @since 6.0.0
	 *
	 * @see `BP_Block->construct()` for a full description of a BP Block arguments.
	 *
	 * @param array $blocks The list of BP Blocks to register.
	 */
	public function blocks_init( $blocks = array() ) {
		/**
		 * Filter here to add new BP Blocks, disable some or all BP Blocks for a component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 6.0.0
		 *
		 * @param array $blocks The list of BP Blocks for the component.
		 */
		$blocks = (array) apply_filters( 'bp_' . $this->id . '_register_blocks', $blocks );
		$blocks = array_filter( $blocks );

		if ( $blocks ) {
			foreach ( $blocks as $block ) {
				bp_register_block( $block );
			}
		}

		/**
		 * Fires in the blocks_init method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since 6.0.0
		 */
		do_action( 'bp_' . $this->id . '_blocks_init' );
	}

	/**
	 * Add component's directory states.
	 *
	 * @since 10.0.0
	 * @deprecated 12.0.0
	 *
	 * @param string[] $states An array of post display states.
	 * @return array The component's directory states.
	 */
	public function admin_directory_states( $states = array() ) {
		_deprecated_function( __METHOD__, '12.0.0' );

		return $states;
	}
}
