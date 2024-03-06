# Getting Started

Welcome to BuddyPress! This section will help you set up your new BuddyPress-powered site as Super/Site Administrator.

## To run BuddyPress, you need WordPress!

BuddyPress is a WordPress plugin. If you haven't installed WordPress yet, please read this [step-by-step plan](https://wordpress.org/documentation/article/get-started-with-wordpress/) of the WordPress Documentation. The latest stable BuddyPress version will always support the latest stable WordPress version. BuddyPress shines brightest when run on the newest available version of WordPress – we optimize for the latest and greatest. For this reason, the BuddyPress team strongly recommends that all WordPress installations be kept up to date. However, we strive to maintain support for a number of legacy WordPress versions – generally, four or five major versions. Near the beginning of each development cycle, we reassess BuddyPress’s minimum required WP version. Our rough guidelines are as follows:

- If a WordPress version became obsolete over a year ago, it’s a candidate for removal. See the [WordPress releases history](https://wordpress.org/news/category/releases/) for a list of WP release dates.
- If a WordPress version’s use slips below 5% of all WP installations, it’s a strong candidate for removal. See the [WordPress statistics](https://wordpress.org/about/stats/) for information about WordPress version usage.

To know if your WordPress version is supported by BuddyPress, please check our [releases history](https://codex.buddypress.org/releases/) for the list of BuddyPress release versions and WordPress version compatibility.

## Other requirements

- BuddyPress does not work on installations where [you give WordPress its own directory](https://wordpress.org/documentation/article/giving-wordpress-its-own-directory/).
- Folder name for any subdirectory or subdomain WordPress/BuddyPress installation must be in lowercase.
- [PHP](./php-version-support.md) must have the GD or imagick modules installed (on the server) to allow re-sizing of images; BP avatar uploads will fail without one of these modules activated (WP will simply fail to create image sizes for posts but won’t show an error)

## Get BuddyPress!

### Use latest stable version

The easiest way to get it is from your WordPress Dashboard. Log-in your WordPress Administration, Click on your Plugins / Add New submenu and if you don't find BuddyPress into the Featured Plugins list, type `BuddyPress` inside the search field and you should find BuddyPress as the first search result. Click on the "Install now" button and you're done!

Alternatively, you can download a zip Archive of BuddyPress from our [Plugin page on the official WordPress.org Plugin directory](https://wordpress.org/plugins/buddypress/). Click on the blue "Download" button and once you got it, go to the same Plugins / Add New WordPress Administration screen, but this time click on the "Upload Plugin" button at the right of the Screen's title. A form to upload the zip Archive you downloaded will appear. Browse your computer files to find this Archive and click on the "Install now" button and you're done!

### Use the development version

**Please make sure to use this version inside your development environment only!**

This [page of our official site](https://buddypress.org/download/) is informing about how you can get it using SVN or Git. It also contains a link about how you can [get involved](https://codex.buddypress.org/participate-and-contribute/) into BuddyPress contribution.

NB: If you want to contribute to this documentation, you can [fork the BuddyPress GitHub repository](https://github.com/buddypress/buddypress/fork) edit files within the `/docs` directory and submit your edits as pull requests!

## Next steps

- Activate BuddyPress
- Set up BuddyPress
