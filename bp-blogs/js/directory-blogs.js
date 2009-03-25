jQuery(document).ready( function() {
	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('#ajax-loader-blogs').toggle();

			jQuery("div#blogs-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');
			jQuery("input#blogs_search").val('');

			var letter = jQuery(this).attr('id')
			letter = letter.split('-');

			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				'letter': letter[1],
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("form#search-blogs-form").submit( function() { 
			jQuery('#ajax-loader-blogs').toggle();

			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				's': jQuery("input#blogs_search").val(),
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("div#blog-dir-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader-blogs').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('page=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
						
			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'page': page[1],
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
});