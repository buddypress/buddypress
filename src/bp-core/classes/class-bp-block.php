<?php
/**
 * BP Block class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 6.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Block Class.
 *
 * @since 6.0.0
 */
class BP_Block {
	/**
	 * WP Block Type object.
	 *
	 * @since 6.0.0
	 * @var WP_Block_Type|WP_Error
	 */
	public $block;

	/**
	 * The script types registered.
	 *
	 * @since 6.0.0
	 * @var array
	 */
	private $registered_scripts;

	/**
	 * The style types registered.
	 *
	 * @since 6.0.0
	 * @var array
	 */
	private $registered_styles;

	/**
	 * Construct the BuddyPress Block.
	 *
	 * @since 6.0.0
	 *
	 * @param array $args The registration arguments for the BP Block.
	 */
	public function __construct( $args ) {
		if ( ! did_action( 'bp_blocks_init' ) ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'BP Blocks needs to be registered hooking `bp_blocks_init`', 'buddypress' ), '6.0.0' );
		}

		$min     = bp_core_get_minified_asset_suffix();
		$wp_args = array_intersect_key(
			$args,
			array(
				'name'            => '',
				'render_callback' => '',
				'attributes'      => '',
				'editor_script'   => '',
				'script'          => '',
				'editor_style'    => '',
				'style'           => '',
			)
		);

		if ( ! isset( $wp_args['name'] ) || ! $wp_args['name'] || ! isset( $wp_args['editor_script'] ) || ! $wp_args['editor_script'] ) {
			$this->block = new WP_Error( 'missing_parameters', __( 'The `name` or `editor_script` required keys are missing.', 'buddypress' ) );
		} else {
			// Get specific BP Blocks arguments.
			$bp_args = array_intersect_key(
				$args,
				array(
					'editor_script_url'  => '',
					'editor_script_deps' => array(),
					'script_url'         => '',
					'script_deps'        => array(),
					'editor_style_url'   => '',
					'editor_style_deps'  => array(),
					'style_url'          => '',
					'style_deps'         => array(),
				)
			);

			// Register the scripts.
			$version                  = bp_get_version();
			$this->registered_scripts = array();

			foreach ( array( 'editor_script', 'script' ) as $script_handle_key ) {
				if ( ! isset( $wp_args[ $script_handle_key ] ) || ! $wp_args[ $script_handle_key ] ) {
					continue;
				}

				if ( ! isset( $bp_args[ $script_handle_key . '_url' ] ) || ! $bp_args[ $script_handle_key . '_url' ] ) {
					continue;
				}

				$deps = array();
				if ( isset( $bp_args[ $script_handle_key . '_deps' ] ) && is_array( $bp_args[ $script_handle_key . '_deps' ] ) ) {
					$deps = $bp_args[ $script_handle_key . '_deps' ];
				}

				$this->registered_scripts[ $script_handle_key ] = wp_register_script(
					$wp_args[ $script_handle_key ],
					$bp_args[ $script_handle_key . '_url' ],
					$deps,
					$version,
					true
				);
			}

			if ( ! isset( $this->registered_scripts['editor_script'] ) || ! $this->registered_scripts['editor_script'] ) {
				$this->block = new WP_Error( 'script_registration_error', __( 'The required `editor_script` could not be registered.', 'buddypress' ) );
			} else {
				// Register the styles.
				$registered_styles = array();

				foreach ( array( 'editor_style', 'style' ) as $style_handle_key ) {
					if ( ! isset( $wp_args[ $style_handle_key ] ) || ! $wp_args[ $style_handle_key ] ) {
						continue;
					}

					if ( ! isset( $bp_args[ $style_handle_key . '_url' ] ) || ! $bp_args[ $style_handle_key . '_url' ] ) {
						continue;
					}

					if ( $min ) {
						$minified_css  = str_replace( '.css', $min . '.css', $bp_args[ $style_handle_key . '_url' ] );
						$css_file_path = str_replace( content_url(), WP_CONTENT_DIR, $minified_css );

						if ( file_exists( $css_file_path ) ) {
							$bp_args[ $style_handle_key . '_url' ] = $minified_css;
						}
					}

					$deps = array();
					if ( isset( $bp_args[ $style_handle_key . '_deps' ] ) && is_array( $bp_args[ $style_handle_key . '_deps' ] ) ) {
						$deps = $bp_args[ $style_handle_key . '_deps' ];
					}

					$this->registered_styles[ $style_handle_key ] = wp_register_style(
						$wp_args[ $style_handle_key ],
						$bp_args[ $style_handle_key . '_url' ],
						$deps,
						$version
					);

					wp_style_add_data( $wp_args[ $style_handle_key ], 'rtl', 'replace' );
					if ( $min ) {
						wp_style_add_data( $wp_args[ $style_handle_key ], 'suffix', $min );
					}
				}

				$name = $wp_args['name'];
				unset( $wp_args['name'] );

				// Set the Block Type.
				$this->block = new WP_Block_Type( $name, $wp_args );

				// Register the Block Type.
				register_block_type( $this->block );

				// Load Block translations if found.
				if ( $this->block->editor_script ) {
					/**
					 * Filter here to use a custom directory to look for the JSON translation file into.
					 *
					 * @since 6.0.0
					 *
					 * @param string $value         Absolute path to the directory to look for the JSON translation file into.
					 * @param string $editor_script The editor's script handle.
					 * @param string $name          The block's name.
					 */
					$translation_dir = apply_filters( 'bp_block_translation_dir', null, $this->block->editor_script, $name );

					/**
					 * Filter here to use a custom domain for the JSON translation file.
					 *
					 * @since 6.0.0
					 *
					 * @param string $value         The custom domain for the JSON translation file.
					 * @param string $editor_script The editor's script handle.
					 * @param string $name          The block's name.
					 */
					$translation_domain = apply_filters( 'bp_block_translation_domain', 'buddypress', $this->block->editor_script, $name );

					// Try to load the translation.
					$translated = wp_set_script_translations( $this->block->editor_script, $translation_domain, $translation_dir );
				}
			}
		}
	}
}
