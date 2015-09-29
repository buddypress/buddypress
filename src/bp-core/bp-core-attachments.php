<?php
/**
 * BuddyPress Attachments functions.
 *
 * @package BuddyPress
 * @subpackage Attachments
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if the current WordPress version is using Plupload 2.1.1
 *
 * Plupload 2.1.1 was introduced in WordPress 3.9. Our bp-plupload.js
 * script requires it. So we need to make sure the current WordPress
 * match with our needs.
 *
 * @since  2.3.0
 *
 * @return bool True if WordPress is 3.9+, false otherwise.
 */
function bp_attachments_is_wp_version_supported() {
	return (bool) version_compare( bp_get_major_wp_version(), '3.9', '>=' );
}

/**
 * Get the Attachments Uploads dir data
 *
 * @since  2.4.0
 *
 * @param  string        $data The data to get. Possible values are: 'dir', 'basedir' & 'baseurl'
 *                       Leave empty to get all datas.
 * @return string|array  The needed Upload dir data.
 */
function bp_attachments_uploads_dir_get( $data = '' ) {
	$attachments_dir = 'buddypress';
	$retval          = '';

	if ( 'dir' === $data ) {
		$retval = $attachments_dir;
	} else {
		$upload_data = bp_upload_dir();

		// Build the Upload data array for BuddyPress attachments
		foreach ( $upload_data as $key => $value ) {
			if ( 'basedir' === $key || 'baseurl' === $key ) {
				$upload_data[ $key ] = trailingslashit( $value ) . $attachments_dir;
			} else {
				unset( $upload_data[ $key ] );
			}
		}

		// Add the dir to the array
		$upload_data['dir'] = $attachments_dir;

		if ( empty( $data ) ) {
			$retval = $upload_data;
		} elseif ( isset( $upload_data[ $data ] ) ) {
			$retval = $upload_data[ $data ];
		}
	}

	/**
	 * Filter here to edit the Attachments upload dir data.
	 *
	 * @since  2.4.0
	 *
	 * @param  string|array $retval      The needed Upload dir data or the full array of data
	 * @param  string       $data        The data requested
	 */
	return apply_filters( 'bp_attachments_uploads_dir_get', $retval, $data );
}

/**
 * Get the max upload file size for any attachment
 *
 * @since  2.4.0
 *
 * @param  string $type A string to inform about the type of attachment
 *                      we wish to get the max upload file size for
 * @return int    max upload file size for any attachment
 */
function bp_attachments_get_max_upload_file_size( $type = '' ) {
	$fileupload_maxk = bp_core_get_root_option( 'fileupload_maxk' );

	if ( '' === $fileupload_maxk ) {
		$fileupload_maxk = 5120000; // 5mb;
	} else {
		$fileupload_maxk = $fileupload_maxk * 1024;
	}

	/**
	 * Filter here to edit the max upload file size.
	 *
	 * @since  2.4.0
	 *
	 * @param  int    $fileupload_maxk Max upload file size for any attachment
	 * @param  string $type            The attachment type (eg: 'avatar' or 'cover_image')
	 */
	return apply_filters( 'bp_attachments_get_max_upload_file_size', $fileupload_maxk, $type );
}

/**
 * Get allowed types for any attachment
 *
 * @since  2.4.0
 *
 * @param  string $type  The extension types to get.
 *                       Default: 'avatar'
 * @return array         The list of allowed extensions for attachments
 */
