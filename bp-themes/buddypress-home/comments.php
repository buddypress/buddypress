<?php // Do not delete these lines
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>

			<p class="nocomments"><?php _e( 'This post is password protected. Enter the password to view comments.' ) ?></p>

			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'class="alt" ';
?>

<!-- You can start editing here. -->

<div id="comments-section">
<?php if ($comments) : ?>
	<h3 id="responses"><?php comments_number( __( 'No Responses', 'buddypress' ), __( 'One Response', 'buddypress' ), __( '% Responses', 'buddypress' ) );?> <?php _e( 'to', 'buddypress' ) ?> &#8220;<?php the_title(); ?>&#8221;</h3>
		
	<ol class="commentlist" id="comments">

		<?php foreach ($comments as $comment) : ?>

		<li <?php echo $oddcomment; ?>id="comment-<?php comment_ID() ?>">
			<div class="comment-details">
				<?php bp_comment_author_avatar() ?>
				<p><?php comment_author_link() ?> <?php _e( 'said:', 'buddypress' ) ?></p>
			</div>

			<div class="comment-content">
				<?php if ($comment->comment_approved == '0') : ?>
					<p><strong><?php _e( 'Your comment is awaiting moderation.', 'buddypress' ) ?></strong></p>
				<?php endif; ?>
				<?php comment_text() ?>

				<p class="commentmetadata"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date('F jS, Y') ?> <?php _e( 'at', 'buddypress' ) ?> <?php comment_time() ?></a> <?php edit_comment_link('Edit','&nbsp; [ ',' ]'); ?></p>
			</div>
			<div class="clear"></div>
		</li>

		<?php
		/* Changes every other comment to a different class */
		$oddcomment = ( empty( $oddcomment ) ) ? 'class="alt" ' : '';
		?>

		<?php endforeach; /* end for each comment */ ?>

	</ol>
<?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments"><?php _e( 'Comments are closed.', 'buddypress' ) ?></p>
	<?php endif; ?>
	
<?php endif; ?>
</div>
	<?php if ( $user_ID ) : ?>
	</div>
	<?php endif; ?>


<div id="compose-reply">
	<?php if ('open' == $post->comment_status) : ?>

	<h3 id="respond"><?php _e( 'Leave a Reply', 'buddypress' ) ?></h3>

	<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
		<p><?php printf( __( 'You must be <a href="%s">logged in</a> to post a comment.', 'buddypress' ), get_option('siteurl') . '/wp-login.php?redirect_to=' . urlencode(get_permalink()) ) ?></p>
	<?php else : ?>

	<form action="<?php echo site_url(); ?>/wp-comments-post.php" method="post" id="commentform">

	<?php if ( $user_ID ) : ?>

		<p><?php _e( 'Logged in as', 'buddypress' ) ?> <a href="<?php echo site_url(); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo site_url(); ?>/wp-login.php?action=logout" title="Log out of this account"><?php _e( 'Logout', 'buddypress' ) ?> &raquo;</a></p>

	<?php else : ?>

	<p><input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
	<label for="author"><?php _e( 'Name', 'buddypress' ) ?> <?php if ($req) _e( '(required)', 'buddypress' ); ?></label></p>

	<p><input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
	<label for="email"><?php _e( 'Mail (will not be published)', 'buddypress' ) ?> <?php if ($req) _e( '(required)', 'buddypress' ); ?></label></p>

	<p><input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
	<label for="url"><?php _e( 'Website', 'buddypress' ) ?></label></p>

	<?php endif; ?>

	<p><textarea name="comment" id="comment" cols="38" rows="10" tabindex="4"></textarea></p>

	<p><input name="submit" type="submit" id="submit" tabindex="5" value="<?php _e ( 'Submit Comment', 'buddypress' ) ?>" />
	<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
	</p>
	<?php do_action('comment_form', $post->ID); ?>

</form>
<?php endif; // If registration required and not logged in ?>
</div>

<?php endif; // if you delete this the sky will fall on your head ?>

