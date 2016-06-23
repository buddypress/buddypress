<?php
/**
 * BuddyPress Customizer implementation for email.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the Customizer for emails.
 *
 * @since 2.5.0
 *
 * @param WP_Customize_Manager $wp_customize The Customizer object.
 */
function bp_email_init_customizer( WP_Customize_Manager $wp_customize ) {

	// Require WP 4.0+.
	if ( ! method_exists( $wp_customize, 'add_panel' ) ) {
		return;
	}

	if ( ! bp_is_email_customizer() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		return;
	}

	$wp_customize->add_panel( 'bp_mailtpl', array(
		'description' => __( 'Customize the appearance of emails sent by BuddyPress.', 'buddypress' ),
		'title'       => _x( 'BuddyPress Emails', 'screen heading', 'buddypress' ),
	) );

	$sections = bp_email_get_customizer_sections();
	foreach( $sections as $section_id => $args ) {
		$wp_customize->add_section( $section_id, $args );
	}

	$settings = bp_email_get_customizer_settings();
	foreach( $settings as $setting_id => $args ) {
		$wp_customize->add_setting( $setting_id, $args );
	}

	/**
	 * BP_Customizer_Control_Range class.
	 */
	if ( ! buddypress()->do_autoload ) {
		require_once dirname( __FILE__ ) . '/classes/class-bp-customizer-control-range.php';
	}

	/**
	 * Fires to let plugins register extra Customizer controls for emails.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Customize_Manager $wp_customize The Customizer object.
	 */
	do_action( 'bp_email_customizer_register_sections', $wp_customize );

	$controls = bp_email_get_customizer_controls();
	foreach ( $controls as $control_id => $args ) {
		$wp_customize->add_control( new $args['class']( $wp_customize, $control_id, $args ) );
	}

	/*
	 * Hook actions/filters for further configuration.
	 */

	add_filter( 'customize_section_active', 'bp_email_customizer_hide_sections', 12, 2 );

	if ( is_customize_preview() ) {
		/*
		 * Enqueue scripts/styles for the Customizer's preview window.
		 *
		 * Scripts can't be registered in bp_core_register_common_styles() etc because
		 * the Customizer loads very, very early.
		 */
		$bp  = buddypress();
		$min = bp_core_get_minified_asset_suffix();

		wp_enqueue_script(
			'bp-customizer-receiver-emails',
			"{$bp->plugin_url}bp-core/admin/js/customizer-receiver-emails{$min}.js",
			array( 'customize-preview' ),
			bp_get_version(),
			true
		);

		// Include the preview loading style.
		add_action( 'wp_footer', array( $wp_customize, 'customize_preview_loading_style' ) );
	}
}
add_action( 'bp_customize_register', 'bp_email_init_customizer' );

/**
 * Are we looking at the email customizer?
 *
 * @since 2.5.0
 *
 * @return bool
 */
function bp_is_email_customizer() {
	return isset( $_GET['bp_customizer'] ) && $_GET['bp_customizer'] === 'email';
}

/**
 * Only show email sections in the Customizer.
 *
 * @since 2.5.0
 *
 * @param bool                 $active  Whether the Customizer section is active.
 * @param WP_Customize_Section $section {@see WP_Customize_Section} instance.
 * @return bool
 */
function bp_email_customizer_hide_sections( $active, $section ) {
	if ( ! bp_is_email_customizer() ) {
		return $active;
	}

	return in_array( $section->id, array_keys( bp_email_get_customizer_sections() ), true );
}

/**
 * Get Customizer sections for emails.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_email_get_customizer_sections() {

	/**
	 * Filter Customizer sections for emails.
	 *
	 * @since 2.5.0
	 *
	 * @param array $sections Email Customizer sections to add.
	 */
	return apply_filters( 'bp_email_get_customizer_sections', array(
		'section_bp_mailtpl_header' => array(
			'capability' => 'bp_moderate',
			'panel'      => 'bp_mailtpl',
			'title'      => _x( 'Header', 'email', 'buddypress' ),
		),
		'section_bp_mailtpl_body' => array(
			'capability' => 'bp_moderate',
			'panel'      => 'bp_mailtpl',
			'title'      => _x( 'Body', 'email', 'buddypress' ),
		),
		'section_bp_mailtpl_footer' => array(
			'capability' => 'bp_moderate',
			'panel'      => 'bp_mailtpl',
			'title'      => _x( 'Footer', 'email', 'buddypress' ),
		),
	) );
}

