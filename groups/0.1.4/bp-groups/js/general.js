jQuery(document).ready( function() {
	jQuery("form#group-search-form").submit(
		function() {
			return false;
		}
	);
	
	jQuery("div#invite-list input").click(
		function() {
			jQuery('#ajax-loader').toggle();

			var friend_id = jQuery(this).val();

			if ( jQuery(this).attr('checked') == true ) {
				var friend_action = 'invite';
			} else {
				var friend_action = 'uninvite';
			}
						
			jQuery.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': friend_action,
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'friend_id': friend_id,
				'group_id': jQuery("input#group_id").val()
			},
			function(response)
			{	
				if ( jQuery("#message") )
					jQuery("#message").hide();
				
				jQuery('#ajax-loader').toggle();
				response = response.substr(0, response.length-1);
			
				if ( friend_action == 'invite' ) {
					jQuery('#friend-list').append(response);	
				} else if ( friend_action == 'uninvite' ) {
					jQuery('#friend-list li#uid-' + friend_id).remove();
				}
			});
		}
	);
	
	jQuery("#friend-list li a.remove").livequery('click',
		function() {
			jQuery('#ajax-loader').toggle();
			
			var friend_id = jQuery(this).attr('id');
			friend_id = friend_id.split('-');
			friend_id = friend_id[1];
			
			jQuery.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': 'uninvite',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'friend_id': friend_id,
				'group_id': jQuery("input#group_id").val()
			},
			function(response)
			{	
				jQuery('#ajax-loader').toggle();
				jQuery('#friend-list li#uid-' + friend_id).remove();
				jQuery('#invite-list input#f-' + friend_id).attr('checked', false);
			});
			
			return false;
		}
	);
	
	jQuery("div#pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'group_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'fpage': fpage[1],
				'num': 5,

				'group-search-box': jQuery("#group-search-box").val()
			},
			function(response)
			{	
				render_group_search_response(response);
			});
			
			return false;
		}
	);
	
	jQuery("div#groupfinder-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'group_finder_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'fpage': fpage[1],
				'num': 5,

				'groupfinder-search-box': jQuery("#groupfinder-search-box").val()
			},
			function(response)
			{	
				render_group_finder_search_response(response);
			});
			
			return false;
		}
	);

	jQuery("input#group-search-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('#ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'group_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce").val(),

					'group-search-box': jQuery("#group-search-box").val()
				},
				function(response)
				{
					render_group_search_response(response);
				});

				return false;
			}
		}
	);
	
	jQuery("input#groupfinder-search-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('#ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'group_finder_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce").val(),

					'groupfinder-search-box': jQuery("#groupfinder-search-box").val()
				},
				function(response)
				{
					render_group_finder_search_response(response);
				});

				return false;
			}
		}
	);
});

function render_group_search_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery("ul#group-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("ul#group-list").html(response[1]);
				jQuery("ul#group-list").fadeIn(200);
			}
		);
		
		jQuery("div#pag").fadeOut(200, 
			function() {
				jQuery("div#pag").html(response[2]);
				jQuery("div#pag").fadeIn(200);
			}
		);
		
	} else {
		jQuery("ul#group-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("div#pag").fadeOut(200);
				var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
				jQuery("ul#group-list").html(message);
				jQuery("ul#group-list").fadeIn(200);
			}
		);
	}
			
	return false;
}

function render_group_finder_search_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');
	
	console.log(response);

	jQuery('#finder-message').before('<ul id="friend-list"></ul>');
	
	if ( jQuery('#finder-message') ) {
		jQuery('#finder-message').fadeOut(200);
	}
	
	if ( response[0] != "-1" ) {
		jQuery("ul#friend-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("ul#friend-list").html(response[1]);
				jQuery("ul#friend-list").fadeIn(200);
			}
		);

		jQuery("div#groupfinder-pag").fadeOut(200, 
			function() {
				jQuery("div#groupfinder-pag").html(response[2]);
				jQuery("div#groupfinder-pag").fadeIn(200);
			}
		);

	} else {					
		jQuery("ul#friend-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("div#groupfinder-pag").fadeOut(200);
				var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
				jQuery("ul#friend-list").html(message);
				jQuery("ul#friend-list").fadeIn(200);
			}
		);
	}
	
	return false;
}
