<?php
require_once( 'bp-core.php' );

define ( 'BP_WIRE_IS_INSTALLED', 1 );
define ( 'BP_WIRE_VERSION', '0.1' );

include_once( 'bp-wire/bp-wire-classes.php' );
//include_once( 'bp-wire/bp-wire-ajax.php' );
//include_once( 'bp-wire/bp-wire-cssjs.php' );
/*include_once( 'bp-messages/bp-wire-admin.php' );*/
include_once( 'bp-wire/bp-wire-templatetags.php' );


/**************************************************************************
 bp_bp_wire_install()
 
 Sets up the component ready for use on a site installation.
 **************************************************************************/

function bp_wire_install( $version ) {
	global $wpdb, $bp;
	
	// No DB tables need to be installed, DB tables for each component wire
	// are set up within that component *if* this component is installed.
	
	add_site_option( 'bp-wire-version', $version );
}

/**************************************************************************
 bp_wire_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function bp_wire_setup_globals() {
	global $bp, $wpdb;
	
	if ( get_site_option('bp-wire-version') < BP_WIRE_VERSION ) {
		bp_wire_install(BP_WIRE_VERSION);
	}
	
	$bp['wire'] = array(
		'image_base' => get_option('siteurl') . '/wp-content/mu-plugins/bp-wire/images',
		'slug'		 => 'wire'
	);
}
add_action( 'wp', 'bp_wire_setup_globals', 1 );	
add_action( '_admin_menu', 'bp_wire_setup_globals', 1 );

/**************************************************************************
 bp_wire_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function bp_wire_setup_nav() {
	global $bp;

	$nav_key = count($bp['bp_nav']) + 1;
	$user_nav_key = count($bp['bp_users_nav']) + 1;

	$bp['bp_nav'][$nav_key] = array(
		'id'	=> $bp['wire']['slug'],
		'name'  => __('Wire'), 
		'link'  => $bp['loggedin_domain'] . $bp['wire']['slug'] . '/'
	);
	
	$bp['bp_users_nav'][$user_nav_key] = array(
		'id'	=> $bp['wire']['slug'],
		'name'  => __('Wire'), 
		'link'  => $bp['current_domain'] . $bp['wire']['slug'] . '/'
	);

	$bp['bp_options_nav'][$bp['wire']['slug']] = array(
		''    => array( 
			'name'      => __('All Posts'),
			'link'      => $bp['loggedin_domain'] . $bp['wire']['slug'] . '/all-posts' ),
	);
	
	if ( $bp['current_component'] == $bp['wire']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Wire');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false ); 
		}
	}

}
add_action( 'wp', 'bp_wire_setup_nav', 2 );


/**************************************************************************
 bp_wire_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function bp_wire_catch_action() {
	global $bp, $current_blog;
	
	if ( $bp['current_component'] == $bp['wire']['slug'] && $current_blog->blog_id > 1 ) {
		switch ( $bp['current_action'] ) {
			case 'post':
				if ( bp_wire_new_post( $bp['current_userid'], $_POST['wire-post-textarea'], $bp['profile']['table_name_wire'] ) ) {
					$bp['message'] = __('Wire message successfully posted.');
					$bp['message_type'] = 'success';

					add_action( 'template_notices', 'bp_core_render_notice' );
				}
				
				if ( !strpos( $_SERVER['HTTP_REFERER'], $bp['wire']['slug'] ) ) {
					$bp['current_component'] = $bp['profile']['slug'];
					$bp['current_action'] = 'public';
					bp_catch_uri( 'profile/index' );
				} else {
					bp_catch_uri( 'wire/latest' );
				}
						
			break;
			
			case 'delete':	
				if ( bp_wire_delete_post( $bp['action_variables'][0], $is_item_admin, $bp['profile']['table_name_wire'] ) ) {
					$bp['message'] = __('Wire message successfully deleted.');
					$bp['message_type'] = 'success';

					add_action( 'template_notices', 'bp_core_render_notice' );									
				}
				
				if ( !strpos( $_SERVER['HTTP_REFERER'], $bp['wire']['slug'] ) ) {
					$bp['current_component'] = $bp['profile']['slug'];
					$bp['current_action'] = 'public';
					bp_catch_uri( 'profile/index' );
				} else {
					bp_catch_uri( 'wire/latest' );
				}			
			break;
			
			default:
				bp_catch_uri( 'wire/latest' );		
			break;
		}
	}
}
add_action( 'wp', 'bp_wire_catch_action', 3 );


function bp_wire_new_post( $item_id, $message, $table_name = null ) {
	global $bp;
	
	if ( empty($message) || !is_user_logged_in() )
		return false;
	
	if ( !$table_name )
		$table_name = $bp[$bp['current_component']]['table_name_wire'];

	$wire_post = new BP_Wire_Post( $table_name );
	$wire_post->item_id = $item_id;
	$wire_post->user_id = $bp['loggedin_userid'];
	$wire_post->date_posted = time();
	
	$message = strip_tags( $message );
	$wire_post->content = $message;
	
	if ( !$wire_post->save() )
		return false;
	
	do_action( 'bp_wire_post_posted', $wire_post->id, $wire_post->item_id, $wire_post->user_id );
	
	return true;
}

function bp_wire_delete_post( $wire_post_id, $is_item_admin = false, $table_name = null ) {
	global $bp;
	
	if ( !is_user_logged_in() )
		return false;

	if ( !$table_name )
		$table_name = $bp[$bp['current_component']]['table_name_wire'];
	
	$wire_post = new BP_Wire_Post( $table_name, $wire_post_id );

	if ( !$is_item_admin ) {
		if ( $wire_post->user_id != $bp['loggedin_userid'] )
			return false;
	}
	
	if ( !$wire_post->delete() )
		return false;

	do_action( 'bp_wire_post_deleted', $wire_post->id, $wire_post->item_id, $wire_post->user_id );
	
	return true;
}

?>