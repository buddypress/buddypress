<?php

/**
 * BuddyPress Member Screens
 *
 * Handlers for member screens that aren't handled elsewhere
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @package BuddyPress Members
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function bp_members_screen_display_profile() {
	do_action( 'bp_members_screen_display_profile' );
	bp_core_load_template( apply_filters( 'bp_members_screen_display_profile', 'members/single/home' ) );
}

/**
 * Handles the display of the members directory index
 *
 * @global object $bp
 *
 * @uses bp_is_user()
 * @uses bp_is_current_component()
 * @uses do_action()
 * @uses bp_core_load_template()
 * @uses apply_filters()
 */
function bp_members_screen_index() {
	if ( bp_is_members_directory() ) {
		bp_update_is_directory( true, 'members' );

		do_action( 'bp_members_screen_index' );

		bp_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );


function bp_core_screen_signup() {
	global $bp;

	if ( !bp_is_current_component( 'register' ) )
		return;

	// Not a directory
	bp_update_is_directory( false, 'register' );

	// If the user is logged in, redirect away from here
	if ( is_user_logged_in() ) {
		if ( bp_is_component_front_page( 'register' ) )
			$redirect_to = trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() );
		else
			$redirect_to = bp_get_root_domain();

		bp_core_redirect( apply_filters( 'bp_loggedin_register_page_redirect_to', $redirect_to ) );

		return;
	}

	$bp->signup->step = 'request-details';

 	if ( !bp_get_signup_allowed() ) {
		$bp->signup->step = 'registration-disabled';

	// If the signup page is submitted, validate and save
	} elseif ( isset( $_POST['signup_submit'] ) && bp_verify_nonce_request( 'bp_new_signup' ) ) {

		do_action( 'bp_signup_pre_validate' );

		// Check the base account details for problems
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );

		// If there are errors with account details, set them for display
		if ( !empty( $account_details['errors']->errors['user_name'] ) )
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];

		if ( !empty( $account_details['errors']->errors['user_email'] ) )
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];

		// Check that both password fields are filled in
		if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) )
			$bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'buddypress' );

		// Check that the passwords match
		if ( ( !empty( $_POST['signup_password'] ) && !empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] )
			$bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'buddypress' );

		$bp->signup->username = $_POST['signup_username'];
		$bp->signup->email = $_POST['signup_email'];

		// Now we've checked account details, we can check profile information
		if ( bp_is_active( 'xprofile' ) ) {

			// Make sure hidden field is passed and populated
			if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

				// Let's compact any profile field info into an array
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				// Loop through the posted fields formatting any datebox values then validate the field
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( !isset( $_POST['field_' . $field_id] ) ) {
						if ( !empty( $_POST['field_' . $field_id . '_day'] ) && !empty( $_POST['field_' . $field_id . '_month'] ) && !empty( $_POST['field_' . $field_id . '_year'] ) )
							$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
					}

					// Create errors for required fields without values
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) )
						$bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
				}

			// This situation doesn't naturally occur so bounce to website root
			} else {
				bp_core_redirect( bp_get_root_domain() );
			}
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = $bp->site_options['registration'];

			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

				// If there are errors with blog details, set them for display
				if ( !empty( $blog_details['errors']->errors['blogname'] ) )
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

				if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
			}
		}

		do_action( 'bp_signup_validate' );

		// Add any errors to the action for the field in the template for display.
		if ( !empty( $bp->signup->errors ) ) {
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				// addslashes() and stripslashes() to avoid create_function()
				// syntax errors when the $error_message contains quotes
				add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo apply_filters(\'bp_members_signup_error_message\', "<div class=\"error\">" . stripslashes( \'' . addslashes( $error_message ) . '\' ) . "</div>" );' ) );
			}
		} else {
			$bp->signup->step = 'save-details';

			// No errors! Let's register those deets.
			$active_signup = !empty( $bp->site_options['registration'] ) ? $bp->site_options['registration'] : '';

			if ( 'none' != $active_signup ) {

				// Make sure the extended profiles module is enabled
				if ( bp_is_active( 'xprofile' ) ) {
					// Let's compact any profile field info into usermeta
					$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

					// Loop through the posted fields formatting any datebox values then add to usermeta - @todo This logic should be shared with the same in xprofile_screen_edit_profile()
					foreach ( (array) $profile_field_ids as $field_id ) {
						if ( ! isset( $_POST['field_' . $field_id] ) ) {

							if ( ! empty( $_POST['field_' . $field_id . '_day'] ) && ! empty( $_POST['field_' . $field_id . '_month'] ) && ! empty( $_POST['field_' . $field_id . '_year'] ) ) {
								// Concatenate the values
								$date_value = $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];

								// Turn the concatenated value into a timestamp
								$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
							}
						}

						if ( !empty( $_POST['field_' . $field_id] ) )
							$usermeta['field_' . $field_id] = $_POST['field_' . $field_id];

						if ( !empty( $_POST['field_' . $field_id . '_visibility'] ) )
							$usermeta['field_' . $field_id . '_visibility'] = $_POST['field_' . $field_id . '_visibility'];
					}

					// Store the profile field ID's in usermeta
					$usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];
				}

				// Hash and store the password
				$usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

				// If the user decided to create a blog, save those details to usermeta
				if ( 'blog' == $active_signup || 'all' == $active_signup )
					$usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;

				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

				// Finally, sign up the user and/or blog
				if ( isset( $_POST['signup_with_blog'] ) && is_multisite() )
					$wp_user_id = bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
				else
					$wp_user_id = bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );

				if ( is_wp_error( $wp_user_id ) ) {
					$bp->signup->step = 'request-details';
					bp_core_add_message( $wp_user_id->get_error_message(), 'error' );
				} else {
					$bp->signup->step = 'completed-confirmation';
				}
			}

			do_action( 'bp_complete_signup' );
		}

	}

	do_action( 'bp_core_screen_signup' );
	bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'bp_core_screen_signup' );

