<?php
/**
 * Activity Post form JS Templates
 *
 * @version 3.1.0
 */
?>

<script type="text/html" id="tmpl-activity-post-form-feedback">
	<span class="bp-icon" aria-hidden="true"></span><p>{{{data.message}}}</p>
</script>

<script type="text/html" id="tmpl-activity-post-form-avatar">
	<# if ( data.display_avatar ) { #>
		<a href="{{data.user_domain}}">
			<img src="{{data.avatar_url}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" />
		</a>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-post-form-options">
	<?php bp_nouveau_activity_hook( '', 'post_form_options' ); ?>
</script>

<script type="text/html" id="tmpl-activity-post-form-buttons">
	<button type="button" class="button dashicons {{data.icon}}" data-button="{{data.id}}"><span class="bp-screen-reader-text">{{data.caption}}</span></button>
</script>

<script type="text/html" id="tmpl-activity-target-item">
	<# if ( data.selected ) { #>
		<input type="hidden" value="{{data.id}}">
	<# } #>

	<# if ( data.avatar_url ) { #>
		<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" alt="" />
	<# } #>

	<span class="bp-item-name">{{data.name}}</span>

	<# if ( data.selected ) { #>
		<button type="button" class="bp-remove-item dashicons dashicons-no" data-item_id="{{data.id}}">
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Remove item', 'button', 'buddypress' ); ?></span>
		</button>
	<# } #>
</script>
