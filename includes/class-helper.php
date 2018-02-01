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
	 * Get the available expiration time ranges that a user can be set to.
	 *
	 * @param  string  $single  Optional to fetch one item from the array.
	 * @param  boolean $keys    Whether to return just the keys.
	 *
	 * @return array            An array of the time data.
	 */
	public static function get_user_durations( $single = '', $keys = false ) {

		// Set my ranges.
		$ranges = array(
			'hour'  => array(
				'value' => HOUR_IN_SECONDS,
				'label' => __( 'One Hour', 'temporary-admin-user' )
			),
			'day'   => array(
				'value' => DAY_IN_SECONDS,
				'label' => __( 'One Day', 'temporary-admin-user' )
			),
			'week'  => array(
				'value' => WEEK_IN_SECONDS,
				'label' => __( 'One Week', 'temporary-admin-user' )
			),
			'month' => array(
				'value' => DAY_IN_SECONDS * 30,
				'label' => __( 'One Month', 'temporary-admin-user' )
			),
		);

		// Return it filtered.
		$ranges = apply_filters( 'tmp_admin_expire_ranges', $ranges );

		// Bail if no data exists.
		if ( empty( $ranges ) ) {
			return false;
		}

		// Return just the array keys.
		if ( ! empty( $keys ) ) {
			return array_keys( $ranges );
		}

		// Return the entire array if no key requested.
		if ( empty( $single ) ) {
			return $ranges;
		}

		// Return the single key item.
		return ! empty( $single ) && isset( $ranges[ $single ] ) ? $ranges[ $single ] : false;
	}

	/**
	 * Calculate the Epoch time expiration.
	 *
	 * @return integer $time  The timestamp number.
	 */
	public static function get_user_expire_time( $time = '' ) {

		// Get my data for the particular period provided.
		$data	= self::get_user_durations( $time );

		// Set my range accordingly.
		$range  = ! empty( $data['value'] ) ? $data['value'] : DAY_IN_SECONDS;

		// Send it back, added to the current stamp.
		return time() + floatval( $range );
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

			case 'created' :
				return __( 'Success! A new user was created.', 'temporary-admin-user' );
				break;

			case 'demoted' :
				return __( 'The selected accounts were demoted.', 'temporary-admin-user' );
				break;

			case 'deleted' :
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

			case 'nousers' :
				return __( 'No user accounts were selected.', 'temporary-admin-user' );
				break;

			case 'norestrict' :
				return __( 'Some user accounts could not be restricted. Please try again.', 'temporary-admin-user' );
				break;

			case 'nodelete' :
				return __( 'Some user accounts could not be deleted. Please try again.', 'temporary-admin-user' );
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
