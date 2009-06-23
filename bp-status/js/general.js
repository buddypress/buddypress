jQuery(document).ready( function() {
	jQuery("div#user-status p, a#status-new-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_show_form',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").slideUp(400, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").slideDown(400, function() {
							jQuery("#status-update-input").focus();
						});
					}
				);
				
				jQuery(window).bind('click', function(ev) {
					if ( !jQuery(ev.target).is('div#user-status') && !jQuery(ev.target).parents('div#user-status').length ) {
						jQuery.post( ajaxurl, {
							action: 'status_show_status',
							'cookie': encodeURIComponent(document.cookie)
						},
						function(response) {				
							jQuery("div#user-status").slideUp(400, 
								function() {
									jQuery("div#user-status").html(response);
									jQuery("div#user-status").slideDown(400);
								}
							);
							
							jQuery(window).unbind('click');
						});
					}
				});
			});

			return false;
		}
	);

	jQuery("form#status-update-form").livequery('submit', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_new_status',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery('input#_wpnonce_add_status').val(),
				'status-update-input': jQuery('#status-update-input').val()
			},
			function(response) {
				if ( response == "1" ) {			
					jQuery("div#user-status").slideUp(400,
						function() {
							jQuery.post( ajaxurl, {
								action: 'status_show_status',
								'cookie': encodeURIComponent(document.cookie)
							},
							function(response) {				
								jQuery("div#user-status").html(response);
								jQuery("div#user-status").slideDown(400);
								jQuery(window).unbind('click');
							});
						} 
					);
				}
			});

			return false;
		}
	);

	jQuery("a#status-clear-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_clear_status',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").fadeOut(300, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").fadeIn(300);
					}
				);
			});

			return false;
		}
	);
});
