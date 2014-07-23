<?php

/**
 * BuddyPress Avatars.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/***
 * Set up the constants we need for avatar support.
 */
function bp_core_set_avatar_constants() {

	$bp = buddypress();

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

	if ( ! defined( 'BP_SHOW_AVATARS' ) ) {
		define( 'BP_SHOW_AVATARS', bp_get_option( 'show_avatars' ) );
	}
}
add_action( 'bp_init', 'bp_core_set_avatar_constants', 3 );

/**
 * Set up global variables related to avatars.
 *
 * @since BuddyPress (1.5.0)
 */
function bp_core_set_avatar_globals() {
	$bp = buddypress();

	$bp->avatar        = new stdClass;
	$bp->avatar->thumb = new stdClass;
	$bp->avatar->full  = new stdClass;

	// Dimensions
	$bp->avatar->thumb->width  = BP_AVATAR_THUMB_WIDTH;
	$bp->avatar->thumb->height = BP_AVATAR_THUMB_HEIGHT;
	$bp->avatar->full->width   = BP_AVATAR_FULL_WIDTH;
	$bp->avatar->full->height  = BP_AVATAR_FULL_HEIGHT;

	// Upload maximums
	$bp->avatar->original_max_width    = BP_AVATAR_ORIGINAL_MAX_WIDTH;
	$bp->avatar->original_max_filesize = BP_AVATAR_ORIGINAL_MAX_FILESIZE;

	// Defaults
	$bp->avatar->thumb->default = bp_core_avatar_default_thumb();
	$bp->avatar->full->default  = bp_core_avatar_default();

	// These have to be set on page load in order to avoid infinite filter loops at runtime
	$bp->avatar->upload_path = bp_core_avatar_upload_path();
	$bp->avatar->url = bp_core_avatar_url();

	// Cache the root blog's show_avatars setting, to avoid unnecessary
	// calls to switch_to_blog()
	$bp->avatar->show_avatars = (bool) BP_SHOW_AVATARS;

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
 * Get an avatar for a BuddyPress object.
 *
 * Supports avatars for users, groups, and blogs by default, but can be
 * extended to support custom components as well.
 *
 * This function gives precedence to locally-uploaded avatars. When a local
 * avatar is not found, Gravatar is queried. To disable Gravatar fallbacks
 * locally:
 *    add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
 *
 * @param array $args {
 *     An array of arguments. All arguments are technically optional; some
 *     will, if not provided, be auto-detected by bp_core_fetch_avatar(). This
 *     auto-detection is described more below, when discussing specific
 *     arguments.
 *
 *     @type int|bool $item_id The numeric ID of the item for which you're
 *           requesting an avatar (eg, a user ID). If no 'item_id' is present,
 *           the function attempts to infer an ID from the 'object' + the
 *           current context: if 'object' is 'user' and the current page is a
 *           user page, 'item_id' will default to the displayed user ID; if
 *           'group' and on a group page, to the current group ID; if 'blog',
 *           to the current blog's ID. If no 'item_id' can be determined in
 *           this way, the function returns false. Default: false.
 *     @type string $object The kind of object for which you're getting an
 *           avatar. BuddyPress natively supports three options: 'user',
 *           'group', 'blog'; a plugin may register more.  Default: 'user'.
 *     @type string $type When a new avatar is uploaded to BP, 'thumb' and
 *           'full' versions are saved. This parameter specifies whether you'd
 *           like the 'full' or smaller 'thumb' avatar. Default: 'thumb'.
 *     @type string|bool $avatar_dir The name of the subdirectory where the
 *           requested avatar should be found. If no value is passed,
 *           'avatar_dir' is inferred from 'object': 'user' becomes 'avatars',
 *           'group' becomes 'group-avatars', 'blog' becomes 'blog-avatars'.
 *           Remember that this string denotes a subdirectory of BP's main
 *           avatar directory (usually based on {@link wp_upload_dir()}); it's a
 *           string like 'group-avatars' rather than the full directory path.
 *           Generally, it'll only be necessary to override the default value if
 *           storing avatars in a non-default location. Defaults to false
 *           (auto-detected).
 *     @type int|bool $width Requested avatar width. The unit is px. This value
 *           is used to build the 'width' attribute for the <img> element. If
 *           no value is passed, BP uses the global avatar width for this
 *           avatar type. Default: false (auto-detected).
 *     @type int|bool $height Requested avatar height. The unit is px. This
 *           value is used to build the 'height' attribute for the <img>
 *           element. If no value is passed, BP uses the global avatar height
 *           for this avatar type. Default: false (auto-detected).
 *     @type string $class The CSS class for the <img> element. Note that BP
 *           uses the 'avatar' class fairly extensively in its default styling,
 *           so if you plan to pass a custom value, consider appending it to
 *           'avatar' (eg 'avatar foo') rather than replacing it altogether.
 *           Default: 'avatar'.
 *     @type string|bool $css_id The CSS id for the <img> element.
 *           Default: false.
 *     @type string $title The title attribute for the <img> element.
 *           Default: false.
 *     @type string $alt The alt attribute for the <img> element. In BP, this
 *           value is generally passed by the wrapper functions, where the data
 *           necessary for concatenating the string is at hand; see
 *           {@link bp_get_activity_avatar()} for an example. Default: ''.
 *     @type string|bool $email An email to use in Gravatar queries. Unless
 *           otherwise configured, BP uses Gravatar as a fallback for avatars
 *           that are not provided locally. Gravatar's API requires using a hash
 *           of the user's email address; this argument provides it. If not
 *           provided, the function will infer it: for users, by getting the
 *           user's email from the database, for groups/blogs, by concatenating
 *           "{$item_id}-{$object}@{bp_get_root_domain()}". The user query adds
 *           overhead, so it's recommended that wrapper functions provide a
 *           value for 'email' when querying user IDs. Default: false.
 *     @type bool $no_grav Whether to disable the default Gravatar fallback.
 *           By default, BP will fall back on Gravatar when it cannot find a
 *           local avatar. In some cases, this may be undesirable, in which
 *           case 'no_grav' should be set to true. To disable Gravatar
 *           fallbacks globally, see the 'bp_core_fetch_avatar_no_grav' filter.
 *           Default: false.
 *     @type bool $html Whether to return an <img> HTML element, vs a raw URL
 *           to an avatar. If false, <img>-specific arguments (like 'css_id')
 *           will be ignored. Default: true.
 * }
 * @return string Formatted HTML <img> element, or raw avatar URL based on $html arg.
 */
function bp_core_fetch_avatar( $args = '' ) {

	// If avatars are disabled for the root site, obey that request and bail
	if ( ! buddypress()->avatar->show_avatars )
		return;

	global $current_blog;

	$bp = buddypress();

	// Set a few default variables
	$def_object = 'user';
	$def_type   = 'thumb';
	$def_class  = 'avatar';

	// Set the default variables array
	$params = wp_parse_args( $args, array(
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
	) );
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
		$alt = __( 'Profile Photo', 'buddypress' );

	$html_alt = ' alt="' . esc_attr( $alt ) . '"';

	// Set title tag, if it's been provided
	if ( !empty( $title ) ) {
		$title = " title='" . esc_attr( apply_filters( 'bp_core_avatar_title', $title, $item_id, $object, $params ) ) . "'";
	}

	// Set CSS ID if passed
	if ( !empty( $css_id ) ) {
		$css_id = ' id="' . esc_attr( $css_id ) . '"';
	}

	// Set image width
	if ( false !== $width ) {
		$html_width = ' width="' . $width . '"';
	} elseif ( 'thumb' == $type ) {
		$html_width = ' width="' . bp_core_avatar_thumb_width() . '"';
	} else {
		$html_width = ' width="' . bp_core_avatar_full_width() . '"';
	}

	// Set image height
	if ( false !== $height ) {
		$html_height = ' height="' . $height . '"';
	} elseif ( 'thumb' == $type ) {
		$html_height = ' height="' . bp_core_avatar_thumb_height() . '"';
	} else {
		$html_height = ' height="' . bp_core_avatar_full_height() . '"';
	}

	// Set img URL and DIR based on prepopulated constants
	$avatar_loc        = new stdClass();
	$avatar_loc->path  = trailingslashit( bp_core_avatar_upload_path() );
	$avatar_loc->url   = trailingslashit( bp_core_avatar_url() );

	$avatar_loc->dir   = trailingslashit( $avatar_dir );
	$avatar_folder_url = apply_filters( 'bp_core_avatar_folder_url', ( $avatar_loc->url  . $avatar_loc->dir . $item_id ), $item_id, $object, $avatar_dir );
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', ( $avatar_loc->path . $avatar_loc->dir . $item_id ), $item_id, $object, $avatar_dir );

	// Add an identifying class
	$class .= ' ' . $object . '-' . $item_id . '-avatar ' . sanitize_html_class( "avatar-$width" ) . ' photo';

	/**
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
				return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $avatar_url . '" class="' . esc_attr( $class ) . '"' . $css_id . $html_width . $html_height . $html_alt . $title . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

			// ...or only the URL
			} else {
				return apply_filters( 'bp_core_fetch_avatar_url', $avatar_url, $params );
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
			$default_grav = apply_filters( 'bp_core_mysteryman_src', 'mm', $grav_size );
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
		$host = 'http://gravatar.com/avatar/';
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com/avatar/';
		}

		// Filter gravatar vars
		$email    = apply_filters( 'bp_core_gravatar_email', $email, $item_id, $object );
		$gravatar = apply_filters( 'bp_gravatar_url', $host ) . md5( strtolower( $email ) ) . '?d=' . $default_grav . '&amp;s=' . $grav_size;

		// Gravatar rating; http://bit.ly/89QxZA
		$rating = get_option( 'avatar_rating' );
		if ( ! empty( $rating ) ) {
			$gravatar .= "&amp;r={$rating}";
		}

	// No avatar was found, and we've been told not to use a gravatar.
	} else {
		$gravatar = apply_filters( "bp_core_default_avatar_$object", bp_core_avatar_default( 'local' ), $params );
	}

	if ( true === $html ) {
		return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $gravatar . '" class="' . esc_attr( $class ) . '"' . $css_id . $html_width . $html_height . $html_alt . $title . ' />', $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );
	} else {
		return apply_filters( 'bp_core_fetch_avatar_url', $gravatar, $params );
	}
}

/**
 * Delete an existing avatar.
 *
 * @param array $args {
 *     Array of function parameters.
 *     @type bool|int $item_id ID of the item whose avatar you're deleting.
 *           Defaults to the current item of type $object.
 *     @type string $object Object type of the item whose avatar you're
 *           deleting. 'user', 'group', 'blog', or custom. Default: 'user'.
 *     @type bool|string $avatar_dir Subdirectory where avatar is located.
 *           Default: false, which falls back on the default location
 *           corresponding to the $object.
 * }
 * @return bool True on success, false on failure.
 */
function bp_core_delete_existing_avatar( $args = '' ) {

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
			$item_id = buddypress()->groups->current_group->id;
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
 * Handle avatar uploading.
 *
 * The functions starts off by checking that the file has been uploaded
 * properly using bp_core_check_avatar_upload(). It then checks that the file
 * size is within limits, and that it has an accepted file extension (jpg, gif,
 * png). If everything checks out, crop the image and move it to its real
 * location.
 *
 * @see bp_core_check_avatar_upload()
 * @see bp_core_check_avatar_type()
 *
 * @param array $file The appropriate entry the from $_FILES superglobal.
 * @param string $upload_dir_filter A filter to be applied to 'upload_dir'.
 * @return bool True on success, false on failure.
 */
function bp_core_avatar_handle_upload( $file, $upload_dir_filter ) {

	/***
	 * You may want to hook into this filter if you want to override this function.
	 * Make sure you return false.
	 */
	if ( !apply_filters( 'bp_core_pre_avatar_handle_upload', true, $file, $upload_dir_filter ) )
		return true;

	require_once( ABSPATH . '/wp-admin/includes/file.php' );

	$uploadErrors = array(
		0 => __( 'The image was uploaded successfully', 'buddypress' ),
		1 => __( 'The image exceeds the maximum allowed file size of: ', 'buddypress' ) . size_format( bp_core_avatar_original_max_filesize() ),
		2 => __( 'The image exceeds the maximum allowed file size of: ', 'buddypress' ) . size_format( bp_core_avatar_original_max_filesize() ),
		3 => __( 'The uploaded file was only partially uploaded.', 'buddypress' ),
		4 => __( 'The image was not uploaded.', 'buddypress' ),
		6 => __( 'Missing a temporary folder.', 'buddypress' )
	);

	if ( ! bp_core_check_avatar_upload( $file ) ) {
		bp_core_add_message( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'buddypress' ), $uploadErrors[$file['file']['error']] ), 'error' );
		return false;
	}

	if ( ! bp_core_check_avatar_size( $file ) ) {
		bp_core_add_message( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'buddypress' ), size_format( bp_core_avatar_original_max_filesize() ) ), 'error' );
		return false;
	}

	if ( ! bp_core_check_avatar_type( $file ) ) {
		bp_core_add_message( __( 'Please upload only JPG, GIF or PNG photos.', 'buddypress' ), 'error' );
		return false;
	}

	// Filter the upload location
	add_filter( 'upload_dir', $upload_dir_filter, 10, 0 );

	$bp = buddypress();

	$bp->avatar_admin->original = wp_handle_upload( $file['file'], array( 'action'=> 'bp_avatar_upload' ) );

	// Remove the upload_dir filter, so that other upload URLs on the page
	// don't break
	remove_filter( 'upload_dir', $upload_dir_filter, 10, 0 );

	// Move the file to the correct upload location.
	if ( !empty( $bp->avatar_admin->original['error'] ) ) {
		bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->original['error'] ), 'error' );
		return false;
	}

	// Get image size
	$size  = @getimagesize( $bp->avatar_admin->original['file'] );
	$error = false;

	// Check image size and shrink if too large
	if ( $size[0] > bp_core_avatar_original_max_width() ) {
		$editor = wp_get_image_editor( $bp->avatar_admin->original['file'] );

		if ( ! is_wp_error( $editor ) ) {
			$editor->set_quality( 100 );

			$resized = $editor->resize( bp_core_avatar_original_max_width(), bp_core_avatar_original_max_width(), false );
			if ( ! is_wp_error( $resized ) ) {
				$thumb = $editor->save( $editor->generate_filename() );
			} else {
				$error = $resized;
			}

			// Check for thumbnail creation errors
			if ( false === $error && is_wp_error( $thumb ) ) {
				$error = $thumb;
			}

			// Thumbnail is good so proceed
			if ( false === $error ) {
				$bp->avatar_admin->resized = $thumb;
			}

		} else {
			$error = $editor;
		}

		if ( false !== $error ) {
			bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $error->get_error_message() ), 'error' );
			return false;
		}
	}

	if ( ! isset( $bp->avatar_admin->image ) )
		$bp->avatar_admin->image = new stdClass();

	// We only want to handle one image after resize.
	if ( empty( $bp->avatar_admin->resized ) ) {
		$bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->original['file'] );
	} else {
		$bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->resized['path'] );
		@unlink( $bp->avatar_admin->original['file'] );
	}

	// Check for WP_Error on what should be an image
	if ( is_wp_error( $bp->avatar_admin->image->dir ) ) {
		bp_core_add_message( sprintf( __( 'Upload failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->image->dir->get_error_message() ), 'error' );
		return false;
	}

	// If the uploaded image is smaller than the "full" dimensions, throw
	// a warning
	$uploaded_image = @getimagesize( bp_core_avatar_upload_path() . buddypress()->avatar_admin->image->dir );
	$full_width     = bp_core_avatar_full_width();
	$full_height    = bp_core_avatar_full_height();
	if ( isset( $uploaded_image[0] ) && $uploaded_image[0] < $full_width || $uploaded_image[1] < $full_height ) {
		bp_core_add_message( sprintf( __( 'You have selected an image that is smaller than recommended. For best results, upload a picture larger than %d x %d pixels.', 'buddypress' ), $full_width, $full_height ), 'error' );
	}

	// Set the url value for the image
	$bp->avatar_admin->image->url = bp_core_avatar_url() . $bp->avatar_admin->image->dir;

	return true;
}

/**
 * Crop an uploaded avatar.
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
 * @param array $args {
 *     Array of function parameters.
 *     @type string $object Object type of the item whose avatar you're
 *           handling. 'user', 'group', 'blog', or custom. Default: 'user'.
 *     @type string $avatar_dir Subdirectory where avatar should be stored.
 *           Default: 'avatars'.
 *     @type bool|int $item_id ID of the item that the avatar belongs to.
 *     @type bool|string $original_file Absolute papth to the original avatar
 *           file.
 *     @type int $crop_w Crop width. Default: the global 'full' avatar width,
 *           as retrieved by bp_core_avatar_full_width().
 *     @type int $crop_h Crop height. Default: the global 'full' avatar height,
 *           as retrieved by bp_core_avatar_full_height().
 *     @type int $crop_x The horizontal starting point of the crop. Default: 0.
 *     @type int $crop_y The vertical starting point of the crop. Default: 0.
 * }
 * @return bool True on success, false on failure.
 */
function bp_core_avatar_handle_crop( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'object'        => 'user',
		'avatar_dir'    => 'avatars',
		'item_id'       => false,
		'original_file' => false,
		'crop_w'        => bp_core_avatar_full_width(),
		'crop_h'        => bp_core_avatar_full_height(),
		'crop_x'        => 0,
		'crop_y'        => 0
	) );

	/***
	 * You may want to hook into this filter if you want to override this function.
	 * Make sure you return false.
	 */
	if ( !apply_filters( 'bp_core_pre_avatar_handle_crop', true, $r ) )
		return true;

	extract( $r, EXTR_SKIP );

	if ( empty( $original_file ) )
		return false;

	$original_file = bp_core_avatar_upload_path() . $original_file;

	if ( !file_exists( $original_file ) )
		return false;

	if ( empty( $item_id ) ) {
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $original_file ), $item_id, $object, $avatar_dir );
	} else {
		$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', bp_core_avatar_upload_path() . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );
	}

	if ( !file_exists( $avatar_folder_dir ) )
		return false;

	require_once( ABSPATH . '/wp-admin/includes/image.php' );
	require_once( ABSPATH . '/wp-admin/includes/file.php' );

	// Delete the existing avatar files for the object
	$existing_avatar = bp_core_fetch_avatar( array(
		'object'  => $object,
		'item_id' => $item_id,
		'html' => false,
	) );

	if ( ! empty( $existing_avatar ) ) {
		// Check that the new avatar doesn't have the same name as the
		// old one before deleting
		$upload_dir           = wp_upload_dir();
		$existing_avatar_path = str_replace( $upload_dir['baseurl'], '', $existing_avatar );
		$new_avatar_path      = str_replace( $upload_dir['basedir'], '', $original_file );

		if ( $existing_avatar_path !== $new_avatar_path ) {
			bp_core_delete_existing_avatar( array( 'object' => $object, 'item_id' => $item_id, 'avatar_path' => $avatar_folder_dir ) );
		}
	}



	// Make sure we at least have a width and height for cropping
	if ( empty( $crop_w ) ) {
		$crop_w = bp_core_avatar_full_width();
	}

	if ( empty( $crop_h ) ) {
		$crop_h = bp_core_avatar_full_height();
	}

	// Get the file extension
	$data = @getimagesize( $original_file );
	$ext  = $data['mime'] == 'image/png' ? 'png' : 'jpg';

	// Set the full and thumb filenames
	$full_filename  = wp_hash( $original_file . time() ) . '-bpfull.'  . $ext;
	$thumb_filename = wp_hash( $original_file . time() ) . '-bpthumb.' . $ext;

	// Crop the image
	$full_cropped  = wp_crop_image( $original_file, (int) $crop_x, (int) $crop_y, (int) $crop_w, (int) $crop_h, bp_core_avatar_full_width(),  bp_core_avatar_full_height(),  false, $avatar_folder_dir . '/' . $full_filename  );
	$thumb_cropped = wp_crop_image( $original_file, (int) $crop_x, (int) $crop_y, (int) $crop_w, (int) $crop_h, bp_core_avatar_thumb_width(), bp_core_avatar_thumb_height(), false, $avatar_folder_dir . '/' . $thumb_filename );

	// Check for errors
	if ( empty( $full_cropped ) || empty( $thumb_cropped ) || is_wp_error( $full_cropped ) || is_wp_error( $thumb_cropped ) )
		return false;

	// Remove the original
	@unlink( $original_file );

	return true;
}

