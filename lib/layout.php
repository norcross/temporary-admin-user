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
	 * construct the HTML for the existing user portion of the admin page
	 *
	 * @param  string  $role      which subset of users to get based on user role (current or expired)
	 *
	 * @return html               the HTML of the new user form portion
	 */
	public static function existing_user_list( $role = 'administrator' ) {

		// get my users
		$users  = TempAdminUser_Utilities::get_temp_users( $role, 'all' );

		// if no users, just return a nice message
		if ( empty( $users ) ) {
			return '<p class="description">' . __( 'There are no current users in this group.', 'temporary-admin-user' ) . '</p>';
		}

		// we have users. start building a table
		$table  = '';

		// start the markup
		$table .= '<table class="wp-list-table widefat fixed users">';

		// set my table header
		$table .= '<thead>';
			// my individual headers
			$table .= '<th class="manage-column column-cb check-column" id="cb" scope="col"><input class="tempadmin-user-check" type="checkbox" id="tempadmin-all"></th>';
			$table .= '<th class="manage-column column-username">' . __( 'Username', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-email">' . __( 'Email Address', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-created">' . __( 'Date Created', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-expired">' . __( 'Expiration', 'temporary-admin-user' ) . '</th>';
		$table .= '</thead>';

		// set my table footer
		$table .= '<tfoot>';
			// my individual headers
			$table .= '<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" id="tempadmin-all"></th>';
			$table .= '<th class="manage-column column-username">' . __( 'Username', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-email">' . __( 'Email Address', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-created">' . __( 'Date Created', 'temporary-admin-user' ) . '</th>';
			$table .= '<th class="manage-column column-expired">' . __( 'Expiration', 'temporary-admin-user' ) . '</th>';
		$table .= '</tfoot>';

		// set the table body
		$table .= '<tbody>';
			// set a counter
			$i = 1;
			// loop the users
			foreach( $users as $user ) {

				// get my alternating class
				$class  = $i & 1 ? 'alternate' : 'standard';

				// get some user meta
				$create = get_user_meta( $user->ID, '_tempadmin_created', true );
				$expire = get_user_meta( $user->ID, '_tempadmin_expire', true );

				// markup the user
				$table .= '<tr class="' . esc_html( $class ) . '">';
					$table .= '<th class="check-column" scope="row">';
						$table .= '<input class="tempadmin-user-check" type="checkbox" value="' . absint( $user->ID ) . '" id="user_' . absint( $user->ID ) . '" name="users[]">';
					$table .= '</th>';
					$table .= '<td class="username column-username">' . esc_attr( $user->user_login ) . '</td>';
					$table .= '<td class="email column-email">' . sanitize_email( $user->user_email ) . '</td>';
					$table .= '<td class="created column-created">';
						$table .= TempAdminUser_Utilities::format_date_display( $create, 'date-format' ) . ' @ ' . TempAdminUser_Utilities::format_date_display( $create, 'time-format' );
					$table .= '</td>';

					$table .= '<td class="expired column-expired">';
						$table .= TempAdminUser_Utilities::format_date_display( $expire, 'date-format' ) . ' @ ' . TempAdminUser_Utilities::format_date_display( $expire, 'time-format' );
					$table .= '</td>';

				$table .= '</tr>';
				// and increment our counter
				$i++;
			}

		$table .= '</tbody>';

		// close the table
		$table .= '</table>';

		// return the table
		return $table;
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
