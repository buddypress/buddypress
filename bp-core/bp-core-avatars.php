<?php
/*
 Based on contributions from: Beau Lebens - http://www.dentedreality.com.au/
 Modified for BuddyPress by: Andy Peatling - http://apeatling.wordpress.com/
*/

/***
 * Set up the constants we need for avatar support
 */
function bp_core_set_avatar_constants() {
	global $bp;

	if ( !defined( 'BP_AVATAR_UPLOAD_PATH' ) )
		define( 'BP_AVATAR_UPLOAD_PATH', bp_core_avatar_upload_path() );

	if ( !defined( 'BP_AVATAR_URL' ) )
		define( 'BP_AVATAR_URL', bp_core_avatar_url() );

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

	if ( !defined( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE' ) ) {
		if ( !$bp->site_options['fileupload_maxk'] )
			define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', 5120000 ); /* 5mb */
		else
			define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', $bp->site_options['fileupload_maxk'] * 1024 );
	}

	if ( !defined( 'BP_AVATAR_DEFAULT' ) )
		define( 'BP_AVATAR_DEFAULT', BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg' );

	if ( !defined( 'BP_AVATAR_DEFAULT_THUMB' ) )
		define( 'BP_AVATAR_DEFAULT_THUMB', BP_PLUGIN_URL . '/bp-core/images/mystery-man-50.jpg' );
}
add_action( 'bp_loaded', 'bp_core_set_avatar_constants', 8 );

/**
 * bp_core_fetch_avatar()
 *
 * Fetches an avatar from a BuddyPress object. Supports user/group/blog as
 * default, but can be extended to include your own custom components too.
 *
 * @global object $bp
 * @global object $current_blog
 * @param array $args Determine the output of this function
 * @return string Formatted HTML <img> element, or raw avatar URL based on $html arg
 */
function bp_core_fetch_avatar( $args = '' ) {
	global $bp, $current_blog;

	// Set a few default variables
	$def_object		= 'user';
	$def_type		= 'thumb';
	$def_class		= 'avatar';
	$def_alt		= __( 'Avatar Image', 'buddypress' );

	// Set the default variables array
	$defaults = array(
		'item_id'		=> false,
		'object'		=> $def_object,	// user/group/blog/custom type (if you use filters)
		'type'			=> $def_type,	// thumb or full
		'avatar_dir'	=> false,		// Specify a custom avatar directory for your object
		'width'			=> false,		// Custom width (int)
		'height'		=> false,		// Custom height (int)
		'class'			=> $def_class,	// Custom <img> class (string)
		'css_id'		=> false,		// Custom <img> ID (string)
		'alt'			=> $def_alt,	// Custom <img> alt (string)
		'email'			=> false,		// Pass the user email (for gravatar) to prevent querying the DB for it
		'no_grav'		=> false,		// If there is no avatar found, return false instead of a grav?
		'html'			=> true			// Wrap the return img URL in <img />
	);

	// Compare defaults to passed and extract
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	// Set item_id if not passed
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

	// Set avatar_dir if not passed (uses $object)
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

	// Add an identifying class to each item
	$class .= ' ' . $object . '-' . $item_id . '-avatar';

	// Set CSS ID if passed
	if ( !empty( $css_id ) )
		$css_id = " id='{$css_id}'";

	// Set avatar width
	if ( $width )
		$html_width = " width='{$width}'";
	else
		$html_width = ( 'thumb' == $type ) ? ' width="' . BP_AVATAR_THUMB_WIDTH . '"' : ' width="' . BP_AVATAR_FULL_WIDTH . '"';

	// Set avatar height
	if ( $height )
		$html_height = " height='{$height}'";
	else
		$html_height = ( 'thumb' == $type ) ? ' height="' . BP_AVATAR_THUMB_HEIGHT . '"' : ' height="' . BP_AVATAR_FULL_HEIGHT . '"';

	// Set avatar URL and DIR based on prepopulated constants
	$avatar_folder_url = apply_filters( 'bp_core_avatar_folder_url', BP_AVATAR_URL . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', BP_AVATAR_UPLOAD_PATH . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

	/****
	 * Look for uploaded avatar first. Use it if it exists.
	 * Set the file names to search for, to select the full size
	 * or thumbnail image.
	 */
	$avatar_size = ( 'full' == $type ) ? '-bpfull' : '-bpthumb';
	$legacy_user_avatar_name = ( 'full' == $type ) ? '-avatar2' : '-avatar1';
	$legacy_group_avatar_name = ( 'full' == $type ) ? '-groupavatar-full' : '-groupavatar-thumb';

	// Check for directory
	if ( file_exists( $avatar_folder_dir ) ) {

		// Open directory
		if ( $av_dir = opendir( $avatar_folder_dir ) ) {

			// Stash files in an array once to check for one that matches
			$avatar_files = array();
			while ( false !== ( $avatar_file = readdir($av_dir) ) ) {
				// Only add files to the array (skip directories)
				if ( 2 < strlen( $avatar_file ) )
					$avatar_files[] = $avatar_file;
			}

			// Check for array
			if ( 0 < count( $avatar_files ) ) {

				// Check for current avatar
				foreach( $avatar_files as $key => $value ) {
					if ( strpos ( $value, $avatar_size )!== false )
						$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
				}

				// Legacy avatar check
				if ( !isset( $avatar_url ) ) {
					foreach( $avatar_files as $key => $value ) {
						if ( strpos ( $value, $legacy_user_avatar_name )!== false )
							$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
					}

					// Legacy group avatar check
					if ( !isset( $avatar_url ) ) {
						foreach( $avatar_files as $key => $value ) {
							if ( strpos ( $value, $legacy_group_avatar_name )!== false )
								$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
						}
					}
				}
			}
		}

		// Close the avatar directory
		closedir( $av_dir );

		// If we found a locally uploaded avatar
		if ( $avatar_url ) {

			// Return it wrapped in an <img> element
			if ( true === $html ) {
				return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $avatar_url . '" alt="' . $alt . '" class="' . $class . '"' . $css_id . $html_width . $html_height . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

			// ...or only the URL
			} else {
				return apply_filters( 'bp_core_fetch_avatar_url', $avatar_url );
			}
		}
	}

	// If no avatars could be found, try to display a gravatar

	// Skips gravatar check if $no_grav is passed
	if ( !$no_grav ) {

		// Set gravatar size
		if ( $width )
			$grav_size = $width;
		else if ( 'full' == $type )
			$grav_size = BP_AVATAR_FULL_WIDTH;
		else if ( 'thumb' == $type )
			$grav_size = BP_AVATAR_THUMB_WIDTH;

		// Set gravatar type
		if ( empty( $bp->grav_default->{$object} ) )
			$default_grav = 'wavatar';
		else if ( 'mystery' == $bp->grav_default->{$object} )
			$default_grav = apply_filters( 'bp_core_mysteryman_src', BP_AVATAR_DEFAULT, $grav_size );
		else
			$default_grav = $bp->grav_default->{$object};

		// Set gravatar object
		if ( empty( $email ) ) {
			if ( 'user' == $object ) {
				$email = bp_core_get_user_email( $item_id );
			} else if ( 'group' == $object || 'blog' == $object ) {
				$email = "{$item_id}-{$object}@{$bp->root_domain}";
			}
		}

		// Set host based on if using ssl
		if ( is_ssl() )
			$host = 'https://secure.gravatar.com/avatar/';
		else
			$host = 'http://www.gravatar.com/avatar/';

		// Filter gravatar vars
		$email		= apply_filters( 'bp_core_gravatar_email', $email, $item_id, $object );
		$gravatar	= apply_filters( 'bp_gravatar_url', $host ) . md5( strtolower( $email ) ) . '?d=' . $default_grav . '&amp;s=' . $grav_size;

		// Return gravatar wrapped in <img />
		if ( true === $html )
			return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $gravatar . '" alt="' . $alt . '" class="' . $class . '"' . $css_id . $html_width . $html_height . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

		// ...or only return the gravatar URL
		else
			return apply_filters( 'bp_core_fetch_avatar_url', $gravatar );

	} else {
		return apply_filters( 'bp_core_fetch_avatar', false, $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );
	}
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

	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', BP_AVATAR_UPLOAD_PATH . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

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

	/***
	 * You may want to hook into this filter if you want to override this function.
	 * Make sure you return false.
	 */
	if ( !apply_filters( 'bp_core_pre_avatar_handle_upload', true, $file, $upload_dir_filter ) )
		return true;

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

	/* Filter the upload location */
	add_filter( 'upload_dir', $upload_dir_filter, 10, 0 );

	$bp->avatar_admin->original = wp_handle_upload( $file['file'], array( 'action'=> 'bp_avatar_upload' ) );

	/* Move the file to the correct upload location. */
	if ( !empty( $bp->avatar_admin->original['error'] ) ) {
		bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->original['error'] ), 'error' );
		return false;
	}

	/* Get image size */
	$size = @getimagesize( $bp->avatar_admin->original['file'] );

	/* Check image size and shrink if too large */
	if ( $size[0] > BP_AVATAR_ORIGINAL_MAX_WIDTH ) {
		$thumb = wp_create_thumbnail( $bp->avatar_admin->original['file'], BP_AVATAR_ORIGINAL_MAX_WIDTH );

		/* Check for thumbnail creation errors */
		if ( is_wp_error( $thumb ) ) {
			bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $thumb->get_error_message() ), 'error' );
			return false;
		}

		/* Thumbnail is good so proceed */
		$bp->avatar_admin->resized = $thumb;
	}

	/* We only want to handle one image after resize. */
	if ( empty( $bp->avatar_admin->resized ) )
		$bp->avatar_admin->image->dir = str_replace( BP_AVATAR_UPLOAD_PATH, '', $bp->avatar_admin->original['file'] );
	else {
		$bp->avatar_admin->image->dir = str_replace( BP_AVATAR_UPLOAD_PATH, '', $bp->avatar_admin->resized );
		@unlink( $bp->avatar_admin->original['file'] );
	}

	/* Set the url value for the image */
	$bp->avatar_admin->image->url = BP_AVATAR_URL . $bp->avatar_admin->image->dir;

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

	/***
	 * You may want to hook into this filter if you want to override this function.
	 * Make sure you return false.
	 */
	if ( !apply_filters( 'bp_core_pre_avatar_handle_crop', true, $r ) )
		return true;

	extract( $r, EXTR_SKIP );

	if ( !$original_file )
		return false;

	$original_file = BP_AVATAR_UPLOAD_PATH . $original_file;

	if ( !file_exists( $original_file ) )
		return false;

	if ( !$item_id )
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $original_file ), $item_id, $object, $avatar_dir );
	else
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', BP_AVATAR_UPLOAD_PATH . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

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
	$full_cropped = wp_crop_image( $original_file, (int)$crop_x, (int)$crop_y, (int)$crop_w, (int)$crop_h, BP_AVATAR_FULL_WIDTH, BP_AVATAR_FULL_HEIGHT, false, $avatar_folder_dir . '/' . $full_filename );
	$thumb_cropped = wp_crop_image( $original_file, (int)$crop_x, (int)$crop_y, (int)$crop_w, (int)$crop_h, BP_AVATAR_THUMB_WIDTH, BP_AVATAR_THUMB_HEIGHT, false, $avatar_folder_dir . '/' . $thumb_filename );

	/* Remove the original */
	@unlink( $original_file );

	return true;
}