/**
 * Replace default WordPress avatars with BP avatars, if available.
 *
 * Filters 'get_avatar'.
 *
 * @param string $avatar The avatar path passed to 'get_avatar'.
 * @param int|string|object $user A user ID, email address, or comment object.
 * @param int $size Size of the avatar image ('thumb' or 'full').
 * @param string $default URL to a default image to use if no avatar is available.
 * @param string $alt Alternate text to use in image tag. Default: ''.
 * @return string BP avatar path, if found; else the original avatar path.
 */
function bp_core_fetch_avatar_filter( $avatar, $user, $size, $default, $alt = '' ) {
	global $pagenow;

	// Do not filter if inside WordPress options page
	if ( 'options-discussion.php' == $pagenow )
		return $avatar;

	// If passed an object, assume $user->user_id
	if ( is_object( $user ) ) {
		if ( isset( $user->user_id ) ) {
			$id = $user->user_id;
		} else {
			$id = $user->ID;
		}

	// If passed a number, assume it was a $user_id
	} else if ( is_numeric( $user ) ) {
		$id = $user;

	// If passed a string and that string returns a user, get the $id
	} elseif ( is_string( $user ) && ( $user_by_email = get_user_by( 'email', $user ) ) ) {
		$id = $user_by_email->ID;
	}

	// If somehow $id hasn't been assigned, return the result of get_avatar
	if ( empty( $id ) ) {
		return !empty( $avatar ) ? $avatar : $default;
	}

	// Image alt tag
	if ( empty( $alt ) ) {
		$alt = sprintf( __( 'Profile photo of %s', 'buddypress' ), bp_core_get_user_displayname( $id ) );
	}

	// Use the 'thumb' type, unless the requested width is bigger than
	// BP's thumb width.
	$type = 'thumb';
	if ( (int) $size > bp_core_avatar_thumb_width() ) {
		$type = 'full';
	}

	// Let BuddyPress handle the fetching of the avatar
	$bp_avatar = bp_core_fetch_avatar( array(
		'item_id' => $id,
		'type'    => $type,
		'width'   => $size,
		'height'  => $size,
		'alt'     => $alt,
	) );

	// If BuddyPress found an avatar, use it. If not, use the result of get_avatar
	return ( !$bp_avatar ) ? $avatar : $bp_avatar;
}
add_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10, 5 );

