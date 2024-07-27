# Theme compatibility features

BuddyPress allows members and groups to upload and manage their Cover Images. This feature enhances the visual appeal of profiles and group pages by providing a customizable background image.

**Under the hood** it uses the BP Theme Compat Features API which was developed in order to transpose the WP Theme Supports API into the BP Template Packs world.

## Theme compatibility feature registration

Just like WordPress is allowing Themes to opt-in for specific functionalities (eg: the [custom header](https://developer.wordpress.org/themes/functionality/custom-headers/)) using the `add_theme_support()` function inside a `'after_setup_theme'` hook callback function, you need to wait for the `'bp_after_setup_theme'` hook to be fired to register your Theme Compat feature using the `bp_set_theme_compat_feature()` function.

```php
function set_my_template_pack_features() {
    $template_pack_id = 'my-template-pack-id';
    $feature_args     = array(
        'name'    => 'my_theme_compat_feature',
        'settings' => array(
            'components' => array( 'groups', 'members' ),
        ),
    );

    // Registers the Theme Compat feature into your template pack.
    bp_set_theme_compat_feature( $template_pack_id, $feature_args );
}
add_action( 'bp_after_setup_theme', 'set_my_template_pack_features' );
```

### `bp_set_theme_compat_feature()`
Use this function to set a Theme compatibility feature.

_This function was introduced in version 2.4.0_

#### Arguments

`bp_members_get_user_url()` accepts two arguments: the Template Pack ID and an associative array of settings.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$template_pack_id`|`string`|Yes|`''`|The Template Pack ID to set the feature for|
|`$feature_args`|`array`|No|`[]`|This associative array of arguments is described below|

`$feature_args` list of arguments:

| Array key | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`name`|`string`|No|`''`|The name key of the feature.|
|`settings`|`array`|No|`[]`|Used to pass as many as needed additional variables. **NB** using a `components` key with an array listing component IDs that the feature is targeting will link it to these components. In this case you can use `bp_is_active( 'members', 'my_theme_compat_feature' )` to check whether you should load the feature's code or not.|

## Getting the Theme compatibility feature settings

When you need to get the settings of a registered Theme Compat feature, you can use the `bp_get_theme_compat_feature()` function.

### `bp_get_theme_compat_feature()`

Use this function to get a Theme compatibility feature's settings. It returns `false` if something went wrong or an object keyed with the settings argument keys containing the settings values.

_This function was introduced in version 2.4.0_

#### Arguments

`bp_get_theme_compat_feature()` accepts one argument: `$name`.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$name`|`string`|Yes|`''`|the name key of the feature|

## Get inspired by the Cover Image Theme compatibility feature

Just like this feature does, including extra filters into your code to let advanced users to customize it to better match their Theme can be a good idea.

Below are examples to feed your imagination about how it's possible to modify the BP built-in Cover Image Theme compat feature default settings or completely disable it.

### Changing Cover Image Sizes

You can change the default Cover Image sizes by adding a filter in your `bp-custom.php` file.

```php
/**
 * PS: The Cover Image feature is using the `bp_parse_args()` function. 
 */ 
function my_custom_cover_image_dimensions( $settings = array() ) {
    $settings['width']  = 1400;
    $settings['height'] = 300;

    return $settings;
}
add_filter( 'bp_before_members_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
add_filter( 'bp_before_groups_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
```

### Changing default Cover Image

When users haven't customized their Cover Images yet, this image will be used as a fallback.

```php
/**
 * PS: The Cover Image feature is using the `bp_parse_args()` function. 
 */ 
function my_custom_cover_xprofile_cover_image( $settings = array() ) {
    $settings['default_cover'] = 'https://site.url/to/your/default_cover_image.jpg';

    return $settings;
}
add_filter( 'bp_before_members_cover_image_settings_parse_args', 'my_custom_cover_xprofile_cover_image', 10, 1 );
```

### Disable Cover Images for members or groups

You can disable Cover Images by adding a filter to your `bp-custom.php` file.

```php
// For members :
add_filter( 'bp_is_members_cover_image_active', '__return_false' );

// For groups :
add_filter( 'bp_is_groups_cover_image_active', '__return_false' );
```

> [!NOTE]
> Using a `bp_is_{$component_id}_{$feature}_active` filter is made possible by the fact the Cover Image feature is using the `$components` feature argument to set it to `members` & `groups`. 
