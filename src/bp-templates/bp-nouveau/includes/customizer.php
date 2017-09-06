<?php
/**
 * Code to hook into the WP Customizer
 *
 * @since 1.0.0
 */

/**
 * Add a specific panel for the BP Nouveau Template Pack.
 *
 * @since 1.0.0
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

	$sections = apply_filters( 'bp_nouveau_customizer_sections', array(
		'bp_nouveau_general_settings' => array(
			'title'       => __( 'General BP Settings', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 10,
			'description' => __( 'Set general BuddyPress styles', 'buddypress' ),
		),
		'bp_nouveau_user_front_page' => array(
			'title'       => __( 'User\'s front page', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 30,
			'description' => __( 'Set your preferences about the members default front page.', 'buddypress' ),
		),
		'bp_nouveau_user_primary_nav' => array(
			'title'       => __( 'User\'s navigation', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 50,
			'description' => __( 'Customize the members primary navigations. Navigate to any random member\'s profile to live preview your changes.', 'buddypress' ),
		),
		'bp_nouveau_loops_layout' => array(
			'title'       => __( 'Loops layouts', 'buddypress' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 70,
			'description' => __( 'Set the number of columns to use for the BuddyPress loops.', 'buddypress' ),
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
		'bp_nouveau_appearance[groups_dir_tabs]' => array(
			'index'             => 'groups_dir_tabs',
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

	// Add the settings
	foreach ( $settings as $id_setting => $setting_args ) {
		$args = array();

		if ( empty( $setting_args['index'] ) || ! isset( $bp_nouveau_options[ $setting_args['index'] ] ) ) {
			continue;
		}

		$args = array_merge( $setting_args, array( 'default' => $bp_nouveau_options[ $setting_args['index'] ] ) );

		$wp_customize->add_setting( $id_setting, $args );
	}

	$controls = apply_filters( 'bp_nouveau_customizer_controls', array(
		'bp_site_avatars' => array(
			'label'      => __( 'Set BP User, Group avatars to rounded style.', 'buddypress' ),
			'section'    => 'bp_nouveau_general_settings',
			'settings'   => 'bp_nouveau_appearance[avatar_style]',
			'type'       => 'checkbox',
		),
		'user_front_page' => array(
			'label'      => __( 'Enable default front page for user profiles.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_front_page',
			'settings'   => 'bp_nouveau_appearance[user_front_page]',
			'type'       => 'checkbox',
		),
		'user_front_bio' => array(
			'label'      => __( 'Display the WordPress Biographical Info of the user.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_front_page',
			'settings'   => 'bp_nouveau_appearance[user_front_bio]',
			'type'       => 'checkbox',
		),
		'user_nav_display' => array(
			'label'      => __( 'Display the User\'s primary nav vertically.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_display]',
			'type'       => 'checkbox',
		),
		'user_nav_tabs' => array(
			'label'      => __( 'Set User nav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_tabs]',
			'type'       => 'checkbox',
		),
		'user_subnav_tabs' => array(
			'label'      => __( 'Set User subnav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_subnav_tabs]',
			'type'       => 'checkbox',
		),
		'user_nav_order' => array(
			'class'      => 'BP_Nouveau_Nav_Customize_Control',
			'label'      => __( 'Reorder the Members single items primary navigation.', 'buddypress' ),
			'section'    => 'bp_nouveau_user_primary_nav',
			'settings'   => 'bp_nouveau_appearance[user_nav_order]',
			'type'       => 'user',
		),
		'members_layout' => array(
			'label'      => __( 'Members loop.', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[members_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'members_group_layout' => array(
			'label'      => __( 'Members loop - Single Groups.', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[members_group_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'members_friends_layout' => array(
			'label'      => __( 'Members Friends - User Account.', 'buddypress' ),
			'section'    => 'bp_nouveau_loops_layout',
			'settings'   => 'bp_nouveau_appearance[members_friends_layout]',
			'type'       => 'select',
			'choices'    => bp_nouveau_customizer_grid_choices(),
		),
		'act_dir_layout' => array(
			'label'      => __( 'Set Activity dir nav to column.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[activity_dir_layout]',
			'type'       => 'checkbox',
		),
		'act_dir_tabs' => array(
			'label'      => __( 'Set Activity nav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[activity_dir_tabs]',
			'type'       => 'checkbox',
		),
		'members_dir_layout' => array(
			'label'      => __( 'Set Members dir nav to column.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[members_dir_layout]',
			'type'       => 'checkbox',
		),
		'members_dir_tabs' => array(
			'label'      => __( 'Set Members nav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[members_dir_tabs]',
			'type'       => 'checkbox',
		),
		'group_dir_layout' => array(
			'label'      => __( 'Set Groups dir nav to column.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[groups_dir_layout]',
			'type'       => 'checkbox',
		),
		'group_dir_tabs' => array(
			'label'      => __( 'Set Groups nav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[groups_dir_tabs]',
			'type'       => 'checkbox',
		),
		'sites_dir_layout' => array(
			'label'      => __( 'Set Sites dir nav to column.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[sites_dir_layout]',
			'type'       => 'checkbox',
		),
		'sites_dir_tabs' => array(
			'label'      => __( 'Set Sites nav to tab style.', 'buddypress' ),
			'section'    => 'bp_nouveau_dir_layout',
			'settings'   => 'bp_nouveau_appearance[sites_dir_tabs]',
			'type'       => 'checkbox',
		),
	) );

	// Add the controls to the customizer's section
	foreach ( $controls as $id_control => $control_args ) {
		if ( empty( $control_args['class'] ) )  {
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
 * @since 1.0.0
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

	do_action( 'bp_nouveau_customizer_enqueue_scripts' );
}
add_action( 'customize_controls_enqueue_scripts', 'bp_nouveau_customizer_enqueue_scripts' );
