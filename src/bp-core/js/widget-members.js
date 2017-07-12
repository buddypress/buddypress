jQuery(document).ready( function() {
	member_widget_click_handler();

	// WP 4.5 - Customizer selective refresh support.
	if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
		wp.customize.selectiveRefresh.bind( 'partial-content-rendered', function() {
			member_widget_click_handler();
		} );
	}
});

function member_widget_click_handler() {
	jQuery('.widget div#members-list-options a').on('click',
		function() {
			var link = this;
			jQuery(link).addClass('loading');

			jQuery('.widget div#members-list-options a').removeClass('selected');
			jQuery(this).addClass('selected');

			jQuery.post( ajaxurl, {
				action: 'widget_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery('input#_wpnonce-members').val(),
				'max-members': jQuery('input#members_widget_max').val(),
				'filter': jQuery(this).attr('id')
			},
			function(response)
			{
				jQuery(link).removeClass('loading');
				member_widget_response(response);
			});

			return false;
		}
	);
}

function member_widget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] !== '-1' ) {
		jQuery('.widget ul#members-list').fadeOut(200,
			function() {
				jQuery('.widget ul#members-list').html(response[1]);
				jQuery('.widget ul#members-list').fadeIn(200);
			}
		);

	} else {
		jQuery('.widget ul#members-list').fadeOut(200,
			function() {
				var message = '<p>' + response[1] + '</p>';
				jQuery('.widget ul#members-list').html(message);
				jQuery('.widget ul#members-list').fadeIn(200);
			}
		);
	}
}
