--- Custom Member Themes ---

Any themes dropped into this directory can be activated as a theme for
all member pages.

Standard WordPress themes won't work off the bat. You'll need to add some
template files, as well as adding a couple of template calls and a hook.

The idea is at some point soon (post 1.0) you will be able to download a
"BuddyPress Theme Enable" package that will upgrade a WordPress theme so it is 
BuddyPress member theme compatible.

For now, here are some quick instructions on how to enable a WordPress theme
so it is "BuddyPress Enabled".

--- BuddyPress Enabling a WordPress Theme ---

1. Copy the following template files/dirs from /buddypress-member/ theme:

 - /activity/
 - /blogs/
 - /friends/
 - /groups/
 - /messages/
 - /profile/
 - /wire/
 - optionsbar.php
 - userbar.php
 - plugin-template.php

2. Add the template function <?php bp_styles() ?> into the 'header.php' file

 - You must add it directly after the <title></title> tags.

3. Add calls to the user and options navigation at the bottom of the 'header.php' file

 - Add these two lines at the very bottom of header.php

   <?php load_template( TEMPLATEPATH . '/userbar.php' ) ?>
   <?php load_template( TEMPLATEPATH . '/optionsbar.php' ) ?>

---------
You should now be able to activate the theme in Site Admin > BuddyPress.

Bare in mind, the BuddyPress components only provide the basic CSS to format the
layout of BuddyPress screens. You will need to add styles to the theme CSS to add images, 
colors and backgrounds.

Remember - any standard WordPress theme can be used for a home theme with no modifications.
As long as it is widget enabled, you can activate BuddyPress widgets.
