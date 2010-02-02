<?php
	/***
	 * Should we show the blog on the front page, or the activity stream?
	 * This is set in wp-admin > Appearance > Theme Options
	 */
	if ( !bp_is_active( 'activity' ) )
		locate_template( array( 'index.php' ), true );
	else {
		if ( 'blog' == bp_dtheme_show_on_frontpage() )
			locate_template( array( 'index.php' ), true );
		else
			locate_template( array( 'activity/index.php' ), true );
	}
?>