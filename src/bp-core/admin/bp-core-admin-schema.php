<?php
/**
 * BuddyPress DB schema.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the DB schema to use for BuddyPress components.
 *
 * @since 1.1.0
 *
 * @global $wpdb $wpdb
 *
 * @return string The default database character-set, if set.
 */
function bp_core_set_charset() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	return !empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET {$wpdb->charset}" : '';
}

/**
 * Main installer.
 *
 * Can be passed an optional array of components to explicitly run installation
 * routines on, typically the first time a component is activated in Settings.
 *
 * @since 1.0.0
 *
 * @param array|bool $active_components Components to install.
 */
function bp_core_install( $active_components = false ) {

	bp_pre_schema_upgrade();

	// If no components passed, get all the active components from the main site.
	if ( empty( $active_components ) ) {

		/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );
	}

	// Install Activity Streams even when inactive (to store last_activity data).
	bp_core_install_activity_streams();

	// Install the signups table.
	bp_core_maybe_install_signups();

	// Notifications.
	if ( !empty( $active_components['notifications'] ) ) {
		bp_core_install_notifications();
	}

	// Friend Connections.
	if ( !empty( $active_components['friends'] ) ) {
		bp_core_install_friends();
	}

	// Extensible Groups.
	if ( !empty( $active_components['groups'] ) ) {
		bp_core_install_groups();
	}

	// Private Messaging.
	if ( !empty( $active_components['messages'] ) ) {
		bp_core_install_private_messaging();
	}

	// Extended Profiles.
	if ( !empty( $active_components['xprofile'] ) ) {
		bp_core_install_extended_profiles();
	}

	// Blog tracking.
	if ( !empty( $active_components['blogs'] ) ) {
		bp_core_install_blog_tracking();
	}
}

