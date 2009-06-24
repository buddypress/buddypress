Installing and setting up bp-forums
'''''''''''''''''''''''''''''''''''

The bp-forums component provides a link between bbPress and BuddyPress so forums can be
created and manipulated directly in BuddyPress.

Forum functionality is currently quite basic and limited to creating forums, creating topics and posting.
More advanced integration including moderation, searching and tagging support will come in later
versions of the component.

*** Please Note ***

You *must* be running the latest alpha of bbPress and at least WPMU version 2.7 for forum integration to 
work.

************************************************
Follow these steps to get forums up and running:
************************************************

1. Run the bbPress installer by browsing to the location that you uploaded bbPress.

2. On Step 2 you will need to integrate bbPress with WordPress

   - Check the "Add integration settings" box

   - Leave "Add cookie integration settings" unchecked

   - Check the "Add user database integration settings" box
     - Add your table prefix (usually always 'wp_')
     - Add your WordPress database settings found in wp-config.php
     - You can usually ignore the "character set" and "collation" boxes
     - Leave the "Custom user tables" section blank.

3. On Step 3 enter anything you like for your site name and first forum name. Select "Admin" from the
   dropdown to use as your keymaster user.

4. Head to your new bbPress install and log in with your WordPress administrator account (admin).

5. Head to the Admin area (/bb-admin) and then the settings menu. Check the "Enable XML-RPC" option.
   Also check the "Enable Pingbacks" option just below.

6. In the bp-forums component there is a /bbpress-plugins/ folder. Copy the 'buddypress-enable.php'
   plugin file from that folder into your bbpress plugins folder (eg domain/bbpress/bb-plugins/)

7. Enable the plugin in the bbPress admin area under the plugins menu.

8. Head back to your BuddyPress installation and sign up as a new user. Once you have
   activated the new user, make a note of the username and password given.

9. Log back into your bbPress installation as the administrator, and head back to the admin panel (/bb-admin).
   Go to the "Users" tab and look for the new user you signed up in step 8 in the list. Hit the "Edit"
   link for that user, on the end of the row.

10. On the edit user screen there is a drop down menu called "User Type". Select "Administrator" as the user
    type for this user. Hit the "Update Profile" button.

11. Enable user switching in bbPress by copying the following line of code into your bbPress bb-config.php
    file:
    
        $bb->bb_xmlrpc_allow_user_switching = true;

12. Log into your WPMU admin interface and head to "BuddyPress > Forums Setup" fill in the details on that
    page. Make sure you don't leave out the ending slash on your bbPress URL. (http://example.com/bbpress/)
    Enter the username and password for the user that you signed up in step 8.

13. Once you have saved those details you can create group forums. Existing groups you will need to head
    to the group admin settings page, disable then enable the group forum setting to generate a new forum. 
    New groups will work fine.

*** NOTE ***

Group forums are public, even if a group is private its forum will be accessable through the bbPress interface.
This will change eventually, but please be aware of the public status of forums before posting.
