<?php

function bp_forums_load_bbpress() {
	global $bp, $wpdb, $wp_roles, $current_user, $wp_users_object;
	global $bb, $bbdb, $bb_table_prefix, $bb_current_user;
	global $bb_roles, $wp_taxonomy_object;

	/* Return if we've already run this function. */
	if ( is_object( $bbdb ) && is_object( $bb_roles ) )
		return;

	if ( !bp_forums_is_installed_correctly() )
		return false;

	define( 'BB_PATH', BP_PLUGIN_DIR . '/bp-forums/bbpress/' );
	define( 'BACKPRESS_PATH', BP_PLUGIN_DIR . '/bp-forums/bbpress/bb-includes/backpress/' );
	define( 'BB_URL', BP_PLUGIN_URL . '/bp-forums/bbpress/' );
	define( 'BB_INC', 'bb-includes/' );

	require_once( BB_PATH . BB_INC . 'class.bb-query.php' );
	require_once( BB_PATH . BB_INC . 'class.bb-walker.php' );

	require_once( BB_PATH . BB_INC . 'functions.bb-core.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-forums.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-topics.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-posts.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-topic-tags.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-capabilities.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-meta.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-pluggable.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-formatting.php' );
	require_once( BB_PATH . BB_INC . 'functions.bb-template.php' );

	require_once( BACKPRESS_PATH . 'class.wp-taxonomy.php' );
	require_once( BB_PATH . BB_INC . 'class.bb-taxonomy.php' );

	$bb = new stdClass();
	require_once( $bp->forums->bbconfig );

	// Setup the global database connection
	$bbdb = new BPDB ( BBDB_USER, BBDB_PASSWORD, BBDB_NAME, BBDB_HOST );

	/* Set the table names */
	$bbdb->forums = $bb_table_prefix . 'forums';
	$bbdb->meta = $bb_table_prefix . 'meta';
	$bbdb->posts = $bb_table_prefix . 'posts';
	$bbdb->terms = $bb_table_prefix . 'terms';
	$bbdb->term_relationships = $bb_table_prefix . 'term_relationships';
	$bbdb->term_taxonomy = $bb_table_prefix . 'term_taxonomy';
	$bbdb->topics = $bb_table_prefix . 'topics';

	if ( isset( $bb->custom_user_table ) )
		$bbdb->users = $bb->custom_user_table;
	else
		$bbdb->users = $wpdb->users;

	if ( isset( $bb->custom_user_meta_table ) )
		$bbdb->usermeta = $bb->custom_user_meta_table;
	else
		$bbdb->usermeta = $wpdb->usermeta;

	$bbdb->prefix = $bb_table_prefix;

	define( 'BB_INSTALLING', false );

	/* This must be loaded before functionss.bb-admin.php otherwise we get a function conflict. */
	if ( !$tables_installed = (boolean) $bbdb->get_results( 'DESCRIBE `' . $bbdb->forums . '`;', ARRAY_A ) )
		require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );

	require_once( BB_PATH . 'bb-admin/includes/functions.bb-admin.php' );

	if ( is_object( $wp_roles ) ) {
		$bb_roles =& $wp_roles;
		bb_init_roles( $bb_roles );
	}

	do_action( 'bb_got_roles' );
	do_action( 'bb_init' );
	do_action( 'init_roles' );

	$bb_current_user = $current_user;
	$wp_users_object = new BP_Forums_BB_Auth;

	if ( !isset( $wp_taxonomy_object ) )
		$wp_taxonomy_object = new BB_Taxonomy( $bbdb );

	$wp_taxonomy_object->register_taxonomy( 'bb_topic_tag', 'bb_topic' );

	// Set a site id if there isn't one already
	if ( !isset( $bb->site_id ) )
		$bb->site_id = BP_ROOT_BLOG;

	/* Check if the tables are installed, if not, install them */
	if ( !$tables_installed ) {
		require_once( BB_PATH . 'bb-admin/includes/defaults.bb-schema.php' );

		dbDelta( $bb_queries );

		require_once( BB_PATH . 'bb-admin/includes/functions.bb-upgrade.php' );
		bb_update_db_version();

		/* Set the site admins as the keymasters */
		$site_admins = get_site_option( 'site_admins', array('admin') );
		foreach ( (array)$site_admins as $site_admin )
			update_usermeta( bp_core_get_userid( $site_admin ), $bb_table_prefix . 'capabilities', array( 'keymaster' => true ) );

		// Create the first forum.
		bb_new_forum( array( 'forum_name' => 'Default Forum' ) );

		// Set the site URI
		bb_update_option( 'uri', BB_URL );
	}

	register_shutdown_function( create_function( '', 'do_action("bb_shutdown");' ) );
}
add_action( 'bbpress_init', 'bp_forums_load_bbpress' );

/* WP to bbP wrapper functions */
function bb_get_current_user() { global $current_user; return $current_user; }
function bb_get_user( $user_id ) { return get_userdata( $user_id ); }
function bb_cache_users( $users ) {}

/**
 * bbPress needs this class for its usermeta manipulation.
 */
class BP_Forums_BB_Auth {
	function update_meta( $args = '' ) {
		$defaults = array( 'id' => 0, 'meta_key' => null, 'meta_value' => null, 'meta_table' => 'usermeta', 'meta_field' => 'user_id', 'cache_group' => 'users' );
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		return update_usermeta( $id, $meta_key, $meta_value );
	}
}

/**
 * bbPress needs the DB class to be BPDB, but we want to use WPDB, so we can
 * extend it and use this.
 */
class BPDB extends WPDB {
	function escape_deep( $data ) {
		if ( is_array( $data ) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[$k] = $this->_escape( $v );
				} else {
					$data[$k] = $this->_real_escape( $v );
				}
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}
}

/* BBPress needs this function to convert vars */
function backpress_convert_object( &$object, $output ) {
	if ( is_array( $object ) ) {
		foreach ( array_keys( $object ) as $key )
			backpress_convert_object( $object[$key], $output );
	} else {
		switch ( $output ) {
			case OBJECT  : break;
			case ARRAY_A : $object = get_object_vars($object); break;
			case ARRAY_N : $object = array_values(get_object_vars($object)); break;
		}
	}
}



?>