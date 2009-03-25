<?php
/* Set the content type to CSS */
header('Content-type: text/css');
?>

@import url(base.css);
@import url(settings.css);

@import url(activity.css);
@import url(blogs.css);
@import url(directories.css);
@import url(friends.css);
@import url(groups.css);
@import url(messaging.css);
@import url(wire.css);
@import url(profiles.css);
@import url(forums.css);

<?php
/* If there are any custom component css files inside the /custom-components/ dir, load them. */
if ( is_dir( './custom-components' ) ) {
	if ( $dh = opendir( './custom-components' ) ) {
		while ( ( $css_file = readdir( $dh ) ) !== false ) {
			if( substr ( $css_file, -4 ) == '.css' ) {
				echo "@import url(custom-components/$css_file);\n";
			}
		}
	}
}

/* Now load the custom styles CSS for custom modifications */
if ( file_exists('custom.css') )
	echo "@import url(custom.css);\n";
?>