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
 * Calculate the Epoch time expiration.
 *
 * @param  string  $length        The length of time we are requesting.
 * @param  string  $action        What action we are taking on the user.
 * @param  integer $current_time  Optional current time to use.
 *
 * @return integer $duration      The expiration date in unix time.
 */
function create_expire_time( $length = '', $action = 'create', $current_time = 0 ) {

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
	$uname  = ! empty( $uname ) ? $uname : wp_generate_password( 10, false, false );

	// Check if it exists.
	$uname  = ! username_exists( $uname ) ? $uname : $uname . substr( uniqid( '', true ), -5 );

	// Return it sanitized.
	return sanitize_user( $uname, true );
}

/**
 * Create a random password.
 *
 * @return string  The password generated from wp_generate_password.
 */
function create_user_password() {

	// Return the password generated.
	return apply_filters( Core\HOOK_PREFIX . 'random_password', wp_generate_password( 16, true, false ) );
}