function bp_core_screen_activation() {
	global $bp;

	if ( !bp_is_current_component( 'activate' ) )
		return false;

	// If the user is logged in, redirect away from here
	if ( is_user_logged_in() ) {
		if ( bp_is_component_front_page( 'activate' ) ) {
			$redirect_to = trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() );
		} else {
			$redirect_to = trailingslashit( bp_get_root_domain() );
		}

		bp_core_redirect( apply_filters( 'bp_loggedin_activate_page_redirect_to', $redirect_to ) );

		return;
	}

	// Check if an activation key has been passed
	if ( isset( $_GET['key'] ) ) {

		// Activate the signup
		$user = apply_filters( 'bp_core_activate_account', bp_core_activate_signup( $_GET['key'] ) );

		// If there were errors, add a message and redirect
		if ( !empty( $user->errors ) ) {
			bp_core_add_message( $user->get_error_message(), 'error' );
			bp_core_redirect( trailingslashit( bp_get_root_domain() . '/' . $bp->pages->activate->slug ) );
		}

		$hashed_key = wp_hash( $_GET['key'] );

		// Check if the avatar folder exists. If it does, move rename it, move
		// it and delete the signup avatar dir
		if ( file_exists( bp_core_avatar_upload_path() . '/avatars/signups/' . $hashed_key ) )
			@rename( bp_core_avatar_upload_path() . '/avatars/signups/' . $hashed_key, bp_core_avatar_upload_path() . '/avatars/' . $user );

		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );

		$bp->activation_complete = true;
	}

	bp_core_load_template( apply_filters( 'bp_core_template_activate', array( 'activate', 'registration/activate' ) ) );
}
add_action( 'bp_screens', 'bp_core_screen_activation' );

/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Members.
 *
 * This class sets up the necessary theme compatability actions to safely output
 * member template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyPress (1.7)
 */
class BP_Members_Theme_Compat {

