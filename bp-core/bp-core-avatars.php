<?php
/*
Based on contributions from: Beau Lebens - http://www.dentedreality.com.au/
Modified for BuddyPress by: Andy Peatling - http://apeatling.wordpress.com/
*/

/* Make sure we have the core WordPress files we need */
require_once( ABSPATH . '/wp-admin/includes/image.php' );
require_once( ABSPATH . '/wp-admin/includes/file.php' );

/* Define settings for avatars. [TODO] This will eventually end up as admin configurable settings */
define( 'CORE_AVATAR_V1_W', apply_filters( 'bp_core_avatar_v1_w', 50 ) );
define( 'CORE_AVATAR_V1_H', apply_filters( 'bp_core_avatar_v1_h', 50 ) );
define( 'CORE_AVATAR_V2_W', apply_filters( 'bp_core_avatar_v2_w', 150 ) );
define( 'CORE_AVATAR_V2_H', apply_filters( 'bp_core_avatar_v2_h', 150 ) );
define( 'CORE_CROPPING_CANVAS_MAX', apply_filters( 'bp_core_avatar_cropping_canvas_max', 450 ) );
define( 'CORE_MAX_FILE_SIZE', get_site_option('fileupload_maxk') * 1024 );
define( 'CORE_DEFAULT_AVATAR', apply_filters( 'bp_core_avatar_default_src', BP_PLUGIN_URL . '/bp-xprofile/images/none.gif' ) );
define( 'CORE_DEFAULT_AVATAR_THUMB', apply_filters( 'bp_core_avatar_default_thumb_src', BP_PLUGIN_URL . '/bp-xprofile/images/none-thumbnail.gif' ) );

function bp_core_get_avatar( $user, $version = 1, $width = null, $height = null, $no_tag = false ) {
	global $bp, $current_blog;

	if ( !is_int($version) )
		$version = (int) $version;
		
	if ( CORE_AVATAR_V2_W == false && CORE_AVATAR_V2_H == false )
		$version = 1;
	
	if ( !$width )
		$width = constant('CORE_AVATAR_V' . $version . '_W');
	
	if ( !$height )
		$height = constant('CORE_AVATAR_V' . $version . '_H');		
	
	$avatar_file = wp_cache_get( 'bp_core_avatar_v' . $version . '_u' . $user, 'bp' );
	if ( false === $avatar_file ) {
		$avatar_file = get_usermeta( $user, 'bp_core_avatar_v' . $version );
		wp_cache_set( 'bp_core_avatar_v' . $version . '_u' . $user, $avatar_file, 'bp' );
	}
	
	$url = $bp->root_domain . '/' . $avatar_file;
	
	if ( strlen($avatar_file) ) {
		if ( $no_tag )
			return $url;
		else
			return apply_filters( 'bp_core_get_avatar', '<img src="' . $url . '" alt="" class="avatar photo" width="' . $width . '" height="' . $height . '" />', $user, $version, $width, $height, $no_tag );
	} else {
		$ud = get_userdata($user);
		$grav_option = get_site_option('user-avatar-default');
		
		if ( empty( $grav_option ) ) {
			$default_grav = 'wavatar';
		} else if ( 'mystery' == $grav_option ) {
			$default_grav = BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg';
		} else {
			$default_grav = $grav_option;
		}
		
		$gravatar = 'http://www.gravatar.com/avatar/' . md5( $ud->user_email ) . '?d=' . $default_grav . '&amp;s=';
		if ( $no_tag )
			return apply_filters( 'bp_core_get_avatar', $gravatar . constant('CORE_AVATAR_V' . $version . '_W'), $user, $version, $width, $height, $no_tag );
		else
			return apply_filters( 'bp_core_get_avatar', '<img src="' . $gravatar . constant('CORE_AVATAR_V' . $version . '_W') . '" alt="" class="avatar" width="' . $width . '" height="' . $height . '" />', $user, $version, $width, $height, $no_tag );
	}
}

