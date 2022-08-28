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
	 * @since 11.0.0 Add support for WP Block API v2 { apiVersion: 2 }.
	 *
	 * @param array $args {
	 *     The registration arguments for the BP Block. Part of the arguments are the ones
	 *     used by `WP_Block_Type`. Below are BP specific arguments.
	 *
	 *     @type string $editor_script_url   URL to the JavaScript main file of the BP Block
	 *                                       to load into the Block Editor.
	 *     @type array  $editor_script_deps  The list of JavaScript dependency handles for the
	 *                                       BP Block main file.
	 *     @type string $script_url          URL to the JavaScript file to load into the Block
	 *                                       Editor and on front-end.
	 *     @type array  $script_deps         The list of JavaScript dependency handles for the
	 *                                       JavaScript file to load into the Block Editor and
	 *                                       on front-end.
	 *     @type string $view_script_url     URL to the JavaScript file to load on front-end.
	 *     @type array  $view_script_deps    The list of JavaScript dependency handles for the
	 *                                       JavaScript file to load on front-end.
	 *     @type string $editor_style_url    URL to the CSS main file of the BP Block to load
	 *                                       into the Block Editor.
	 *     @type array  $editor_style_deps   The list of CSS dependency handles for the
	 *                                       CSS main file.
	 *     @type string $style_url           URL to the CSS file to load into the Block Editor
	 *                                       and on front-end.
	 *     @type array  $style_deps          The list of CSS dependency handles for the CSS file
	 *                                       to load into the Block Editor and on front-end.
	 *     @type string $domain_path         The path to the folder where custom block translations
	 *                                       are located.
	 *     @type array  $buddypress_contexts The list of BuddyPress contexts a block can be loaded into.
	 * }
	 */
	public function __construct( $args ) {
		if ( ! did_action( 'bp_blocks_init' ) ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'BP Blocks needs to be registered hooking `bp_blocks_init`', 'buddypress' ), '6.0.0' );
		}

		$min          = bp_core_get_minified_asset_suffix();
		$metadata_map = array(
			'ancestor'        => 'ancestor',
			'apiVersion'      => 'api_version',
			'attributes'      => 'attributes',
			'category'        => 'category',
			'description'     => 'description',
			'editorScript'    => 'editor_script',
			'editorStyle'     => 'editor_style',
			'example'         => 'example',
			'icon'            => 'icon',
			'keywords'        => 'keywords',
			'name'            => 'name',
			'parent'          => 'parent',
			'providesContext' => 'provides_context',
			'script'          => 'script',
			'style'           => 'style',
			'styles'          => 'styles',
			'supports'        => 'supports',
			'textdomain'      => 'textdomain',
			'title'           => 'title',
			'usesContext'     => 'uses_context',
			'variations'      => 'variations',
			'version'         => 'version',
			'viewScript'      => 'view_script',
		);

		// Init WordPress Block $args.
		$wp_args = array();

		// rekey $args.
		foreach ( $args as $arg_key => $arg ) {
			$snake_case_key = '';

			if ( isset( $metadata_map[ $arg_key ] ) ) {
				$snake_case_key             = $metadata_map[ $arg_key ];
				$wp_args[ $snake_case_key ] = $arg;
			} elseif ( in_array( $arg_key, $metadata_map, true ) ) {
				$wp_args[ $arg_key ] = $arg;
			}
		}

		if ( isset( $args['render_callback'] ) && $args['render_callback'] ) {
			$wp_args['render_callback'] = $args['render_callback'];
		}

		if ( ! isset( $wp_args['name'] ) || ! $wp_args['name'] || ! isset( $wp_args['editor_script'] ) || ! $wp_args['editor_script'] ) {
			$this->block = new WP_Error( 'missing_parameters', __( 'The `name` or `editor_script` required keys are missing.', 'buddypress' ) );
		} else {
			if ( isset( $wp_args['api_version'], $args['plugin_url'] ) && 2 === (int) $wp_args['api_version'] ) {
				foreach ( array( 'editor_script', 'editor_style', 'script', 'style', 'view_script' ) as $asset_key ) {
					if ( ! isset( $wp_args[ $asset_key ] ) ) {
						continue;
					}

					$asset_abs_uri_key          = $asset_key . '_url';
					$args[ $asset_abs_uri_key ] = trailingslashit( $args['plugin_url'] ) . remove_block_asset_path_prefix( $wp_args[ $asset_key ] );
					$args[ $asset_key ]         = str_replace( '/', '-', $wp_args['name'] ) . '-' . str_replace( '_', '-', $asset_key );
					$wp_args[ $asset_key ]      = $args[ $asset_key ];
				}
			}

			// Get specific BP Blocks arguments.
			$bp_args = array_intersect_key(
				$args,
				array(
					'editor_script_url'   => '',
					'editor_script_deps'  => array(),
					'script_url'          => '',
					'script_deps'         => array(),
					'view_script_url'     => '',
					'view_script_deps'    => array(),
					'editor_style_url'    => '',
					'editor_style_deps'   => array(),
					'style_url'           => '',
					'style_deps'          => array(),
					'domain_path'         => null,
					'buddypress_contexts' => array(),
				)
			);

			// Register the scripts.
			$this->registered_scripts = array();
			$version                  = bp_get_version();
			if ( isset( $wp_args['version'] ) && $wp_args['version'] ) {
				$version = $wp_args['version'];
			}

			foreach ( array( 'editor_script', 'script', 'view_script' ) as $script_handle_key ) {
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

				// Used to restrict blocks to specific BuddyPress contexts.
				if ( isset( $bp_args['buddypress_contexts'] ) ) {
					$wp_args['buddypress_contexts'] = $bp_args['buddypress_contexts'];
				}

				// Set the Block Type.
				$this->block = new WP_Block_Type( $name, $wp_args );

				// Register the Block Type.
				register_block_type( $this->block );

				// Load Block translations if found.
				if ( $this->block->editor_script ) {
					$domain_path = null;
					if ( isset( $bp_args['domain_path'] ) && is_dir( $bp_args['domain_path'] ) ) {
						$domain_path = $bp_args['domain_path'];
					}

					/**
					 * Filter here to use a custom directory to look for the JSON translation file into.
					 *
					 * @since 6.0.0
					 *
					 * @param string $domain_path   Absolute path to the directory to look for the JSON translation file into.
					 * @param string $editor_script The editor's script handle.
					 * @param string $name          The block's name.
					 */
					$translation_dir = apply_filters( 'bp_block_translation_dir', $domain_path, $this->block->editor_script, $name );

					$textdomain = 'buddypress';
					if ( isset( $wp_args['textdomain'] ) && $wp_args['textdomain'] ) {
						$textdomain = $wp_args['textdomain'];
					}

					/**
					 * Filter here to use a custom domain for the JSON translation file.
					 *
					 * @since 6.0.0
					 *
					 * @param string $textdomain    The custom domain for the JSON translation file.
					 * @param string $editor_script The editor's script handle.
					 * @param string $name          The block's name.
					 */
					$translation_domain = apply_filters( 'bp_block_translation_domain', $textdomain, $this->block->editor_script, $name );

					// Try to load the translation.
					$translated = wp_set_script_translations( $this->block->editor_script, $translation_domain, $translation_dir );
				}
			}
		}
	}
}