/**
 * Install database tables for the Notifications component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_notifications() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_notifications (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20),
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				date_notified datetime NOT NULL,
				is_new bool NOT NULL DEFAULT 0,
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY user_id (user_id),
				KEY is_new (is_new),
				KEY component_name (component_name),
				KEY component_action (component_action),
				KEY useritem (user_id,is_new)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_notifications_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				notification_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY notification_id (notification_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Activity component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_activity_streams() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_activity (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				component varchar(75) NOT NULL,
				type varchar(75) NOT NULL,
				action text NOT NULL,
				content longtext NOT NULL,
				primary_link text NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) DEFAULT NULL,
				date_recorded datetime NOT NULL,
				hide_sitewide bool DEFAULT 0,
				mptt_left int(11) NOT NULL DEFAULT 0,
				mptt_right int(11) NOT NULL DEFAULT 0,
				is_spam tinyint(1) NOT NULL DEFAULT 0,
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY component (component),
				KEY type (type),
				KEY mptt_left (mptt_left),
				KEY mptt_right (mptt_right),
				KEY hide_sitewide (hide_sitewide),
				KEY is_spam (is_spam)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_activity_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				activity_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY activity_id (activity_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Notifications component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_friends() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_friends (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				initiator_user_id bigint(20) NOT NULL,
				friend_user_id bigint(20) NOT NULL,
				is_confirmed bool DEFAULT 0,
				is_limited bool DEFAULT 0,
				date_created datetime NOT NULL,
				KEY initiator_user_id (initiator_user_id),
				KEY friend_user_id (friend_user_id)
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Groups component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_groups() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				creator_id bigint(20) NOT NULL,
				name varchar(100) NOT NULL,
				slug varchar(200) NOT NULL,
				description longtext NOT NULL,
				status varchar(10) NOT NULL DEFAULT 'public',
				enable_forum tinyint(1) NOT NULL DEFAULT '1',
				date_created datetime NOT NULL,
				KEY creator_id (creator_id),
				KEY status (status)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_members (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				inviter_id bigint(20) NOT NULL,
				is_admin tinyint(1) NOT NULL DEFAULT '0',
				is_mod tinyint(1) NOT NULL DEFAULT '0',
				user_title varchar(100) NOT NULL,
				date_modified datetime NOT NULL,
				comments longtext NOT NULL,
				is_confirmed tinyint(1) NOT NULL DEFAULT '0',
				is_banned tinyint(1) NOT NULL DEFAULT '0',
				invite_sent tinyint(1) NOT NULL DEFAULT '0',
				KEY group_id (group_id),
				KEY is_admin (is_admin),
				KEY is_mod (is_mod),
				KEY user_id (user_id),
				KEY inviter_id (inviter_id),
				KEY is_confirmed (is_confirmed)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_groupmeta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY group_id (group_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Messages component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_private_messaging() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_messages (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				thread_id bigint(20) NOT NULL,
				sender_id bigint(20) NOT NULL,
				subject varchar(200) NOT NULL,
				message longtext NOT NULL,
				date_sent datetime NOT NULL,
				KEY sender_id (sender_id),
				KEY thread_id (thread_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_recipients (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				thread_id bigint(20) NOT NULL,
				unread_count int(10) NOT NULL DEFAULT '0',
				sender_only tinyint(1) NOT NULL DEFAULT '0',
				is_deleted tinyint(1) NOT NULL DEFAULT '0',
				KEY user_id (user_id),
				KEY thread_id (thread_id),
				KEY is_deleted (is_deleted),
				KEY sender_only (sender_only),
				KEY unread_count (unread_count)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_notices (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				subject varchar(200) NOT NULL,
				message longtext NOT NULL,
				date_sent datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '0',
				KEY is_active (is_active)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				message_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY message_id (message_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Profiles component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_extended_profiles() {
	global $wpdb;

	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	// These values should only be updated if they are not already present.
	if ( ! bp_get_option( 'bp-xprofile-base-group-name' ) ) {
		bp_update_option( 'bp-xprofile-base-group-name', _x( 'General', 'First field-group name', 'buddypress' ) );
	}

	if ( ! bp_get_option( 'bp-xprofile-fullname-field-name' ) ) {
		bp_update_option( 'bp-xprofile-fullname-field-name', _x( 'Display Name', 'Display name field', 'buddypress' ) );
	}

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_groups (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name varchar(150) NOT NULL,
				description mediumtext NOT NULL,
				group_order bigint(20) NOT NULL DEFAULT '0',
				can_delete tinyint(1) NOT NULL,
				KEY can_delete (can_delete)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_fields (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) unsigned NOT NULL,
				parent_id bigint(20) unsigned NOT NULL,
				type varchar(150) NOT NULL,
				name varchar(150) NOT NULL,
				description longtext NOT NULL,
				is_required tinyint(1) NOT NULL DEFAULT '0',
				is_default_option tinyint(1) NOT NULL DEFAULT '0',
				field_order bigint(20) NOT NULL DEFAULT '0',
				option_order bigint(20) NOT NULL DEFAULT '0',
				order_by varchar(15) NOT NULL DEFAULT '',
				can_delete tinyint(1) NOT NULL DEFAULT '1',
				KEY group_id (group_id),
				KEY parent_id (parent_id),
				KEY field_order (field_order),
				KEY can_delete (can_delete),
				KEY is_required (is_required)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_data (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				field_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				value longtext NOT NULL,
				last_updated datetime NOT NULL,
				KEY field_id (field_id),
				KEY user_id (user_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				object_id bigint(20) NOT NULL,
				object_type varchar(150) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY object_id (object_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );

	// Insert the default group and fields.
	$insert_sql = array();

	if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_groups WHERE id = 1" ) ) {
		$insert_sql[] = "INSERT INTO {$bp_prefix}bp_xprofile_groups ( name, description, can_delete ) VALUES ( " . $wpdb->prepare( '%s', stripslashes( bp_get_option( 'bp-xprofile-base-group-name' ) ) ) . ", '', 0 );";
	}

	if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE id = 1" ) ) {
		$insert_sql[] = "INSERT INTO {$bp_prefix}bp_xprofile_fields ( group_id, parent_id, type, name, description, is_required, can_delete ) VALUES ( 1, 0, 'textbox', " . $wpdb->prepare( '%s', stripslashes( bp_get_option( 'bp-xprofile-fullname-field-name' ) ) ) . ", '', 1, 0 );";
	}

	dbDelta( $insert_sql );
}

/**
 * Install database tables for the Sites component.
 *
 * @since 1.0.0
 *
 * @uses bp_core_set_charset()
 * @uses bp_core_get_table_prefix()
 * @uses dbDelta()
 */