/**
 * Is the current avatar upload error-free?
 *
 * @param array $file The $_FILES array.
 * @return bool True if no errors are found. False if there are errors.
 */
function bp_core_check_avatar_upload( $file ) {
	if ( isset( $file['error'] ) && $file['error'] )
		return false;

	return true;
}

/**
 * Is the file size of the current avatar upload permitted?
 *
 * @param array $file The $_FILES array.
 * @return bool True if the avatar is under the size limit, otherwise false.
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
 * @param array $file The $_FILES array.
 * @return bool True if the file extension is permitted, otherwise false.
 */
function bp_core_check_avatar_type($file) {
	if ( ( !empty( $file['file']['type'] ) && !preg_match('/(jpe?g|gif|png)$/i', $file['file']['type'] ) ) || !preg_match( '/(jpe?g|gif|png)$/i', $file['file']['name'] ) )
		return false;

	return true;
}

/**
 * Fetch data from the BP root blog's upload directory.
 *
 * Handy for multisite instances because all uploads are made on the BP root
 * blog and we need to query the BP root blog for the upload directory data.
 *
 * This function ensures that we only need to use {@link switch_to_blog()}
 * once to get what we need.
 *
 * @since BuddyPress (1.8.0)
 *
 * @uses wp_upload_dir()
 *
 * @param string $type The variable we want to return from the $bp->avatars
 *        object. Only 'upload_path' and 'url' are supported. Default: 'upload_path'.
 * @return string The avatar upload directory path.
 */
