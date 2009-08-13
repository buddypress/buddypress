<?php
/* Don't remove these lines. */
add_filter('comment_text', 'popuplinks');
while ( have_posts()) : the_post();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
     <title><?php echo get_option('blogname'); ?> - <?php _e( 'Comments on', 'buddypress' ) ?> <?php the_title(); ?></title>

	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<style type="text/css" media="screen">
		@import url( <?php bloginfo('stylesheet_url'); ?> );
		body { margin: 3px; }
	</style>

</head>
<body id="commentspopup">

<h1 id="header"><a href="" title="<?php echo get_option('blogname'); ?>"><?php echo get_option('blogname'); ?></a></h1>

<h2 id="comments"><?php _e( 'Comments', 'buddypress' ) ?></h2>

<p><a href="<?php echo get_post_comments_feed_link($post->ID); ?>"><?php _e( '<abbr title="Really Simple Syndication">RSS</abbr> feed for comments on this post.', 'buddypress' ) ?></a></p>

<?php if ('open' == $post->ping_status) { ?>
<p><?php _e( 'The <abbr title="Universal Resource Locator">URL</abbr> to TrackBack this entry is:', 'buddypress' ) ?> <em><?php trackback_url() ?></em></p>
<?php } ?>

<?php
// this line is WordPress' motor, do not delete it.
$commenter = wp_get_current_commenter();
extract($commenter);
$comments = get_approved_comments($id);
$post = get_post($id);
if (!empty($post->post_password) && $_COOKIE['wp-postpass_'. COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
	echo(get_the_password_form());
} else { ?>

<?php if ($comments) { ?>
<ol id="commentlist">
<?php foreach ($comments as $comment) { ?>
	<li id="comment-<?php comment_ID() ?>">
	<?php comment_text() ?>
	<p><cite><?php comment_type('Comment', 'Trackback', 'Pingback'); ?> <?php _e( 'by', 'buddypress' ) ?> <?php comment_author_link() ?> &#8212; <?php comment_date() ?> @ <a href="#comment-<?php comment_ID() ?>"><?php comment_time() ?></a></cite></p>
	</li>

<?php } // end for each comment ?>
</ol>
<?php } else { // this is displayed if there are no comments so far ?>
	<p><?php _e( 'No comments yet.', 'buddypress' ) ?></p>
<?php } ?>

<?php if ('open' == $post->comment_status) { ?>
<h2><?php _e( 'Leave a comment', 'buddypress' ) ?></h2>
<p><?php _e( 'Line and paragraph breaks automatic, e-mail address never displayed, <acronym title="Hypertext Markup Language">HTML</acronym> allowed:', 'buddypress' ) ?> <code><?php echo allowed_tags(); ?></code></p>

<form action="<?php echo site_url(); ?>/wp-comments-post.php" method="post" id="commentform">
<?php if ( $user_ID ) : ?> 
	<p><?php _e( 'Logged in as', 'buddypress' ) ?> <a href="<?php echo site_url(); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo site_url(); ?>/wp-login.php?action=logout" title="<?php _e( 'Log out of this account', 'buddypress' ) ?>"><?php _e( 'Logout', 'buddypress' ) ?> &raquo;</a></p>
<?php else : ?> 
	<p>
	  <input type="text" name="author" id="author" class="textarea" value="<?php echo $comment_author; ?>" size="28" tabindex="1" />
	   <label for="author"><?php _e( 'Name', 'buddypress' ) ?></label>
	<input type="hidden" name="comment_post_ID" value="<?php echo attribute_escape( $id ); ?>" />
	<input type="hidden" name="redirect_to" value="<?php echo attribute_escape($_SERVER["REQUEST_URI"]); ?>" />
	</p>

	<p>
	  <input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="28" tabindex="2" />
	   <label for="email"><?php _e( 'E-mail', 'buddypress' ) ?></label>
	</p>

	<p>
	  <input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="28" tabindex="3" />
	   <label for="url"><abbr title="Universal Resource Locator"><?php _e( 'URL', 'buddypress' ) ?></abbr></label>
	</p>
<?php endif; ?>

	<p>
	  <label for="comment"><?php _e( 'Your Comment', 'buddypress' ) ?></label>
	<br />
	  <textarea name="comment" id="comment" cols="70" rows="4" tabindex="4"></textarea>
	</p>

	<p>
	  <input name="submit" type="submit" tabindex="5" value="<?php _e( 'Say It!', 'buddypress' ) ?>" />
	</p>
	<?php do_action('comment_form', $post->ID); ?>
</form>
<?php } else { // comments are closed ?>
<p><?php _e( 'Sorry, the comment form is closed at this time.', 'buddypress' ) ?></p>
<?php }
} // end password check
?>

<div><strong><a href="javascript:window.close()"><?php _e( 'Close this window.', 'buddypress' ) ?></a></strong></div>

<?php // if you delete this the sky will fall on your head
endwhile;
?>

<!-- // this is just the end of the motor - don't touch that line either :) -->
<?php //} ?>
<p class="credit"><?php timer_stop(1); ?> <cite><?php _e( 'Powered by <a href="http://wordpress.org/" title="Powered by WordPress, state-of-the-art semantic personal publishing platform"><strong>Wordpress</strong>' ) ?></a></cite></p>
<?php // Seen at http://www.mijnkopthee.nl/log2/archive/2003/05/28/esc(18) ?>
<script type="text/javascript">
<!--
document.onkeypress = function esc(e) {
	if(typeof(e) == "undefined") { e=event; }
	if (e.keyCode == 27) { self.close(); }
}
// -->
</script>
</body>
</html>
