/* jshint undef: false */
/* @since 1.7.0 */
/* @version 10.0.0 */
/* Password Verify */
( function( $ ){
	function check_pass_strength( event ) {
		var pass1 = $( '.password-entry' ).val(),
		    pass2 = $( '.password-entry-confirm' ).val(),
		    currentForm = $( '.password-entry' ).closest( 'form' ),
		    strength, requiredStrength;

		if ( 'undefined' !== typeof window.bpPasswordVerify && window.bpPasswordVerify.requiredPassStrength ) {
			requiredStrength = parseInt( window.bpPasswordVerify.requiredPassStrength, 10 );
		}

		// Reset classes and result text
		$( '#pass-strength-result' ).removeClass( 'short bad good strong' );
		if ( ! pass1 ) {
			$( '#pass-strength-result' ).html( pwsL10n.empty );
			return;
		}

		// wp.passwordStrength.userInputBlacklist() has been deprecated in WP 5.5.0.
		if ( 'function' === typeof wp.passwordStrength.userInputDisallowedList ) {
			strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputDisallowedList(), pass2 );
		} else {
			strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass2 );
		}

		switch ( strength ) {
			case 2:
				$( '#pass-strength-result' ).addClass( 'bad' ).html( pwsL10n.bad );
				break;
			case 3:
				$( '#pass-strength-result' ).addClass( 'good' ).html( pwsL10n.good );
				break;
			case 4:
				$( '#pass-strength-result' ).addClass( 'strong' ).html( pwsL10n.strong );
				break;
			case 5:
				$( '#pass-strength-result' ).addClass( 'short' ).html( pwsL10n.mismatch );
				break;
			default:
				$( '#pass-strength-result' ).addClass( 'short' ).html( pwsL10n['short'] );
				break;
		}

		if ( requiredStrength && 4 >= requiredStrength ) {
			var passwordWarningContainer = $( currentForm ).find( '#password-warning' );

				if ( strength < requiredStrength ) {
					if ( ! $( passwordWarningContainer ).length ) {
						$( event.currentTarget ).before(
							$( '<p></p>' ).prop( 'id', 'password-warning' )
										  .addClass( 'description' )
						);
					}

					$( passwordWarningContainer ).html( bpPasswordVerify.tooWeakPasswordWarning );
				} else if ( $( passwordWarningContainer ).length ) {
					$( passwordWarningContainer ).remove();
				}

			if ( ! $( currentForm ).find( '#password-strength-score' ).length ) {
				$( currentForm ).prepend(
					$('<input></input>').prop( {
						id: 'password-strength-score',
						type: 'hidden',
						'name': '_password_strength_score'
					} )
				);
			}

			$( '#password-strength-score' ).val( strength );
		}
	}

	// Bind check_pass_strength to keyup events in the password fields
	$( function() {
		$( '.password-entry' ).val( '' ).on( 'keyup', check_pass_strength );
		$( '.password-entry-confirm' ).val( '' ).on( 'keyup', check_pass_strength );
	});

} )( jQuery );
