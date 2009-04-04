<?php
/* Load the WP environment */
require_once( preg_replace('%(.*)[/\\\\]wp-content[/\\\\].*%', '\1', dirname( __FILE__ ) ) . '/wp-load.php' ); 
require_once( BP_PLUGIN_DIR . '/bp-core.php' );
require_once( BP_PLUGIN_DIR . '/bp-friends.php' );

// Setup the $bp global array as it's not auto set outside of the normal WP enviro.
bp_core_setup_globals();
friends_setup_globals();

// Get the friend ids based on the search terms
$friends = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $_GET['limit'], 1 );

if ( $friends['friends'] ) {
	foreach ( $friends['friends'] as $key => $friend ) {
		$ud = get_userdata($friend['user_id']);
		$username = $ud->user_login;
		echo bp_core_get_avatar( $friend['user_id'], 1, 15, 15 ) . ' ' . bp_fetch_user_fullname( $friend['user_id'], false ) . ' (' . $username . ')';
	}
}
?>