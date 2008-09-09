<?php
/*
Based on contributions from: Beau Lebens - http://www.dentedreality.com.au/
Modified for BuddyPress by: Andy Peatling - http://apeatling.wordpress.com/
*/

/* Make sure we have the core WordPress files we need */
require_once( ABSPATH . '/wp-admin/includes/image.php' );
require_once( ABSPATH . '/wp-admin/includes/file.php' );

/* Define settings for avatars. [TODO] This will eventually end up as admin configurable settings */
define( 'CORE_AVATAR_V1_W', 50 );
define( 'CORE_AVATAR_V1_H', 50 );
define( 'CORE_AVATAR_V2_W', 150 );
define( 'CORE_AVATAR_V2_H', 150 );
define( 'CORE_CROPPING_CANVAS_MAX', 450 );
define( 'CORE_MAX_FILE_SIZE', get_site_option('fileupload_maxk') * 1024 );
define( 'CORE_DEFAULT_AVATAR', get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/images/none.gif' );
define( 'CORE_DEFAULT_AVATAR_THUMB', get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/images/none-thumbnail.gif' );

function bp_core_get_avatar( $user, $version = 1, $no_tag = false, $width = null, $height = null ) {
	if ( !is_int($version) )
		$version = (int) $version;
		
	if ( CORE_AVATAR_V2_W == false && CORE_AVATAR_V2_H == false )
		$version = 1;
	
	$home_base_id = get_usermeta( $user, 'home_base' );
	$url = get_blog_option($home_base_id, 'siteurl');
	
	if ( !$width )
		$width = constant('CORE_AVATAR_V' . $version . '_W');
	
	if ( !$height )
		$width = constant('CORE_AVATAR_V' . $version . '_H');		
	
	$str = get_usermeta( $user, "bp_core_avatar_v$version" );
	
	if ( strlen($str) ) {
		if ( $no_tag )
			return $str;
		else
			return '<img src="' . $url . '/' . $str . '" alt="" class="avatar" width="' . $width . '" height="' . $height . '" />';
	} else {
		if ( $no_tag )
			return CORE_DEFAULT_AVATAR_THUMB;
		else
			return '<img src="' . CORE_DEFAULT_AVATAR . '" alt="" class="avatar" width="' . $width . '" height="' . $height . '" />';
	}
}

// Override internal "get_avatar()" function to use our own where possible
// WARNING: Does NOT apply size restrictions
function bp_core_get_avatar_filter( $avatar, $id_or_email, $size, $default ) {
	$str = '';
	$ver = ( $size == 1 || $size == 2 ) ? $size : 1;
	
	if ( CORE_AVATAR_V2_W == false && CORE_AVATAR_V2_H == false )
		$ver = 1;
		
	if ( is_numeric($id_or_email) ) {
		$str = bp_core_get_avatar( $id_or_email, $ver );
	} elseif ( is_object($id_or_email) ) {
		if ( !empty($id_or_email->user_id) ) {
			$str = bp_core_get_avatar( $id_or_email->user_id, $ver );
		}
	}

	return empty($str) ? $avatar : $str;
}
add_filter( 'get_avatar', 'bp_core_get_avatar_filter', 10, 4 );


// Main UI Rendering
function bp_core_avatar_admin( $message = null, $action = null, $delete_action = null ) {
	?>	
	<?php if ( !isset($_POST['slick_avatars_action']) && !isset($_GET['slick_avatars_action']) ) { ?>
	<div class="wrap">
		<h2><?php _e('Your Avatar') ?></h2>
		
		<?php if ( $message ) { ?>
			<br />
			<div id="message" class="updated fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>

		<p><?php _e('Your avatar will be used on your profile and throughout the site.') ?></p>
		<p><?php _e('Click below to select a JPG, GIF or PNG format photo from your computer and then click \'Upload Photo\' to proceed.') ?></p>
		
		<?php
		if ( !$action )
			$action = get_option('siteurl') . '/wp-admin/admin.php?page=bp-xprofile.php';
		
		if ( !$delete_action )
			$delete_action = get_option('siteurl') . '/wp-admin/admin.php?page=bp-xprofile.php&slick_avatars_action=delete';
		
		bp_core_render_avatar_upload_form($action);

		$str = bp_core_get_avatar( get_current_user_id(), 1 );
		if ( strlen($str) ) {
			echo '<h3>' . __('This is your current avatar') . '</h3>';
			echo '<span class="crop-img avatar">' . bp_core_get_avatar(get_current_user_id(), 1) . '</span>';
			echo '<span class="crop-img avatar">' . bp_core_get_avatar(get_current_user_id(), 2) . '</span>';
			echo '<a href="' .  $delete_action . '">Delete</a>';
		}
		
		echo '</div>';
	
	} else if ( isset($_POST['slick_avatars_action']) && $_POST['slick_avatars_action'] == 'upload' ) {
	
		echo '<div class="wrap"><h2>';
		_e('Your Avatar');
		echo '</h2>';
		
		// Confirm that the nonce is valid
		if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'slick_avatars') )
			bp_core_ap_die( 'Security error.' );
		
		if ( !bp_core_check_avatar_upload($_FILES) )
			bp_core_ap_die( 'Your upload failed, please try again.' );
		
		if ( !bp_core_check_avatar_size($_FILES) )
			bp_core_ap_die( 'The file you uploaded is too big. Please upload a file under ' . size_format(1024 * CORE_MAX_FILE_SIZE) );

		if ( !bp_core_check_avatar_type($_FILES) )
			bp_core_ap_die( 'Please upload only JPG, GIF or PNG photos.' );
		
		// "Handle" upload into temporary location
		if ( !$original = bp_core_handle_avatar_upload($_FILES) )
			bp_core_ap_die( 'Upload Failed! Your image is likely too big.' );
		
		if ( !bp_core_check_avatar_dimensions($original) )
			bp_core_ap_die( 'The image you upload must have dimensions of ' . CORE_CROPPING_CANVAS_MAX . " x " . CORE_CROPPING_CANVAS_MAX . " pixels or larger." );
		
		// Resize down to something we can display on the page
		if ( !$canvas = bp_core_resize_avatar($original) )
			bp_core_ap_die('Could not create thumbnail.');
		
		// Render the cropper UI
		if ( !$action )
			$action = get_option('home') .'/wp-admin/admin.php?page=bp-xprofile.php';
		
		bp_core_render_avatar_cropper($original, $canvas, $action);
		
		echo '</div>';
		
	} else if ( isset($_POST['slick_avatars_action']) && $_POST['slick_avatars_action'] == 'crop' ) {
		// Crop, save, store
		
		// Confirm that the nonce is valid
		if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'slick_avatars') )
			bp_core_ap_die( 'Security error.' );
		
		if ( !bp_core_check_crop( $_POST['orig'], $_POST['canvas'] ) )
			bp_core_ap_die('Error when cropping, please go back and try again');
		
		if ( !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'] ) )
			bp_core_ap_die('Error when saving avatars, please go back and try again.');
		
		// Store details to the DB and we're done
		echo '<div class="wrap"><h2>';
		_e('Your Avatar');
		echo '</h2>';
		
		echo '<p>' . __('Your new avatar was successfully created!') . '</p>';
		
		bp_core_avatar_save($result);
		
		echo '<span class="crop-img">' . bp_core_get_avatar( get_current_user_id(), 1 ) . '</span>';
		
		if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) {
			echo '<span class="crop-img">' . bp_core_get_avatar( get_current_user_id(), 2 ) . '</span>';
		}
		
		echo '</div>';
	} else if ( isset($_GET['slick_avatars_action']) && $_GET['slick_avatars_action'] == 'delete' ) {
		// Delete an avatar

		bp_core_delete_avatar();
		
		unset($_GET['slick_avatars_action']);
		$message = __('Avatar successfully removed.');
		bp_core_avatar_admin($message);
		
	}
	?>
	<?php
}

