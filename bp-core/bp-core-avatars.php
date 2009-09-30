<?php
/*
 Based on contributions from: Beau Lebens - http://www.dentedreality.com.au/
 Modified for BuddyPress by: Andy Peatling - http://apeatling.wordpress.com/
*/

/***
 * Set up the constants we need for avatar support
 */

if ( !defined( 'BP_AVATAR_THUMB_WIDTH' ) )
	define( 'BP_AVATAR_THUMB_WIDTH', 50 );

if ( !defined( 'BP_AVATAR_THUMB_HEIGHT' ) )
	define( 'BP_AVATAR_THUMB_HEIGHT', 50 );

if ( !defined( 'BP_AVATAR_FULL_WIDTH' ) )
	define( 'BP_AVATAR_FULL_WIDTH', 150 );

if ( !defined( 'BP_AVATAR_FULL_HEIGHT' ) )
	define( 'BP_AVATAR_FULL_HEIGHT', 150 );

if ( !defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) )
	define( 'BP_AVATAR_ORIGINAL_MAX_WIDTH', 450 );

if ( !defined( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE' ) )
	define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', get_site_option( 'fileupload_maxk' ) * 1024 );

if ( !defined( 'BP_AVATAR_DEFAULT' ) )
	define( 'BP_AVATAR_DEFAULT', BP_PLUGIN_URL . '/bp-xprofile/images/none.gif' );

if ( !defined( 'BP_AVATAR_DEFAULT_THUMB' ) )
	define( 'BP_AVATAR_DEFAULT_THUMB', BP_PLUGIN_URL . '/bp-xprofile/images/none-thumbnail.gif' );
	
function bp_core_fetch_avatar( $args = '' ) {
	global $bp, $current_blog;
	
	$defaults = array(
		'item_id' => false,
		'object' => 'user', // user OR group OR blog OR custom type (if you use filters)
		'type' => 'thumb',
		'avatar_dir' => false,
		'width' => false, 
		'height' => false,
		'class' => 'avatar',
		'css_id' => false,
		'alt' => __( 'Avatar Image', 'buddypress' ),
		'no_grav' => false // If there is no avatar found, return false instead of a grav?
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );	

	if ( !$item_id ) {
		if ( 'user' == $object )
			$item_id = $bp->displayed_user->id;
		else if ( 'group' == $object )
			$item_id = $bp->groups->current_group->id;
		else if ( 'blog' == $object )
			$item_id = $current_blog->id;
			
		$item_id = apply_filters( 'bp_core_avatar_item_id', $item_id, $object );
	
		if ( !$item_id ) return false;
	}
		
	if ( !$avatar_dir ) {
		if ( 'user' == $object )
			$avatar_dir = 'avatars';
		else if ( 'group' == $object )
			$avatar_dir = 'group-avatars';
		else if ( 'blog' == $object )
			$avatar_dir = 'blog-avatars';
			
		$avatar_dir = apply_filters( 'bp_core_avatar_dir', $avatar_dir, $object );
		
		if ( !$avatar_dir ) return false;		
	}
	
	if ( !$css_id )
		$css_id = $object . '-' . $item_id . '-avatar'; 
	
	if ( $width )
		$html_width = " width='{$width}'";
	else
		$html_width = ( 'thumb' == $type ) ? ' width="' . BP_AVATAR_THUMB_WIDTH . '"' : ' width="' . BP_AVATAR_FULL_WIDTH . '"';
		
	if ( $height )
		$html_height = " height='{$height}'";
	else
		$html_height = ( 'thumb' == $type ) ? ' height="' . BP_AVATAR_THUMB_HEIGHT . '"' : ' height="' . BP_AVATAR_FULL_HEIGHT . '"';
	
	$avatar_folder_url = apply_filters( 'bp_core_avatar_folder_url', get_blog_option( BP_ROOT_BLOG, 'siteurl' ) . '/' . basename( WP_CONTENT_DIR ) . '/blogs.dir/' . BP_ROOT_BLOG . '/files/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );	
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );	
	
	/* If no avatars have been uploaded for this item, display a gravatar */	
	if ( !file_exists( $avatar_folder_dir ) && !$no_grav ) {
		
		if ( empty( $bp->grav_default->{$object} ) )
			$default_grav = 'wavatar';
		else if ( 'mystery' == $bp->grav_default->{$object} )
			$default_grav = BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg';
		else
			$default_grav = $bp->grav_default->{$object};

		if ( $width ) $grav_size = $width;
		else if ( 'full' == $type ) $grav_size = BP_AVATAR_FULL_WIDTH;
		else if ( 'thumb' == $type ) $grav_size = BP_AVATAR_THUMB_WIDTH;
		
		if ( 'user' == $object ) {
			$ud = get_userdata( $item_id );
			$grav_email = $ud->user_email;
		} else if ( 'group' == $object || 'blog' == $object ) {
			$grav_email = "{$item_id}-{$object}@{$bp->root_domain}";
		}
	
		$grav_email = apply_filters( 'bp_core_gravatar_email', $grav_email, $item_id, $object );	
		$gravatar = apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' ) . md5( $grav_email ) . '?d=' . $default_grav . '&amp;s=' . $grav_size;
		
		return apply_filters( 'bp_core_fetch_avatar', "<img src='{$gravatar}' alt='{$alt}' id='{$css_id}' class='{$class}'{$html_width}{$html_height} />", $params );
	
	} else if ( !file_exists( $avatar_folder_dir ) && $no_grav )
		return false;
	
	/* Set the file names to search for to select the full size or thumbnail image. */
	$avatar_name = ( 'full' == $type ) ? '-bpfull' : '-bpthumb';	
	$legacy_user_avatar_name = ( 'full' == $type ) ? '-avatar2' : '-avatar1';	
	$legacy_group_avatar_name = ( 'full' == $type ) ? '-groupavatar-full' : '-groupavatar-thumb';	
	
	if ( $av_dir = opendir( $avatar_folder_dir ) ) {
	    while ( false !== ( $avatar_file = readdir($av_dir) ) ) {
			if ( preg_match( "/{$avatar_name}/", $avatar_file ) || preg_match( "/{$legacy_user_avatar_name}/", $avatar_file ) || preg_match( "/{$legacy_group_avatar_name}/", $avatar_file ) )
				$avatar_url = $avatar_folder_url . '/' . $avatar_file;
	    }
	}
    closedir($av_dir);

	return apply_filters( 'bp_core_fetch_avatar', "<img src='{$avatar_url}' alt='{$alt}' id='{$css_id}' class='{$class}'{$html_width}{$html_height} />", $params );	
}

function bp_core_delete_existing_avatar( $args = '' ) {
	global $bp;
	
	$defaults = array(
		'item_id' => false,
		'object' => 'user', // user OR group OR blog OR custom type (if you use filters)
		'avatar_dir' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );	
	
	if ( !$item_id ) {
		if ( 'user' == $object )
			$item_id = $bp->displayed_user->id;
		else if ( 'group' == $object )
			$item_id = $bp->groups->current_group->id;
		else if ( 'blog' == $object )
			$item_id = $current_blog->id;
			
		$item_id = apply_filters( 'bp_core_avatar_item_id', $item_id, $object );
	
		if ( !$item_id ) return false;
	}
		
	if ( !$avatar_dir ) {
		if ( 'user' == $object )
			$avatar_dir = 'avatars';
		else if ( 'group' == $object )
			$avatar_dir = 'group-avatars';
		else if ( 'blog' == $object )
			$avatar_dir = 'blog-avatars';
			
		$avatar_dir = apply_filters( 'bp_core_avatar_dir', $avatar_dir, $object );
		
		if ( !$avatar_dir ) return false;		
	}

	if ( 'user' == $object ) {
		/* Delete any legacy meta entries if this is a user avatar */
		delete_usermeta( $item_id, 'bp_core_avatar_v1_path' );
		delete_usermeta( $item_id, 'bp_core_avatar_v1' );
		delete_usermeta( $item_id, 'bp_core_avatar_v2_path' );
		delete_usermeta( $item_id, 'bp_core_avatar_v2' );
	}

	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );	

	if ( !file_exists( $avatar_folder_dir ) )
		return false;

	if ( $av_dir = opendir( $avatar_folder_dir ) ) {
	    while ( false !== ( $avatar_file = readdir($av_dir) ) ) {
			if ( ( preg_match( "/-bpfull/", $avatar_file ) || preg_match( "/-bpthumb/", $avatar_file ) ) && '.' != $avatar_file && '..' != $avatar_file )
				@unlink( $avatar_folder_dir . '/' . $avatar_file );				
		}
	}
    closedir($av_dir);

	@rmdir( $avatar_folder_dir );

	do_action( 'bp_core_delete_existing_avatar', $args );

	return true;
}

function bp_core_avatar_handle_upload( $file, $upload_dir_filter ) {
	global $bp;
	
	require_once( ABSPATH . '/wp-admin/includes/image.php' );
	require_once( ABSPATH . '/wp-admin/includes/file.php' );
	
	$uploadErrors = array(
        0 => __("There is no error, the file uploaded with success", 'buddypress'), 
        1 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(BP_AVATAR_ORIGINAL_MAX_FILESIZE), 
        2 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(BP_AVATAR_ORIGINAL_MAX_FILESIZE),
        3 => __("The uploaded file was only partially uploaded", 'buddypress'),
        4 => __("No file was uploaded", 'buddypress'),
        6 => __("Missing a temporary folder", 'buddypress')
	);

	if ( !bp_core_check_avatar_upload( $file ) ) {
		bp_core_add_message( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'buddypress' ), $uploadErrors[$file['file']['error']] ), 'error' );
		return false;
	}
	
	if ( !bp_core_check_avatar_size( $file ) ) {
		bp_core_add_message( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'buddypress'), size_format(BP_AVATAR_ORIGINAL_MAX_FILESIZE) ), 'error' );
		return false;
	}
	
	if ( !bp_core_check_avatar_type( $file ) ) {
		bp_core_add_message( __( 'Please upload only JPG, GIF or PNG photos.', 'buddypress' ), 'error' );
		return false;
	}
	
	// Filter the upload location
	add_filter( 'upload_dir', $upload_dir_filter, 10, 0 );
	
	$bp->avatar_admin->original = wp_handle_upload( $file['file'], array( 'action'=> 'bp_avatar_upload' ) );

	// Move the file to the correct upload location.
	if ( !empty( $bp->avatar_admin->original['error'] ) ) {
		bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->original['error'] ), 'error' );
		return false;
	}
		
	// Resize the image down to something manageable and then delete the original
	if ( getimagesize( $bp->avatar_admin->original['file'] ) > BP_AVATAR_ORIGINAL_MAX_WIDTH ) {
		$bp->avatar_admin->resized = wp_create_thumbnail( $bp->avatar_admin->original['file'], BP_AVATAR_ORIGINAL_MAX_WIDTH );
	}
	
	$bp->avatar_admin->image = new stdClass;
	
	// We only want to handle one image after resize. 
	if ( empty( $bp->avatar_admin->resized ) )
		$bp->avatar_admin->image->dir = $bp->avatar_admin->original['file'];
	else {
		$bp->avatar_admin->image->dir = $bp->avatar_admin->resized;
		@unlink( $bp->avatar_admin->original['file'] );
	}
	
	/* Set the url value for the image */
	$bp->avatar_admin->image->url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $bp->avatar_admin->image->dir );

	return true;
}

