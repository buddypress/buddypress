<?php
function bp_core_admin_add_avatar() {
	global $bp, $wpdb;
	
	if ( function_exists('xprofile_install') && $wpdb->blogid == $bp['loggedin_homebase_id'] ) {
		$avatar_href = bp_core_get_avatar( $bp['loggedin_userid'], 1, true );
		
		if ( $avatar_href != '' ) {
			?>
			<style type="text/css">
				#wphead h1 {
					background: url( <?php echo $avatar_href ?> ) no-repeat 2% 50%;
					padding: 20px 0 20px 85px;
				}
			</style>
			<?php
		}
	}
}
add_action( 'admin_head', 'bp_core_admin_add_avatar' );

?>