<?php get_header(); ?>

	<div class="content-header">
	</div>

	<div id="content">
		<div class="pagination-links" id="pag">
			<?php bp_friend_pagination() ?>
		</div>
		
		<h2>My Friends</h2>
		
		<div class="left-menu">
			<?php bp_friend_search_form('Search Friends') ?>
		</div>
		
		<div class="main-column">
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>
			
			<?php if ( bp_has_friendships() ) : ?>
				<ul id="friend-list">
				<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
					<li>
						<?php bp_friend_avatar_thumb() ?>
						<h4><?php bp_friend_link() ?></h4>
						<span class="activity"><?php bp_friend_last_active() ?></span>
						<hr />
					</li>
				<?php endwhile; ?>
			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e('Your friends list is currently empty.'); ?></p>
				</div>

			<?php endif;?>
		</div>
	</div>

<?php get_footer() ?>