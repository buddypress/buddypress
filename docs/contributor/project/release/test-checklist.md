# Testing a release before deploying it

The minor of major version [package has been built](build-checklist.md#deploying-to-wporg), before deploying it to \[wp-org\] repository, let's control everything went fine testing the build:

+ Copy the content of the `buddypress-to-deploy/build` folder into a new `buddypress` folder.
+ Zip it.
+ Test it on a "fresh" WordPress regular site and then on a WordPress mulisite network.

> [!TIP]
> If you organized a release party on BuddyPress Slack's channel, don't hesitate to ask other contributors to help you test this new version.

## WordPress regular site

1. Make sure it's a WordPress fresh install (BuddyPress has not been installed/activated on the site).
2. Go to `WP Admin > Plugins > Add new` to upload the Zip you created earlier and activate BuddyPress.
3. Test activating / deactivating all components is happening without issues/notices.
4. Add a new WordPress post and then a BP Member block to the post content to check the BP REST API is available via the block's Autocomplete field.
5. Switch to BP Nouveau & BP Legacy template packs from `WP Admin > Settings > BuddyPress > Options` then go to `site.url/members` to check there are no issues/notices.

## WordPress multisite network

1. Go to `WP Network Admin > Plugins` to check a previous BuddyPress version is active at the network level.
2. Go to `WP Network Admin > Plugins > Add new` to upload the Zip you created earlier to replace the previous BuddyPress version and simulate a plugin upgrade.
3. Repeat WP regular site's checklist points 3 to 5.
