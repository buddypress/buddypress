<?php do_action( 'bp_before_my_friends_loop' ) ?>		

<div id="friends-loop">
	
	<?php if ( bp_has_friendships() ) : ?>
		
		<div class="pagination">

			<div class="pag-count">
				<?php bp_friend_pagination_count() ?>
			</div>
			
			<div class="pagination-links" id="pag">
				<?php bp_friend_pagination() ?>
			</div>
		
		</div>
		
		<?php do_action( 'bp_before_my_friends_list' ) ?>
		
		<ul id="friend-list" class="item-list">
			<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
			
				<li>
					<?php bp_friend_avatar_thumb() ?>
					<h4><?php bp_friend_link() ?></h4>
					<span class="activity"><?php bp_friend_last_active() ?></span>

					<?php do_action( 'bp_my_friends_list_item' ) ?>	
								
					<div class="action">
						<?php bp_add_friend_button() ?>
						
						<?php do_action( 'bp_my_friends_list_item_action' ) ?>
					</div>
				</li>
		
			<?php endwhile; ?>
		</ul>

		<?php do_action( 'bp_after_my_friends_list' ) ?>
		
	<?php else: ?>

		<?php if ( bp_friends_is_filtered() ) : ?>
			
			<div id="message" class="info">
				<p><?php _e( "No friends matched your search filter terms", 'buddypress' ) ?></p>
			</div>			
			
		<?php else : ?>
			
			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "Your friends list is currently empty", 'buddypress' ), __( "%s's friends list is currently empty", 'buddypress' ) ) ?></p>
			</div>
			
		<?php endif; ?>
		
		<?php if ( bp_is_home() && !bp_friends_is_filtered() ) : ?>

			<?php do_action( 'bp_before_random_members_list' ) ?>
						
			<h3><?php _e( 'Why not make friends with some of these members?', 'buddypress' ) ?></h3>
			<?php bp_friends_random_members() ?>

			<?php do_action( 'bp_after_random_members_list' ) ?>
		
		<?php endif; ?>
		
	<?php endif;?>
	
</div>

<?php do_action( 'bp_after_my_friends_loop' ) ?>	