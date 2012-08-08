<?php

/**
 * BuddyPress Avatars
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/***
 * Set up the constants we need for avatar support
 */
function bp_core_set_avatar_constants() {
	global $bp;

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
		if ( !isset( $bp->site_options['fileupload_maxk'] ) ) {
			define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', 5120000 ); // 5mb
		} else {
			define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', $bp->site_options['fileupload_maxk'] * 1024 );
		}
	}

	if ( !defined( 'BP_AVATAR_DEFAULT' ) )
		define( 'BP_AVATAR_DEFAULT', BP_PLUGIN_URL . 'bp-core/images/mystery-man.jpg' );

	if ( !defined( 'BP_AVATAR_DEFAULT_THUMB' ) )
		define( 'BP_AVATAR_DEFAULT_THUMB', BP_PLUGIN_URL . 'bp-core/images/mystery-man-50.jpg' );
}
add_action( 'bp_init', 'bp_core_set_avatar_constants', 3 );

function bp_core_set_avatar_globals() {
	global $bp;

	$bp->avatar        = new stdClass;
	$bp->avatar->thumb = new stdClass;
	$bp->avatar->full  = new stdClass;

	// Dimensions
	$bp->avatar->thumb->width  	   = BP_AVATAR_THUMB_WIDTH;
	$bp->avatar->thumb->height 	   = BP_AVATAR_THUMB_HEIGHT;
	$bp->avatar->full->width 	   = BP_AVATAR_FULL_WIDTH;
	$bp->avatar->full->height 	   = BP_AVATAR_FULL_HEIGHT;

	// Upload maximums
	$bp->avatar->original_max_width    = BP_AVATAR_ORIGINAL_MAX_WIDTH;
	$bp->avatar->original_max_filesize = BP_AVATAR_ORIGINAL_MAX_FILESIZE;

	// Defaults
	$bp->avatar->thumb->default = BP_AVATAR_DEFAULT_THUMB;
	$bp->avatar->full->default 	= BP_AVATAR_DEFAULT;

	// These have to be set on page load in order to avoid infinite filter loops at runtime
	$bp->avatar->upload_path = bp_core_avatar_upload_path();
	$bp->avatar->url	   	 = bp_core_avatar_url();

	// Backpat for pre-1.5
	if ( ! defined( 'BP_AVATAR_UPLOAD_PATH' ) )
		define( 'BP_AVATAR_UPLOAD_PATH', $bp->avatar->upload_path );

	// Backpat for pre-1.5
	if ( ! defined( 'BP_AVATAR_URL' ) )
		define( 'BP_AVATAR_URL', $bp->avatar->url );

	do_action( 'bp_core_set_avatar_globals' );
}
add_action( 'bp_setup_globals', 'bp_core_set_avatar_globals' );

/**
 * bp_core_fetch_avatar()
 *
 * Fetches an avatar from a BuddyPress object. Supports user/group/blog as
 * default, but can be extended to include your own custom components too.
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @global $current_blog WordPress global containing information and settings for the current blog being viewed.
 * @param array $args Determine the output of this function
 * @return string Formatted HTML <img> element, or raw avatar URL based on $html arg
 */