function bp_core_check_avatar_upload($file) {
	if ( !isset($file['file']) || $file['file']['size'] == 0 )
		return false;
	
	return true;
}

function bp_core_check_avatar_size($file) {
	if ( $file['file']['size'] > CORE_MAX_FILE_SIZE )
		return false;
	
	return true;
}

function bp_core_check_avatar_type($file) {
	if ( ( strlen($file['file']['type']) && !preg_match('/(jpe?g|gif|png)$/', $file['file']['type'] ) ) && !preg_match( '/(jpe?g|gif|png)$/', $file['file']['name'] ) )
		return false;
	
	return true;
}

function bp_core_handle_avatar_upload($file) {
	$res = wp_handle_upload( $file['file'], array('action'=>'slick_avatars') );
	if ( !in_array('error', array_keys($res) ) ) {
		return $res['file'];
	} else {
		return false;
	}
}

function bp_core_check_avatar_dimensions($file) {
	$size = getimagesize($file);
	
	if ( $size[0] < CORE_AVATAR_V2_W || $size[1] < CORE_CROPPING_CANVAS_MAX )
		return false;
	
	return true;
}

function bp_core_resize_avatar($file, $size = CORE_CROPPING_CANVAS_MAX) {
	$canvas = wp_create_thumbnail( $file, $size);
	
	if ( bp_core_thumb_error($canvas) )
		return false;
	
	return $canvas = str_replace( '//', '/', $canvas );
}

