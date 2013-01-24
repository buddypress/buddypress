<?php
/**
 * The BuddyPress Plugin
 *
 * BuddyPress is social networking software with a twist from the creators of WordPress.
 *
 * @package BuddyPress
 * @subpackage Main
 */

/**
 * Plugin Name: BuddyPress
 * Plugin URI:  http://buddypress.org
 * Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
 * Author:      The BuddyPress Community
 * Author URI:  http://buddypress.org/community/members/
 * Version:     1.6.4
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Constants *****************************************************************/

if ( !class_exists( 'BuddyPress' ) ) :
/**
 * Main BuddyPress Class
 *
 * Tap tap tap... Is this thing on?
 *
 * @since BuddyPress (1.6)
 */
class BuddyPress {

	/**
	 * Note to Plugin and Theme authors:
	 *
	 * Do not directly reference the variables below in your code. Their names
	 * and locations in the BuddyPress class are subject to change at any time.
	 *
	 * Most of them have reference functions located in bp-core-functions.php.
	 * The ones that don't can be accessed via their respective WordPress API's.
	 *
	 * Components are encouraged to store their data in the $bp global rather
	 * than new globals to keep all BuddyPress data in one place.
	 */

	/** Version ***************************************************************/

	/**
	 * @var string BuddyPress version
	 */
	public $version = '1.6.4';

	/**
	 * @var int Database version of current BuddyPress files
	 */
	public $db_version = 6066;

	/**
	 * @var int Database version raw from database connection
	 */
	public $db_version_raw = 0;

	/**
	 * @var string State of BuddyPress installation
	 */
	public $maintenance_mode = '';

	/**
	 * @var bool Include deprecated BuddyPress files or not
	 */
	public $load_deprecated = true;

	/** Root ******************************************************************/

	/**
	 * @var int The root blog ID
	 */
	public $root_blog_id = 1;

	/** Paths *****************************************************************/

	/**
	 * The absolute path and filename of this file.
	 *
	 * @since BuddyPress (1.6)
	 * @var string
	 */
	public $file;

	/**
	 * @var string Basename of the BuddyPress plugin directory
	 */
	public $basename = '';

	/**
	 * @var string Absolute path to the BuddyPress plugin directory
	 */
	public $plugin_dir = '';

	/**
	 * @var string Absolute path to the BuddyPress themes directory
	 */
	public $themes_dir = '';

	/**
	 * @var string Absolute path to the BuddyPress language directory
	 */
	public $lang_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the BuddyPress plugin directory
	 */
	public $plugin_url = '';

	/**
	 * @var string URL to the BuddyPress themes directory
	 */
	public $themes_url = '';

	/** Users *****************************************************************/

	/**
	 * @var object Current user
	 */
	public $current_user = null;

	/**
	 * @var object Displayed user
	 */
	public $displayed_user = null;

	/** Navigation ************************************************************/

	/**
	 * @var array Primary BuddyPress navigation
	 */
	public $bp_nav = array();

	/**
	 * @var array Secondary BuddyPress navigation to $bp_nav
	 */
	public $bp_options_nav = array();

	/** Toolbar ***************************************************************/

	/**
	 * @var string The primary toolbar ID
	 */
	public $my_account_menu_id = '';

	/** URI's *****************************************************************/

	/**
	 * @var array The unfiltered URI broken down into chunks
	 * @see bp_core_set_uri_globals()
	 */
	public $unfiltered_uri = array();

	/**
	 * @var int The current offset of the URI
	 * @see bp_core_set_uri_globals()
	 */
	public $unfiltered_uri_offset = 0;

	/**
	 * @var bool Are status headers already sent?
	 */
	public $no_status_set = false;

	/**
	 * @var array The canonical URI stack
	 * @see bp_redirect_canonical()
	 * @see bp_core_new_nav_item()
	 */
	public $canonical_stack = array();

	/** Components ************************************************************/

	/**
	 * @var string Name of the current BuddyPress component (primary)
	 */
	public $current_component = '';

	/**
	 * @var string Name of the current BuddyPress item (secondary)
	 */
	public $current_item = '';

	/**
	 * @var string Name of the current BuddyPress action (tertiary)
	 */
	public $current_action = '';

	/**
	 * @var array Additional navigation elements (supplemental)
	 */
	public $action_variables = array();

	/**
	 * @var bool Displaying custom 2nd level navigation menu (I.E a group)
	 */
	public $is_single_item = false;

	/** Option Overload *******************************************************/

