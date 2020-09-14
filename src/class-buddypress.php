<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main BuddyPress Class.
 *
 * Tap tap tap... Is this thing on?
 *
 * @since 1.6.0
 */
class BuddyPress {

	/** Magic *****************************************************************/

	/**
	 * BuddyPress uses many variables, most of which can be filtered to
	 * customize the way that it works. To prevent unauthorized access,
	 * these variables are stored in a private array that is magically
	 * updated using PHP 5.2+ methods. This is to prevent third party
	 * plugins from tampering with essential information indirectly, which
	 * would cause issues later.
	 *
	 * @see BuddyPress::setup_globals()
	 * @var array
	 */
	private $data;

	/** Not Magic *************************************************************/

	/**
	 * @var array Primary BuddyPress navigation.
	 */
	public $bp_nav = array();

	/**
	 * @var array Secondary BuddyPress navigation to $bp_nav.
	 */
	public $bp_options_nav = array();

	/**
	 * @var array The unfiltered URI broken down into chunks.
	 * @see bp_core_set_uri_globals()
	 */
	public $unfiltered_uri = array();

	/**
	 * @var array The canonical URI stack.
	 * @see bp_redirect_canonical()
	 * @see bp_core_new_nav_item()
	 */
	public $canonical_stack = array();

	/**
	 * @var array Additional navigation elements (supplemental).
	 */
	public $action_variables = array();

	/**
	 * @var string Current member directory type.
	 */
	public $current_member_type = '';

	/**
	 * @var array Required components (core, members).
	 */
	public $required_components = array();

	/**
	 * @var array Additional active components.
	 */
	public $loaded_components = array();

	/**
	 * @var array Active components.
	 */
	public $active_components = array();

	/**
	 * Whether autoload is in use.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public $do_autoload = true;

	/** Option Overload *******************************************************/

	/**
	 * @var array Optional Overloads default options retrieved from get_option().
	 */
	public $options = array();

	/** Singleton *************************************************************/

	/**
	 * Main BuddyPress Instance.
	 *
	 * BuddyPress is great.
	 * Please load it only one time.
	 * For this, we thank you.
	 *
	 * Insures that only one instance of BuddyPress exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.7.0
	 *
	 * @static object $instance
	 * @see buddypress()
	 *
	 * @return BuddyPress|null The one true BuddyPress.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been run previously.
		if ( null === $instance ) {
			$instance = new BuddyPress;
			$instance->constants();
			$instance->setup_globals();
			$instance->legacy_constants();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance.
		return $instance;

		// The last metroid is in captivity. The galaxy is at peace.
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent BuddyPress from being loaded more than once.
	 *
	 * @since 1.7.0
	 * @see BuddyPress::instance()
	 * @see buddypress()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent BuddyPress from being cloned.
	 *
	 * @since 1.7.0
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddypress' ), '1.7' ); }

	/**
	 * A dummy magic method to prevent BuddyPress from being unserialized.
	 *
	 * @since 1.7.0
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddypress' ), '1.7' ); }

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to check the set status for.
	 *
	 * @return bool
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting BuddyPress variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to return the value for.
	 *
	 * @return mixed
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Magic method for setting BuddyPress variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) { $this->data[$key] = $value; }

	/**
	 * Magic method for unsetting BuddyPress variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to unset a value for.
	 */
	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls.
	 *
	 * @since 1.7.0
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return null
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/** Private Methods *******************************************************/

