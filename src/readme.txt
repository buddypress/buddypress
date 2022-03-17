=== BuddyPress ===
Contributors: johnjamesjacoby, DJPaul, boonebgorges, r-a-y, imath, mercime, tw2113, dcavins, hnla, karmatosed, slaFFik, dimensionmedia, henrywright, netweb, offereins, espellcaste, modemlooper, danbp, Venutius, apeatling, shanebp
Tags: user profiles, activity streams, messaging, friends, user groups, notifications, community, social networking, intranet
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.4
Requires PHP: 5.6
Tested up to: 5.9
Stable tag: 10.2.0

BuddyPress helps site builders & developers add community features to their websites, with user profiles, activity streams, and more!

== Description ==

Are you looking for modern, robust, and sophisticated social network software? BuddyPress is a suite of components that are common to a typical social network, and allows for great add-on features through WordPress's extensive plugin system.

Aimed at site builders & developers, BuddyPress is focused on ease of integration, ease of use, and extensibility. It is deliberately powerful yet unbelievably simple social network software, built by contributors to WordPress.

https://wordpress.tv/2015/08/23/rocio-valdivia-buddypress-much-more-than-a-plugin/

Members can register on your site to create user profiles, have private conversations, make social connections, create and interact in groups, and much more. Truly a social network in a box, BuddyPress helps you build a home for your company, school, sports team, or other niche community.

= Built with developers in mind =

BuddyPress helps site builders & developers add community features to their websites. It comes with a robust theme compatibility API that does its best to make every BuddyPress content page look and feel right with just about any WordPress theme. You will likely need to adjust some styling on your own to make everything look pristine.

BuddyPress themes are just WordPress themes with additional templates, and with a little work, you could easily create your own, too! A handful of BuddyPress-specific themes are readily available for download from WordPress.org, and lots more are available from third-party theme authors.

