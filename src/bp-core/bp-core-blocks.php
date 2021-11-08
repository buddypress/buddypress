<?php
/**
 * Core BP Blocks functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 6.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress blocks require the BP REST API.
 *
 * @since 6.0.0
 *
 * @return bool True if the current installation supports BP Blocks.
 *              False otherwise.
 */
function bp_support_blocks() {
	/**
	 * Filter here, returning `false`, to completely disable BuddyPress blocks.
	 *
	 * @since 10.0.0
	 *
	 * @param bool $value True if the BP REST API is available. False otherwise.
	 */
	return apply_filters( 'bp_support_blocks', bp_rest_api_is_available() );
}

/**
 * Registers the BP Block components.
 *
 * @since 6.0.0
 * @since 9.0.0 Adds a dependency to `wp-server-side-render` if WP >= 5.3.
 *              Uses a dependency to `wp-editor` otherwise.
 */
function bp_register_block_components() {
	wp_register_script(
		'bp-block-components',
		plugins_url( 'js/block-components.js', __FILE__ ),
		array(
			'wp-element',
			'wp-components',
			'wp-i18n',
			'wp-api-fetch',
			'wp-url',
		),
		bp_get_version(),
		false
	);

	// Adds BP Block Components to the `bp` global.
	wp_add_inline_script(
		'bp-block-components',
		'window.bp = window.bp || {};
		bp.blockComponents = bpBlock.blockComponents;
		delete bpBlock;',
		'after'
	);
}
add_action( 'bp_blocks_init', 'bp_register_block_components', 1 );

/**
 * Registers the BP Block Assets.
 *
 * @since 9.0.0
 */
function bp_register_block_assets() {
	wp_register_script(
		'bp-block-data',
		plugins_url( 'js/block-data.js', __FILE__ ),
		array(
			'wp-data',
			'wp-api-fetch',
			'lodash',
		),
		bp_get_version(),
		false
	);

	// Adds BP Block Assets to the `bp` global.
	wp_add_inline_script(
		'bp-block-data',
		sprintf(
			'window.bp = window.bp || {};
			bp.blockData = bpBlock.blockData;
			bp.blockData.embedScriptURL = \'%s\';
			delete bpBlock;',
			esc_url_raw( includes_url( 'js/wp-embed.min.js' ) )
		),
		'after'
	);
}
add_action( 'bp_blocks_init', 'bp_register_block_assets', 2 );

/**
 * Filters the Block Editor settings to gather BuddyPress ones into a `bp` key.
 *
 * @since 6.0.0
 *
 * @param array $editor_settings Default editor settings.
 * @return array The editor settings including BP blocks specific ones.
 */
function bp_blocks_editor_settings( $editor_settings = array() ) {
	/**
	 * Filter here to include your BP Blocks specific settings.
	 *
	 * @since 6.0.0
	 *
	 * @param array $bp_editor_settings BP blocks specific editor settings.
	 */
	$bp_editor_settings = (array) apply_filters( 'bp_blocks_editor_settings', array() );

	if ( $bp_editor_settings ) {
		$editor_settings['bp'] = $bp_editor_settings;
	}

	return $editor_settings;
}

/**
 * Select the right `block_editor_settings` filter according to WP version.
 *
 * @since 8.0.0
 */
function bp_block_init_editor_settings_filter() {
	if ( function_exists( 'get_block_editor_settings' ) ) {
		add_filter( 'block_editor_settings_all', 'bp_blocks_editor_settings' );
	} else {
		add_filter( 'block_editor_settings', 'bp_blocks_editor_settings' );
	}
}
add_action( 'bp_init', 'bp_block_init_editor_settings_filter' );

/**
 * Preload the Active BuddyPress Components.
 *
 * @since 9.0.0
 *
 * @param string[] $paths The Block Editors preload paths.
 * @return string[] The Block Editors preload paths.
 */
function bp_blocks_preload_paths( $paths = array() ) {
	return array_merge(
		$paths,
		array(
			'/buddypress/v1/components?status=active',
		)
	);
}
add_filter( 'block_editor_rest_api_preload_paths', 'bp_blocks_preload_paths' );

/**
 * Register a BuddyPress block type.
 *
 * @since 6.0.0
 *
 * @param array $args The registration arguments for the block type.
 * @return BP_Block   The BuddyPress block type object.
 */
function bp_register_block( $args = array() ) {
	return new BP_Block( $args );
}

/**
 * Gets a Widget Block list of classnames.
 *
 * @since 9.0.0
 *
 * @param string $block_name The Block name.
 * @return array The list of widget classnames for the Block.
 */
function bp_blocks_get_widget_block_classnames( $block_name = '' ) {
	$components         = bp_core_get_active_components( array(), 'objects' );
	$components['core'] = buddypress()->core;
	$classnames         = array();

	foreach ( $components as $component ) {
		if ( isset( $component->block_globals[ $block_name ] ) ) {
			$block_props = $component->block_globals[ $block_name ]->props;

			if ( isset( $block_props['widget_classnames'] ) && $block_props['widget_classnames'] ) {
				$classnames = (array) $block_props['widget_classnames'];
				break;
			}
		}
	}

	return $classnames;
}

/**
 * Make sure the BP Widget Block classnames are included into Widget Blocks.
 *
 * @since 9.0.0
 *
 * @param string $classname The classname to be used in the block widget's container HTML.
 * @param string $block_name The name of the block.
 * @return string The classname to be used in the block widget's container HTML.
 */
