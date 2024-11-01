<?php
/**
 * BuddyPress Component's feature Class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Component Feature API.
 *
 * @since 15.0.0
 */
class BP_Component_Feature extends BP_Component {

	/**
	 * Unique ID for the feature.
	 *
	 * @since 15.0.0
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Unique Slug for the feature.
	 *
	 * @since 15.0.0
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Unique ID for the feature's component.
	 *
	 * @since 15.0.0
	 *
	 * @var string
	 */
	public $component_id = '';

	/**
	 * This is a component's feature.
	 *
	 * @since 15.0.0
	 *
	 * @var boolean
	 */
	public $is_feature = true;

	/**
	 * Disable the `BP_Component()` starting method.
	 *
	 * @since 15.0.0
	 *
	 * @param string $id     Unique ID. Letters, numbers, and underscores only.
	 * @param string $name   Unique raw name for the component (do not use translatable strings).
	 * @param string $path   The file path for the component's files. Used by {@link BP_Component::includes()}.
	 * @param array  $params {
	 *     Additional parameters used by the component.
	 *
	 *     @see BP_Component::start() for a description of parameters.
	 * }
	 */
	public function start( $id = '', $name = '', $path = '', $params = array() ) {
		_doing_it_wrong(
			__CLASS__ . '::' . __METHOD__,
			esc_html__( 'This method is not implemented. Use the `init` method to initialize this feature instead.', 'buddypress' ),
			'15.0.0'
		);
	}

	/**
	 * Feature's initialization.
	 *
	 * @since 15.0.0
	 *
	 * @param string $id           A unique string to use as the feature's ID.
	 * @param string $component_id The unique string used as the component's ID the feature applies to.
	 * @param array  $params {
	 *     Additional parameters used by the component's feature.
	 *
	 *     @type int $adminbar_myaccount_order Set the position for our menu under the WP Toolbar's "My Account menu".
	 * }
	 */
	public function init( $id, $component_id, $params = array() ) {
		$this->id   = $id;
		$this->slug = $id;

		if ( ! bp_is_active( $component_id ) ) {
			_doing_it_wrong(
				__CLASS__ . '::' . __METHOD__,
				sprintf(
					/* Translators: %s is the component ID. */
					esc_html__( 'This feature needs the %s component to be active.', 'buddypress' ),
					esc_html( $component_id )
				),
				'15.0.0'
			);
		} else {
			$this->component_id             = $component_id;
			$this->path                     = trailingslashit( buddypress()->{$this->component_id}->path ) . 'bp-' . $this->component_id;

			// Sets the position for our menu under the WP Toolbar's "My Account" menu.
			if ( ! empty( $params['adminbar_myaccount_order'] ) ) {
				$this->adminbar_myaccount_order = (int) $params['adminbar_myaccount_order'];
			}

			// Do some clean-up.
			foreach ( array_keys( get_class_vars( 'BP_Component' ) ) as $key ) {
				if ( in_array( $key, array( 'id', 'component_id', 'slug', 'main_nav', 'sub_nav', 'path', 'adminbar_myaccount_order' ), true ) ) {
					continue;
				}

				unset( $this->{$key} );
			}

			// Move on to the next step.
			$this->setup_actions();
		}
	}

	/**
	 * Set up component’s feature global variables.
	 *
	 * @since 15.0.0
	 */
	public function globals() {
		/**
		 * Fires at the end of the `globals` method.
		 *
		 * This is a dynamic hook that is based on the component & feature string IDs.
		 *
		 * @since 15.0.0
		 */
		do_action( 'bp_' . $this->component_id . '_' . $this->id . '_globals' );
	}

	/**
	 * Include required files.
	 *
	 * @since 15.0.0
	 *
	 * @param array $includes An array of file names.
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( $this->path );

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {
			$php_file = $slashed_path . rtrim( $file, '.php' ) . '.php';

			if ( ! file_exists( $php_file ) ) {
				continue;
			}

			require_once $php_file;
		}

		/**
		 * Fires at the end of the `includes` method.
		 *
		 * This is a dynamic hook that is based on the component & feature string IDs.
		 *
		 * @since 15.0.0
		 */
		do_action( 'bp_' . $this->component_id . '_' . $this->id . '_includes' );
	}

	/**
	 * Set up action hooks for the Feature.
	 *
	 * @since 15.0.0
	 */
	public function setup_actions() {
		// Setup globals.
		add_action( 'bp_' . $this->component_id . '_setup_globals', array( $this, 'globals' ) );

		// Include required files.
		add_action( 'bp_' . $this->component_id . '_includes', array( $this, 'includes' ) );

		// Load files conditionally, based on certain pages.
		add_action( 'bp_late_include', array( $this, 'late_includes' ), 11 );

		// Register feature's navigation.
		add_action( 'bp_register_nav', array( $this, 'register_nav' ) );

		// Setup feature's navigation.
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ) );

		// Setup WP Toolbar menus.
		add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), $this->adminbar_myaccount_order );

		// Setup cache groups.
		add_action( 'bp_setup_cache_groups', array( $this, 'setup_cache_groups' ) );

		// Register BP REST Endpoints.
		if ( bp_rest_in_buddypress() && bp_rest_api_is_available() ) {
			add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ) );
		}

		// Register BP Blocks.
		if ( bp_support_blocks() ) {
			add_action( 'bp_blocks_init', array( $this, 'blocks_init' ) );
		}

		/**
		 * Fires at the end of the `setup_actions` method.
		 *
		 * This is a dynamic hook that is based on the component & feature string IDs.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Component_Feature $feature_object The Feature object.
		 */
		do_action( 'bp_' . $this->component_id . '_' . $this->id . '_setup_actions', $this );
	}
}
