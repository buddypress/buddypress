# BuddyPress Cover Images

BuddyPress allows members and groups to upload and manage their cover images. This feature enhances the visual appeal of profiles and group pages by providing a customizable background image. Here’s how you can manage BuddyPress cover images in your theme.

## Table of Contents

1. [Introduction](#introduction)
2. [Default Cover Image Sizes](#default-cover-image-sizes)
3. [Changing Cover Image Sizes](#changing-cover-image-sizes)
4. [Cover Image Uploading](#cover-image-uploading)
5. [Overriding Template Files](#overriding-template-files)
6. [Customizing with CSS](#customizing-with-css)
7. [Frequently Asked Questions](#frequently-asked-questions)

## Introduction

BuddyPress cover images allow users to personalize their profiles and groups by adding a background image. This feature is similar to cover images on social media platforms like Facebook and Twitter. By default, cover images are enabled for both members and groups.

## Default Cover Image Sizes

By default, BuddyPress sets the cover image size to 1300px wide by 225px tall. These dimensions ensure that cover images look good on a variety of devices and screen sizes.

## Changing Cover Image Sizes

You can change the default cover image sizes by adding a filter in your theme’s `functions.php` file.

```PHP
function my_custom_cover_image_dimensions( $settings = array() ) {
    $settings['width']  = 1400;
    $settings['height'] = 300;
    return $settings;
}
add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
add_filter( 'bp_before_groups_cover_image_settings_parse_args', 'my_custom_cover_image_dimensions' );
```

This example changes the cover image size to 1400px wide by 300px tall.

```php
function my_custom_cover_xprofile_cover_image( $settings = array() ) {
    $settings['default_cover'] = 'https://site.url/to/your/default_cover_image.jpg';

    return $settings;
}
add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', 'my_custom_cover_xprofile_cover_image', 10, 1 );
```

In this example, replace 'https://site.url/to/your/default_cover_image.jpg' with the URL of your default cover image.

## Cover Image Uploading

Users can upload cover images through their profile or group settings:

1. **For Members:** Go to the profile page, click on the cover image area, and select "Change Cover Image."
2. **For Groups:** Go to the group page, click on the cover image area, and select "Change Cover Image."

Follow the prompts to upload the cover image.

# Overriding BuddyPress Templates in a Child Theme

To override the BuddyPress Single Member Cover Image and Group Cover Image templates in your child theme.

## For Single Member Cover Image

**Copy the file from the plugin directory to your child theme:**

    - **Source:** `wp-content/plugins/bp-nouveau/buddypress/members/single/cover-image-header.php`
    - **Destination:** `wp-content/themes/your-child-theme/buddypress/members/single/cover-image-header.php`

## For Group Cover Image

**Copy the file from the plugin directory to your child theme:**

    - **Source:** `wp-content/plugins/bp-nouveau/buddypress/groups/single/cover-image-header.php`
    - **Destination:** `wp-content/themes/your-child-theme/buddypress/groups/single/cover-image-header.php`

By following these steps, you can successfully override the BuddyPress templates for single member cover image and group cover image in your child theme.

Edit these files to customize the layout and design of the cover images.

## Customizing with CSS

You can further customize the appearance of cover images using CSS. Add your custom styles to your theme’s stylesheet or a custom CSS plugin.

## Frequently Asked Questions

### How do I disable cover images for members or groups?

You can disable cover images by adding a filter to your theme’s `functions.php` file.

```PHP
// For members :
add_filter( 'bp_is_members_cover_image_active', '__return_false' );

// For groups :
add_filter( 'bp_is_groups_cover_image_active', '__return_false' );
```