function bp_core_render_avatar_cropper($original, $new, $action, $user_id = null, $no_form_tag = false, $url = false) {
	$size = getimagesize($new);
	
	if ( !$user_id )
		$user_id = get_current_user_id();

	if ( !$url ) {
		$home_base_id = get_usermeta( $user_id, 'home_base' );
		$url = get_blog_option($home_base_id, 'siteurl');
	}

	$src = str_replace( array(ABSPATH), array($url . '/'), $new );

	// Load cropper details
	
	// V1 UI
	if ( !$no_form_tag )
		echo '<form action="' . $action . '" method="post" id="avatar-cropper">';

	echo '<input type="hidden" name="slick_avatars_action" value="crop" />';
	echo '<input type="hidden" name="action" value="slick_avatars" />';
	echo '<input type="hidden" name="nonce" value="' . wp_create_nonce('slick_avatars') . '" />';
	echo '<input type="hidden" name="orig" value="' . $original . '" />';
	echo '<input type="hidden" name="canvas" value="' . $new . '" />';
	
	echo '<div id="avatar_v1">';
	echo '<h3>' . __('Main Avatar') . '</h3>';
	echo '<p>' . __('Please select the area of your photo you would like to use for your avatar') . '(' . CORE_AVATAR_V1_W . 'px x ' . CORE_AVATAR_V1_H . 'px).</p>';
	
	// Canvas
	echo '<div id="crop-v1" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="Select the area to crop" id="crop-v1-img" /></div>';
	
	// Preview
	echo '<p class="crop-preview"><strong>' . __('Crop Preview') . '</strong></p>';
	echo '<div id="crop-preview-v1" class="crop-preview"></div>';
	
	// Hidden form fields
	echo '<input type="hidden" id="v1_x1" name="v1_x1" value="" />';
	echo '<input type="hidden" id="v1_y1" name="v1_y1" value="" />';
	echo '<input type="hidden" id="v1_x2" name="v1_x2" value="" />';
	echo '<input type="hidden" id="v1_y2" name="v1_y2" value="" />';
	echo '<input type="hidden" id="v1_w" name="v1_w" value="" />';
	echo '<input type="hidden" id="v1_h" name="v1_h" value="" />';
	
	// V2 UI (optional)
	if (CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false) {
		// Continue button (v1 => v2)
		echo '<p class="submit"><input type="button" name="avatar_continue" value="' . __('Crop &amp; Continue') . '" onclick="cropAndContinue();" /></p>';
		echo '</div>';
		
		echo '<div id="avatar_v2">';
		echo '<h3>' . __('Alternate Avatar') . '</h3>';
		echo '<p>' . __('Please select the area of your photo you would like to use for an alternate version') . '(' . CORE_AVATAR_V2_W . 'px x ' . CORE_AVATAR_V2_H . 'px).</p>';
		
		// Canvas
		echo '<div id="crop-v2" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="Select the area to crop" id="crop-v2-img" /></div>';

		// Preview
		echo '<p class="crop-preview"><strong>' . __('Crop Preview') . '</strong></p>';
		echo '<div id="crop-preview-v2" class="crop-preview"></div>';

		// Hidden form fields
		echo '<input type="hidden" id="v2_x1" name="v2_x1" value="" />';
		echo '<input type="hidden" id="v2_y1" name="v2_y1" value="" />';
		echo '<input type="hidden" id="v2_x2"name="v2_x2" value="" />';
		echo '<input type="hidden" id="v2_y2"name="v2_y2" value="" />';
		echo '<input type="hidden" id="v2_w" name="v2_w" value="" />';
		echo '<input type="hidden" id="v2_h" name="v2_h" value="" />';
		
		// Final button to process everything
		echo '<p class="submit"><input type="submit" name="save" value="' . __('Crop &amp; Save') . '" /></p>';
		echo '</div>';
	} else {
		// Close out v1 DIV
		echo '</div>';
		
		// Final button to process everything
		echo '<p class="submit"><input type="submit" name="save" value="' . __('Crop &amp; Save') . '" /></p>';
	}
	
	if ( !$no_form_tag )
		echo '</form>';
	?>
	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function(){
			v1Cropper();
		});
	</script>
	<?php
}

