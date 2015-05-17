<?php
/**
 * Core attachment class.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment class
 *
 * Extend it to manage your component's uploads.
 *
 * @since BuddyPress (2.3.0)
 */
abstract class BP_Attachment {

	/** Upload properties *****************************************************/

	/**
	 * The file being uploaded
	 *
	 * @var array
	 */
	public $attachment = array();

	/**
	 * The default args to be merged with the
	 * ones passed by the child class
	 *
	 * @var array
	 */
	protected $default_args = array(
		'original_max_filesize' => 0,
		'allowed_mime_types'    => array(),
		'base_dir'              => '',
		'action'                => '',
		'file_input'            => '',
		'upload_error_strings'  => array(),
		'required_wp_files'     => array( 'file' ),
	);

	/**
	 * Construct Upload parameters
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     @type int    $original_max_filesize Maximum file size in kilobytes. Defaults to php.ini settings.
	 *     @type array  $allowed_mime_types    List of allowed file extensions (eg: array( 'jpg', 'gif', 'png' ) ).
	 *                                         Defaults to WordPress allowed mime types
	 *     @type string $base_dir              Component's upload base directory. Defaults to WordPress 'uploads'
	 *     @type string $action                The upload action used when uploading a file, $_POST['action'] must be set
	 *                                         and its value must equal $action {@link wp_handle_upload()} (required)
	 *     @type string $file_input            The name attribute used in the file input. (required)
	 *     @type array  $upload_error_strings  A list of specific error messages (optional).
	 *     @type array  $required_wp_files     The list of required WordPress core files. Default: array( 'file' );
	 * }
	 * @uses  sanitize_key()
	 * @uses  wp_max_upload_size()
	 * @uses  bp_parse_args()
	 * @uses  BP_Attachment->set_upload_error_strings()
	 * @uses  BP_Attachment->set_upload_dir()
	 */
	public function __construct( $args = '' ) {
		// Upload action and the file input name are required parameters
		if ( empty( $args['action'] ) || empty( $args['file_input'] ) ) {
			return false;
		}

		// Sanitize the action ID and the file input name
		$this->action     = sanitize_key( $args['action'] );
		$this->file_input = sanitize_key( $args['file_input'] );

		/**
		 * Max file size defaults to php ini settings or, in the case of
		 * a multisite config, the root site fileupload_maxk option
		 */
		$this->default_args['original_max_filesize'] = (int) wp_max_upload_size();

		$params = bp_parse_args( $args, $this->default_args, $this->action . '_upload_params' );

		foreach ( $params as $key => $param ) {
			if ( 'upload_error_strings' === $key ) {
				$this->{$key} = $this->set_upload_error_strings( $param );

			// Sanitize the base dir
			} elseif ( 'base_dir' === $key ) {
				$this->{$key} = sanitize_title( $param );

			// Action & File input are already set and sanitized
			} elseif ( 'action' !== $key && 'file_input' !== $key ) {
				$this->{$key} = $param;
			}
		}

		// Set the path/url and base dir for uploads
		$this->set_upload_dir();
	}

	/**
	 * Set upload path and url for the component.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @uses  bp_upload_dir()
	 */
	public function set_upload_dir() {
		// Set the directory, path, & url variables
		$this->upload_dir  = bp_upload_dir();

		if ( empty( $this->upload_dir ) ) {
			return false;
		}

		$this->upload_path = $this->upload_dir['basedir'];
		$this->url         = $this->upload_dir['baseurl'];

		// Ensure URL is https if SSL is set/forced
		if ( is_ssl() ) {
			$this->url = str_replace( 'http://', 'https://', $this->url );
		}

		/**
		 * Custom base dir.
		 *
		 * If the component set this property, set the specific path, url and create the dir
		 */
		if ( ! empty( $this->base_dir ) ) {
			$this->upload_path = trailingslashit( $this->upload_path ) . $this->base_dir;
			$this->url         = trailingslashit( $this->url  ) . $this->base_dir;

			// Finally create the base dir
			$this->create_dir();
		}
	}

