<?php
/**
 * BuddyPress Opt-outs List Table class.
 *
 * @package BuddyPress
 * @subpackage CoreAdminClasses
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for nonmember opt-outs admin page.
 *
 * @since 8.0.0
 */
class BP_Optouts_List_Table extends WP_Users_List_Table {

	/**
	 * Opt-out count.
	 *
	 * @since 8.0.0
	 *
	 * @var int
	 */
	public $total_items = 0;

	/**
	 * Constructor.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct(
			array(
				'ajax'     => false,
				'plural'   => 'optouts',
				'singular' => 'optout',
				'screen'   => get_current_screen()->id,
			)
		);
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 8.0.0
	 */
	public function prepare_items() {
		$search   = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );
		$paged    = $this->get_pagenum();

		$args = array(
			'search_terms' => $search,
			'order_by'     => 'date_modified',
			'sort_order'   => 'DESC',
			'page'         => $paged,
			'per_page'     => $per_page,
		);

		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['order_by'] = $_REQUEST['orderby'];
		}

		if ( isset( $_REQUEST['order'] ) ) {
			$args['sort_order'] = $_REQUEST['order'];
		}

		$this->items       = bp_get_optouts( $args );
		$optouts_class     = new BP_Optout();
		$this->total_items = $optouts_class->get_total_count( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 10.1.0
	 *
	 * @return string Name of the default primary column, in this case, 'email_address'.
	 */
	protected function get_default_primary_column_name() {
		return 'email_address';
	}

	/**
	 * Get the list of views available on this table.
	 *
	 * @since 8.0.0
	 */
	public function views() {
		if ( is_multisite() && bp_core_do_network_admin() ) {
			$tools_parent = 'admin.php';
		} else {
			$tools_parent = 'tools.php';
		}

		$url_base = add_query_arg(
			array(
				'page' => 'bp-optouts',
			),
			bp_get_admin_url( $tools_parent )
		);
		?>

		<h2 class="screen-reader-text">
			<?php
				/* translators: accessibility text */
				esc_html_e( 'Filter opt-outs list', 'buddypress' );
			?>
		</h2>
		<ul class="subsubsub">
			<?php
			/**
			 * Fires inside listing of views so plugins can add their own.
			 *
			 * @since 8.0.0
			 *
			 * @param string $url_base Current URL base for view.
			 */
			do_action( 'bp_optouts_list_table_get_views', $url_base ); ?>
		</ul>
	<?php
	}

	/**
	 * Get rid of the extra nav.
	 *
	 * WP_Users_List_Table will add an extra nav to change user's role.
	 * As we're dealing with opt-outs, we don't need this.
	 *
	 * @since 8.0.0
	 *
	 * @param array $which Current table nav item.
	 */
	public function extra_tablenav( $which ) {
		return;
	}

	/**
	 * Specific opt-out columns.
	 *
	 * @since 8.0.0
	 *
	 * @return array
	 */
	public function get_columns() {
		/**
		 * Filters the nonmember opt-outs columns.
		 *
		 * @since 8.0.0
		 *
		 * @param array $value Array of columns to display.
		 */
		return apply_filters(
			'bp_optouts_list_columns',
			array(
				'cb'                     => '<input type="checkbox" />',
				'email_address'          => __( 'Email Address Hash', 'buddypress' ),
				'username'               => __( 'Email Sender', 'buddypress' ),
				'user_registered'        => __( 'Email Sender Registered', 'buddypress' ),
				'email_type'             => __( 'Email Type', 'buddypress' ),
				'email_type_description' => __( 'Email Description', 'buddypress' ),
				'optout_date_modified'   => __( 'Date Modified', 'buddypress' ),
			)
		);
	}

	/**
	 * Specific bulk actions for opt-outs.
	 *
	 * @since 8.0.0
	 */
	public function get_bulk_actions() {
		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = _x( 'Delete', 'Optout database record action', 'buddypress' );
		}

		return $actions;
	}

	/**
	 * The text shown when no items are found.
	 *
	 * Nice job, clean sheet!
	 *
	 * @since 8.0.0
	 */
	public function no_items() {
		esc_html_e( 'No opt-outs found.', 'buddypress' );
	}

	/**
	 * The columns opt-outs can be reordered by.
	 *
	 * @since 8.0.0
	 */
	public function get_sortable_columns() {
		return array(
			'username'             => 'user_id',
			'email_type'           => 'email_type',
			'optout_date_modified' => 'date_modified',
		);
	}

	/**
	 * Display opt-out rows.
	 *
	 * @since 8.0.0
	 */
	public function display_rows() {
		$style = '';
		foreach ( $this->items as $optout ) {
			$style = 'alt' == $style ? '' : 'alt';

			// Escapes are made into `self::single_row()`.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo "\n\t" . $this->single_row( $optout, $style );
		}
	}

	/**
	 * Display an opt-out row.
	 *
	 * @since 8.0.0
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 *
	 * @param BP_Optout $optout   BP_Optout object.
	 * @param string    $style    Styles for the row.
	 * @param string    $role     Role to be assigned to user.
	 * @param int       $numposts Number of posts.
	 * @return void
	 */
	public function single_row( $optout = null, $style = '', $role = '', $numposts = 0 ) {
		if ( '' === $style ) {
			echo '<tr id="optout-' . intval( $optout->id ) . '">';
		} else {
			echo '<tr class="alternate" id="optout-' . intval( $optout->id ) . '">';
		}

		// BuddyPress relies on WordPress's `WP_Users_List_Table::single_row_columns()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->single_row_columns( $optout );
		echo '</tr>';
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_cb( $optout = null ) {
	?>
		<label class="screen-reader-text" for="optout_<?php echo intval( $optout->id ); ?>">
			<?php
				/* translators: %d: accessibility text. */
				printf( esc_html__( 'Select opt-out request: %d', 'buddypress' ), intval( $optout->id ) );
			?>
		</label>
		<input type="checkbox" id="optout_<?php echo intval( $optout->id ) ?>" name="optout_ids[]" value="<?php echo esc_attr( $optout->id ) ?>" />
		<?php
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_email_address( $optout = null ) {
		echo esc_html( $optout->email_address );

		$actions = array();

		if ( is_network_admin() ) {
			$form_url = network_admin_url( 'admin.php' );
		} else {
			$form_url = bp_get_admin_url( 'tools.php' );
		}

		// Delete link.
		$delete_link = add_query_arg(
			array(
				'page'      => 'bp-optouts',
				'optout_id' => $optout->id,
				'action'    => 'delete',
			),
			$form_url
		);
		$actions['delete'] = sprintf( '<a href="%1$s" class="delete">%2$s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'buddypress' ) );

		/**
		 * Filters the row actions for each opt-out in list.
		 *
		 * @since 8.0.0
		 *
		 * @param array  $actions Array of actions and corresponding links.
		 * @param object $optout  The BP_Optout.
		 */
		$actions = apply_filters( 'bp_optouts_management_row_actions', $actions, $optout );

		// BuddyPress relies on WordPress's `WP_Users_List_Table::row_actions()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->row_actions( $actions );
	}

	/**
	 * The inviter/site member who sent the email that prompted the opt-out.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_username( $optout = null ) {
		$avatar  = get_avatar( $optout->user_id, 32 );
		$inviter = get_user_by( 'id', $optout->user_id );

		if ( ! $inviter ) {
			return;
		}

		$user_link = bp_members_get_user_url( $optout->user_id );

		echo wp_kses(
			$avatar,
			array(
				'img' => array(
					'alt'    => true,
					'src'    => true,
					'srcset' => true,
					'class'  => true,
					'height' => true,
					'width'  => true,
				)
			)
		);
		printf( '<strong><a href="%1$s" class="edit">%2$s</a></strong><br/>', esc_url( $user_link ), esc_html( $inviter->user_login ) );
	}

	/**
	 * Display registration date of user whose communication prompted opt-out.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_user_registered( $optout = null ) {
		$inviter = get_user_by( 'id', $optout->user_id );

		if ( ! $inviter ) {
			return;
		}

		echo esc_html( mysql2date( 'Y/m/d g:i:s a', $inviter->user_registered  ) );
	}

	/**
	 * Display type of email that prompted opt-out.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_email_type( $optout = null ) {
		echo esc_html( $optout->email_type );
	}

	/**
	 * Display description of bp-email-type that prompted opt-out.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_email_type_description( $optout = null ) {
		$type_term = get_term_by( 'slug', $optout->email_type, 'bp-email-type' );

		if ( $type_term ) {
			echo esc_html( $type_term->description );
		}

	}

	/**
	 * Display opt-out date.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout BP_Optout object.
	 */
	public function column_optout_date_modified( $optout = null ) {
		echo esc_html( mysql2date( 'Y/m/d g:i:s a', $optout->date_modified ) );
	}

	/**
	 * Allow plugins to add custom columns.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Optout $optout      BP_Optout object.
	 * @param string    $column_name The column name.
	 * @return string
	 */
	function column_default( $optout = null, $column_name = '' ) {

		/**
		 * Filters the single site custom columns for plugins.
		 *
		 * @since 8.0.0
		 *
		 * @param string    $column_name The column name.
		 * @param BP_Optout $optout      BP_Optout object.
		 */
		return apply_filters( 'bp_optouts_management_custom_column', '', $column_name, $optout );
	}
}