	/**
	 * Bootstrap constants.
	 *
	 * @since 1.6.0
	 *
	 */
	private function constants() {

		// Place your custom code (actions/filters) in a file called
		// '/plugins/bp-custom.php' and it will be loaded before anything else.
		if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) ) {
			require( WP_PLUGIN_DIR . '/bp-custom.php' );
		}

		// Path and URL.
		if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
			define( 'BP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'BP_PLUGIN_URL' ) ) {
			define( 'BP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Legacy forum constant - supported for compatibility with bbPress 2.
		if ( ! defined( 'BP_FORUMS_PARENT_FORUM_ID' ) ) {
			define( 'BP_FORUMS_PARENT_FORUM_ID', 1 );
		}

		// Legacy forum constant - supported for compatibility with bbPress 2.
		if ( ! defined( 'BP_FORUMS_SLUG' ) ) {
			define( 'BP_FORUMS_SLUG', 'forums' );
		}

		// Only applicable to those running trunk.
		if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) ) {
			define( 'BP_SOURCE_SUBDIRECTORY', '' );
		}

		// Define on which blog ID BuddyPress should run.
		if ( ! defined( 'BP_ROOT_BLOG' ) ) {

			// Default to use current blog ID.
			// Fulfills non-network installs and BP_ENABLE_MULTIBLOG installs.
			$root_blog_id = get_current_blog_id();

			// Multisite check.
			if ( is_multisite() ) {

				// Multiblog isn't enabled.
				if ( ! defined( 'BP_ENABLE_MULTIBLOG' ) || ( defined( 'BP_ENABLE_MULTIBLOG' ) && (int) constant( 'BP_ENABLE_MULTIBLOG' ) === 0 ) ) {
					// Check to see if BP is network-activated
					// We're not using is_plugin_active_for_network() b/c you need to include the
					// /wp-admin/includes/plugin.php file in order to use that function.

					// Get network-activated plugins.
					$plugins = get_site_option( 'active_sitewide_plugins');

					// Basename.
					$basename = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';

					// Plugin is network-activated; use main site ID instead.
					if ( isset( $plugins[ $basename ] ) ) {
						$current_site = get_current_site();
						$root_blog_id = $current_site->blog_id;
					}
				}

			}

			define( 'BP_ROOT_BLOG', $root_blog_id );
		}

		// The search slug has to be defined nice and early because of the way
		// search requests are loaded.
		//
		// @todo Make this better.
		if ( ! defined( 'BP_SEARCH_SLUG' ) ) {
			define( 'BP_SEARCH_SLUG', 'search' );
		}
	}

	/**
	 * Component global variables.
	 *
	 * @since 1.6.0
	 *
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version    = '6.3.0';
		$this->db_version = 12385;

		/** Loading ***********************************************************/

		/**
		 * Should deprecated code be loaded?
		 *
		 * @since 2.0.0 Defaults to false always
		 * @since 2.8.0 Defaults to true on upgrades, false for new installs.
		 */
		$this->load_deprecated = false;

		/** Toolbar ***********************************************************/

		/**
		 * @var string The primary toolbar ID.
		 */
		$this->my_account_menu_id = '';

		/** URIs **************************************************************/

		/**
		 * @var int The current offset of the URI.
		 * @see bp_core_set_uri_globals()
		 */
		$this->unfiltered_uri_offset = 0;

		/**
		 * @var bool Are status headers already sent?
		 */
		$this->no_status_set = false;

		/** Components ********************************************************/

		/**
		 * @var string Name of the current BuddyPress component (primary).
		 */
		$this->current_component = '';

		/**
		 * @var string Name of the current BuddyPress item (secondary).
		 */
		$this->current_item = '';

		/**
		 * @var string Name of the current BuddyPress action (tertiary).
		 */
		$this->current_action = '';

		/**
		 * @var bool Displaying custom 2nd level navigation menu (I.E a group).
		 */
		$this->is_single_item = false;

		/** Root **************************************************************/

		/**
		 * Filters the BuddyPress Root blog ID.
		 *
		 * @since 1.5.0
		 *
		 * @const constant BP_ROOT_BLOG BuddyPress Root blog ID.
		 */
		$this->root_blog_id = (int) apply_filters( 'bp_get_root_blog_id', BP_ROOT_BLOG );

		/** Paths**************************************************************/

		// BuddyPress root directory.
		$this->file           = constant( 'BP_PLUGIN_DIR' ) . 'bp-loader.php';
		$this->basename       = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';
		$this->plugin_dir     = trailingslashit( constant( 'BP_PLUGIN_DIR' ) . constant( 'BP_SOURCE_SUBDIRECTORY' ) );
		$this->plugin_url     = trailingslashit( constant( 'BP_PLUGIN_URL' ) . constant( 'BP_SOURCE_SUBDIRECTORY' ) );

		// Languages.
		$this->lang_dir       = $this->plugin_dir . 'bp-languages';

		// Templates (theme compatibility).
		$this->themes_dir     = $this->plugin_dir . 'bp-templates';
		$this->themes_url     = $this->plugin_url . 'bp-templates';

		// Themes (for bp-default).
		$this->old_themes_dir = $this->plugin_dir . 'bp-themes';
		$this->old_themes_url = $this->plugin_url . 'bp-themes';

		/** Theme Compat ******************************************************/

		$this->theme_compat   = new stdClass(); // Base theme compatibility class.
		$this->filters        = new stdClass(); // Used when adding/removing filters.

		/** Users *************************************************************/

		$this->current_user   = new stdClass();
		$this->displayed_user = new stdClass();

		/** Post types and taxonomies *****************************************/

		/**
		 * Filters the post type slug for the email component.
		 *
		 * since 2.5.0
		 *
		 * @param string $value Email post type slug.
		 */
		$this->email_post_type     = apply_filters( 'bp_email_post_type', 'bp-email' );

		/**
		 * Filters the taxonomy slug for the email type component.
		 *
		 * @since 2.5.0
		 *
		 * @param string $value Email type taxonomy slug.
		 */
		$this->email_taxonomy_type = apply_filters( 'bp_email_tax_type', 'bp-email-type' );
	}

	/**
	 * Legacy BuddyPress constants.
	 *
	 * Try to avoid using these. Their values have been moved into variables
	 * in the instance, and have matching functions to get/set their values.
	 *
	 * @since 1.7.0
	 */
	private function legacy_constants() {

		// Define the BuddyPress version.
		if ( ! defined( 'BP_VERSION' ) ) {
			define( 'BP_VERSION', $this->version );
		}

		// Define the database version.
		if ( ! defined( 'BP_DB_VERSION' ) ) {
			define( 'BP_DB_VERSION', $this->db_version );
		}

		// Define if deprecated functions should be ignored.
		if ( ! defined( 'BP_IGNORE_DEPRECATED' ) ) {
			define( 'BP_IGNORE_DEPRECATED', true );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since 1.6.0
	 *
	 */
	private function includes() {
		spl_autoload_register( array( $this, 'autoload' ) );

		// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
		require( $this->plugin_dir . 'bp-core/bp-core-wpabstraction.php' );

		// Setup the versions (after we include multisite abstraction above).
		$this->versions();

		/** Update/Install ****************************************************/

		// Theme compatibility.
		require( $this->plugin_dir . 'bp-core/bp-core-template-loader.php'     );
		require( $this->plugin_dir . 'bp-core/bp-core-theme-compatibility.php' );

		// Require all of the BuddyPress core libraries.
		require( $this->plugin_dir . 'bp-core/bp-core-dependency.php'       );
		require( $this->plugin_dir . 'bp-core/bp-core-actions.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-caps.php'             );
		require( $this->plugin_dir . 'bp-core/bp-core-cache.php'            );
		require( $this->plugin_dir . 'bp-core/bp-core-cssjs.php'            );
		require( $this->plugin_dir . 'bp-core/bp-core-update.php'           );
		require( $this->plugin_dir . 'bp-core/bp-core-options.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-taxonomy.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-filters.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-attachments.php'      );
		require( $this->plugin_dir . 'bp-core/bp-core-avatars.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-widgets.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-template.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-adminbar.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-buddybar.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-catchuri.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-functions.php'        );
		require( $this->plugin_dir . 'bp-core/bp-core-moderation.php'       );
		require( $this->plugin_dir . 'bp-core/bp-core-loader.php'           );
		require( $this->plugin_dir . 'bp-core/bp-core-customizer-email.php' );
		require( $this->plugin_dir . 'bp-core/bp-core-rest-api.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-blocks.php'           );

		// Maybe load deprecated functionality (this double negative is proof positive!).
		if ( ! bp_get_option( '_bp_ignore_deprecated_code', ! $this->load_deprecated ) ) {
			require( $this->plugin_dir . 'bp-core/deprecated/1.2.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.5.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.6.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.7.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.9.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.0.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.1.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.2.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.3.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.4.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.5.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.6.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.7.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.8.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/2.9.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/3.0.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/4.0.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/6.0.php' );
		}

		// Load wp-cli module if PHP 5.4+.
		if ( defined( 'WP_CLI' ) && file_exists( $this->plugin_dir . 'cli/wp-cli-bp.php' ) && version_compare( phpversion(), '5.4.0', '>=' ) ) {
			require( $this->plugin_dir . 'cli/wp-cli-bp.php' );
		}
	}

	/**
	 * Autoload classes.
	 *
	 * @since 2.5.0
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class_parts = explode( '_', strtolower( $class ) );

		if ( 'bp' !== $class_parts[0] ) {
			return;
		}

		$components = array(
			'activity',
			'blogs',
			'core',
			'friends',
			'groups',
			'members',
			'messages',
			'notifications',
			'settings',
			'xprofile',
		);

		// These classes don't have a name that matches their component.
		$irregular_map = array(
			'BP_Akismet'                => 'activity',
			'BP_REST_Activity_Endpoint' => 'activity',

			'BP_REST_Blogs_Endpoint'                   => 'blogs',
			'BP_REST_Attachments_Blog_Avatar_Endpoint' => 'blogs',

			'BP_Admin'                     => 'core',
			'BP_Attachment_Avatar'         => 'core',
			'BP_Attachment_Cover_Image'    => 'core',
			'BP_Attachment'                => 'core',
			'BP_Button'                    => 'core',
			'BP_Block'                     => 'core',
			'BP_Component'                 => 'core',
			'BP_Customizer_Control_Range'  => 'core',
			'BP_Date_Query'                => 'core',
			'BP_Email_Delivery'            => 'core',
			'BP_Email_Address'             => 'core',
			'BP_Email_Recipient'           => 'core',
			'BP_Email_Sender'              => 'core',
			'BP_Email_Participant'         => 'core',
			'BP_Email'                     => 'core',
			'BP_Embed'                     => 'core',
			'BP_Media_Extractor'           => 'core',
			'BP_Members_Suggestions'       => 'core',
			'BP_PHPMailer'                 => 'core',
			'BP_Recursive_Query'           => 'core',
			'BP_Suggestions'               => 'core',
			'BP_Theme_Compat'              => 'core',
			'BP_User_Query'                => 'core',
			'BP_Walker_Category_Checklist' => 'core',
			'BP_Walker_Nav_Menu_Checklist' => 'core',
			'BP_Walker_Nav_Menu'           => 'core',
			'BP_Invitation_Manager'        => 'core',
			'BP_Invitation'                => 'core',
			'BP_REST_Components_Endpoint'  => 'core',
			'BP_REST_Attachments'          => 'core',

			'BP_Core_Friends_Widget'   => 'friends',
			'BP_REST_Friends_Endpoint' => 'friends',

			'BP_Group_Extension'                        => 'groups',
			'BP_Group_Member_Query'                     => 'groups',
			'BP_REST_Groups_Endpoint'                   => 'groups',
			'BP_REST_Group_Membership_Endpoint'         => 'groups',
			'BP_REST_Group_Invites_Endpoint'            => 'groups',
			'BP_REST_Group_Membership_Request_Endpoint' => 'groups',
			'BP_REST_Attachments_Group_Avatar_Endpoint' => 'groups',
			'BP_REST_Attachments_Group_Cover_Endpoint'  => 'groups',

			'BP_Core_Members_Template'                   => 'members',
			'BP_Core_Members_Widget'                     => 'members',
			'BP_Core_Recently_Active_Widget'             => 'members',
			'BP_Core_Whos_Online_Widget'                 => 'members',
			'BP_Registration_Theme_Compat'               => 'members',
			'BP_Signup'                                  => 'members',
			'BP_REST_Members_Endpoint'                   => 'members',
			'BP_REST_Attachments_Member_Avatar_Endpoint' => 'members',
			'BP_REST_Attachments_Member_Cover_Endpoint'  => 'members',
			'BP_REST_Signup_Endpoint'                    => 'members',

			'BP_REST_Messages_Endpoint' => 'messages',

			'BP_REST_Notifications_Endpoint' => 'notifications',

			'BP_REST_XProfile_Fields_Endpoint'       => 'xprofile',
			'BP_REST_XProfile_Field_Groups_Endpoint' => 'xprofile',
			'BP_REST_XProfile_Data_Endpoint'         => 'xprofile',
		);

		$component = null;

		// First check to see if the class is one without a properly namespaced name.
		if ( isset( $irregular_map[ $class ] ) ) {
			$component = $irregular_map[ $class ];

		// Next chunk is usually the component name.
		} elseif ( in_array( $class_parts[1], $components, true ) ) {
			$component = $class_parts[1];
		}

		if ( ! $component ) {
			return;
		}

		// Sanitize class name.
		$class = strtolower( str_replace( '_', '-', $class ) );

		if ( 'bp-rest-attachments' === $class ) {
			$path = dirname( __FILE__ ) . "/bp-{$component}/classes/trait-attachments.php";
		} else {
			$path = dirname( __FILE__ ) . "/bp-{$component}/classes/class-{$class}.php";
		}

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		/*
		 * Sanity check 2 - Check if component is active before loading class.
		 * Skip if PHPUnit is running, or BuddyPress is installing for the first time.
		 */
		if (
			! in_array( $component, array( 'core', 'members' ), true ) &&
			! bp_is_active( $component ) &&
			! function_exists( 'tests_add_filter' )
		) {
			return;
		}

		require $path;
	}

	/**
	 * Set up the default hooks and actions.
	 *
	 * @since 1.6.0
	 *
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks.
		add_action( 'activate_'   . $this->basename, 'bp_activation'   );
		add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If BuddyPress is being deactivated, do not add any actions.
		if ( bp_is_deactivation( $this->basename ) ) {
			return;
		}

		// Array of BuddyPress core actions
		$actions = array(
			'setup_theme',              // Setup the default theme compat.
			'setup_current_user',       // Setup currently logged in user.
			'register_post_types',      // Register post types.
			'register_post_statuses',   // Register post statuses.
			'register_taxonomies',      // Register taxonomies.
			'register_views',           // Register the views.
			'register_theme_directory', // Register the theme directory.
			'register_theme_packages',  // Register bundled theme packages (bp-themes).
			'load_textdomain',          // Load textdomain.
			'add_rewrite_tags',         // Add rewrite tags.
			'generate_rewrite_rules'    // Generate rewrite rules.
		);

		// Add the actions.
		foreach( $actions as $class_action ) {
			if ( method_exists( $this, $class_action ) ) {
				add_action( 'bp_' . $class_action, array( $this, $class_action ), 5 );
			}
		}

		/**
		 * Fires after the setup of all BuddyPress actions.
		 *
		 * Includes bbp-core-hooks.php.
		 *
		 * @since 1.7.0
		 *
		 * @param BuddyPress $this. Current BuddyPress instance. Passed by reference.
		 */
		do_action_ref_array( 'bp_after_setup_actions', array( &$this ) );
	}

	/**
	 * Private method to align the active and database versions.
	 *
	 * @since 1.7.0
	 */
	private function versions() {

		// Get the possible DB versions (boy is this gross).
		$versions               = array();
		$versions['1.6-single'] = get_blog_option( $this->root_blog_id, '_bp_db_version' );

		// 1.6-single exists, so trust it.
		if ( !empty( $versions['1.6-single'] ) ) {
			$this->db_version_raw = (int) $versions['1.6-single'];

		// If no 1.6-single exists, use the max of the others.
		} else {
			$versions['1.2']        = get_site_option(                      'bp-core-db-version' );
			$versions['1.5-multi']  = get_site_option(                           'bp-db-version' );
			$versions['1.6-multi']  = get_site_option(                          '_bp_db_version' );
			$versions['1.5-single'] = get_blog_option( $this->root_blog_id,      'bp-db-version' );

			// Remove empty array items.
			$versions             = array_filter( $versions );
			$this->db_version_raw = (int) ( !empty( $versions ) ) ? (int) max( $versions ) : 0;
		}
	}

	/** Public Methods ********************************************************/

	/**
	 * Set up BuddyPress's legacy theme directory.
	 *
	 * Starting with version 1.2, and ending with version 1.8, BuddyPress
	 * registered a custom theme directory - bp-themes - which contained
	 * the bp-default theme. Since BuddyPress 1.9, bp-themes is no longer
	 * registered (and bp-default no longer offered) on new installations.
	 * Sites using bp-default (or a child theme of bp-default) will
	 * continue to have bp-themes registered as before.
	 *
	 * @since 1.5.0
	 *
	 * @todo Move bp-default to wordpress.org/extend/themes and remove this.
	 */
	public function register_theme_directory() {
		if ( ! bp_do_register_theme_directory() ) {
			return;
		}

		register_theme_directory( $this->old_themes_dir );
	}

	/**
	 * Register bundled theme packages.
	 *
	 * Note that since we currently have complete control over bp-themes and
	 * the bp-legacy folders, it's fine to hardcode these here. If at a
	 * later date we need to automate this, an API will need to be built.
	 *
	 * @since 1.7.0
	 */
	public function register_theme_packages() {

		// Register the default theme compatibility package.
		bp_register_theme_package( array(
			'id'      => 'legacy',
			'name'    => __( 'BuddyPress Legacy', 'buddypress' ),
			'version' => bp_get_version(),
			'dir'     => trailingslashit( $this->themes_dir . '/bp-legacy' ),
			'url'     => trailingslashit( $this->themes_url . '/bp-legacy' )
		) );

		bp_register_theme_package( array(
			'id'      => 'nouveau',
			'name'    => __( 'BuddyPress Nouveau', 'buddypress' ),
			'version' => bp_get_version(),
			'dir'     => trailingslashit( $this->themes_dir . '/bp-nouveau' ),
			'url'     => trailingslashit( $this->themes_url . '/bp-nouveau' )
		) );

		// Register the basic theme stack. This is really dope.
		bp_register_template_stack( 'get_stylesheet_directory', 10 );
		bp_register_template_stack( 'get_template_directory',   12 );
		bp_register_template_stack( 'bp_get_theme_compat_dir',  14 );
	}

	/**
	 * Set up the default BuddyPress theme compatibility location.
	 *
	 * @since 1.7.0
	 */
	public function setup_theme() {

		// Bail if something already has this under control.
		if ( ! empty( $this->theme_compat->theme ) ) {
			return;
		}

		// Setup the theme package to use for compatibility.
		bp_setup_theme_compat( bp_get_theme_package_id() );
	}
}
