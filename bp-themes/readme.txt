BuddyPress Themes
'''''''''''''''''

Just as WordPress themes control how your blog looks, BuddyPress themes control how your members'
profile, groups, wire and all other BuddyPress features look.

A default BuddyPress enabled installation comes with two themes. 

The first theme "bphome" is just a regular WordPress theme. If you do not already have a WordPress
theme that you are using for your root blog, then you can install this theme to get you started. The theme
takes the focus away from the blog, and provides a front page of three columns where you can drop widgets.

You are not required to install the "bphome" theme, you can use any WordPress theme.

The second theme "bpmember" is a BuddyPress theme. You are required to install at least one BuddyPress
theme for BuddyPress to function correctly. BuddyPress themes sit in their own directory, so they are not
confused with WordPress blog themes. You should copy the "bpmember" theme into the folder
"wp-content/bp-themes/" - you should create the bp-themes folder if it does not already exist.

Here are some step by step instuctions for installing both the "bphome" and "bpmember" themes.

1. Copy "bp-themes/bphome" to "wp-content/themes/bphome/"

2. Copy "bp-themes/bpmember" to "wp-content/bp-themes/bpmember/" create the bp-themes folder
   if needed.

3. You may need to log in as an administrator and activate the 'bphome' theme in 'Site Admin > Themes'

4. Activate the bphome theme in the "Appearance" menu for the home blog if you want to use it.

5. The "bpmember" theme will be activated automatically. However, if you install other BuddyPress themes
   in the future, you will need to activate them under the "Site Admin > BuddyPress" menu.