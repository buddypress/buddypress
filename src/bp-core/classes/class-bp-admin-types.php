<?php
/**
 * BuddyPress Types Admin Class.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 7.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BP_Admin_Types' ) ) :

/**
 * Load BuddyPress Types admin area.
 *
 * @since 7.O.0
 */
class BP_Admin_Types {
	/**
	 * Current BuddyPress taxonomy.
	 *
	 * @since 7.0.0
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * All registered BuddyPress taxonomies.
	 *
	 * @since 7.0.0
	 * @var array()
	 */
	public $taxonomies = array();

	/**
	 * Current screen ID.
	 *
	 * @since 7.0.0
	 * @var string
	 */
	public $screen_id = '';

	/**
	 * The main BuddyPress Types admin loader.
	 *
	 * @since 7.0.0
	 */
	public function __construct() {
		$this->setup_globals();

		if ( $this->taxonomy && $this->screen_id ) {
			$this->includes();
			$this->setup_hooks();

			if ( isset( $_POST['action'] ) || isset( $_GET['action'] ) ) {
				if ( isset( $_GET['action'] ) ) {
					$action = wp_unslash( $_GET['action'] );
				} else {
					$action = wp_unslash( $_POST['action'] );
				}

				$this->handle_action( $action );
			}
		}
	}

	/**
	 * Register BP Types Admin.
	 *
	 * @since 7.0.0
	 *
	 * @return BP_Admin_Types
	 */
	public static function register_types_admin() {
		if ( ! is_admin() ) {
			return;
		}

		$bp = buddypress();

		if ( empty( $bp->core->types_admin ) ) {
			$bp->core->types_admin = new self;
		}

		return $bp->core->types_admin;
	}

	/**
	 * Set the globals.
	 *
	 * @since 7.0.0
	 */
	private function setup_globals() {
		$current_screen = get_current_screen();

		if ( isset( $current_screen->taxonomy ) && $current_screen->taxonomy ) {
			$this->taxonomies = bp_get_default_taxonomies();

			if ( isset( $this->taxonomies[ $current_screen->taxonomy ] ) ) {
				$this->taxonomy  = $current_screen->taxonomy;
				$this->screen_id = $current_screen->id;
			}
		}
	}

	/**
	 * Include Admin functions.
	 *
	 * @since 7.0.0
	 */
	private function includes() {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/bp-core-admin-types.php';
	}

