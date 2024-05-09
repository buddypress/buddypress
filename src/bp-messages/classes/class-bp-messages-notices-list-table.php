<?php
/**
 * BuddyPress messages admin site-wide notices list table class.
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 * @since 3.0.0
 * @deprecated 14.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '14.0.0', '/wp-content/plugins/buddypress/bp-members/classes/class-bp-members-notices-list-table.php', esc_html__( 'Please use `BP_Members_Notices_List_Table` instead.', 'buddypress' ) );

/**
 * BuddyPress Notices List Table class.
 */
class BP_Messages_Notices_List_Table extends BP_Members_Notices_List_Table {

	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @param array $args Arguments passed to the WP_List_Table::constructor.
	 */
	public function __construct( $args = array() ) {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::__construct( $args );
	}

	/**
	 * Checks the current user's permissions
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 */
	public function ajax_user_can() {
		_deprecated_function( __METHOD__, '14.0.0' );
		return parent::ajax_user_can();
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 */
	public function prepare_items() {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::prepare_items();
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @return array
	 */
	public function get_columns() {
		_deprecated_function( __METHOD__, '14.0.0' );
		return parent::get_columns();
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::single_row( $item );
	}

	/**
	 * Generates content for the "subject" column.
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_subject( $item ) {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::column_subject( $item );
	}

	/**
	 * Generates content for the "message" column.
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_message( $item ) {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::column_message( $item );
	}

	/**
	 * Generates content for the "date_sent" column.
	 *
	 * @since 3.0.0
	 * @deprecated 14.0.0
	 *
	 * @param object $item The current item
	 */
	public function column_date_sent( $item ) {
		_deprecated_function( __METHOD__, '14.0.0' );
		parent::column_date_sent( $item );
	}
}
