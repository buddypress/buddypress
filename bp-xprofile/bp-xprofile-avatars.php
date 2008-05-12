<?php
/*
Contributor: Beau Lebens - http://www.dentedreality.com.au/
Modified By: Andy Peatling
*/

require_once( ABSPATH . '/wp-admin/includes/image.php' );
require_once( ABSPATH . '/wp-admin/includes/file.php' );

define( 'XPROFILE_AVATAR_V1_W', 50 );
define( 'XPROFILE_AVATAR_V1_H', 50 );
define( 'XPROFILE_AVATAR_V2_W', 220 );
define( 'XPROFILE_AVATAR_V2_H', 220 );
define( 'XPROFILE_CROPPING_CANVAS_MAX', 450 );
define( 'XPROFILE_MAX_FILE_SIZE', get_site_option('fileupload_maxk') * 1024 );
define( 'XPROFILE_DEFAULT_AVATAR', get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/images/none.gif' );


function xprofile_get_avatar( $user, $version = 1, $in_css = false ) {
	if ( !is_int($version) )
		$version = (int) $version;
		
	if ( XPROFILE_AVATAR_V2_W == false && XPROFILE_AVATAR_V2_H == false )
		$version = 1;
	
	$str = get_usermeta( $user, "xprofile_avatar_v$version" );
	
	if ( strlen($str) ) {
		if ( $in_css )
			return $str;
		else
			return '<img src="' . $str . '" alt="" class="avatar" width="' . constant('XPROFILE_AVATAR_V' . $version . '_W') . '" height="' . constant('XPROFILE_AVATAR_V' . $version . '_H') . '" />';
	} else {
		if ( $in_css )
			return XPROFILE_DEFAULT_AVATAR;
		else
			return '<img src="' . XPROFILE_DEFAULT_AVATAR . '" alt="" class="avatar" width="' . constant('XPROFILE_AVATAR_V' . $version . '_W') . '" height="' . constant('XPROFILE_AVATAR_V' . $version . '_H') . '" />';
	}
}

function get_avatar( $user, $version = 1 ) {
	echo xprofile_get_avatar( $user, $version );
}

// Load the cropper etc if we're on the right page
if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'bp-xprofile.php' ) {
	wp_enqueue_script('cropper');
}

// Override internal "get_avatar()" function to use our own where possible
// WARNING: Does NOT apply size restrictions
function xprofile_get_avatar_filter( $avatar, $id_or_email, $size, $default ) {
	$str = '';
	$ver = ( $size == 1 || $size == 2 ) ? $size : 1;
	
	if ( XPROFILE_AVATAR_V2_W == false && XPROFILE_AVATAR_V2_H == false )
		$ver = 1;
		
	if ( is_numeric($id_or_email) ) {
		$str = xprofile_get_avatar( $id_or_email, $ver );
	} elseif ( is_object($id_or_email) ) {
		if ( !empty($id_or_email->user_id) ) {
			$str = xprofile_get_avatar( $id_or_email->user_id, $ver );
		}
	}

	return empty($str) ? $avatar : $str;
}
add_filter( 'get_avatar', 'xprofile_get_avatar_filter', 10, 4 );

