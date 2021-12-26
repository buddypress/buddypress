<?php
/**
 * BuddyPress Avatars recycle template.
 *
 * This template is used to create the recycle Backbone views.
 *
 * @since 10.0.0
 * @version 10.0.0
 */
?>
<script id="tmpl-bp-avatar-recycle" type="text/html">
	<div class="avatars-history">
		<div class="avatar-history-list">
			<table class="avatar-history-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Profile photo', 'buddypress' ); ?></th>
						<th><?php esc_html_e( 'Uploaded on', 'buddypress' ); ?></th>
					</tr>
				</thead>
				<tbody id="bp-avatars-history-list"></tbody>
			</table>
		</div>
		<div class="avatar-history-actions">
			<p class="warning"><?php esc_html_e( 'Click on a profile photo from your history to recycle it as your current profile photo or delete it.', 'buddypress' ); ?></p>
			<button class="avatar-history-action recycle disabled">
				<?php esc_html_e( 'Recycle', 'buddypress' ); ?>
			</button>
			<button class="avatar-history-action delete disabled">
				<?php esc_html_e( 'Delete', 'buddypress' ); ?>
			</button>
		</div>
	</div>
</script>

<script id="tmpl-bp-avatar-recycle-history-item" type="text/html">
	<td>
		<label for="avatar_{{data.id}}">
			<input type="radio" name="avatar_id" value="{{data.id}}" id="avatar_{{data.id}}" class="<?php echo esc_attr( is_admin() && ! wp_doing_ajax() ? 'screen-reader-text' : 'bp-screen-reader-text' ); ?>"/>
			<img src="{{{data.url}}}" id="{{data.id}}" class="avatar" width="<?php echo esc_attr( bp_core_avatar_thumb_width() ); ?>" height="<?php echo esc_attr( bp_core_avatar_thumb_height() ); ?>"/>
		</label>
	</td>
	<td>
		<span class="time">{{data.date}}</span>
	</td>
</script>
