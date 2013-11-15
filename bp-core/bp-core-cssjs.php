<?php
/**
 * Core component CSS & JS.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Load the JS for "Are you sure?" .confirm links.
 */
function bp_core_confirmation_js() {

	if ( is_multisite() && ! bp_is_root_blog() )
		return false;

	if ( !wp_script_is( 'jquery' ) )
		wp_enqueue_script( 'jquery' );

	if ( !wp_script_is( 'jquery', 'done' ) )
		wp_print_scripts( 'jquery' ); ?>

	<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( 'a.confirm').click( function() {
				if ( confirm( '<?php _e( 'Are you sure?', 'buddypress' ) ?>' ) )
					return true; else return false;
			});
		});
	</script>

<?php
}
add_action( 'wp_head',    'bp_core_confirmation_js', 100 );
add_action( 'admin_head', 'bp_core_confirmation_js', 100 );

/**
 * Enqueues jCrop library and hooks BP's custom cropper JS.
 */
function bp_core_add_jquery_cropper() {
	wp_enqueue_style( 'jcrop' );
	wp_enqueue_script( 'jcrop', array( 'jquery' ) );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_js' );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_css' );
}

/**
 * Output the inline JS needed for the cropper to work on a per-page basis.
 */
function bp_core_add_cropper_inline_js() {

	// Bail if no image was uploaded
	$image = apply_filters( 'bp_inline_cropper_image', getimagesize( bp_core_avatar_upload_path() . buddypress()->avatar_admin->image->dir ) );
	if ( empty( $image ) )
		return;

	//
	$full_height = bp_core_avatar_full_height();
	$full_width  = bp_core_avatar_full_width();

	// Calculate Aspect Ratio
	if ( !empty( $full_height ) && ( $full_width != $full_height ) ) {
		$aspect_ratio = $full_width / $full_height;
	} else {
		$aspect_ratio = 1;
	}

	// Default cropper coordinates
	$crop_left   = round( $image[0] / 4 );
	$crop_top    = round( $image[1] / 4 );
	$crop_right  = $image[0] - $crop_left;
	$crop_bottom = $image[1] - $crop_top; ?>

	<script type="text/javascript">
		jQuery(window).load( function(){
			jQuery('#avatar-to-crop').Jcrop({
				onChange: showPreview,
				onSelect: showPreview,
				onSelect: updateCoords,
				aspectRatio: <?php echo $aspect_ratio; ?>,
				setSelect: [ <?php echo $crop_left; ?>, <?php echo $crop_top; ?>, <?php echo $crop_right; ?>, <?php echo $crop_bottom; ?> ]
			});
			updateCoords({x: <?php echo $crop_left; ?>, y: <?php echo $crop_top; ?>, w: <?php echo $crop_right; ?>, h: <?php echo $crop_bottom; ?>});
		});

		function updateCoords(c) {
			jQuery('#x').val(c.x);
			jQuery('#y').val(c.y);
			jQuery('#w').val(c.w);
			jQuery('#h').val(c.h);
		}

		function showPreview(coords) {
			if ( parseInt(coords.w) > 0 ) {
				var fw = <?php echo $full_width; ?>;
				var fh = <?php echo $full_height; ?>;
				var rx = fw / coords.w;
				var ry = fh / coords.h;

				jQuery( '#avatar-crop-preview' ).css({
					width: Math.round(rx * <?php echo $image[0]; ?>) + 'px',
					height: Math.round(ry * <?php echo $image[1]; ?>) + 'px',
					marginLeft: '-' + Math.round(rx * coords.x) + 'px',
					marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
			}
		}
	</script>

<?php
}

/**
 * Output the inline CSS for the BP image cropper.
 *
 * @package BuddyPress Core
 */
function bp_core_add_cropper_inline_css() {
?>

	<style type="text/css">
		.jcrop-holder { float: left; margin: 0 20px 20px 0; text-align: left; }
		#avatar-crop-pane { width: <?php echo bp_core_avatar_full_width() ?>px; height: <?php echo bp_core_avatar_full_height() ?>px; overflow: hidden; }
		#avatar-crop-submit { margin: 20px 0; }
		.jcrop-holder img,
		#avatar-crop-pane img,
		#avatar-upload-form img,
		#create-group-form img,
		#group-settings-form img { border: none !important; max-width: none !important; }
	</style>

<?php
}

/**
 * Define the 'ajaxurl' JS variable, used by themes as an AJAX endpoint.
 *
 * @since BuddyPress (1.1.0)
 */
function bp_core_add_ajax_url_js() {
?>

	<script type="text/javascript">var ajaxurl = '<?php echo bp_core_ajax_url(); ?>';</script>

<?php
}
add_action( 'wp_head', 'bp_core_add_ajax_url_js' );

/**
 * Get the proper value for BP's ajaxurl.
 *
 * Designed to be sensitive to FORCE_SSL_ADMIN and non-standard multisite
 * configurations.
 *
 * @since BuddyPress (1.7.0)
 *
 * @return string AJAX endpoint URL.
 */
function bp_core_ajax_url() {
	return apply_filters( 'bp_core_ajax_url', admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' ) );
}
