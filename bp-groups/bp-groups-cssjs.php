<?php
/**************************************************************************
 groups_add_js()
  
 Inserts the Javascript needed for managing groups.
 **************************************************************************/	

function groups_add_js() {
	global $bp;
	
	if ( !isset($_GET['page']) )
		$_GET['page'] = null;

	if ( strpos( $_GET['page'], 'groups' ) !== false || $bp['current_component'] == $bp['groups']['slug'] ) {
		echo '
			<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-groups/js/general.js" type="text/javascript"></script>';
	}
}
add_action( 'wp_head', 'groups_add_js' );
add_action( 'admin_head', 'groups_add_js' );

/**************************************************************************
 add_css()
  
 Inserts the CSS needed to style the groups pages.
 **************************************************************************/	

function groups_add_css()
{
	?>
	
	<?php
}

?>