<?php
/**
 * BuddyPress Groups admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyPress
 * @subpackage Groups
 * @since 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Groups component admin page.
 *
 * @since 1.7.0
 */
class BP_Groups_List_Table extends WP_List_Table {

	/**
	 * The type of view currently being displayed.
	 *
	 * E.g. "All", "Pending", "Approved", "Spam"...
	 *
	 * @since 1.7.0
	 * @var string
	 */
	public $view = 'all';

	/**
	 * Group counts for each group type.
	 *
	 * @since 1.7.0
	 * @var int
	 */
	public $group_counts = 0;

	/**
	 * Multidimensional array of group visibility (status) types and their groups.
	 *
	 * @link https://buddypress.trac.wordpress.org/ticket/6277
	 * @var array
	 */
	public $group_type_ids = array();

	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'groups',
			'singular' => 'group',
		) );

		// Add Group Type column and bulk change controls.
		if ( bp_groups_get_group_types() ) {
			// Add Group Type column.
			add_filter( 'bp_groups_list_table_get_columns',        array( $this, 'add_type_column' )                  );
			add_filter( 'bp_groups_admin_get_group_custom_column', array( $this, 'column_content_group_type' ), 10, 3 );
			// Add the bulk change select.
			add_action( 'bp_groups_list_table_after_bulk_actions', array( $this, 'add_group_type_bulk_change_select' ) );
		}
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 1.7.0
	 */
	public function prepare_items() {
		global $groups_template;

		$screen = get_current_screen();

		// Option defaults.
		$include_id   = false;
		$search_terms = false;

		// Set current page.
		$page = $this->get_pagenum();

		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$screen->id}_per_page" ) );

		// Sort order.
		$order = 'DESC';
		if ( !empty( $_REQUEST['order'] ) ) {
			$order = ( 'desc' == strtolower( $_REQUEST['order'] ) ) ? 'DESC' : 'ASC';
		}

		// Order by - default to newest.
		$orderby = 'last_activity';
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			switch ( $_REQUEST['orderby'] ) {
				case 'name' :
					$orderby = 'name';
					break;
				case 'id' :
					$orderby = 'date_created';
					break;
				case 'members' :
					$orderby = 'total_member_count';
					break;
				case 'last_active' :
					$orderby = 'last_activity';
					break;
			}
		}

		// Are we doing a search?
		if ( !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];

		// Check if user has clicked on a specific group (if so, fetch only that group).
		if ( !empty( $_REQUEST['gid'] ) )
			$include_id = (int) $_REQUEST['gid'];

		// Set the current view.
		if ( isset( $_GET['group_status'] ) && in_array( $_GET['group_status'], array( 'public', 'private', 'hidden' ) ) ) {
			$this->view = $_GET['group_status'];
		}

		// We'll use the ids of group status types for the 'include' param.
		$this->group_type_ids = BP_Groups_Group::get_group_type_ids();

		// Pass a dummy array if there are no groups of this type.
		$include = false;
		if ( 'all' != $this->view && isset( $this->group_type_ids[ $this->view ] ) ) {
			$include = ! empty( $this->group_type_ids[ $this->view ] ) ? $this->group_type_ids[ $this->view ] : array( 0 );
		}

		// Get group type counts for display in the filter tabs.
		$this->group_counts = array();
		foreach ( $this->group_type_ids as $group_type => $group_ids ) {
			$this->group_counts[ $group_type ] = count( $group_ids );
		}

		// Group types
		$group_type = false;
		if ( isset( $_GET['bp-group-type'] ) && null !== bp_groups_get_group_type_object( $_GET['bp-group-type'] ) ) {
			$group_type = $_GET['bp-group-type'];
		}

		// If we're viewing a specific group, flatten all activities into a single array.
		if ( $include_id ) {
			$groups = array( (array) groups_get_group( $include_id ) );
		} else {
			$groups_args = array(
				'include'  => $include,
				'per_page' => $per_page,
				'page'     => $page,
				'orderby'  => $orderby,
				'order'    => $order
			);

			if ( $group_type ) {
				$groups_args['group_type'] = $group_type;
			}

			$groups = array();
			if ( bp_has_groups( $groups_args ) ) {
				while ( bp_groups() ) {
					bp_the_group();
					$groups[] = (array) $groups_template->group;
				}
			}
		}

		// Set raw data to display.
		$this->items = $groups;

		// Store information needed for handling table pagination.
		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $groups_template->total_group_count,
			'total_pages' => ceil( $groups_template->total_group_count / $per_page )
		) );
	}

	/**
	 * Get an array of all the columns on the page.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array of column headers.
	 */
	public function get_column_info() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
			$this->get_default_primary_column_name(),
		);

		return $this->_column_headers;
	}

	/**
	 * Get name of default primary column
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		// Comment column is mapped to Group's name.
		return 'comment';
	}

	/**
	 * Display a message on screen when no items are found ("No groups found").
	 *
	 * @since 1.7.0
	 */
	public function no_items() {
		_e( 'No groups found.', 'buddypress' );
	}

	/**
	 * Output the Groups data table.
	 *
	 * @since 1.7.0
	 */
	public function display() {
		$this->display_tablenav( 'top' ); ?>

		<h2 class="screen-reader-text"><?php
			/* translators: accessibility text */
			_e( 'Groups list', 'buddypress' );
		?></h2>

		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tbody id="the-comment-list">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>
		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 2.7.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		/**
		 * Fires just after the bulk action controls in the WP Admin groups list table.
		 *
		 * @since 2.7.0
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		do_action( 'bp_groups_list_table_after_bulk_actions', $which );
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @since 1.7.0
	 *
	 * @param object|array $item The current group item in the loop.
	 */
	public function single_row( $item = array() ) {
		static $even = false;

		$row_classes = array();

		if ( $even ) {
			$row_classes = array( 'even' );
		} else {
			$row_classes = array( 'alternate', 'odd' );
		}

		/**
		 * Filters the classes applied to a single row in the groups list table.
		 *
		 * @since 1.9.0
		 *
		 * @param array  $row_classes Array of classes to apply to the row.
		 * @param string $value       ID of the current group being displayed.
		 */
		$row_classes = apply_filters( 'bp_groups_admin_row_class', $row_classes, $item['id'] );
		$row_class = ' class="' . implode( ' ', $row_classes ) . '"';

		echo '<tr' . $row_class . ' id="group-' . esc_attr( $item['id'] ) . '" data-parent_id="' . esc_attr( $item['id'] ) . '" data-root_id="' . esc_attr( $item['id'] ) . '">';
		echo $this->single_row_columns( $item );
		echo '</tr>';

		$even = ! $even;
	}

	/**
	 * Get the list of views available on this table (e.g. "all", "public").
	 *
	 * @since 1.7.0
	 */
	public function get_views() {
		$url_base = bp_get_admin_url( 'admin.php?page=bp-groups' ); ?>

		<h2 class="screen-reader-text"><?php
			/* translators: accessibility text */
			_e( 'Filter groups list', 'buddypress' );
		?></h2>

		<ul class="subsubsub">
			<li class="all"><a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( 'all' == $this->view ) echo 'current'; ?>"><?php _e( 'All', 'buddypress' ); ?></a> |</li>
			<li class="public"><a href="<?php echo esc_url( add_query_arg( 'group_status', 'public', $url_base ) ); ?>" class="<?php if ( 'public' == $this->view ) echo 'current'; ?>"><?php printf( _n( 'Public <span class="count">(%s)</span>', 'Public <span class="count">(%s)</span>', $this->group_counts['public'], 'buddypress' ), number_format_i18n( $this->group_counts['public'] ) ); ?></a> |</li>
			<li class="private"><a href="<?php echo esc_url( add_query_arg( 'group_status', 'private', $url_base ) ); ?>" class="<?php if ( 'private' == $this->view ) echo 'current'; ?>"><?php printf( _n( 'Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>', $this->group_counts['private'], 'buddypress' ), number_format_i18n( $this->group_counts['private'] ) ); ?></a> |</li>
			<li class="hidden"><a href="<?php echo esc_url( add_query_arg( 'group_status', 'hidden', $url_base ) ); ?>" class="<?php if ( 'hidden' == $this->view ) echo 'current'; ?>"><?php printf( _n( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>', $this->group_counts['hidden'], 'buddypress' ), number_format_i18n( $this->group_counts['hidden'] ) ); ?></a></li>

			<?php

			/**
			 * Fires inside listing of views so plugins can add their own.
			 *
			 * @since 1.7.0
			 *
			 * @param string $url_base Current URL base for view.
			 * @param string $view     Current view being displayed.
			 */
			do_action( 'bp_groups_list_table_get_views', $url_base, $this->view ); ?>
		</ul>
	<?php
	}

	/**
	 * Get bulk actions for single group row.
	 *
	 * @since 1.7.0
	 *
	 * @return array Key/value pairs for the bulk actions dropdown.
	 */
	public function get_bulk_actions() {

		/**
		 * Filters the list of bulk actions to display on a single group row.
		 *
		 * @since 1.7.0
		 *
		 * @param array $value Array of bulk actions to display.
		 */
		return apply_filters( 'bp_groups_list_table_get_bulk_actions', array(
			'delete' => __( 'Delete', 'buddypress' )
		) );
	}

	/**
	 * Get the table column titles.
	 *
	 * @since 1.7.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @return array Array of column titles.
	 */
	public function get_columns() {

		/**
		 * Filters the titles for the columns for the groups list table.
		 *
		 * @since 2.0.0
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters( 'bp_groups_list_table_get_columns', array(
			'cb'          => '<input name type="checkbox" />',
			'comment'     => _x( 'Name', 'Groups admin Group Name column header',               'buddypress' ),
			'description' => _x( 'Description', 'Groups admin Group Description column header', 'buddypress' ),
			'status'      => _x( 'Status', 'Groups admin Privacy Status column header',         'buddypress' ),
			'members'     => _x( 'Members', 'Groups admin Members column header',               'buddypress' ),
			'last_active' => _x( 'Last Active', 'Groups admin Last Active column header',       'buddypress' )
		) );
	}

	/**
	 * Get the column names for sortable columns.
	 *
	 * Note: It's not documented in WP, but the second item in the
	 * nested arrays below is $desc_first. Normally, we would set
	 * last_active to be desc_first (since you're generally interested in
	 * the *most* recently active group, not the *least*). But because
	 * the default sort for the Groups admin screen is DESC by last_active,
	 * we want the first click on the Last Active column header to switch
	 * the sort order - ie, to make it ASC. Thus last_active is set to
	 * $desc_first = false.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array of sortable column names.
	 */
	public function get_sortable_columns() {
		return array(
			'gid'         => array( 'gid', false ),
			'comment'     => array( 'name', false ),
			'members'     => array( 'members', false ),
			'last_active' => array( 'last_active', false ),
		);
	}

	/**
	 * Override WP_List_Table::row_actions().
	 *
	 * Basically a duplicate of the row_actions() method, but removes the
	 * unnecessary <button> addition.
	 *
	 * @since 2.3.3
	 * @since 2.3.4 Visibility set to public for compatibility with WP < 4.0.0.
	 *
	 * @param array $actions        The list of actions.
	 * @param bool  $always_visible Whether the actions should be always visible.
	 * @return string
	 */
	public function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( !$action_count )
			return '';

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * Markup for the Checkbox column.
	 *
	 * @since 1.7.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	public function column_cb( $item = array() ) {
		/* translators: accessibility text */
		printf( '<label class="screen-reader-text" for="gid-%1$d">' . __( 'Select group %1$d', 'buddypress' ) . '</label><input type="checkbox" name="gid[]" value="%1$d" id="gid-%1$d" />', $item['id'] );
	}

	/**
	 * Markup for the Group ID column.
	 *
	 * @since 1.7.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	public function column_gid( $item = array() ) {
		echo '<strong>' . absint( $item['id'] ) . '</strong>';
	}

	/**
	 * Name column, and "quick admin" rollover actions.
	 *
	 * Called "comment" in the CSS so we can re-use some WP core CSS.
	 *
	 * @since 1.7.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	public function column_comment( $item = array() ) {

		// Preorder items: Edit | Delete | View.
		$actions = array(
			'edit'   => '',
			'delete' => '',
			'view'   => '',
		);

		// We need the group object for some BP functions.
		$item_obj = (object) $item;

		// Build actions URLs.
		$base_url   = bp_get_admin_url( 'admin.php?page=bp-groups&amp;gid=' . $item['id'] );
		$delete_url = wp_nonce_url( $base_url . "&amp;action=delete", 'bp-groups-delete' );
		$edit_url   = $base_url . '&amp;action=edit';
		$view_url   = bp_get_group_permalink( $item_obj );

		/**
		 * Filters the group name for a group's column content.
		 *
		 * @since 1.7.0
		 *
		 * @param string $value Name of the group being rendered.
		 * @param array  $item  Array for the current group item.
		 */
		$group_name = apply_filters_ref_array( 'bp_get_group_name', array( $item['name'], $item ) );

		// Rollover actions.
		// Edit.
		$actions['edit']   = sprintf( '<a href="%s">%s</a>', esc_url( $edit_url   ), __( 'Edit',   'buddypress' ) );

		// Delete.
		$actions['delete'] = sprintf( '<a href="%s">%s</a>', esc_url( $delete_url ), __( 'Delete', 'buddypress' ) );

		// Visit.
		$actions['view']   = sprintf( '<a href="%s">%s</a>', esc_url( $view_url   ), __( 'View',   'buddypress' ) );

		/**
		 * Filters the actions that will be shown for the column content.
		 *
		 * @since 1.7.0
		 *
		 * @param array $value Array of actions to be displayed for the column content.
		 * @param array $item  The current group item in the loop.
		 */
		$actions = apply_filters( 'bp_groups_admin_comment_row_actions', array_filter( $actions ), $item );

		// Get group name and avatar.
		$avatar = '';

		if ( buddypress()->avatar->show_avatars ) {
			$avatar  = bp_core_fetch_avatar( array(
				'item_id'    => $item['id'],
				'object'     => 'group',
				'type'       => 'thumb',
				'avatar_dir' => 'group-avatars',
				'alt'        => sprintf( __( 'Group logo of %s', 'buddypress' ), $group_name ),
				'width'      => '32',
				'height'     => '32',
				'title'      => $group_name
			) );
		}

		$content = sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( $edit_url ), $group_name );

		echo $avatar . ' ' . $content . ' ' . $this->row_actions( $actions );
	}

	/**
	 * Markup for the Description column.
	 *
	 * @since 1.7.0
	 *
	 * @param array $item Information about the current row.
	 */
	public function column_description( $item = array() ) {

		/**
		 * Filters the markup for the Description column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Markup for the Description column.
		 * @param array  $item  The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'bp_get_group_description', array( $item['description'], $item ) );
	}

	/**
	 * Markup for the Status column.
	 *
	 * @since 1.7.0
	 *
	 * @param array $item Information about the current row.
	 */
	public function column_status( $item = array() ) {
		$status      = $item['status'];
		$status_desc = '';

		// @todo This should be abstracted out somewhere for the whole
		// Groups component.
		switch ( $status ) {
			case 'public' :
				$status_desc = __( 'Public', 'buddypress' );
				break;
			case 'private' :
				$status_desc = __( 'Private', 'buddypress' );
				break;
			case 'hidden' :
				$status_desc = __( 'Hidden', 'buddypress' );
				break;
		}

		/**
		 * Filters the markup for the Status column.
		 *
		 * @since 1.7.0
		 *
		 * @param string $status_desc Markup for the Status column.
		 * @parma array  $item        The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'bp_groups_admin_get_group_status', array( $status_desc, $item ) );
	}

	/**
	 * Markup for the Number of Members column.
	 *
	 * @since 1.7.0
	 *
	 * @param array $item Information about the current row.
	 */
	public function column_members( $item = array() ) {
		$count = groups_get_groupmeta( $item['id'], 'total_member_count' );

		/**
		 * Filters the markup for the number of Members column.
		 *
		 * @since 1.7.0
		 *
		 * @param int   $count Markup for the number of Members column.
		 * @parma array $item  The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'bp_groups_admin_get_group_member_count', array( (int) $count, $item ) );
	}

	/**
	 * Markup for the Last Active column.
	 *
	 * @since 1.7.0
	 *
	 * @param array $item Information about the current row.
	 */
	public function column_last_active( $item = array() ) {
		$last_active = groups_get_groupmeta( $item['id'], 'last_activity' );

		/**
		 * Filters the markup for the Last Active column.
		 *
		 * @since 1.7.0
		 *
		 * @param string $last_active Markup for the Last Active column.
		 * @parma array  $item        The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'bp_groups_admin_get_group_last_active', array( $last_active, $item ) );
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $item        Information about the current row.
	 * @param string $column_name The column name.
	 * @return string
	 */
	public function column_default( $item = array(), $column_name = '' ) {

		/**
		 * Filters a string to allow plugins to add custom column content.
		 *
		 * @since 2.0.0
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current group item in the loop.
		 */
		return apply_filters( 'bp_groups_admin_get_group_custom_column', '', $column_name, $item );
	}

	// Group Types

	/**
	 * Add group type column to the WordPress admin groups list table.
	 *
	 * @since 2.7.0
	 *
	 * @param array $columns Groups table columns.
	 *
	 * @return array $columns
	 */
	public function add_type_column( $columns = array() ) {
		$columns['bp_group_type'] = _x( 'Group Type', 'Label for the WP groups table group type column', 'buddypress' );

		return $columns;
	}

	/**
	 * Markup for the Group Type column.
	 *
	 * @since 2.7.0
	 *
	 * @param string $retval      Empty string.
	 * @param string $column_name Name of the column being rendered.
	 * @param array  $item        The current group item in the loop.
	 * @return string
	 */
	public function column_content_group_type( $retval = '', $column_name, $item ) {
		if ( 'bp_group_type' !== $column_name ) {
			return $retval;
		}

		add_filter( 'bp_get_group_type_directory_permalink', array( $this, 'group_type_permalink_use_admin_filter' ), 10, 2 );
		$retval = bp_get_group_type_list( $item['id'], array(
			'parent_element' => '',
			'label_element'  => '',
			'label'          => '',
			'show_all'       => true
		) );
		remove_filter( 'bp_get_group_type_directory_permalink', array( $this, 'group_type_permalink_use_admin_filter' ), 10 );

		/**
		 * Filters the markup for the Group Type column.
		 *
		 * @since 2.7.0
		 *
		 * @param string $retval Markup for the Group Type column.
		 * @parma array  $item   The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'bp_groups_admin_get_group_type_column', array( $retval, $item ) );
	}

	/**
	 * Filters the group type list permalink in the Group Type column.
	 *
	 * Changes the group type permalink to use the admin URL.
	 *
	 * @since 2.7.0
	 *
	 * @param  string $retval Current group type permalink.
	 * @param  object $type   Group type object.
	 * @return string
	 */
	public function group_type_permalink_use_admin_filter( $retval, $type ) {
		return add_query_arg( array( 'bp-group-type' => urlencode( $type->name ) ) );
	}

	/**
	 * Markup for the Group Type bulk change select.
	 *
	 * @since 2.7.0
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function add_group_type_bulk_change_select( $which ) {
		// `$which` is only passed in WordPress 4.6+. Avoid duplicating controls in earlier versions.
		static $displayed = false;
		if ( version_compare( bp_get_major_wp_version(), '4.6', '<' ) && $displayed ) {
			return;
		}
		$displayed = true;
		$id_name = 'bottom' === $which ? 'bp_change_type2' : 'bp_change_type';

		$types = bp_groups_get_group_types( array(), 'objects' );
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="<?php echo $id_name; ?>"><?php _e( 'Change group type to&hellip;', 'buddypress' ) ?></label>
			<select name="<?php echo $id_name; ?>" id="<?php echo $id_name; ?>" style="display:inline-block;float:none;">
				<option value=""><?php _e( 'Change group type to&hellip;', 'buddypress' ) ?></option>

				<?php foreach( $types as $type ) : ?>

					<option value="<?php echo esc_attr( $type->name ); ?>"><?php echo esc_html( $type->labels['singular_name'] ); ?></option>

				<?php endforeach; ?>

				<option value="remove_group_type"><?php _e( 'No Group Type', 'buddypress' ) ?></option>

			</select>
			<?php
			wp_nonce_field( 'bp-bulk-groups-change-type-' . bp_loggedin_user_id(), 'bp-bulk-groups-change-type-nonce' );
			submit_button( __( 'Change', 'buddypress' ), 'button', 'bp_change_group_type', false );
		?>
		</div>
		<?php
	}
}
