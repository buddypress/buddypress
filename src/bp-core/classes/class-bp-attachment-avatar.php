<?php
/**
 * Core Avatars attachment class
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Avatar class
 *
 * Extends BP Attachment to manage the avatar uploads
 *
 * @since BuddyPress (2.3.0)
 */
class BP_Attachment_Avatar extends BP_Attachment {

	/**
	 * Construct Upload parameters
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see  BP_Attachment::__construct() for list of parameters
	 * @uses bp_core_avatar_original_max_filesize()
	 * @uses BP_Attachment::__construct()
	 */
	public function __construct() {
		parent::__construct( array(
			'action'                => 'bp_avatar_upload',
			'file_input'            => 'file',
			'original_max_filesize' => bp_core_avatar_original_max_filesize(),

			// Specific errors for avatars
			'upload_error_strings'  => array(
				9  => sprintf( __( 'That photo is too big. Please upload one smaller than %s', 'buddypress' ), size_format( bp_core_avatar_original_max_filesize() ) ),
				10 => __( 'Please upload only JPG, GIF or PNG photos.', 'buddypress' ),
			),
		) );
	}

	/**
	 * Set Upload Dir data for avatars
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @uses bp_core_avatar_upload_path()
	 * @uses bp_core_avatar_url()
	 * @uses bp_upload_dir()
	 * @uses BP_Attachment::set_upload_dir()
	 */
	public function set_upload_dir() {
		if ( bp_core_avatar_upload_path() && bp_core_avatar_url() ) {
			$this->upload_path = bp_core_avatar_upload_path();
			$this->url         = bp_core_avatar_url();
			$this->upload_dir  = bp_upload_dir();
		} else {
			parent::set_upload_dir();
		}
	}

	/**
	 * Avatar specific rules
	 *
	 * Adds an error if the avatar size or type don't match BuddyPress needs
	 * The error code is the index of $upload_error_strings
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param  array $file the temporary file attributes (before it has been moved)
	 * @uses   bp_core_check_avatar_size()
	 * @uses   bp_core_check_avatar_type()
	 * @return array the file with extra errors if needed
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big
		if ( ! bp_core_check_avatar_size( array( 'file' => $file ) ) ) {
			$file['error'] = 9;

		// File is of invalid type
		} elseif ( ! bp_core_check_avatar_type( array( 'file' => $file ) ) ) {
			$file['error'] = 10;
		}

		// Return with error code attached
		return $file;
	}

	/**
	 * Maybe shrink the attachment to fit maximum allowed width
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param string $file the absolute path to the file
	 * @uses  bp_core_avatar_original_max_width()
	 * @uses  wp_get_image_editor()
	 * @return mixed
	 */
	public static function shrink( $file = '' ) {
		// Get image size
		$size   = @getimagesize( $file );
		$retval = false;

		// Check image size and shrink if too large
		if ( $size[0] > bp_core_avatar_original_max_width() ) {
			$editor = wp_get_image_editor( $file );

			if ( ! is_wp_error( $editor ) ) {
				$editor->set_quality( 100 );

				$resized = $editor->resize( bp_core_avatar_original_max_width(), bp_core_avatar_original_max_width(), false );
				if ( ! is_wp_error( $resized ) ) {
					$thumb = $editor->save( $editor->generate_filename() );
				} else {
					$retval = $resized;
				}

				// Check for thumbnail creation errors
				if ( ( false === $retval ) && is_wp_error( $thumb ) ) {
					$retval = $thumb;
				}

				// Thumbnail is good so proceed
				if ( false === $retval ) {
					$retval = $thumb;
				}

			} else {
				$retval = $editor;
			}
		}

		return $retval;
	}

	/**
	 * Check if the image dimensions are smaller than full avatar dimensions
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param string $file the absolute path to the file
	 * @uses  bp_core_avatar_full_width()
	 * @uses  bp_core_avatar_full_height()
	 * @return boolean
	 */
	public static function is_too_small( $file = '' ) {
		$uploaded_image = @getimagesize( $file );
		$full_width     = bp_core_avatar_full_width();
		$full_height    = bp_core_avatar_full_height();

		if ( isset( $uploaded_image[0] ) && $uploaded_image[0] < $full_width || $uploaded_image[1] < $full_height ) {
			return true;
		}

		return false;
	}

	/**
	 * Crop the avatar
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see  BP_Attachment::crop for the list of parameters
	 * @param array $args
	 * @uses bp_core_fetch_avatar()
	 * @uses bp_core_delete_existing_avatar()
	 * @uses bp_core_avatar_full_width()
	 * @uses bp_core_avatar_full_height()
	 * @uses bp_core_avatar_dimension()
	 * @uses BP_Attachment::crop
	 * @return array the cropped avatars (full and thumb)
	 */
	public function crop( $args = array() ) {
		// Bail if the original file is missing
		if ( empty( $args['original_file'] ) ) {
			return false;
		}

		/**
		 * Original file is a relative path to the image
		 * eg: /avatars/1/avatar.jpg
		 */
		$relative_path = $args['original_file'];
		$absolute_path = $this->upload_path . $relative_path;

		// Bail if the avatar is not available
		if ( ! file_exists( $absolute_path ) )  {
			return false;
		}

		if ( empty( $args['item_id'] ) ) {
			$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $absolute_path ), $args['item_id'], $args['object'], $args['avatar_dir'] );
		} else {
			$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', $this->upload_path . '/' . $args['avatar_dir'] . '/' . $args['item_id'], $args['item_id'], $args['object'], $args['avatar_dir'] );
		}

		// Bail if the avatar folder is missing for this item_id
		if ( ! file_exists( $avatar_folder_dir ) ) {
			return false;
		}

		// Delete the existing avatar files for the object
		$existing_avatar = bp_core_fetch_avatar( array(
			'object'  => $args['object'],
			'item_id' => $args['item_id'],
			'html' => false,
		) );

		/**
		 * Check that the new avatar doesn't have the same name as the
		 * old one before deleting
		 */
		if ( ! empty( $existing_avatar ) && $existing_avatar !== $this->url . $relative_path ) {
			bp_core_delete_existing_avatar( array( 'object' => $args['object'], 'item_id' => $args['item_id'], 'avatar_path' => $avatar_folder_dir ) );
		}

		// Make sure we at least have minimal data for cropping
		if ( empty( $args['crop_w'] ) ) {
			$args['crop_w'] = bp_core_avatar_full_width();
		}

		if ( empty( $args['crop_h'] ) ) {
			$args['crop_h'] = bp_core_avatar_full_height();
		}

		// Get the file extension
		$data = @getimagesize( $absolute_path );
		$ext  = $data['mime'] == 'image/png' ? 'png' : 'jpg';

		$args['original_file'] = $absolute_path;
		$args['src_abs']       = false;
		$avatar_types = array( 'full' => '', 'thumb' => '' );

		foreach ( $avatar_types as $key_type => $type ) {
			$args['dst_w']    = bp_core_avatar_full_width();
			$args['dst_h']    = bp_core_avatar_full_height();
			$args['dst_file'] = $avatar_folder_dir . '/' . wp_hash( $absolute_path . time() ) . '-bp' . $key_type . '.' . $ext;

			$avatar_types[ $key_type ] = parent::crop( $args );
		}

		// Remove the original
		@unlink( $absolute_path );

		// Return the full and thumb cropped avatars
		return $avatar_types;
	}
}
