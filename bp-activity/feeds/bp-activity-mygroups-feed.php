<?php

/**
 * RSS2 Feed Template for displaying a member's group's activity
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
	<?php do_action('bp_activity_mygroups_feed'); ?>
>

<channel>
	<?php /* translators: Member groups activity RSS title - "[Site Name] | [Displayed User Name] | My Groups - Public Activity" */ ?>
	<title><?php printf( '%1$s | %2$s | %3$s', bp_get_site_name(), bp_get_displayed_user_fullname(), __( 'My Groups - Public Activity', 'buddypress' ) ) ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo home_url( bp_get_activity_root_slug() . '/#my-groups/' ) ?></link>
	<?php /* translators: Member groups activity RSS description - "[Displayed user name] - My Groups - Public Activity" */ ?>
	<description><?php printf( __( '%1$s - My Groups - Public Activity', 'buddypress' ), bp_get_displayed_user_fullname() ) ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_activity_get_last_updated(), false); ?></pubDate>
	<generator>http://buddypress.org/?v=<?php echo BP_VERSION ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('bp_activity_mygroups_feed_head'); ?>

	<?php
		$groups = groups_get_user_groups( bp_loggedin_user_id() );
		$group_ids = implode( ',', $groups['groups'] );
	?>

	<?php if ( bp_has_activities( 'object=' . $bp->groups->id . '&primary_id=' . $group_ids . '&max=50&display_comments=threaded' ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<item>
				<guid><?php bp_activity_thread_permalink() ?></guid>
				<title><?php bp_activity_feed_item_title() ?></title>
				<link><?php echo bp_activity_thread_permalink() ?></link>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s O', bp_get_activity_feed_item_date(), false); ?></pubDate>

				<description>
					<![CDATA[
						<?php bp_activity_feed_item_description() ?>

						<?php if ( bp_activity_can_comment() ) : ?>
							<p><?php printf( __( 'Comments: %s', 'buddypress' ), bp_activity_get_comment_count() ); ?></p>
						<?php endif; ?>
					]]>
				</description>
				<?php do_action('bp_activity_mygroups_feed_item'); ?>
			</item>
		<?php endwhile; ?>

	<?php endif; ?>
</channel>
</rss>