function bp_attachments_get_allowed_types( $type = 'avatar' ) {
	// Defaults to BuddyPress supported image extensions
	$exts = array( 'jpeg', 'gif', 'png' );

	/**
	 * It's not a BuddyPress feature, get the allowed extensions
	 * matching the $type requested
	 */
	if ( 'avatar' !== $type && 'cover_image' !== $type ) {
		// Reset the default exts
		$exts = array();

		switch ( $type ) {
			case 'video' :
				$exts = wp_get_video_extensions();
			break;

			case 'audio' :
				$exts = wp_get_video_extensions();
			break;

			default:
				$allowed_mimes = get_allowed_mime_types();

				/**
				 * Search for allowed mimes matching the type
				 *
				 * eg: using 'application/vnd.oasis' as the $type
				 * parameter will get all OpenOffice extensions supported
				 * by WordPress and allowed for the current user.
				 */
				if ( '' !== $type ) {
					$allowed_mimes = preg_grep( '/' . addcslashes( $type, '/.+-' ) . '/', $allowed_mimes );
				}

				$allowed_types = array_keys( $allowed_mimes );

				// Loop to explode keys using '|'
				foreach ( $allowed_types as $allowed_type ) {
					$t = explode( '|', $allowed_type );
					$exts = array_merge( $exts, (array) $t );
				}
			break;
		}
	}

	/**
	 * Filter here to edit the allowed extensions by attachment type.
	 *
	 * @since  2.4.0
	 *
	 * @param  array  $exts List of allowed extensions
	 * @param  string $type The requested file type
	 */
	return apply_filters( 'bp_attachments_get_allowed_types', $exts, $type );
}

/**
 * Get allowed attachment mime types.
 *
 * @since 2.4.0
 *
 * @param  string $type         The extension types to get (Optional).
 * @param  array $allowed_types List of allowed extensions
 * @return array                List of allowed mime types
 */
function bp_attachments_get_allowed_mimes( $type = '', $allowed_types = array() ) {
	if ( empty( $allowed_types ) ) {
		$allowed_types = bp_attachments_get_allowed_types( $type );
	}

	$validate_mimes = wp_match_mime_types( join( ',', $allowed_types ), wp_get_mime_types() );
	$allowed_mimes  = array_map( 'implode', $validate_mimes );

	/**
	 * Include jpg type if jpeg is set
	 */
	if ( isset( $allowed_mimes['jpeg'] ) && ! isset( $allowed_mimes['jpg'] ) ) {
		$allowed_mimes['jpg'] = $allowed_mimes['jpeg'];
	}

	return $allowed_mimes;
}

/**
 * Check the uploaded attachment type is allowed
 *
 * @since  2.4.0
 *
 * @param  string $file          Full path to the file.
 * @param  string $filename      The name of the file (may differ from $file due to $file being
 *                               in a tmp directory).
 * @param  array  $allowed_mimes The attachment allowed mimes (Required)
 * @return bool                  True if the attachment type is allowed. False otherwise
 */
function bp_attachments_check_filetype( $file, $filename, $allowed_mimes ) {
	$filetype = wp_check_filetype_and_ext( $file, $filename, $allowed_mimes );

	if ( ! empty( $filetype['ext'] ) && ! empty( $filetype['type'] ) ) {
		return true;
	}

	return false;
}

/**
 * Get the url or the path for a type of attachment
 *
 * @since  2.4.0
 *
 * @param  string $data whether to get the url or the path
 * @param  array  $args {
 *     @type string $object_dir  The object dir (eg: members/groups). Defaults to members.
 *     @type int    $item_id     The object id (eg: a user or a group id). Defaults to current user.
 *     @type string $type        The type of the attachment which is also the subdir where files are saved.
 *                               Defaults to 'cover-image'
 *     @type string $file        The name of the file.
 * }
 * @return string|bool the url or the path to the attachment, false otherwise
 */
