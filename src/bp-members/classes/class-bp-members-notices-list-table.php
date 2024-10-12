<?php
/**
 * BuddyPress Members notices admin list table class.
 *
 * @package BuddyPress
 * @subpackage MembersClasses
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * BuddyPress Members Notices List Table class.
 *
 * @since 15.0.0
 */
class BP_Members_Notices_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 15.0.0
	 *
	 * @param array $args Arguments passed to the WP_List_Table::constructor.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural'   => 'notices',
				'singular' => 'notice',
				'ajax'     => true,
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	/**
	 * Checks the current user's permissions
	 *
	 * @since 15.0.0
	 */
	public function ajax_user_can() {
		return bp_current_user_can( 'bp_moderate' );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 15.0.0
	 */
	public function prepare_items() {
		$page     = $this->get_pagenum();
		$per_page = $this->get_items_per_page( 'bp_notices_per_page' );

		$this->items = BP_Members_Notice::get(
			array(
				'pag_num'  => $per_page,
				'pag_page' => $page,
				'type'     => 'all',
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => BP_Members_Notice::get_total_notice_count(),
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'bp_notices_list_table_get_columns',
			array(
				'subject'   => _x( 'Subject', 'Admin Notices column header', 'buddypress' ),
				'priority'  => _x( 'Priority', 'Admin Notices column header', 'buddypress' ),
				'target'    => _x( 'Targeted audience', 'Admin Notices column header', 'buddypress' ),
				'date_sent' => _x( 'Created', 'Admin Notices column header', 'buddypress' ),
			)
		);
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 15.0.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {

		if ( isset( $item->priority ) && 1 === $item->priority ) {
			echo '<tr class="notice-active">';
		} else {
			echo '<tr>';
		}

		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Generates content for the "subject" column.
	 *
	 * @since 15.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_subject( $item ) {
		$actions = array(
			'activate_deactivate' => sprintf(
				'<a href="%s" data-bp-notice-id="%d" data-bp-action="deactivate">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'page'          => 'bp-notices',
								'notice_action' => 'deactivate',
								'notice_id'     => $item->id
							),
							bp_get_admin_url( 'users.php' )
						),
						'messages-deactivate-notice-' . $item->id
					)
				),
				(int) $item->id,
				esc_html__( 'Deactivate Notice', 'buddypress' )
			),
			'delete' => sprintf(
				'<a href="%s" data-bp-notice-id="%d" data-bp-action="delete">%s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'page'          => 'bp-notices',
									'notice_action' => 'delete',
									'notice_id'     => $item->id
								),
								bp_get_admin_url( 'users.php' )
							),
							'messages-delete-notice-' . $item->id
						)
					),
					(int) $item->id,
					esc_html__( 'Delete Notice', 'buddypress' )
			)
		);

		if ( 255 === (int) $item->priority ) {
			$actions['activate_deactivate'] = sprintf(
				'<a href="%s" data-bp-notice-id="%d" data-bp-action="activate">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'page'          => 'bp-notices',
								'notice_action' => 'activate',
								'notice_id'     => $item->id
							),
							bp_get_admin_url( 'users.php' )
						),
						'messages-activate-notice-' . $item->id
					)
				),
				(int) $item->id,
				esc_html__( 'Activate Notice', 'buddypress' )
			);
		}

		echo '<strong>' . bp_get_notice_title( $item ) . '</strong> ';

		// BuddyPress relies on WordPress's `WP_List_Table::row_actions()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->row_actions( $actions );
	}

	/**
	 * Generates content for the "priority" column.
	 *
	 * @since 15.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_priority( $item ) {
		bp_notice_priority( $item );
	}

	/**
	 * Generates content for the "target" column.
	 *
	 * @since 15.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_target( $item ) {
		bp_notice_target( $item );
	}

	/**
	 * Generates content for the "date_sent" column.
	 *
	 * @since 15.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_date_sent( $item ) {
		echo esc_html( bp_format_time( strtotime( $item->date_sent ) ) );
	}
}