// Override internal "get_avatar()" function to use our own where possible
// WARNING: Does NOT apply size restrictions
function bp_core_get_avatar_filter( $avatar, $id_or_email, $size, $default ) {
	$str = '';
	$ver = ( 1 == $size || 2 == $size ) ? $size : 1;
	
	if ( !CORE_AVATAR_V2_W && !CORE_AVATAR_V2_H )
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
function bp_core_avatar_admin( $message = null, $action, $delete_action) {
	global $wp_upload_error;
	?>	
	<?php if ( !isset($_POST['slick_avatars_action']) && !isset($_GET['slick_avatars_action']) ) { ?>		
		<?php if ( $message ) { ?>
			<br />
			<div id="message" class="updated fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>

		<p><?php _e('Your avatar will be used on your profile and throughout the site.', 'buddypress') ?></p>
		<p><?php _e('Click below to select a JPG, GIF or PNG format photo from your computer and then click \'Upload Photo\' to proceed.', 'buddypress') ?></p>
		
		<?php

		bp_core_render_avatar_upload_form($action);

		$str = bp_core_get_avatar( get_current_user_id(), 1 );
		if ( strlen($str) ) {
			echo '<h3>' . __('This is your current avatar', 'buddypress') . '</h3>';
			echo '<span class="crop-img avatar">' . bp_core_get_avatar(get_current_user_id(), 1) . '</span>';
			echo '<span class="crop-img avatar">' . bp_core_get_avatar(get_current_user_id(), 2) . '</span>';
			echo '<a href="' .  wp_nonce_url( $delete_action, 'bp_delete_avatar_link' ) . '">' . __( 'Delete', 'buddypress' ) . '</a>';
		}

	} else if ( isset($_POST['slick_avatars_action']) && 'upload' == $_POST['slick_avatars_action'] ) {
	
		// Confirm that the nonce is valid
		if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'slick_avatars') )
			bp_core_ap_die( 'Security error.' );
		
		// Set friendly error feedback.
		$uploadErrors = array(
		        0 => __("There is no error, the file uploaded with success", 'buddypress'), 
		        1 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(CORE_MAX_FILE_SIZE), 
		        2 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(CORE_MAX_FILE_SIZE),
		        3 => __("The uploaded file was only partially uploaded", 'buddypress'),
		        4 => __("No file was uploaded", 'buddypress'),
		        6 => __("Missing a temporary folder", 'buddypress')
		);

		if ( !bp_core_check_avatar_upload($_FILES) )
			bp_core_ap_die( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'buddypress' ), $uploadErrors[$_FILES['file']['error']] ) );
		
		if ( !bp_core_check_avatar_size($_FILES) )
			bp_core_ap_die( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'buddypress'), size_format(CORE_MAX_FILE_SIZE) ) );

		if ( !bp_core_check_avatar_type($_FILES) )
			bp_core_ap_die( __( 'Please upload only JPG, GIF or PNG photos.', 'buddypress' ) );
		
		// "Handle" upload into temporary location
		if ( !$original = bp_core_handle_avatar_upload($_FILES) )
			bp_core_ap_die( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $wp_upload_error ) );
		
		// Resize down to something we can display on the page or use original if its small enough already.
		if ( !$canvas = bp_core_resize_avatar($original) )
			$canvas = $original;
		
		// Render the cropper UI		
		bp_core_render_avatar_cropper($original, $canvas, $action);

	} else if ( isset($_POST['slick_avatars_action']) && 'crop' == $_POST['slick_avatars_action'] ) {
		// Crop, save, store
		
		// Confirm that the nonce is valid
		if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'slick_avatars') )
			bp_core_ap_die( __( 'Security error.', 'buddypress' ) );
		
		if ( !bp_core_check_crop( $_POST['orig'], $_POST['canvas'] ) )
			bp_core_ap_die( __( 'Error when cropping, please go back and try again', 'buddypress' ) );
		
		if ( !$result = bp_core_avatar_cropstore( stripslashes($_POST['orig']), $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'] ) )
			bp_core_ap_die( __( 'Error when saving avatars, please go back and try again.', 'buddypress' ) );
		
		// Store details to the DB and we're done		
		echo '<p>' . __('Your new avatar was successfully created!', 'buddypress') . '</p>';
		
		bp_core_avatar_save($result);
		
		echo '<span class="crop-img">' . bp_core_get_avatar( get_current_user_id(), 1 ) . '</span>';
		
		if ( CORE_AVATAR_V2_W && CORE_AVATAR_V2_H ) {
			echo '<span class="crop-img">' . bp_core_get_avatar( get_current_user_id(), 2 ) . '</span>';
		}

	} else if ( isset($_GET['slick_avatars_action']) && 'delete' == $_GET['slick_avatars_action'] ) {
		// Delete an avatar

		bp_core_delete_avatar();
		
		unset($_GET['slick_avatars_action']);
		$message = __('Avatar successfully removed.', 'buddypress');
		bp_core_avatar_admin($message);
		
	}
	?>
	<?php
}