/**
 * bp_core_fetch_avatar_filter()
 *
 * Attempts to filter get_avatar function and let BuddyPress have a go
 * at finding an avatar that may have been uploaded locally.
 *
 * @global array $authordata
 * @param string $avatar The result of get_avatar from before-filter
 * @param int|string|object $user A user ID, email address, or comment object
 * @param int $size Size of the avatar image (thumb/full)
 * @param string $default URL to a default image to use if no avatar is available
 * @param string $alt Alternate text to use in image tag. Defaults to blank
 * @return <type>
 */
function bp_core_fetch_avatar_filter( $avatar, $user, $size, $default, $alt ) {
	global $pagenow;

	// Do not filter if inside WordPress options page
	if ( 'options-discussion.php' == $pagenow )
		return $avatar;

	// If passed an object, assume $user->user_id
	if ( is_object( $user ) )
		$id = $user->user_id;

	// If passed a number, assume it was a $user_id
	else if ( is_numeric( $user ) )
		$id = $user;

	// If passed a string and that string returns a user, get the $id
	else if ( is_string( $user ) && ( $user_by_email = get_user_by_email( $user ) ) )
		$id = $user_by_email->ID;

	// If somehow $id hasn't been assigned, return the result of get_avatar
	if ( empty( $id ) )
		return !empty( $avatar ) ? $avatar : $default;

	// Let BuddyPress handle the fetching of the avatar
	$bp_avatar = bp_core_fetch_avatar( array( 'item_id' => $id, 'width' => $size, 'height' => $size, 'alt' => $alt ) );

	// If BuddyPress found an avatar, use it. If not, use the result of get_avatar
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

/**
 * bp_core_avatar_upload_path()
 *
 * Returns the absolute upload path for the WP installation
 *
 * @global object $current_blog Current blog information
 * @uses wp_upload_dir To get upload directory info
 * @return string Absolute path to WP upload directory
 */
function bp_core_avatar_upload_path() {
	global $current_blog;

	// Get upload directory information from current site
	$upload_dir = wp_upload_dir();

	// If multisite, and current blog does not match root blog, make adjustments
	if ( bp_core_is_multisite() && BP_ROOT_BLOG != $current_blog->blog_id )
		$upload_dir['basedir'] = get_blog_option( BP_ROOT_BLOG, 'upload_path' );

	return apply_filters( 'bp_core_avatar_upload_path', $upload_dir['basedir'] );
}

/**
 * bp_core_avatar_url()
 *
 * Returns the raw base URL for root site upload location
 *
 * @global object $current_blog Current blog information
 * @uses wp_upload_dir To get upload directory info
 * @return string Full URL to current upload location
 */
function bp_core_avatar_url() {
	global $current_blog;

	// Get upload directory information from current site
	$upload_dir = wp_upload_dir();

	// If multisite, and current blog does not match root blog, make adjustments
	if ( bp_core_is_multisite() && BP_ROOT_BLOG != $current_blog->blog_id )
		$upload_dir['baseurl'] = str_replace( get_blog_option( $current_blog->blog_id, 'home' ) , get_blog_option( BP_ROOT_BLOG, 'home' ), $upload_dir['baseurl'] );

	return apply_filters( 'bp_core_avatar_url', $upload_dir['baseurl'] );
}

?>