function bp_attachments_get_attachment( $data = 'url', $args = array() ) {
	// Default value
	$attachment_data = false;

	$r = bp_parse_args( $args, array(
		'object_dir' => 'members',
		'item_id'    => bp_loggedin_user_id(),
		'type'       => 'cover-image',
		'file'       => '',
	), 'attachments_get_attachment_src' );

	// Get BuddyPress Attachments Uploads Dir datas
	$bp_attachments_uploads_dir = bp_attachments_uploads_dir_get();

	$type_subdir = $r['object_dir'] . '/' . $r['item_id'] . '/' . $r['type'];
	$type_dir    = trailingslashit( $bp_attachments_uploads_dir['basedir'] ) . $type_subdir;

	if ( ! is_dir( $type_dir ) ) {
		return $attachment_data;
	}

	if ( ! empty( $r['file'] ) ) {
		if ( ! file_exists( trailingslashit( $type_dir ) . $r['file'] ) ) {
			return $attachment_data;
		}

		if ( 'url' === $data ) {
			$attachment_data = trailingslashit( $bp_attachments_uploads_dir['baseurl'] ) . $type_subdir . '/' . $r['file'];
		} else {
			$attachment_data = trailingslashit( $type_dir ) . $r['file'];
		}

	} else {
		$file = false;

		// Open the directory and get the first file
		if ( $att_dir = opendir( $type_dir ) ) {

			while ( false !== ( $attachment_file = readdir( $att_dir ) ) ) {
				// Look for the first file having the type in its name
				if ( false !== strpos( $attachment_file, $r['type'] ) && empty( $file ) ) {
					$file = $attachment_file;
					break;
				}
			}
		}

		if ( empty( $file ) ) {
			return $attachment_data;
		}

		if ( 'url' === $data ) {
			$attachment_data = trailingslashit( $bp_attachments_uploads_dir['baseurl'] ) . $type_subdir . '/' . $file;
		} else {
			$attachment_data = trailingslashit( $type_dir ) . $file;
		}
	}

	return $attachment_data;
}

/**
 * Delete an attachment for the given arguments
 *
 * @since  2.4.0
 *
 * @param  array $args
 * @see    bp_attachments_get_attachment() For more information on accepted arguments.
 * @return bool True if the attachment was deleted, false otherwise
 */
function bp_attachments_delete_file( $args = array() ) {
	$attachment_path = bp_attachments_get_attachment( 'path', $args );

	if ( empty( $attachment_path ) ) {
		return false;
	}

	@unlink( $attachment_path );
	return true;
}

/**
 * Get the BuddyPress Plupload settings.
 *
 * @since  2.3.0
 *
 * @return array list of BuddyPress Plupload settings.
 */
function bp_attachments_get_plupload_default_settings() {

	$max_upload_size = wp_max_upload_size();

	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}

	$defaults = array(
		'runtimes'            => 'html5,flash,silverlight,html4',
		'file_data_name'      => 'file',
		'multipart_params'    => array(
			'action'          => 'bp_upload_attachment',
			'_wpnonce'        => wp_create_nonce( 'bp-uploader' ),
		),
		'url'                 => admin_url( 'admin-ajax.php', 'relative' ),
		'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
		'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
		'filters' => array(
			'max_file_size'   => $max_upload_size . 'b',
		),
		'multipart'           => true,
		'urlstream_upload'    => true,
	);

	// WordPress is not allowing multi selection for iOs 7 device.. See #29602.
	if ( wp_is_mobile() && strpos( $_SERVER['HTTP_USER_AGENT'], 'OS 7_' ) !== false &&
		strpos( $_SERVER['HTTP_USER_AGENT'], 'like Mac OS X' ) !== false ) {

		$defaults['multi_selection'] = false;
	}

	$settings = array(
		'defaults' => $defaults,
		'browser'  => array(
			'mobile'    => wp_is_mobile(),
			'supported' => _device_can_upload(),
		),
		'limitExceeded' => is_multisite() && ! is_upload_space_available(),
	);

	/**
	 * Filter the BuddyPress Plupload default settings.
	 *
	 * @since 2.3.0
	 *
	 * @param array $params Default Plupload parameters array.
	 */
	return apply_filters( 'bp_attachments_get_plupload_default_settings', $settings );
}

/**
 * Builds localization strings for the BuddyPress Uploader scripts.
 *
 * @since  2.3.0
 *
 * @return array Plupload default localization strings.
 */