BuddyPress also comes with built-in support for Akismet and [bbPress](https://wordpress.org/plugins/bbpress/), two very popular and very powerful WordPress plugins. If you're using either, visit their settings pages and ensure everything is configured to your liking.

= The BuddyPress ecosystem =

WordPress.org is home to some amazing extensions for BuddyPress, including:

- [rtMedia for WordPress, BuddyPress and bbPress](https://wordpress.org/plugins/buddypress-media/)
- [BuddyPress Docs](https://wordpress.org/plugins/buddypress-docs/)

Search WordPress.org for "BuddyPress" to find them all!

= Join our community =

If you're interested in contributing to BuddyPress, we'd love to have you. Head over to the [BuddyPress Documentation](https://codex.buddypress.org/participate-and-contribute/) site to find out how you can pitch in.

BuddyPress is available in many languages thanks to the volunteer efforts of individuals all around the world. Check out our <a href="https://codex.buddypress.org/translations/">translations page</a> on the BuddyPress Documentation site for more details. If you are a polyglot, please <a href="https://translate.wordpress.org/projects/wp-plugins/buddypress">consider helping translate BuddyPress</a> into your language.

Growing the BuddyPress community means better software for everyone!

== Installation ==

= Requirements =

To run BuddyPress, we recommend your host supports:

* PHP version 7.2 or greater.
* MySQL version 5.6 or greater, or, MariaDB version 10.0 or greater.
* HTTPS support.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles everything itself. To do an automatic install of BuddyPress, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "BuddyPress" and click Search Plugins. Once you've found it, you can view details about the latest release, such as community reviews, ratings, and description. Install BuddyPress by simply pressing "Install Now".

Once activated:

1. Visit 'Settings > BuddyPress > Components' and adjust the active components to match your community. (You can always toggle these later.)
2. Visit 'Settings > BuddyPress > Pages' and setup your directories and special pages. We create a few automatically, but suggest you customize these to fit the flow and verbiage of your site.
3. Visit 'Settings > BuddyPress > Settings' and take a moment to match BuddyPress's settings to your expectations. We pick the most common configuration by default, but every community is different.

== Frequently Asked Questions ==

= Can I use my existing WordPress theme? =

Yes! BuddyPress works out-of-the-box with nearly every WordPress theme.

= Will this work on WordPress multisite? =

Yes! If your WordPress installation has multisite enabled, BuddyPress will support the global tracking of blogs, posts, comments, and even custom post types with a little bit of custom code.

Furthermore, BuddyPress can be activated and operate in just about any scope you need for it to:

* Activate at the site level to only load BuddyPress on that site.
* Activate at the network level for full integration with all sites in your network. (This is the most common multisite installation type.)
* Enable <a href="https://codex.buddypress.org/getting-started/customizing/bp_enable_multiblog/">multiblog</a> mode to allow your BuddyPress content to be displayed on any site in your WordPress Multisite network, using the same central data.
* Extend BuddyPress with a third-party multi-network plugin to allow each site or network to have an isolated and dedicated community, all from the same WordPress installation.

Read <a href="https://codex.buddypress.org/getting-started/installation-in-wordpress-multisite/">custom BuddyPress activations </a> for more information.

= Where can I get support? =

Our community provides free support at <a href="https://buddypress.org/support/">https://buddypress.org/support/</a>.

= Where can I find documentation? =

Our documentation site can be found at <a href="https://codex.buddypress.org/">https://codex.buddypress.org/</a>.

= Where can I report a bug? =

Report bugs, suggest ideas, and participate in development at <a href="https://buddypress.trac.wordpress.org/">https://buddypress.trac.wordpress.org</a>.

= Where can I get the bleeding edge version of BuddyPress? =

Check out the development trunk of BuddyPress from Subversion at <a href="https://buddypress.svn.wordpress.org/trunk/">https://buddypress.svn.wordpress.org/trunk/</a>, or clone from Git at git://buddypress.git.wordpress.org/.

= Who builds BuddyPress? =

BuddyPress is free software, built by an international community of volunteers. Some contributors to BuddyPress are employed by companies that use BuddyPress, while others are consultants who offer BuddyPress-related services for hire. No one is paid by the BuddyPress project for his or her contributions.

If you would like to provide monetary support to BuddyPress, please consider a donation to the <a href="https://wordpressfoundation.org">WordPress Foundation</a>, or ask your favorite contributor how they prefer to have their efforts rewarded.

= Discussion Forums =

Try <a href="https://wordpress.org/plugins/bbpress/">bbPress</a>. It integrates with BuddyPress Groups, Profiles, and Notifications. Each group on your site can choose to have its own forum, and each user's topics, replies, favorites, and subscriptions appear in their profiles.

== Screenshots ==

1. **Activity Streams** - Global, personal, and group activity streams with threaded commenting, direct posting, favoriting and @mentions. All with full RSS feeds and email notification support.
2. **Extended Profiles** - Fully editable profile fields allow you to define the fields users can fill in to describe themselves. Tailor profile fields to suit your audience.
3. **User Settings** - Give your users complete control over profile and notification settings. Settings are fully integrated into your theme, and can be disabled by the administrator.
4. **Extensible Groups** - Powerful public, private or hidden groups allow your users to break the discussion down into specific topics. Extend groups with your own custom features using the group extension API.
5. **Friend Connections** - Let your users make connections so they can track the activity of others, or filter to show only those users they care about the most.
6. **Private Messaging** - Private messaging will allow your users to talk to each other directly and in private. Not just limited to one-on-one discussions, your users can send messages to multiple recipients.
7. **Site Tracking** - Track posts and comments in the activity stream, and allow your users to add their own blogs using WordPress' Multisite feature.
8. **Notifications** - Keep your members up-to-date with relevant activity via toolbar and email notifications.

== Upgrade Notice ==

= 10.2.0 =
See: https://codex.buddypress.org/releases/version-10-2-0/

= 10.1.0 =
See: https://codex.buddypress.org/releases/version-10-1-0/

= 10.0.0 =
See: https://codex.buddypress.org/releases/version-10-0-0/

= 9.2.0 =
See: https://codex.buddypress.org/releases/version-9-2-0/

= 9.1.1 =
See: https://codex.buddypress.org/releases/version-9-1-1/

= 9.0.0 =
See: https://codex.buddypress.org/releases/version-9-0-0/

= 8.0.0 =
See: https://codex.buddypress.org/releases/version-8-0-0/

= 7.3.0 =
See: https://codex.buddypress.org/releases/version-7-3-0/

= 7.2.1 =
See: https://codex.buddypress.org/releases/version-7-2-1/

= 7.2.0 =
See: https://codex.buddypress.org/releases/version-7-2-0/

= 7.1.0 =
See: https://codex.buddypress.org/releases/version-7-1-0/

= 7.0.0 =
See: https://codex.buddypress.org/releases/version-7-0-0/

= 6.4.0 =
See: https://codex.buddypress.org/releases/version-6-4-0/

= 6.3.0 =
See: https://codex.buddypress.org/releases/version-6-3-0/

= 6.2.0 =
See: https://codex.buddypress.org/releases/version-6-2-0/

= 6.1.0 =
See: https://codex.buddypress.org/releases/version-6-1-0/

= 6.0.0 =
See: https://codex.buddypress.org/releases/version-6-0-0/

= 5.2.0 =
See: https://codex.buddypress.org/releases/version-5-2-0/

= 5.1.2 =
See: https://codex.buddypress.org/releases/version-5-1-2/

= 5.1.1 =
See: https://codex.buddypress.org/releases/version-5-1-1/

= 5.1.0 =
See: https://codex.buddypress.org/releases/version-5-1-0/

= 5.0.0 =
See: https://codex.buddypress.org/releases/version-5-0-0/

== Changelog ==

= 10.2.0 =
See: https://codex.buddypress.org/releases/version-10-2-0/

= 10.1.0 =
See: https://codex.buddypress.org/releases/version-10-1-0/

= 10.0.0 =
See: https://codex.buddypress.org/releases/version-10-0-0/

= 9.2.0 =
See: https://codex.buddypress.org/releases/version-9-2-0/

= 9.1.1 =
See: https://codex.buddypress.org/releases/version-9-1-1/

= 9.0.0 =
See: https://codex.buddypress.org/releases/version-9-0-0/

= 8.0.0 =
See: https://codex.buddypress.org/releases/version-8-0-0/

= 7.3.0 =
See: https://codex.buddypress.org/releases/version-7-3-0/

= 7.2.1 =
See: https://codex.buddypress.org/releases/version-7-2-1/

= 7.2.0 =
See: https://codex.buddypress.org/releases/version-7-2-0/

= 7.1.0 =
See: https://codex.buddypress.org/releases/version-7-1-0/

= 7.0.0 =
See: https://codex.buddypress.org/releases/version-7-0-0/

= 6.4.0 =
See: https://codex.buddypress.org/releases/version-6-4-0/

= 6.3.0 =
See: https://codex.buddypress.org/releases/version-6-3-0/

= 6.2.0 =
See: https://codex.buddypress.org/releases/version-6-2-0/

= 6.1.0 =
See: https://codex.buddypress.org/releases/version-6-1-0/

= 6.0.0 =
See: https://codex.buddypress.org/releases/version-6-0-0/

= 5.2.0 =
See: https://codex.buddypress.org/releases/version-5-2-0/

= 5.1.2 =
See: https://codex.buddypress.org/releases/version-5-1-2/

= 5.1.1 =
See: https://codex.buddypress.org/releases/version-5-1-1/

= 5.1.0 =
See: https://codex.buddypress.org/releases/version-5-1-0/

= 5.0.0 =
See: https://codex.buddypress.org/releases/version-5-0-0/
