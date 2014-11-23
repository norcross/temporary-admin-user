//*****************************************************************************************************
// handle message displays
//*****************************************************************************************************
function tempAdminMessageDisplay( msgTxt, msgType ) {

	// remove any existing
	jQuery( 'div.tempadmin-settings-wrap' ).find( 'div.tempadmin-message' ).remove();

	// display the message
	jQuery( 'div#wpbody h2:first' ).after( '<div id="message" class="' + msgType + ' below-h2 tempadmin-message tempadmin-message-hidden"><p>' + msgTxt + '</p></div>');

	// delay a bit then hide it
	jQuery( 'div.tempadmin-message' ).slideDown( 'slow' ).delay( 3000 ).slideUp( 'slow' ).removeClass( 'tempadmin-message-hidden' );
}

//*****************************************************************************************************
// start the engine
//*****************************************************************************************************
jQuery(document).ready( function($) {

//*****************************************************************************************************
// process the new user creation
//*****************************************************************************************************
	$( 'div.tempadmin-new-user-box' ).on( 'click', 'input#tempadmin-submit', function ( event ) {

		// stop our default event
		event.preventDefault();

		// fetch the passed variables
		var tempAdminNonce  = tempAdminData.makeNonce;
		var tempAdminEmail  = $( 'div.tempadmin-new-user-email').find( 'input#tempadmin-data-email' ).val();
		var tempAdminTime   = $( 'div.tempadmin-new-user-time').find( 'select#tempadmin-data-time option:selected' ).val();

		// fail silent witout a nonce
		if ( tempAdminNonce === '' ) {
			return false;
		}

		// return an error message for missing email
		if ( tempAdminEmail === '' ) {
			tempAdminMessageDisplay( tempAdminData.noEmail, 'error' );
			return;
		}

		// send the ajax call
		jQuery.ajax({
			url:		tempAdminData.ajaxUrl,
			type:		'POST',
			async:		true,
			dataType:	'json',
			data:		{
				action:     'create_user_js',
				nonce:      tempAdminNonce,
				email:      tempAdminEmail,
				time:       tempAdminTime
			},
			xhrFields:	{
				withCredentials: true
			},
			success: function( obj ) {
				if ( obj.message !== '' ) {
					tempAdminMessageDisplay( obj.message, 'updated' );
				}
				// display the message
				return;
			},
			error: function( obj ) {
				if ( obj.message !== '' ) {
					tempAdminMessageDisplay( obj.message, 'error' );
				}
				return false;
			}
		});

	});


//*****************************************************************************************************
// we are done here. go home
//*****************************************************************************************************
});