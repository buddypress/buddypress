<?php
if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));

function bp_add_avatar_cssjs() {
	global $current_action;
	
	if ( $current_action == 'change-avatar' ) {
		echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/prototype.js"></script>';
		echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/scriptaculous/scriptaculous.js"></script>';
		echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/scriptaculous/dragdrop.js"></script>';
		echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/crop/cropper.js"></script>';
		echo '<script type="text/javascript" src="' . get_option('home') . '/wp-includes/js/jquery/jquery.js"></script>';
		
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
					ratioDim: { x: <?php echo round(XPROFILE_AVATAR_V1_W / XPROFILE_AVATAR_V1_H, 5); ?>, y: 1 },
					minWidth:   <?php echo XPROFILE_AVATAR_V1_W; ?>,
					minHeight:  <?php echo XPROFILE_AVATAR_V1_H; ?>,
					prevWidth:  <?php echo XPROFILE_AVATAR_V1_W; ?>,
					prevHeight: <?php echo XPROFILE_AVATAR_V1_H; ?>,
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

		<?php if (XPROFILE_AVATAR_V2_W !== false && XPROFILE_AVATAR_V2_H !== false) { ?>
		function v2Cropper() {
			v1Crop = new Cropper.ImgWithPreview( 
				'crop-v2-img',
				{ 
					ratioDim: { x: <?php echo round(XPROFILE_AVATAR_V2_W / XPROFILE_AVATAR_V2_H, 5); ?>, y: 1 },
					minWidth:   <?php echo XPROFILE_AVATAR_V2_W; ?>,
					minHeight:  <?php echo XPROFILE_AVATAR_V2_H; ?>,
					prevWidth:  <?php echo XPROFILE_AVATAR_V2_W; ?>,
					prevHeight: <?php echo XPROFILE_AVATAR_V2_H; ?>,
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
}
add_action( 'wp_head', 'bp_add_avatar_cssjs' );
?>