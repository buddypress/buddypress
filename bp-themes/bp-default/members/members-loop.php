<?php /* Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_object_filter() */ ?>

<?php do_action( 'bp_before_members_loop' ) ?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>

	<div class="pagination">

		<div class="pag-count" id="member-dir-count">
			<?php bp_members_pagination_count() ?>
		</div>

		<div class="pagination-links" id="member-dir-pag">
			<?php bp_members_pagination_links() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_directory_members_list' ) ?>

	<ul id="members-list" class="item-list">
	<?php while ( bp_members() ) : bp_the_member(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_member_permalink() ?>"><?php bp_member_avatar() ?></a>
			</div>

			<div class="item">
				<div class="item-title">
					<a href="<?php bp_member_permalink() ?>"><?php bp_member_name() ?></a>
					<?php if ( bp_get_member_latest_update() ) : ?>
						<span class="update"> - <?php bp_member_latest_update( 'length=10' ) ?></span>
					<?php endif; ?>
				</div>
				<div class="item-meta"><span class="activity"><?php bp_member_last_active() ?></span></div>

				<?php do_action( 'bp_directory_members_item' ) ?>

				<?php
				 /***
				  * If you want to show specific profile fields here you can,
				  * but it'll add an extra query for each member in the loop
				  * (only one regadless of the number of fields you show):
				  *
				  * bp_member_profile_data( 'field=the field name' );
				  */
				?>
			</div>

			<div class="action">
				<?php bp_member_add_friend_button() ?>

				<?php do_action( 'bp_directory_members_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_members_list' ) ?>

	<?php bp_member_hidden_fields() ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( "Sorry, no members were found.", 'buddypress' ) ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_members_loop' ) ?>