function bp_core_get_upload_dir( $type = 'upload_path' ) {
	$bp = buddypress();

	switch ( $type ) {
		case 'upload_path' :
			$constant = 'BP_AVATAR_UPLOAD_PATH';
			$key      = 'basedir';

			break;

		case 'url' :
			$constant = 'BP_AVATAR_URL';
			$key      = 'baseurl';

			break;

		default :
			return false;

			break;
	}

	// See if the value has already been calculated and stashed in the $bp global
	if ( isset( $bp->avatar->$type ) ) {
		$retval = $bp->avatar->$type;
	} else {
		// If this value has been set in a constant, just use that
		if ( defined( $constant ) ) {
			$retval = constant( $constant );
		} else {

			// Use cached upload dir data if available
			if ( ! empty( $bp->avatar->upload_dir ) ) {
				$upload_dir = $bp->avatar->upload_dir;

			// No cache, so query for it
			} else {
				// We need to switch to the root blog on multisite installs
				if ( is_multisite() ) {
					switch_to_blog( bp_get_root_blog_id() );
				}

				// Get upload directory information from current site
				$upload_dir = wp_upload_dir();

				// Will bail if not switched
				restore_current_blog();

				// Stash upload directory data for later use
				$bp->avatar->upload_dir = $upload_dir;
			}

			// Directory does not exist and cannot be created
			if ( ! empty( $upload_dir['error'] ) ) {
				$retval = '';

			} else {
				$retval = $upload_dir[$key];

				// If $key is 'baseurl', check to see if we're on SSL
				// Workaround for WP13941, WP15928, WP19037.
				if ( $key == 'baseurl' && is_ssl() ) {
					$retval = str_replace( 'http://', 'https://', $retval );
				}
			}

		}

		// Stash in $bp for later use
		$bp->avatar->$type = $retval;
	}

	return $retval;
}