	/**
	 * @var array Optional Overloads default options retrieved from get_option()
	 */
	public $options = array();

	/** Functions *************************************************************/

	/**
	 * The main BuddyPress loader
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses BuddyPress::constants() Setup legacy constants
	 * @uses BuddyPress::setup_globals() Setup globals needed
	 * @uses BuddyPress::includes() Includ required files
	 * @uses BuddyPress::setup_actions() Setup hooks and actions
	 */
	public function __construct() {
		$this->constants();
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Legacy BuddyPress constants
	 *
	 * Try to avoid using these. Their values have been moved into variables
	 * in the $bp global, and have matching functions to get/set their value.
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses is_multisite()
	 * @uses get_current_site()
	 * @uses get_current_blog_id()
	 * @uses plugin_dir_path()
	 * @uses plugin_dir_url()
	 */
	private function constants() {

		// Define the BuddyPress version
		if ( !defined( 'BP_VERSION' ) )
			define( 'BP_VERSION', $this->version );

		// Define the database version
		if ( !defined( 'BP_DB_VERSION' ) )
			define( 'BP_DB_VERSION', $this->db_version );

		// Place your custom code (actions/filters) in a file called
		// '/plugins/bp-custom.php' and it will be loaded before anything else.
		if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) )
			require( WP_PLUGIN_DIR . '/bp-custom.php' );

		// Define on which blog ID BuddyPress should run
		if ( !defined( 'BP_ROOT_BLOG' ) ) {

			// Default to 1
			$root_blog_id = 1;

			// Root blog is the main site on this network
			if ( is_multisite() && !defined( 'BP_ENABLE_MULTIBLOG' ) ) {
				$current_site = get_current_site();
				$root_blog_id = $current_site->blog_id;

			// Root blog is every site on this network
			} elseif ( is_multisite() && defined( 'BP_ENABLE_MULTIBLOG' ) ) {
				$root_blog_id = get_current_blog_id();
			}

			define( 'BP_ROOT_BLOG', $root_blog_id );
		}

		// Path and URL
		if ( !defined( 'BP_PLUGIN_DIR' ) )
			define( 'BP_PLUGIN_DIR', trailingslashit( WP_PLUGIN_DIR . '/buddypress' ) );

		if ( !defined( 'BP_PLUGIN_URL' ) ) {
			$plugin_url = plugin_dir_url( __FILE__ );

			// If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
			if ( is_ssl() )
				$plugin_url = str_replace( 'http://', 'https://', $plugin_url );

			define( 'BP_PLUGIN_URL', $plugin_url );
		}

