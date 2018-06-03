<?php
/**
 * BP Nouveau Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>
<div class="subnav-filters filters user-subnav bp-messages-filters" id="subsubnav"></div>

<div class="bp-messages-feedback"></div>
<div class="bp-messages-content"></div>

<script type="text/html" id="tmpl-bp-messages-feedback">
	<div class="bp-feedback {{data.type}}">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>{{{data.message}}}</p>
	</div>
</script>

<?php
/**
 * This view is used to inject hooks buffer
 */
?>
<script type="text/html" id="tmpl-bp-messages-hook">
	{{{data.extraContent}}}
</script>

<script type="text/html" id="tmpl-bp-messages-form">
	<?php bp_nouveau_messages_hook( 'before', 'compose_content' ); ?>

	<label for="send-to-input"><?php esc_html_e( 'Send @Username', 'buddypress' ); ?></label>
	<input type="text" name="send_to" class="send-to-input" id="send-to-input" />

	<label for="subject"><?php _e( 'Subject', 'buddypress' ); ?></label>
	<input type="text" name="subject" id="subject"/>

	<div id="bp-message-content"></div>

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>

	<div class="submit">
		<input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php echo esc_attr_x( 'Send', 'button', 'buddypress' ); ?>"/>
		<input type="button" id="bp-messages-reset" class="text-button small bp-secondary-action" value="<?php echo esc_attr_x( 'Reset', 'form reset button', 'buddypress' ); ?>"/>
	</div>
</script>

<script type="text/html" id="tmpl-bp-messages-editor">
	<?php
	// Add a temporary filter on editor buttons
	add_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );

	wp_editor(
		'',
		'message_content',
		array(
			'textarea_name' => 'message_content',
			'teeny'         => false,
			'media_buttons' => false,
			'dfw'           => false,
			'tinymce'       => true,
			'quicktags'     => false,
			'tabindex'      => '3',
			'textarea_rows' => 5,
		)
	);

	// Remove the temporary filter on editor buttons
	remove_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );
	?>
</script>

<script type="text/html" id="tmpl-bp-messages-paginate">
	<# if ( 1 !== data.page ) { #>
		<button id="bp-messages-prev-page"class="button messages-button">
			<span class="dashicons dashicons-arrow-left"></span>
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Previous page', 'link', 'buddypress' ); ?></span>
		</button>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
		<button id="bp-messages-next-page"class="button messages-button">
			<span class="dashicons dashicons-arrow-right"></span>
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Next page', 'link', 'buddypress' ); ?></span>
		</button>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-messages-filters">
	<li class="user-messages-search" role="search" data-bp-search="{{data.box}}">
		<div class="bp-search messages-search">
			<form action="" method="get" id="user_messages_search_form" class="bp-messages-search-form" data-bp-search="messages">
				<label for="user_messages_search" class="bp-screen-reader-text">
					<?php _e( 'Search Messages', 'buddypress' ); ?>
				</label>
				<input type="search" id="user_messages_search" placeholder="<?php echo esc_attr_x( 'Search', 'search placeholder text', 'buddypress' ); ?>"/>
				<button type="submit" id="user_messages_search_submit">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Search', 'button', 'buddypress' ); ?></span>
				</button>
			</form>
		</div>
	</li>
	<li class="user-messages-bulk-actions"></li>
</script>

<script type="text/html" id="tmpl-bp-bulk-actions">
	<input type="checkbox" id="user_messages_select_all" value="1"/>
	<label for="user_messages_select_all"><?php esc_html_e( 'All Messages', 'buddypress' ); ?></label>
	<div class="bulk-actions-wrap bp-hide">
		<div class="bulk-actions select-wrap">
			<label for="user-messages-bulk-actions" class="bp-screen-reader-text">
				<?php esc_html_e( 'Select bulk action', 'buddypress' ); ?>
			</label>
			<select id="user-messages-bulk-actions">
				<# for ( i in data ) { #>
					<option value="{{data[i].value}}">{{data[i].label}}</option>
				<# } #>
			</select>
			<span class="select-arrow" aria-hidden="true"></span>
		</div>
		<button class="messages-button bulk-apply bp-tooltip" type="submit" data-bp-tooltip="<?php echo esc_attr_x( 'Apply', 'button', 'buddypress' ); ?>">
			<span class="dashicons dashicons-yes" aria-hidden="true"></span>
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Apply', 'button', 'buddypress' ); ?></span>
		</button>
	</div>
</script>

