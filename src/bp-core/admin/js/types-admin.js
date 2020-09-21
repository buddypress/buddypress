( function() {
	var bpTypesCustomizeForm = function() {
		if ( document.querySelector( '#addtag input[name="post_type"]' ) ) {
			document.querySelector( '#addtag input[name="post_type"]' ).remove();
		}

		if ( document.querySelectorAll( '.form-field' ) ) {
			document.querySelectorAll( '.form-field' ).forEach( function( element ) {
				if ( -1 === element.classList.value.indexOf( 'bp-types-form' ) ) {
					element.remove();
				}
			} );
		}

		if ( document.querySelector( '#bp_type_has_directory' ) ) {
			if ( true === document.querySelector( '#bp_type_has_directory' ).checked ) {
				document.querySelector( '.term-bp_type_directory_slug-wrap' ).classList.add( 'bp-set-directory-slug' );
			}

			document.querySelector( '#bp_type_has_directory' ).addEventListener( 'change', function( event ) {
				if ( true === event.target.checked ) {
					document.querySelector( '.term-bp_type_directory_slug-wrap' ).classList.add( 'bp-set-directory-slug' );
					document.querySelector( '#bp_type_directory_slug' ).removeAttribute( 'disabled' );
				} else {
					document.querySelector( '.term-bp_type_directory_slug-wrap' ).classList.remove( 'bp-set-directory-slug' );
					document.querySelector( '#bp_type_directory_slug' ).setAttribute( 'disabled', 'disabled' );
				}
			} );
		}

		if ( document.querySelector( '#delete-link' ) ) {
			document.querySelector( '#delete-link' ).remove();
		}
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', bpTypesCustomizeForm );
	} else {
		bpTypesCustomizeForm;
	}
} )();
