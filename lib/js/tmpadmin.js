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
// check if any box in a list is checked
//*****************************************************************************************************
function tempAdminBoxCheck( boxBlock ) {

	// first set my value to false
	var anyBoxesChk = false;

	// now loop through and check
	jQuery( 'div#' + boxBlock + ' table.users tbody input[type="checkbox"]' ).each( function() {

		// if any are true, return
		if ( jQuery( this ).is( ':checked' ) ) {
			anyBoxesChk = true;
		}

	});

    // return the result
	return anyBoxesChk;
}

//*****************************************************************************************************
// show or hide the button in the group if requested
//*****************************************************************************************************
function tempAdminBoxButton( boxBlock, buttonState ) {
    jQuery( 'div#' + boxBlock ).find( 'span.tempadmin-users-list-action' ).css( 'visibility', buttonState );
}

//*****************************************************************************************************
// determine the last class on a table item to apply alternating CSS
//*****************************************************************************************************
function tempAdminNextRowClass( boxBlock ) {

	// set a default class
	var nextRowClass    = 'alternate';

	// pull the last row from the table and check the class
    if ( jQuery( 'div#' + boxBlock ).find( 'tr.tempadmin-single-user-row:last' ).hasClass( 'alternate' ) ) {
		nextRowClass = 'standard';
    }

    // return the class
    return nextRowClass;
}

//*****************************************************************************************************
// reset the row classes after removal
//*****************************************************************************************************
function tempAdminResetRowClass( boxBlock ) {

	// first loop through and clear out both
	jQuery( 'div#' + boxBlock + ' tr.tempadmin-single-user-row' ).each( function() {
		jQuery( this ).removeClass( 'standard alternate' );
	});

	// now add the classes
	jQuery( 'div#' + boxBlock + ' tr.tempadmin-single-user-row:even' ).addClass( 'alternate' );
	jQuery( 'div#' + boxBlock + ' tr.tempadmin-single-user-row:odd' ).addClass( 'standard' );
}

//*****************************************************************************************************
// add the new user onto the active users row
//*****************************************************************************************************
function tempAdminAddNewRow( newRow ) {

	// add the new row
	jQuery( 'div#tempadmin-users-active div.tempadmin-users-list-data' ).find( 'tbody' ).append( newRow );

	// and reset the classes
	tempAdminResetRowClass( 'tempadmin-users-active' );
}

//*****************************************************************************************************
// start the engine
//*****************************************************************************************************
jQuery(document).ready( function($) {

//********************************************************************************************************************************
// quick helper to check for an existance of an element
//********************************************************************************************************************************
	$.fn.divExists = function(callback) {
		// slice some args
		var args = [].slice.call( arguments, 1 );
		// check for length
		if ( this.length ) {
			callback.call( this, args );
		}
		// return it
		return this;
	};

//*****************************************************************************************************
// set some vars
//*****************************************************************************************************
	var boxBlock;
	var boxCheck = false;

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
		})
		.done( function( obj ) {

			if( obj.success === true ) {
				// display the message
				if ( obj.message !== '' ) {
					tempAdminMessageDisplay( obj.message, 'updated' );
				}
				// clear the input field
				$( 'div.tempadmin-new-user-email').find( 'input#tempadmin-data-email' ).val( '' );
				// add our new user row
				if ( obj.newrow !== '' ) {
					tempAdminAddNewRow( obj.newrow );
				}
				// and return
				return;
			}

			if( obj.success === false ) {
				if ( obj.message !== '' ) {
					tempAdminMessageDisplay( obj.message, 'error' );
				}
				// clear the email field if the error was due to a used email address
				if ( obj.errcode !== '' && obj.errcode == 'USED_EMAIL' ) {
					$( 'div.tempadmin-new-user-email').find( 'input#tempadmin-data-email' ).val( '' );
				}
				return false;
			}
		});

	});

//*****************************************************************************************************
// display the hidden buttons when users are selected from the list
//*****************************************************************************************************
	$( 'div.tempadmin-users-list-box' ).each( function() {

		// find a selected value to start
		boxBlock    = $( this ).attr( 'id' );

		// do our boxcheck
		boxCheck    = tempAdminBoxCheck( boxBlock );

		// handle the button
		if ( boxCheck === true ) {
			tempAdminBoxButton( boxBlock, 'visible' );
		} else {
			tempAdminBoxButton( boxBlock, 'hidden' );
		}

		// now handle our clicking
		$( this ).on( 'change', 'input.tempadmin-user-check', function ( event ) {

			boxBlock    = $( this ).parents( 'div.tempadmin-users-list-box' ).attr( 'id' );

			// do our boxcheck
			boxCheck    = tempAdminBoxCheck( boxBlock );

			// handle the button
			if ( boxCheck === true ) {
				tempAdminBoxButton( boxBlock, 'visible' );
			} else {
				tempAdminBoxButton( boxBlock, 'hidden' );
			}
		});

	});

//*****************************************************************************************************
// trigger the checkbox if a username or email is clicked on a single row
//*****************************************************************************************************
	$( 'tr.tempadmin-single-user-row' ).on( 'click', 'td.column-clickable', function () {
		$( this ).siblings( 'th.check-column' ).find( 'input.tempadmin-user-check' ).trigger( 'click' );
	});

//*****************************************************************************************************
// process the user demotion or deletion
//*****************************************************************************************************
/*
	$( 'div.tempadmin-users-list-box' ).on( 'click', 'input.tempadmin-action-button-red', function ( event ) {

		// stop our default event
//		event.preventDefault();
	});
*/
//*****************************************************************************************************
// we are done here. go home
//*****************************************************************************************************
});