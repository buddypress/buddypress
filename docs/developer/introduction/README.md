# Introduction

BuddyPress Add-ons are WordPress plugins extending BuddyPress with new features. If you're new to WordPress plugin development, you should probably have a look to the [official WordPress Plugin development handbook](https://developer.wordpress.org/plugins/).

## Why we make BuddyPress Add-ons?

A very important rule in BuddyPress development is: __do not edit BuddyPress core__. As the BuddyPress plugin is overwritten each time it is updated to a new version, locally edited BuddyPress core files would also be overwritten. Thankfully, WordPress provides a powerful Plugin API we can use to extend, reduce or change the behavior of almost every BP features.

The BuddyPress core team [has decided](https://buddypress.org/2023/05/lets-better-organize-the-buddypress-plugin/) to use Add-ons to better organize the BuddyPress plugin moving optional components into specific Add-ons users can download and activate separately. We believe doing so will bring more freedom to everyone:

- More freedom to end-users as they can choose to use alternate components built by third party plugin authors.
- More freedom to plugin authors as they can challenge features built & maintained by the BuddyPress Core team.
- More freedom to BuddyPress Core developers as they can build great features without worrying a third party plugin author might have built it already.
