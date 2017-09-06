<?php
/**
 * BP Nouveau Invites main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with invites.
 *
 * @since 1.0.0
 */
?>
<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group invitations menu', 'buddypress' ); ?>"></nav>

<h2 class="bp-screen-title <?php if ( bp_is_group_create() ) { echo esc_attr( 'creation-step-name' ); } ?>">
	<?php _e( 'Invite Members', 'buddypress' ); ?>
</h2>

<div class="subnav-filters group-subnav-filters bp-invites-filters"></div>
<div class="bp-invites-feedback"></div>
<div class="members bp-invites-content"></div>

<script type="text/html" id="tmpl-bp-invites-nav">
	<a href="{{data.href}}" class="bp-invites-nav-item" data-nav="{{data.id}}">{{data.name}}</a>
</script>

<script type="text/html" id="tmpl-bp-invites-users">
	<div class="item-avatar">
		<img src="{{data.avatar}}" class="avatar" alt="">
	</div>

	<div class="item">
		<div class="list-title member-name">
			{{data.name}}
		</div>

		<# if ( undefined !== data.is_sent ) { #>
			<div class="item-meta">

				<# if ( undefined !== data.invited_by ) { #>
					<ul class="group-inviters">
						<li><?php esc_html_e( 'Invited by:', 'buddypress' ); ?></li>
						<# for ( i in data.invited_by ) { #>
							<li><a href="{{data.invited_by[i].user_link}}" class="bp-tooltip" data-bp-tooltip="{{data.invited_by[i].user_name}}"><img src="{{data.invited_by[i].avatar}}" width="30px" class="avatar mini" alt="{{data.invited_by[i].user_name}}"></a></li>
						<# } #>
					</ul>
				<# } #>

				<p class="status">
					<# if ( false === data.is_sent ) { #>
						<?php esc_html_e( 'The invite has not been sent yet.', 'buddypress' ); ?>
					<# } else { #>
						<?php esc_html_e( 'The invite has been sent.', 'buddypress' ); ?>
					<# } #>
				</p>

			</div>
		<# } #>
	</div>

	<div class="action">
		<# if ( undefined === data.is_sent || ( false === data.is_sent && true === data.can_edit ) ) { #>
			<button type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip="<?php esc_attr_e( 'Invite / Uninvite', 'buddypress' );?>">
				<span class="icons"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Invite/Uninvite', 'buddypress' );?></span>
			</button>
		<# } #>

		<# if ( undefined !== data.can_edit && true === data.can_edit ) { #>
			<button type="button" class="button invite-button group-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip="<?php esc_attr_e( 'Remove', 'buddypress' );?>">
				<span class=" icons"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Remove', 'buddypress' );?></span>
			</button>
		<# } #>
	</div>

</script>

<script type="text/html" id="tmpl-bp-invites-selection">
	<a href="#" title="{{data.name}}">
		<img src="{{data.avatar}}" class="avatar" alt="{{data.name}}" />
	</a>
</script>

<script type="text/html" id="tmpl-bp-invites-form">

	<label for="send-invites-control"><?php esc_attr_e( 'Optional: add a message to your invite.', 'buddypress' ); ?></label>
	<textarea id="send-invites-control" class="bp-faux-placeholder-label"></textarea>

	<div class="action">
		<button type="button" id="bp-invites-reset" class="button bp-secondary-action"><?php _ex( 'Cancel', 'Cancel invitation', 'buddypress' ); ?></button>
		<button type="button" id="bp-invites-send" class="button bp-primary-action"><?php _ex( 'Send', 'Send invitation', 'buddypress' ); ?></button>
	</div>
</script>

<script type="text/html" id="tmpl-bp-invites-filters">
	<div class="group-invites-search subnav-search clearfix" role="search" >
		<div class="bp-search">
			<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form" data-bp-search="{{data.scope}}">
				<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( 'Search Members', false ); ?></label>
				<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( __( 'Search', 'buddypress' ) ); ?>"/>

				<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddypress' ); ?></span>
				</button>
			</form>
			</div>
	</div>
</script>

<script type="text/html" id="tmpl-bp-invites-paginate">
	<# if ( 1 !== data.page ) { #>
		<a href="#" id="bp-invites-prev-page" class="button invite-button">
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous', 'buddypress' );?></span>
		</a>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
		<a href="#" id="bp-invites-next-page" class="button invite-button">
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Next', 'buddypress' );?></span>
		</a>
	<# } #>
</script>
