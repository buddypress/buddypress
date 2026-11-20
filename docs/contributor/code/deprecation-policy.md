# Deprecated function loading

## Current behavior

Starting with BuddyPress 15.0, `bp_get_deprecated_functions_versions()` lists the shipped versioned files in `src/bp-core/deprecated/`, and BuddyPress loads those files on new and upgraded installations, in source checkouts, and during PHPUnit runs. Loading a file does not guarantee that every definition in it is unconditional; respect existing compatibility checks such as those for BP Classic.

`BP_LOAD_DEPRECATED` and `BP_IGNORE_DEPRECATED` are deprecated. Neither constant affects file selection, whatever its value. Defining either can produce a deprecation notice under WordPress debug handling. The constants can be removed from configuration when compatibility with earlier BuddyPress versions is no longer needed.

## Contributing deprecated functions

When a function is deprecated, follow the existing versioned file and documentation patterns in `src/bp-core/deprecated/`. Preserve its compatibility behavior, mark the deprecation version, and use the appropriate WordPress deprecation notice. Identify a replacement when one exists; do not invent one when it does not.

Add or update relevant tests in the component's `tests/phpunit/testcases/<component>/deprecated.php` file. Cover behavior as well as loading where appropriate, and use the existing expected-deprecation annotations. Keep the version list in `bp_get_deprecated_functions_versions()` consistent with the shipped files.

## Earlier loading strategy

Before 15.0, the selection depended on installation history and configuration. The strategy introduced in [r13304](https://buddypress.trac.wordpress.org/changeset/13304) did not load deprecated functions on new installations and limited those loaded on upgrades. Later changes, including [r13964](https://buddypress.trac.wordpress.org/changeset/13964) and [r13998](https://buddypress.trac.wordpress.org/changeset/13998), addressed 12.0 compatibility and extended that version's loading through 15.0. These historical rules are not the current loading behavior.

This guidance describes function loading, not a general promise that deprecated APIs can never be removed.