function bp_core_install_blog_tracking() {
	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_user_blogs (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				blog_id bigint(20) NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_user_blogs_blogmeta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				blog_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY blog_id (blog_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/** Signups *******************************************************************/

/**
 * Install the signups table.
 *
 * @since 2.0.0
 *
 * @global $wpdb
 * @uses wp_get_db_schema() to get WordPress ms_global schema
 */
function bp_core_install_signups() {
	global $wpdb;

	// Signups is not there and we need it so let's create it.
	require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );
	require_once( ABSPATH                  . 'wp-admin/includes/upgrade.php'     );

	// Never use bp_core_get_table_prefix() for any global users tables.
	$wpdb->signups = $wpdb->base_prefix . 'signups';

	// Use WP's core CREATE TABLE query.
	$create_queries = wp_get_db_schema( 'ms_global' );
	if ( ! is_array( $create_queries ) ) {
		$create_queries = explode( ';', $create_queries );
		$create_queries = array_filter( $create_queries );
	}

	// Filter out all the queries except wp_signups.
	foreach ( $create_queries as $key => $query ) {
		if ( preg_match( "|CREATE TABLE ([^ ]*)|", $query, $matches ) ) {
			if ( trim( $matches[1], '`' ) !== $wpdb->signups ) {
				unset( $create_queries[ $key ] );
			}
		}
	}

	// Run WordPress's database upgrader.
	if ( ! empty( $create_queries ) ) {
		dbDelta( $create_queries );
	}
}

/**
 * Update the signups table, adding `signup_id` column and drop `domain` index.
 *
 * This is necessary because WordPress's `pre_schema_upgrade()` function wraps
 * table ALTER's in multisite checks, and other plugins may have installed their
 * own sign-ups table; Eg: Gravity Forms User Registration Add On.
 *
 * @since 2.0.1
 *
 * @see pre_schema_upgrade()
 * @link https://core.trac.wordpress.org/ticket/27855 WordPress Trac Ticket
 * @link https://buddypress.trac.wordpress.org/ticket/5563 BuddyPress Trac Ticket
 *
 * @global WPDB $wpdb
 */
function bp_core_upgrade_signups() {
	global $wpdb;

	// Bail if global tables should not be upgraded.
	if ( defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ) {
		return;
	}

	// Never use bp_core_get_table_prefix() for any global users tables.
	$wpdb->signups = $wpdb->base_prefix . 'signups';

	// Attempt to alter the signups table.
	$wpdb->query( "ALTER TABLE {$wpdb->signups} ADD signup_id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST" );
	$wpdb->query( "ALTER TABLE {$wpdb->signups} DROP INDEX domain" );
}

/**
 * Add default emails.
 *
 * @since 2.5.0
 */
function bp_core_install_emails() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails = array(
		'activity-comment' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} replied to one of your updates:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{thread.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} replied to one of your updates:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddypress' ),
		),
		'activity-comment-author' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} replied to one of your comments:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{thread.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} replied to one of your comments:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddypress' ),
		),
		'activity-at-message' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} mentioned you in a status update:\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{mentioned.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} mentioned you in a status update:\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddypress' ),
		),
		'groups-at-message' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in an update', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{mentioned.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n\"{{usermessage}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddypress' ),
		),
		'core-user-registration' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Activate your account', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: <a href=\"{{{activate.url}}}\">{{{activate.url}}}</a>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: {{{activate.url}}}", 'buddypress' ),
		),
		'core-user-registration-with-blog' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Activate {{{user-site.url}}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: <a href=\"{{{activate-site.url}}}\">{{{activate-site.url}}}</a>.\n\nAfter you activate, you can visit your site at <a href=\"{{{user-site.url}}}\">{{{user-site.url}}}</a>.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: {{{activate-site.url}}}\n\nAfter you activate, you can visit your site at {{{user-site.url}}}.", 'buddypress' ),
		),
		'friends-request' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] New friendship request from {{initiator.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{initiator.url}}}\">{{initiator.name}}</a> wants to add you as a friend.\n\nTo accept this request and manage all of your pending requests, visit: <a href=\"{{{friend-requests.url}}}\">{{{friend-requests.url}}}</a>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{initiator.name}} wants to add you as a friend.\n\nTo accept this request and manage all of your pending requests, visit: {{{friend-requests.url}}}\n\nTo view {{initiator.name}}'s profile, visit: {{{initiator.url}}}", 'buddypress' ),
		),
		'friends-request-accepted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{friend.name}} accepted your friendship request', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{friendship.url}}}\">{{friend.name}}</a> accepted your friend request.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{friend.name}} accepted your friend request.\n\nTo learn more about them, visit their profile: {{{friendship.url}}}", 'buddypress' ),
		),
		'groups-details-updated' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Group details updated', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Group details for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; were updated:\n<blockquote>{{changed_text}}</blockquote>", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Group details for the group &quot;{{group.name}}&quot; were updated:\n\n{{changed_text}}\n\nTo view the group, visit: {{{group.url}}}", 'buddypress' ),
		),
		'groups-invitation' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] You have an invitation to the group: "{{group.name}}"', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: &quot;{{group.name}}&quot;.\n<a href=\"{{{invites.url}}}\">Go here to accept your invitation</a> or <a href=\"{{{group.url}}}\">visit the group</a> to learn more.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{inviter.name}} has invited you to join the group: &quot;{{group.name}}&quot;.\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddypress' ),
		),
		'groups-member-promoted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] You have been promoted in the group: "{{group.name}}"', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "You have been promoted to <b>{{promoted_to}}</b> in the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "You have been promoted to {{promoted_to}} in the group: &quot;{{group.name}}&quot;.\n\nTo visit the group, go to: {{{group.url}}}", 'buddypress' ),
		),
		'groups-membership-request' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group: {{group.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;{{group.name}}&quot;. As you are an administrator of this group, you must either accept or reject the membership request.\n\n<a href=\"{{{group-requests.url}}}\">Go here to manage this</a> and all other pending requests.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{requesting-user.name}} wants to join the group &quot;{{group.name}}&quot;. As you are the administrator of this group, you must either accept or reject the membership request.\n\nTo manage this and all other pending requests, visit: {{{group-requests.url}}}\n\nTo view {{requesting-user.name}}'s profile, visit: {{{profile.url}}}", 'buddypress' ),
		),
		'messages-unread' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] New message from {{sender.name}}', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "{{sender.name}} sent you a new message: &quot;{{usersubject}}&quot;\n\n<blockquote>&quot;{{usermessage}}&quot;</blockquote>\n\n<a href=\"{{{message.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{sender.name}} sent you a new message: &quot;{{usersubject}}&quot;\n\n&quot;{{usermessage}}&quot;\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddypress' ),
		),
		'settings-verify-email-change' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Verify your new email address', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "You recently changed the email address associated with your account on {{site.name}}. If this is correct, <a href=\"{{{verify.url}}}\">go here to confirm the change</a>.\n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "You recently changed the email address associated with your account on {{site.name}}. If this is correct, go to the following link to confirm the change: {{{verify.url}}}\n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddypress' ),
		),
		'groups-membership-request-accepted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been accepted.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Your membership request for the group &quot;{{group.name}}&quot; has been accepted.\n\nTo view the group, visit: {{{group.url}}}", 'buddypress' ),
		),
		'groups-membership-request-rejected' => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been rejected.", 'buddypress' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "Your membership request for the group &quot;{{group.name}}&quot; has been rejected.\n\nTo request membership again, visit: {{{group.url}}}", 'buddypress' ),
		),
	);

	$descriptions = array(
		'activity-comment'                   => __( 'A member has replied to an activity update that the recipient posted.', 'buddypress' ),
		'activity-comment-author'            => __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddypress' ),
		'activity-at-message'                => __( 'Recipient was mentioned in an activity update.', 'buddypress' ),
		'groups-at-message'                  => __( 'Recipient was mentioned in a group activity update.', 'buddypress' ),
		'core-user-registration'             => __( 'Recipient has registered for an account.', 'buddypress' ),
		'core-user-registration-with-blog'   => __( 'Recipient has registered for an account and site.', 'buddypress' ),
		'friends-request'                    => __( 'A member has sent a friend request to the recipient.', 'buddypress' ),
		'friends-request-accepted'           => __( 'Recipient has had a friend request accepted by a member.', 'buddypress' ),
		'groups-details-updated'             => __( "A group's details were updated.", 'buddypress' ),
		'groups-invitation'                  => __( 'A member has sent a group invitation to the recipient.', 'buddypress' ),
		'groups-member-promoted'             => __( "Recipient's status within a group has changed.", 'buddypress' ),
		'groups-membership-request'          => __( 'A member has requested permission to join a group.', 'buddypress' ),
		'messages-unread'                    => __( 'Recipient has received a private message.', 'buddypress' ),
		'settings-verify-email-change'       => __( 'Recipient has changed their email address.', 'buddypress' ),
		'groups-membership-request-accepted' => __( 'Recipient had requested to join a group, which was accepted.', 'buddypress' ),
		'groups-membership-request-rejected' => __( 'Recipient had requested to join a group, which was rejected.', 'buddypress' ),
	);

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {
		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$term_ids = wp_set_post_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $term_ids as $term_id ) {
			wp_update_term( (int) $term_id, bp_get_email_tax_type(), array(
				'description' => $descriptions[ $id ],
			) );
		}
	}

	/**
	 * Fires after BuddyPress adds the posts for its emails.
	 *
	 * @since 2.5.0
	 */
	do_action( 'bp_core_install_emails' );
}
