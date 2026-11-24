# Template asset URLs

`bp_locate_template_asset()` searches the BuddyPress template stack for a CSS or JavaScript file. It returns `false` when the file is missing, or an array with its filesystem path in `file` and its public URL in `uri`.

BuddyPress maps assets in the active child theme, parent theme, and theme compatibility package to their configured URLs. It also resolves assets in WordPress plugin, must-use plugin, theme, and content directories. The URL mapping recognizes symlink targets and keeps the relevant WordPress URL filters.

If you register a template-stack directory outside those locations, provide its public base URL with the `bp_template_asset_directory_urls` filter. The array keys are directory paths and the values are URLs. More specific directories should appear before broader ones.

```php
function my_plugin_template_directory() {
	return plugin_dir_path( __FILE__ ) . 'templates';
}

bp_register_template_stack( 'my_plugin_template_directory', 5 );

add_filter(
	'bp_template_asset_directory_urls',
	function ( $directories ) {
		$directories[ my_plugin_template_directory() ] = plugin_dir_url( __FILE__ ) . 'templates';
		return $directories;
	}
);
```

For example, an asset at `templates/buddypress/css/custom.css` in this plugin receives a URL ending in `templates/buddypress/css/custom.css`. The directory key may be a symlink or its physical target. The mapping does not alter template lookup order or the `file` value.
