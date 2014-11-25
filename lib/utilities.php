<?php
/**
 * Temporary Admin User - Utilities Module
 *
 * Contains various utility or helper functions
 *
 * @package Temporary Admin User
 */
/*  Copyright 2014 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

 // Start up the engine
class TempAdminUser_Utilities {

	/**
	 * get and return a specific error message
	 *
	 * @param  string  $key       the error message being requested
	 *
	 * @return string             the message text
	 */
	public static function get_admin_messages( $key = 'default' ) {

		// set an array of messages
		$text   = array(
			'created'   => __( 'Success! A new user was created.', 'temporary-admin-user' ),
			'demoted'   => __( 'The selected accounts were demoted.', 'temporary-admin-user' ),
			'deleted'   => __( 'The selected accounts were deleted.', 'temporary-admin-user' ),
			'nonce'     => __( 'Nonce failed.', 'temporary-admin-user' ),
			'noemail'   => __( 'Please enter a valid email address.', 'temporary-admin-user' ),
			'usedemail' => __( 'This email address already exists. Please use another.', 'temporary-admin-user' ),
			'notype'    => __( 'No action type was provided.', 'temporary-admin-user' ),
			'badtype'   => __( 'The action type requested was not valid.', 'temporary-admin-user' ),
			'nocreate'  => __( 'No user account was created.', 'temporary-admin-user' ),
			'nousers'   => __( 'No user accounts were selected.', 'temporary-admin-user' ),
			'nodemote'  => __( 'Some user accounts could not be demoted. Please try again.', 'temporary-admin-user' ),
			'nodelete'  => __( 'Some user accounts could not be deleted. Please try again.', 'temporary-admin-user' ),
			'default'   => __( 'There was an error with your submission.', 'temporary-admin-user' ),
		);

		// filter them to allow more
		$text   = apply_filters( 'tempadmin_error_messages', $text );

		// return the specific one, or the default
		if ( ! empty( $key ) && ! empty( $text[$key] ) ) {
			return $text[$key];
		} else {
			return $text['default'];
		}
	}

	/**
	 * create a username from the provided email address. if
	 * the username already exists, it adds some random numbers
	 * to the end to make it unique
	 *
	 * @param  string  $email     the user-provided email
	 *
	 * @return string             the stripped and sanitized username
	 */
	public static function create_username( $email = '' ) {

		// break it up
		$split  = explode( '@', $email );

		// get the name portion, stripping out periods
		$name   = str_replace( array( '.', '+' ), '', $split[0] );

		// check if it exists
		if ( username_exists( $name ) ) {
			$name   = $name . substr( uniqid( '', true ), -5 );
		}

		// return it sanitized
		return sanitize_user( $name, true );
	}

	/**
	 * get the available expiration time ranges that a user can be set to
	 *
	 * @param  string  $key       optional to fetch one item from the array
	 *
	 * @return array              an array of the time data
	 */
	public static function get_time_ranges( $key = '' ) {

		// set my ranges
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

		// return it filtered
		$ranges = apply_filters( 'tempadmin_expire_ranges', $ranges );

		// bail if no data exists
		if ( empty( $ranges ) ) {
			return false;
		}

		// return the entire array if no key requested
		if ( empty( $key ) ) {
			return $ranges;
		}

		// return the single key if requested and it exists
		if ( ! empty( $key ) && isset( $ranges[$key] ) ) {
			return $ranges[$key];
		}

		// nothing left. just return
		return;
	}

	/**
	 * create a random password
	 *
	 * @return string             the password generated from wp_generate_password
	 */
	public static function generate_password() {

		// set my variables
		$charcount  = apply_filters( 'tempadmin_pass_charcount', 16 );
		$speclchar  = apply_filters( 'tempadmin_pass_specchar', true );
		$xspeclchar = apply_filters( 'tempadmin_pass_xspeclchar', false );

		// return the password generated
		return wp_generate_password( absint( $charcount ), $speclchar, $xspeclchar );
	}

	/**
	 * calculate the Epoch time expiration
	 *
	 * @return integer $time      the timestamp number
	 */
	public static function get_user_expire_time( $time = '' ) {

		// get my data for the particular period provided
		$data	= self::get_time_ranges( $time );

		// set my range accordingly
		$range  = ! empty( $data['value'] ) ? $data['value'] : DAY_IN_SECONDS;

		// send it back, added to the current stamp
		return time() + floatval( $range );
	}

	/**
	 * set up the WP_User_Query args
	 *
	 * @param  string  $role      the user role we want to pull from. defaults to admin
	 * @param  string  $fields    the fields to include on the return. defaults to ID
	 *
	 * @return array              an array of data for use
	 */
	public static function get_temp_users( $role = 'administrator', $fields = 'ID' ) {

		// set my args
		$args   = array(
			'fields'       => $fields,
			'role'         => $role,
			'meta_key'     => '_tempadmin_expire',
			'order'        => 'ASC',
			'orderby'      => 'meta_value',
			'meta_query'   => array(
				0 => array(
					'key'     => '_tempadmin_user',
					'value'   => true
				)
			)
		);

		// get my users
		$users  = new WP_User_Query( $args );

		// bail if none
		if ( empty( $users->results ) ) {
			return false;
		}

		// send them back
		return $users->results;
	}

	/**
	 * set up a data array of various site information
	 *
	 * @param  string  $key       optional to fetch one item from the array
	 *
	 * @return array              an array of data for use
	 */
	public static function get_site_data( $key = '' ) {

		// make my data array
		$data   = array(
			'site-name'     => get_bloginfo( 'name' ),
			'site-url'      => get_bloginfo( 'url' ),
			'admin-email'   => get_bloginfo( 'admin_email' ),
			'login-url'     => wp_login_url(),
			'date-format'   => get_option( 'date_format' ),
			'time-format'   => get_option( 'time_format' ),
			'timezone'      => get_option( 'timezone_string' )
		);

		// filter it
		$data  = apply_filters( 'tempadmin_site_data', $data );

		// bail if no data exists
		if ( empty( $data ) ) {
			return false;
		}

		// return the entire array if no key requested
		if ( empty( $key ) ) {
			return $data;
		}

		// return the single key if requested and it exists
		if ( ! empty( $key ) && isset( $data[$key] ) ) {
			return $data[$key];
		}

		// nothing left. just return
		return;
	}

	/**
	 * take our timestamp and format it to the stored WP
	 * value, adjusted for timezone if set
	 *
	 * @param  integer $stamp     the epoch timestamp
	 *
	 * @return string             the formatted date / time
	 */
	public static function format_date_display( $stamp = 0, $type = 'date-format' ) {

		// fetch my site data
		$sitedata   = self::get_site_data();

		// if we don't have timezone data, just return it
		if ( empty( $sitedata['timezone'] ) ) {
			return date( $sitedata[$type], $stamp );
		}

		// we have a timezone, so reset it
		$date   = new DateTime( '@' . $stamp );

		// now set the timezone
		$date->setTimezone( new DateTimeZone( $sitedata['timezone'] ) );

		// return the formatted
		return $date->format( $sitedata[$type] );
	}

	/**
	 * take the unformatted email content and convert to
	 * nicer HTML for your viewing pleasure
	 *
	 * @param  string  $message   the unformatted plain text message
	 *
	 * @return string             the newly formatted HTML message
	 */
	public static function format_email_content( $message = '' ) {

		// convert my linebreaks to HTML linebreaks
		$message    = nl2br( $message, false );

		// now do some fancy replacements
		$message    = '<p>' . str_replace( '<br>', '</p><p>', $message ) . '</p>';

		// now return it with an optional filter
		return apply_filters( 'tempadmin_email_formatted_text', $message );
	}

	/**
	 * create our admin URL with optional query strings
	 *
	 * @param  array   $strings   optional to fetch one item from the array
	 * @param  boolean $echo      whether to echo the URL or just return it
	 *
	 * @return array              an array of data for use
	 */
	public static function get_admin_page_link( $result = 'success', $strings = array(), $echo = false ) {

		// grab the base menu link on it's own
		$base   = menu_page_url( 'temporary-admin-user', 0 );

		// if no action, then return it as-is
		if ( empty( $base ) ) {
			return false;
		}

		// if we have an error, set that first
		if ( ! empty( $result ) && $result == 'error' ) {
			$link   = $base . '&error=1';
		}

		// if we have an success, set that first
		if ( ! empty( $result ) && $result == 'success' ) {
			$link   = $base . '&success=1';
		}

		// check for strings to add
		if ( ! empty( $strings ) && is_array( $strings ) ) {

			// loop them
			foreach( $strings as $k => $v ) {
				$link  .= '&' . $k . '=' . $v;
			}
		}

		// build and return the link
		if ( ! empty( $echo ) ) {
			echo $link;
		}

		// return it
		return $link;
	}

	/**
	 * check the user ID against the allowed permissions
	 * to prevent temp admins from adding / deleting
	 * users and other actions on site
	 *
	 * @param  integer $user_id   the epoch timestamp
	 *
	 * @return boolean            false if the use is a temp (or none at all), true otherwise
	 */
	public static function check_user_perm( $user_id = 0 ) {

		// if user ID is missing, fetch the current logged in
		if ( empty( $user_id ) ) {
			$user_id    = get_current_user_id();
		}

		// bail without an ID
		if ( empty( $user_id ) ) {
			return false;
		}

		// fetch the meta key
		$check  = get_user_meta( $user_id, '_tempadmin_user', true );

		// return our bool
		return ! empty( $check ) ? false : true;
	}

	/**
	 * check the user ID against the stored expiration
	 * timestamp to verify they are active
	 *
	 * @param  integer $user_id   the epoch timestamp
	 *
	 * @return boolean            false if the use is expired (or none at all), true otherwise
	 */
	public static function check_user_expire( $user_id = 0 ) {

		// if user ID is missing, fetch the current logged in
		if ( empty( $user_id ) ) {
			$user_id    = get_current_user_id();
		}

		// bail without an ID
		if ( empty( $user_id ) ) {
			return false;
		}

		// fetch the meta key
		$expire = get_user_meta( $user_id, '_tempadmin_expire', true );

		// return our bool
		return ! empty( $expire ) && time() <= floatval( $expire ) ? true : false;
	}

/// end class
}


// Instantiate our class
new TempAdminUser_Utilities();
