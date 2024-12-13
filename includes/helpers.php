<?php
/**
 * Contains various utility or helper functions.
 *
 * @package TempAdminUser
 */

// Call our namepsace.
namespace Norcross\TempAdminUser\Helpers;

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Queries as Queries;

/**
 * Fetch the admin menu link on the users menu.
 *
 * @return string
 */
function get_admin_menu_link() {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set the root menu page and the admin base.
	$set_menu_root  = trim( Core\MENU_ROOT );

	// If we're doing Ajax, build it manually.
	if ( wp_doing_ajax() ) {
		return add_query_arg( [ 'page' => $set_menu_root ], admin_url( 'users.php' ) );
	}

	// Use the `menu_page_url` function if we have it.
	if ( function_exists( 'menu_page_url' ) ) {

		// Return using the function.
		return menu_page_url( $set_menu_root, false );
	}

	// Build out the link if we don't have our function.
	return add_query_arg( [ 'page' => $set_menu_root ], admin_url( 'users.php' ) );
}

/**
 * Redirect based on an edit action result.
 *
 * @param  string  $error    Optional error code.
 * @param  string  $result   What the result of the action was.
 * @param  boolean $success  Whether it was successful.
 *
 * @return void
 */
function redirect_admin_action_result( $error = '', $result = 'failed', $success = false ) {

	// Set our base redirect link.
	$base_redirect  = get_admin_menu_link();

	// Set up my redirect args.
	$redirect_args  = [
		'tmp-admin-users-success'         => $success,
		'tmp-admin-users-action-complete' => 'yes',
		'tmp-admin-users-action-result'   => esc_attr( $result ),
	];

	// Add the error code if we have one.
	$redirect_args  = ! empty( $error ) ? wp_parse_args( $redirect_args, [ 'tmp-admin-users-error-code' => esc_attr( $error ) ] ) : $redirect_args;

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, $base_redirect );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}

/**
 * Calculate the Epoch time expiration.
 *
 * @param  string  $length        The length of time we are requesting.
 * @param  string  $action        What action we are taking on the user.
 * @param  integer $current_time  Optional current time to use.
 *
 * @return integer $duration      The expiration date in unix time.
 */
function create_expire_time( $length = 'day', $action = 'create', $current_time = 0 ) {

	// Allow my time length to be filtered based on action.
	$length = apply_filters( Core\HOOK_PREFIX . 'promote_duration', $length, $action );

	// Get my data for the particular period provided.
	$data	= Queries\get_user_durations( $length );

	// Set my range accordingly.
	$range  = absint( $data['value'] ) > 0 ? $data['value'] : DAY_IN_SECONDS;

	// Set our current time.
	$setnow = ! empty( $current_time ) ? $current_time : current_datetime()->format('U');

	// Create the expiration.
	$expire = absint( $setnow ) + absint( $range );

	// Send it back, added to the current stamp.
	return absint( $expire );
}

/**
 * Create a username from the provided email address. If the username already
 * exists, it adds some random numbers to the end to make it unique.
 *
 * @param  string $user_email  The user-provided email.
 *
 * @return string              The stripped and sanitized username.
 */
function create_username( $user_email = '' ) {

	// Return an empty string.
	if ( empty( $user_email ) ) {
		return '';
	}

	// Break it up.
	$split  = explode( '@', $user_email );

	// Get the name portion, stripping out periods.
	$uname  = preg_replace( '/[^a-zA-Z0-9\s]/', '', $split[0] );

	// Run our check to make sure something was left over.
	$uname  = ! empty( $uname ) ? $uname : 'random-' . mt_rand( 1000000000, 9999999999 );

	// Return it sanitized.
	return sanitize_user( $uname, true );
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
			return __( 'No parameters were defined. Please review the options below and try again.', 'temporary-admin-user' );
			break;

		case 'email-exists' :
			return __( 'No user roles were selected. Please select one or more and try again.', 'temporary-admin-user' );
			break;

		case 'no-duration' :
			return __( 'Please enter both a numeric value and the range to set a date.', 'temporary-admin-user' );
			break;

		case 'new-error' :
			return __( 'No inactive users were found based on the selected options.', 'temporary-admin-user' );
			break;

		case 'new-user' :
			return __( 'Success! A new temporary admin user has been created.', 'temporary-admin-user' );
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
