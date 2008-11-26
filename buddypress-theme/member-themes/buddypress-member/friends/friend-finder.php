<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<h2><?php _e( 'Friend Finder', 'buddypress' ); ?></h2>
	
	<div class="left-menu">
		<?php bp_friend_search_form('Find Friends') ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<div class="pagination-links" id="finder-pag">
			<?php bp_friend_pagination() ?>
		</div>
		
		<?php if ( bp_has_users() ) : ?>
			<ul id="friend-list" class="item-list">
			<?php while ( bp_user_users() ) : bp_the_user(); ?>
				<li class="hcard">
					<?php bp_user_avatar_thumb() ?>
					<h4 class="fn"><?php bp_user_url() ?></h4>
					<span class="activity"><?php bp_user_last_active() ?></span>
				</li>
			<?php endwhile; ?>
			</ul>
		<?php else: ?>

			<div id="finder-message">
				<div id="message" class="info">
					<p>
					   <strong><?php _e( 'Find your Friends using Friend Finder!', 'buddypress' ); ?></strong><br />
					   <?php _e( 'Use the search box to find friends on the site. You can enter any type of information you want, a first or last name, email address or any specific interesting information.', 'buddypress' ); ?></p>
				</div>
			</div>
			
		<?php endif;?>
	</div>
</div>

<?php get_footer() ?>