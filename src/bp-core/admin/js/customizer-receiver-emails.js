/**
 * Customizer implementation for Email.
 *
 * If you're looking to add JS for every instance of a control, don't add it here.
 * The file only implements the Customizer controls for Emails.
 *
 * @since 2.5.0
 */

(function( $ ) {
	wp.customize( 'bp_email_options[email_bg]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.email_bg' ).attr( 'bgcolor', newval );
				$( 'hr' ).attr( 'color', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[header_bg]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.header_bg' ).attr( 'bgcolor', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[header_text_size]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.header_text_size' ).css( 'font-size', newval + 'px' );
			}
		});
	});

	wp.customize( 'bp_email_options[header_text_color]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.header_text_color' ).css( 'color', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[highlight_color]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.header_bg' ).css( 'border-top-color', newval );
				$( 'a' ).css( 'color', newval );
				$( 'hr' ).attr( 'color', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[body_bg]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.body_bg' ).attr( 'bgcolor', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[body_text_size]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				// 1.618 = golden mean.
				$( '.body_text_size' )
					.css( 'font-size', newval + 'px' )
					.css( 'line-height', Math.floor( newval * 1.618 ) + 'px' );

				// 1.35 = default body_text_size multipler. Gives default heading of 20px.
				$( '.welcome' ).css( 'font-size', Math.floor( newval * 1.35 ) + 'px' );
			}
		});
	});

	wp.customize( 'bp_email_options[body_text_color]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.body_text_color' ).css( 'color', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[footer_bg]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.footer_bg' ).attr( 'bgcolor', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[footer_text_size]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.footer_text_size' )
					.css( 'font-size', newval + 'px' )
					.css( 'line-height', Math.floor( newval * 1.618 ) + 'px' );
			}
		});
	});

	wp.customize( 'bp_email_options[footer_text_color]', function( value ) {
		value.bind(function( newval ) {
			if ( newval.length ) {
				$( '.footer_text_color' ).css( 'color', newval );
			}
		});
	});

	wp.customize( 'bp_email_options[footer_text]', function( value ) {
		value.bind(function( newval ) {
			$( '.footer_text' ).html( newval );
		});
	});
})( jQuery );
