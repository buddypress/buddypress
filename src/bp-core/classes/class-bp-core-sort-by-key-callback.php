<?php

/**
 * Callback class for bp_sort_by_key().
 *
 * Used in place of an anonymous closure.
 *
 * Developers should not use this class directly, as it may be removed once support for PHP 5.2 is dropped.
 *
 * @ignore
 *
 * @since 2.5.0
 */
class BP_Core_Sort_By_Key_Callback {
	/**
	 * Object/array index to use for sorting.
	 *
	 * @since 2.5.0
	 * @var mixed
	 */
	protected $key;

	/**
	 * Sort type.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed  $key  Object or array index to use for sorting.
	 * @param string $type Sort type.
	 */
	public function __construct( $key, $type ) {
		$this->key  = $key;
		$this->type = $type;
	}

	/**
	 * Sort callback.
	 *
	 * @since 2.5.0
	 *
	 * @param $a object|array
	 * @param $b object|array
	 * @return int
	 */
	public function sort_callback( $a, $b ) {
		$values = array( 0 => false, 1 => false, );
		$func_args = func_get_args();
		foreach ( $func_args as $indexi => $index ) {
			if ( isset( $index->{$this->key} ) ) {
				$values[ $indexi ] = $index->{$this->key};
			} elseif ( isset( $index[ $this->key ] ) ) {
				$values[ $indexi ] = $index[ $this->key ];
			}
		}

		if ( isset( $values[0], $values[1] ) ) {
			if ( 'num' === $this->type ) {
				$cmp = $values[0] - $values[1];
			} else {
				$cmp = strcmp( $values[0], $values[1] );
			}

			if ( 0 > $cmp ) {
				$retval = -1;
			} elseif ( 0 < $cmp ) {
				$retval = 1;
			} else {
				$retval = 0;
			}
			return $retval;
		} else {
			return 0;
		}
	}
}