function bp_core_avatar_handle_crop( $args = '' ) {
	global $bp;
	
	$defaults = array(
		'object' => 'user',
		'avatar_dir' => 'avatars',
		'item_id' => false,
		'original_file' => false,
		'crop_w' => BP_AVATAR_FULL_WIDTH,
		'crop_h' => BP_AVATAR_FULL_HEIGHT,
		'crop_x' => 0,
		'crop_y' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$original_file )
		return false;

	if ( !file_exists( WP_CONTENT_DIR . '/' . $original_file ) )
		return false;
			
	if ( !$item_id )
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', WP_CONTENT_DIR . dirname( $original_file ), $item_id, $object, $avatar_dir );	
	else
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', WP_CONTENT_DIR . '/blogs.dir/' . BP_ROOT_BLOG . '/files/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

	if ( !file_exists( $avatar_folder_dir ) )
		return false;
	
	require_once( ABSPATH . '/wp-admin/includes/image.php' );
	require_once( ABSPATH . '/wp-admin/includes/file.php' );

	/* Delete the existing avatar files for the object */
	bp_core_delete_existing_avatar( array( 'object' => $object, 'avatar_path' => $avatar_folder_dir ) );
	
	/* Make sure we at least have a width and height for cropping */
	if ( !(int)$crop_w )
		$crop_w = BP_AVATAR_FULL_WIDTH;
	
	if ( !(int)$crop_h )
		$crop_h = BP_AVATAR_FULL_HEIGHT;

	/* Set the full and thumb filenames */
	$full_filename = wp_hash( $original_file . time() ) . '-bpfull.jpg';
	$thumb_filename = wp_hash( $original_file . time() ) . '-bpthumb.jpg';
			
	/* Crop the image */
	$full_cropped = wp_crop_image( WP_CONTENT_DIR . $original_file, (int)$crop_x, (int)$crop_y, (int)$crop_w, (int)$crop_h, BP_AVATAR_FULL_WIDTH, BP_AVATAR_FULL_HEIGHT, false, $avatar_folder_dir . '/' . $full_filename );
	$thumb_cropped = wp_crop_image( WP_CONTENT_DIR . $original_file, (int)$crop_x, (int)$crop_y, (int)$crop_w, (int)$crop_h, BP_AVATAR_THUMB_WIDTH, BP_AVATAR_THUMB_HEIGHT, false, $avatar_folder_dir . '/' . $thumb_filename );
	
	/* Remove the original */
	@unlink( WP_CONTENT_DIR . $original_file );

	return true;
}

// Override internal "get_avatar()" function to use our own where possible
function bp_core_fetch_avatar_filter( $avatar, $id_or_email, $size, $default, $alt ) {
	if ( is_object ( $id_or_email ) )
		$id_or_email = $id_or_email->user_id;
	
	$bp_avatar = bp_core_fetch_avatar( array( 'no_grav' => true, 'item_id' => $id_or_email, 'width' => $size, 'height' => $size, 'alt' => $alt ) );

	return ( !$bp_avatar ) ? $avatar : $bp_avatar;
}
add_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10, 5 );

function bp_core_check_avatar_upload($file) {
	if ( $file['error'] )
		return false;
	
	return true;
}

function bp_core_check_avatar_size($file) {
	if ( $file['file']['size'] > BP_AVATAR_ORIGINAL_MAX_FILESIZE )
		return false;
	
	return true;
}

function bp_core_check_avatar_type($file) {
	if ( ( strlen($file['file']['type']) && !preg_match('/(jpe?g|gif|png)$/', $file['file']['type'] ) ) && !preg_match( '/(jpe?g|gif|png)$/', $file['file']['name'] ) )
		return false;
	
	return true;
}

?>