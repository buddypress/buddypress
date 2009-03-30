How to install BuddyPress
'''''''''''''''''''''''''

**********************************************
Step 1: Make sure WPMU is installed correctly.
**********************************************

This is the most important step of all. Before you try and install
BuddyPress, you must make sure WPMU is installed and working correctly.

The WPMU support forums are a great resource. http://mu.wordpress.org/forums

Please test your installation first. Make sure you can register and activate
new blogs. Make sure you can post and leave comments on blogs. Make sure you
are not getting blank screens or 404's anywhere on the install.


**************************************************************************
Step 2: Add the BuddyPress plugins to your wp-content/mu-plugins directory
**************************************************************************

Drop everything into the 'wp-content/mu-plugins/' directory for your installation.
You do not need to activate plugins dropped into this folder.


**************************************************
Step 3: Move the themes to their correct locations
**************************************************

The home theme is not required, you can use a standard WordPress theme if you wish.

If you want to use the home theme, move:
  From: 'wp-content/mu-plugins/bp-themes/buddypress-home'
  To: 'wp-content/themes/buddypress-home/'

Move the default BuddyPress member theme:
  From: 'wp-content/mu-plugins/bp-themes/buddypress-member/'
  To: 'wp-content/bp-themes/buddypress-member/'


**************************************************************
Step 4: Log in as an administrator and activate the home theme
**************************************************************

If you plan on using the default home blog theme (it is optional) you will need to enable
and activate it.

Head to the WordPress admin panel, when logged in as a site administrator. Go to the
"Site Admin > Themes" menu and select the "Yes" radio option for "BuddyPress Home Theme".

Next, go to "Appearance > Themes" and activate the "BuddyPress Home Theme" for the root
blog.

You will want to go back to "Site Admin > Themes" once the you have activated it for the
root blog so that no one else can use that theme.

If you have multiple member themes installed, you will need to go to "Site Admin > BuddyPress"
and make sure you have selected the member theme you would like to use from the dropdown
menu.


******************************************************************
Step 5: Create your default profile fields and enable registration
******************************************************************

If you have installed the Extended Profiles component, log into your installation as
the administrator and in the administration panel head to:

 - Site Admin > Profiles

Here you can set up profile groups and fields for users to fill in. Any fields you add 
to the "Basic" group will appear on the signup form along with an avatar upload option.

** If this is a brand new WPMU install ** you will need to enable registrations. Head to:

 - Site Admin > Options

Check the "Enabled" radio button under "Allow new registrations" and hit the update 
options button. You can enable/disable blog registrations if you wish.


****************
Useful Resources
****************

 - BuddyPress Forums:
   http://buddypress.org/forums

 - BuddyPress Codex
   http://codex.buddypress.org/

 - BuddyPress Testdrive:
   http://testbp.org

 - BuddyPress Trac server (code repo and install links)
   http://trac.buddypress.org

 - Report a Bug:
   http://trac.buddypress.org/newticket