/**
 * Get Customizer settings for emails.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_email_get_customizer_settings() {
	$defaults = bp_email_get_appearance_settings();

	/**
	 * Filter Customizer settings for emails.
	 *
	 * @since 2.5.0
	 *
	 * @param array $settings Email Customizer settings to add.
	 */
	return apply_filters( 'bp_email_get_customizer_settings', array(
		'bp_email_options[email_bg]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['email_bg'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[header_bg]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['header_bg'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[header_text_size]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['header_text_size'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[header_text_color]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['header_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[highlight_color]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['highlight_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[body_bg]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['body_bg'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[body_text_size]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['body_text_size'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[body_text_color]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['body_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[footer_text]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['footer_text'],
			'sanitize_callback' => 'wp_filter_post_kses',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[footer_bg]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['footer_bg'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[footer_text_size]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['footer_text_size'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
		'bp_email_options[footer_text_color]' => array(
			'capability'        => 'bp_moderate',
			'default'           => $defaults['footer_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
			'type'              => 'option',
		),
	) );
}

/**
 * Get Customizer controls for emails.
 *
 * @since 2.5.0
 *
 * @return array
 */
function bp_email_get_customizer_controls() {

	/**
	 * Filter Customizer controls for emails.
	 *
	 * @since 2.5.0
	 *
	 * @param array $controls Email Customizer controls to add.
	 */
	return apply_filters( 'bp_email_get_customizer_controls', array(
		'bp_mailtpl_email_bg' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Email background color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_header',
			'settings' => 'bp_email_options[email_bg]',
		),

		'bp_mailtpl_header_bg' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Header background color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_header',
			'settings' => 'bp_email_options[header_bg]',
		),

		'bp_mailtpl_highlight_color' => array(
			'class'       => 'WP_Customize_Color_Control',
			'description' => __( 'Applied to links and other decorative areas.', 'buddypress' ),
			'label'       => __( 'Highlight color', 'buddypress' ),
			'section'     => 'section_bp_mailtpl_header',
			'settings'    => 'bp_email_options[highlight_color]',
		),

		'bp_mailtpl_header_text_color' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Text color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_header',
			'settings' => 'bp_email_options[header_text_color]',
		),

		'bp_mailtpl_header_text_size' => array(
			'class'    => 'BP_Customizer_Control_Range',
			'label'    => __( 'Text size', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_header',
			'settings' => 'bp_email_options[header_text_size]',

			'input_attrs' => array(
				'max'  => 100,
				'min'  => 1,
				'step' => 1,
			),
		),


		'bp_mailtpl_body_bg' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Background color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_body',
			'settings' => 'bp_email_options[body_bg]',
		),


		'bp_mailtpl_body_text_color' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Text color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_body',
			'settings' => 'bp_email_options[body_text_color]',
		),

		'bp_mailtpl_body_text_size' => array(
			'class'    => 'BP_Customizer_Control_Range',
			'label'    => __( 'Text size', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_body',
			'settings' => 'bp_email_options[body_text_size]',

			'input_attrs' => array(
				'max'  => 24,
				'min'  => 8,
				'step' => 1,
			),
		),


		'bp_mailtpl_footer_text' => array(
			'class'       => 'WP_Customize_Control',
			'description' => __('Change the email footer here', 'buddypress' ),
			'label'       => __( 'Footer text', 'buddypress' ),
			'section'     => 'section_bp_mailtpl_footer',
			'settings'    => 'bp_email_options[footer_text]',
			'type'        => 'textarea',
		),

		'bp_mailtpl_footer_bg' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Background color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_footer',
			'settings' => 'bp_email_options[footer_bg]',
		),

		'bp_mailtpl_footer_text_color' => array(
			'class'    => 'WP_Customize_Color_Control',
			'label'    => __( 'Text color', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_footer',
			'settings' => 'bp_email_options[footer_text_color]',
		),

		'bp_mailtpl_footer_text_size' => array(
			'class'    => 'BP_Customizer_Control_Range',
			'label'    => __( 'Text size', 'buddypress' ),
			'section'  => 'section_bp_mailtpl_footer',
			'settings' => 'bp_email_options[footer_text_size]',

			'input_attrs' => array(
				'max'  => 24,
				'min'  => 8,
				'step' => 1,
			),
		),
	) );
}

/**
 * Implements a JS redirect to the Customizer, previewing a randomly selected email.
 *
 * @since 2.5.0
 */
function bp_email_redirect_to_customizer() {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	$email = get_posts( array(
		'fields'           => 'ids',
		'orderby'          => 'rand',
		'post_status'      => 'publish',
		'post_type'        => bp_get_email_post_type(),
		'posts_per_page'   => 1,
		'suppress_filters' => false,
	) );

	$preview_url = admin_url();

	if ( $email ) {
		$preview_url = get_post_permalink( $email[0] ) . '&bp_customizer=email';
	}

	$redirect_url = add_query_arg(
		array(
			'autofocus[panel]' => 'bp_mailtpl',
			'bp_customizer'    => 'email',
			'return'           => rawurlencode( admin_url() ),
			'url'              => rawurlencode( $preview_url ),
		),
		admin_url( 'customize.php' )
	);

	if ( $switched ) {
		restore_current_blog();
	}

	printf(
		'<script type="text/javascript">window.location = "%s";</script>',
		esc_url_raw( $redirect_url )
	);

	exit;
}
