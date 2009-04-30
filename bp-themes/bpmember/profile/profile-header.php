<div id="profile-name">
	<h1 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>
	
	<?php if( function_exists('bp_user_status') ) : ?>
		<p class="status"><?php bp_user_status() ?></p>
	<?php endif; ?>
</div>