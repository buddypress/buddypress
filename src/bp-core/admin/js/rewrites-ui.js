( function() {
	var bpRewritesUI = function() {
		var accordions = document.querySelectorAll( '.health-check-accordion' );

		accordions.forEach( function( accordion ) {
			accordion.addEventListener( 'click', function( e ) {
				if ( e.target && e.target.matches( 'button.health-check-accordion-trigger' ) ) {
					e.preventDefault();

					var isExpanded = ( 'true' === e.target.getAttribute( 'aria-expanded' ) ),
					    panel = document.querySelector( '#' + e.target.getAttribute( 'aria-controls' ) );

					if ( isExpanded ) {
						e.target.setAttribute( 'aria-expanded', 'false' );
						panel.setAttribute( 'hidden', true );
					} else {
						e.target.setAttribute( 'aria-expanded', 'true' );
						panel.removeAttribute( 'hidden' );
					}
				}
			} );
		} );
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', bpRewritesUI );
	} else {
		bpRewritesUI();
	}
} )();
