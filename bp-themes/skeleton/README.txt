BuddyPress Skeleton Member Theme
--------------------------------
Version: 1.2
Release Date: April 29th, 2009

About the Theme
===============

The skeleton member theme is a bare bones theme that provides you with a good
starting point to create your own BuddyPress theme. It includes all of the
template function calls you need to make use of all BuddyPress component features.


Template Files
==============

BuddyPress themes control both the entry and display of data. This is done to provide
a smoother user experience for your end users. They do not need to jump between the
wp-admin area and the front-end site.

You do not have to support all BuddyPress components in your theme. For instance, if
you decided you didn't want to support the friends component, you can just delete all
the template files in the /friends/ directory.

All the template files in this skeleton theme follow the same HTML structure. If you
stick to that structure you'll find you can make most of the changes you'll need just in
the CSS files. The CSS files bundled with this theme include a CSS document outline
for you to get started from. Be sure to read the section at the bottom of this readme
regarding "Structural CSS".


Installation
============

Add the theme folder to:
 
 - wp-content/bp-themes/

Once you have copied the theme over, you must activate it within the WordPress admin
panel under the "Site Admin > BuddyPress" in the member theme select box.


A Note on Structural CSS
========================

The BuddyPress components actually provide some basic structural CSS to help get you
started. This CSS is not contained in the theme, but can be extended upon or overridden
in the theme CSS.

If you decide you do not want to make use of this structural CSS, and would rather turn
it off, there are lines you can uncomment in the "functions.php" file of this theme.