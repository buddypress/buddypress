<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_core_set_charset() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	// BuddyPress component DB schema
	return !empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET {$wpdb->charset}" : '';
}

function bp_core_install( $active_components = false ) {

	if ( empty( $active_components ) )
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

	// Core DB Tables
	bp_core_install_notifications();

	// Activity Streams
	if ( !empty( $active_components['activity'] ) )
		bp_core_install_activity_streams();

	// Friend Connections
	if ( !empty( $active_components['friends'] ) )
		bp_core_install_friends();

	// Extensible Groups
	if ( !empty( $active_components['groups'] ) )
		bp_core_install_groups();

	// Private Messaging
	if ( !empty( $active_components['messages'] ) )
		bp_core_install_private_messaging();

	// Extended Profiles
	if ( !empty( $active_components['xprofile'] ) )
		bp_core_install_extended_profiles();

	// Blog tracking
	if ( !empty( $active_components['blogs'] ) )
		bp_core_install_blog_tracking();
}

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

	dbDelta( $sql );
}

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
				primary_link varchar(255) NOT NULL,
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
				KEY meta_key (meta_key)
		   	   ) {$charset_collate};";

	dbDelta( $sql );
}

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
				KEY meta_key (meta_key)
			   ) {$charset_collate};";

	dbDelta( $sql );
}

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

	dbDelta( $sql );
}

function bp_core_install_extended_profiles() {
	global $wpdb;

	$sql             = array();
	$charset_collate = bp_core_set_charset();
	$bp_prefix       = bp_core_get_table_prefix();

	// These values should only be updated if they are not already present
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
				KEY meta_key (meta_key)
		   	   ) {$charset_collate};";

	dbDelta( $sql );

	// Insert the default group and fields
	$insert_sql = array();

	if ( !$wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_groups WHERE id = 1" ) )
		$insert_sql[] = "INSERT INTO {$bp_prefix}bp_xprofile_groups ( name, description, can_delete ) VALUES ( " . $wpdb->prepare( '%s', stripslashes( bp_get_option( 'bp-xprofile-base-group-name' ) ) ) . ", '', 0 );";

	if ( !$wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE id = 1" ) )
		$insert_sql[] = "INSERT INTO {$bp_prefix}bp_xprofile_fields ( group_id, parent_id, type, name, description, is_required, can_delete ) VALUES ( 1, 0, 'textbox', " . $wpdb->prepare( '%s', stripslashes( bp_get_option( 'bp-xprofile-fullname-field-name' ) ) ) . ", '', 1, 0 );";

	dbDelta( $insert_sql );
}

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
				KEY meta_key (meta_key)
		       ) {$charset_collate};";

	dbDelta( $sql );
}