/**
 * Get the absolute upload path for the WP installation.
 *
 * @uses wp_upload_dir To get upload directory info
 *
 * @return string Absolute path to WP upload directory.
 */
function bp_core_avatar_upload_path() {
	return apply_filters( 'bp_core_avatar_upload_path', bp_core_get_upload_dir() );
}

/**
 * Get the raw base URL for root site upload location.
 *
 * @uses wp_upload_dir To get upload directory info.
 *
 * @return string Full URL to current upload location.
 */
function bp_core_avatar_url() {
	return apply_filters( 'bp_core_avatar_url', bp_core_get_upload_dir( 'url' ) );
}

/**
 * Check if a given user ID has an uploaded avatar.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param int $user_id ID of the user whose avatar is being checked.
 * @return bool True if the user has uploaded a local avatar. Otherwise false.
 */
function bp_get_user_has_avatar( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	$retval = false;
	if ( bp_core_fetch_avatar( array( 'item_id' => $user_id, 'no_grav' => true, 'html' => false ) ) != bp_core_avatar_default( 'local' ) )
		$retval = true;

	return (bool) apply_filters( 'bp_get_user_has_avatar', $retval, $user_id );
}

/**
 * Utility function for fetching an avatar dimension setting.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param string $type Dimension type you're fetching dimensions for. 'thumb'
 *        or 'full'. Default: 'thumb'.
 * @param string $h_or_w Which dimension is being fetched. 'height' or 'width'.
 *        Default: 'height'.
 * @return int $dim The dimension.
 */
