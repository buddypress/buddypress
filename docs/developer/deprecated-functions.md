# Deprecated functions

BuddyPress keeps deprecated functions in versioned files and loads only some of those files at runtime. This page describes that policy so plugin authors, theme authors, and site owners can tell whether an old function will still exist.

The files live in [`src/bp-core/deprecated/`](https://github.com/buddypress/buddypress/tree/master/src/bp-core/deprecated). Each file is named after the major version that deprecated its functions, for example [`12.0.php`](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php). `bp_get_deprecated_functions_versions()` decides which files to load.

A loaded deprecated function still runs. When `WP_DEBUG` is enabled, WordPress also raises a deprecation notice through `_deprecated_function()`. A call to a function whose file was not loaded is an undefined-function error.

## Regular rule

An upgraded site loads the deprecated files for the **two most recent** versions that have one. A site whose first BuddyPress install is the current major version loads none of the older files. This has been the rule since [changeset r13304](https://buddypress.trac.wordpress.org/changeset/13304).

A development checkout loaded from the `src` directory follows the same opt-out: it loads no deprecated files unless one of the constants below says otherwise.

## The 12.0 exception in 14.0 and 15.0

BuddyPress 12.0 introduced the BP Rewrites API and deprecated many legacy URL functions. The replacement functions are documented in [BP URL functions](./functions/rewrites.md). The deprecated list is [`12.0.php`](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php).

Those functions stay available longer than the regular two-version window. BuddyPress 14.0 and 15.0 keep loading them, including on a brand-new install, so plugins, themes, and custom code that still call them do not fatally error. The decision is recorded in [Trac ticket #9210](https://buddypress.trac.wordpress.org/ticket/9210).

> [!IMPORTANT]
> BuddyPress 15.0 is the last major release that includes the functions deprecated in 12.0. BuddyPress 16.0 returns to the regular rule above. Update plugins, themes, and custom code before upgrading to 16.0.

### What BuddyPress 15.0 loads

The first installed major version is stored in the `_bp_initial_major_version` option. Sites that first installed BuddyPress before 11.0 have no stored value and are treated as version `0`, which counts as an earlier install.

| How the site reached BuddyPress 15.0 | Files loaded |
| --- | --- |
| First installed on 15.0 | `12.0.php` |
| Upgraded to 15.0 from any earlier version, including a missing `_bp_initial_major_version` | `12.0.php`, `14.0.php`, and `15.0.php` |

On 15.0, that upgrade set is every deprecated file from 12.0 through the current major version. The regular "two most recent files" set on this release would have been `14.0.php` and `15.0.php`. The 12.0 file stays in the set because of the exception above.

## Constants

Define these in `wp-config.php` when you need to override the rules above. They also apply to a development checkout loaded from `src`.

Load none of the deprecated files:

```php
define( 'BP_IGNORE_DEPRECATED', true );
```

Load every file in `src/bp-core/deprecated/`:

```php
define( 'BP_LOAD_DEPRECATED', true );
```

When both constants are set to `true`, `BP_LOAD_DEPRECATED` wins and every deprecated file is loaded. `BP_LOAD_DEPRECATED` is broader than a normal 15.0 site: a packaged 15.0 install loads only the files in the table above.

## Before you upgrade to 16.0

Search plugins, themes, and custom code for the functions listed in [`12.0.php`](https://github.com/buddypress/buddypress/blob/master/src/bp-core/deprecated/12.0.php). Build BuddyPress URLs with the functions in [BP URL functions](./functions/rewrites.md).

Sites that still need the legacy URL layer can use the [BP Classic](https://wordpress.org/plugins/bp-classic/) add-on. Some functions removed from core in 12.0 are provided by that add-on. While BP Classic is active, BuddyPress does not redeclare those functions from `12.0.php`.

Turn on `WP_DEBUG` on a staging site while the 12.0 file is still loaded. Deprecation notices identify the calls that need to change. After 16.0, BuddyPress will no longer include those functions.

## Maintaining the list

When a core change deprecates a function, add the function to `src/bp-core/deprecated/{major}.php` and add that major version to the list in `bp_get_deprecated_functions_versions()`. The unit test `test_bp_get_deprecated_functions_versions_array_is_consistent_with_deprecated_files` fails when a file in that directory has no matching entry in the list.
