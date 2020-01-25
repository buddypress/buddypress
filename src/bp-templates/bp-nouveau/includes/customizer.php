<?php
/**
 * Code to hook into the WP Customizer
 *
 * @since 3.0.0
 * @version 3.1.0
 */

/**
 * Add a specific panel for the BP Nouveau Template Pack.
 *
 * @since 3.0.0
 *
 * @param WP_Customize_Manager $wp_customize WordPress customizer.
 */
function bp_nouveau_customize_register( WP_Customize_Manager $wp_customize ) {
	if ( ! bp_is_root_blog() ) {
		return;
	}

	require_once( trailingslashit( bp_nouveau()->includes_dir ) . 'customizer-controls.php' );
	$wp_customize->register_control_type( 'BP_Nouveau_Nav_Customize_Control' );
	$bp_nouveau_options = bp_nouveau_get_appearance_settings();

	$wp_customize->add_panel( 'bp_nouveau_panel', array(
		'description' => __( 'Customize the appearance of BuddyPress Nouveau Template pack.', 'buddypress' ),
		'title'       => _x( 'BuddyPress Nouveau', 'Customizer Panel', 'buddypress' ),
		'priority'    => 200,
	) );

	/**
	 * Filters the BuddyPress Nouveau customizer sections and their arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value Array of Customizer sections.
	 */
	$sections = apply_filters( 'bp_nouveau_customizer_sections', array(
		'bp_nouveau_general_settings' => array(
			'title'       => __( 'General BP Settings', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 10,
			'description' => __( 'Configure general BuddyPress appearance options.', 'buddypress' ),
		),
		'bp_nouveau_user_front_page' => array(
			'title'       => __( 'Member front page', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 30,
			'description' => __( 'Configure the default front page for members.', 'buddypress' ),
		),
		'bp_nouveau_user_primary_nav' => array(
			'title'       => __( 'Member navigation', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 50,
			'description' => __( 'Customize the navigation menu for members. In the preview window, navigate to a user to preview your changes.', 'buddypress' ),
		),
		'bp_nouveau_loops_layout' => array(
			'title'       => __( 'Loop layouts', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 70,
			'description' => __( 'Set the number of columns to use for BuddyPress loops.', 'buddypress' ),
		),
		'bp_nouveau_dir_layout' => array(
			'title'       => __( 'Directory layouts', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 80,
			'description' => __( 'Select the layout style for directory content &amp; navigation.', 'buddypress' ),
		),
	) );

	// Add the sections to the customizer
	foreach ( $sections as $id_section => $section_args ) {
		$wp_customize->add_section( $id_section, $section_args );
	}

	/**
	 * Filters the BuddyPress Nouveau customizer settings and their arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value Array of Customizer settings.
	 */
	$settings = apply_filters( 'bp_nouveau_customizer_settings', array(
		'bp_nouveau_appearance[avatar_style]' => array(
			'index'             => 'avatar_style',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_front_page]' => array(
			'index'             => 'user_front_page',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_front_bio]' => array(
			'index'             => 'user_front_bio',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_nav_display]' => array(
			'index'             => 'user_nav_display',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_nav_tabs]' => array(
			'index'             => 'user_nav_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_subnav_tabs]' => array(
			'index'             => 'user_subnav_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_nav_order]' => array(
			'index'             => 'user_nav_order',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_order',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_layout]' => array(
			'index'             => 'members_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_group_layout]' => array(
			'index'             => 'members_group_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_friends_layout]' => array(
			'index'             => 'members_friends_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[activity_dir_layout]' => array(
			'index'             => 'activity_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[activity_dir_tabs]' => array(
			'index'             => 'activity_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_dir_layout]' => array(
			'index'             => 'members_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_dir_tabs]' => array(
			'index'             => 'members_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[groups_dir_layout]' => array(
			'index'             => 'groups_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[sites_dir_layout]' => array(
			'index'             => 'sites_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[sites_dir_tabs]' => array(
			'index'             => 'sites_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
	) );

	if ( current_theme_supports( 'align-wide' ) ) {
		$settings['bp_nouveau_appearance[global_alignment]'] = array(
			'index'             => 'global_alignment',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'sanitize_html_class',
			'transport'         => 'refresh',
			'type'              => 'option',
		);
	}

	// Add the settings
	foreach ( $settings as $id_setting => $setting_args ) {
		$args = array();

		if ( empty( $setting_args['index'] ) || ! isset( $bp_nouveau_options[ $setting_args['index'] ] ) ) {
			continue;
		}

		$args = array_merge( $setting_args, array( 'default' => $bp_nouveau_options[ $setting_args['index'] ] ) );

		$wp_customize->add_setting( $id_setting, $args );
	}

	$controls = array(
		'bp_site_avatars' => array(
			'label'      => __( 'Use the round style for member and group avatars.', 'buddypress' ),
			'section'    => 'bp_nouveau_general_settings',
			'settings'   => 'bp_nouveau_appearance[avatar_style]',
			'type'       => 'checkbox',
		),
		'user_front_page' => array(
			'label'      => __( 'Enable default front page for member profiles.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_front_page',
			'settings'   => 'bp_nouveau_appearance[user_front_page]',
			'type'       => 'checkbox',
		),
		'user_front_bio' => array(
			'label'      => __( 'Display the biographical info from the member\'s WordPress profile.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_front_page',
			'settings'   => 'bp_nouveau_appearance[user_front_bio]',
			'type'       => 'checkbox',
		),
		'user_nav_display' => array(
			'label'      => __( 'Display the member navigation vertically.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_display]',
			'type'       => 'checkbox',
		),
		'user_nav_tabs' => array(
			'label'      => __( 'Use tab styling for primary nav.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_tabs]',
			'type'       => 'checkbox',
		),
		'user_subnav_tabs' => array(
			'label'      => __( 'Use tab styling for secondary nav.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_subnav_tabs]',
			'type'       => 'checkbox',
		),
		'user_nav_order' => array(
			'class'      => 'BP_Nouveau_Nav_Customize_Control',
			'label'      => __( 'Reorder the primary navigation for a user.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_order]',
			'type'       => 'user',
		),
		'members_layout' => array(
			'label'      => __( 'Members', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[members_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'members_friends_layout' => array(
			'label'      => __( 'Member > Friends', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[members_friends_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'members_dir_layout' => array(
			'label'      => __( 'Use column navigation for the Members directory.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[members_dir_layout]',
			'type'       => 'checkbox',
		),
		'members_dir_tabs' => array(
			'label'      => __( 'Use tab styling for Members directory navigation.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[members_dir_tabs]',
			'type'       => 'checkbox',
		),
	);

	/**
	 * Filters the BuddyPress Nouveau customizer controls and their arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value Array of Customizer controls.
	 */
	$controls = apply_filters( 'bp_nouveau_customizer_controls', $controls );

	if ( current_theme_supports( 'align-wide' ) ) {
		$controls['global_alignment'] = array(
			'label'      => __( 'Select the BuddyPress container width for your site.', 'buddypress' ),
			'section'    => 'bp_nouveau_general_settings',
			'settings'   => 'bp_nouveau_appearance[global_alignment]',
			'type'       => 'select',
			'choices'    => array(
				'alignnone' => __( 'Default width', 'buddypress' ),
				'alignwide' => __( 'Wide width', 'buddypress' ),
				'alignfull' => __( 'Full width', 'buddypress' ),
			),
		);
	}

	// Add the controls to the customizer's section
	foreach ( $controls as $id_control => $control_args ) {
		if ( empty( $control_args['class'] ) ) {
			$wp_customize->add_control( $id_control, $control_args );
		} else {
			$wp_customize->add_control( new $control_args['class']( $wp_customize, $id_control, $control_args ) );
		}
	}
}
add_action( 'bp_customize_register', 'bp_nouveau_customize_register', 10, 1 );

/**
 * Enqueue needed JS for our customizer Settings & Controls
 *
 * @since 3.0.0
 */
function bp_nouveau_customizer_enqueue_scripts() {
	$min = bp_core_get_minified_asset_suffix();

	wp_enqueue_script(
		'bp-nouveau-customizer',
		trailingslashit( bp_get_theme_compat_url() ) . "js/customizer{$min}.js",
		array( 'jquery', 'jquery-ui-sortable', 'customize-controls', 'iris', 'underscore', 'wp-util' ),
		bp_nouveau()->version,
		true
	);

	/**
	 * Fires after Nouveau enqueues its required javascript.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_nouveau_customizer_enqueue_scripts' );
}
add_action( 'customize_controls_enqueue_scripts', 'bp_nouveau_customizer_enqueue_scripts' );