	/**
	 * Setup the members component theme compatibility
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_members' ) );
	}

	/**
	 * Are we looking at something that needs members theme compatability?
	 *
	 * @since BuddyPress (1.7)
	 */
	public function is_members() {

		// Bail if not looking at the members component or a user's page
		if ( ! bp_is_members_component() && ! bp_is_user() )
			return;

		// Members Directory
		if ( ! bp_current_action() && ! bp_current_item() ) {
			bp_update_is_directory( true, 'members' );

			do_action( 'bp_members_screen_index' );

			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// User page
		} elseif ( bp_is_user() ) {
			// If we're on a single activity permalink page, we shouldn't use the members
			// template, so stop here!
			if ( bp_is_active( 'activity' ) && bp_is_single_activity() )
				return;

			do_action( 'bp_members_screen_display_profile' );

			add_filter( 'bp_get_buddypress_template',                array( $this, 'single_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post'    ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_dummy_content' ) );

		}
	}

	/** Directory *************************************************************/

	/**
	 * Add template hierarchy to theme compat for the members directory page.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates = array() ) {

		// Setup our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_members_directory', array(
			'members/index-directory.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with directory data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => bp_get_directory_title( 'members' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_members',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the members index template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'members/index', null, false );
	}

	/** Single ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for member pages.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function single_template_hierarchy( $templates ) {
		// Setup some variables we're going to reference in our custom templates
		$user_nicename = buddypress()->displayed_user->userdata->user_nicename;

		// Setup our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_members_single_item', array(
			'members/single/index-id-'        . sanitize_file_name( bp_displayed_user_id() ) . '.php',
			'members/single/index-nicename-'  . sanitize_file_name( $user_nicename )         . '.php',
			'members/single/index-action-'    . sanitize_file_name( bp_current_action() )    . '.php',
			'members/single/index-component-' . sanitize_file_name( bp_current_component() ) . '.php',
			'members/single/index.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with the displayed user's data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => '<a href="' . bp_get_displayed_user_link() . '">' . bp_get_displayed_user_fullname() . '</a>',
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_members',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the members' single home template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_content() {
		return bp_buffer_template_part( 'members/single/home', null, false );
	}
}
new BP_Members_Theme_Compat();

/**
 * The main theme compat class for BuddyPress Registration.
 *
 * This class sets up the necessary theme compatability actions to safely output
 * registration template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyPress (1.7)
 */
class BP_Registration_Theme_Compat {

	/**
	 * Setup the groups component theme compatibility
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_registration' ) );
	}

	/**
	 * Are we looking at either the registration or activation pages?
	 *
	 * @since BuddyPress (1.7)
	 */
	public function is_registration() {

		// Bail if not looking at the registration or activation page
		if ( ! bp_is_register_page() && ! bp_is_activation_page() ) {
			return;
		}

		// Not a directory
		bp_update_is_directory( false, 'register' );

		// Setup actions
		add_filter( 'bp_get_buddypress_template',                array( $this, 'template_hierarchy' ) );
		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post'    ) );
		add_filter( 'bp_replace_the_content',                    array( $this, 'dummy_content' ) );
	}

	/** Template ***********************************************************/

	/**
	 * Add template hierarchy to theme compat for registration / activation pages.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function template_hierarchy( $templates ) {
		$component = sanitize_file_name( bp_current_component() );

		// Setup our templates based on priority
		$new_templates = apply_filters( "bp_template_hierarchy_{$component}", array(
			"members/index-{$component}.php"
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with dummy data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function dummy_post() {
		// Registration page
		if ( bp_is_register_page() ) {
			$title = __( 'Create an Account', 'buddypress' );

			if ( 'completed-confirmation' == bp_get_current_signup_step() ) {
				$title = __( 'Check Your Email To Activate Your Account!', 'buddypress' );
			}

		// Activation page
		} else {
			$title = __( 'Activate your Account', 'buddypress' );

			if ( bp_account_was_activated() ) {
				$title = __( 'Account Activated', 'buddypress' );
			}
		}

		$post_type = bp_is_register_page() ? 'bp_register' : 'bp_activate';

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with either the register or activate templates.
	 *
	 * @since BuddyPress (1.7)
	 */
	public function dummy_content() {
		if ( bp_is_register_page() ) {
			return bp_buffer_template_part( 'members/register', null, false );
		} else {
			return bp_buffer_template_part( 'members/activate', null, false );
		}
	}
}
new BP_Registration_Theme_Compat();