<script type="text/html" id="tmpl-bp-messages-thread">
	<div class="thread-cb">
		<input class="message-check" type="checkbox" name="message_ids[]" id="bp-message-thread-{{data.id}}" value="{{data.id}}">
		<label for="bp-message-thread-{{data.id}}" class="bp-screen-reader-text"><?php esc_html_e( 'Select message:', 'buddypress' ); ?> {{data.subject}}</label>
	</div>

	<# if ( ! data.recipientsCount ) { #>
		<div class="thread-from">
			<a class="user-link" href="{{data.sender_link}}">
				<img class="avatar" src="{{data.sender_avatar}}" alt="" />
				<span class="bp-screen-reader-text"><?php esc_html_e( 'From:', 'buddypress' ); ?></span>
				<span class="user-name">{{data.sender_name}}</span>
			</a>
		</div>
	<# } else {
		var recipient = _.first( data.recipients );
		#>
		<div class="thread-to">
			<a class="user-link" href="{{recipient.user_link}}">
				<img class="avatar" src="{{recipient.avatar}}" alt="" />
				<span class="bp-screen-reader-text"><?php esc_html_e( 'To:', 'buddypress' ); ?></span>
				<span class="user-name">{{recipient.user_name}}</span>
			</a>

			<# if ( data.toOthers ) { #>
				<span class="num-recipients">{{data.toOthers}}</span>
			<# } #>
		</div>
	<# } #>

	<div class="thread-content" data-thread-id="{{data.id}}">
		<div class="thread-subject">
			<span class="thread-count">({{data.count}})</span>
			<a class="subject" href="../view/{{data.id}}/">{{data.subject}}</a>
		</div>
		<p class="excerpt">{{data.excerpt}}</p>
	</div>
	<div class="thread-date">
		<time datetime="{{data.date.toISOString()}}">{{data.display_date}}</time>
	</div>
</script>

<script type="text/html" id="tmpl-bp-messages-preview">
	<# if ( undefined !== data.content ) { #>

		<h2 class="message-title preview-thread-title"><?php esc_html_e( 'Active conversation:', 'buddypress' ); ?><span class="messages-title">{{{data.subject}}}</span></h2>
		<div class="preview-content">
			<header class="preview-pane-header">

				<# if ( undefined !== data.recipients ) { #>
					<dl class="thread-participants">
						<dt><?php esc_html_e( 'Participants:', 'buddypress' ); ?></dt>
						<dd>
							<ul class="participants-list">
								<# for ( i in data.recipients ) { #>
									<li><a href="{{data.recipients[i].user_link}}" class="bp-tooltip" data-bp-tooltip="{{data.recipients[i].user_name}}"><img class="avatar mini" src="{{data.recipients[i].avatar}}" alt="{{data.recipients[i].user_name}}" /></a></li>
								<# } #>
							</ul>
						</dd>
					</dl>
				<# } #>

				<div class="actions">

					<button type="button" class="message-action-delete bp-tooltip bp-icons" data-bp-action="delete" data-bp-tooltip="<?php esc_attr_e( 'Delete conversation.', 'buddypress' ); ?>">
						<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete conversation.', 'buddypress' ); ?></span>
					</button>

					<# if ( undefined !== data.star_link ) { #>

						<# if ( false !== data.is_starred ) { #>
							<a role="button" class="message-action-unstar bp-tooltip bp-icons" href="{{data.star_link}}" data-bp-action="unstar" aria-pressed="true" data-bp-tooltip="<?php esc_attr_e( 'Unstar Conversation', 'buddypress' ); ?>">
								<span class="bp-screen-reader-text"><?php esc_html_e( 'Unstar Conversation', 'buddypress' ); ?></span>
							</a>
						<# } else { #>
							<a role="button" class="message-action-star bp-tooltip bp-icons" href="{{data.star_link}}" data-bp-action="star" aria-pressed="false" data-bp-tooltip="<?php esc_attr_e( 'Star Conversation', 'buddypress' ); ?>">
								<span class="bp-screen-reader-text"><?php esc_html_e( 'Star Conversation', 'buddypress' ); ?></span>
							</a>
						<# } #>

					<# } #>

					<a href="../view/{{data.id}}/" class="message-action-view bp-tooltip bp-icons" data-bp-action="view" data-bp-tooltip="<?php esc_attr_e( 'View full conversation and reply.', 'buddypress' ); ?>">
						<span class="bp-screen-reader-text"><?php esc_html_e( 'View full conversation and reply.', 'buddypress' ); ?></span>
					</a>

					<# if ( data.threadOptions ) { #>
						<span class="bp-messages-hook thread-options">
							{{{data.threadOptions}}}
						</span>
					<# } #>
				</div>
			</header>

			<div class='preview-message'>
				{{{data.content}}}
			</div>

			<# if ( data.inboxListItem ) { #>
				<table class="bp-messages-hook inbox-list-item">
					<tbody>
						<tr>{{{data.inboxListItem}}}</tr>
					</tbody>
				</table>
			<# } #>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-messages-single-header">
	<h2 id="message-subject" class="message-title single-thread-title">{{{data.subject}}}</h2>
	<header class="single-message-thread-header">
		<# if ( undefined !== data.recipients ) { #>
			<dl class="thread-participants">
				<dt><?php esc_html_e( 'Participants:', 'buddypress' ); ?></dt>
				<dd>
					<ul class="participants-list">
						<# for ( i in data.recipients ) { #>
							<li><a href="{{data.recipients[i].user_link}}" class="bp-tooltip" data-bp-tooltip="{{data.recipients[i].user_name}}"><img class="avatar mini" src="{{data.recipients[i].avatar}}" alt="{{data.recipients[i].user_name}}" /></a></li>
						<# } #>
					</ul>
				</dd>
			</dl>
		<# } #>

		<div class="actions">
			<button type="button" class="message-action-delete bp-tooltip bp-icons" data-bp-action="delete" data-bp-tooltip="<?php esc_attr_e( 'Delete conversation.', 'buddypress' ); ?>">
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete conversation.', 'buddypress' ); ?></span>
			</button>
		</div>
	</header>
