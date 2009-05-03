<?php
// Do we want the blog or profile as home? Check will go here.
if ( $current_component == 'profile' ) :
	include_once (TEMPLATEPATH . '/profile/index.php');
else :
	include_once (TEMPLATEPATH . '/blog.php');
endif;
?>