<?php
/**
 * Temporary Admin User - Helper Module
 *
 * Contains various utility or helper functions
 *
 * @package TempAdminUser
 */

 // Start up the engine
class TempAdminUser_Helper {

	/**
	 * Return our base link, with function fallbacks.
	 *
	 * @return string
	 */
	public static function get_menu_link() {
		return ! function_exists( 'menu_page_url' ) ? admin_url( 'users.php?page=' . TMP_ADMIN_USER_MENU_BASE ) : menu_page_url( TMP_ADMIN_USER_MENU_BASE, false );
	}

	/**
	 * Check if we're on our settings admin page.
	 *
	 * @return boolean
	 */
	public static function check_admin_page() {

		// Bail on non-admin right away.
		if ( ! is_admin() ) {
			return false;
		}

		// Check the query string and go forward, my child.
		return ! empty( $_GET['page'] ) && TMP_ADMIN_USER_MENU_BASE === sanitize_text_field( $_GET['page'] ) ? true : false;
	}

	/**
	 * Take a GMT timestamp and convert it to the local.
	 *
	 * @param  integer $timestamp  The timestamp in GMT.
	 * @param  string  $format     What date format we want to return. False for the timestamp.
	 *
	 * @return integer $timestamp  The timestamp in GMT.
	 */
	public static function gmt_to_local( $timestamp = 0, $format = 'Y/m/d g:i:s' ) {

		// Bail if we don't have a timestamp to check.
		if ( empty( $timestamp ) ) {
			return;
		}

		// Fetch our timezone.
		$savedzone  = get_option( 'timezone_string', false );

		// Make sure one is stored.
		$storedzone = ! empty( $savedzone ) ? $savedzone : 'America/New_York';

		// Pull my stored time with the UTC code on it.
		$date_gmt   = new DateTime( date( 'Y-m-d H:i:s', $timestamp ), new DateTimeZone( 'GMT' ) );

		// Now set the timezone to return the date.
		$date_gmt->setTimezone( new DateTimeZone( $storedzone ) );

		// Return it formatted, or the timestamp.
		return ! empty( $format ) ? $date_gmt->format( $format ) : $date_gmt->format( 'U' );
	}

	/**
	 * Get and return a specific error message.
	 *
	 * @param  string $code  The error key being requested.
	 *
	 * @return string        The message text.
	 */
	public static function get_admin_messages( $code = 'default' ) {

		// Return if we don't have an error code.
		if ( empty( $code ) ) {
			return __( 'There was an error with your request.', 'temporary-admin-user' );
		}

		// Handle my different error codes.
		switch ( esc_attr( strtolower( $code ) ) ) {

			case 'create' :
			case 'created' :
				return __( 'Success! A new user was created.', 'temporary-admin-user' );
				break;

			case 'promote' :
				return __( 'The selected account was promoted.', 'temporary-admin-user' );
				break;

			case 'bulk_promote' :
				return __( 'The selected accounts were promoted.', 'temporary-admin-user' );
				break;

			case 'restrict' :
				return __( 'The selected account was restricted.', 'temporary-admin-user' );
				break;

			case 'bulk_restrict' :
				return __( 'The selected accounts were restricted.', 'temporary-admin-user' );
				break;

			case 'delete' :
				return __( 'The selected account was deleted.', 'temporary-admin-user' );
				break;

			case 'bulk_delete' :
				return __( 'The selected accounts were deleted.', 'temporary-admin-user' );
				break;

			case 'nonce' :
				return __( 'The required nonce is missing or has failed.', 'temporary-admin-user' );
				break;

			case 'noemail' :
				return __( 'Please enter a valid email address.', 'temporary-admin-user' );
				break;

			case 'usedemail' :
				return __( 'The provided email address already exists. Please use another.', 'temporary-admin-user' );
				break;

			case 'noduration' :
				return __( 'No user duration was selected.', 'temporary-admin-user' );
				break;

			case 'badduration' :
				return __( 'The selected duration is not valid.', 'temporary-admin-user' );
				break;

			case 'notype' :
				return __( 'No action type was provided.', 'temporary-admin-user' );
				break;

			case 'badtype' :
				return __( 'The action type requested was not valid.', 'temporary-admin-user' );
				break;

			case 'nocreate' :
				return __( 'No user account was created.', 'temporary-admin-user' );
				break;

			case 'noid' :
				return __( 'No user ID was included in the request.', 'temporary-admin-user' );
				break;

			case 'nouser' :
				return __( 'No user exists for the given ID.', 'temporary-admin-user' );
				break;

			case 'baduser' :
				return __( 'The provided ID is not a valid user ID.', 'temporary-admin-user' );
				break;

			case 'nousers' :
				return __( 'No user accounts were selected.', 'temporary-admin-user' );
				break;

			case 'norestrict' :
				return __( 'Some user accounts could not be restricted. Please try again.', 'temporary-admin-user' );
				break;

			case 'nodelete' :
				return __( 'Some user accounts could not be deleted. Please try again.', 'temporary-admin-user' );
				break;

			case 'isatemp' :
				return __( 'This user was created with the Temporary Admin User plugin.', 'temporary-admin-user' );
				break;

			case 'unknown' :
			case 'unknown_error' :
				return __( 'There was an unknown error with your request.', 'temporary-admin-user' );
				break;

			default :
				return __( 'There was an error with your request.', 'temporary-admin-user' );

			// End all case breaks.
		}
	}

	// End class.
}

// Instantiate our class
new TempAdminUser_Helper();