	/**
	 * Set Upload error messages
	 *
	 * Used into the $overrides argument of BP_Attachment->upload()
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $param a list of error messages to add to BuddyPress core ones
	 * @return array the list of upload errors
	 */
	public function set_upload_error_strings( $param = array() ) {
		/**
		 * Index of the array is the error code
		 * Custom errors will start at 9 code
		 */
		$upload_errors = array(
			0 => __( 'The file was uploaded successfully', 'buddypress' ),
			1 => __( 'The uploaded file exceeds the maximum allowed file size for this site', 'buddypress' ),
			2 => sprintf( __( 'The uploaded file exceeds the maximum allowed file size of: %s', 'buddypress' ), size_format( $this->original_max_filesize ) ),
			3 => __( 'The uploaded file was only partially uploaded.', 'buddypress' ),
			4 => __( 'No file was uploaded.', 'buddypress' ),
			5 => '',
			6 => __( 'Missing a temporary folder.', 'buddypress' ),
			7 => __( 'Failed to write file to disk.', 'buddypress' ),
			8 => __( 'File upload stopped by extension.', 'buddypress' ),
		);

		if ( ! array_intersect_key( $upload_errors, (array) $param ) ) {
			foreach ( $param as $key_error => $error_message ) {
				$upload_errors[ $key_error ] = $error_message;
			}
		}

		return $upload_errors;
	}

	/**
	 * Include the WordPress core needed files
	 *
	 * @since BuddyPress (2.3.0)
	 */
	public function includes() {
		foreach ( array_unique( $this->required_wp_files ) as $wp_file ) {
			if ( ! file_exists( ABSPATH . "/wp-admin/includes/{$wp_file}.php" ) ) {
				continue;
			}

			require_once( ABSPATH . "/wp-admin/includes/{$wp_file}.php" );
		}
	}

	/**
	 * Upload the attachment
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param  array $file               The appropriate entry the from $_FILES superglobal.
	 * @param  string $upload_dir_filter A specific filter to be applied to 'upload_dir' (optional).
	 * @param  string $time              Optional. Time formatted in 'yyyy/mm'. Default null.
	 * @uses   wp_handle_upload()        To upload the file
	 * @uses   add_filter()              To temporarly overrides WordPress uploads data
	 * @uses   remove_filter()           To stop overriding WordPress uploads data
	 * @uses   apply_filters()           Call 'bp_attachment_upload_overrides' to include specific upload overrides
	 *
	 * @return array                     On success, returns an associative array of file attributes.
	 *                                   On failure, returns an array containing the error message
	 *                                   (eg: array( 'error' => $message ) )
	 */
	public function upload( $file, $upload_dir_filter = '', $time = null ) {
		/**
		 * Upload action and the file input name are required parameters
		 * @see BP_Attachment:__construct()
		 */
		if ( empty( $this->action ) || empty( $this->file_input ) ) {
			return false;
		}

		/**
		 * Add custom rules before enabling the file upload
		 */
		add_filter( "{$this->action}_prefilter", array( $this, 'validate_upload' ), 10, 1 );

		/**
		 * The above dynamic filter was introduced in WordPress 4.0, as we support WordPress
		 * back to 3.6, we need to also use the pre 4.0 static filter and remove it after
		 * the upload was processed.
		 */
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_upload' ), 10, 1 );

		// Set Default overrides
		$overrides = array(
			'action'               => $this->action,
			'upload_error_strings' => $this->upload_error_strings,
		);

		/**
		 * Add a mime override if needed
		 * Used to restrict uploads by extensions
		 */
		if ( ! empty( $this->allowed_mime_types ) ) {
			$mime_types = $this->validate_mime_types();

			if ( ! empty( $mime_types ) ) {
				$overrides['mimes'] = $mime_types;
			}
		}

		/**
		 * If you need to add some overrides we haven't thought of
		 *
		 * @var  array $overrides the wp_handle_upload overrides
		 */
		$overrides = apply_filters( 'bp_attachment_upload_overrides', $overrides );

		$this->includes();

		/**
		 * If the $base_dir was set when constructing the class,
		 * and no specific filter has been requested, use a default
		 * filter to create the specific $base dir
		 * @see  BP_Attachment->upload_dir_filter()
		 */
		if ( empty( $upload_dir_filter ) && ! empty( $this->base_dir ) ) {
			$upload_dir_filter = array( $this, 'upload_dir_filter' );
		}

		// Make sure the file will be uploaded in the attachment directory
		if ( ! empty( $upload_dir_filter ) ) {
			add_filter( 'upload_dir', $upload_dir_filter, 10, 0 );
		}

		// Upload the attachment
		$this->attachment = wp_handle_upload( $file[ $this->file_input ], $overrides, $time );

		// Restore WordPress Uploads data
		if ( ! empty( $upload_dir_filter ) ) {
			remove_filter( 'upload_dir', $upload_dir_filter, 10, 0 );
		}

		// Remove the pre WordPress 4.0 static filter
		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_upload' ), 10, 1 );

		// Finally return the uploaded file or the error
		return $this->attachment;
	}