function bp_core_avatar_dimension( $type = 'thumb', $h_or_w = 'height' ) {
	$bp  = buddypress();
	$dim = isset( $bp->avatar->{$type}->{$h_or_w} ) ? (int) $bp->avatar->{$type}->{$h_or_w} : false;

	return apply_filters( 'bp_core_avatar_dimension', $dim, $type, $h_or_w );
}

/**
 * Get the 'thumb' avatar width setting.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The 'thumb' width.
 */
function bp_core_avatar_thumb_width() {
	return apply_filters( 'bp_core_avatar_thumb_width', bp_core_avatar_dimension( 'thumb', 'width' ) );
}

/**
 * Get the 'thumb' avatar height setting.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The 'thumb' height.
 */
function bp_core_avatar_thumb_height() {
	return apply_filters( 'bp_core_avatar_thumb_height', bp_core_avatar_dimension( 'thumb', 'height' ) );
}

/**
 * Get the 'full' avatar width setting
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The 'full' width.
 */
function bp_core_avatar_full_width() {
	return apply_filters( 'bp_core_avatar_full_width', bp_core_avatar_dimension( 'full', 'width' ) );
}

/**
 * Get the 'full' avatar height setting.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The 'full' height.
 */
function bp_core_avatar_full_height() {
	return apply_filters( 'bp_core_avatar_full_height', bp_core_avatar_dimension( 'full', 'height' ) );
}

