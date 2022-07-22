<?php
/**
 * Blogs functions
 *
 * @since 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @since 3.0.0
 */
function bp_nouveau_get_blogs_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'blogs',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array( 'selected' ),
		'link'      => bp_get_root_domain() . '/' . bp_get_blogs_root_slug(),
		'text'      => __( 'All Sites', 'buddypress' ),
		'count'     => bp_get_total_blog_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		$my_blogs_count = bp_get_total_blog_count_for_user( bp_loggedin_user_id() );

		// If the user has blogs create a nav item
		if ( $my_blogs_count ) {
			$nav_items['personal'] = array(
				'component' => 'blogs',
				'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_nouveau_get_component_slug( 'blogs' ),
				'text'      => __( 'My Sites', 'buddypress' ),
				'count'     => $my_blogs_count,
				'position'  => 15,
			);
		}

		// If the user can create blogs, add the create nav
		if ( bp_blog_signup_enabled() ) {
			$nav_items['create'] = array(
				'component' => 'blogs',
				'slug'      => 'create', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array( 'no-ajax', 'site-create', 'create-button' ),
				'link'      => trailingslashit( bp_get_blogs_directory_permalink() . 'create' ),
				'text'      => __( 'Create a Site', 'buddypress' ),
				'count'     => false,
				'position'  => 999,
			);
		}
	}

	// Check for the deprecated hook :
	$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( 'bp_blogs_directory_blog_types', 'blogs', 20 );

	if ( ! empty( $extra_nav_items ) ) {
		$nav_items = array_merge( $nav_items, $extra_nav_items );
	}

	/**
	 * Use this filter to introduce your custom nav items for the blogs directory.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $nav_items The list of the blogs directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_blogs_directory_nav_items', $nav_items );
}

/**
 * Get Dropdown filters for the blogs component
 *
 * @since 3.0.0
 *
 * @param string $context 'directory' or 'user'
 *
 * @return array the filters
 */
function bp_nouveau_get_blogs_filters( $context = '' ) {
	if ( empty( $context ) ) {
		return array();
	}

	$action = '';
	if ( 'user' === $context ) {
		$action = 'bp_member_blog_order_options';
	} elseif ( 'directory' === $context ) {
		$action = 'bp_blogs_directory_order_options';
	}

	/**
	 * Recommended, filter here instead of adding an action to 'bp_member_blog_order_options'
	 * or 'bp_blogs_directory_order_options'
	 *
	 * @since 3.0.0
	 *
	 * @param array  the blogs filters.
	 * @param string the context.
	 */
	$filters = apply_filters( 'bp_nouveau_get_blogs_filters', array(
		'active'       => __( 'Last Active', 'buddypress' ),
		'newest'       => __( 'Newest', 'buddypress' ),
		'alphabetical' => __( 'Alphabetical', 'buddypress' ),
	), $context );

	if ( $action ) {
		return bp_nouveau_parse_hooked_options( $action, $filters );
	}

	return $filters;
}

/**
 * Add settings to the customizer for the blogs component.
 *
 * @since 3.0.0
 *
 * @param array $settings the settings to add.
 *
 * @return array the settings to add.
 */
function bp_nouveau_blogs_customizer_settings( $settings = array() ) {
	return array_merge( $settings, array(
		'bp_nouveau_appearance[blogs_layout]' => array(
			'index'             => 'blogs_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
	) );
}

/**
 * Add controls for the settings of the customizer for the blogs component.
 *
 * @since 3.0.0
 *
 * @param array $controls the controls to add.
 *
 * @return array the controls to add.
 */
function bp_nouveau_blogs_customizer_controls( $controls = array() ) {
	return array_merge( $controls, array(
		'blogs_layout' => array(
			'label'      => __( 'Sites loop:', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[blogs_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'sites_dir_layout' => array(
			'label'      => __( 'Use column navigation for the Sites directory.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[sites_dir_layout]',
			'type'       => 'checkbox',
		),
		'sites_dir_tabs' => array(
			'label'      => __( 'Use tab styling for Sites directory navigation.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[sites_dir_tabs]',
			'type'       => 'checkbox',
		),
	) );
}

/**
 * Inline script to toggle the signup blog form
 *
 * @since 3.0.0
 *
 * @return string Javascript output
 */
function bp_nouveau_get_blog_signup_inline_script() {
	return '
		( function( $ ) {
			if ( $( \'body\' ).hasClass( \'register\' ) ) {
				var blog_checked = $( \'#signup_with_blog\' );

				// hide "Blog Details" block if not checked by default
				if ( ! blog_checked.prop( \'checked\' ) ) {
					$( \'#blog-details\' ).toggle();
				}

				// toggle "Blog Details" block whenever checkbox is checked
				blog_checked.change( function( event ) {
					// Toggle HTML5 required attribute.
					$.each( $( \'#blog-details\' ).find( \'[aria-required]\' ), function( i, input ) {
						$( input ).prop( \'required\',  $( event.target ).prop( \'checked\' ) );
					} );

					$( \'#blog-details\' ).toggle();
				} );
			}
		} )( jQuery );
	';
}

/**
 * Filter bp_get_blog_class().
 * Adds a class if blog item has a latest post.
 *
 * @since 3.0.0
 */
function bp_nouveau_blog_loop_item_has_lastest_post( $classes ) {
	if ( bp_get_blog_latest_post_title() ) {
		$classes[] = 'has-latest-post';
	}

	return $classes;
}
add_filter( 'bp_get_blog_class', 'bp_nouveau_blog_loop_item_has_lastest_post' );
