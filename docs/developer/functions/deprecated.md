# Deprecated BuddyPress functions

Deprecated functions are older APIs retained for compatibility. Starting with BuddyPress 15.0, the shipped versioned deprecated-function files load on new and upgraded installations, including source checkouts and PHPUnit runs. Individual definitions may still depend on their surrounding compatibility conditions.

Calls to deprecated functions may generate notices when WordPress debugging is enabled. Check each function's documentation and deprecation notice for a replacement, then update and test your plugin or theme. A replacement may accept different arguments or behave differently; check its contract before switching. Not every function has a direct replacement.

`BP_LOAD_DEPRECATED` and `BP_IGNORE_DEPRECATED` no longer control loading in 15.0. Defining either constant, regardless of its value, can produce a deprecation notice. Remove definitions that are no longer needed, taking care if the same configuration is used with earlier BuddyPress versions.

Loading deprecated files helps compatibility; it does not guarantee that every old API will remain available indefinitely. For example, see the [BuddyPress URL functions guide](rewrites.md) when migrating legacy URL code.
