<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">
	<h2><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?></h2>
	
	<div class="left-menu">
		<?php bp_friend_search_form('Search Friends') ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<?php if ( bp_has_friendships() ) : ?>
			<div class="pagination-links" id="pag">
				<?php bp_friend_pagination() ?>
			</div>
			
			<ul id="friend-list" class="item-list">
			<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
				<li>
					<?php bp_friend_avatar_thumb() ?>
					<h4><?php bp_friend_link() ?></h4>
					<span class="activity"><?php bp_friend_last_active() ?></span>
				</li>
			<?php endwhile; ?>
			</ul>
		<?php else: ?>

			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "Your friends list is currently empty", 'buddypress' ), __( "%s's friends list is currently empty", 'buddypress' ) ) ?></p>
			</div>

		<?php endif;?>
	</div>
</div>

<?php get_footer() ?>