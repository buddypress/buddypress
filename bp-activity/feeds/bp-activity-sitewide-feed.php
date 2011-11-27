<?php

/**
 * RSS2 Feed Template for displaying the site wide activity stream.
 *
 * @package BuddyPress
 * @subpackage ActivityFeeds
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
header('Status: 200 OK');
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	<?php do_action('bp_activity_sitewide_feed'); ?>
>

<channel>
	<?php /* translators: Sitewide activity RSS title - "[Site Name] | Site Wide Activity" */ ?>
	<title><?php printf( '%1$s | %2$s', bp_get_site_name(), __( 'Site Wide Activity', 'buddypress' ) ) ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/feed' ?></link>
	<description><?php _e( 'Site Wide Activity Feed', 'buddypress' ) ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_activity_get_last_updated(), false); ?></pubDate>
	<generator>http://buddypress.org/?v=<?php echo BP_VERSION ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('bp_activity_sitewide_feed_head'); ?>

	<?php if ( bp_has_activities( 'type=sitewide&max=50&display_comments=threaded' ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<item>
				<guid><?php bp_activity_thread_permalink() ?></guid>
				<title><?php bp_activity_feed_item_title() ?></title>
				<link><?php bp_activity_thread_permalink() ?></link>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_get_activity_feed_item_date(), false); ?></pubDate>

				<description>
					<![CDATA[
					<?php bp_activity_feed_item_description() ?>

					<?php if ( bp_activity_can_comment() ) : ?>
						<p><?php printf( __( 'Comments: %s', 'buddypress' ), bp_activity_get_comment_count() ); ?></p>
					<?php endif; ?>
					]]>
				</description>
			<?php do_action('bp_activity_personal_feed_item'); ?>
			</item>
		<?php endwhile; ?>

	<?php endif; ?>
</channel>
</rss>
