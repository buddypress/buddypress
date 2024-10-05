# Extending BuddyPress Activity Embeds

BuddyPress registers a custom oEmbed endpoint that handles activity embeds using the WordPress REST API. The endpoint allows you to fetch and display embedded activity items.

## oEmbed REST API Endpoint:

```
/wp-json/oembed/1.0/embed/activity?url=URL_TO_ACTIVITY_ITEM
```

## Supported Parameters:

- **`url`**: The permalink of the activity item.
- **`format`**: The format of the embed (default: json).
- **`maxwidth`**: The maximum width of the embed.
- **`hide_media`**: Set to `true` to hide media from the embed.

Example usage:

```bash
curl "https://example.com/wp-json/oembed/1.0/embed/activity?url=https://example.com/members/user/activity/123"
```

## Extending BuddyPress Activity Embeds

### Adding Custom Hooks

BuddyPress allows developers to extend and modify the embed functionality through various hooks and filters.

#### Key Filters:

- **`bp_activity_embed_html`**: Modify the HTML output for the activity embed iframe.
- **`bp_activity_embed_fallback_html`**: Customize the fallback HTML for the activity embed.
- **`bp_activity_get_embed_excerpt`**: Modify the excerpt generated for the embed.
- **`bp_activity_embed_display_media`**: Control whether media is displayed in the embed.

Example: Adding a custom element to the embed footer

```php
function my_custom_embed_footer() {
    echo '<div>Custom footer content</div>';
}
add_action( 'get_template_part_assets/embeds/footer', 'my_custom_embed_footer' );
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