function bp_core_fetch_avatar( $args = '' ) {
	global $bp, $current_blog;

	// Set a few default variables
	$def_object = 'user';
	$def_type   = 'thumb';
	$def_class  = 'avatar';

	// Set the default variables array
	$defaults = array(
		'item_id'    => false,
		'object'     => $def_object, // user/group/blog/custom type (if you use filters)
		'type'       => $def_type,   // thumb or full
		'avatar_dir' => false,       // Specify a custom avatar directory for your object
		'width'      => false,       // Custom width (int)
		'height'     => false,       // Custom height (int)
		'class'      => $def_class,  // Custom <img> class (string)
		'css_id'     => false,       // Custom <img> ID (string)
		'alt'        => '',    	     // Custom <img> alt (string)
		'email'      => false,       // Pass the user email (for gravatar) to prevent querying the DB for it
		'no_grav'    => false,       // If there is no avatar found, return false instead of a grav?
		'html'       => true,        // Wrap the return img URL in <img />
		'title'      => ''           // Custom <img> title (string)
	);

	// Compare defaults to passed and extract
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	/** Set item_id ***********************************************************/

	if ( empty( $item_id ) ) {

		switch ( $object ) {

			case 'blog'  :
				$item_id = $current_blog->id;
				break;

			case 'group' :
				if ( bp_is_active( 'groups' ) ) {
					$item_id = $bp->groups->current_group->id;
				} else {
					$item_id = false;
				}

				break;

			case 'user'  :
			default      :
				$item_id = bp_displayed_user_id();
				break;
		}

		$item_id = apply_filters( 'bp_core_avatar_item_id', $item_id, $object, $params );

		if ( empty( $item_id ) ) {
			return false;
		}
	}

	$class = apply_filters( 'bp_core_avatar_class', $class, $item_id, $object, $params );

	/** Set avatar_dir ********************************************************/

	if ( empty( $avatar_dir ) ) {

		switch ( $object ) {

			case 'blog'  :
				$avatar_dir = 'blog-avatars';
				break;

			case 'group' :
				if ( bp_is_active( 'groups' ) ) {
					$avatar_dir = 'group-avatars';
				} else {
					$avatar_dir = false;
				}

				break;

			case 'user'  :
			default      :
				$avatar_dir = 'avatars';
				break;
		}

		$avatar_dir = apply_filters( 'bp_core_avatar_dir', $avatar_dir, $object, $params );

		if ( empty( $avatar_dir ) ) {
			return false;
		}
	}

	/** <img> alt *************************************************************/

	if ( false !== strpos( $alt, '%s' ) || false !== strpos( $alt, '%1$s' ) ) {

		// Get item name for alt/title tags
		$item_name = '';

		switch ( $object ) {

			case 'blog'  :
				$item_name = get_blog_option( $item_id, 'blogname' );
				break;

			case 'group' :
				$item_name = bp_get_group_name( groups_get_group( array( 'group_id' => $item_id ) ) );
				break;

			case 'user'  :
			default :
				$item_name = bp_core_get_user_displayname( $item_id );
				break;
		}

		$item_name = apply_filters( 'bp_core_avatar_alt', $item_name, $item_id, $object, $params );
		$alt       = sprintf( $alt, $item_name );
	}

	/** Sanity Checks *********************************************************/

	// Get a fallback for the 'alt' parameter
	if ( empty( $alt ) )
		$alt = __( 'Avatar Image', 'buddypress' );

	// Set title tag, if it's been provided
	if ( !empty( $title ) )
		$title = " title='" . esc_attr( apply_filters( 'bp_core_avatar_title', $title, $item_id, $object, $params ) ) . "'";

	// Set CSS ID if passed
	if ( !empty( $css_id ) )
		$css_id = ' id="' . $css_id . '"';

	// Set image width
	if ( false !== $width )
		$html_width = ' width="' . $width . '"';
	else
		$html_width = ( 'thumb' == $type ) ? ' width="' . bp_core_avatar_thumb_width() . '"' : ' width="' . bp_core_avatar_full_width() . '"';

	// Set image height
	if ( false !== $height )
		$html_height = ' height="' . $height . '"';
	else
		$html_height = ( 'thumb' == $type ) ? ' height="' . bp_core_avatar_thumb_height() . '"' : ' height="' . bp_core_avatar_full_height() . '"';

	// Set img URL and DIR based on prepopulated constants
	$avatar_loc        = new stdClass();
	$avatar_loc->path  = trailingslashit( bp_core_avatar_upload_path() );
	$avatar_loc->url   = trailingslashit( bp_core_avatar_url() );

	$avatar_loc->dir   = trailingslashit( $avatar_dir );
	$avatar_folder_url = apply_filters( 'bp_core_avatar_folder_url', ( $avatar_loc->url  . $avatar_loc->dir . $item_id ), $item_id, $object, $avatar_dir );
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', ( $avatar_loc->path . $avatar_loc->dir . $item_id ), $item_id, $object, $avatar_dir );

	// Add an identifying class
	$class .= ' ' . $object . '-' . $item_id . '-avatar';

	/****
	 * Look for uploaded avatar first. Use it if it exists.
	 * Set the file names to search for, to select the full size
	 * or thumbnail image.
	 */
	$avatar_size              = ( 'full' == $type ) ? '-bpfull' : '-bpthumb';
	$legacy_user_avatar_name  = ( 'full' == $type ) ? '-avatar2' : '-avatar1';
	$legacy_group_avatar_name = ( 'full' == $type ) ? '-groupavatar-full' : '-groupavatar-thumb';

	// Check for directory
	if ( file_exists( $avatar_folder_dir ) ) {

		// Open directory
		if ( $av_dir = opendir( $avatar_folder_dir ) ) {

			// Stash files in an array once to check for one that matches
			$avatar_files = array();
			while ( false !== ( $avatar_file = readdir( $av_dir ) ) ) {
				// Only add files to the array (skip directories)
				if ( 2 < strlen( $avatar_file ) ) {
					$avatar_files[] = $avatar_file;
				}
			}

			// Check for array
			if ( 0 < count( $avatar_files ) ) {

				// Check for current avatar
				foreach( $avatar_files as $key => $value ) {
					if ( strpos ( $value, $avatar_size )!== false ) {
						$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
					}
				}

				// Legacy avatar check
				if ( !isset( $avatar_url ) ) {
					foreach( $avatar_files as $key => $value ) {
						if ( strpos ( $value, $legacy_user_avatar_name )!== false ) {
							$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
						}
					}

					// Legacy group avatar check
					if ( !isset( $avatar_url ) ) {
						foreach( $avatar_files as $key => $value ) {
							if ( strpos ( $value, $legacy_group_avatar_name )!== false ) {
								$avatar_url = $avatar_folder_url . '/' . $avatar_files[$key];
							}
						}
					}
				}
			}
		}

		// Close the avatar directory
		closedir( $av_dir );

		// If we found a locally uploaded avatar
		if ( isset( $avatar_url ) ) {

			// Return it wrapped in an <img> element
			if ( true === $html ) {
				return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $avatar_url . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '"' . $css_id . $html_width . $html_height . $title . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

			// ...or only the URL
			} else {
				return apply_filters( 'bp_core_fetch_avatar_url', $avatar_url );
			}
		}
	}

	// If no avatars could be found, try to display a gravatar

	// Skips gravatar check if $no_grav is passed
	if ( ! apply_filters( 'bp_core_fetch_avatar_no_grav', $no_grav ) ) {

		// Set gravatar size
		if ( false !== $width ) {
			$grav_size = $width;
		} else if ( 'full' == $type ) {
			$grav_size = bp_core_avatar_full_width();
		} else if ( 'thumb' == $type ) {
			$grav_size = bp_core_avatar_thumb_width();
		}

		// Set gravatar type
		if ( empty( $bp->grav_default->{$object} ) ) {
			$default_grav = 'wavatar';
		} else if ( 'mystery' == $bp->grav_default->{$object} ) {
			$default_grav = apply_filters( 'bp_core_mysteryman_src', bp_core_avatar_default(), $grav_size );
		} else {
			$default_grav = $bp->grav_default->{$object};
		}

		// Set gravatar object
		if ( empty( $email ) ) {
			if ( 'user' == $object ) {
				$email = bp_core_get_user_email( $item_id );
			} else if ( 'group' == $object || 'blog' == $object ) {
				$email = "{$item_id}-{$object}@{bp_get_root_domain()}";
			}
		}

		// Set host based on if using ssl
		$host = 'http://www.gravatar.com/avatar/';
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com/avatar/';
		}

		// Filter gravatar vars
		$email    = apply_filters( 'bp_core_gravatar_email', $email, $item_id, $object );
		$gravatar = apply_filters( 'bp_gravatar_url', $host ) . md5( strtolower( $email ) ) . '?d=' . $default_grav . '&amp;s=' . $grav_size;

		// Gravatar rating; http://bit.ly/89QxZA
		$rating = get_option( 'avatar_rating' );
		if ( ! empty( $rating ) )
			$gravatar .= "&amp;r={$rating}";

	// No avatar was found, and we've been told not to use a gravatar.
	} else {
		$gravatar = apply_filters( "bp_core_default_avatar_$object", BP_PLUGIN_URL . 'bp-core/images/mystery-man.jpg', $params );
	}

	if ( true === $html )
		return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $gravatar . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '"' . $css_id . $html_width . $html_height . $title . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );
	else
		return apply_filters( 'bp_core_fetch_avatar_url', $gravatar );
}