		// The search slug has to be defined nice and early because of the way
		// search requests are loaded
		//
		// @todo Make this better
		if ( !defined( 'BP_SEARCH_SLUG' ) )
			define( 'BP_SEARCH_SLUG', 'search' );
	}

	/**
	 * Component global variables
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses plugin_dir_path() To generate BuddyPress plugin path
	 * @uses plugin_dir_url() To generate BuddyPress plugin url
	 * @uses apply_filters() Calls various filters
	 */
	private function setup_globals() {

		/** Root **************************************************************/

		// BuddyPress Root blog ID
		$this->root_blog_id = (int) apply_filters( 'bp_get_root_blog_id', BP_ROOT_BLOG );

		/** Paths *************************************************************/

		// BuddyPress root directory
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = BP_PLUGIN_DIR;
		$this->plugin_url = BP_PLUGIN_URL;

		// Themes
		$this->themes_dir = $this->plugin_dir . 'bp-themes';
		$this->themes_url = $this->plugin_url . 'bp-themes';

		// Languages
		$this->lang_dir   = $this->plugin_dir . 'bp-languages';

		/** Users *************************************************************/

		$this->current_user       = new stdClass();
		$this->displayed_user     = new stdClass();
	}

	/**
	 * Include required files
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses is_admin() If in WordPress admin, load additional file
	 */
	private function includes() {

		// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
		require( BP_PLUGIN_DIR . '/bp-core/bp-core-wpabstraction.php' );

		// Get the possible DB versions (boy is this gross)
		$versions               = array();
		$versions['1.6-single'] = get_blog_option( $this->root_blog_id, '_bp_db_version' );

		// 1.6-single exists, so trust it
		if ( !empty( $versions['1.6-single'] ) ) {
			$this->db_version_raw = (int) $versions['1.6-single'];

		// If no 1.6-single exists, use the max of the others
		} else {
			$versions['1.2']        = get_site_option(                      'bp-core-db-version' );
			$versions['1.5-multi']  = get_site_option(                           'bp-db-version' );
			$versions['1.6-multi']  = get_site_option(                          '_bp_db_version' );
			$versions['1.5-single'] = get_blog_option( $this->root_blog_id,     'bp-db-version'  );

			// Remove empty array items
			$versions             = array_filter( $versions );
			$this->db_version_raw = (int) ( !empty( $versions ) ) ? (int) max( $versions ) : 0;
		}

		/** Update/Install ****************************************************/

		// This is a new installation
		if ( is_admin() ) {

			// New installation
			if ( empty( $this->db_version_raw ) ) {
				$this->maintenance_mode = 'install';

			// Update
			} elseif ( (int) $this->db_version_raw < (int) $this->db_version ) {
				$this->maintenance_mode = 'update';
			}

			// The installation process requires a few BuddyPress core libraries
			if ( !empty( $this->maintenance_mode ) ) {
				require( $this->plugin_dir . 'bp-core/bp-core-admin.php'     );
				require( $this->plugin_dir . 'bp-core/bp-core-functions.php' );
				require( $this->plugin_dir . 'bp-core/bp-core-template.php'  );
				require( $this->plugin_dir . 'bp-core/bp-core-update.php'    );
				require( $this->plugin_dir . 'bp-core/bp-core-caps.php'      );
				require( $this->plugin_dir . 'bp-core/bp-core-options.php'   );

				/**
				 * Textdomain is usually loaded via the bp_core_loaded action, but
				 * that action isn't available when BP is in maintenance mode.
				 */
				add_action( 'plugins_loaded', 'bp_core_load_buddypress_textdomain', 9 );

				// Load up BuddyPress's admin
				add_action( 'plugins_loaded', 'bp_admin' );
			}
		}

		// Not in maintenance mode
		if ( empty( $this->maintenance_mode ) ) {

			// Require all of the BuddyPress core libraries
			require( $this->plugin_dir . 'bp-core/bp-core-actions.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-caps.php'       );
			require( $this->plugin_dir . 'bp-core/bp-core-cache.php'      );
			require( $this->plugin_dir . 'bp-core/bp-core-cssjs.php'      );
			require( $this->plugin_dir . 'bp-core/bp-core-update.php'     );
			require( $this->plugin_dir . 'bp-core/bp-core-options.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-classes.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-filters.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-avatars.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-widgets.php'    );
			require( $this->plugin_dir . 'bp-core/bp-core-template.php'   );
			require( $this->plugin_dir . 'bp-core/bp-core-adminbar.php'   );
			require( $this->plugin_dir . 'bp-core/bp-core-buddybar.php'   );
			require( $this->plugin_dir . 'bp-core/bp-core-catchuri.php'   );
			require( $this->plugin_dir . 'bp-core/bp-core-component.php'  );
			require( $this->plugin_dir . 'bp-core/bp-core-functions.php'  );
			require( $this->plugin_dir . 'bp-core/bp-core-moderation.php' );
			require( $this->plugin_dir . 'bp-core/bp-core-loader.php'     );

			// Skip or load deprecated content
			if ( false !== $this->load_deprecated ) {
				require( $this->plugin_dir . 'bp-core/deprecated/1.5.php' );
				require( $this->plugin_dir . 'bp-core/deprecated/1.6.php' );
			}
		}
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses register_activation_hook() To register the activation hook
	 * @uses register_deactivation_hook() To register the deactivation hook
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'bp_activation'   );
		add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If BuddyPress is being deactivated, do not add any actions
		if ( bp_is_deactivation( $this->basename ) )
			return;

		// Array of BuddyPress core actions
		$actions = array(
			'setup_current_user',       // Setup currently logged in user
			'register_post_types',      // Register post types
			'register_post_statuses',   // Register post statuses
			'register_taxonomies',      // Register taxonomies
			'register_views',           // Register the views
			'register_theme_directory', // Register the theme directory
			'load_textdomain',          // Load textdomain
			'add_rewrite_tags',         // Add rewrite tags
			'generate_rewrite_rules'    // Generate rewrite rules
		);

		// Add the actions
		foreach( $actions as $class_action )
			add_action( 'bp_' . $class_action, array( $this, $class_action ), 5 );

		// Setup the BuddyPress theme directory
		register_theme_directory( $this->themes_dir );
	}
}

// "And now for something completely different"
$GLOBALS['bp'] = new BuddyPress;

endif;
