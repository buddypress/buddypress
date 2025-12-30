# BuddyPress PHP Version Support

Keeping your WordPress installation, including BuddyPress, on a supported and current PHP version is crucial for site performance, security, and compatibility. Regularly consult the [WordPress Requirements](https://wordpress.org/about/requirements/) page.

## Minimum requirements (BuddyPress 15.0.0 and later)

As of BuddyPress **15.0.0**, the minimum required versions are:

- **WordPress:** 6.4 or greater.
- **PHP:** 7.0 or greater.

This change was introduced during the 15.0.0 development cycle (see [Trac ticket #9051](https://buddypress.trac.wordpress.org/ticket/9051) and the [BP Team Updates blog post](https://bpdevel.wordpress.com/2024/09/02/raising-the-minimum-version-of-wordpress-and-php-required-in-buddypress-15-0-0/)).

## Recommended requirements

For best performance and security, we recommend:

- **PHP:** 7.4 or greater.
- **Database:** MySQL 8.0+ or MariaDB 10.5+.
- **HTTPS:** TLS/HTTPS support on your server.

> Note: The recommended versions are higher than the minimums. Using the latest supported versions ensures the best stability and security.

## Why we changed this

The BuddyPress team raised the minimum requirements in version 15.0.0 to keep the software secure, compatible, and maintainable.

## If your host runs an older PHP version

- Check your PHP version (`php -v` or through your hosting control panel).
- If outdated, ask your host to upgrade or move to a provider supporting PHP 7.4+.
- Always test backups and staging sites before upgrading production.
