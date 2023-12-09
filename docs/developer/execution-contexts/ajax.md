# Using Ajax requests in BuddyPress

Starting in BuddyPress 12.0.0, if your Ajax callback needs to get values about BP URI globals such as the displayed user, the current component, the current action or current action variables, you'll need to register your Ajax action so that BuddyPress is informed you're expecting these globals' values.

To avoid running the `WP()` request analysis at each Ajax request, BuddyPress is only doing so for the registered Ajax actions.

To register your Ajax action, you simply need to use the `bp_ajax_register_action()` once the `bp_init` action hook has been fired.

```php
/**
 * Register a BP Ajax action.
 */
function register_your_ajax_action() {
	bp_ajax_register_action( 'your_ajax_action' );
}
add_action( 'bp_init', 'register_your_ajax_action' );
```
