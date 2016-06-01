<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-user-query.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-user.php';
require dirname( __FILE__ ) . '/classes/class-bp-date-query.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-notification.php';
require dirname( __FILE__ ) . '/classes/class-bp-button.php';
require dirname( __FILE__ ) . '/classes/class-bp-embed.php';
require dirname( __FILE__ ) . '/classes/class-bp-walker-nav-menu.php';
require dirname( __FILE__ ) . '/classes/class-bp-walker-nav-menu-checklist.php';
require dirname( __FILE__ ) . '/classes/class-bp-suggestions.php';
require dirname( __FILE__ ) . '/classes/class-bp-members-suggestions.php';
require dirname( __FILE__ ) . '/classes/class-bp-recursive-query.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-sort-by-key-callback.php';
require dirname( __FILE__ ) . '/classes/class-bp-media-extractor.php';
require dirname( __FILE__ ) . '/classes/class-bp-attachment.php';
require dirname( __FILE__ ) . '/classes/class-bp-attachment-avatar.php';
require dirname( __FILE__ ) . '/classes/class-bp-attachment-cover-image.php';
require dirname( __FILE__ ) . '/classes/class-bp-email-recipient.php';
require dirname( __FILE__ ) . '/classes/class-bp-email.php';
require dirname( __FILE__ ) . '/classes/class-bp-email-delivery.php';
require dirname( __FILE__ ) . '/classes/class-bp-phpmailer.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-nav.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-nav-item.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-oembed-extension.php';

if ( buddypress()->do_nav_backcompat ) {
	require dirname( __FILE__ ) . '/classes/class-bp-core-bp-nav-backcompat.php';
	require dirname( __FILE__ ) . '/classes/class-bp-core-bp-options-nav-backcompat.php';
}
