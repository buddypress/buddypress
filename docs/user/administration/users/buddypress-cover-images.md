# Theme compatibility features

BuddyPress allows members and groups to upload and manage their cover images. This feature enhances the visual appeal of profiles and group pages by providing a customizable background image. Under the hood it uses the BP Theme Compat features API which was developed in order to transpose the WP Theme Supports API into the BP Template Packs world.

## Theme Compat feature registration

Just like WordPress is allowing themes to opt-in for specific functionalities (eg: the [custom header](https://developer.wordpress.org/themes/functionality/custom-headers/)) using the `add_theme_support()` function inside a `'after_setup_theme'` hook callback function, you need to wait for the `'bp_after_setup_theme'` hook to be fired to register your Theme Compat feature using the `bp_set_theme_compat_feature()` function.

```php
function set_my_template_pack_features() {
    $template_pack_id = 'my-template-pack-id';
    $feature_args     = array(
        'name'    => 'my_theme_compat_feature',
        'settings' => array(
            'components' => array( 'groups', 'members' ),
        ),
    );

    // Registers the compat feature into your template pack.
    bp_set_theme_compat_feature( $template_pack_id, $feature_args );
}
add_action( 'bp_after_setup_theme', 'set_my_template_pack_features' );
`

### `bp_set_theme_compat_feature()`
Use this function to set a Theme Compat feature.

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

## Getting the Theme Compat feature settings

When you need to get the settings of a registered Theme Compat feature, you can use the `bp_get_theme_compat_feature()` function.

### `bp_get_theme_compat_feature()`

Use this function to get a Theme Compat feature settings. It returns `false` if something went wrong or an object keyed width the settings argument keys containing the settings values.

_This function was introduced in version 2.4.0_

#### Arguments

`bp_get_theme_compat_feature()` accepts one argument: `$name`.

| Argument | Type | Required | Defaults | Description |
|---|---|---|---|---|
|`$name`|`string`|Yes|`''`|the name key of the feature|

## About the Cover Image Theme Compat feature

Just like it does it can be a good idea for your Theme Compat feature to include extra filters into your code to let advanced users customize it to better match their theme. Below are examples about how it's possible to modify default cover image settings or completely disable the feature.

### Changing Cover Image Sizes

You can change the default cover image sizes by adding a filter in your  `bp-custom.php` file.

```php
function my_custom_cover_image_dimensions( $settings = array() ) {
    $settings['width']  = 1400;
    $settings['height'] = 300;
    return $settings;
}
add_filter( 'bp_before_members_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
add_filter( 'bp_before_groups_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
```

This example changes the cover image size to 1400px wide by 300px tall.

### Changing default cover image

When users haven't customized their cover images yet, this image will be used as a fallback.

```php
function my_custom_cover_xprofile_cover_image( $settings = array() ) {
    $settings['default_cover'] = 'https://site.url/to/your/default_cover_image.jpg';

    return $settings;
}
add_filter( 'bp_before_members_cover_image_settings_parse_args', 'my_custom_cover_xprofile_cover_image', 10, 1 );
```

In this example, replace `https://site.url/to/your/default_cover_image.jpg` with the URL of your default cover image.


### Disable cover images for members or groups

You can disable cover images by adding a filter to your `bp-custom.php` file.

```php
// For members :
add_filter( 'bp_is_members_cover_image_active', '__return_false' );

// For groups :
add_filter( 'bp_is_groups_cover_image_active', '__return_false' );
```
