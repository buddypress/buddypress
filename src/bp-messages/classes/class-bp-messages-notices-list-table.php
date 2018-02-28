<?php
/**
 * BuddyPress messages admin site-wide notices list table class.
 *
 * @package BuddyPress
 * @subpackage Messages
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BP_Messages_Notices_List_Table extends WP_List_Table {
	
	/**
	 * Constructor
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function prepare_items() {
		$page     = $this->get_pagenum();
		$per_page = $this->get_items_per_page( 'bp_notices_per_page' );

		$this->items = BP_Messages_Notice::get_notices( array(
			'pag_num'  => $per_page,
			'pag_page' => $page
		) );

		$this->set_pagination_args( array(
			'total_items' => BP_Messages_Notice::get_total_notice_count(),
			'per_page' => $per_page,
		) );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return apply_filters( 'bp_notices_list_table_get_columns', array(
			'subject'   => _x( 'Subject', 'Admin Notices column header', 'buddypress' ),
			'message'   => _x( 'Content', 'Admin Notices column header', 'buddypress' ),
			'date_sent' => _x( 'Created', 'Admin Notices column header', 'buddypress' ),
		) );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		$class = '';

		if ( ! empty( $item->is_active ) ) {
			$class = ' class="notice-active"';
		}

		echo "<tr{$class}>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Generates content for the "subject" column.
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_subject( $item ) {
		$actions = array(
			'activate_deactivate' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'page' => 'bp-notices',
				'activate' => $item->id
			), bp_get_admin_url( 'users.php' ) ) ), 'messages_activate_notice' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="activate">' . esc_html__( 'Activate Notice', 'buddypress' ) . '</a>',
			'delete' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'page' => 'bp-notices',
				'delete' => $item->id
			), bp_get_admin_url( 'users.php' ) ) ), 'messages_delete_thread' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="delete">' . esc_html__( 'Delete Notice', 'buddypress' ) . '</a>',
		);

		if ( ! empty( $item->is_active ) ) {
			$actions['activate_deactivate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'page' => 'bp-notices',
				'deactivate' => $item->id
			), bp_get_admin_url( 'users.php' ) ) ), 'messages_deactivate_notice' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="deactivate">' . esc_html__( 'Deactivate Notice', 'buddypress' ) . '</a>';
		}

		echo '<strong>' . apply_filters( 'bp_get_message_notice_subject', $item->subject ) . '</strong> ' . $this->row_actions( $actions );
	}

	/**
	 * Generates content for the "message" column.
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_message( $item ) {
		echo apply_filters( 'bp_get_message_notice_text', $item->message );
	}

	/**
	 * Generates content for the "date_sent" column.
	 *
	 * @since 3.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_date_sent( $item ) {
		echo apply_filters( 'bp_get_message_notice_post_date', bp_format_time( strtotime( $item->date_sent ) ) );
	}
}