function bp_widget_block_dynamic_classname( $classname, $block_name ) {
	$bp_classnames = bp_blocks_get_widget_block_classnames( $block_name );

	if ( $bp_classnames ) {
		$bp_classnames = array_map( 'sanitize_html_class', $bp_classnames );
		$classname    .= ' ' . implode( ' ', $bp_classnames );
	}

	return $classname;
}
add_filter( 'widget_block_dynamic_classname', 'bp_widget_block_dynamic_classname', 10, 2 );

/**
 * Create a link to the registration form for use on the bottom of the login form widget.
 *
 * @since 9.0.0
 *
 * @param string $content Content to display. Default empty.
 * @param array  $args    Array of login form arguments.
 * @return string         HTML output.
 */
function bp_blocks_get_login_widget_registration_link( $content = '', $args = array() ) {
	if ( isset( $args['form_id'] ) && 'bp-login-widget-form' === $args['form_id'] ) {
		if ( bp_get_signup_allowed() ) {
			$content .= sprintf(
				'<p class="bp-login-widget-register-link"><a href="%1$s">%2$s</a></p>',
				esc_url( bp_get_signup_page() ),
				esc_html__( 'Register', 'buddypress' )
			);
		}

		if ( isset( $args['include_pwd_link'] ) && true === $args['include_pwd_link'] ) {
			$content .= sprintf(
				'<p class="bp-login-widget-pwd-link"><a href="%1$s">%2$s</a></p>',
				esc_url( wp_lostpassword_url( bp_get_root_domain() ) ),
				esc_html__( 'Lost your password?', 'buddypress' )
			);
		}
	}

	$action_output = '';
	if ( has_action( 'bp_login_widget_form' ) ) {
		ob_start();
		/**
		 * Fires inside the display of the login widget form.
		 *
		 * @since 2.4.0
		 */
		do_action( 'bp_login_widget_form' );
		$action_output = ob_get_clean();
	}

	if ( $action_output ) {
		$content .= $action_output;
	}

	return $content;
}

/**
 * Callback function to render the BP Login Form.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_block_render_login_form_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title'         => '',
			'forgotPwdLink' => false,
		)
	);

	$title = $block_args['title'];

	$classnames         = 'widget_bp_core_login_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$widget_content = '';

	if ( $title ) {
		$widget_content .= sprintf(
			'<h2 class="widget-title">%s</h2>',
			esc_html( $title )
		);
	}

	if ( is_user_logged_in() ) {
		$action_output = '';
		if ( has_action( 'bp_before_login_widget_loggedin' ) ) {
			ob_start();
			/**
			 * Fires before the display of widget content if logged in.
			 *
			 * @since 1.9.0
			 */
			do_action( 'bp_before_login_widget_loggedin' );
			$action_output = ob_get_clean();
		}

		if ( $action_output ) {
			$widget_content .= $action_output;
		}

		$widget_content .= sprintf(
			'<div class="bp-login-widget-user-avatar">
				<a href="%1$s">
					%2$s
				</a>
			</div>',
			bp_loggedin_user_domain(),
			bp_get_loggedin_user_avatar(
				array(
					'type'   => 'thumb',
					'width'  => 50,
					'height' => 50,
				)
			)
		);

		$widget_content .= sprintf(
			'<div class="bp-login-widget-user-links">
				<div class="bp-login-widget-user-link">%1$s</div>
				<div class="bp-login-widget-user-logout"><a class="logout" href="%2$s">%3$s</a></div>
			</div>',
			bp_core_get_userlink( bp_loggedin_user_id() ),
			wp_logout_url( bp_get_requested_url() ),
			__( 'Log Out', 'buddypress' )
		);

		$action_output = '';
		if ( has_action( 'bp_after_login_widget_loggedin' ) ) {
			ob_start();
			/**
			 * Fires after the display of widget content if logged in.
			 *
			 * @since 1.9.0
			 */
			do_action( 'bp_after_login_widget_loggedin' );
			$action_output = ob_get_clean();
		}

		if ( $action_output ) {
			$widget_content .= $action_output;
		}
	} else {
		$action_output = '';
		$pwd_link      = (bool) $block_args['forgotPwdLink'];

		if ( has_action( 'bp_before_login_widget_loggedout' ) ) {
			ob_start();
			/**
			 * Fires before the display of widget content if logged out.
			 *
			 * @since 1.9.0
			 */
			do_action( 'bp_before_login_widget_loggedout' );
			$action_output = ob_get_clean();
		}

		if ( $action_output ) {
			$widget_content .= $action_output;
		}

		add_filter( 'login_form_bottom', 'bp_blocks_get_login_widget_registration_link', 10, 2 );

		$widget_content .= wp_login_form(
			array(
				'echo'             => false,
				'form_id'          => 'bp-login-widget-form',
				'id_username'      => 'bp-login-widget-user-login',
				'label_username'   => __( 'Username', 'buddypress' ),
				'id_password'      => 'bp-login-widget-user-pass',
				'label_password'   => __( 'Password', 'buddypress' ),
				'id_remember'      => 'bp-login-widget-rememberme',
				'id_submit'        => 'bp-login-widget-submit',
				'include_pwd_link' => $pwd_link,
			)
		);

		remove_filter( 'login_form_bottom', 'bp_blocks_get_login_widget_registration_link', 10, 2 );

		$action_output = '';
		if ( has_action( 'bp_after_login_widget_loggedout' ) ) {
			ob_start();
			/**
			 * Fires after the display of widget content if logged out.
			 *
			 * @since 1.9.0
			 */
			do_action( 'bp_after_login_widget_loggedout' );
			$action_output = ob_get_clean();
		}

		if ( $action_output ) {
			$widget_content .= $action_output;
		}
	}

	if ( ! did_action( 'dynamic_sidebar_before' ) ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$widget_content
		);
	}

	return $widget_content;
}
