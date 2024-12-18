<?php
/**
 * Handle any admin notices.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Admin\Notices;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Admin\Markup as AdminMarkup;

/**
 * Start our engines.
 */
add_action( 'admin_notices', __NAMESPACE__ . '\display_admin_notices' );

/**
 * Display our admin notices.
 *
 * @return void
 */
function display_admin_notices() {

	// Make sure this is the correct admin page.
	$confirm_admin  = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS );

	// Make sure it is what we want.
	if ( empty( $confirm_admin ) || Core\MENU_ROOT !== $confirm_admin ) {
		return;
	}

	// Check for our complete flag.
	$confirm_action = filter_input( INPUT_GET, 'tmp-admin-users-action-complete', FILTER_SANITIZE_SPECIAL_CHARS );

	// Make sure it is what we want.
	if ( empty( $confirm_action ) || 'yes' !== $confirm_action ) {
		return;
	}

	// Now check for the result.
	$confirm_result = filter_input( INPUT_GET, 'tmp-admin-users-action-result', FILTER_SANITIZE_SPECIAL_CHARS );

	// Make sure we have a result to show.
	if ( empty( $confirm_result ) ) {
		return;
	}

	// Determine the message type.
	$maybe_failed   = filter_input( INPUT_GET, 'tmp-admin-users-success', FILTER_SANITIZE_NUMBER_INT );
	$confirm_type   = ! empty( $maybe_failed ) ? 'success' : 'error';

	// Handle dealing with an error return.
	if ( 'error' === $confirm_type ) {

		// Figure out my error code.
		$maybe_code = filter_input( INPUT_GET, 'tmp-admin-users-error-code', FILTER_SANITIZE_SPECIAL_CHARS );
		$error_code = ! empty( $maybe_code ) ? $maybe_code : 'unknown';

		// Handle my error text retrieval.
		$error_text = get_admin_notice_text( $error_code );

		// Make sure the error type is correct, since one is more informational.
		$error_type = 'email-exists' === $error_code ? 'info' : 'error';

		// And handle the display.
		AdminMarkup\render_admin_notice_markup( $error_text, $error_type );

		// And be done.
		return;
	}

	// Handle my success message based on the clear flag.
	$alert_text = get_admin_notice_text( $confirm_result );

	// And handle the display.
	AdminMarkup\render_admin_notice_markup( $alert_text, 'success' );

	// And be done.
	return;
}

/**
 * Check an code and (usually an error) return the appropriate text.
 *
 * @param  string $return_code  The code provided.
 *
 * @return string
 */
function get_admin_notice_text( $return_code = '' ) {

	// Handle my different error codes.
	switch ( esc_attr( $return_code ) ) {

		case 'no-email' :
			return __( 'The required user email was not provided.', 'temporary-admin-user' );
			break;

		case 'no-user-id' :
			return __( 'The required user ID was not provided.', 'temporary-admin-user' );
			break;

		case 'email-exists' :
			return __( 'The provided email address is already in use.', 'temporary-admin-user' );
			break;

		case 'no-duration' :
			return __( 'Please select a duration to allow the user access.', 'temporary-admin-user' );
			break;

		case 'invalid-user-action' :
			return __( 'The requested user action was not valid.', 'temporary-admin-user' );
			break;

		case 'failed-new-user' :
			return __( 'The new user could not be created. Please check your error logs.', 'temporary-admin-user' );
			break;

		case 'user-extend-error' :
			return __( 'The expiration time for the existing user could not be updated. Please check your error logs.', 'temporary-admin-user' );
			break;

		case 'user-promote-error' :
			return __( 'The existing user could not be promoted. Please check your error logs.', 'temporary-admin-user' );
			break;

		case 'user-restrict-error' :
			return __( 'The existing user could not be restricted. Please check your error logs.', 'temporary-admin-user' );
			break;

		case 'user-delete-error' :
			return __( 'The existing user could not be deleted. Please check your error logs.', 'temporary-admin-user' );
			break;

		case 'new-user-created' :
			return __( 'Success! A new temporary admin user has been created.', 'temporary-admin-user' );
			break;

		case 'user-extend-success' :
			return __( 'Success! The requested temporary user account expiration was extended.', 'temporary-admin-user' );
			break;

		case 'user-promote-success' :
			return __( 'Success! The requested temporary user account was promoted.', 'temporary-admin-user' );
			break;

		case 'user-restrict-success' :
			return __( 'Success! The requested temporary user account was restricted.', 'temporary-admin-user' );
			break;

		case 'user-delete-success' :
			return __( 'Success! The requested temporary user account was deleted.', 'temporary-admin-user' );
			break;

		case 'unknown' :
		case 'unknown-error' :
			return __( 'There was an unknown error with your request.', 'temporary-admin-user' );
			break;

		default :
			return __( 'There was an error with your request.', 'temporary-admin-user' );
			break;

		// End all case breaks.
	}
}