function bp_attachments_get_plupload_l10n() {
	// Localization strings
	return apply_filters( 'bp_attachments_get_plupload_l10n', array(
			'queue_limit_exceeded'      => __( 'You have attempted to queue too many files.', 'buddypress' ),
			'file_exceeds_size_limit'   => __( '%s exceeds the maximum upload size for this site.', 'buddypress' ),
			'zero_byte_file'            => __( 'This file is empty. Please try another.', 'buddypress' ),
			'invalid_filetype'          => __( 'This file type is not allowed. Please try another.', 'buddypress' ),
			'not_an_image'              => __( 'This file is not an image. Please try another.', 'buddypress' ),
			'image_memory_exceeded'     => __( 'Memory exceeded. Please try another smaller file.', 'buddypress' ),
			'image_dimensions_exceeded' => __( 'This is larger than the maximum size. Please try another.', 'buddypress' ),
			'default_error'             => __( 'An error occurred. Please try again later.', 'buddypress' ),
			'missing_upload_url'        => __( 'There was a configuration error. Please contact the server administrator.', 'buddypress' ),
			'upload_limit_exceeded'     => __( 'You may only upload 1 file.', 'buddypress' ),
			'http_error'                => __( 'HTTP error.', 'buddypress' ),
			'upload_failed'             => __( 'Upload failed.', 'buddypress' ),
			'big_upload_failed'         => __( 'Please try uploading this file with the %1$sbrowser uploader%2$s.', 'buddypress' ),
			'big_upload_queued'         => __( '%s exceeds the maximum upload size for the multi-file uploader when used in your browser.', 'buddypress' ),
			'io_error'                  => __( 'IO error.', 'buddypress' ),
			'security_error'            => __( 'Security error.', 'buddypress' ),
			'file_cancelled'            => __( 'File canceled.', 'buddypress' ),
			'upload_stopped'            => __( 'Upload stopped.', 'buddypress' ),
			'dismiss'                   => __( 'Dismiss', 'buddypress' ),
			'crunching'                 => __( 'Crunching&hellip;', 'buddypress' ),
			'unique_file_warning'       => __( 'Make sure to upload a unique file', 'buddypress' ),
			'error_uploading'           => __( '&#8220;%s&#8221; has failed to upload.', 'buddypress' ),
			'has_avatar_warning'        => __( 'If you&#39;d like to delete the existing profile photo but not upload a new one, please use the delete tab.', 'buddypress' )
	) );
}

/**
 * Enqueues the script needed for the Uploader UI.
 *
 * @see  BP_Attachment::script_data() && BP_Attachment_Avatar::script_data() for examples showing how
 * to set specific script data.
 *
 * @since  2.3.0
 *
 * @param  string $class name of the class extending BP_Attachment (eg: BP_Attachment_Avatar).
 *
 * @return null|WP_Error
 */
