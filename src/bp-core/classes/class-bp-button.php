<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.2.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * API to create BuddyPress buttons.
 *
 * @since 1.2.6
 * @since 2.7.0 Introduced $parent_element, $parent_attr, $button_element, $button_attr as
 *              $args parameters.
 *              Deprecated $wrapper, $wrapper_id, $wrapper_class, $link_href, $link_class,
 *              $link_id, $link_rel, $link_title as $args params.
 *
 * @param array $args {
 *     Array of arguments.
 *
 *     @type string      $id                String describing the button type.
 *     @type string      $component         The name of the component the button belongs to. Default: 'core'.
 *     @type bool        $must_be_logged_in Optional. Does the user need to be logged in to see this button? Default:
 *                                          true.
 *     @type bool        $block_self        Optional. True if the button should be hidden when a user is viewing his
 *                                          own profile. Default: true.
 *     @type string      $parent_element    Optional. Parent element to wrap button around. Default: 'div'.
 *     @type array       $parent_attr       Optional. Element attributes for parent element. Set whatever attributes
 *                                          like 'id', 'class' as array keys.
 *     @type string      $button_element    Optional. Button element. Default: 'a'.
 *     @type array       $button_attr       Optional. Button attributes. Set whatever attributes like 'id', 'class' as
 *                                          array keys.
 *     @type string      $link_text         Optional. Text to appear on the button. Default: ''.
 *     @type string|bool $wrapper           Deprecated. Use $parent_element instead.
 *     @type string      $wrapper_id        Deprecated. Use $parent_attr and set 'id' as array key.
 *     @type string      $wrapper_class     Deprecated. Use $parent_attr and set 'class' as array key.
 *     @type string      $link_href         Deprecated. Use $button_attr and set 'href' as array key.
 *     @type string      $link_class        Deprecated. Use $button_attr and set 'class' as array key.
 *     @type string      $link_id           Deprecated. Use $button_attr and set 'id' as array key.
 *     @type string      $link_rel          Deprecated. Use $button_attr and set 'rel' as array key.
 *     @type string      $link_title        Deprecated. Use $button_attr and set 'title' as array key.
 * }
 */
class BP_Button {

	/** Button properties *****************************************************/

	/**
	 * The button ID.
	 *
	 * @since 1.2.6
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * The name of the component that the button belongs to.
	 *
	 * @since 1.2.6
	 *
	 * @var string
	 */
	public $component = 'core';

	/**
	 * Does the user need to be logged in to see this button?
	 *
	 * @since 1.2.6
	 *
	 * @var bool
	 */
	public $must_be_logged_in = true;

	/**
	 * Whether the button should be hidden when viewing your own profile.
	 *
	 * @since 1.2.6
	 *
	 * @var bool
	 */
	public $block_self = true;

	/** Wrapper ***************************************************************/

	/**
	 * Parent element to wrap button around.
	 *
	 * @since 2.7.0
	 *
	 * @var string Default: 'div'.
	 */
	public $parent_element = '';

	/**
	 * Element attributes for parent element.
	 *
	 * @since 2.7.0
	 *
	 * @var array Set whatever attributes like 'id', 'class' as array key.
	 */
	public $parent_attr = array();

	/** Button ****************************************************************/

	/**
	 * Button element.
	 *
	 * @since 2.7.0
	 *
	 * @var string Default: 'a'.
	 */
	public $button_element = 'a';

	/**
	 * Button attributes.
	 *
	 * @since 2.7.0
	 *
	 * @var array Set whatever attributes like 'id', 'href' as array key.
	 */
	public $button_attr = array();

	/**
	 * The contents of the button link.
	 *
	 * @since 1.2.6
	 *
	 * @var string
	 */
	public $link_text = '';

	/**
	 * HTML result.
	 *
	 * @since 1.2.6
	 *
	 * @var string
	 */
	public $contents = '';

	/** Deprecated ***********************************************************/

	/**
	 * The type of DOM element to use for a wrapper.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Use $parent_element instead.
	 *
	 * @var string|bool
	 */
	public $wrapper = 'div';

	/**
	 * The DOM class of the button wrapper.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'class' key in $parent_attr instead.
	 *
	 * @var string
	 */
	public $wrapper_class = '';

	/**
	 * The DOM ID of the button wrapper.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'id' key in $parent_attr instead.
	 *
	 * @var string
	 */
	public $wrapper_id = '';

	/**
	 * The destination link of the button.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'href' key in $button_attr instead.
	 *
	 * @var string
	 */
	public $link_href = '';

	/**
	 * The DOM class of the button link.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'class' key in $button_attr instead.
	 *
	 * @var string
	 */
	public $link_class = '';

	/**
	 * The DOM ID of the button link.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'id' key in $button_attr instead.
	 *
	 * @var string
	 */
	public $link_id = '';

	/**
	 * The DOM rel value of the button link.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'rel' key in $button_attr instead.
	 *
	 * @var string
	 */
	public $link_rel = '';

	/**
	 * Title of the button link.
	 *
	 * @since      1.2.6
	 * @deprecated 2.7.0 Set 'title' key in $button_attr instead.
	 *
	 * @var string
	 */
	public $link_title = '';

	/** Methods ***************************************************************/

