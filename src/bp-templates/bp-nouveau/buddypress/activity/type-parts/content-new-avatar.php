<?php
/**
 * BuddyPress - `new_avatar` activity type content part.
 *
 * This template is only used to display the `new_avatar` activity type content.
 *
 * @since 10.0.0
 * @version 10.0.0
 */
?>
<div class="bp-member-activity-preview">

	<?php if ( bp_activity_has_generated_content_part( 'user_cover_image' ) ) : ?>
		<div class="bp-member-preview-cover">
			<a href="<?php bp_activity_generated_content_part( 'user_url' ); ?>">
				<img src="<?php bp_activity_generated_content_part( 'user_cover_image' ); ?>" alt=""/>
			</a>
		</div>
	<?php endif; ?>

	<div class="bp-member-short-description">
		<?php if ( bp_activity_has_generated_content_part( 'user_profile_photo' ) ) : ?>
			<div class="bp-member-avatar-content <?php echo bp_activity_has_generated_content_part( 'user_cover_image' ) ? 'has-cover-image' : ''; ?>">
				<a href="<?php bp_activity_generated_content_part( 'user_url' ); ?>">
					<img src="<?php bp_activity_generated_content_part( 'user_profile_photo' ); ?>" class="profile-photo avatar" alt=""/>
				</a>
			</div>
		<?php endif; ?>

		<p class="bp-member-short-description-title">
			<a href="<?php bp_activity_generated_content_part( 'user_url' ); ?>"><?php bp_activity_generated_content_part( 'user_display_name' ); ?></a>
		</p>

		<p class="bp-member-nickname">
			<a href="<?php is_user_logged_in() ? bp_activity_generated_content_part( 'user_mention_url' ) : bp_activity_generated_content_part( 'user_url' ); ?>">@<?php bp_activity_generated_content_part( 'user_mention_name' ); ?></a>
		</p>

		<div class="bp-profile-button">
			<a href="<?php bp_activity_generated_content_part( 'user_url' ); ?>" class="button large primary button-primary" role="button"><?php esc_html_e( 'View Profile', 'buddypress'); ?></a>
		</div>
	</div>
</div>