// Main UI Rendering
function xprofile_avatar_admin() {
	?>
	<div class="wrap">
		<h2><?php _e('Your Avatar') ?></h2>
	
	<?php if (!isset($_POST['slick_avatars_action'])) { ?>
		<p><?php _e('Your avatar will be used on your profile and throughout the site.') ?></p>
		<p><?php _e('Click below to select a JPG, GIF or PNG format photo from your computer and then click \'Upload Photo\' to proceed.') ?></p>
		
		<form method="post" action="<?php echo get_option('home') ?>/wp-admin/admin.php?page=bp-xprofile.php" enctype="multipart/form-data">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo xprofile_MAX_FILE_SIZE; ?>" />
			<input type="hidden" name="slick_avatars_action" value="upload" />
			<input type="hidden" name="action" value="slick_avatars" />
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('slick_avatars'); ?>" />
			<input type="file" name="file" id="file" />
			<input type="submit" name="upload" id="upload" value="Upload Photo" />
		</form>
		
		<?php
		$str = xprofile_get_avatar( get_current_user_id(), 1 );
		if ( strlen($str) ) {
			echo '<h3>' . __('This is your current avatar') . '</h3>';
			echo '<span class="crop-img avatar">' . xprofile_get_avatar(get_current_user_id(), 1) . '</span>';
			echo '<span class="crop-img avatar">' . xprofile_get_avatar(get_current_user_id(), 2) . '</span>';
		}
	} else if ( isset($_POST['slick_avatars_action']) && $_POST['slick_avatars_action'] == 'upload' ) {
		// Handling the upload of the original photo
		// Confirm that the nonce is valid
		if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'slick_avatars') ) {
			xprofile_ap_die('Security error.');
		}
		
		// Check upload details exist
		if ( !isset($_FILES['file']) ) {
			xprofile_ap_die('Your upload failed, please try again.');
		}

		// Confirm size
		if ( $_FILES['file']['size'] > XPROFILE_MAX_FILE_SIZE ) {
			xprofile_ap_die( 'The file you uploaded is too big. Please upload a file under ' . size_format(1024 * xprofile_MAX_FILE_SIZE) );
		}
		
		// Confirm type
		if ( ( strlen($_FILES['file']['type']) && !preg_match('/(jpe?g|gif|png)$/', $_FILES['file']['type'] ) ) &&
			!preg_match( '/(jpe?g|gif|png)$/', $_FILES['file']['name'] ) ) {
			xprofile_ap_die('Please upload only JPG, GIF or PNG photos.');
		}
		
		// "Handle" upload into temporary location
		$res = wp_handle_upload( $_FILES['file'], array('action'=>'slick_avatars') );
		if ( !in_array('error', array_keys($res) ) ) {
			$original = $res['file'];
		} else {
			$str = stripos( $res['error'], 'MAX_FILE_SIZE' ) ? 'Your file is too big, please use a smaller photo.' : $res['error'];
			xprofile_ap_die( 'Upload Failed! ' . $str );
		}

		// Resize down to something we can display on the page
		$canvas = wp_create_thumbnail( $original, XPROFILE_CROPPING_CANVAS_MAX );
		if ( xprofile_thumb_error($canvas) ) {
			xprofile_ap_die('Could not create thumbnail.');
		}
		$canvas = str_replace( '//', '/', $canvas );
		$size = getimagesize($canvas);
		
		// Get the URL to access the uploaded file
		$src = str_replace( array(ABSPATH), array(get_option('home') . '/'), $canvas );
		
		// Load cropper details
		
		// V1 UI
		echo '<form action="' . get_option('home') .'/wp-admin/admin.php?page=bp-xprofile.php" method="post">';
		echo '<input type="hidden" name="slick_avatars_action" value="crop" />';
		echo '<input type="hidden" name="action" value="slick_avatars" />';
		echo '<input type="hidden" name="nonce" value="' . wp_create_nonce('slick_avatars') . '" />';
		echo '<input type="hidden" name="orig" value="' . $original . '" />';
		echo '<input type="hidden" name="canvas" value="' . $canvas . '" />';
		
		echo '<div id="avatar_v1">';
		echo '<h3>' . __('Main Avatar') . '</h3>';
		echo '<p>' . __('Please select the area of your photo you would like to use for your avatar') . '(' . XPROFILE_AVATAR_V1_W . 'px x ' . XPROFILE_AVATAR_V1_H . 'px).</p>';
		
		// Canvas
		echo '<div id="crop-v1" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="Select the area to crop" id="crop-v1-img" /></div>';
		
		// Preview
		echo '<p><strong>' . __('Crop Preview') . '</strong></p>';
		echo '<div id="crop-preview-v1" class="crop-preview"></div>';
		
		// Hidden form fields
		echo '<input type="hidden" id="v1_x1" name="v1_x1" value="" />';
		echo '<input type="hidden" id="v1_y1" name="v1_y1" value="" />';
		echo '<input type="hidden" id="v1_x2" name="v1_x2" value="" />';
		echo '<input type="hidden" id="v1_y2" name="v1_y2" value="" />';
		echo '<input type="hidden" id="v1_w" name="v1_w" value="" />';
		echo '<input type="hidden" id="v1_h" name="v1_h" value="" />';
		
		// V2 UI (optional)
		if (XPROFILE_AVATAR_V2_W !== false && XPROFILE_AVATAR_V2_H !== false) {
			// Continue button (v1 => v2)
			echo '<p class="submit"><input type="button" name="avatar_continue" value="' . __('Crop &amp; Continue') . '" onclick="cropAndContinue();" /></p>';
			echo '</div>';
			
			echo '<div id="avatar_v2">';
			echo '<h3>' . __('Alternate Avatar') . '</h3>';
			echo '<p>' . __('Please select the area of your photo you would like to use for an alternate version') . '(' . XPROFILE_AVATAR_V2_W . 'px x ' . XPROFILE_AVATAR_V2_H . 'px).</p>';
			
			// Canvas
			echo '<div id="crop-v2" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="Select the area to crop" id="crop-v2-img" /></div>';

			// Preview
			echo '<p><strong>' . __('Crop Preview') . '</strong></p>';
			echo '<div id="crop-preview-v2" class="crop-preview"></div>';

			// Hidden form fields
			echo '<input type="hidden" id="v2_x1" name="v2_x1" value="" />';
			echo '<input type="hidden" id="v2_y1" name="v2_y1" value="" />';
			echo '<input type="hidden" id="v2_x2"name="v2_x2" value="" />';
			echo '<input type="hidden" id="v2_y2"name="v2_y2" value="" />';
			echo '<input type="hidden" id="v2_w" name="v2_w" value="" />';
			echo '<input type="hidden" id="v2_h" name="v2_h" value="" />';
			
			// Final button to process everything
			echo '<p class="submit"><input type="submit" name="submit" value="' . __('Crop &amp; Save') . '" /></p>';
			echo '</div>';
		} else {
			// Close out v1 DIV
			echo '</div>';
			
			// Final button to process everything
			echo '<p class="submit"><input type="submit" name="submit" value="' . __('Crop &amp; Save') . '" /></p>';
		}
		?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function(){
				v1Cropper();
			});
		</script>
		<?php
	} else if ( isset($_POST['slick_avatars_action']) && $_POST['slick_avatars_action'] == 'crop' ) {
		// Crop, save, store
		if ( is_file($_POST['orig']) && is_readable($_POST['orig']) && is_file($_POST['canvas']) && is_readable($_POST['canvas']) ) {
			$source = $_POST['orig'];
			$size = getimagesize($source);
			$canvas = $_POST['canvas'];
			$dims = getimagesize($canvas);
		
			// Figure out multiplier for scaling
			$multi = $size[0] / $dims[0];
			
			// Perform v1 crop
			$v1_dest = dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', '-avatar1' . '$1', basename($source), 1);
			$v1_out = wp_crop_image( $source, ($_POST['v1_x1'] * $multi), ($_POST['v1_y1'] * $multi), ($_POST['v1_w'] * $multi), ($_POST['v1_h'] * $multi), XPROFILE_AVATAR_V1_W, XPROFILE_AVATAR_V1_H, false, $v1_dest );
	
			// Perform v2 crop
			if (XPROFILE_AVATAR_V2_W !== false && XPROFILE_AVATAR_V2_H !== false) {
				$v2_dest = dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', '-avatar2' . '$1', basename($source), 1);
				$v2_out = wp_crop_image( $source, ($_POST['v2_x1'] * $multi), ($_POST['v2_y1'] * $multi), ($_POST['v2_w'] * $multi), ($_POST['v2_h'] * $multi), XPROFILE_AVATAR_V2_W, XPROFILE_AVATAR_V2_H, false, $v2_dest );
			}
		
			// Clean up canvas and original images used during cropping
			foreach ( array( str_replace( '..', '', $source ), str_replace( '..', '', $_POST['canvas']) ) as $f ) {
				@unlink($f);
			}
			
			$dir = $source;
			
			do {
				$dir = dirname($dir);
				@rmdir($dir); // will fail on non-empty directories
			} while ( substr_count($dir, '/') >= 2 && stristr($dir, ABSPATH) );
			
			// Store details to the DB and we're done
			echo '<p>' . __('Your new avatar was successfully created!') . '</p>';
			
			$old = get_usermeta( get_current_user_id(), 'xprofile_avatar_v1_path' );
			$v1_href = str_replace( array(ABSPATH), array( get_option('home') . '/' ), $v1_out );
			update_usermeta( get_current_user_id(), 'xprofile_avatar_v1', $v1_href );
			update_usermeta( get_current_user_id(), 'xprofile_avatar_v1_path', $v1_out );
			@unlink($old); // Removing old avatar
			echo '<span class="crop-img">' . xprofile_get_avatar( get_current_user_id(), 1 ) . '</span>';
			
			if ( XPROFILE_AVATAR_V2_W !== false && XPROFILE_AVATAR_V2_H !== false ) {
				$old = get_usermeta( get_current_user_id(), 'xprofile_avatar_v2_path' );
				$v2_href = str_replace( array(ABSPATH), array(get_option('home') . '/'), $v2_out );
				update_usermeta( get_current_user_id(), 'xprofile_avatar_v2', $v2_href );
				update_usermeta( get_current_user_id(), 'xprofile_avatar_v2_path', $v2_out );
				@unlink($old); // Removing old avatar
				echo '<span class="crop-img">' . xprofile_get_avatar( get_current_user_id(), 2 ) . '</span>';
			}
		}
	}
	?>
	</div>
	<?php
}

function xprofile_ap_die( $msg ) {
	echo '<p><strong>' . $msg . '</strong></p>';
	echo '<p><a href="' . get_option('home') .'/wp-admin/admin.php?page=bp-xprofile.php">' . __('Try Again') . '</a></p>';
	echo '</div>';
	exit;
}

function xprofile_thumb_error( $str ) {
	return preg_match( '/(filetype|invalid|not found)/is', $str );
}

?>