function bp_core_check_avatar_upload($file) {
	if ( $file['error'] )
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
	global $wp_upload_error;
	
	// Change the upload file location to /avatars/user_id
	add_filter( 'upload_dir', 'bp_core_avatar_upload_dir' );
	
	$res = wp_handle_upload( $file['file'], array('action'=>'slick_avatars') );
		
	if ( !in_array('error', array_keys($res) ) ) {
		return $res['file'];
	} else {
		$wp_upload_error = $res['error'];
		return false;
	}
}

function bp_core_avatar_upload_dir( $upload, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	$path  = get_blog_option( 1, 'upload_path' );
	$newdir = path_join( ABSPATH, $path );
	$newdir .= '/avatars/' . $user_id;

	$newbdir = $newdir;
	
	@wp_mkdir_p( $newdir );

	$newurl = trailingslashit( get_blog_option( 1, 'siteurl' ) ) . '/avatars/' . $user_id;
	$newburl = $newurl;
	$newsubdir = '/avatars/' . $user_id;

	return apply_filters( 'bp_core_avatar_upload_dir', array( 'path' => $newdir, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

function bp_core_check_avatar_dimensions($file) {
	$size = getimagesize($file);

	if ( $size[0] < (int)CORE_AVATAR_V2_W || $size[1] < (int)CORE_CROPPING_CANVAS_MAX )
		return false;
	
	return true;
}

function bp_core_resize_avatar( $file, $size = false ) {
	
	if ( !$size )
		$size = CORE_CROPPING_CANVAS_MAX;

	$canvas = wp_create_thumbnail( $file, $size );
	
	if ( $canvas->errors )
		return false;
	
	return $canvas = str_replace( '//', '/', $canvas );
}

function bp_core_render_avatar_cropper( $original, $new, $action, $user_id = null, $no_form_tag = false, $url = false ) {
	global $bp;
	
	$size = getimagesize($new);
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	$src = str_replace( array(ABSPATH), array(site_url() . '/'), $new );

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
	echo '<h3>' . __( 'Please Crop Your Avatar!', 'buddypress' ) . '</h3>';
	echo '<h4>' . __('Thumbnail Avatar', 'buddypress') . '</h4>';
	echo '<p>' . __('Please crop a small version of your avatar to use for thumbnails.', 'buddypress') . '</p>';
	
	// Canvas
	echo '<div id="crop-v1" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="' . __( 'Select the area to crop', 'buddypress' ) . '" id="crop-v1-img" /></div>';
	
	// Preview
	echo '<p class="crop-preview"><strong>' . __('Crop Preview', 'buddypress') . '</strong></p>';
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
		echo '<p class="submit"><input type="button" name="avatar_continue" id="avatar_continue" value="' . __('Crop Thumbnail &amp; Continue', 'buddypress') . '" onclick="cropAndContinue();" /></p>';
		echo '</div>';
		
		echo '<div id="avatar_v2" style="display: none">';
		echo '<h4>' . __('Full Size Avatar', 'buddypress') . '</h4>';
		echo '<p>' . __('Please crop a full size version of your avatar.', 'buddypress') . '</p>';
		
		// Canvas
		echo '<div id="crop-v2" class="crop-img"><img src="' . $src . '" ' . $size[3] . ' border="0" alt="' . __('Select the area to crop', 'buddypress' ) . '" id="crop-v2-img" /></div>';

		// Preview
		echo '<p class="crop-preview"><strong>' . __('Crop Preview', 'buddypress') . '</strong></p>';
		echo '<div id="crop-preview-v2" class="crop-preview"></div>';

		// Hidden form fields
		echo '<input type="hidden" id="v2_x1" name="v2_x1" value="" />';
		echo '<input type="hidden" id="v2_y1" name="v2_y1" value="" />';
		echo '<input type="hidden" id="v2_x2"name="v2_x2" value="" />';
		echo '<input type="hidden" id="v2_y2"name="v2_y2" value="" />';
		echo '<input type="hidden" id="v2_w" name="v2_w" value="" />';
		echo '<input type="hidden" id="v2_h" name="v2_h" value="" />';
		
		// Final button to process everything
		echo '<p class="submit"><input type="submit" id="crop-complete" name="save" value="' . __('Crop Full Size &amp; Save', 'buddypress') . '" /></p>';
		echo '</div>';
	} else {
		// Close out v1 DIV
		echo '</div>';
		
		// Final button to process everything
		echo '<p class="submit"><input type="submit" name="save" value="' . __('Crop Full Size &amp; Save', 'buddypress') . '" /></p>';
	}
	
	do_action( 'bp_core_render_avatar_cropper', $original, $new, $action );
	
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
	
	$v1_filename = apply_filters( 'bp_avatar_v1_filename', $v1_filename );
	$v2_filename = apply_filters( 'bp_avatar_v2_filename', $v2_filename );
	
	// Perform v1 crop
	$v1_dest = apply_filters( 'bp_avatar_v1_dest', dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', $v1_filename . '$1', basename($source), 1), $source ); 
	
	if ( $from_signup )
		$v1_out = wp_crop_image( $source, $v1_x1, $v1_y1, $v1_w, $v1_h, CORE_AVATAR_V1_W, CORE_AVATAR_V1_H, false, $v1_dest );
	else
		$v1_out = wp_crop_image( $source, ($v1_x1 * $multi), ($v1_y1 * $multi), ($v1_w * $multi), ($v1_h * $multi), CORE_AVATAR_V1_W, CORE_AVATAR_V1_H, false, $v1_dest );
		
	// Perform v2 crop
	if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) {
		$v2_dest = apply_filters( 'bp_avatar_v2_dest', dirname($source) . '/' . preg_replace('!(\.[^.]+)?$!', $v2_filename . '$1', basename($source), 1), $source );

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
	
	return apply_filters( 'bp_core_avatar_cropstore', array('v1_out' => $v1_out, 'v2_out' => $v2_out) );
}

function bp_core_avatar_save( $vars, $user_id = false ) {
	if ( !$user_id )
		$user_id = get_current_user_id();
	
	$old = get_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	$v1_href = apply_filters( 'bp_avatar_v1_href', str_replace( array(ABSPATH), array($src), $vars['v1_out'] ), $src, $vars['v1_out'] );
	update_usermeta( $user_id, 'bp_core_avatar_v1', $v1_href );
	update_usermeta( $user_id, 'bp_core_avatar_v1_path', $vars['v1_out'] );
	@unlink($old); // Removing old avatar
	
	if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) {
		$old = get_usermeta( $user_id, 'bp_core_avatar_v2_path' );
		$v2_href = apply_filters( 'bp_avatar_v2_href', str_replace( array(ABSPATH), array($src), $vars['v2_out'] ), $src, $vars['v2_out'] );
		update_usermeta( $user_id, 'bp_core_avatar_v2', $v2_href );
		update_usermeta( $user_id, 'bp_core_avatar_v2_path', $vars['v2_out'] );
		@unlink($old); // Removing old avatar
	}
	
	do_action( 'bp_core_avatar_save', $user_id, $old, $v1_href, $vars['v1_out'] );
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
		<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Photo', 'buddypress' ) ?>"/>
		
		<?php do_action( 'bp_core_render_avatar_upload_form' ) ?>
		
<?php if ( !$no_form_tag ) { ?>
	</form>
<?php
	}
}

function bp_core_delete_avatar() {
	$user_id = get_current_user_id();
	
	$old_v1 = get_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	$old_v2 = get_usermeta( $user_id, 'bp_core_avatar_v2_path' );
	
	delete_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2_path' );
	
	delete_usermeta( $user_id, 'bp_core_avatar_v1' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2' );
	
	// Remove the actual images
	@unlink($old_v1);
	@unlink($old_v2);
	
	do_action( 'bp_core_delete_avatar', $user_id, $old_v1, $old_v2 );
}

function bp_core_ap_die( $msg ) {
	global $bp;
	echo '<p><strong>' . $msg . '</strong></p>';
	echo '<p><a href="' . $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar">' . __('Try Again', 'buddypress') . '</a></p>';
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

?>