	/**
	 * Validate the allowed mime types using WordPress allowed mime types
	 *
	 * In case of a multisite, the mime types are already restricted by
	 * the 'upload_filetypes' setting. BuddyPress will respect this setting.
	 * @see check_upload_mimes()
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @uses get_allowed_mime_types()
	 */
	protected function validate_mime_types() {
		$wp_mimes = get_allowed_mime_types();
		$valid_mimes = array();

		// Set the allowed mimes for the upload
		foreach ( (array) $this->allowed_mime_types as $ext ) {
			foreach ( $wp_mimes as $ext_pattern => $mime ) {
				if ( $ext !== '' && strpos( $ext_pattern, $ext ) !== false ) {
					$valid_mimes[$ext_pattern] = $mime;
				}
			}
		}
		return $valid_mimes;
	}

	/**
	 * Specific upload rules
	 *
	 * Override this function from your child class to build your specific rules
	 * By default, if an original_max_filesize is provided, a check will be done
	 * on the file size.
	 *
	 * @see BP_Attachment_Avatar->validate_upload() for an example of use
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param  array $file the temporary file attributes (before it has been moved)
	 * @return array the file
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		if ( ! empty( $this->original_max_filesize ) && $file['size'] > $this->original_max_filesize ) {
			$file['error'] = 2;
		}

		// Return the file
		return $file;
	}

	/**
	 * Default filter to save the attachments
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @uses   apply_filters() call 'bp_attachment_upload_dir' to eventually override the upload location
	 *                         regarding to context
	 * @return array the upload directory data
	 */
	public function upload_dir_filter() {
		/**
		 * Filters the component's upload directory.
		 *
		 * @since BuddyPress (2.3.0)
		 *
		 * @param array $value Array containing the path, URL, and other helpful settings.
		 */
		return apply_filters( 'bp_attachment_upload_dir', array(
			'path'    => $this->upload_path,
			'url'     => $this->url,
			'subdir'  => false,
			'basedir' => $this->upload_path,
			'baseurl' => $this->url,
			'error'   => false
		) );
	}

	/**
	 * Create the custom base directory for the component uploads
	 *
	 * Override this function in your child class to run specific actions
	 * (eg: add an .htaccess file)
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @uses   wp_mkdir_p()
	 */
	public function create_dir() {
		// Bail if no specific base dir is set
		if ( empty( $this->base_dir ) ) {
			return false;
		}

		// Check if upload path already exists
		if ( ! is_dir( $this->upload_path ) ) {

			// If path does not exist, attempt to create it
			if ( ! wp_mkdir_p( $this->upload_path ) ) {
				return false;
			}
		}

		// Directory exists
		return true;
	}

