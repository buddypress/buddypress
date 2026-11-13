# Deprecated functions and plugin compatibility

Some plugins and custom code use older BuddyPress functions. A deprecated function is an older function that developers are encouraged to replace; deprecation does not necessarily mean it has stopped working.

Starting with BuddyPress 15.0, BuddyPress loads its shipped deprecated-function files on both new and upgraded sites. This helps older plugins continue to work, but it cannot guarantee compatibility with every plugin or customization. Keep plugins updated and contact their developers if a problem persists.

## Older configuration settings

`BP_LOAD_DEPRECATED` and `BP_IGNORE_DEPRECATED` no longer control which deprecated functions load. Defining either setting, even as `false`, can produce a deprecation notice when WordPress debugging is enabled.

If either setting is present in your site's configuration, it can be removed for BuddyPress 15.0 and later. If you are unsure how to change the configuration safely, ask your site administrator or hosting provider. No action is needed if neither setting is defined. Earlier BuddyPress versions can behave differently, so consider other installations that share the configuration before removing a setting.
