# Branch 9.0 ChangeLog

All notable changes about branch 9.0 of BuddyPress are documented in this file.

`_bp_db_version`: `12850`.

## Version 9.2.0 - 2022-01-03

Trac revision: 13207. [Full changes list](https://buddypress.trac.wordpress.org/query?status=closed&group=resolution&milestone=9.2.0).

### Fixes

- xProfile component: drop an extra double quote inside the #tabs-signup-group tag (see [#8586](https://buddypress.trac.wordpress.org/ticket/8586))
- xProfile: prevent the Name field to override WP Field Types on signup (see [#8568](https://buddypress.trac.wordpress.org/ticket/8568))
- Core component: validate an url param exists before processing an oEmbed XML request (see [#8601](https://buddypress.trac.wordpress.org/ticket/8601))
- Activity component: Improve the Core Search routing function to support Activity search (see [#8608](https://buddypress.trac.wordpress.org/ticket/8608))
- BP Blocks: make sure front-end JS for dynamic blocks are not loaded in WP Admin (see [#8610](https://buddypress.trac.wordpress.org/ticket/8610))

## Version 9.1.1 - 2021-08-18

Trac revision: 13068 [Full changes list](https://buddypress.trac.wordpress.org/query?status=closed&group=resolution&milestone=9.1.0).

### Fixes

- Activity: update the nonce used by the Activity Reply JS Fallback ([#8545](https://buddypress.trac.wordpress.org/ticket/8545)).
- Settings: do not try to validate a dismissed email change ([#8538](https://buddypress.trac.wordpress.org/ticket/8538)).
- Settings: make sure changing pwd from the General Screen encrypts it ([#8539](https://buddypress.trac.wordpress.org/ticket/8539)).

### Security

- Make sure the activation key is never included into responses of the BP REST API Signup endpoints.
- Prevent potential SQL injections making sure the order by clause built inside the `BP_Notifications_Notification::get_order_by_sql()` only accepts an allowed list of column names.
- Prevent potential SQL injections making sure the order by clause built inside the `BP_Invitation::get_order_by_sql()` only accepts an allowed list of column names.

## Version 9.0.0 "Mico" - 2021-07-19

Trac revision: 13027. [Full changes list](https://buddypress.trac.wordpress.org/query?status=closed&group=resolution&milestone=9.0.0).

### Added

### Changed

### Deprecated

### Removed
