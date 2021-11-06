( function() {
	var bpBackCompatAdminTabs = function() {
		var wrap = document.querySelector( '.nav-tab-wrapper' ).closest( '.wrap' );

		if ( wrap ) {
			// 1. Add the tabbed class to the body tag.
			document.body.classList.add( 'bp-is-tabbed-screen' );

			// 2. Make the wrapping div the BuddyPress body.
			wrap.classList.add( 'buddypress-body' );
			wrap.classList.remove( 'wrap' );

			// 3. Create the BuddyPress header.
			var buddypressHeader = document.createElement( 'div' );
			buddypressHeader.classList.add( 'buddypress-header' );

			var headings = wrap.querySelectorAll( 'h1' );
			var buddypressTitleSection = document.createElement( 'div' );
			buddypressTitleSection.classList.add( 'buddypress-title-section' );

			// 4. Move the document title in it.
			if ( headings && headings[0] ) {
				var buddyPressLogo = document.createElement( 'span' );
				buddyPressLogo.classList.add( 'bp-badge' );
				headings[0].innerHTML = '&nbsp;' + headings[0].innerHTML;
				headings[0].prepend( buddyPressLogo );
				buddypressTitleSection.appendChild( headings[0] );
			}

			buddypressHeader.appendChild( buddypressTitleSection );

			// 5. Move the tabs in it.
			var headerNavTabs = document.createElement( 'nav' );
			headerNavTabs.classList.add( 'buddypress-tabs-wrapper' );

			var bpAdminTabs = document.querySelectorAll( '.buddypress-nav-tab' );
			var columns = [];
			bpAdminTabs.forEach( function( tabItem ) {
				headerNavTabs.appendChild( tabItem );
				columns.push( '1fr' );
			} );

			// 6. Add the header's nav tabs into the header.
			buddypressHeader.appendChild( headerNavTabs );

			// 7. Edit the number of grid columns.
			if ( columns.length > 0 ) {
				headerNavTabs.setAttribute( 'style', '-ms-grid-columns: ' + columns.join( ' ' ) + '; grid-template-columns: ' + columns.join( ' ' ) + ';');
			}

			// 8. Create the header's separator.
			var headerSeparator = document.createElement( 'hr' );
			headerSeparator.classList.add( 'wp-header-end' );

			// 9. Insert the BuddyPress header into the document.
			document.querySelector('#wpbody-content').insertBefore( buddypressHeader, wrap );
			document.querySelector('#wpbody-content').insertBefore( headerSeparator, wrap );
		}
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', bpBackCompatAdminTabs );
	} else {
		bpBackCompatAdminTabs;
	}
} )();