	/**
	 * Builds the button based on class parameters.
	 *
	 * @since 1.2.6
	 *
	 * @param array|string $args See {@BP_Button}.
	 */
	public function __construct( $args = '' ) {

		$r = wp_parse_args( $args, get_class_vars( __CLASS__ ) );

		// Backward compatibility with deprecated parameters.
		$r = $this->backward_compatibility_args( $r );

		// Deprecated. Subject to removal in a future release.
		$this->wrapper = $r['wrapper'];
		if ( !empty( $r['link_id']    ) ) $this->link_id    = ' id="' .    $r['link_id']    . '"';
		if ( !empty( $r['link_href']  ) ) $this->link_href  = ' href="' .  $r['link_href']  . '"';
		if ( !empty( $r['link_title'] ) ) $this->link_title = ' title="' . $r['link_title'] . '"';
		if ( !empty( $r['link_rel']   ) ) $this->link_rel   = ' rel="' .   $r['link_rel']   . '"';
		if ( !empty( $r['link_class'] ) ) $this->link_class = ' class="' . $r['link_class'] . '"';
		if ( !empty( $r['link_text']  ) ) $this->link_text  =              $r['link_text'];

		// Required button properties.
		$this->id                = $r['id'];
		$this->component         = $r['component'];
		$this->must_be_logged_in = (bool) $r['must_be_logged_in'];
		$this->block_self        = (bool) $r['block_self'];

		// $id and $component are required and component must be active.
		if ( empty( $r['id'] ) || empty( $r['component'] ) || ! bp_is_active( $this->component ) ) {
			return false;
		}

		// No button for guests if must be logged in.
		if ( true == $this->must_be_logged_in && ! is_user_logged_in() ) {
			return false;
		}

		// The block_self property.
		if ( true == $this->block_self ) {
			/*
			 * No button if you are the current user in a members loop.
			 *
			 * This condition takes precedence, because members loops can be found on user
			 * profiles.
			 */
			if ( bp_get_member_user_id() ) {
				if ( is_user_logged_in() && bp_loggedin_user_id() == bp_get_member_user_id() ) {
					return false;
				}

			// No button if viewing your own profile (and not in a members loop).
			} elseif ( bp_is_my_profile() ) {
				return false;
			}
		}

		// Should we use a parent element?
		if ( ! empty( $r['parent_element'] ) ) {
			if ( ! isset( $r['parent_attr']['class'] ) ) {
				$r['parent_attr']['class'] = '';
			}

			// Always add 'generic-button' class.
			if ( false === strpos( $r['parent_attr']['class'], 'generic-button' ) ) {
				if ( ! empty( $r['parent_attr']['class'] ) ) {
					$r['parent_attr']['class'] .= ' ';
				}
				$r['parent_attr']['class'] .= 'generic-button';
			}

			// Render parent element attributes.
			$parent_elem = new BP_Core_HTML_Element( array(
				'element' => $r['parent_element'],
				'attr'    => $r['parent_attr']
			) );

			// Set before and after.
			$before = $parent_elem->get( 'open_tag' );
			$after  = $parent_elem->get( 'close_tag' );

		// No parent element.
		} else {
			$before = $after = '';
		}

		// Button properties.
		$button = '';
		if ( ! empty( $r['button_element'] ) ) {
			$button = new BP_Core_HTML_Element( array(
				'element'    => $r['button_element'],
				'attr'       => $r['button_attr'],
				'inner_html' => ! empty( $r['link_text'] ) ? $r['link_text'] : ''
			) );
			$button = $button->contents();
		}

		// Build the button.
		$this->contents = $before . $button . $after;

		/**
		 * Filters the button based on class parameters.
		 *
		 * This filter is a dynamic filter based on component and component ID and
		 * allows button to be manipulated externally.
		 *
		 * @since 1.2.6
		 * @since 2.7.0 Added $r as a parameter.
		 *
		 * @param string    $contents HTML being used for the button.
		 * @param BP_Button $this     Current BP_Button instance.
		 * @param string    $before   HTML appended before the actual button.
		 * @param string    $after    HTML appended after the actual button.
		 * @param array     $r        Parsed button arguments.
		 */
		$this->contents = apply_filters( 'bp_button_' . $this->component . '_' . $this->id, $this->contents, $this, $before, $after, $r );
	}


	/**
	 * Provide backward compatibility for deprecated button arguments.
	 *
	 * @since 2.7.0.
	 *
	 * @param  array $r See {@link BP_Button} class for full documentation.
	 * @return array
	 */
	protected function backward_compatibility_args( $r = array() ) {
		// Array of deprecated arguments.
		$backpat_args = array(
			'wrapper', 'wrapper_class', 'wrapper_id',
			'link_href', 'link_class', 'link_id', 'link_rel', 'link_title'
		);

		foreach ( $backpat_args as $prop ) {
			if ( empty( $r[ $prop ] ) ) {
				continue;
			}

			$parent = $child = false;
			$sep    = strpos( $prop, '_' );

			// Check if this is an attribute.
			if ( false !== $sep ) {
				$child  = true;
				$parent = substr( $prop, 0, $sep );
			} else {
				$parent = $prop;
			}

			if ( 'wrapper' === $parent ) {
				$parent = 'parent';
			} else {
				$parent = 'button';
			}

			// Set element.
			if ( false === $child && empty( $r[ "{$parent}_element" ] ) ) {
				$r[ "{$parent}_element" ] = $r[ $prop ];

			// Set attributes.
			} elseif ( true === $child ) {
				$new_prop = substr( $prop, strpos( $prop, '_' ) +1 );
				if ( empty( $r[ "{$parent}_attr" ] ) ) {
					$r[ "{$parent}_attr" ] = array();
				}

				if ( empty( $r[ "{$parent}_attr" ][ $new_prop ] ) ) {
					$r[ "{$parent}_attr" ][ $new_prop ] = $r[ $prop ];
				}
			}
		}

		return $r;
	}


	/**
	 * Return the markup for the generated button.
	 *
	 * @since 1.2.6
	 *
	 * @return string Button markup.
	 */
	public function contents() {
		return $this->contents;
	}

	/**
	 * Output the markup of button.
	 *
	 * @since 1.2.6
	 */
	public function display() {
		if ( !empty( $this->contents ) )
			echo $this->contents;
	}
}
