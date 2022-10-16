<?php
/**
 * BuddyPress - Community gate template
 *
 * @since 11.0.0
 * @version 11.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<p><?php bp_community_visibility_information(); ?></p>
</div>
