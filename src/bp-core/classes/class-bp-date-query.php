<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Date_Query' ) ) :

/**
 * BuddyPress date query class.
 *
 * Extends the {@link WP_Date_Query} class for use with BuddyPress.
 *
 * @since 2.1.0
 *
 * @param array $date_query {
 *     Date query arguments.  See first parameter of {@link WP_Date_Query::__construct()}.
 * }
 * @param string $column The DB column to query against.
 */
class BP_Date_Query extends WP_Date_Query {
	/**
	 * The column to query against. Can be changed via the query arguments.
	 *
	 * @var string
	 */
	public $column;

	/**
	 * Whether to prepend the 'AND' operator to the WHERE SQL clause.
	 *
	 * @since 10.0.0
	 *
	 * @var bool
	 */
	public $prepend_and = false;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @since 10.0.0 Added $prepend_and argument.
	 *
	 * @param array  $date_query  Date query arguments.
	 * @param string $column      The DB column to query against.
	 * @param bool   $prepend_and Whether to prepend the 'AND' operator to the WHERE SQL clause.
	 *
	 * @see WP_Date_Query::__construct()
	 */
	public function __construct( $date_query, $column = '', $prepend_and = false ) {
		if ( ! empty( $column ) ) {
			$this->column = $column;
			add_filter( 'date_query_valid_columns', array( $this, 'register_date_column' ) );
		}

		if ( ! empty( $prepend_and ) ) {
			$this->prepend_and = true;
		}

		parent::__construct( $date_query, $column );
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		remove_filter( 'date_query_valid_columns', array( $this, 'register_date_column' ) );
	}

	/**
	 * Registers our date column with WP Date Query to pass validation.
	 *
	 * @param array $retval Current DB columns.
	 * @return array
	 */
	public function register_date_column( $retval = array() ) {
		$retval[] = $this->column;
		return $retval;
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Since BuddyPress builds its SQL queries differently than WP_Query, we have
	 * to override the parent method to remove the leading 'AND' operator from the
	 * WHERE clause.
	 *
	 * @since 10.0.0
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses() {
		// If we want to have the leading 'AND' operator, just use parent method.
		if ( $this->prepend_and ) {
			return parent::get_sql_clauses();
		}

		// If we're here, that means we do not want the leading 'AND' operator.
		return $this->get_sql_for_query( $this->queries );
	}

	/**
	 * Helper method to generate and fetch the WHERE SQL clause for a date query.
	 *
	 * See {@link BP_Date_Query::__construct()} for all argument documentation.
	 *
	 * @since 10.0.0
	 *
	 * @param  array  $date_query  Date query arguments.
	 * @param  string $column      DB column to query against date.
	 * @param  bool   $prepend_and Whether to prepend the 'AND' operator to the WHERE clause.
	 * @return string
	 */
	public static function get_where_sql( $date_query = array(), $column = '', $prepend_and = false ) {
		$sql = '';

		// Generate and fetch the WHERE clause for a date query.
		if ( ! empty( $date_query ) && is_array( $date_query ) && ! empty( $column ) ) {
			$date_query = new self( $date_query, $column, $prepend_and );
			$sql = $date_query->get_sql();
		}

		return $sql;
	}
}
endif;
