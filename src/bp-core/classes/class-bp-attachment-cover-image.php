<?php
/**
 * Core Cover Image attachment class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Cover Image class.
 *
 * Extends BP Attachment to manage the cover images uploads.
 *
 * @since 2.4.0
 */
class BP_Attachment_Cover_Image extends BP_Attachment {
	/**
	 * The constuctor.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		// Allowed cover image types & upload size.
		$allowed_types        = bp_attachments_get_allowed_types( 'cover_image' );
		$max_upload_file_size = bp_attachments_get_max_upload_file_size( 'cover_image' );

		parent::__construct( array(
			'action'                => 'bp_cover_image_upload',
			'file_input'            => 'file',
			'original_max_filesize' => $max_upload_file_size,
			'base_dir'              => bp_attachments_uploads_dir_get( 'dir' ),
			'required_wp_files'     => array( 'file', 'image' ),

			// Specific errors for cover images.
			'upload_error_strings'  => array(
				/* translators: %s: Max file size for the cover image */
				11  => sprintf( _x( 'That image is too big. Please upload one smaller than %s', 'cover image upload error', 'buddypress' ), size_format( $max_upload_file_size ) ),

				/* translators: %s: comma separated list of file types allowed for the cover image */
				12  => sprintf( _nx( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'cover image upload error', 'buddypress' ), self::get_cover_image_types( $allowed_types ) ),
			),
		) );
	}

	/**
	 * Gets the available cover image types.
	 *
	 * @since 2.4.0
	 *
	 * @param array $allowed_types Array of allowed cover image types.
	 * @return string $value Comma-separated list of allowed cover image types.
	 */
	public static function get_cover_image_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'cover image types separator', 'buddypress' );
		return join( $comma . ' ', $types );
	}

	/**
	 * Cover image specific rules.
	 *
	 * Adds an error if the cover image size or type don't match BuddyPress needs.
	 * The error code is the index of $upload_error_strings.
	 *
	 * @since 2.4.0
	 *
	 * @param array $file The temporary file attributes (before it has been moved).
	 * @return array $file The file with extra errors if needed.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big.
		if ( isset( $file['size'] ) && ( $file['size'] > $this->original_max_filesize ) ) {
			$file['error'] = 11;

		// File is of invalid type.
		} elseif ( isset( $file['tmp_name'] ) && isset( $file['name'] ) && ! bp_attachments_check_filetype( $file['tmp_name'], $file['name'], bp_attachments_get_allowed_mimes( 'cover_image' ) ) ) {
			$file['error'] = 12;
		}

		// Return with error code attached.
		return $file;
	}

	/**
	 * Set the directory when uploading a file.
	 *
	 * @since 2.4.0
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array $value Upload data (path, url, basedir...).
	 */
	public function upload_dir_filter( $upload_dir = array() ) {
		return bp_attachments_cover_image_upload_dir();
	}

	/**
	 * Adjust the cover image to fit with advised width & height.
	 *
	 * @since 2.4.0
	 *
	 * @param string $file       The absolute path to the file.
	 * @param array  $dimensions Array of dimensions for the cover image.
	 * @return mixed
	 */
	public function fit( $file = '', $dimensions = array() ) {
		if ( empty( $dimensions['width'] ) || empty( $dimensions['height'] ) ) {
			return false;
		}

		// Get image size.
		$cover_data = parent::get_image_data( $file );

		// Init the edit args.
		$edit_args = array();

		// Do we need to resize the image?
		if ( ( isset( $cover_data['width'] ) && $cover_data['width'] > $dimensions['width'] ) ||
		( isset( $cover_data['height'] ) && $cover_data['height'] > $dimensions['height'] ) ) {
			$edit_args = array(
				'max_w' => $dimensions['width'],
				'max_h' => $dimensions['height'],
				'crop'  => true,
			);
		}

		// Do we need to rotate the image?
		$angles = array(
			3 => 180,
			6 => -90,
			8 =>  90,
		);

		if ( isset( $cover_data['meta']['orientation'] ) && isset( $angles[ $cover_data['meta']['orientation'] ] ) ) {
			$edit_args['rotate'] = $angles[ $cover_data['meta']['orientation'] ];
		}

		// No need to edit the avatar, original file will be used.
		if ( empty( $edit_args ) ) {
			return false;

		// Add the file to the edit arguments.
		} else {
			$edit_args = array_merge( $edit_args, array( 'file' => $file, 'save' => false ) );
		}

		// Get the editor so that we can use a specific save method.
		$editor = parent::edit_image( 'cover_image', $edit_args );

		if ( is_wp_error( $editor ) )  {
			return $editor;
		} elseif ( ! is_a( $editor, 'WP_Image_Editor' ) ) {
			return false;
		}

		// Save the new image file.
		return $editor->save( $this->generate_filename( $file ) );
	}

	/**
	 * Generate a filename for the cover image.
	 *
	 * @since 2.4.0
	 *
	 * @param string $file The absolute path to the file.
	 * @return false|string $value The absolute path to the new file name.
	 */
	public function generate_filename( $file = '' ) {
		if ( empty( $file ) || ! file_exists( $file ) ) {
			return false;
		}

		$info = pathinfo( $file );
		$ext  = strtolower( $info['extension'] );
		$name = wp_unique_filename( $info['dirname'], uniqid() . "-bp-cover-image.$ext" );

		return trailingslashit( $info['dirname'] ) . $name;
	}

	/**
	 * Build script datas for the Uploader UI.
	 *
	 * @since 2.4.0
	 *
	 * @return array The javascript localization data
	 */
	public function script_data() {
		// Get default script data.
		$script_data = parent::script_data();

		if ( bp_is_user() ) {
			$item_id = bp_displayed_user_id();

			$script_data['bp_params'] = array(
				'object'          => 'user',
				'item_id'         => $item_id,
				'has_cover_image' => bp_attachments_get_user_has_cover_image( $item_id ),
				'nonces'  => array(
					'remove' => wp_create_nonce( 'bp_delete_cover_image' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'Your new cover image was uploaded successfully.', 'buddypress' ),
				2 => __( 'There was a problem deleting your cover image. Please try again.', 'buddypress' ),
				3 => __( 'Your cover image was deleted successfully!', 'buddypress' ),
			);
		} elseif ( bp_is_group() ) {
			$item_id = bp_get_current_group_id();

			$script_data['bp_params'] = array(
				'object'          => 'group',
				'item_id'         => bp_get_current_group_id(),
				'has_cover_image' => bp_attachments_get_group_has_cover_image( $item_id ),
				'nonces'  => array(
					'remove' => wp_create_nonce( 'bp_delete_cover_image' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'The group cover image was uploaded successfully.', 'buddypress' ),
				2 => __( 'There was a problem deleting the group cover image. Please try again.', 'buddypress' ),
				3 => __( 'The group cover image was deleted successfully!', 'buddypress' ),
			);
		} else {

			/**
			 * Filters the cover image params to include specific BuddyPress params for your object.
			 * e.g. Cover image for blogs single item.
			 *
			 * @since 2.4.0
			 *
			 * @param array $value The cover image specific BuddyPress parameters.
			 */
			$script_data['bp_params'] = apply_filters( 'bp_attachment_cover_image_params', array() );
		}

		// Include our specific js & css.
		$script_data['extra_js']  = array( 'bp-cover-image' );
		$script_data['extra_css'] = array( 'bp-avatar' );

		/**
		 * Filters the cover image script data.
		 *
		 * @since 2.4.0
		 *
		 * @param array $script_data Array of data for the cover image.
		 */
		return apply_filters( 'bp_attachments_cover_image_script_data', $script_data );
	}
}