function bp_core_check_crop( $original, $canvas ) {
	if ( is_file($original) && is_readable($original) && is_file($canvas) && is_readable($canvas) )
		return true;
	
	return false;
}

function bp_core_avatar_cropstore( $source, $canvas, $v1_x1, $v1_y1, $v1_w, $v1_h, $v2_x1, $v2_y1, $v2_w, $v2_h, $from_signup = false, $filename = 'avatar', $item_id = null ) {
	$size = getimagesize($source);
	$dims = getimagesize($canvas);

	// Figure out multiplier for scaling
	$multi = $size[0] / $dims[0];
	
	if ( $item_id )
		$filename_item_id = $item_id . '-';
	
	if ( $filename != 'avatar' ) {
		$v1_filename = '-' . $filename_item_id . $filename . '-thumb';
		$v2_filename = '-' . $filename_item_id . $filename . '-full';		
	} else {
		$v1_filename = '-avatar1';
		$v2_filename = '-avatar2';		
	}
	
	// Perform v1 crop
	$v1_dest = dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', $v1_filename . '$1', basename($source), 1);
	
	if ( $from_signup )
		$v1_out = wp_crop_image( $source, $v1_x1, $v1_y1, $v1_w, $v1_h, CORE_AVATAR_V1_W, CORE_AVATAR_V1_H, false, $v1_dest );
	else
		$v1_out = wp_crop_image( $source, ($v1_x1 * $multi), ($v1_y1 * $multi), ($v1_w * $multi), ($v1_h * $multi), CORE_AVATAR_V1_W, CORE_AVATAR_V1_H, false, $v1_dest );
		
	// Perform v2 crop
	if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) {
		$v2_dest = dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', $v2_filename . '$1', basename($source), 1);

		if ( $from_signup )
			$v2_out = wp_crop_image( $source, $v2_x1, $v2_y1, $v2_w, $v2_h, CORE_AVATAR_V2_W, CORE_AVATAR_V2_H, false, $v2_dest );
		else
			$v2_out = wp_crop_image( $source, ($v2_x1 * $multi), ($v2_y1 * $multi), ($v2_w * $multi), ($v2_h * $multi), CORE_AVATAR_V2_W, CORE_AVATAR_V2_H, false, $v2_dest );
	}

	// Clean up canvas and original images used during cropping
	foreach ( array( str_replace( '..', '', $source ), str_replace( '..', '', $canvas) ) as $f ) {
		@unlink($f);
	}
	
	$dir = $source;

	do {
		$dir = dirname($dir);
		@rmdir($dir); // will fail on non-empty directories
	} while ( substr_count($dir, '/') >= 2 && stristr($dir, ABSPATH) );
	
	return array('v1_out' => $v1_out, 'v2_out' => $v2_out);
}

function bp_core_avatar_save( $vars, $user_id = false, $upload_dir = false, $url = false ) {
	if ( !$user_id )
		$user_id = get_current_user_id();
		
	if ( !$url ) {
		$home_base_id = get_usermeta( $user_id, 'home_base' );
		$url = get_blog_option($home_base_id, 'siteurl');
	}
	
	$old = get_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	$v1_href = str_replace( array(ABSPATH), array($src), $vars['v1_out'] );
	update_usermeta( $user_id, 'bp_core_avatar_v1', $v1_href );
	update_usermeta( $user_id, 'bp_core_avatar_v1_path', $vars['v1_out'] );
	@unlink($old); // Removing old avatar
	
	if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) {
		$old = get_usermeta( $user_id, 'bp_core_avatar_v2_path' );
		$v2_href = str_replace( array(ABSPATH), array($src), $vars['v2_out'] );
		update_usermeta( $user_id, 'bp_core_avatar_v2', $v2_href );
		update_usermeta( $user_id, 'bp_core_avatar_v2_path', $vars['v2_out'] );
		@unlink($old); // Removing old avatar
	}
}

function bp_core_render_avatar_upload_form($action, $no_form_tag = false) {
	if ( !$no_form_tag ) { ?>
	<form method="post" action="<?php echo $action ?>" enctype="multipart/form-data" id="avatar-upload">
<?php } ?>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo CORE_MAX_FILE_SIZE; ?>" />
		<input type="hidden" name="slick_avatars_action" value="upload" />
		<input type="hidden" name="action" value="slick_avatars" />
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('slick_avatars'); ?>" />
		<input type="file" name="file" id="file" />
		<input type="submit" name="upload" id="upload" value="Upload Photo" />
<?php if ( !$no_form_tag ) { ?>
	</form>
<?php
	}	
}