function bp_attachments_enqueue_scripts( $class = '' ) {
	// Enqueue me just once per page, please.
	if ( did_action( 'bp_attachments_enqueue_scripts' ) ) {
		return;
	}

	if ( ! $class || ! class_exists( $class ) ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Get an instance of the class and get the script data
	$attachment = new $class;
	$script_data  = $attachment->script_data();

	$args = bp_parse_args( $script_data, array(
		'action'            => '',
		'file_data_name'    => '',
		'max_file_size'     => 0,
		'browse_button'     => 'bp-browse-button',
		'container'         => 'bp-upload-ui',
		'drop_element'      => 'drag-drop-area',
		'bp_params'         => array(),
		'extra_css'         => array(),
		'extra_js'          => array(),
		'feedback_messages' => array(),
	), 'attachments_enqueue_scripts' );

	if ( empty( $args['action'] ) || empty( $args['file_data_name'] ) ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Get the BuddyPress uploader strings
	$strings = bp_attachments_get_plupload_l10n();

	// Get the BuddyPress uploader settings
	$settings = bp_attachments_get_plupload_default_settings();

	// Set feedback messages
	if ( ! empty( $args['feedback_messages'] ) ) {
		$strings['feedback_messages'] = $args['feedback_messages'];
	}

	// Use a temporary var to ease manipulation
	$defaults = $settings['defaults'];

	// Set the upload action
	$defaults['multipart_params']['action'] = $args['action'];

	// Set BuddyPress upload parameters if provided
	if ( ! empty( $args['bp_params'] ) ) {
		$defaults['multipart_params']['bp_params'] = $args['bp_params'];
	}

	// Merge other arguments
	$ui_args = array_intersect_key( $args, array(
		'file_data_name' => true,
		'browse_button'  => true,
		'container'      => true,
		'drop_element'   => true,
	) );

	$defaults = array_merge( $defaults, $ui_args );

	if ( ! empty( $args['max_file_size'] ) ) {
		$defaults['filters']['max_file_size'] = $args['max_file_size'] . 'b';
	}

	// Specific to BuddyPress Avatars
	if ( 'bp_avatar_upload' === $defaults['multipart_params']['action'] ) {

		// Include the cropping informations for avatars
		$settings['crop'] = array(
			'full_h'  => bp_core_avatar_full_height(),
			'full_w'  => bp_core_avatar_full_width(),
		);

		// Avatar only need 1 file and 1 only!
		$defaults['multi_selection'] = false;

		// Does the object already has an avatar set
		$has_avatar = $defaults['multipart_params']['bp_params']['has_avatar'];

		// What is the object the avatar belongs to
		$object = $defaults['multipart_params']['bp_params']['object'];

		// Init the Avatar nav
		$avatar_nav = array(
			'upload' => array( 'id' => 'upload', 'caption' => __( 'Upload', 'buddypress' ), 'order' => 0  ),

			// The delete view will only show if the object has an avatar
			'delete' => array( 'id' => 'delete', 'caption' => __( 'Delete', 'buddypress' ), 'order' => 100, 'hide' => (int) ! $has_avatar ),
		);

		// Create the Camera Nav if the WebCam capture feature is enabled
		if ( bp_avatar_use_webcam() && 'user' === $object ) {
			$avatar_nav['camera'] = array( 'id' => 'camera', 'caption' => __( 'Take Photo', 'buddypress' ), 'order' => 10 );

			// Set warning messages
			$strings['camera_warnings'] = array(
				'requesting'  => __( 'Please allow us to access to your camera.', 'buddypress'),
				'loading'     => __( 'Please wait as we access your camera.', 'buddypress' ),
				'loaded'      => __( 'Camera loaded. Click on the "Capture" button to take your photo.', 'buddypress' ),
				'noaccess'    => __( 'It looks like you do not have a webcam or we were unable to get permission to use your webcam. Please upload a photo instead.', 'buddypress' ),
				'errormsg'    => __( 'Your browser is not supported. Please upload a photo instead.', 'buddypress' ),
				'videoerror'  => __( 'Video error. Please upload a photo instead.', 'buddypress' ),
				'ready'       => __( 'Your profile photo is ready. Click on the "Save" button to use this photo.', 'buddypress' ),
				'nocapture'   => __( 'No photo was captured. Click on the "Capture" button to take your photo.', 'buddypress' ),
			);
		}

		/**
		 * Use this filter to add a navigation to a custom tool to set the object's avatar.
		 *
		 * @since 2.3.0
		 *
		 * @param array $avatar_nav An associative array of available nav items where each item is an array organized this way:
		 * $avatar_nav[ $nav_item_id ] {
		 *     @type string $nav_item_id The nav item id in lower case without special characters or space.
		 *     @type string $caption     The name of the item nav that will be displayed in the nav.
		 *     @type int    $order       An integer to specify the priority of the item nav, choose one.
		 *                               between 1 and 99 to be after the uploader nav item and before the delete nav item.
		 *     @type int    $hide        If set to 1 the item nav will be hidden
		 *                               (only used for the delete nav item).
		 * }
		 * @param string $object the object the avatar belongs to (eg: user or group)
		 */
		$settings['nav'] = bp_sort_by_key( apply_filters( 'bp_attachments_avatar_nav', $avatar_nav, $object ), 'order', 'num' );
	}

	// Set Plupload settings
	$settings['defaults'] = $defaults;

	/**
	 * Enqueue some extra styles if required
	 *
	 * Extra styles need to be registered.
	 */
	if ( ! empty( $args['extra_css'] ) ) {
		foreach ( (array) $args['extra_css'] as $css ) {
			if ( empty( $css ) ) {
				continue;
			}

			wp_enqueue_style( $css );
		}
	}

	wp_enqueue_script ( 'bp-plupload' );
	wp_localize_script( 'bp-plupload', 'BP_Uploader', array( 'strings' => $strings, 'settings' => $settings ) );

	/**
	 * Enqueue some extra scripts if required
	 *
	 * Extra scripts need to be registered.
	 */
	if ( ! empty( $args['extra_js'] ) ) {
		foreach ( (array) $args['extra_js'] as $js ) {
			if ( empty( $js ) ) {
				continue;
			}

			wp_enqueue_script( $js );
		}
	}

	/**
	 * Fires at the conclusion of bp_attachments_enqueue_scripts()
	 * to avoid the scripts to be loaded more than once.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_attachments_enqueue_scripts' );
}

/**
 * Check the current user's capability to edit an avatar for a given object.
 *
 * @since  2.3.0
 *
 * @param  string $capability The capability to check.
 * @param  array  $args       An array containing the item_id and the object to check.
 *
 * @return bool
 */
function bp_attachments_current_user_can( $capability, $args = array() ) {
	$can = false;

	if ( 'edit_avatar' === $capability ) {
		/**
		 * Needed avatar arguments are set.
		 */
		if ( isset( $args['item_id'] ) && isset( $args['object'] ) ) {
			// Group profile photo
			if ( bp_is_active( 'groups' ) && 'group' === $args['object'] ) {
				if ( bp_is_group_create() ) {
					$can = (bool) groups_is_user_creator( bp_loggedin_user_id(), $args['item_id'] ) || bp_current_user_can( 'bp_moderate' );
				} else {
					$can = (bool) groups_is_user_admin( bp_loggedin_user_id(), $args['item_id'] ) || bp_current_user_can( 'bp_moderate' );
				}
			// User profile photo
			} elseif ( bp_is_active( 'xprofile' ) && 'user' === $args['object'] ) {
				$can = bp_loggedin_user_id() === (int) $args['item_id'] || bp_current_user_can( 'bp_moderate' );
			}
		/**
		 * No avatar arguments, fallback to bp_user_can_create_groups()
		 * or bp_is_item_admin()
		 */
		} else {
			if ( bp_is_group_create() ) {
				$can = bp_user_can_create_groups();
			} else {
				$can = bp_is_item_admin();
			}
		}
	}

	return apply_filters( 'bp_attachments_current_user_can', $can, $capability, $args );
}

/**
 * Send a JSON response back to an Ajax upload request.
 *
 * @since  2.3.0
 *
 * @param  bool  $success  True for a success, false otherwise.
 * @param  bool  $is_html4 True if the Plupload runtime used is html4, false otherwise.
 * @param  mixed $data     Data to encode as JSON, then print and die.
 */
function bp_attachments_json_response( $success, $is_html4 = false, $data = null ) {
	$response = array( 'success' => $success );

	if ( isset( $data ) ) {
		$response['data'] = $data;
	}

	// Send regular json response
	if ( ! $is_html4 ) {
		wp_send_json( $response );

	/**
	 * Send specific json response
	 * the html4 Plupload handler requires a text/html content-type for older IE.
	 * See https://core.trac.wordpress.org/ticket/31037
	 */
	} else {
		echo wp_json_encode( $response );

		wp_die();
	}
}

/**
 * Get an Attachment template part.
 *
 * @since 2.3.0
 *
 * @param string $slug Template part slug. eg 'uploader' for 'uploader.php'.
 *
 * @return bool
 */
function bp_attachments_get_template_part( $slug ) {
	$attachment_template_part = 'assets/_attachments/' . $slug;

	// Load the attachment template in WP Administration screens.
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		$attachment_admin_template_part = buddypress()->themes_dir . '/bp-legacy/buddypress/' . $attachment_template_part . '.php';

		// Check whether the template part exists.
		if ( ! file_exists( $attachment_admin_template_part ) ) {
			return false;
		}

		// Load the template part.
		require( $attachment_admin_template_part );

	// Load the attachment template in WP_USE_THEMES env.
	} else {
		bp_get_template_part( $attachment_template_part );
	}
}