</script>

<script type="text/html" id="tmpl-bp-messages-single-list">
	<div class="message-metadata">
		<# if ( data.beforeMeta ) { #>
			<div class="bp-messages-hook before-message-meta">{{{data.beforeMeta}}}</div>
		<# } #>

		<a href="{{data.sender_link}}" class="user-link">
			<img class="avatar" src="{{data.sender_avatar}}" alt="" />
			<strong>{{data.sender_name}}</strong>
		</a>

		<time datetime="{{data.date.toISOString()}}" class="activity">{{data.display_date}}</time>

		<div class="actions">
			<# if ( undefined !== data.star_link ) { #>

				<button type="button" class="message-action-unstar bp-tooltip bp-icons <# if ( false === data.is_starred ) { #>bp-hide<# } #>" data-bp-star-link="{{data.star_link}}" data-bp-action="unstar" data-bp-tooltip="<?php esc_attr_e( 'Unstar Message', 'buddypress' ); ?>">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Unstar Message', 'buddypress' ); ?></span>
				</button>

				<button type="button" class="message-action-star bp-tooltip bp-icons <# if ( false !== data.is_starred ) { #>bp-hide<# } #>" data-bp-star-link="{{data.star_link}}" data-bp-action="star" data-bp-tooltip="<?php esc_attr_e( 'Star Message', 'buddypress' ); ?>">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Star Message', 'buddypress' ); ?></span>
				</button>

			<# } #>
		</div>

		<# if ( data.afterMeta ) { #>
			<div class="bp-messages-hook after-message-meta">{{{data.afterMeta}}}</div>
		<# } #>
	</div>

	<# if ( data.beforeContent ) { #>
		<div class="bp-messages-hook before-message-content">{{{data.beforeContent}}}</div>
	<# } #>

	<div class="message-content">{{{data.content}}}</div>

	<# if ( data.afterContent ) { #>
		<div class="bp-messages-hook after-message-content">{{{data.afterContent}}}</div>
	<# } #>

</script>

<script type="text/html" id="tmpl-bp-messages-single">
	<?php bp_nouveau_messages_hook( 'before', 'thread_content' ); ?>

	<div id="bp-message-thread-header" class="message-thread-header"></div>

	<?php bp_nouveau_messages_hook( 'before', 'thread_list' ); ?>

	<ul id="bp-message-thread-list"></ul>

	<?php bp_nouveau_messages_hook( 'after', 'thread_list' ); ?>

	<?php bp_nouveau_messages_hook( 'before', 'thread_reply' ); ?>

	<form id="send-reply" class="standard-form send-reply">
		<div class="message-box">
			<div class="message-metadata">

				<?php bp_nouveau_messages_hook( 'before', 'reply_meta' ); ?>

				<div class="avatar-box">
					<?php bp_loggedin_user_avatar( 'type=thumb&height=30&width=30' ); ?>

					<strong><?php esc_html_e( 'Send a Reply', 'buddypress' ); ?></strong>
				</div>

				<?php bp_nouveau_messages_hook( 'after', 'reply_meta' ); ?>

			</div><!-- .message-metadata -->

			<div class="message-content">

				<?php bp_nouveau_messages_hook( 'before', 'reply_box' ); ?>

				<label for="message_content" class="bp-screen-reader-text"><?php _e( 'Reply to Message', 'buddypress' ); ?></label>
				<div id="bp-message-content"></div>

				<?php bp_nouveau_messages_hook( 'after', 'reply_box' ); ?>

				<div class="submit">
					<input type="submit" name="send" value="<?php echo esc_attr_x( 'Send Reply', 'button', 'buddypress' ); ?>" id="send_reply_button"/>
				</div>

			</div><!-- .message-content -->

		</div><!-- .message-box -->
	</form>

	<?php bp_nouveau_messages_hook( 'after', 'thread_reply' ); ?>

	<?php bp_nouveau_messages_hook( 'after', 'thread_content' ); ?>
</script>
