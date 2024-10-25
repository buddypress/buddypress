<?php
/**
 * BuddyPress Core Theme Compatibility Base Class.
 *
 * @package BuddyPress
 * @subpackage ThemeCompatibility
 * @since 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Theme Compatibility base class.
 *
 * This is only intended to be extended, and is included here as a basic guide
 * for future Theme Packs to use. {@link BP_Legacy} is a good example of
 * extending this class.
 *
 * @since 1.7.0
 * @since 14.3.0 Changed the `$name` property's description.
 *
 * @todo We should probably do something similar to BP_Component::start().
 * @todo If this is only intended to be extended, it should be abstract.
 *
 * @param array $properties {
 *     An array of properties describing the theme compat package.
 *     @type string $id      ID of the package. Must be unique.
 *     @type string $name    Raw name for the theme. This should match the name given
 *                           in style.css.
 *     @type string $version Theme version. Used for busting script and style
 *                           browser caches.
 *     @type string $dir     Filesystem path of the theme.
 *     @type string $url     Base URL of the theme.
 * }
 */
class BP_Theme_Compat {

	/**
	 * Template package properties, as passed to the constructor.
	 *
	 * @since 1.7.0
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Pass the $properties to the object on creation.
	 *
	 * @since 1.7.0
	 *
	 * @param array $properties Array of properties for BP_Theme_Compat.
	 */
	public function __construct( array $properties = array() ) {
		$this->_data = $properties;
	}

	/**
	 * Set up the BuddyPress-specific theme compat methods.
	 *
	 * Themes should use this method in their constructor.
	 *
	 * @since 1.7.0
	 */
	protected function start() {
		// Sanity check.
		if ( ! bp_use_theme_compat_with_current_theme() ) {
			return;
		}

		// Setup methods.
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set up global data for your template package.
	 *
	 * Meant to be overridden in your class. See
	 * {@link BP_Legacy::setup_globals()} for an example.
	 *
	 * @since 1.7.0
	 */
	protected function setup_globals() {}

	/**
	 * Set up theme hooks for your template package.
	 *
	 * Meant to be overridden in your class. See
	 * {@link BP_Legacy::setup_actions()} for an example.
	 *
	 * @since 1.7.0
	 */
	protected function setup_actions() {}

	/**
	 * Set a theme's property.
	 *
	 * @since 1.7.0
	 *
	 * @param string $property Property name.
	 * @param mixed  $value    Property value.
	 * @return bool
	 */
	public function __set( $property, $value ) {
		$this->_data[ $property ] = $value;

		return $this->_data[ $property ];
	}

	/**
	 * Get a theme's property.
	 *
	 * @since 1.7.0
	 *
	 * @param string $property Property name.
	 * @return mixed The value of the property if it exists, otherwise an
	 *               empty string.
	 */
	public function __get( $property ) {
		return array_key_exists( $property, $this->_data ) ? $this->_data[ $property ] : '';
	}

	/**
	 * Check a theme's property exists.
	 *
	 * @since 9.0.0
	 *
	 * @param string $property Property name.
	 * @return bool True if the property exists. False otherwise.
	 */
	public function __isset( $property ) {
		return array_key_exists( $property, $this->_data );
	}
}
