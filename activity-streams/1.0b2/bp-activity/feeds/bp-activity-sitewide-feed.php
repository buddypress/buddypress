<?php
/**
 * RSS2 Feed Template for displaying the site wide activity stream.
 *
 * @package BuddyPress
 */

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
	<title><?php echo get_site_option( 'site_name' ); ?> - <?php _e( 'Site Wide Activity', 'buddypress' ) ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo site_url() . '/' . $bp['activity']['slug'] . '/feed' ?></link>
	<description><?php _e( 'Site Wide Activity Feed', 'buddypress' ) ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', bp_activity_get_last_updated(), false); ?></pubDate>
	<generator>http://buddypress.org/?bp-activity-version=<?php echo BP_ACTIVITY_VERSION ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('bp_activity_sitewide_feed_head'); ?>
	
	<?php $activity_items = BP_Activity_Activity::get_sitewide_items_for_feed( 35 ) ?>
	
	<?php foreach ( $activity_items as $activity ) { ?>
		<?php if ( $activity['title'] == '' && $activity['description'] == '' ) continue; ?>
	<item>
		<title><![CDATA[<?php echo $activity['title'] ?>]]></title>
		<link><?php echo $activity['link'] ?></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $activity['pubdate'], false); ?></pubDate>

		<description><![CDATA[<?php echo $activity['description'] ?>]]></description>
	<?php do_action('rss2_item'); ?>
	</item>
	<?php } ?>
</channel>
</rss>
