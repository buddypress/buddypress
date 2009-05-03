<?php
/* Set the content type to CSS */
header('Content-type: text/css'); 

/* Load the base and css. */
if ( file_exists('base.css') )
	echo "@import url(base.css);\n";

/* Load the custom css if there is any. */
if ( file_exists('custom.css') )
	echo "@import url(custom.css);\n";
?>