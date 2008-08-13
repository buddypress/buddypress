<?php get_header(); ?>

	<div class="content-header">
	</div>

	<div id="content">
		<div class="pagination-links" id="finder-pag">
			<?php bp_friend_pagination() ?>
		</div>
		
		<h2>Friend Finder</h2>
		
		<div class="left-menu">
			<?php bp_friend_search_form('Find Friends') ?>
		</div>
		
		<div class="main-column">
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>
			
			<?php if ( bp_has_users() ) : ?>
				<ul id="friend-list">
				<?php while ( bp_user_users() ) : bp_the_user(); ?>
					<li>
						<?php bp_user_avatar_thumb() ?>
						<h4><?php bp_user_url() ?></h4>
						<span class="activity">active <?php bp_user_last_active() ?> ago.</span>
						<hr />
					</li>
				<?php endwhile; ?>
				</ul>
			<?php else: ?>

				<div id="finder-message">
					<div id="message" class="info">
						<p>
						   <strong>Find your Friends using Friend Finder!</strong><br />
						   Use the search box to find friends on the site. 
						   You can enter any type of information you want, a first or last name, 
						   email address or any specific interesting information.
						</p>
					</div>
				</div>
				
			<?php endif;?>
		</div>
	</div>

<?php get_footer() ?>