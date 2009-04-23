jQuery(document).ready( function() {
	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('#ajax-loader-members').toggle();

			jQuery("div#members-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');
			jQuery("input#members_search").val('');
			
			var letter = jQuery(this).attr('id')
			letter = letter.split('-');
			
			var page = ( jQuery('input#members-page-num').val() ) ? jQuery('input#members-page-num').val() : 1;

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				'letter': letter[1],
				'page': page
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-members').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("form#search-members-form").submit( function() { 
			jQuery('#ajax-loader-members').toggle();

			var page = ( jQuery('input#members-page-num').val() ) ? jQuery('input#members-page-num').val() : 1;

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				's': jQuery("input#members_search").val(),
				'page': page
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-members').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("div#member-dir-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader-members').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('upage=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
			
			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'upage': page[1],
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-members').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
});
