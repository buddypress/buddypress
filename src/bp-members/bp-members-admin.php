<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyPress
 * @subpackage MembersAdmin
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is the Admin User's community profile enabled?
 *
 * @since 10.0.0
 *
 * @return bool True if enabled. False otherwise.
 */
function bp_members_is_community_profile_enabled() {
	/**
	 * Filter here to disable the Admin User's Community profile.
	 *
	 * @since 10.0.0
	 *
	 * @param bool $value By default the Admin User's Community profile is enabled.
	 */
	return apply_filters( 'bp_members_is_community_profile_enabled', true );
}

// Load the BP Members admin.
add_action( 'bp_init', array( 'BP_Members_Admin', 'register_members_admin' ) );

/**
 * Create Users submenu to manage BuddyPress types.
 *
 * @since 7.0.0
 */
function bp_members_type_admin_menu() {
	if ( ! bp_is_root_blog() ) {
		return;
	}

	if ( bp_is_network_activated() && ! bp_is_multiblog_mode() && is_network_admin() ) {
		// Adds a users.php submenu to go to the root blog Member types screen.
		$member_type_admin_url = add_query_arg( 'taxonomy', bp_get_member_type_tax_name(), get_admin_url( bp_get_root_blog_id(), 'edit-tags.php' ) );

		add_submenu_page(
			'users.php',
			__( 'Member Types', 'buddypress' ),
			__( 'Member Types', 'buddypress' ),
			'bp_moderate',
			esc_url( $member_type_admin_url )
		);

	} elseif ( ! is_network_admin() ) {
		add_submenu_page(
			'users.php',
			__( 'Member Types', 'buddypress' ),
			__( 'Member Types', 'buddypress' ),
			'bp_moderate',
			basename( add_query_arg( 'taxonomy', bp_get_member_type_tax_name(), bp_get_admin_url( 'edit-tags.php' ) ) )
		);
	}
}
add_action( 'bp_admin_menu', 'bp_members_type_admin_menu' );

/**
 * Checks whether a member type already exists.
 *
 * @since 7.0.0
 *
 * @param  boolean $exists  True if the member type already exists. False otherwise.
 * @param  string  $type_id The member type identifier.
 * @return bool True if the member type already exists. False otherwise.
 */
function bp_members_type_admin_type_exists( $exists = false, $type_id = '' ) {
	if ( ! $type_id ) {
		return $exists;
	}

	return ! is_null( bp_get_member_type_object( $type_id ) );
}
add_filter( bp_get_member_type_tax_name() . '_check_existing_type', 'bp_members_type_admin_type_exists', 1, 2 );

/**
 * Set the feedback messages for the Member Types Admin actions.
 *
 * @since 7.0.0
 *
 * @param array $messages The feedback messages.
 * @return array The feedback messages including the ones for the Member Types Admin actions.
 */
function bp_members_type_admin_updated_messages( $messages = array() ) {
	$type_taxonomy = bp_get_member_type_tax_name();

	$messages[ $type_taxonomy ] = array(
		0  => '',
		1  => __( 'Please define the Member Type ID field.', 'buddypress' ),
		2  => __( 'Member type successfully added.', 'buddypress' ),
		3  => __( 'Sorry, there was an error and the Member type wasn’t added.', 'buddypress' ),
		// The following one needs to be != 5.
		4  => __( 'Member type successfully updated.', 'buddypress' ),
		5  => __( 'Sorry, this Member type already exists.', 'buddypress' ),
		6  => __( 'Sorry, the Member type was not deleted: it does not exist.', 'buddypress' ),
		7  => __( 'Sorry, This Member type is registered using code, deactivate the plugin or remove the custom code before trying to delete it again.', 'buddypress' ),
		8  => __( 'Sorry, there was an error while trying to delete this Member type.', 'buddypress' ),
		9  => __( 'Member type successfully deleted.', 'buddypress' ),
		10 => __( 'Member type could not be updated due to missing required information.', 'buddypress' ),
	);

	return $messages;
}
add_filter( 'term_updated_messages', 'bp_members_type_admin_updated_messages' );

