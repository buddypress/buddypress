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
	wp_enqueue_script( 'jquery-livequery-pack', BP_PLUGIN_URL . '/bp-core/js/jquery/jquery.livequery.pack.js', 'jquery' );
	wp_enqueue_script( 'bp-general-js', BP_PLUGIN_URL . '/bp-core/js/general.js' );
}
add_action( 'wp', 'bp_core_add_js' );
add_action( 'admin_menu', 'bp_core_add_js' );

/**
 * bp_core_add_ajax_js()
 *
 * Add the reference to ajaxurl used by all ajax functionality in BuddyPress.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_ajax_js() {
	global $bp;
	
	echo 
'<script type="text/javascript">var ajaxurl = "' . $bp->root_domain . str_replace( 'index.php', 'wp-load.php', $_SERVER['SCRIPT_NAME'] ) . '";</script>
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
	// Enable a sitewide CSS file that will apply styles to both the home blog theme
	// and the member theme.
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) . '/css/site-wide.css' ) )
		wp_enqueue_style( 'site-wide-styles', WP_CONTENT_URL . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) . '/css/site-wide.css' );
	
	wp_print_styles();
}
add_action( 'wp_head', 'bp_core_add_css', 2 );

/**
 * bp_core_admin_bar_css()
 *
 * Add the CSS required for the global admin bar.
 * 
 * @package BuddyPress Core
 */
function bp_core_admin_bar_css() {
	if ( defined( 'BP_DISABLE_ADMIN_BAR') )
		return false;
		
	if ( is_user_logged_in() || ( !(int)get_site_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() ) ) {
		wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', BP_PLUGIN_URL . '/bp-core/css/admin-bar.css' ) );
		
		if ( 'rtl' == get_bloginfo('text_direction') && file_exists( BP_PLUGIN_DIR . '/bp-core/css/admin-bar-rtl.css' ) )
			wp_enqueue_style( 'bp-admin-bar-rtl', BP_PLUGIN_URL . '/bp-core/css/admin-bar-rtl.css' );	
	}
	wp_print_styles();
}
add_action( 'wp_head', 'bp_core_admin_bar_css', 1 );

/**
 * bp_core_add_structure_css()
 *
 * Add the CSS to add layout structure to BP pages in any WordPress theme.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-core-structure', BP_PLUGIN_URL . '/bp-core/css/structure.css' );	
}
add_action( 'bp_styles', 'bp_core_add_structure_css' );

/**
 * bp_core_add_admin_js()
 *
 * Add the JS needed for all components in the admin area.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_admin_js() {
	if ( false !== strpos( $_GET['page'], 'bp-core' ) ) {
		wp_enqueue_script( 'bp-account-admin-js', BP_PLUGIN_URL . '/bp-core/js/account-admin.js' );
	}
	
	if ( false !== strpos( $_GET['page'], 'bp-core/admin-mods' ) ) {
		wp_enqueue_script('password-strength-meter');
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
	if ( defined( 'BP_DISABLE_ADMIN_BAR') )
		return false;
		
	wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', BP_PLUGIN_URL . '/bp-core/css/admin-bar.css' ) );
}
add_action( 'admin_menu', 'bp_core_add_admin_css' );

/**
 * bp_core_admin_menu_icon_css()
 *
 * Add a hover-able icon to the "BuddyPress" wp-admin area menu.
 * 
 * @package BuddyPress Core
 */
function bp_core_admin_menu_icon_css() {
	global $bp;
?>
	<style type="text/css">
		ul#adminmenu li.toplevel_page_bp-core .wp-menu-image a { background-image: url( <?php echo $bp->core->image_base . '/admin_menu_icon.png' ?> ) !important; background-position: -1px -32px; }
		ul#adminmenu li.toplevel_page_bp-core:hover .wp-menu-image a { background-position: -1px 0; }
		ul#adminmenu li.toplevel_page_bp-core .wp-menu-image a img { display: none; }
	</style>
<?php
}
add_action( 'admin_head', 'bp_core_admin_menu_icon_css' );

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

		<?php if ( CORE_AVATAR_V2_W !== false && CORE_AVATAR_V2_H !== false ) { ?>
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