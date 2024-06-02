<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.7.0
 */

/**
 * Generate markup for an HTML element.
 *
 * @since 2.7.0
 */
class BP_Core_HTML_Element {

	/**
	 * Open tag for an element.
	 *
	 * This would include attributes if applicable. eg. '<a href="" class="">'
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public $open_tag = '';

	/**
	 * Inner HTML for an element.
	 *
	 * For example, this could be anchor text within an <a> element.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public $inner_html = '';

	/**
	 * Closing tag for an element.
	 *
	 * For example, "</a>".
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public $close_tag = '';

	/**
	 * Constructor.
	 *
	 * @since 2.7.0
	 *
	 * @param array $r {
	 *     An array of arguments.
	 *     @type string $element    The element to render. eg. 'a' for the anchor element.
	 *     @type array  $attr       Optional. The element's attributes set as key/value pairs. eg.
	 *                              array( 'href' => 'http://example.com', 'class' => 'my-class' )
	 *     @type string $inner_html Optional. The inner HTML for the element if applicable. Please note that
	 *                              this isn't sanitized, so you should use your own sanitization routine
	 *                              before using this parameter.
	 * }
	 */
	public function __construct( $r = array() ) {
		$elem = sanitize_html_class( $r['element'] );
		if ( empty( $elem ) ) {
			return;
		}

		// Render attributes.
		$attributes = '';
		foreach ( (array) $r['attr'] as $attr => $val ) {
			// If attribute is empty, skip.
			if ( empty( $val ) ) {
				continue;
			}

			if ( 'href' === $attr || 'formaction' === $attr || 'src' === $attr ) {
				$val = esc_url( $val );
			} elseif ( 'id' === $attr ) {
				$val = sanitize_html_class( $val );
			} else {
				$val = esc_attr( $val );
			}

			$attributes .= sprintf( '%s="%s" ', sanitize_html_class( $attr ), $val );
		}

		// <input> / <img> is self-closing.
		if ( 'input' === $elem || 'img' === $elem ) {
			$this->open_tag = sprintf( '<%1$s %2$s />', $elem, $attributes );

			// All other elements.
		} else {
			$this->open_tag   = sprintf( '<%1$s %2$s>', $elem, $attributes );
			$this->inner_html = ! empty( $r['inner_html'] ) ? $r['inner_html'] : '';
			$this->close_tag  = sprintf( '</%1$s>', $elem );
		}
	}

	/**
	 * Returns a property from this class.
	 *
	 * @since 2.7.0
	 *
	 * @param string $prop Property name. Either 'open_tag', 'inner_html', 'close_tag'.
	 * @return string
	 */
	public function get( $prop = '' ) {
		if ( ! isset( $this->{$prop} ) ) {
			return '';
		}

		return $this->{$prop};
	}

	/**
	 * Returns full contents of HTML element.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
	public function contents() {
		return $this->open_tag . $this->inner_html . $this->close_tag;
	}
}
