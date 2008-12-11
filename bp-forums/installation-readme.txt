Installing and setting up bp-forums
'''''''''''''''''''''''''''''''''''

The bp-forums component provides a link between bbPress and BuddyPress so forums can be
created and manipulated directly in BuddyPress.

Integration is simple right now and limited to creating forums, creating topics and posting.
More advanced integration including moderation, searching and tagging support will come in later
versions of the component.

*** Please Note ***

You *must* either be running the trunk of bbPress and the trunk of WPMU **OR**
at least version 1.0 of bbPress and version 2.7 of WPMU for bp-forums to work.


Follow these steps to get forums up and running:

1. Run the bbPress installer by browsing to the location that you uploaded bbPress.

2. On Step 2 you will need to integrate bbPress with WordPress

   - Check the "Add integration settings" box

   - Check the "Add cookie integration settings" box
     - Add your WordPress URL and blog URL (they are the same thing)
     - Add your auth keys, these are in your wp-config.php

   - Check the "Add user database integration settings" box
     - Add your table prefix (usually always 'wp_')
     - Add your WordPress database settings found in wp-config.php
     - You can usually ignore the "character set" and "collation" boxes
     - Leave the "Custom user tables" section blank.

3. On Step 3 enter anything you like as the site name and then select an admin user
   from WPMU to set as the keymaster.

4. Head to your new bbPress install and log in with the account you set as the keymaster.

5. Head to the Admin area (/bb-admin) and then the settings menu. Check the "Enable XML-RPC" option.

6. In the bp-forums component there is a /bbpress-plugins/ folder. Copy the 'buddypress-enable.php'
   plugin file from that folder into your bbpress plugins folder (eg domain/bbpress/bb-plugins/)

7. Enable the plugin in the bbPress admin area under the plugins menu.

8. Sign up a new user in bbPress (make a note of the username and password). Next log in as the keymaster 
   account, head to 'users' and find the new user in the list. Hit the "edit" link and set the "User Type"
   of the user to "Administrator". Save the changes.

9. Enable user switching in bbPress by copying the following line of code into your bbPress bb-config.php
   file:
    
        $bb->bb_xmlrpc_allow_user_switching = true;

10. Log into your WPMU admin interface and head to "Site Admin > Group Forums" fill in the details on that
    page. Enter the username and password for the "admin" user you just created.

11. Once you have saved those details you can create group forums. Existing groups you will need to head
    to the group admin settings page, disable then enable the group forum setting to generate a new forum. 
    New groups will work fine.

*** NOTE ***

Group forums are public, even if a group is private its forum will be accessable through the bbPress interface.
This will change eventually, but please be aware of the public status of forums before posting.
