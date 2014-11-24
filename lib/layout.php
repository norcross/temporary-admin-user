<?php
/**
 * Temporary Admin User - Layout Module
 *
 * Contains all the layout and markup setup
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
class TempAdminUser_Layout {

	/**
	 * construct the HTML for the new user portion of the admin page
	 *
	 * @return html               the HTML of the new user form portion
	 */
	public static function new_user_form() {

		// fetch my time ranges
		$ranges = TempAdminUser_Utilities::get_time_ranges();

		// create an empty
		$show   = '';

		// begin the form markup for the wrapper on the field box
		$show  .= '<form method="post">';

			// the text input for the email
			$show  .= '<div class="tempadmin-new-user-field tempadmin-new-user-email">';
			$show  .= '<input type="email" id="tempadmin-data-email" name="tempadmin-data[email]" value="" class="widefat" >';
			$show  .= '<label for="tempadmin-data-email">' . __( 'Email Address', 'temporary-admin-user' ) . '</label>';
			$show  .= '</div>';

			// check for having available ranges
			if ( ! empty( $ranges ) ) {
				// the warpper markup for the time input field
				$show  .= '<div class="tempadmin-new-user-field tempadmin-new-user-time">';
				// set the markup for the dropdown
				$show  .= '<select id="tempadmin-data-time" name="tempadmin-data[time]" class="widefat" required>';
				// loop my ranges
				foreach ( $ranges as $key => $values ) {
					$show  .= '<option value="' . esc_attr( $key ) . '">' . esc_attr( $values['label'] ) . '</option>';
				}
				// close the select box
				$show  .= '</select>';
				// add my field label
				$show  .= '<label for="tempadmin-new-user-time">' . __( 'Time', 'temporary-admin-user' ) . '</label>';
				// now close the markup
				$show  .= '</div>';
			} else {
				// we didn't have ranges (for some reason) so just add a hidden field
				$show  .= '<input type="hidden" id="tempadmin-data-time" name="tempadmin-data[time]" value="day">';
			}

			// and my submit button
			$show  .= '<div class="tempadmin-new-user-field tempadmin-new-user-submit">';
			$show  .= get_submit_button( __( 'Create User', 'temporary-admin-user' ), 'primary', '', false, array( 'id' => 'tempadmin-submit' ) );
			$show  .= '</div>';

			// add our nonce for non JS saving
			$show  .= wp_nonce_field( 'tempadmin_make_nojs', 'tempadmin-manual-nonce', true, false );

		// close the  markup for the wrapper on the field box
		$show  .= '</form>';

		// return the markup
		return $show;
	}

	/**
	 * [user_action_button description]
	 * @param  string $title [description]
	 * @param  string $type  [description]
	 * @return [type]        [description]
	 */
	public static function user_action_button( $title = '', $type = '' ) {

		// set all my classes in an array
		$class  = array( 'delete', 'small', 'tempadmin-user-action', 'tempadmin-action-button-red' );

		// cast my type to make sure it doesn't mess anything up
		$type   = ! empty( $type ) ? esc_sql( $type ) : 'unknown';

		// build the button
		$button	= get_submit_button( $title, $class, '', false, array( 'id' => 'tempadmin-user-action-' . $type, 'data-type' => $type ) );

		// add a hidden field to indicate which action is happening
		$hidden = '<input type="hidden" name="tempadmin-user-action" value="' . $type . '">';

		// add our nonce for non JS saving
		$nonce  = wp_nonce_field( 'tempadmin_' . $type . '_nojs', 'tempadmin-manual-' . $type . '-nonce', true, false );

		// return them both
		return $button . $hidden . $nonce;
	}

	/**
	 * construct the HTML for the existing user portion of the admin page
	 *
	 * @param  string  $role      which subset of users to get based on user role (current or expired)
	 *
	 * @return html               the HTML of the new user form portion
	 */
	public static function existing_user_list( $role = 'administrator' ) {

		// get my users
		$users  = TempAdminUser_Utilities::get_temp_users( $role, 'all' );

		// we have users. start building a table
		$table  = '';

		// start the table wrapper markup
		$table .= '<table class="wp-list-table widefat fixed users">';

		// set my table header
		$table .= '<thead>';
		$table .= self::user_head_foot_row();
		$table .= '</thead>';

		// set my table footer
		$table .= '<tfoot>';
		$table .= self::user_head_foot_row();
		$table .= '</tfoot>';

		// set the table body
		$table .= '<tbody>';
		// if no users, just show an empty row
		if ( empty( $users ) ) {
			$table .= '<tr class="tempadmin-single-user-row tempadmin-empty-users-row standard">';
				$table .= '<td colspan="4">';
					$table .= '<span class="description">' . __( 'There are no users currently in this group.', 'temporary-admin-user' ) . '</span>';
				$table .= '</td>';
			$table .= '</tr>';
		} else {
			// set a counter
			$i = 1;
			// loop the users
			foreach( $users as $user ) {

				// get my alternating class
				$class  = $i & 1 ? 'alternate' : 'standard';

				// pull the row markup
				$table .= self::single_user_row( $user, $class );

				// and increment our counter
				$i++;
			}
		}
		$table .= '</tbody>';

		// close the table wrapper
		$table .= '</table>';

		// return the table
		return $table;
	}

	/**
	 * load the table header and footer
	 *
	 * @return [type]        [description]
	 */
	public static function user_head_foot_row() {

		// make an empty
		$row    = '';

		$row   .= '<th class="manage-column column-cb check-column" id="cb" scope="col"><input class="tempadmin-user-check" type="checkbox" id="tempadmin-all"></th>';
		$row   .= '<th class="manage-column column-username">' . __( 'Username', 'temporary-admin-user' ) . '</th>';
		$row   .= '<th class="manage-column column-email">' . __( 'Email Address', 'temporary-admin-user' ) . '</th>';
		$row   .= '<th class="manage-column column-created">' . __( 'Date Created', 'temporary-admin-user' ) . '</th>';
		$row   .= '<th class="manage-column column-expired">' . __( 'Expiration', 'temporary-admin-user' ) . '</th>';

		// send it back
		return $row;
	}

	/**
	 * [single_user_row description]
	 * @param  [type] $user  [description]
	 * @param  string $class [description]
	 * @return [type]        [description]
	 */
	public static function single_user_row( $user = OBJECT, $class = 'standard' ) {

		// check if we recieved just the user ID and fetch the object
		if ( is_numeric( $user ) && ! is_object( $user ) ) {
			$user   = get_user_by( 'id', $user );
		}

		// get some user meta
		$create = get_user_meta( $user->ID, '_tempadmin_created', true );
		$expire = get_user_meta( $user->ID, '_tempadmin_expire', true );

		// make an empty
		$row    = '';

		// markup the user
		$row   .= '<tr id="single-user-' . absint( $user->ID ) . '" class="tempadmin-single-user-row ' . esc_html( $class ) . '">';
			$row   .= '<th class="check-column" scope="row">';
				$row   .= '<input class="tempadmin-user-check" type="checkbox" value="' . absint( $user->ID ) . '" id="user_' . absint( $user->ID ) . '" name="users[]">';
			$row   .= '</th>';
			$row   .= '<td class="username column-username column-clickable">' . esc_attr( $user->user_login ) . '</td>';
			$row   .= '<td class="email column-email column-clickable">' . sanitize_email( $user->user_email ) . '</td>';
			$row   .= '<td class="created column-created">';
			if ( ! empty( $create ) ) {
				$row   .= TempAdminUser_Utilities::format_date_display( $create, 'date-format' ) . ' @ ' . TempAdminUser_Utilities::format_date_display( $create, 'time-format' );
			}
			$row   .= '</td>';

			$row   .= '<td class="expired column-expired">';
			if ( ! empty( $expire ) ) {
				$row   .= TempAdminUser_Utilities::format_date_display( $expire, 'date-format' ) . ' @ ' . TempAdminUser_Utilities::format_date_display( $expire, 'time-format' );
			}
			$row   .= '</td>';

		$row   .= '</tr>';

		// return the row
		return $row;
	}

	/**
	 * create the content portion of the email sent to a new user
	 *
	 * @param  object  $user      the WP_User object
	 * @param  string  $password  the password assigned to the new user
	 * @param  array   $sitedata  the data array of site info
	 *
	 * @return mixed              the content of the email, possibly HTML formatted
	 */
	public static function generate_email_content( $user = OBJECT, $password = '', $sitedata =  array() ) {

		// get our two expire setups
		$expire_stamp   = get_user_meta( $user->ID, '_tempadmin_expire', true );
		$expire_date    = TempAdminUser_Utilities::format_date_display( $expire_stamp, 'date-format' );
		$expire_time    = TempAdminUser_Utilities::format_date_display( $expire_stamp, 'time-format' );

		// set an empty
		$message    = '';

		// set the primary hello message
		$message   .= sprintf( __( 'A temporary user account has been created for you on %s. This login will expire on %s at %s', 'temporary-admin-user' ), $sitedata['site-name'], $expire_date, $expire_time ) . "\r\n";

		// and the user / pass / login URL message
		$message   .= sprintf( __( 'Username: %s', 'temporary-admin-user' ), esc_attr( $user->user_login ) ) . "\r\n";
		$message   .= sprintf( __( 'Password: %s', 'temporary-admin-user' ), $password ) . "\r\n";
		$message   .= sprintf( __( 'Login URL: %s', 'temporary-admin-user' ), esc_url( $sitedata['login-url'] ) );

		// filter it
		$message   = apply_filters( 'tempadmin_email_message', $message );

		// if we aren't HTML, just return plain text
		if ( 'html' !== apply_filters( 'tempadmin_email_content_type', 'html' ) ) {
			return $message;
		}

		// so we have the HTML type. format that
		$content    = '';
		$content   .= '<html>' . "\n";
		$content   .= '<head>' . "\n";
		$content   .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . "\n";
		$content   .= '</head>' . "\n";
		$content   .= '<body>' . "\n";
		$content   .= TempAdminUser_Utilities::format_email_content( $message );
		$content   .= '</body>' . "\n";
		$content   .= '</html>' . "\n";

		// send it back
		return trim( $content );
	}

/// end class
}


// Instantiate our class
new TempAdminUser_Layout();