/**
 * Delete an existing avatar
 *
 * Accepted values for $args are:
 *  item_id - item id which relates to the object type.
 *  object - the objetc type user, group, blog, etc.
 *  avatar_dir - The directory where the avatars to be uploaded.
 *
 * @global object $bp BuddyPress global settings
 * @param mixed $args
 * @return bool Success/failure
 */
function bp_core_delete_existing_avatar( $args = '' ) {
	global $bp;

	$defaults = array(
		'item_id'    => false,
		'object'     => 'user', // user OR group OR blog OR custom type (if you use filters)
		'avatar_dir' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	if ( empty( $item_id ) ) {
		if ( 'user' == $object )
			$item_id = bp_displayed_user_id();
		else if ( 'group' == $object )
			$item_id = $bp->groups->current_group->id;
		else if ( 'blog' == $object )
			$item_id = $current_blog->id;

		$item_id = apply_filters( 'bp_core_avatar_item_id', $item_id, $object );

		if ( !$item_id ) return false;
	}

	if ( empty( $avatar_dir ) ) {
		if ( 'user' == $object )
			$avatar_dir = 'avatars';
		else if ( 'group' == $object )
			$avatar_dir = 'group-avatars';
		else if ( 'blog' == $object )
			$avatar_dir = 'blog-avatars';

		$avatar_dir = apply_filters( 'bp_core_avatar_dir', $avatar_dir, $object );

		if ( !$avatar_dir ) return false;
	}

	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', bp_core_avatar_upload_path() . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

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

/**
 * Handles avatar uploading.
 *
 * The functions starts off by checking that the file has been uploaded properly using bp_core_check_avatar_upload().
 * It then checks that the file size is within limits, and that it has an accepted file extension (jpg, gif, png).
 * If everything checks out, crop the image and move it to its real location.
 *
 * @global object $bp BuddyPress global settings
 * @param array $file The appropriate entry the from $_FILES superglobal.
 * @param string $upload_dir_filter A filter to be applied to upload_dir
 * @return bool Success/failure
 * @see bp_core_check_avatar_upload()
 * @see bp_core_check_avatar_type()
 */
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
		1 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format( bp_core_avatar_original_max_filesize() ),
		2 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format( bp_core_avatar_original_max_filesize() ),
		3 => __("The uploaded file was only partially uploaded", 'buddypress'),
		4 => __("No file was uploaded", 'buddypress'),
		6 => __("Missing a temporary folder", 'buddypress')
	);

	if ( !bp_core_check_avatar_upload( $file ) ) {
		bp_core_add_message( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'buddypress' ), $uploadErrors[$file['file']['error']] ), 'error' );
		return false;
	}

	if ( !bp_core_check_avatar_size( $file ) ) {
		bp_core_add_message( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'buddypress'), size_format( bp_core_avatar_original_max_filesize() ) ), 'error' );
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

	// Get image size
	$size = @getimagesize( $bp->avatar_admin->original['file'] );

	// Check image size and shrink if too large
	if ( $size[0] > bp_core_avatar_original_max_width() ) {
		$thumb = wp_create_thumbnail( $bp->avatar_admin->original['file'], bp_core_avatar_original_max_width() );

		// Check for thumbnail creation errors
		if ( is_wp_error( $thumb ) ) {
			bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $thumb->get_error_message() ), 'error' );
			return false;
		}

		// Thumbnail is good so proceed
		$bp->avatar_admin->resized = $thumb;
	}

	// We only want to handle one image after resize.
	if ( empty( $bp->avatar_admin->resized ) )
		$bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->original['file'] );
	else {
		$bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->resized );
		@unlink( $bp->avatar_admin->original['file'] );
	}

	// Check for WP_Error on what should be an image
	if ( is_wp_error( $bp->avatar_admin->image->dir ) ) {
		bp_core_add_message( sprintf( __( 'Upload failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->image->dir->get_error_message() ), 'error' );
		return false;
	}

	// Set the url value for the image
	$bp->avatar_admin->image->url = bp_core_avatar_url() . $bp->avatar_admin->image->dir;

	return true;
}

/**
 * Crop an uploaded avatar
 *
 * $args has the following parameters:
 *  object - What component the avatar is for, e.g. "user"
 *  avatar_dir  The absolute path to the avatar
 *  item_id - Item ID
 *  original_file - The absolute path to the original avatar file
 *  crop_w - Crop width
 *  crop_h - Crop height
 *  crop_x - The horizontal starting point of the crop
 *  crop_y - The vertical starting point of the crop
 *
 * @param mixed $args
 * @return bool Success/failure
 */
function bp_core_avatar_handle_crop( $args = '' ) {

	$defaults = array(
		'object'        => 'user',
		'avatar_dir'    => 'avatars',
		'item_id'       => false,
		'original_file' => false,
		'crop_w'        => bp_core_avatar_full_width(),
		'crop_h'        => bp_core_avatar_full_height(),
		'crop_x'        => 0,
		'crop_y'        => 0
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

	$original_file = bp_core_avatar_upload_path() . $original_file;

	if ( !file_exists( $original_file ) )
		return false;

	if ( !$item_id )
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $original_file ), $item_id, $object, $avatar_dir );
	else
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', bp_core_avatar_upload_path() . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

	if ( !file_exists( $avatar_folder_dir ) )
		return false;

	require_once( ABSPATH . '/wp-admin/includes/image.php' );
	require_once( ABSPATH . '/wp-admin/includes/file.php' );

	// Delete the existing avatar files for the object
	bp_core_delete_existing_avatar( array( 'object' => $object, 'avatar_path' => $avatar_folder_dir ) );

	// Make sure we at least have a width and height for cropping
	if ( !(int) $crop_w )
		$crop_w = bp_core_avatar_full_width();

	if ( !(int) $crop_h )
		$crop_h = bp_core_avatar_full_height();

	// Set the full and thumb filenames
	$full_filename  = wp_hash( $original_file . time() ) . '-bpfull.jpg';
	$thumb_filename = wp_hash( $original_file . time() ) . '-bpthumb.jpg';

	// Crop the image
	$full_cropped  = wp_crop_image( $original_file, (int) $crop_x, (int) $crop_y, (int) $crop_w, (int) $crop_h, bp_core_avatar_full_width(), bp_core_avatar_full_height(), false, $avatar_folder_dir . '/' . $full_filename );
	$thumb_cropped = wp_crop_image( $original_file, (int) $crop_x, (int) $crop_y, (int) $crop_w, (int) $crop_h, bp_core_avatar_thumb_width(), bp_core_avatar_thumb_height(), false, $avatar_folder_dir . '/' . $thumb_filename );

	// Check for errors
	if ( ! $full_cropped || ! $thumb_cropped || is_wp_error( $full_cropped ) || is_wp_error( $thumb_cropped ) )
		return false;

	// Remove the original
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
function bp_core_fetch_avatar_filter( $avatar, $user, $size, $default, $alt = '' ) {
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
	else if ( is_string( $user ) && ( $user_by_email = get_user_by( 'email', $user ) ) )
		$id = $user_by_email->ID;

	// If somehow $id hasn't been assigned, return the result of get_avatar
	if ( empty( $id ) )
		return !empty( $avatar ) ? $avatar : $default;

	if ( !$alt )
		$alt = sprintf( __( 'Avatar of %s', 'buddypress' ), bp_core_get_user_displayname( $id ) );

	// Let BuddyPress handle the fetching of the avatar
	$bp_avatar = bp_core_fetch_avatar( array( 'item_id' => $id, 'width' => $size, 'height' => $size, 'alt' => $alt ) );

	// If BuddyPress found an avatar, use it. If not, use the result of get_avatar
	return ( !$bp_avatar ) ? $avatar : $bp_avatar;
}
add_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10, 5 );

/**
 * Has the current avatar upload generated an error?
 *
 * @param array $file
 * @return bool
 */
function bp_core_check_avatar_upload( $file ) {
	if ( isset( $file['error'] ) && $file['error'] )
		return false;

	return true;
}

/**
 * Is the file size of the current avatar upload permitted?
 *
 * @param array $file
 * @return bool
 */
function bp_core_check_avatar_size( $file ) {
	if ( $file['file']['size'] > bp_core_avatar_original_max_filesize() )
		return false;

	return true;
}

/**
 * Does the current avatar upload have an allowed file type?
 *
 * Permitted file types are JPG, GIF and PNG.
 *
 * @param string $file
 * @return bool
 */
function bp_core_check_avatar_type($file) {
	if ( ( !empty( $file['file']['type'] ) && !preg_match('/(jpe?g|gif|png)$/i', $file['file']['type'] ) ) || !preg_match( '/(jpe?g|gif|png)$/i', $file['file']['name'] ) )
		return false;

	return true;
}

/**
 * bp_core_avatar_upload_path()
 *
 * Returns the absolute upload path for the WP installation
 *
 * @uses wp_upload_dir To get upload directory info
 * @return string Absolute path to WP upload directory
 */
function bp_core_avatar_upload_path() {
	global $bp;

	// See if the value has already been calculated and stashed in the $bp global
	if ( isset( $bp->avatar->upload_path ) ) {
		$basedir = $bp->avatar->upload_path;
	} else {
		// If this value has been set in a constant, just use that
		if ( defined( 'BP_AVATAR_UPLOAD_PATH' ) ) {
			$basedir = BP_AVATAR_UPLOAD_PATH;
		} else {
			if ( !bp_is_root_blog() ) {
				// Switch dynamically in order to support BP_ENABLE_MULTIBLOG
				switch_to_blog( bp_get_root_blog_id() );
			}

			// Get upload directory information from current site
			$upload_dir = wp_upload_dir();

			// Directory does not exist and cannot be created
			if ( !empty( $upload_dir['error'] ) ) {
				$basedir = '';

			} else {
				$basedir = $upload_dir['basedir'];
			}

			// Will bail if not switched
			restore_current_blog();
		}

		// Stash in $bp for later use
		$bp->avatar->upload_path = $basedir;
	}

	return apply_filters( 'bp_core_avatar_upload_path', $basedir );
}

/**
 * bp_core_avatar_url()
 *
 * Returns the raw base URL for root site upload location
 *
 * @uses wp_upload_dir To get upload directory info
 * @return string Full URL to current upload location
 */
function bp_core_avatar_url() {
	global $bp;

	// See if the value has already been calculated and stashed in the $bp global
	if ( isset( $bp->avatar->url ) ) {
		$baseurl = $bp->avatar->url;

	} else {
		// If this value has been set in a constant, just use that
		if ( defined( 'BP_AVATAR_URL' ) ) {
			$baseurl = BP_AVATAR_URL;
		} else {
			// Get upload directory information from current site
			$upload_dir = wp_upload_dir();

			// Directory does not exist and cannot be created
			if ( !empty( $upload_dir['error'] ) ) {
				$baseurl = '';

			} else {
				$baseurl = $upload_dir['baseurl'];

				// If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
				if ( is_ssl() )
					$baseurl = str_replace( 'http://', 'https://', $baseurl );

				// If multisite, and current blog does not match root blog, make adjustments
				if ( is_multisite() && bp_get_root_blog_id() != get_current_blog_id() )
					$baseurl = trailingslashit( get_blog_option( bp_get_root_blog_id(), 'home' ) ) . get_blog_option( bp_get_root_blog_id(), 'upload_path' );
			}
		}

		// Stash in $bp for later use
		$bp->avatar->url = $baseurl;
	}

	return apply_filters( 'bp_core_avatar_url', $baseurl );
}

/**
 * Check if a given user ID has an uploaded avatar
 *
 * @since BuddyPress (1.0)
 * @param int $user_id
 * @return boolean
 */
function bp_get_user_has_avatar( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	$retval = false;
	if ( bp_core_fetch_avatar( array( 'item_id' => $user_id, 'no_grav' => true, 'html' => false ) ) != bp_core_avatar_default() )
		$retval = true;

	return (bool) apply_filters( 'bp_get_user_has_avatar', $retval, $user_id );
}

/**
 * Utility function for fetching an avatar dimension setting
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param str $type 'thumb' for thumbs, otherwise full
 * @param str $h_or_w 'height' for height, otherwise width
 * @return int $dim The dimension
 */
function bp_core_avatar_dimension( $type = 'thumb', $h_or_w = 'height' ) {
	global $bp;

	$dim = isset( $bp->avatar->{$type}->{$h_or_w} ) ? (int) $bp->avatar->{$type}->{$h_or_w} : false;

	return apply_filters( 'bp_core_avatar_dimension', $dim, $type, $h_or_w );
}

/**
 * Get the avatar thumb width setting
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The thumb width
 */
function bp_core_avatar_thumb_width() {
	return apply_filters( 'bp_core_avatar_thumb_width', bp_core_avatar_dimension( 'thumb', 'width' ) );
}

/**
 * Get the avatar thumb height setting
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The thumb height
 */
function bp_core_avatar_thumb_height() {
	return apply_filters( 'bp_core_avatar_thumb_height', bp_core_avatar_dimension( 'thumb', 'height' ) );
}

/**
 * Get the avatar full width setting
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The full width
 */
function bp_core_avatar_full_width() {
	return apply_filters( 'bp_core_avatar_full_width', bp_core_avatar_dimension( 'full', 'width' ) );
}

/**
 * Get the avatar full height setting
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The full height
 */
function bp_core_avatar_full_height() {
	return apply_filters( 'bp_core_avatar_full_height', bp_core_avatar_dimension( 'full', 'height' ) );
}

/**
 * Get the max width for original avatar uploads
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The width
 */
function bp_core_avatar_original_max_width() {
	global $bp;

	return apply_filters( 'bp_core_avatar_original_max_width', (int) $bp->avatar->original_max_width );
}

/**
 * Get the max filesize for original avatar uploads
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The filesize
 */
function bp_core_avatar_original_max_filesize() {
	global $bp;

	return apply_filters( 'bp_core_avatar_original_max_filesize', (int) $bp->avatar->original_max_filesize );
}

/**
 * Get the default avatar
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The URL of the default avatar
 */
function bp_core_avatar_default() {
	global $bp;

	return apply_filters( 'bp_core_avatar_default', $bp->avatar->full->default );
}

/**
 * Get the default avatar thumb
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int The URL of the default avatar thumb
 */
function bp_core_avatar_default_thumb() {
	global $bp;

	return apply_filters( 'bp_core_avatar_thumb', $bp->avatar->thumb->default );
}


?>