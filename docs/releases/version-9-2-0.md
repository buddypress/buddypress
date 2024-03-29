# Version 9.2.0

Version 9.2.0 is a BuddyPress maintenance release. It was released on January 3, 2022. 5 bugs were fixed.
For Version 9.2.0, the database version (`_bp_db_version` in `wp_options`) was `12850`, and the Trac revision was `13207`.

## Fixes

- xProfile component: drop an extra double quote inside the #tabs-signup-group tag (see [#8586](https://buddypress.trac.wordpress.org/ticket/8586))
- xProfile: prevent the Name field to override WP Field Types on signup (see [#8568](https://buddypress.trac.wordpress.org/ticket/8568))
- Core component: validate an url param exists before processing an oEmbed XML request (see [#8601](https://buddypress.trac.wordpress.org/ticket/8601))
- Activity component: Improve the Core Search routing function to support Activity search (see [#8608](https://buddypress.trac.wordpress.org/ticket/8608))
- BP Blocks: make sure front-end JS for dynamic blocks are not loaded in WP Admin (see [#8610](https://buddypress.trac.wordpress.org/ticket/8610))

The detailed list of changes for this release are available at BuddyPress Trac. See [milestone 9.2.0](https://buddypress.trac.wordpress.org/query?status=closed&group=resolution&milestone=9.2.0).
