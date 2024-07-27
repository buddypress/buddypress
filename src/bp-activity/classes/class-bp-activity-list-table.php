<?php
/**
 * BuddyPress Activity component admin list table.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyPress
 * @subpackage ActivityAdmin
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Activity component admin page.
 *
 * @since 1.6.0
 */
class BP_Activity_List_Table extends WP_List_Table {

	/**
	 * What type of view is being displayed?
	 *
	 * E.g. "all", "pending", "approved", "spam"...
	 *
	 * @since 1.6.0
	 * @var string $view
	 */
	public $view = 'all';

	/**
	 * How many activity items have been marked as spam.
	 *
	 * @since 1.6.0
	 * @var int $spam_count
	 */
	public $spam_count = 0;

	/**
	 * Total number of activities.
	 *
	 * @since 6.0.0
	 * @var int $all_count
	 */
	public $all_count = 0;

	/**
	 * Store activity-to-user-ID mappings for use in the In Response To column.
	 *
	 * @since 1.6.0
	 * @var array $activity_user_id
	 */
	protected $activity_user_id = array();

	/**
	 * If users can comment on post and comment activity items.
	 *
	 * @link https://buddypress.trac.wordpress.org/ticket/6277
	 *
	 * @since 2.2.2
	 * @var bool $disable_blogforum_comments
	 */
	public $disable_blogforum_comments = false;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		// See if activity commenting is enabled for post/comment activity items.
		$this->disable_blogforum_comments = bp_disable_blogforum_comments();

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'activities',
			'singular' => 'activity',
			'screen'   => get_current_screen(),
		) );
	}

	/**
	 * Handle filtering of data, sorting, pagination, and any other data manipulation prior to rendering.
	 *
	 * @since 1.6.0
	 */
	function prepare_items() {

		// Option defaults.
		$filter           = array();
		$filter_query     = false;
		$include_id       = false;
		$search_terms     = false;
		$spam             = 'ham_only';

		// Set current page.
		$page = $this->get_pagenum();

		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		// Check if we're on the "Spam" view.
		if ( ! empty( $_REQUEST['activity_status'] ) && 'spam' === $_REQUEST['activity_status'] ) {
			$spam       = 'spam_only';
			$this->view = 'spam';
		}

		// Filter.
		if ( ! empty( $_REQUEST['activity_type'] ) ) {
			$filter = array( 'action' => $_REQUEST['activity_type'] );

			// Set the view as a filtered one.
			$this->view = 'filtered';

			/**
			 * Filter here to override the filter with a filter query
			 *
			 * @since  2.5.0
			 *
			 * @param array $filter
			 */
			$has_filter_query = apply_filters( 'bp_activity_list_table_filter_activity_type_items', $filter );

			if ( ! empty( $has_filter_query['filter_query'] ) ) {
				// Reset the filter.
				$filter       = array();

				// And use the filter query instead.
				$filter_query = $has_filter_query['filter_query'];
			}
		}

		// Are we doing a search?
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search_terms = $_REQUEST['s'];

			// Set the view as a search request.
			$this->view = 'search';
		}

		// Check if user has clicked on a specific activity (if so, fetch only that, and any related, activity).
		if ( ! empty( $_REQUEST['aid'] ) ) {
			$include_id = (int) $_REQUEST['aid'];

			// Set the view as a single activity.
			$this->view = 'single';
		}

		// Get the spam total (ignoring any search query or filter).
		$spams = bp_activity_get( array(
			'display_comments' => 'stream',
			'show_hidden'      => true,
			'spam'             => 'spam_only',
			'count_total_only' => true,
		) );
		$this->spam_count = $spams['total'];
		unset( $spams );

		// Get the activities from the database.
		$activities = bp_activity_get( array(
			'display_comments' => 'stream',
			'filter'           => $filter,
			'in'               => $include_id,
			'page'             => $page,
			'per_page'         => $per_page,
			'search_terms'     => $search_terms,
			'filter_query'     => $filter_query,
			'show_hidden'      => true,
			'spam'             => $spam,
			'count_total'      => 'count_query',
		) );

		// If we're viewing a specific activity, flatten all activities into a single array.
		if ( $include_id ) {
			$activities['activities'] = BP_Activity_List_Table::flatten_activity_array( $activities['activities'] );
			$activities['total']      = count( $activities['activities'] );

			// Sort the array by the activity object's date_recorded value.
			usort( $activities['activities'], function ( $a, $b ) { return $a->date_recorded > $b->date_recorded; } );
		}

		// The bp_activity_get function returns an array of objects; cast these to arrays for WP_List_Table.
		$new_activities = array();
		foreach ( $activities['activities'] as $activity_item ) {
			$new_activities[] = (array) $activity_item;

			// Build an array of activity-to-user ID mappings for better efficiency in the In Response To column.
			$this->activity_user_id[$activity_item->id] = $activity_item->user_id;
		}

		// Set raw data to display.
		$this->items = $new_activities;

		// Store information needed for handling table pagination.
		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $activities['total'],
			'total_pages' => ceil( $activities['total'] / $per_page )
		) );

		// Don't truncate activity items; bp_activity_truncate_entry() needs to be used inside a BP_Activity_Template loop.
		remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

		// Set the Total number of activities.
		if ( 'all' === $this->view ) {
			$this->all_count = (int) $activities['total'];

		// Only perform a query if not on the main list view.
		} elseif ( 'single' !== $this->view ) {
			$count_activities = bp_activity_get(
				array(
					'fields'           => 'ids',
					'show_hidden'      => true,
					'count_total_only' => true,
				)
			);

			if ( $count_activities['total'] ) {
				$this->all_count = (int) $count_activities['total'];
			}
		}
	}

	/**
	 * Get an array of all the columns on the page.
	 *
	 * @since 1.6.0
	 *
	 * @return array Column headers.
	 */
	function get_column_info() {
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
		return 'author';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since 1.6.0
	 */
	function no_items() {
		esc_html_e( 'No activities found.', 'buddypress' );
	}

	/**
	 * Output the Activity data table.
	 *
	 * @since 1.6.0
	 */
	function display() {
		$this->display_tablenav( 'top' ); ?>

		<h2 class="screen-reader-text">
			<?php
				/* translators: accessibility text */
				esc_html_e( 'Activities list', 'buddypress' );
			?>
		</h2>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0">
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
	 * Generate content for a single row of the table.
	 *
	 * @since 1.6.0
	 *
	 * @param object $item The current item.
	 */
	function single_row( $item ) {
		static $even = false;

		$row_classes = array();

		if ( $even ) {
			$row_classes = array( 'even' );
		} else {
			$row_classes = array( 'alternate', 'odd' );
		}

		if ( 'activity_comment' === $item['type'] ) {
			$root_id = $item['item_id'];
		} else {
			$root_id = $item['id'];
		}

		echo '<tr class="' . implode( ' ', array_map( 'sanitize_html_class', $row_classes ) ) . '" id="activity-' . esc_attr( $item['id'] ) . '" data-parent_id="' . esc_attr( $item['id'] ) . '" data-root_id="' . esc_attr( $root_id ) . '">';

		// Escapes are made into `self::single_row_columns()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->single_row_columns( $item );
		echo '</tr>';

		$even = ! $even;
	}

	/**
	 * Get the list of views available on this table (e.g. "all", "spam").
	 *
	 * @since 1.6.0
	 */
	function get_views() {
		$url_base = add_query_arg( array( 'page' => 'bp-activity' ), bp_get_admin_url( 'admin.php' ) ); ?>

		<h2 class="screen-reader-text">
			<?php
				/* translators: accessibility text */
				esc_html_e( 'Filter activities list', 'buddypress' );
			?>
		</h2>

		<ul class="subsubsub">
			<li class="all">
				<a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( 'all' === $this->view ) echo 'current'; ?>">
				<?php printf(
						/* translators: %s is the placeholder for the count html tag `<span class="count"/>` */
						esc_html__( 'All %s', 'buddypress' ),
						sprintf(
							'<span class="count">(%s)</span>',
							esc_html( number_format_i18n( $this->all_count ) )
						)
					); ?>
				</a> |
			</li>
			<li class="spam">
				<a href="<?php echo esc_url( add_query_arg( array( 'activity_status' => 'spam' ), $url_base ) ); ?>" class="<?php if ( 'spam' === $this->view ) echo 'current'; ?>">
					<?php printf(
						/* translators: %s is the placeholder for the count html tag `<span class="count"/>` */
						esc_html__( 'Spam %s', 'buddypress' ),
						sprintf(
							'<span class="count">(%s)</span>',
							esc_html( number_format_i18n( $this->spam_count ) )
						)
					); ?>
				</a>
			</li>

			<?php

			/**
			 * Fires inside listing of views so plugins can add their own.
			 *
			 * @since 1.6.0
			 *
			 * @param string $url_base Current URL base for view.
			 * @param string $view     Current view being displayed.
			 */
			do_action( 'bp_activity_list_table_get_views', $url_base, $this->view ); ?>
		</ul>
	<?php
	}

	/**
	 * Get bulk actions.
	 *
	 * @since 1.6.0
	 *
	 * @return array Key/value pairs for the bulk actions dropdown.
	 */
	public function get_bulk_actions() {

		/**
		 * Filters the default bulk actions so plugins can add custom actions.
		 *
		 * @since 1.6.0
		 *
		 * @param array $actions Default available actions for bulk operations.
		 */
		return apply_filters( 'bp_activity_list_table_get_bulk_actions',
			array(
				'bulk_spam'   => __( 'Mark as Spam', 'buddypress' ),
				'bulk_ham'    => __( 'Not Spam', 'buddypress' ),
				'bulk_delete' => __( 'Delete Permanently', 'buddypress' ),
			)
		);
	}

	/**
	 * Get the table column titles.
	 *
	 * @since 1.6.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @return array The columns to appear in the Activity list table.
	 */
	function get_columns() {

		/**
		 * Filters the titles for the columns for the activity list table.
		 *
		 * @since 2.4.0
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters( 'bp_activity_list_table_get_columns',
			array(
				'cb'       => '<input name type="checkbox" />',
				'author'   => _x( 'Author', 'Admin SWA column header', 'buddypress' ),
				'comment'  => _x( 'Activity', 'Admin SWA column header', 'buddypress' ),
				'action'   => _x( 'Action', 'Admin SWA column header', 'buddypress' ),
				'response' => _x( 'In Response To', 'Admin SWA column header', 'buddypress' ),
			)
		);
	}

	/**
	 * Get the column names for sortable columns.
	 *
	 * @since 1.6.0
	 *
	 * @return array The columns that can be sorted on the Activity screen.
	 */
	public function get_sortable_columns() {

		/**
		 * Filters the column names for the sortable columns.
		 *
		 * @since 5.0.0
		 *
		 * @param array $value Array of column names.
		 */
		return apply_filters( 'bp_activity_list_table_get_sortable_columns', array() );
	}

	/**
	 * Markup for the "filter" part of the form (i.e. which activity type to display).
	 *
	 * @since 1.6.0
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	function extra_tablenav( $which ) {

		// Bail on bottom table nav.
		if ( 'bottom' === $which ) {
			return;
		}

		// Is any filter currently selected?
		$selected = ( ! empty( $_REQUEST['activity_type'] ) ) ? $_REQUEST['activity_type'] : '';

		// Get the actions.
		$activity_actions = bp_activity_get_actions(); ?>

		<div class="alignleft actions">
			<label for="activity-type" class="screen-reader-text">
				<?php
					/* translators: accessibility text */
					esc_html_e( 'Filter by activity type', 'buddypress' );
				?>
			</label>
			<select name="activity_type" id="activity-type">
				<option value="" <?php selected( ! $selected ); ?>><?php esc_html_e( 'View all actions', 'buddypress' ); ?></option>

				<?php foreach ( $activity_actions as $component => $actions ) : ?>
					<?php
					// Older avatar activity items use 'profile' for component. See r4273.
					if ( $component === 'profile' ) {
						$component = 'xprofile';
					}

					// The 'activity_update' filter is already used by the Activity component.
					if ( isset( $actions->activity_update ) && 'bp_groups_format_activity_action_group_activity_update' === $actions->activity_update['format_callback'] ) {
						unset( $actions->activity_update );
					}

					if ( bp_is_active( $component ) ) {
						if ( $component === 'xprofile' ) {
							$component_name = buddypress()->profile->name;
						} else {
							$component_name = buddypress()->$component->name;
						}

					} else {
						// Prevent warnings by other plugins if a component is disabled but the activity type has been registered.
						$component_name = ucfirst( $component );
					}
					?>

					<optgroup label="<?php echo esc_html( $component_name ); ?>">

						<?php foreach ( $actions as $action_key => $action_values ) : ?>

							<?php

							// Skip the incorrectly named pre-1.6 action.
							if ( 'friends_register_activity_action' !== $action_key  ) : ?>

								<option value="<?php echo esc_attr( $action_key ); ?>" <?php selected( $action_key,  $selected ); ?>><?php echo esc_html( $action_values[ 'value' ] ); ?></option>

							<?php endif; ?>

						<?php endforeach; ?>

					</optgroup>

				<?php endforeach; ?>

			</select>

			<?php submit_button( __( 'Filter', 'buddypress' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) ); ?>
		</div>

	<?php
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
	 * @param array $actions The list of actions.
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
	 * Checkbox column markup.
	 *
	 * @since 1.6.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	function column_cb( $item ) {
		/* translators: accessibility text */
		printf( '<label class="screen-reader-text" for="aid-%1$d">' . esc_html__( 'Select activity item %1$d', 'buddypress' ) . '</label><input type="checkbox" name="aid[]" value="%1$d" id="aid-%1$d" />', intval( $item['id'] ) );
	}

	/**
	 * Author column markup.
	 *
	 * @since 1.6.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	function column_author( $item ) {
		$avatar = get_avatar( $item['user_id'], '32' );

		printf(
			'<strong>%1$s %2$s</strong>',
			wp_kses(
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
			),
			// phpcs:ignore WordPress.Security.EscapeOutput
			bp_core_get_userlink( $item['user_id'] )
		);
	}

	/**
	 * Action column markup.
	 *
	 * @since 2.0.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	function column_action( $item ) {
		$actions = bp_activity_admin_get_activity_actions();

		if ( isset( $actions[ $item['type'] ] ) ) {
			echo esc_html( $actions[ $item['type'] ] );
		} else {
			/* translators: %s: the name of the activity type */
			printf( esc_html__( 'Unregistered action - %s', 'buddypress' ), esc_html( $item['type'] ) );
		}
	}

	/**
	 * Content column, and "quick admin" rollover actions.
	 *
	 * Called "comment" in the CSS so we can re-use some WP core CSS.
	 *
	 * @since 1.6.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	function column_comment( $item ) {
		// Determine what type of item (row) we're dealing with.
		if ( $item['is_spam'] ) {
			$item_status = 'spam';
		} else {
			$item_status = 'all';
		}

		// Preorder items: Reply | Edit | Spam | Delete Permanently.
		$actions = array(
			'reply'  => '',
			'edit'   => '',
			'spam'   => '', 'unspam' => '',
			'delete' => '',
		);

		// Build actions URLs.
		$base_url   = bp_get_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $item['id'] );
		$spam_nonce = esc_html( '_wpnonce=' . wp_create_nonce( 'spam-activity_' . $item['id'] ) );

		$delete_url = $base_url . "&amp;action=delete&amp;$spam_nonce";
		$edit_url   = $base_url . '&amp;action=edit';
		$ham_url    = $base_url . "&amp;action=ham&amp;$spam_nonce";
		$spam_url   = $base_url . "&amp;action=spam&amp;$spam_nonce";

		// Rollover actions.
		// Reply - JavaScript only; implemented by AJAX.
		if ( 'spam' != $item_status ) {
			if ( $this->can_comment( $item ) ) {
				$actions['reply'] = sprintf( '<a href="#" class="reply hide-if-no-js">%s</a>', esc_html__( 'Reply', 'buddypress' ) );
			} else {
				$actions['reply'] = sprintf( '<span class="form-input-tip">%s</span>', esc_html__( 'Replies disabled', 'buddypress' ) );
			}

			// Edit.
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'buddypress' ) );
		}

		// Spam/unspam.
		if ( 'spam' == $item_status ) {
			$actions['unspam'] = sprintf( '<a href="%s">%s</a>', esc_url( $ham_url ), esc_html__( 'Not Spam', 'buddypress' ) );
		} else {
			$actions['spam'] = sprintf( '<a href="%s">%s</a>', esc_url( $spam_url ), esc_html__( 'Spam', 'buddypress' ) );
		}

		// Delete.
		$actions['delete'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $delete_url ), esc_html__( 'Delete Permanently', 'buddypress' ) );

		// Start timestamp.
		echo '<div class="submitted-on">';

		/**
		 * Filters available actions for plugins to alter.
		 *
		 * @since 1.6.0
		 *
		 * @param array $actions Array of available actions user could use.
		 * @param array $item    Current item being added to page.
		 */
		$actions = apply_filters( 'bp_activity_admin_comment_row_actions', array_filter( $actions ), $item );

		printf(
			/* translators: %s: activity date and time */
			esc_html__( 'Submitted on %s', 'buddypress' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( bp_activity_get_permalink( $item['id'] ) ),
				sprintf(
					/* translators: 1: activity date, 2: activity time */
					esc_html__( '%1$s at %2$s', 'buddypress' ),
					esc_html( date_i18n( bp_get_option( 'date_format' ), strtotime( $item['date_recorded'] ) ) ),
					esc_html( get_date_from_gmt( $item['date_recorded'], bp_get_option( 'time_format' ) ) )
				)
			)
		);

		// End timestamp.
		echo '</div>';

		$activity = new BP_Activity_Activity( $item['id'] );

		// Get activity content - if not set, use the action.
		if ( ! empty( $item['content'] ) ) {
			/** This filter is documented in bp-activity/bp-activity-template.php */
			$content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $item['content'], &$activity ) );
		} else {
			// Emulate bp_get_activity_action().
			$r = array(
				'no_timestamp' => false,
			);

			/** This filter is documented in bp-activity/bp-activity-template.php */
			$content = apply_filters_ref_array( 'bp_get_activity_action', array( $item['action'], &$activity, $r ) );
		}

		// phpcs:disable WordPress.Security.EscapeOutput
		echo apply_filters(
			/**
			 * Filter here to add extra output to the activity content into the Administration.
			 *
			 * @since  2.4.0
			 *
			 * @param  string $content The activity content.
			 * @param  array  $item    The activity object converted into an array.
			 */
			'bp_activity_admin_comment_content',
			$content,
			$item
		);

		echo ' ' . $this->row_actions( $actions );
		// phpcs:enable
	}

	/**
	 * "In response to" column markup.
	 *
	 * @since 1.6.0
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row).
	 */
	function column_response( $item ) {

		// Is $item is a root activity?
		?>

		<div class="response-links">

		<?php
		// Activity permalink.
		$activity_permalink = '';
		if ( ! $item['is_spam'] ) {
			$activity_permalink = '<a href="' . esc_url( bp_activity_get_permalink( $item['id'], (object) $item ) ) . '" class="comments-view-item-link">' . esc_html__( 'View Activity', 'buddypress' ) . '</a>';
		}

		/**
		 * Filters default list of default root activity types.
		 *
		 * @since 1.6.0
		 *
		 * @param array $value Array of default activity types.
		 * @param array $item  Current item being displayed.
		 */
		if ( empty( $item['item_id'] ) || ! in_array( $item['type'], apply_filters( 'bp_activity_admin_root_activity_types', array( 'activity_comment' ), $item ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $activity_permalink;

			$comment_count     = ! empty( $item['children'] ) ? bp_activity_recurse_comment_count( (object) $item ) : 0;
			$root_activity_url = bp_get_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $item['id'] );

			// If the activity has comments, display a link to the activity's permalink, with its comment count in a speech bubble.
			if ( $comment_count ) {
				printf( '<a href="%1$s" class="post-com-count post-com-count-approved"><span class="comment-count comment-count-approved">%2$s</span></a>', esc_url( $root_activity_url ), esc_html( number_format_i18n( $comment_count ) ) );
			}

		// For non-root activities, display a link to the replied-to activity's author's profile.
		} else {
			$avatar = get_avatar( $this->get_activity_user_id( $item['item_id'] ), '32' );
			printf(
				'<strong>%1$s %2$s</strong><br />',
				wp_kses(
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
				),
				// phpcs:ignore WordPress.Security.EscapeOutput
				bp_core_get_userlink( $this->get_activity_user_id( $item['item_id'] ) )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $activity_permalink;
		}
		?>
		</div>
		<?php
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since 2.4.0
	 *
	 * @param array  $item        Information about the current row.
	 * @param string $column_name The column name.
	 * @return string
	 */
	public function column_default( $item = array(), $column_name = '' ) {

		/**
		 * Filters a string to allow plugins to add custom column content.
		 *
		 * @since 2.4.0
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current activity item in the loop.
		 */
		return apply_filters( 'bp_activity_admin_get_custom_column', '', $column_name, $item );
	}

	/**
	 * Get the user id associated with a given activity item.
	 *
	 * Wraps bp_activity_get_specific(), with some additional logic for
	 * avoiding duplicate queries.
	 *
	 * @since 1.6.0
	 *
	 * @param int $activity_id Activity ID to retrieve User ID for.
	 * @return int User ID of the activity item in question.
	 */
	protected function get_activity_user_id( $activity_id ) {
		// If there is an existing activity/user ID mapping, just return the user ID.
		if ( ! empty( $this->activity_user_id[$activity_id] ) ) {
			return $this->activity_user_id[$activity_id];

		/*
		 * We don't have a mapping. This means the $activity_id is not on the current
		 * page of results, so fetch its details from the database.
		 */
		} else {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id, 'show_hidden' => true, 'spam' => 'all', ) );

			/*
			 * If, somehow, the referenced activity has been deleted, leaving its associated
			 * activities as orphans, use the logged in user's ID to avoid errors.
			 */
			if ( empty( $activity['activities'] ) ) {
				return bp_loggedin_user_id();
			}

			// Store the new activity/user ID mapping for any later re-use.
			$this->activity_user_id[ $activity['activities'][0]->id ] = $activity['activities'][0]->user_id;

			// Return the user ID.
			return $activity['activities'][0]->user_id;
		}
	}

	/**
	 * Checks if an activity item can be replied to.
	 *
	 * This method merges functionality from {@link bp_activity_can_comment()} and
	 * {@link bp_blogs_disable_activity_commenting()}. This is done because the activity
	 * list table doesn't use a BuddyPress activity loop, which prevents those
	 * functions from working as intended.
	 *
	 * @since 2.0.0
	 * @since 2.5.0 Include Post type activities types
	 *
	 * @param array $item An array version of the BP_Activity_Activity object.
	 * @return bool $can_comment
	 */
	protected function can_comment( $item ) {
		$can_comment = bp_activity_type_supports( $item['type'], 'comment-reply' );

		if ( ! $this->disable_blogforum_comments && bp_is_active( 'blogs' ) ) {
			$parent_activity = false;

			if ( bp_activity_type_supports( $item['type'], 'post-type-comment-tracking' ) ) {
				$parent_activity = (object) $item;
			} elseif ( 'activity_comment' === $item['type'] ) {
				$parent_activity = new BP_Activity_Activity( $item['item_id'] );
				$can_comment     = bp_activity_can_comment_reply( (object) $item );
			}

			if ( isset( $parent_activity->type ) && bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' ) ) {
				// Fetch blog post comment depth and if the blog post's comments are open.
				bp_blogs_setup_activity_loop_globals( $parent_activity );

				$can_comment = bp_blogs_can_comment_reply( true, $item );
			}
		}

		/**
		 * Filters if an activity item can be commented on or not.
		 *
		 * @since 2.0.0
		 * @since 2.5.0 Add a second parameter to include the activity item into the filter.
		 *
		 * @param bool  $can_comment Whether an activity item can be commented on or not.
		 * @param array $item        An array version of the BP_Activity_Activity object.
		 */
		return apply_filters( 'bp_activity_list_table_can_comment', $can_comment, $item );
	}

	/**
	 * Flatten the activity array.
	 *
	 * In some cases, BuddyPress gives us a structured tree of activity
	 * items plus their comments. This method converts it to a flat array.
	 *
	 * @since 1.6.0
	 *
	 * @param array $tree Source array.
	 * @return array Flattened array.
	 */
	public static function flatten_activity_array( $tree ){
		foreach ( (array) $tree as $node ) {
			if ( isset( $node->children ) ) {

				foreach ( BP_Activity_List_Table::flatten_activity_array( $node->children ) as $child ) {
					$tree[] = $child;
				}

				unset( $node->children );
			}
		}

		return $tree;
	}
}
