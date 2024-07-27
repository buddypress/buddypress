<?php
/**
 * Show message and log-in form when the currently requested
 * screen is not accessible by the current user.
 *
 * @package BuddyPress
 * @subpackage bp-nouveau
 *
 * @since 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e( 'This community area is accessible to logged-in members only.', 'buddypress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:bp/login-form {"forgotPwdLink":true} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