/**
 * Get the max width for original avatar uploads.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The max width for original avatar uploads.
 */
function bp_core_avatar_original_max_width() {
	return apply_filters( 'bp_core_avatar_original_max_width', (int) buddypress()->avatar->original_max_width );
}

/**
 * Get the max filesize for original avatar uploads.
 *
 * @since BuddyPress (1.5.0)
 *
 * @return int The max filesize for original avatar uploads.
 */
function bp_core_avatar_original_max_filesize() {
	return apply_filters( 'bp_core_avatar_original_max_filesize', (int) buddypress()->avatar->original_max_filesize );
}

/**
 * Get the URL of the 'full' default avatar.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param string $type 'local' if the fallback should be the locally-hosted
 *        version of the mystery-man, 'gravatar' if the fallback should be
 *        Gravatar's version. Default: 'gravatar'.
 * @return string The URL of the default avatar.
 */
function bp_core_avatar_default( $type = 'gravatar' ) {
	// Local override
	if ( defined( 'BP_AVATAR_DEFAULT' ) ) {
		$avatar = BP_AVATAR_DEFAULT;

	// Use the local default image
	} else if ( 'local' === $type ) {
		$avatar = buddypress()->plugin_url . 'bp-core/images/mystery-man.jpg';

	// Use Gravatar's mystery man as fallback
	} else {
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			$host = 'http://www.gravatar.com';
		}

		$avatar = $host . '/avatar/00000000000000000000000000000000?d=mm&amp;s=' . bp_core_avatar_full_width();
	}

	return apply_filters( 'bp_core_avatar_default', $avatar );
}

/**
 * Get the URL of the 'thumb' default avatar.
 *
 * Uses Gravatar's mystery-man avatar, unless BP_AVATAR_DEFAULT_THUMB has been
 * defined.
 *
 * @since BuddyPress (1.5.0)
 *
 * @param string $type 'local' if the fallback should be the locally-hosted
 *        version of the mystery-man, 'gravatar' if the fallback should be
 *        Gravatar's version. Default: 'gravatar'.
 * @return string The URL of the default avatar thumb.
 */
function bp_core_avatar_default_thumb( $type = 'gravatar' ) {
	// Local override
	if ( defined( 'BP_AVATAR_DEFAULT_THUMB' ) ) {
		$avatar = BP_AVATAR_DEFAULT_THUMB;

	// Use the local default image
	} else if ( 'local' === $type ) {
		$avatar = buddypress()->plugin_url . 'bp-core/images/mystery-man-50.jpg';

	// Use Gravatar's mystery man as fallback
	} else {
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			$host = 'http://www.gravatar.com';
		}

		$avatar = $host . '/avatar/00000000000000000000000000000000?d=mm&amp;s=' . bp_core_avatar_thumb_width();
	}

	return apply_filters( 'bp_core_avatar_thumb', $avatar );
}
