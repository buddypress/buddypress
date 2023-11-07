<?php
/**
 * BuddyPress Updater.
 *
 * @package BuddyPress
 * @subpackage Updater
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is this a fresh installation of BuddyPress?
 *
 * If there is no raw DB version, we infer that this is the first installation.
 *
 * @since 1.7.0
 *
 * @return bool True if this is a fresh BP install, otherwise false.
 */
function bp_is_install() {
	return ! bp_get_db_version_raw();
}

/**
 * Is this a BuddyPress update?
 *
 * Determined by comparing the registered BuddyPress version to the version
 * number stored in the database. If the registered version is greater, it's
 * an update.
 *
 * @since 1.6.0
 *
 * @return bool True if update, otherwise false.
 */
function bp_is_update() {

	// Current DB version of this site (per site in a multisite network).
	$current_db   = bp_get_option( '_bp_db_version' );
	$current_live = bp_get_db_version();

	// Compare versions (cast as int and bool to be safe).
	$is_update = (bool) ( (int) $current_db < (int) $current_live );

	// Return the product of version comparison.
	return $is_update;
}

/**
 * Determine whether BuddyPress is in the process of being activated.
 *
 * @since 1.6.0
 *
 * @param string $basename BuddyPress basename.
 * @return bool True if activating BuddyPress, false if not.
 */
