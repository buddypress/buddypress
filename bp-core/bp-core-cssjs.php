<?php
/**
 * bp_core_add_js()
 *
 * Add the JS required by the core, as well as shared JS used by other components.
 * [TODO] This needs to use wp_enqueue_script()
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-livequery-pack', site_url() . "/wp-content/mu-plugins/bp-core/js/jquery/jquery.livequery.pack.js", 'jquery' );
	wp_enqueue_script( 'bp-general-js', site_url() . '/wp-content/mu-plugins/bp-core/js/general.js' );
}
add_action( 'wp', 'bp_core_add_js' );

/**
 * bp_core_add_ajax_js()
 *
 * Add the reference to ajaxurl used by all ajax functionality in BuddyPress.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_ajax_js() {
	echo 
'<script type="text/javascript">var ajaxurl = "' . site_url() . '/wp-content/mu-plugins/bp-core/bp-core-ajax-handler.php";</script>
';
}
add_action( 'wp_head', 'bp_core_add_ajax_js' );

/**
 * bp_core_add_css()
 *
 * Add the CSS required by all BP components, regardless of the current theme.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_css() {
	if ( is_user_logged_in() ) {
		wp_enqueue_style( 'bp-admin-bar', site_url() . '/wp-content/mu-plugins/bp-core/css/admin-bar.css' );
	}
	
	/* If you want custom css styles, include a custom-styles.css file in /bp-core/css/custom-styles.css */
	if ( file_exists(ABSPATH . MUPLUGINDIR . '/bp-core/css/custom-styles.css') )
		wp_enqueue_style( 'bp-core-custom-styles', site_url() . MUPLUGINDIR . '/bp-core/css/custom-styles.css' );		
	
	wp_print_styles();
}
add_action( 'wp_head', 'bp_core_add_css' );


/**
 * bp_core_add_admin_js()
 *
 * Add the JS needed for all components in the admin area.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_admin_js() {
	if ( strpos( $_GET['page'], 'bp-core' ) !== false ) {
		wp_enqueue_script( 'bp-account-admin-js', site_url() . '/wp-content/mu-plugins/bp-core/js/account-admin.js' );
	}
	
	if ( strpos( $_GET['page'], 'bp-core/admin-mods' ) !== false ) {
		wp_enqueue_script('password-strength-meter');
	}

	if ( strpos( $_GET['page'], 'bp-core/homebase-creation' ) !== false ) {
		wp_enqueue_script('prototype');
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('cropper');
		add_action( 'admin_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'admin_menu', 'bp_core_add_admin_js' );


/**
 * bp_core_add_admin_css()
 *
 * Add the CSS needed for all components in the admin area.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_admin_css() {
	if ( strpos( $_GET['page'], 'bp-core/homebase-creation' ) !== false ) {
		wp_enqueue_style( 'bp-core-home-base-css', site_url() . '/wp-content/mu-plugins/bp-core/css/home-base.css' );
	}
}
add_action( 'admin_menu', 'bp_core_add_admin_css' );


/**
 * bp_core_add_cropper_js()
 *
 * Adds the JS needed for general avatar cropping.
 * 
 * @package BuddyPress Core
 */
function bp_core_add_cropper_js() { 
?>
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