function bp_core_delete_avatar() {
	$old_v1 = get_usermeta( get_current_user_id(), 'bp_core_avatar_v1_path' );
	$old_v2 = get_usermeta( get_current_user_id(), 'bp_core_avatar_v2_path' );
	
	delete_usermeta( get_current_user_id(), 'bp_core_avatar_v1_path' );
	delete_usermeta( get_current_user_id(), 'bp_core_avatar_v2_path' );
	
	delete_usermeta( get_current_user_id(), 'bp_core_avatar_v1' );
	delete_usermeta( get_current_user_id(), 'bp_core_avatar_v2' );
	
	// Remove the actual images
	@unlink($old_v1);
	@unlink($old_v2);
}

function bp_core_ap_die( $msg ) {
	echo '<p><strong>' . $msg . '</strong></p>';
	echo '<p><a href="' . get_option('home') .'/wp-admin/admin.php?page=bp-xprofile.php">' . __('Try Again') . '</a></p>';
	echo '</div>';
	exit;
}

function bp_core_thumb_error( $str ) {
	if ( !is_string($str) ) {
		return false;
	} else {
		return preg_match( '/(filetype|invalid|not found)/is', $str );
	}
}

function bp_core_add_cropper_js() {
	echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/prototype.js"></script>';
	echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/scriptaculous/scriptaculous.js"></script>';
	echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/scriptaculous/dragdrop.js"></script>';
	echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/crop/cropper.js"></script>';		
?>
	<style type="text/css">
		#avatar_v2 { display: none; }
		.crop-img { float: left; margin: 0 20px 15px 0; }
		.submit { clear: left; }
	</style>

	<script type="text/javascript">
	function cropAndContinue() {
		jQuery('#avatar_v1').slideUp();
		jQuery('#avatar_v2').slideDown('normal', function(){
			v2Cropper();
		});
	}

	function v1Cropper() {
		v1Crop = new Cropper.ImgWithPreview( 
			'crop-v1-img',
			{ 
				ratioDim: { x: <?php echo round(CORE_AVATAR_V1_W / CORE_AVATAR_V1_H, 5); ?>, y: 1 },
				minWidth:   <?php echo CORE_AVATAR_V1_W; ?>,
				minHeight:  <?php echo CORE_AVATAR_V1_H; ?>,
				prevWidth:  <?php echo CORE_AVATAR_V1_W; ?>,
				prevHeight: <?php echo CORE_AVATAR_V1_H; ?>,
				onEndCrop: onEndCropv1,
				previewWrap: 'crop-preview-v1'
			}
		);
	}

	function onEndCropv1(coords, dimensions) {
		jQuery('#v1_x1').val(coords.x1);
		jQuery('#v1_y1').val(coords.y1);
		jQuery('#v1_x2').val(coords.x2);
		jQuery('#v1_y2').val(coords.y2);
		jQuery('#v1_w').val(dimensions.width);
		jQuery('#v1_h').val(dimensions.height);
	}

	<?php if (CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false) { ?>
	function v2Cropper() {
		v1Crop = new Cropper.ImgWithPreview( 
			'crop-v2-img',
			{ 
				ratioDim: { x: <?php echo round(CORE_AVATAR_V2_W / CORE_AVATAR_V2_H, 5); ?>, y: 1 },
				minWidth:   <?php echo CORE_AVATAR_V2_W; ?>,
				minHeight:  <?php echo CORE_AVATAR_V2_H; ?>,
				prevWidth:  <?php echo CORE_AVATAR_V2_W; ?>,
				prevHeight: <?php echo CORE_AVATAR_V2_H; ?>,
				onEndCrop: onEndCropv2,
				previewWrap: 'crop-preview-v2'
			}
		);
	}
	<?php } ?>

	function onEndCropv2(coords, dimensions) {
		jQuery('#v2_x1').val(coords.x1);
		jQuery('#v2_y1').val(coords.y1);
		jQuery('#v2_x2').val(coords.x2);
		jQuery('#v2_y2').val(coords.y2);
		jQuery('#v2_w').val(dimensions.width);
		jQuery('#v2_h').val(dimensions.height);
	}
	</script>
	<?php
}

?>