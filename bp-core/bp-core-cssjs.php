<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_core_confirmation_js() {
	global $wpdb;

	if ( is_multisite() && $wpdb->blogid != bp_get_root_blog_id() )
		return false;

	if ( !wp_script_is( 'jquery' ) )
		wp_enqueue_script( 'jquery' );

	if ( !wp_script_is( 'jquery', 'done' ) )
		wp_print_scripts( 'jquery' ); ?>

	<script type="text/javascript"> jQuery(document).ready( function() { jQuery("a.confirm").click( function() { if ( confirm( '<?php _e( 'Are you sure?', 'buddypress' ) ?>' ) ) return true; else return false; }); });</script>

<?php
}
add_action( 'wp_head', 'bp_core_confirmation_js', 100 );

/**
 * bp_core_add_jquery_cropper()
 *
 * Makes sure the jQuery jCrop library is loaded.
 *
 * @package BuddyPress Core
 */
function bp_core_add_jquery_cropper() {
	wp_enqueue_script( 'jcrop', array( 'jquery' ) );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_js' );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_css' );
}

/**
 * bp_core_add_cropper_inline_js()
 *
 * Adds the inline JS needed for the cropper to work on a per-page basis.
 *
 * @package BuddyPress Core
 */
function bp_core_add_cropper_inline_js() {
	global $bp;

	$image = apply_filters( 'bp_inline_cropper_image', getimagesize( bp_core_avatar_upload_path() . $bp->avatar_admin->image->dir ) );
	$aspect_ratio = 1;

	$full_height = bp_core_avatar_full_height();
	$full_width  = bp_core_avatar_full_width();

	// Calculate Aspect Ratio
	if ( $full_height && ( $full_width != $full_height ) )
		$aspect_ratio = $full_width / $full_height;

	$width  = $image[0] / 2;
	$height = $image[1] / 2; ?>

	<script type="text/javascript">
		jQuery(window).load( function(){
			jQuery('#avatar-to-crop').Jcrop({
				onChange: showPreview,
				onSelect: showPreview,
				onSelect: updateCoords,
				aspectRatio: <?php echo $aspect_ratio ?>,
				setSelect: [ 50, 50, <?php echo $width ?>, <?php echo $height ?> ]
			});
			updateCoords({x: 50, y: 50, w: <?php echo $width ?>, h: <?php echo $height ?>});
		});

		function updateCoords(c) {
			jQuery('#x').val(c.x);
			jQuery('#y').val(c.y);
			jQuery('#w').val(c.w);
			jQuery('#h').val(c.h);
		}

		function showPreview(coords) {
			if ( parseInt(coords.w) > 0 ) {
				var rx = <?php echo $full_width; ?> / coords.w;
				var ry = <?php echo $full_height; ?> / coords.h;
				<?php if ( $image ) : ?>
				var w  = <?php echo $image[0]; ?>;
				var h  = <?php echo $image[1]; ?>;
				<?php endif; ?>

				jQuery('#avatar-crop-preview').css({
				<?php if ( $image ) : ?>
					width: Math.round(rx * w) + 'px',
					height: Math.round(ry * h) + 'px',
				<?php endif; ?>
					marginLeft: '-' + Math.round(rx * coords.x) + 'px',
					marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
			}
		}
	</script>

<?php
}

/**
 * bp_core_add_cropper_inline_css()
 *
 * Adds the inline CSS needed for the cropper to work on a per-page basis.
 *
 * @package BuddyPress Core
 */
function bp_core_add_cropper_inline_css() {
?>

	<style type="text/css">
		.jcrop-holder { float: left; margin: 0 20px 20px 0; text-align: left; }
		.jcrop-vline, .jcrop-hline { font-size: 0; position: absolute; background: white top left repeat url('<?php echo BP_PLUGIN_URL ?>/bp-core/images/Jcrop.gif'); }
		.jcrop-vline { height: 100%; width: 1px !important; }
		.jcrop-hline { width: 100%; height: 1px !important; }
		.jcrop-handle { font-size: 1px; width: 7px !important; height: 7px !important; border: 1px #eee solid; background-color: #333; *width: 9px; *height: 9px; }
		.jcrop-tracker { width: 100%; height: 100%; }
		.custom .jcrop-vline, .custom .jcrop-hline { background: yellow; }
		.custom .jcrop-handle { border-color: black; background-color: #C7BB00; -moz-border-radius: 3px; -webkit-border-radius: 3px; }
		#avatar-crop-pane { width: <?php echo bp_core_avatar_full_width() ?>px; height: <?php echo bp_core_avatar_full_height() ?>px; overflow: hidden; }
		#avatar-crop-submit { margin: 20px 0; }
		#avatar-upload-form img, #create-group-form img, #group-settings-form img { border: none !important; }
	</style>

<?php
}

/**
 * bp_core_add_ajax_url_js()
 *
 * Adds AJAX target URL so themes can access the WordPress AJAX functionality.
 *
 * @since 1.1
 */
function bp_core_add_ajax_url_js() {
?>

	<script type="text/javascript">var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';</script>

<?php
}
add_action( 'wp_head', 'bp_core_add_ajax_url_js' );

?>