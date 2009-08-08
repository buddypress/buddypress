<?php
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
 * bp_core_add_jquery_cropper()
 *
 * Makes sure the jQuery jCrop library is loaded.
 * 
 * @package BuddyPress Core
 */
function bp_core_add_jquery_cropper() {
	wp_enqueue_script( 'jcrop' );
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
	
	$image = apply_filters( 'bp_inline_cropper_image', getimagesize( $bp->avatar_admin->image->dir ) );
?>
	<script type="text/javascript">
		jQuery(window).load( function(){
			jQuery('#avatar-to-crop').Jcrop({
				onChange: showPreview,
				onSelect: showPreview,
				onSelect: updateCoords,
				aspectRatio: 1,
				setSelect: [ 50, 50, 200, 200 ]
			});
		});

		function updateCoords(c) {
			jQuery('#x').val(c.x);
			jQuery('#y').val(c.y);
			jQuery('#w').val(c.w);
			jQuery('#h').val(c.h);
		};

		function showPreview(coords) {
			if ( parseInt(coords.w) > 0 ) {
				var rx = 100 / coords.w;
				var ry = 100 / coords.h;

				jQuery('#avatar-crop-preview').css({
					width: Math.round(rx * <?php echo $image[0] ?>) + 'px',
					height: Math.round(ry * <?php echo $image[1] ?>) + 'px',
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
	global $bp;
?>
	<style type="text/css">
		.jcrop-holder { text-align: left; }
		.jcrop-vline, .jcrop-hline { font-size: 0; position: absolute; background: white top left repeat url( <?php echo $bp->core->image_base ?>/Jcrop.gif ); }
		.jcrop-vline { height: 100%; width: 1px !important; }
		.jcrop-hline { width: 100%; height: 1px !important; }
		.jcrop-handle { font-size: 1px; width: 7px !important; height: 7px !important; border: 1px #eee solid; background-color: #333; *width: 9px; *height: 9px; }
		.jcrop-tracker { width: 100%; height: 100%; }
		.custom .jcrop-vline, .custom .jcrop-hline { background: yellow; }
		.custom .jcrop-handle { border-color: black; background-color: #C7BB00; -moz-border-radius: 3px; -webkit-border-radius: 3px; }
		
	</style>
<?php
}

/**
 * bp_core_add_ajax_url_js()
 *
 * Adds AJAX target URL so themes can access the WordPress AJAX functionality.
 * 
 * @package BuddyPress Core
 */
function bp_core_add_ajax_url_js() {
	global $bp;
	
	echo 
'<script type="text/javascript">var ajaxurl = "' . $bp->root_domain . str_replace( 'index.php', 'wp-load.php', $_SERVER['SCRIPT_NAME'] ) . '";</script>
';
}
add_action( 'wp_head', 'bp_core_add_ajax_url_js' );

?>