	/**
	 * Set hooks.
	 *
	 * @since 7.0.0
	 */
	private function setup_hooks() {
		// Actions.
		add_action( 'admin_head-edit-tags.php', array( $this, 'screen_head' ) );
		add_action( 'admin_head-term.php', array( $this, 'screen_head' ) );
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'screen_scripts' ) );
		add_action( "{$this->taxonomy}_add_form_fields", array( $this, 'add_form_fields' ), 10, 1 );
		add_action( "{$this->taxonomy}_edit_form_fields", array( $this, 'edit_form_fields' ), 10, 2 );

		// Filters
		add_filter( 'bp_core_admin_register_scripts', array( $this, 'register_scripts' ) );
		add_filter( "manage_{$this->screen_id}_columns", array( $this, 'column_headers' ), 10, 1 );
		add_filter( "manage_{$this->taxonomy}_custom_column", array( $this, 'column_contents' ), 10, 3 );
		add_filter( "{$this->taxonomy}_row_actions", array( $this, 'row_actions' ), 10, 2 );
		add_filter( "bulk_actions-{$this->screen_id}", '__return_empty_array', 10, 1 );
	}

	/**
	 * Handle BP Type actions.
	 *
	 * @since 7.0.0
	 *
	 * @param string $action Required. The action to handle ('add-tag', 'editedtag' or 'delete' ).
	 */
	private function handle_action( $action ) {
		$referer = wp_get_referer();

		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}

		// Adding a new type into the database.
		if ( 'add-tag' === $action ) {
			check_admin_referer( 'add-tag', '_wpnonce_add-tag' );

			$result = bp_core_admin_insert_type( $_POST );

			if ( is_wp_error( $result ) ) {
				$referer = add_query_arg(
					array_merge(
						$result->get_error_data(),
						array(
							'error' => 1,
						)
					),
					$referer
				);

				wp_safe_redirect( $referer );
				exit;
			}

			wp_safe_redirect( add_query_arg( 'message', 2, $referer ) );
			exit;

			// Updating an existing type intot the Database.
		} elseif ( 'editedtag' === $action ) {
			$args                 = $_POST;
			$args['type_term_id'] = 0;
			unset( $args['tag_ID'] );

			if ( isset( $_POST['tag_ID'] ) ) {
				$args['type_term_id'] = $_POST['tag_ID'];
			}

			if ( isset( $_POST['taxonomy'] ) ) {
				$args['taxonomy'] = $_POST['taxonomy'];
			}

			check_admin_referer( 'update-tag_' . $args['type_term_id'] );

			$result = bp_core_admin_update_type( $args );

			if ( is_wp_error( $result ) ) {
				$referer = add_query_arg(
					array_merge(
						$result->get_error_data(),
						array(
							'error' => 1,
						)
					),
					$referer
				);

				wp_safe_redirect( $referer );
				exit;
			}

			wp_safe_redirect( add_query_arg( 'message', 4, $referer ) );
			exit;

			// Deletes a type.
		} elseif ( 'delete' === $action ) {
			$args                 = $_GET;
			$args['type_term_id'] = 0;
			unset( $args['tag_ID'] );

			if ( isset( $_GET['tag_ID'] ) ) {
				$args['type_term_id'] = $_GET['tag_ID'];
			}

			if ( isset( $_GET['taxonomy'] ) ) {
				$args['taxonomy'] = $_GET['taxonomy'];
			}

			check_admin_referer( 'delete-tag_' . $args['type_term_id'] );
			$referer = remove_query_arg( array( 'action', 'tag_ID', '_wpnonce' ), $referer );

			// Delete the type.
			$result = bp_core_admin_delete_type( $args );

			if ( is_wp_error( $result ) ) {
				$referer = add_query_arg(
					array_merge(
						$result->get_error_data(),
						array(
							'error' => 1,
						)
					),
					$referer
				);

				wp_safe_redirect( $referer );
				exit;
			}

			wp_safe_redirect( add_query_arg( 'message', 9, $referer ) );
			exit;
		}
	}

	/**
	 * Override the Admin parent file to highlight the right menu.
	 *
	 * @since 7.0.0
	 */
	public function screen_head() {
		global $parent_file;

		if ( 'members' === $this->taxonomies[ $this->taxonomy ]['component'] ) {
			$parent_file = 'users.php';
		} else {
			$parent_file = 'bp-' . $this->taxonomies[ $this->taxonomy ]['component'];
		}
	}

	/**
	 * Registers script.
	 *
	 * @since 7.0.0
	 */
	public function register_scripts( $scripts = array() ) {
		// Neutralize WordPress Taxonomy scripts.
		wp_dequeue_script( 'admin-tags' );
		wp_dequeue_script( 'inline-edit-tax' );

		// Adapt some styles.
		wp_add_inline_style(
			'common',
			'.form-field:not(.bp-types-form), .term-bp_type_directory_slug-wrap:not(.bp-set-directory-slug), .edit-tag-actions #delete-link { display: none; }'
		);

		// Register the Types admin script.
		return array_merge(
			$scripts,
			array(
				'bp-admin-types' => array(
					'file'         => sprintf(
						'%1$sadmin/js/types-admin%2$s.js',
						plugin_dir_url( dirname( __FILE__ ) ),
						bp_core_get_minified_asset_suffix()
					),
					'dependencies' => array(),
					'footer'       => true,
				),
			)
		);
	}

	/**
	 * Enqueues script.
	 *
	 * @since 7.0.0
	 */
	public function screen_scripts() {
		wp_enqueue_script( 'bp-admin-types' );
	}

	/**
	 * Outputs the BP type add form.
	 *
	 * @since 7.0.0
	 *
	 * @param string      $taxonomy The type taxonomy name.
	 * @param null|object $type     The type object, `null` if not passed to the method.
	 */
	public function add_form_fields( $taxonomy = '', $type = null ) {
		$taxonomy_object = get_taxonomy( $taxonomy );
		$labels          = get_taxonomy_labels( $taxonomy_object );

		// Default values for the Type ID field.
		$type_id_label   = __( 'Type ID', 'buddypress' );
		$type_id_desc    = __( 'Enter a lower-case string without spaces or special characters (used internally to identify the type).', 'buddypress' );

		if ( isset( $labels->bp_type_id_label ) && $labels->bp_type_id_label ) {
			$type_id_label = $labels->bp_type_id_label;
		}

		if ( isset( $labels->bp_type_id_description ) && $labels->bp_type_id_description ) {
			$type_id_desc = $labels->bp_type_id_description;
		}

		// Outputs the Type ID field.
		if ( isset( $type->name ) ) {
			printf(
				'<tr class="form-field bp-types-form form-required term-bp_type_id-wrap">
					<th scope="row"><label for="bp_type_id">%1$s</label></th>
					<td>
						<input name="bp_type_id" id="bp_type_id" type="text" value="%2$s" size="40" disabled="disabled">
					</td>
				</tr>',
				esc_html( $type_id_label ),
				esc_attr( $type->name ),
				esc_html( $type_id_desc )
			);
		} else {
			printf(
				'<div class="form-field bp-types-form form-required term-bp_type_id-wrap">
					<label for="bp_type_id">%1$s</label>
					<input name="bp_type_id" id="bp_type_id" type="text" value="" size="40" aria-required="true">
					<p>%2$s</p>
				</div>',
				esc_html( $type_id_label ),
				esc_html( $type_id_desc )
			);
		}

		// Gets the Type's metadata.
		$metafields = get_registered_meta_keys( 'term', $taxonomy );

		foreach ( $metafields as $meta_key => $meta_schema ) {
			if ( ! isset( $labels->{ $meta_key } ) || ! $labels->{ $meta_key } ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Type metadata labels need to be set into the labels argument when registering your taxonomy using the meta key as the label’s key.', 'buddypress' )
					. ' ' .
					sprintf(
						/* translators: %s is the name of the Type meta key */
						__( 'As a result, the form elements for the "%s" meta key cannot be displayed', 'buddypress' ), $meta_key ),
					'7.0.0'
				);
				continue;
			}

			$type_key = str_replace( 'bp_type_', '', $meta_key );

			if ( 'string' === $meta_schema['type'] ) {
				if ( isset( $type->name ) ) {
					$type_prop_value = null;
					if ( in_array( $type_key, array( 'name', 'singular_name' ), true ) ) {
						if ( isset( $type->labels[ $type_key ] ) ) {
							$type_prop_value = $type->labels[ $type_key ];
						}

					} elseif ( isset( $type->{$type_key} ) ) {
						$type_prop_value = $type->{$type_key};
					}

					printf(
						'<tr class="form-field bp-types-form form-required term-%1$s-wrap">
							<th scope="row"><label for="%1$s">%2$s</label></th>
							<td>
								<input name="%1$s" id="%1$s" type="text" value="%3$s" size="40" aria-required="true">
								<p class="description">%4$s</p>
							</td>
						</tr>',
						esc_attr( $meta_key ),
						esc_html( $labels->{ $meta_key } ),
						esc_attr( $type_prop_value ),
						esc_html( $meta_schema['description'] )
					);

				} else {
					printf(
						'<div class="form-field bp-types-form form-required term-%1$s-wrap">
							<label for="%1$s">%2$s</label>
							<input name="%1$s" id="%1$s" type="text" value="" size="40">
							<p>%3$s</p>
						</div>',
						esc_attr( $meta_key ),
						esc_html( $labels->{ $meta_key } ),
						esc_html( $meta_schema['description'] )
					);
				}
			} else {
				if ( isset( $type->name ) ) {
					$checked = '';
					if ( isset( $type->{$type_key} ) && true === (bool) $type->{$type_key} ) {
						$checked = ' checked="checked"';
					}

					printf(
						'<tr class="form-field bp-types-form term-%1$s-wrap">
							<th scope="row"><label for="%1$s">%2$s</label></th>
							<td>
								<input name="%1$s" id="%1$s" type="checkbox" value="1"%3$s> %4$s
								<p class="description">%5$s</p>
							</td>
						</tr>',
						esc_attr( $meta_key ),
						esc_html( $labels->{ $meta_key } ),
						$checked,
						esc_html__( 'Yes', 'buddypress' ),
						esc_html( $meta_schema['description'] )
					);
				} else {
					printf(
						'<div class="form-field bp-types-form term-%1$s-wrap">
							<label for="%1$s">
								<input name="%1$s" id="%1$s" type="checkbox" value="1"> %2$s
							</label>
							<p>%3$s</p>
						</div>',
						esc_attr( $meta_key ),
						esc_html( $labels->{ $meta_key } ),
						esc_html( $meta_schema['description'] )
					);
				}
			}
		}
	}

	/**
	 * Outputs the BP type edit form.
	 *
	 * @since 7.0.0
	 *
	 * @param WP_Term $term     The term object for the BP Type.
	 * @param string  $taxonomy The type taxonomy name.
	 * @return string           HTML Output.
	 */
	public function edit_form_fields( $term = null, $taxonomy = '' ) {
		if ( ! isset( $term->name ) || ! $term->name || ! $taxonomy ) {
			return;
		}

		$type         = new stdClass();
		$type->name   = $term->name;
		$type->labels = array();
		$metadatas    = get_metadata( 'term', $term->term_id );

		foreach ( $metadatas as $meta_key => $meta_values ) {
			$meta_value = reset( $meta_values );
			$type_key   = str_replace( 'bp_type_', '', $meta_key );

			if ( in_array( $type_key, array( 'name', 'singular_name' ), true ) ) {
				$type->labels[ $type_key ] = $meta_value;
			} else {
				$type->{$type_key} = $meta_value;
			}
		}

		return $this->add_form_fields( $taxonomy, $type );
	}

	/**
	 * Filters the terms list table column headers to customize them for BuddyPress Types.
	 *
	 * @since 7.0.0
	 *
	 * @param array  $column_headers The column header labels keyed by column ID.
	 * @return array                 The column header labels keyed by column ID.
	 */
	public function column_headers( $column_headers = array() ) {
		if ( isset( $column_headers['name'] ) ) {
			$column_headers['name'] = __( 'Type ID', 'buddypress' );
		}

		unset( $column_headers['cb'], $column_headers['description'], $column_headers['posts'] );

		$column_headers['plural_name'] = __( 'Name', 'buddypress' );
		$column_headers['counts']      = _x( 'Count', 'Number/count of types', 'buddypress' );

		return $column_headers;
	}

	/**
	 * Sets the content for the Plural name & Counts columns.
	 *
	 * @since 7.0.0
	 *
	 * @param string  $string      Blank string.
	 * @param string  $column_name Name of the column.
	 * @param int     $type_id     The type's term ID.
	 * @return string              The Type Plural name.
	 */
	public function column_contents( $column_content = '', $column_name = '', $type_id = 0 ) {
		if ( 'plural_name' !== $column_name && 'counts' !== $column_name || ! $type_id ) {
			return $column_content;
		}

		// Set the Plural name column.
		if ( 'plural_name' === $column_name ) {
			$type_plural_name = get_term_meta( $type_id, 'bp_type_name', true );

			// Plural name meta is not set? Let's check register by code types!
			if ( ! $type_plural_name ) {
				$type_name = get_term_field( 'name', $type_id, $this->taxonomy );

				/**
				 * Filter here to set missing term meta for registered by code types.
				 *
				 * @see bp_set_registered_by_code_member_type_metadata() for an example of use.
				 *
				 * @since 7.0.0
				 *
				 * @param string $value Metadata for the BP Type.
				 */
				$metadata = apply_filters( "{$this->taxonomy}_set_registered_by_code_metada", array(), $type_name );

				if ( isset( $metadata['bp_type_name'] ) ) {
					$type_plural_name = $metadata['bp_type_name'];
				}
			}

			echo esc_html( $type_plural_name );

			// Set the Totals column.
		} elseif ( 'counts' === $column_name ) {
			global $parent_file;
			$type  = bp_get_term_by( 'id', $type_id, $this->taxonomy );
			if ( 0 === (int) $type->count ) {
				return 0;
			}

			// Format the count.
			$count = number_format_i18n( $type->count );

			$args = array(
				str_replace( '_', '-', $this->taxonomy ) => $type->slug,
			);

			$base_url = $parent_file;
			if ( false === strpos( $parent_file, '.php' ) ) {
				$base_url = add_query_arg( 'page', $parent_file, 'admin.php' );
			}

			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( add_query_arg( $args, bp_get_admin_url( $base_url ) ) ),
				esc_html( $count )
			);
		}
	}

	/**
	 * Customizes the Types Admin list table row actions.
	 *
	 * @since 7.0.0
	 *
	 * @param array   $actions The table row actions.
	 * @param WP_Term $type    The current BP Type for the row.
	 * @return array           The table row actions for the current BP type.
	 */
	public function row_actions( $actions = array(), $type = null ) {
		if ( ! isset( $type->taxonomy ) || ! $type->taxonomy ) {
			return $actions;
		}

		/**
		 * Filter here to set the types "registered by code".
		 *
		 * @see bp_get_member_types_registered_by_code() for an example of use.
		 *
		 * @since 7.0.0
		 */
		$registered_by_code_types = apply_filters( "{$type->taxonomy}_registered_by_code", array() );

		// Types registered by code cannot be deleted as long as the custom registration code exists.
		if ( isset( $registered_by_code_types[ $type->name ] ) ) {
			unset( $actions['delete'] );
		}

		// Inline edits are disabled for all types.
		unset( $actions['inline hide-if-no-js'] );

		// Removes the post type query argument for the edit action.
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = str_replace( '&#038;post_type=post', '', $actions['edit'] );
		}

		return $actions;
	}
}

endif;
