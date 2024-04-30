<?php
/**
 * Friends Widget Block template.
 *
 * @since 9.0.0
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 9.0.0
 */
?>
<script type="html/template" id="tmpl-bp-friends-item">
	<li class="vcard">
		<div class="item-avatar">
			<a href="{{{data.link}}}" class="bp-tooltip" data-bp-tooltip="{{data.name}}">
				<img loading="lazy" src="{{{data.avatar_urls.thumb}}}" class="avatar user-{{data.id}}-avatar avatar-50 photo" width="50" height="50" alt="{{data.avatar_alt}}">
			</a>
		</div>

		<div class="item">
			<div class="item-title fn"><a href="{{{data.link}}}">{{data.name}}</a></div>
			<div class="item-meta">
				<span class="activity">{{data.extra}}</span>
			</div>
		</div>
	</li>
</script>