	/**
	 * Crop an image file
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     @type string $original_file The source file (absolute path) for the Attachment.
	 *     @type int    $crop_x The start x position to crop from.
	 *     @type int    $crop_y The start y position to crop from.
	 *     @type int    $crop_w The width to crop.
	 *     @type int    $crop_h The height to crop.
	 *     @type int    $dst_w The destination width.
	 *     @type int    $dst_h The destination height.
	 *     @type int    $src_abs Optional. If the source crop points are absolute.
	 *     @type string $dst_file Optional. The destination file to write to.
	 * }
	 * @uses wp_crop_image()
	 * @return string|WP_Error New filepath on success, WP_Error on failure.
	 */
	public function crop( $args = array() ) {
		$wp_error = new WP_Error();

		$r = bp_parse_args( $args, array(
			'original_file' => '',
			'crop_x'        => 0,
			'crop_y'        => 0,
			'crop_w'        => 0,
			'crop_h'        => 0,
			'dst_w'         => 0,
			'dst_h'         => 0,
			'src_abs'       => false,
			'dst_file'      => false,
		), 'bp_attachment_crop_args' );

		if ( empty( $r['original_file'] ) || ! file_exists( $r['original_file'] ) ) {
			$wp_error->add( 'crop_error', __( 'Cropping the file failed: missing source file.', 'buddypress' ) );
			return $wp_error;
		}

		// Check image file pathes
		$path_error = __( 'Cropping the file failed: the file path is not allowed.', 'buddypress' );

		// Make sure it's coming from an uploaded file
		if ( false === strpos( $r['original_file'], $this->upload_path ) ) {
			$wp_error->add( 'crop_error', $path_error );
			return $wp_error;
		}

		/**
		 * If no destination file is provided, WordPress will use a default name
		 * and will write the file in the source file's folder.
		 * If a destination file is provided, we need to make sure it's going into uploads
		 */
		if ( ! empty( $r['dst_file'] ) && false === strpos( $r['dst_file'], $this->upload_path ) ) {
			$wp_error->add( 'crop_error', $path_error );
			return $wp_error;
		}

		// Check image file types
		$check_types = array( 'src_file' => array( 'file' => $r['original_file'], 'error' => _x( 'source file', 'Attachment source file', 'buddypress' ) ) );
		if ( ! empty( $r['dst_file'] ) ) {
			$check_types['dst_file'] = array( 'file' => $r['dst_file'], 'error' => _x( 'destination file', 'Attachment destination file', 'buddypress' ) );
		}

		/**
		 * WordPress image supported types
		 * @see wp_attachment_is()
		 */
		$supported_image_types = array(
			'jpg'  => 1,
			'jpeg' => 1,
			'jpe'  => 1,
			'gif'  => 1,
			'png'  => 1,
		);

		foreach ( $check_types as $file ) {
			$is_image      = wp_check_filetype( $file['file'] );
			$ext           = $is_image['ext'];

			if ( empty( $ext ) || empty( $supported_image_types[ $ext ] ) ) {
				$wp_error->add( 'crop_error', sprintf( __( 'Cropping the file failed: %s is not a supported image file.', 'buddypress' ), $file['error'] ) );
				return $wp_error;
			}
		}

		// Add the image.php to the required WordPress files, if it's not already the case
		$required_files = array_flip( $this->required_wp_files );
		if ( ! isset( $required_files['image'] ) ) {
			$this->required_wp_files[] = 'image';
		}

		// Load the files
		$this->includes();

		// Finally crop the image
		return wp_crop_image( $r['original_file'], (int) $r['crop_x'], (int) $r['crop_y'], (int) $r['crop_w'], (int) $r['crop_h'], (int) $r['dst_w'], (int) $r['dst_h'], $r['src_abs'], $r['dst_file'] );
	}

	/**
	 * Build script datas for the Uploader UI
	 *
	 * Override this method from your child class to build the script datas
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return array the javascript localization data
	 */
	public function script_data() {
		$script_data = array(
			'action'            => $this->action,
			'file_data_name'    => $this->file_input,
			'max_file_size'     => $this->original_max_filesize,
			'feedback_messages' => array(
				1 => __( 'Sorry, uploading the file failed.', 'buddypress' ),
				2 => __( 'File successfully uploaded.', 'buddypress' ),
			),
		);

		return $script_data;
	}
}
