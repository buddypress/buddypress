<div class="content-header">
	
</div>

<div id="content">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>

		<div class="info-group">
			<h4>Admins</h4>
			<?php bp_group_list_admins() ?>
		</div>
	</div>

	<div class="main-column">

		<div id="group-name">
			<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
			<p class="status"><?php bp_group_type() ?></p>
		</div>

		<div class="info-group">
			<h4>Group Members</h4>
			
			<form action="<?php bp_group_form_action('members') ?>" method="post" id="group-members-form">
			<?php if ( bp_group_has_members() ) : ?>
				
				<?php if ( bp_group_member_needs_pagination() ) : ?>
					<div id="member-count" class="pag-count">
						<?php bp_group_member_pagination_count() ?>
					</div>

					<div id="member-pagination" class="pagination-links">
						<?php bp_group_member_pagination() ?>
					</div>
				<?php endif; ?>
				
				<ul id="member-list" class="item-list">
				<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
					<li>
						<?php bp_group_member_avatar() ?>
						<h5><?php bp_group_member_link() ?></h5>
						<span class="activity"><?php bp_group_member_joined_since() ?></span>
						
						<?php if ( function_exists( 'friends_install' ) ) : ?>
							<div class="action">
								<?php bp_add_friend_button( bp_group_member_id() ) ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
				</ul>
			<?php else: ?>

				<div id="message" class="info">
					<p>This group has no members.</p>
				</div>

			<?php endif;?>
			
			<input type="hidden" name="group_id" id="group_id" value="<?php bp_group_id() ?>" />
			</form>
		</div>
	
	</div>
	
	<?php endwhile; endif; ?>
</div>