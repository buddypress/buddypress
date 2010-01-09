<form action="" method="post" id="whats-new-form" name="whats-new-form">

	<?php do_action( 'bp_before_activity_post_form' ) ?>

	<div id="whats-new-avatar">
		<?php bp_loggedin_user_avatar( 'width=60&height=60' ) ?>
	</div>

	<h5>
		<?php if ( bp_is_group_home() ) : ?>
			<?php printf( __( "What's new in %s, %s?", 'buddypress' ), bp_get_group_name(), bp_dtheme_firstname() ) ?>
		<?php else : ?>
			<?php printf( __( "What's new %s?", 'buddypress' ), bp_dtheme_firstname() ) ?>
		<?php endif; ?>
	</h5>

	<div id="whats-new-content">
		<div id="whats-new-textarea">
			<textarea name="whats-new" id="whats-new" value="" /><?php if ( isset( $_GET['r'] ) ) : ?>@<?php echo esc_attr( $_GET['r'] ) ?> <?php endif; ?></textarea>
		</div>

		<div id="whats-new-options">
			<div id="whats-new-submit">
				<span class="ajax-loader"></span> &nbsp;
				<input type="submit" name="aw-whats-new-submit" id="aw-whats-new-submit" value="<?php _e( 'Post Update', 'buddypress' ) ?>" />
			</div>

			<?php if ( !bp_is_my_profile() && !bp_is_group_home() ) : ?>
				<div id="whats-new-post-in-box">
					<?php _e( 'Post in', 'buddypress' ) ?>:

					<select id="whats-new-post-in" name="whats-new-post-in">
						<option selected="selected" value="0"><?php _e( 'My Profile', 'buddypress' ) ?></option>

						<?php if ( bp_has_groups( 'user_id=' . bp_loggedin_user_id() . '&type=alphabetical&max=100&per_page=100' ) ) : while ( bp_groups() ) : bp_the_group(); ?>
							<option value="<?php bp_group_id() ?>"><?php bp_group_name() ?></option>
						<?php endwhile; endif; ?>
					</select>
				</div>
			<?php elseif ( bp_is_group_home() ) : ?>
				<input type="hidden" id="whats-new-post-in" name="whats-new-post-in" value="<?php bp_group_id() ?>" />
			<?php endif; ?>

			<?php do_action( 'bp_activity_post_form_options' ) ?>

		</div><!-- #whats-new-options -->
	</div><!-- #whats-new-content -->

	<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>
	<?php do_action( 'bp_after_activity_post_form' ) ?>

</form><!-- #whats-new-form -->
