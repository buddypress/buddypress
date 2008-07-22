
jQuery(document).ready( function() {
	jQuery("form#friend-search-form").submit(
		function() {
			return false;
		}
	);
	
	jQuery("div#pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'friends_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'initiator_id': jQuery("input#initiator").val(),
				'fpage': fpage[1],
				'num': 5,

				'friend-search-box': jQuery("#friend-search-box").val()
			},
			function(response)
			{	
				
				render_friend_search_response(response);
			});
			
			return false;
		}
	);
	
	jQuery("div#finder-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'finder_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'fpage': fpage[1],
				'num': 5,

				'finder-search-box': jQuery("#finder-search-box").val()
			},
			function(response)
			{	
				
				render_finder_search_response(response);
			});
			
			return false;
		}
	);
	jQuery("input#friend-search-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('#ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'friends_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce").val(),

					'friend-search-box': jQuery("#friend-search-box").val()
				},
				function(response)
				{
					
					render_friend_search_response(response);
				});

				return false;
			}
		}
	);
	
	jQuery("input#finder-search-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('#ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'finder_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce").val(),

					'finder-search-box': jQuery("#finder-search-box").val()
				},
				function(response)
				{
					render_finder_search_response(response);
				});

				return false;
			}
		}
	);
	
});



function render_friend_search_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery("ul#friend-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("ul#friend-list").html(response[1]);
				jQuery("ul#friend-list").fadeIn(200);
			}
		);
		
		jQuery("div#pag").fadeOut(200, 
			function() {
				jQuery("div#pag").html(response[2]);
				jQuery("div#pag").fadeIn(200);
			}
		);
		
	} else {
		jQuery("ul#friend-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("div#pag").fadeOut(200);
				var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
				jQuery("ul#friend-list").html(message);
				jQuery("ul#friend-list").fadeIn(200);
			}
		);
	}
			
	return false;
}

function render_finder_search_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

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

		jQuery("div#finder-pag").fadeOut(200, 
			function() {
				jQuery("div#finder-pag").html(response[2]);
				jQuery("div#finder-pag").fadeIn(200);
			}
		);

	} else {					
		jQuery("ul#friend-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader').toggle();
				jQuery("div#finder-pag").fadeOut(200);
				var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
				jQuery("ul#friend-list").html(message);
				jQuery("ul#friend-list").fadeIn(200);
			}
		);
	}
	
	return false;
}