function bp_is_activation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating.
	if ( empty( $action ) || !in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated.
	if ( $action == 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty.
	if ( empty( $basename ) && !empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename.
	if ( empty( $basename ) ) {
		return false;
	}

	// Is BuddyPress being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine whether BuddyPress is in the process of being deactivated.
 *
 * @since 1.6.0
 *
 * @param string $basename BuddyPress basename.
 * @return bool True if deactivating BuddyPress, false if not.
 */
function bp_is_deactivation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating.
	if ( empty( $action ) || !in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated.
	if ( 'deactivate' == $action ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty.
	if ( empty( $basename ) && !empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename.
	if ( empty( $basename ) ) {
		return false;
	}

	// Is bbPress being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the BP version stored in the database to the current version.
 *
 * @since 1.6.0
 */
function bp_version_bump() {
	bp_update_option( '_bp_db_version', bp_get_db_version() );
}

/**
 * Set up the BuddyPress updater.
 *
 * @since 1.6.0
 */
function bp_setup_updater() {

	// Are we running an outdated version of BuddyPress?
	if ( ! bp_is_update() ) {
		return;
	}

	bp_version_updater();
}

/**
 * Initialize an update or installation of BuddyPress.
 *
 * BuddyPress's version updater looks at what the current database version is,
 * and runs whatever other code is needed - either the "update" or "install"
 * code.
 *
 * This is most often used when the data schema changes, but should also be used
 * to correct issues with BuddyPress metadata silently on software update.
 *
 * @since 1.7.0
 */
function bp_version_updater() {

	// Get the raw database version.
	$raw_db_version = (int) bp_get_db_version_raw();

	/**
	 * Filters the default components to activate for a new install.
	 *
	 * @since 1.7.0
	 *
	 * @param array $value Array of default components to activate.
	 */
	$default_components = apply_filters( 'bp_new_install_default_components', array(
		'activity'      => 1,
		'members'       => 1,
		'settings'      => 1,
		'xprofile'      => 1,
		'notifications' => 1,
	) );

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );
	$switched_to_root_blog = false;

	// Make sure the current blog is set to the root blog.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		bp_register_taxonomies();

		$switched_to_root_blog = true;
	}

	// Install BP schema and activate default components.
	if ( bp_is_install() ) {
		// Set the first BP major version the plugin was installed.
		bp_update_option( '_bp_initial_major_version', bp_get_major_version() );

		// Add an unread Admin notification.
		if ( 13422 === bp_get_db_version() ) {
			$unread   = bp_core_get_unread_admin_notifications();
			$unread[] = 'bp120-new-installs-warning';

			bp_update_option( 'bp_unread_admin_notifications', $unread );
		}

		// Apply schema and set default components as active.
		bp_core_install( $default_components );
		bp_update_option( 'bp-active-components', $default_components );
		bp_core_add_page_mappings( $default_components, 'delete' );
		bp_core_install_emails();

		// Force permalinks to be refreshed at next page load.
		bp_delete_rewrite_rules();

	// Upgrades.
	} else {

		// Run the schema install to update tables.
		bp_core_install();

		// Version 1.5.0.
		if ( $raw_db_version < 1801 ) {
			bp_update_to_1_5();
			bp_core_add_page_mappings( $default_components, 'delete' );
		}

		// Version 1.6.0.
		if ( $raw_db_version < 6067 ) {
			bp_update_to_1_6();
		}

		// Version 1.9.0.
		if ( $raw_db_version < 7553 ) {
			bp_update_to_1_9();
		}

		// Version 1.9.2.
		if ( $raw_db_version < 7731 ) {
			bp_update_to_1_9_2();
		}

		// Version 2.0.0.
		if ( $raw_db_version < 7892 ) {
			bp_update_to_2_0();
		}

		// Version 2.0.1.
		if ( $raw_db_version < 8311 ) {
			bp_update_to_2_0_1();
		}

		// Version 2.2.0.
		if ( $raw_db_version < 9181 ) {
			bp_update_to_2_2();
		}

		// Version 2.3.0.
		if ( $raw_db_version < 9615 ) {
			bp_update_to_2_3();
		}

		// Version 2.5.0.
		if ( $raw_db_version < 10440 ) {
			bp_update_to_2_5();
		}

		// Version 2.7.0.
		if ( $raw_db_version < 11105 ) {
			bp_update_to_2_7();
		}

		// Version 5.0.0.
		if ( $raw_db_version < 12385 ) {
			bp_update_to_5_0();
		}

		// Version 8.0.0.
		if ( $raw_db_version < 12850 ) {
			bp_update_to_8_0();
		}

		// Version 10.0.0.
		if ( $raw_db_version < 13165 ) {
			bp_update_to_10_0();
		}

		// Version 11.0.0.
		if ( $raw_db_version < 13271 ) {
			bp_update_to_11_0();
		}

		// Version 11.4.0.
		if ( $raw_db_version < 13408 ) {
			bp_update_to_11_4();
		}

		// Version 12.0.0.
		if ( $raw_db_version < 13422 ) {
			bp_update_to_12_0();
		}
	}

	/* All done! *************************************************************/

	// Bump the version.
	bp_version_bump();

	if ( $switched_to_root_blog ) {
		restore_current_blog();
	}
}

/**
 * Perform database operations that must take place before the general schema upgrades.
 *
 * `dbDelta()` cannot handle certain operations - like changing indexes - so we do it here instead.
 *
 * @since 2.3.0
 */
function bp_pre_schema_upgrade() {
	global $wpdb;

	$raw_db_version = (int) bp_get_db_version_raw();
	$bp_prefix      = bp_core_get_table_prefix();

	// 2.3.0: Change index lengths to account for utf8mb4.
	if ( $raw_db_version < 9695 ) {
		// Map table_name => columns.
		$tables = array(
			$bp_prefix . 'bp_activity_meta'       => array( 'meta_key' ),
			$bp_prefix . 'bp_groups_groupmeta'    => array( 'meta_key' ),
			$bp_prefix . 'bp_messages_meta'       => array( 'meta_key' ),
			$bp_prefix . 'bp_notifications_meta'  => array( 'meta_key' ),
			$bp_prefix . 'bp_user_blogs_blogmeta' => array( 'meta_key' ),
			$bp_prefix . 'bp_xprofile_meta'       => array( 'meta_key' ),
		);

		foreach ( $tables as $table_name => $indexes ) {
			foreach ( $indexes as $index ) {
				if ( $wpdb->query( $wpdb->prepare( "SHOW TABLES LIKE %s", bp_esc_like( $table_name ) ) ) ) {
					$wpdb->query( "ALTER TABLE {$table_name} DROP INDEX {$index}" );
				}
			}
		}
	}
}

/** Upgrade Routines **********************************************************/

/**
 * Remove unused metadata from database when upgrading from < 1.5.
 *
 * Database update methods based on version numbers.
 *
 * @since 1.7.0
 */
function bp_update_to_1_5() {

	// Delete old database version options.
	delete_site_option( 'bp-activity-db-version' );
	delete_site_option( 'bp-blogs-db-version'    );
	delete_site_option( 'bp-friends-db-version'  );
	delete_site_option( 'bp-groups-db-version'   );
	delete_site_option( 'bp-messages-db-version' );
	delete_site_option( 'bp-xprofile-db-version' );
}

/**
 * Remove unused metadata from database when upgrading from < 1.6.0.
 *
 * Database update methods based on version numbers.
 *
 * @since 1.7.0
 */
function bp_update_to_1_6() {

	// Delete possible site options.
	delete_site_option( 'bp-db-version'       );
	delete_site_option( '_bp_db_version'      );
	delete_site_option( 'bp-core-db-version'  );
	delete_site_option( '_bp-core-db-version' );

	// Delete possible blog options.
	delete_blog_option( bp_get_root_blog_id(), 'bp-db-version'       );
	delete_blog_option( bp_get_root_blog_id(), 'bp-core-db-version'  );
	delete_site_option( bp_get_root_blog_id(), '_bp-core-db-version' );
	delete_site_option( bp_get_root_blog_id(), '_bp_db_version'      );
}

/**
 * Add the notifications component to active components.
 *
 * Notifications was added in 1.9.0, and previous installations will already
 * have the core notifications API active. We need to add the new Notifications
 * component to the active components option to retain existing functionality.
 *
 * @since 1.9.0
 */
function bp_update_to_1_9() {

	// Setup hardcoded keys.
	$active_components_key      = 'bp-active-components';
	$notifications_component_id = 'notifications';

	// Get the active components.
	$active_components          = bp_get_option( $active_components_key );

	// Add notifications.
	if ( ! in_array( $notifications_component_id, $active_components ) ) {
		$active_components[ $notifications_component_id ] = 1;
	}

	// Update the active components option.
	bp_update_option( $active_components_key, $active_components );
}

/**
 * Perform database updates for BP 1.9.2.
 *
 * In 1.9, BuddyPress stopped registering its theme directory when it detected
 * that bp-default (or a child theme) was not currently being used, in effect
 * deprecating bp-default. However, this ended up causing problems when site
 * admins using bp-default would switch away from the theme temporarily:
 * bp-default would no longer be available, with no obvious way (outside of
 * a manual filter) to restore it. In 1.9.2, we add an option that flags
 * whether bp-default or a child theme is active at the time of upgrade; if so,
 *
 * the theme directory will continue to be registered even if the theme is
 * deactivated temporarily. Thus, new installations will not see bp-default,
 * but legacy installations using the theme will continue to see it.
 *
 * @since 1.9.2
 */
function bp_update_to_1_9_2() {
	if ( 'bp-default' === get_stylesheet() || 'bp-default' === get_template() ) {
		update_site_option( '_bp_retain_bp_default', 1 );
	}
}

/**
 * 2.0 update routine.
 *
 * - Ensure that the activity tables are installed, for last_activity storage.
 * - Migrate last_activity data from usermeta to activity table.
 * - Add values for all BuddyPress options to the options table.
 *
 * @since 2.0.0
 */
function bp_update_to_2_0() {

	/* Install activity tables for 'last_activity' ***************************/

	bp_core_install_activity_streams();

	/* Migrate 'last_activity' data ******************************************/

	bp_last_activity_migrate();

	/* Migrate signups data **************************************************/

	if ( ! is_multisite() ) {

		// Maybe install the signups table.
		bp_core_maybe_install_signups();

		// Run the migration script.
		bp_members_migrate_signups();
	}

	/* Add BP options to the options table ***********************************/

	bp_add_options();
}

/**
 * 2.0.1 database upgrade routine.
 *
 * @since 2.0.1
 */
function bp_update_to_2_0_1() {

	// We purposely call this during both the 2.0 upgrade and the 2.0.1 upgrade.
	// Don't worry; it won't break anything, and safely handles all cases.
	bp_core_maybe_install_signups();
}

/**
 * 2.2.0 update routine.
 *
 * - Add messages meta table.
 * - Update the component field of the 'new members' activity type.
 * - Clean up hidden friendship activities.
 *
 * @since 2.2.0
 */
function bp_update_to_2_2() {

	// Also handled by `bp_core_install()`.
	if ( bp_is_active( 'messages' ) ) {
		bp_core_install_private_messaging();
	}

	if ( bp_is_active( 'activity' ) ) {
		bp_migrate_new_member_activity_component();

		if ( bp_is_active( 'friends' ) ) {
			bp_cleanup_friendship_activities();
		}
	}
}

/**
 * 2.3.0 update routine.
 *
 * - Add notifications meta table.
 *
 * @since 2.3.0
 */
function bp_update_to_2_3() {

	// Also handled by `bp_core_install()`.
	if ( bp_is_active( 'notifications' ) ) {
		bp_core_install_notifications();
	}
}

/**
 * 2.5.0 update routine.
 *
 * - Add emails.
 *
 * @since 2.5.0
 */
function bp_update_to_2_5() {
	bp_core_install_emails();
}

/**
 * 2.7.0 update routine.
 *
 * - Add email unsubscribe salt.
 * - Save legacy directory titles to the corresponding WP pages.
 * - Add ignore deprecated code option (false for updates).
 *
 * @since 2.7.0
 */
function bp_update_to_2_7() {
	bp_add_option( 'bp-emails-unsubscribe-salt', base64_encode( wp_generate_password( 64, true, true ) ) );

	// Update post_titles
	bp_migrate_directory_page_titles();

	/*
	 * Add `parent_id` column to groups table.
	 * Also handled by `bp_core_install()`.
	 */
	if ( bp_is_active( 'groups' ) ) {
		bp_core_install_groups();

		// Invalidate all cached group objects.
		global $wpdb;
		$bp = buddypress();

		$group_ids = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name}" );

		foreach ( $group_ids as $group_id ) {
			wp_cache_delete( $group_id, 'bp_groups' );
		}
	}

	// Do not ignore deprecated code for existing installs.
	bp_add_option( '_bp_ignore_deprecated_code', false );
}

/**
 * Retuns needed the fullname field ID for an update task.
 *
 * @since 8.0.0
 *
 * @return int The fullname field ID.
 */
function bp_get_fullname_field_id_for_update() {
	/**
	 * The xProfile component is active by default on new installs, even if it
	 * might be inactive during this update, we need to set the custom visibility
	 * for the default field, in case the Administrator decides to reactivate it.
	 */
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = %s", addslashes( bp_get_option( 'bp-xprofile-fullname-field-name' ) ) ) );
}

/**
 * 5.0.0 update routine.
 *
 * - Make sure the custom visibility is disabled for the default profile field.
 * - Create the invitations table.
 * - Migrate requests and invitations to the new table.
 *
 * @since 5.0.0
 */
function bp_update_to_5_0() {
	/**
	 * The xProfile component is active by default on new installs, even if it
	 * might be inactive during this update, we need to set the custom visibility
	 * for the default field, in case the Administrator decides to reactivate it.
	 */
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();
	$field_id  = bp_get_fullname_field_id_for_update();

	$wpdb->insert(
		$bp_prefix . 'bp_xprofile_meta',
		array(
			'object_id'   => $field_id,
			'object_type' => 'field',
			'meta_key'    => 'allow_custom_visibility',
			'meta_value'  => 'disabled'
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s'
		)
	);

	bp_core_install_invitations();

	if ( bp_is_active( 'groups' ) ) {
		bp_groups_migrate_invitations();
	}
}

/**
 * 8.0.0 update routine.
 *
 * - Edit the `new_avatar` activity type's component to `members`.
 * - Upgrade Primary xProfile Group's fields to signup fields.
 *
 * @since 8.0.0
 */
function bp_update_to_8_0() {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	// Install welcome email to email list.
	add_filter( 'bp_email_get_schema', 'bp_core_get_8_0_upgrade_email_schema' );

	bp_core_install_emails();

	remove_filter( 'bp_email_get_schema', 'bp_core_get_8_0_upgrade_email_schema' );

	// Update the `new_avatar` activity type's component to `members`.
	$wpdb->update(
		$bp_prefix . 'bp_activity',
		array(
			'component' => 'members',
		),
		array(
			'type' => 'new_avatar',
		),
		array(
			'%s',
		),
		array(
			'%s',
		)
	);

	// Check if we need to create default signup fields.
	$field_id            = bp_get_fullname_field_id_for_update();
	$has_signup_position = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$bp_prefix}bp_xprofile_meta WHERE meta_key = 'signup_position' AND object_type = 'field' AND object_id = %d", $field_id ) );
	if ( bp_get_signup_allowed() && ! $has_signup_position ) {
		// Get the Primary Group's fields.
		$signup_fields = $wpdb->get_col( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE group_id = 1 ORDER BY field_order ASC" );

		// Migrate potential signup fields.
		if ( $signup_fields ) {
			$signup_position = 0;
			foreach ( $signup_fields as $signup_field_id ) {
				$signup_position += 1;

				$wpdb->insert(
					$bp_prefix . 'bp_xprofile_meta',
					array(
						'object_id'   => $signup_field_id,
						'object_type' => 'field',
						'meta_key'    => 'signup_position',
						'meta_value'  => $signup_position,
					),
					array(
						'%d',
						'%s',
						'%s',
						'%d',
					)
				);
			}
		}
	}

	bp_core_install_nonmember_opt_outs();
}

/**
 * Select only the emails that need to be installed with version 8.0.
 *
 * @since 8.0.0
 *
 * @param array $emails The array of emails schema.
 */
function bp_core_get_8_0_upgrade_email_schema( $emails ) {
	$new_emails = array();

	if ( isset( $emails['core-user-activation'] ) ) {
		$new_emails['core-user-activation'] = $emails['core-user-activation'];
	}

	if ( isset( $emails['bp-members-invitation'] ) ) {
		$new_emails['bp-members-invitation'] = $emails['bp-members-invitation'];
	}

	return $new_emails;
}

/**
 * 10.0.0 update routine.
 *
 * - Install new BP Emails for membership requests.
 *
 * @since 10.0.0
 */
function bp_update_to_10_0() {

	// Install membership request emails.
	add_filter( 'bp_email_get_schema', 'bp_core_get_10_0_upgrade_email_schema' );

	bp_core_install_emails();

	remove_filter( 'bp_email_get_schema', 'bp_core_get_10_0_upgrade_email_schema' );
}

/**
 * Select only the emails that need to be installed with version 10.0.
 *
 * @since 10.0.0
 *
 * @param array $emails The array of emails schema.
 */
function bp_core_get_10_0_upgrade_email_schema( $emails ) {
	$new_emails = array();

	if ( isset( $emails['members-membership-request'] ) ) {
		$new_emails['members-membership-request'] = $emails['members-membership-request'];
	}

	if ( isset( $emails['members-membership-request-rejected'] ) ) {
		$new_emails['members-membership-request-rejected'] = $emails['members-membership-request-rejected'];
	}

	return $new_emails;
}

/**
 * 11.0.0 update routine.
 *
 * - Install new BP Emails for group membership requests which is completed by admin.
 *
 * @since 11.0.0
 */
function bp_update_to_11_0() {
	bp_delete_option( '_bp_ignore_deprecated_code' );

	add_filter( 'bp_email_get_schema', 'bp_core_get_11_0_upgrade_email_schema' );

	bp_core_install_emails();

	remove_filter( 'bp_email_get_schema', 'bp_core_get_11_0_upgrade_email_schema' );
}

/**
 * Select only the emails that need to be installed with version 11.0.
 *
 * @since 11.0.0
 *
 * @param array $emails The array of emails schema.
 */
function bp_core_get_11_0_upgrade_email_schema( $emails ) {
	$new_emails = array();

	if ( isset( $emails['groups-membership-request-accepted-by-admin'] ) ) {
		$new_emails['groups-membership-request-accepted-by-admin'] = $emails['groups-membership-request-accepted-by-admin'];
	}

	if ( isset( $emails['groups-membership-request-rejected-by-admin'] ) ) {
		$new_emails['groups-membership-request-rejected-by-admin'] = $emails['groups-membership-request-rejected-by-admin'];
	}

	return $new_emails;
}

/**
 * 11.4.0 update routine.
 *
 * @since 11.4.0
 */
function bp_update_to_11_4() {
	$unread = array( 'bp114-prepare-for-rewrites' );

	// Check if 10.0 notice was dismissed.
	$old_dismissed = (bool) bp_get_option( 'bp-dismissed-notice-bp100-welcome-addons', false );
	if ( ! $old_dismissed ) {
		$unread[] = 'bp100-welcome-addons';
	}

	// Remove the dismissible option.
	bp_delete_option( 'bp-dismissed-notice-bp100-welcome-addons' );

	// Create unread Admin notifications.
	bp_update_option( 'bp_unread_admin_notifications', $unread );
}

/**
 * 12.0.0 update routine.
 *
 * - Swith directory page post type from "page" to "buddypress".
 * - Remove Legacy Widgets option.
 * - Add the default community visibility value.
 *
 * @since 12.0.0
 */
function bp_update_to_12_0() {
	/*
	 * Only perform the BP Rewrites API & Legacy Widgets upgrade tasks
	 * when the BP Classic plugin is not active.
	 */
	if ( ! function_exists( 'bp_classic' ) ) {
		$post_type = bp_core_get_directory_post_type();

		if ( 'page' !== $post_type ) {
			$directory_pages   = bp_core_get_directory_pages();
			$nav_menu_item_ids = array();

			// Do not check post slugs nor post types.
			remove_filter( 'wp_unique_post_slug', 'bp_core_set_unique_directory_page_slug', 10 );

			// Update Directory pages post types.
			foreach ( $directory_pages as $directory_page ) {
				$nav_menu_item_ids[] = $directory_page->id;

				// Switch the post type.
				wp_update_post(
					array(
						'ID'          => $directory_page->id,
						'post_type'   => $post_type,
						'post_status' => 'publish',
					)
				);
			}

			// Update nav menu items!
			$nav_menus = wp_get_nav_menus( array( 'hide_empty' => true ) );
			foreach ( $nav_menus as $nav_menu ) {
				$items = wp_get_nav_menu_items( $nav_menu->term_id );
				foreach ( $items as $item ) {
					if ( 'page' !== $item->object || ! in_array( $item->object_id, $nav_menu_item_ids, true ) ) {
						continue;
					}

					wp_update_nav_menu_item(
						$nav_menu->term_id,
						$item->ID,
						array(
							'menu-item-db-id'       => $item->db_id,
							'menu-item-object-id'   => $item->object_id,
							'menu-item-object'      => $post_type,
							'menu-item-parent-id'   => $item->menu_item_parent,
							'menu-item-position'    => $item->menu_order,
							'menu-item-type'        => 'post_type',
							'menu-item-title'       => $item->title,
							'menu-item-url'         => $item->url,
							'menu-item-description' => $item->description,
							'menu-item-attr-title'  => $item->attr_title,
							'menu-item-target'      => $item->target,
							'menu-item-classes'     => implode( ' ', (array) $item->classes ),
							'menu-item-xfn'         => $item->xfn,
							'menu-item-status'      => 'publish',
						)
					);
				}
			}

			// Force permalinks to be refreshed at next page load.
			bp_delete_rewrite_rules();
		}

		// Widgets.
		$widget_options = array(
			'widget_bp_core_login_widget',
			'widget_bp_core_members_widget',
			'widget_bp_core_whos_online_widget',
			'widget_bp_core_recently_active_widget',
			'widget_bp_groups_widget',
			'widget_bp_messages_sitewide_notices_widget',
		);

		foreach ( $widget_options as $widget_option ) {
			bp_delete_option( $widget_option );
		}
	}

	// Community visibility.
	bp_update_option( '_bp_community_visibility', array( 'global' => 'anyone' ) );

	/**
	 * Fires once BuddyPress achieved 12.0 upgrading tasks.
	 *
	 * @since 12.0.0
	 */
	do_action( 'bp_updated_to_12_0' );
}

/**
 * Updates the component field for new_members type.
 *
 * @since 2.2.0
 *
 * @global wpdb $wpdb WordPress database object.
 */
function bp_migrate_new_member_activity_component() {
	global $wpdb;
	$bp = buddypress();

	// Update the component for the new_member type.
	$wpdb->update(
		// Activity table.
		$bp->members->table_name_last_activity,
		array(
			'component' => $bp->members->id,
		),
		array(
			'component' => 'xprofile',
			'type'      => 'new_member',
		),
		// Data sanitization format.
		array(
			'%s',
		),
		// WHERE sanitization format.
		array(
			'%s',
			'%s'
		)
	);
}

/**
 * Remove all hidden friendship activities.
 *
 * @since 2.2.0
 */
function bp_cleanup_friendship_activities() {
	bp_activity_delete( array(
		'component'     => buddypress()->friends->id,
		'type'          => 'friendship_created',
		'hide_sitewide' => true,
	) );
}

/**
 * Update WP pages so that their post_title matches the legacy component directory title.
 *
 * As of 2.7.0, component directory titles come from the `post_title` attribute of the corresponding WP post object,
 * instead of being hardcoded. To ensure that directory titles don't change for existing installations, we update these
 * WP posts with the formerly hardcoded titles.
 *
 * @since 2.7.0
 */
function bp_migrate_directory_page_titles() {
	$bp_pages = bp_core_get_directory_page_ids( 'all' );

	$default_titles = bp_core_get_directory_page_default_titles();

	$legacy_titles = array(
		'activity' => _x( 'Site-Wide Activity', 'component directory title', 'buddypress' ),
		'blogs'    => _x( 'Sites', 'component directory title', 'buddypress' ),
		'groups'   => _x( 'Groups', 'component directory title', 'buddypress' ),
		'members'  => _x( 'Members', 'component directory title', 'buddypress' ),
	);

	foreach ( $bp_pages as $component => $page_id ) {
		if ( ! isset( $legacy_titles[ $component ] ) ) {
			continue;
		}

		$page = get_post( $page_id );
		if ( ! $page ) {
			continue;
		}

		// If the admin has changed the default title, don't touch it.
		if ( isset( $default_titles[ $component ] ) && $default_titles[ $component ] !== $page->post_title ) {
			continue;
		}

		// If the saved page title is the same as the legacy title, there's nothing to do.
		if ( $legacy_titles[ $component ] == $page->post_title ) {
			continue;
		}

		// Update the page with the legacy title.
		wp_update_post( array(
			'ID' => $page_id,
			'post_title' => $legacy_titles[ $component ],
		) );
	}
}

/**
 * Redirect user to BP's What's New page on first page load after activation.
 *
 * @since 1.7.0
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation.
 */
function bp_add_activation_redirect() {

	// Bail if activating from network, or bulk.
	if ( isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Record that this is a new installation, so we show the right
	// welcome message.
	if ( bp_is_install() ) {
		set_transient( '_bp_is_new_install', true, 30 );
	}

	// Add the transient to redirect.
	set_transient( '_bp_activation_redirect', true, 30 );
}

/** Signups *******************************************************************/

/**
 * Check if the signups table needs to be created or upgraded.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 */
function bp_core_maybe_install_signups() {
	global $wpdb;

	// The table to run queries against.
	$signups_table = $wpdb->base_prefix . 'signups';

	// Suppress errors because users shouldn't see what happens next.
	$old_suppress  = $wpdb->suppress_errors();

	// Never use bp_core_get_table_prefix() for any global users tables.
	$table_exists  = (bool) $wpdb->get_results( "DESCRIBE {$signups_table};" );

	// Table already exists, so maybe upgrade instead?
	if ( true === $table_exists ) {

		// Look for the 'signup_id' column.
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM {$signups_table} LIKE 'signup_id'" );

		// 'signup_id' column doesn't exist, so run the upgrade
		if ( empty( $column_exists ) ) {
			bp_core_upgrade_signups();
		}

	// Table does not exist, and we are a single site, so install the multisite
	// signups table using WordPress core's database schema.
	} elseif ( ! is_multisite() ) {
		bp_core_install_signups();
	}

	// Restore previous error suppression setting.
	$wpdb->suppress_errors( $old_suppress );
}

/** Activation Actions ********************************************************/

/**
 * Fire activation hooks and events.
 *
 * Runs on BuddyPress activation.
 *
 * @since 1.6.0
 */
function bp_activation() {

	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	// Add options.
	bp_add_options();

	/**
	 * Fires during the activation of BuddyPress.
	 *
	 * Use as of 1.6.0.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_activation' );

	// @deprecated as of 1.6.0
	do_action( 'bp_loader_activate' );
}

/**
 * Fire deactivation hooks and events.
 *
 * Runs on BuddyPress deactivation.
 *
 * @since 1.6.0
 */
function bp_deactivation() {
	/**
	 * Fires during the deactivation of BuddyPress.
	 *
	 * Use as of 1.6.0.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_deactivation' );

	// @deprecated as of 1.6.0
	do_action_deprecated( 'bp_loader_deactivate', array(), '1.6.0' );
}

/**
 * Fire uninstall hook.
 *
 * Runs when uninstalling BuddyPress.
 *
 * @since 1.6.0
 */
function bp_uninstall() {

	/**
	 * Fires during the uninstallation of BuddyPress.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_uninstall' );
}
