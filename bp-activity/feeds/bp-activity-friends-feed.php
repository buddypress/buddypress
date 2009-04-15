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
	<?php do_action('bp_activity_friends_feed'); ?>
>

<channel>
	<title><?php echo $bp->displayed_user->fullname; ?> - <?php _e( 'Friends Activity', 'buddypress' ) ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo $bp->displayed_user->domain . $bp->activity->slug . '/my-friends/feed' ?></link>
	<description><?php printf( __( '%s - Friends Activity Feed', 'buddypress' ), $bp->displayed_user->fullname ) ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', bp_activity_get_last_updated(), false); ?></pubDate>
	<generator>http://buddypress.org/?bp-activity-version=<?php echo BP_ACTIVITY_VERSION ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('bp_activity_friends_feed_head'); ?>
	
	<?php if ( bp_has_activities( 'type=friends&max=50' ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<item>
				<title><![CDATA[<?php bp_activity_feed_item_title() ?>]]></title>
				<link><?php echo bp_activity_feed_item_link() ?></link>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', bp_activity_feed_item_date(), false); ?></pubDate>

				<description><![CDATA[<?php bp_activity_feed_item_description() ?>]]></description>
			<?php do_action('bp_activity_personal_feed_item'); ?>
			</item>
		<?php endwhile; ?>

	<?php endif; ?>
</channel>
</rss>