/**
 * Formats xprofile field data about a signup/membership request for display.
 *
 * Operates recursively on arrays, which are then imploded with commas.
 *
 * @since 10.0.0
 *
 * @param string|array $value Field value.
 */
function bp_members_admin_format_xprofile_field_for_display( $value ) {
	if ( is_array( $value ) ) {
		$value = array_map( 'bp_members_admin_format_xprofile_field_for_display', $value );
		$value = implode( ', ', $value );
	} else {
		$value = stripslashes( $value );
		$value = esc_html( $value );
	}

	return $value;
}

/**
 * Outputs Informations about a signup/membership request into a modal inside the Signups Admin Screen.
 *
 * @since 10.0.0
 *
 * @param array $signup_field_labels The Signup field labels.
 * @param object|null $signup_object The signup data object.
 */
function bp_members_admin_preview_signup_profile_info( $signup_field_labels = array(), $signup_object = null ) {

	?>
	<div id="signup-info-modal-<?php echo $signup_object->id; ?>" style="display:none;">
		<h1><?php printf( '%1$s (%2$s)', esc_html( $signup_object->user_name ), esc_html( $signup_object->user_email ) ); ?></h1>

		<?php if ( bp_is_active( 'xprofile' ) && isset( $signup_object->meta ) && $signup_field_labels ) :
				// Init ids.
				$profile_field_ids = array();

				// Get all xprofile field IDs except field 1.
				if ( ! empty( $signup_object->meta['profile_field_ids'] ) ) {
					$profile_field_ids = array_flip( explode( ',', $signup_object->meta['profile_field_ids'] ) );
					unset( $profile_field_ids[1] );
				}
			?>
			<h2><?php esc_html_e( 'Extended Profile Information', 'buddypress' ); ?></h2>

			<table class="signup-profile-data-drawer wp-list-table widefat fixed striped">
				<?php if ( 1 <= count( $profile_field_ids ) ): foreach ( array_keys( $profile_field_ids ) as $profile_field_id ) :
					$field_value = isset( $signup_object->meta[ "field_{$profile_field_id}" ] ) ? $signup_object->meta[ "field_{$profile_field_id}" ] : ''; ?>
					<tr>
						<td class="column-fields"><?php echo esc_html( $signup_field_labels[ $profile_field_id ] ); ?></td>
						<td><?php echo bp_members_admin_format_xprofile_field_for_display( $field_value ); ?></td>
					</tr>
				<?php endforeach; else: ?>
					<tr>
						<td><?php esc_html_e( 'There is no additional information to display.', 'buddypress' ); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		<?php endif; ?>

		<?php if ( bp_members_site_requests_enabled() ) : ?>
			<h2><?php esc_html_e( 'Site Request Information', 'buddypress' ); ?></h2>
			<table class="signup-profile-data-drawer wp-list-table widefat fixed striped">
				<?php if ( ! empty( $signup_object->domain ) || ! empty( $signup_object->path ) ) : ?>
					<tr>
						<td class="column-fields"><?php esc_html_e( 'Site Title', 'buddypress' ); ?></td>
						<td><?php echo esc_html( $signup_object->title ); ?></td>
					</tr>
					<tr>
						<td class="column-fields"><?php esc_html_e( 'Domain', 'buddypress' ); ?></td>
						<td><?php echo esc_html( $signup_object->domain ); ?></td>
					</tr>
					<tr>
						<td class="column-fields"><?php esc_html_e( 'Path', 'buddypress' ); ?></td>
						<td><?php echo esc_html( $signup_object->path ); ?></td>
					</tr>
				<?php else : ?>
					<tr>
						<td><?php esc_html_e( 'This user has not requested a blog.', 'buddypress' ); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
