<?php
/**
 * BP Buttons Group class.
 *
 * @since 3.0.0
 * @version 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds a group of BP_Button
 *
 * @since 3.0.0
 */
class BP_Buttons_Group {

	/**
	 * The parameters of the Group of buttons
	 *
	 * @var array
	 */
	protected $group = array();

	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional array having the following parameters {
	 *     @type string $id                A string to use as the unique ID for the button. Required.
	 *     @type int    $position          Where to insert the Button. Defaults to 99.
	 *     @type string $component         The Component's the button is build for (eg: Activity, Groups..). Required.
	 *     @type bool   $must_be_logged_in Whether the button should only be displayed to logged in users. Defaults to True.
	 *     @type bool   $block_self        Optional. True if the button should be hidden when a user is viewing his own profile.
	 *                                     Defaults to False.
	 *     @type string $parent_element    Whether to use a wrapper. Defaults to false.
	 *     @type string $parent_attr       set an array of attributes for the parent element.
	 *     @type string $button_element    Set this to 'button', 'img', or 'a', defaults to anchor.
	 *     @type string $button_attr       Any attributes required for the button_element
	 *     @type string $link_text         The text of the link. Required.
	 * }
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $arg ) {
			$this->add( $arg );
		}
	}


	/**
	 * Sort the Buttons of the group according to their position attribute
	 *
	 * @since 3.0.0
	 *
	 * @param array the list of buttons to sort.
	 *
	 * @return array the list of buttons sorted.
	 */
	public function sort( $buttons ) {
		$sorted = array();

		foreach ( $buttons as $button ) {
			$position = 99;

			if ( isset( $button['position'] ) ) {
				$position = (int) $button['position'];
			}

			// If position is already taken, move to the first next available
			if ( isset( $sorted[ $position ] ) ) {
				$sorted_keys = array_keys( $sorted );

				do {
					$position += 1;
				} while ( in_array( $position, $sorted_keys, true ) );
			}

			$sorted[ $position ] = $button;
		}

		ksort( $sorted );
		return $sorted;
	}

	/**
	 * Get the BuddyPress buttons for the group
	 *
	 * @since 3.0.0
	 *
	 * @param bool $sort whether to sort the buttons or not.
	 *
	 * @return array An array of HTML links.
	 */
	public function get( $sort = true ) {
		$buttons = array();

		if ( empty( $this->group ) ) {
			return $buttons;
		}

		if ( true === $sort ) {
			$this->group = $this->sort( $this->group );
		}

		foreach ( $this->group as $key_button => $button ) {
			// Reindex with ids.
			if ( true === $sort ) {
				$this->group[ $button['id'] ] = $button;
				unset( $this->group[ $key_button ] );
			}

			$buttons[ $button['id'] ] = bp_get_button( $button );
		}

		return $buttons;
	}

	/**
	 * Update the group of buttons
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional. See the __constructor for a description of this argument.
	 */
	public function update( $args = array() ) {
		foreach ( $args as $id => $params ) {
			if ( isset( $this->group[ $id ] ) ) {
				$this->group[ $id ] = bp_parse_args(
					$params,
					$this->group[ $id ],
					'buttons_group_update'
				);
			} else {
				$this->add( $params );
			}
		}
	}

	/**
	 * Adds a button.
	 *
	 * @since 9.0.0
	 *
	 * @param array $args Required. See the __constructor for a description of this argument.
	 * @return bool true on success, false on failure to add.
	 */
	private function add( $args ) {
		$r = bp_parse_args(
			(array) $args,
			array(
				'id'                => '',
				'position'          => 99,
				'component'         => '',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'parent_element'    => false,
				'parent_attr'       => array(),
				'button_element'    => 'a',
				'button_attr'       => array(),
				'link_text'         => '',
			),
			'buttons_group_constructor'
		);

		// Just don't set the button if a param is missing
		if ( empty( $r['id'] ) || empty( $r['component'] ) || empty( $r['link_text'] ) ) {
			return false;
		}

		$r['id'] = sanitize_key( $r['id'] );

		// If the button already exist don't add it
		if ( isset( $this->group[ $r['id'] ] ) ) {
			return false;
		}

		/*
		 * If, in bp_nouveau_get_*_buttons(), we pass through a false value for 'parent_element'
		 * but we have attributtes for it in the array, let's default to setting a div.
		 *
		 * Otherwise, the original false value will be passed through to BP buttons.
		 * @todo: this needs review, probably trying to be too clever
		 */
		if ( ( ! empty( $r['parent_attr'] ) ) && false === $r['parent_element'] ) {
			$r['parent_element'] = 'div';
		}

		$this->group[ $r['id'] ] = $r;
		return true;
	}
}
