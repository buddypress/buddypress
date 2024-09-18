# BuddyPress Embeds Documentation

## Overview

BuddyPress introduced support for embedding single activity items starting from version 2.6.0, utilizing the WordPress oEmbed functionality. This allows activity items to be embedded within WordPress posts or pages by pasting the activity permalink into the editor.

### Embedding Activity Items

Users can embed a BuddyPress activity item by copying the activity permalink and pasting it into a WordPress post or page. The embedded activity will display content, media (if applicable), and comments.

---

## Disabling Activity Embeds

If you prefer not to use activity embeds on your BuddyPress site, you can disable the feature using the following code snippet:

```php
add_filter( 'bp_is_activity_embeds_active', '__return_false' );
```

---

## Media Support in Embeds

When an activity item contains media from a registered WordPress oEmbed provider, the media will be displayed in the embedded content. If no oEmbed media is found, BuddyPress will attempt to embed inline video or audio using HTML5.

For a list of supported oEmbed providers, visit [WordPress oEmbed Providers](https://codex.wordpress.org/Embeds).

---

## Overriding Embed Templates

### BuddyPress Legacy Template Pack

To customize how activity embeds are displayed using the Legacy template pack, copy the relevant template files from the BuddyPress plugin directory to your theme.

#### Steps:

1. Copy the embed template files:

   ```bash
   /wp-content/plugins/buddypress/bp-templates/bp-legacy/buddypress/assets/embeds/
   ```

   into your theme directory:

   ```bash
   /wp-content/themes/YOUR-THEME/buddypress/assets/embeds/
   ```

2. Make your changes to the copied templates. You can override the following templates:

   - `header-activity.php`: Customize the activity header.
   - `footer.php`: Modify the footer for embeds.
   - `activity.php`: Customize the main content of embedded activity items.

3. If you want to customize the CSS, copy:
   ```bash
   /wp-content/plugins/buddypress/bp-templates/bp-legacy/css/embeds-activity.css
   ```
   into your theme directory:
   ```bash
   /wp-content/themes/YOUR-THEME/buddypress/css/embeds-activity.css
   ```

### BuddyPress Nouveau Template Pack

The same process applies to the Nouveau template pack. Copy the embed templates from:

```bash
/src/bp-templates/bp-nouveau/buddypress/assets/embeds/
```

To your theme’s BuddyPress folder. This will allow you to override and customize:

- `header-activity.php`
- `footer.php`
- `activity.php`

You can also copy and modify the styles in:

```bash
/src/bp-templates/bp-nouveau/css/embeds-activity.css
```

---

## REST API Endpoints for Embeds

BuddyPress registers a custom oEmbed endpoint that handles activity embeds using the WordPress REST API. The endpoint allows you to fetch and display embedded activity items.

### oEmbed REST API Endpoint:

```
/wp-json/oembed/1.0/embed/activity?url=URL_TO_ACTIVITY_ITEM
```

#### Supported Parameters:

- **`url`**: The permalink of the activity item.
- **`format`**: The format of the embed (default: json).
- **`maxwidth`**: The maximum width of the embed.
- **`hide_media`**: Set to `true` to hide media from the embed.

Example usage:

```bash
curl "https://example.com/wp-json/oembed/1.0/embed/activity?url=https://example.com/members/user/activity/123"
```

---

## Extending BuddyPress Embeds

### Adding Custom Hooks

BuddyPress allows developers to extend and modify the embed functionality through various hooks and filters.

#### Key Filters:

- **`bp_activity_embed_html`**: Modify the HTML output for the activity embed iframe.
- **`bp_activity_embed_fallback_html`**: Customize the fallback HTML for the activity embed.
- **`bp_activity_get_embed_excerpt`**: Modify the excerpt generated for the embed.
- **`bp_activity_embed_display_media`**: Control whether media is displayed in the embed.

Example: Adding a custom element to the embed footer

```php
add_action( 'get_template_part_assets/embeds/footer', 'my_custom_embed_footer' );
function my_custom_embed_footer() {
    echo '<div>Custom footer content</div>';
}
```

### Customizing the oEmbed Response

You can modify the oEmbed response data using the `BP_Activity_oEmbed_Extension` class by hooking into its methods. This class manages the activity embed endpoint, ensuring proper response formatting and validation.

Example:

```php
class My_Custom_BP_Activity_oEmbed extends BP_Activity_oEmbed_Extension {
    protected function set_oembed_response_data( $item_id ) {
        $response = parent::set_oembed_response_data( $item_id );
        // Add custom data to the response.
        $response['custom_field'] = 'My custom data';
        return $response;
    }
}
```

---

## Use Cases for BuddyPress Embeds

### Use Case 1: Embedding Activity Items in WordPress Posts

Users can easily share individual BuddyPress activity items by embedding them into WordPress posts or pages. This allows for greater interaction and sharing of community updates.

### Use Case 2: Restricting Media Display in Embeds

For sites that want to hide media in embedded activity items (e.g., to keep embeds simple), the `hide_media` parameter can be used to suppress media display.

Example:

```php
https://example.com/wp-json/oembed/1.0/embed/activity?url=https://example.com/members/user/activity/123&hide_media=true
```

### Use Case 3: Customizing the Look and Feel of Activity Embeds

Developers can fully customize the appearance of embedded activity items by overriding the default templates and CSS provided by BuddyPress. This allows for a seamless integration of activity embeds with your site's design.

---
