<?php
/**
 * BuddyPress Component's feature Class.
 *
 * @package buddypress\bp-core\classes\class-bp-component-feature
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 */
	public function init( $id, $component_id ) {
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
			$this->component_id = $component_id;
			$this->path         = trailingslashit( buddypress()->{$this->component_id}->path ) . 'bp-' . $this->component_id;

			// Do some clean-up.
			foreach ( array_keys( get_class_vars( 'BP_Component' ) ) as $key ) {
				if ( in_array( $key, array( 'id', 'component_id', 'slug', 'main_nav', 'sub_nav', 'path' ), true ) ) {
					continue;
				}

				unset( $this->{$key} );
			}

			// Move on to the next step.
			$this->setup_actions();
		}
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
		// Include required files.
		add_action( 'bp_' . $this->component_id . '_includes', array( $this, 'includes' ) );

		// Load files conditionally, based on certain pages.
		add_action( 'bp_late_include', array( $this, 'late_includes' ), 11 );

		// Register feature's navigation.
		add_action( 'bp_register_nav', array( $this, 'register_nav' ) );

		// Setup feature's navigation.
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ) );

		// Setup WP Toolbar menus.
		add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ) );

		// Register BP REST Endpoints.
		if ( bp_rest_in_buddypress() && bp_rest_api_is_available() ) {
			add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
		}

		// Register BP Blocks.
		if ( bp_support_blocks() ) {
			add_action( 'bp_blocks_init', array( $this, 'blocks_init' ), 10 );
		}

		/**
		 * Fires at the end of the `setup_actions` method.
		 *
		 * This is a dynamic hook that is based on the component & feature string IDs.
		 *
		 * @since 15.0.0
		 */
		do_action( 'bp_' . $this->component_id . '_' . $this->id . '_setup_actions' );
	}
}
