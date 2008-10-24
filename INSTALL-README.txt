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

***************************************
Step 2: Use all the BuddyPress plugins.
***************************************

While in pre-release, it's advised to use all the BuddyPress plugins. Full checks
have not been done yet for component dependencies. This means if you leave out
something like the extended profiles component, things will likely break right now.

Once all components are at a stable point, these dependencies will be checked and fixed
so you'll be able to pick and choose which components to enable.


**********************************************************************************
Step 3: Make sure you are adding the correct directories to /wp-content/mu-plugins
**********************************************************************************

If you are using tagged components and downloading them via zip files this is even more
important.

When you download a tagged component, using the core v0.2.3 component as an example,
the zip file will extract as:

  - /tags/core/0.2.3/bp-core/
  - /tags/core/0.2.3/bp-core/bp-core.php

You will need to copy the /bp-core/ and /bp-core/bp-core.php dir and file into your 
MU-plugin directory, so you have:

  - /wp-content/mu-plugins/bp-core/
  - /wp-content/mu-plugins/bp-core.php

If you are using the trunk version of the components, you should just be able to copy 
everything into /wp-content/mu-plugins/ If you don't know what the trunk version is, 
ignore this bit.


***********************************************************
Step 4: Place the BuddyPress themes in the correct location
***********************************************************

 - Please follow the instructions in the /buddypress-theme/readme.txt file

************************************************************
Step 5: Log in as an administrator and create profile fields
************************************************************

Log into your installation as the administrator and in the back end admin area head to:

 - Site Admin > Profiles

Here you can set up profile groups and fields for users to fill in. Any fields you add 
to the "Basic" group will appear on the signup form along with an avatar upload option.

** If this is a brand new WPMU install ** you will need to enable registrations. Head to:

 - Site Admin > Options

Check the "Enabled" radio button under "Allow new registrations" and hit the update 
options button. You can enable/disable blog registrations if you wish.


*******************************************
Step 6: Up and running and useful resources
*******************************************

You should now be up and running with BuddyPress. If you are experiencing 404's or blank 
screens it is likely related to your WPMU setup. If your WPMU setup was definitely working 
perfectly before you installed BuddyPress, ask for help on the mailing list.

Here are some links to useful resources:

 - BuddyPress FAQ's
   http://codex.buddypress.org/faqs

 - BuddyPress mailing list:
   http://lists.automattic.com/mailman/listinfo/buddypress-dev

 - BuddyPress Testdrive:
   http://testdrivewpmu.com

 - BuddyPress Trac server (code repo and install links)
   http://trac.buddypress.org

 - Report a Bug:
   http://trac.buddypress.org/newticket








