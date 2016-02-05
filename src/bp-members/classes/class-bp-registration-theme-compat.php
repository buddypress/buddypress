<?php
/**
 * BuddyPress Member Screens.
 *
 * Handlers for member screens that aren't handled elsewhere.
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main theme compat class for BuddyPress Registration.
 *
 * This class sets up the necessary theme compatibility actions to safely output
 * registration template parts to the_title and the_content areas of a theme.
 *
 * @since 1.7.0
 */
class BP_Registration_Theme_Compat {

	/**
	 * Setup the groups component theme compatibility.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_registration' ) );
	}

	/**
	 * Are we looking at either the registration or activation pages?
	 *
	 * @since 1.7.0
	 */
	public function is_registration() {

		// Bail if not looking at the registration or activation page.
		if ( ! bp_is_register_page() && ! bp_is_activation_page() ) {
			return;
		}

		// Not a directory.
		bp_update_is_directory( false, 'register' );

		// Setup actions.
		add_filter( 'bp_get_buddypress_template',                array( $this, 'template_hierarchy' ) );
		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post'    ) );
		add_filter( 'bp_replace_the_content',                    array( $this, 'dummy_content' ) );
	}

	/** Template ***********************************************************/

	/**
	 * Add template hierarchy to theme compat for registration/activation pages.
	 *
	 * This is to mirror how WordPress has
	 * {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since 1.8.0
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function template_hierarchy( $templates ) {
		$component = sanitize_file_name( bp_current_component() );

		/**
		 * Filters the template hierarchy for theme compat and registration/activation pages.
		 *
		 * This filter is a variable filter that depends on the current component
		 * being used.
		 *
		 * @since 1.8.0
		 *
		 * @param array $value Array of template paths to add to hierarchy.
		 */
		$new_templates = apply_filters( "bp_template_hierarchy_{$component}", array(
			"members/index-{$component}.php"
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates().
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with dummy data.
	 *
	 * @since 1.7.0
	 */
	public function dummy_post() {
		// Registration page.
		if ( bp_is_register_page() ) {
			$title = __( 'Create an Account', 'buddypress' );

			if ( 'completed-confirmation' == bp_get_current_signup_step() ) {
				$title = __( 'Check Your Email To Activate Your Account!', 'buddypress' );
			}

		// Activation page.
		} else {
			$title = __( 'Activate Your Account', 'buddypress' );

			if ( bp_account_was_activated() ) {
				$title = __( 'Account Activated', 'buddypress' );
			}
		}

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with either the register or activate templates.
	 *
	 * @since 1.7.0
	 */
	public function dummy_content() {
		if ( bp_is_register_page() ) {
			return bp_buffer_template_part( 'members/register', null, false );
		} else {
			return bp_buffer_template_part( 'members/activate', null, false );
		}
	}
}
