=== Plugin Name ===
Contributors: apeatling
Tags: wpmu, buddypress, social, networking, profiles, messaging, friends, groups, forums, activity
Requires at least: 2.8.4
Tested up to: 2.8.4
Stable tag: 1.1-rc

BuddyPress is a suite of WordPress MU social networking plugins and themes.

== Description ==

BuddyPress will extend WordPress MU and bring social networking features to a new or existing installation.

BuddyPress is a suite of WordPress plugins and themes, each adding a distinct new feature. BuddyPress 
contains all the features you'd expect from WordPress but aims to let members socially interact.

All BuddyPress plugins can be themed to match your own style, in just the same way as a WordPress blog. 
The BuddyPress plugins are bundled with a default theme to get you going out of the box.

== Installation ==

BuddyPress requires WordPress MU, it will not work on a single install of WordPress (yet).

--- Plugins: ---

1. Upload everything into the "/wp-content/plugins/buddypress/" directory of your installation.

2. Activate BuddyPress in the "Plugins" admin panel using the "Activate Site Wide" or "Activate" link (both work).

--- Themes: ---

1. Move "/wp-content/plugins/buddypress/bp-themes/bp-sn-parent" and
   "/wp-content/plugins/buddypress/bp-themes/bp-default" to "/wp-content/themes/"

You must then login as an admin and head to the "Site Admin > Themes" directory and activate the default
BuddyPress theme (bp-default).

Next, you will want to head to the "Appearance" menu and activate the BuddyPress default theme for the root blog of your WordPress MU installation.

--- Upgrading from an earlier version: ---

1. Backup!

2. Overwrite the /plugins/buddypress/ directory with the latest version.

3. If you are using the default theme, move the themes in "wp-content/plugins/buddypress/bp-themes/" to "wp-content/themes" and overwrite any existing themes.

4. VERY IMPORTANT: If you are no longer using the old two-theme system from BuddyPress 1.0, please make sure to delete your /wp-content/bp-themes/ folder to activate the new one-theme setup.

--- Forums Support ---

To enable forums please log in and head to "BuddyPress > Forums Setup" in the admin area.

== Frequently Asked Questions ==

= Will this work on standard WordPress? =

No, this will only work on WordPress MU for the time being, but watch this space.

= Where can I get support? =

The support forums can be found here: http://buddypress.org/forums

= Where can I find documentation? =

The documentation codex can be found here: http://codex.buddypress.org/

= Where can I report a bug? =

Bugs can be reported here: http://trac.buddypress.org/newticket

= Where can checkout the latest bleeding edge? =

BuddyPress subversion trunk can be found at: http://svn.buddypress.org/trunk/
