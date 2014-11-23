<?php
/*
Plugin Name: Temporary Admin User
Plugin URI: http://andrewnorcross.com/plugins/
Description: Create a temporary WordPress admin user to provide access on support issues, etc.
Version: 0.0.1
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

	Copyright 2013 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( ! defined( 'TMP_ADMIN_USER_BASE ' ) ) {
	define( 'TMP_ADMIN_USER_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'TMP_ADMIN_USER_DIR' ) ) {
	define( 'TMP_ADMIN_USER_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'TMP_ADMIN_USER_VER' ) ) {
	define( 'TMP_ADMIN_USER_VER', '0.0.1' );
}

// Start up the engine
class TempAdminUser_Core
{
	/**
	 * Static property to hold our singleton instance
	 * @var TempAdminUser_Core
	 */
	static $instance = false;

	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return TempAdminUser_Core
	 */
	private function __construct() {
		add_action( 'plugins_loaded',                   array( $this, 'textdomain'              )           );
		add_action( 'init',                             array( $this, 'check_user_statuses'     ),  9999    );
		add_action( 'admin_enqueue_scripts',            array( $this, 'scripts_styles'          ),  10      );
		add_action( 'admin_menu',                       array( $this, 'admin_menu'              )           );

		add_action( 'admin_notices',                    array( $this, 'admin_notices'           ),  10      );

		add_action( 'admin_init',                       array( $this, 'create_user_nojs'        )           );
		add_action( 'wp_ajax_create_user_js',           array( $this, 'create_user_js'          )           );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {

		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * load textdomain for international goodness
	 *
	 * @return null
	 */
	public function textdomain() {
		load_plugin_textdomain( 'temporary-admin-user', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * load our CSS and JS files for the new user creation page
	 *
	 * @param  string  $hook      the page hook for the admin
	 *
	 * @return null
	 */
	public function scripts_styles( $hook ) {

		// bail if we aren't on our page
		if ( empty( $hook ) || ! empty( $hook ) && $hook !== 'users_page_temporary-admin-user' ) {
			return;
		}

		// set our file suffixes
		$css_fx = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';
		$js_fx  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.js' : '.min.js';

		// and our version
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : TMP_ADMIN_USER_VER;

		// load te CSS file
		wp_enqueue_style( 'tempadmin-user', plugins_url( '/lib/css/tmpadmin' . $css_fx, __FILE__ ), array(), $vers, 'all' );

		// load and localize
		wp_enqueue_script( 'tempadmin-user', plugins_url( 'lib/js/tmpadmin' . $js_fx, __FILE__ ), array( 'jquery' ), $vers, true );
		wp_localize_script( 'tempadmin-user', 'tempAdminData', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'makeNonce' => wp_create_nonce( 'tempadmin_make_js' ),
			'noEmail'   => self::get_admin_messages( 'email' )
		));
	}

	/**
	 * create admin page for temporary users
	 *
	 * @return null
	 */
	public function admin_menu() {
		add_users_page( __( 'Temporary Users', 'temporary-admin-user' ), __( 'Temporary Users', 'temporary-admin-user' ), apply_filters( 'tempadmin_user_cap', 'manage_options' ), 'temporary-admin-user', array( $this, 'admin_settings' ) );
	}

	/**
	 * display message on saved settings
	 * @return [HTML] message above page
	 */
	public function admin_notices() {

		// first check to make sure we're on our settings
		if ( empty( $_REQUEST['page'] ) || empty( $_REQUEST['page'] ) && $_REQUEST['page'] !== 'temporary-admin-user' ) {
			return;
		}

		// if we have neither success or error, bail
		if ( empty( $_REQUEST['success'] ) && empty( $_REQUEST['error'] ) ) {
			return;
		}

		// if we have success, just return that and go on your way
		if ( ! empty( $_REQUEST['success'] ) ) {

			echo '<div id="message" class="updated below-h2 tempadmin-message">';
				echo '<p>' . self::get_admin_messages( 'success' ) . '</p>';
			echo '</div>';

			return;
		}

		// get my type (error code)
		$type   = ! empty( $_REQUEST['errcode'] ) ? $_REQUEST['errcode'] : '';

		// set an empty
		$text   = '';

		// now our error code checks
		switch ( $type ) {

			case 'NONCE_FAILED':

				$text   = self::get_admin_messages( 'nonce' );
				break;

			case 'NO_EMAIL':

				$text   = self::get_admin_messages( 'email' );
				break;

			case 'NO_USER':

				$text   = self::get_admin_messages( 'nouser' );
				break;

			default:

				$text   = self::get_admin_messages( 'default' );

		// end all case breaks
		}

		echo '<div id="message" class="error below-h2 tempadmin-message">';
			echo '<p>' . esc_attr( $text ) . '</p>';
		echo '</div>';

		return;
	}

	/**
	 * display admin page for creating and managing temporary users
	 *
	 * @return null
	 */
	public function admin_settings() {

		// check our user permissions again
		if ( ! current_user_can( apply_filters( 'tempadmin_user_cap', 'manage_options' ) ) ) {
			echo __( 'You do not have permission to access this page.', 'temporary-admin-user' );
			die();
		}

		// begin the markup for the settings page
		echo '<div class="wrap tempadmin-settings-wrap">';
			echo '<h2>' . __( 'Manage Temporary Users', 'temporary-admin-user' ) . '</h2>';

			// display our new user form
			echo '<div class="tempadmin-settings-box tempadmin-new-user-box">';
				echo '<h3>' . __( 'Create New User', 'temporary-admin-user' ) . '</h3>';
				echo self::new_user_form();
			echo '</div>';

			// display our existing active users
			echo '<div class="tempadmin-settings-box tempadmin-users-active">';
				echo '<h3>' . __( 'Active User Accounts', 'temporary-admin-user' ) . '</h3>';
				echo self::existing_user_list( 'administrator' );
			echo '</div>';

			// display our existing expired users
			echo '<div class="tempadmin-settings-box tempadmin-users-expired">';
				echo '<h3>' . __( 'Expired User Accounts', 'temporary-admin-user' ) . '</h3>';
				echo self::existing_user_list( 'subscriber' );
			echo '</div>';

		// close the markup for the settings page
		echo '</div>';
	}

	/**
	 * create the new temporary user
	 *
	 * @param  string  $email     the user-submitted email
	 * @param  string  $time      the user-submitted expiration
	 *
	 * @return integer $user      the newly created user ID
	 */
	protected static function create_new_user( $email = '', $time = '' ) {

		// first get our password, since we use it in multiple places
		$password   = self::generate_password();

		// set my user args
		$user_args  = array(
			'user_login'  => self::create_username( $email ),
			'user_pass'   => $password,
			'user_email'  => sanitize_email( $email, true ),
			'role'        => 'administrator'
		);

		// filter the args
		$user_args  = apply_filters( 'tempadmin_new_user_args', $user_args );

		// create the user
		$user_id    = wp_insert_user( $user_args ) ;

		// return an error message if the user was not created
		if( is_wp_error( $user_id ) ) {
 			// get my first error code
 			$code   = $user_id->get_error_code();
 			// return the array
 			return array(
 				'error'     => true,
 				'errcode'   => $code,
 				'message'   => $user_id->get_error_message( $code )
 			);
		}

		// now add our custom meta keys
		update_user_meta( $user_id, '_tempadmin_user', true );
		update_user_meta( $user_id, '_tempadmin_created', time() );
		update_user_meta( $user_id, '_tempadmin_expire', self::get_user_expire_time( $time ) );

		// and update some basic WP related user meta
		update_user_meta( $user_id, 'show_welcome_panel', 0 );
		update_user_meta( $user_id, 'dismissed_wp_pointers', 'wp330_toolbar,wp330_saving_widgets,wp340_choose_image_from_library,wp340_customize_current_theme_link,wp350_media,wp360_revisions,wp360_locks' );

		// first check for the bypass
		if ( true === apply_filters( 'tempadmin_send_email_notification', true ) ) {
			self::new_user_notification( $user_id, $password );
		}

		// return the user ID
		return $user_id;
	}

	/**
	 * create a random password
	 *
	 * @return string             the password generated from wp_generate_password
	 */
	protected static function generate_password() {

		// set my variables
		$charcount  = apply_filters( 'tempadmin_pass_charcount', 16 );
		$speclchar  = apply_filters( 'tempadmin_pass_specchar', true );
		$xspeclchar = apply_filters( 'tempadmin_pass_xspeclchar', false );

		// return the password generated
		return wp_generate_password( absint( $charcount ), $speclchar, $xspeclchar );
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
	 * set the HTML type as text / HTML instead of plaintext
	 */
	public static function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * set up the new user notification email
	 *
	 * @param  integer $user      the newly created user ID or the entire object
	 * @param  string  $password  the password assigned to the new user
	 *
	 * @return mixed              an HTML email with the new user info
	 */
	protected static function new_user_notification( $user = 0, $password = '' ) {

		// check if we recieved just the user ID and fetch the object
		if ( is_numeric( $user ) && ! is_object( $user ) ) {
			$user   = new WP_User( $user );
		}

		// bail if somehow we didn't get a valid user object
		if( ! is_object( $user ) ) {
			return false;
		}

		// get our email content type
		$email_type = apply_filters( 'tempadmin_email_content_type', 'html' );

		// fetch the site info, which we need for the email
		$sitedata   = self::get_site_data();

		// switch to HTML format unless otherwise told
		if ( $email_type == 'html' ) {
			add_filter( 'wp_mail_content_type', array( __class__, 'set_html_content_type' ) );
		}

		// construct email headers
		$headers    = 'From: ' . esc_attr( $sitedata['site-name'] ) . ' <' . sanitize_email( $sitedata['admin-email'] ) . '>' . "\r\n";
		$headers   .= 'Return-Path: ' . sanitize_email( $sitedata['admin-email'] ) ."\r\n";
		// add the additional headers for HTML email
		if ( $email_type == 'html' ) {
			$headers   .= 'MIME-Version: 1.0' . "\r\n";
			$headers   .= 'Content-Type: text/html; charset="UTF-8"'. "\r\n";
		}

		// set my email subject
		$subject    = apply_filters( 'tempadmin_email_subject', __( 'New User Account', 'temporary-admin-user' ) );

		// get my user content
		$content    = self::generate_email_content( $user, $password, $sitedata );

		// and send the email
		wp_mail( sanitize_email( $user->user_email ), $subject, $content, $headers );

		// reset the email content type (if originally set )
		if ( $email_type == 'html' ) {
			remove_filter( 'wp_mail_content_type', array( __class__, 'set_html_content_type' ) );
		}

		// and return
		return;
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
		$expire_date    = self::format_date_display( $expire_stamp, 'date-format' );
		$expire_time    = self::format_date_display( $expire_stamp, 'time-format' );

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
		$content   .= self::format_email_content( $message );
		$content   .= '</body>' . "\n";
		$content   .= '</html>' . "\n";

		// send it back
		return trim( $content );
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
	 * construct the HTML for the new user portion of the admin page
	 *
	 * @return html               the HTML of the new user form portion
	 */
	public static function new_user_form() {

		// fetch my time ranges
		$ranges = self::get_time_ranges();

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
		$users  = self::get_temp_users( $role, 'all' );

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
			$table .= '<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" id="tempadmin-all"></th>';
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
						$table .= '<input type="checkbox" value="' . absint( $user->ID ) . '" id="user_' . absint( $user->ID ) . '" name="users[]">';
					$table .= '</th>';
					$table .= '<td class="username column-username">' . esc_attr( $user->user_login ) . '</td>';
					$table .= '<td class="email column-email">' . sanitize_email( $user->user_email ) . '</td>';
					$table .= '<td class="created column-created">';
						$table .= self::format_date_display( $create, 'date-format' ) . ' @ ' . self::format_date_display( $create, 'time-format' );
					$table .= '</td>';

					$table .= '<td class="expired column-expired">';
						$table .= self::format_date_display( $expire, 'date-format' ) . ' @ ' . self::format_date_display( $expire, 'time-format' );
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
	 * [format_date_display description]
	 * @param  integer $stamp [description]
	 * @param  string  $type  [description]
	 * @return [type]         [description]
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
	 * set up a data array of various site information
	 *
	 * @param  string  $key       optional to fetch one item from the array
	 *
	 * @return array              an array of data for use
	 */
	protected static function get_site_data( $key = '' ) {

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
			'meta_key'     => '_tempadmin_user',
			'meta_value'   => true,
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
	 * [check_user_statuses description]
	 * @return [type] [description]
	 */
	public function check_user_statuses() {

		// check for transient, run every hour (for now)
		if( false === get_transient( 'tempadmin_status_check' )  ) {

			// get my users
			$users  = self::get_temp_users();

			// if none exist, set the transient and bail
			if ( empty( $users ) ) {
				// write the transient
				set_transient( 'tempadmin_status_check', 1, HOUR_IN_SECONDS );
				// and bail
				return;
			}

			// we have users. loop and send them over to be updated
			foreach ( $users as $user_id ) {

				// get their expiration time
				$expire = get_user_meta( $user_id, '_tempadmin_expire', true );

				// if that time has passed, reset their status
				if ( empty( $expire ) || time() > $expire ) {
					self::reset_user_status( $user_id );
				}

			}

			set_transient( 'tempadmin_status_check', 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * update the user to a specified role
	 *
	 * @param  integer $user_id   the user object being modified
	 * @param  string  $role      the status being changed to
	 *
	 * @return bool               the result of the update
	 */
	protected static function reset_user_status( $user_id = 0, $role = 'subscriber' ) {

		// set a quick setup string
		$update = wp_update_user( array( 'ID' => absint( $user_id ), 'role' => $role ) );

		// send back true / false bool
		return ! is_wp_error( $update ) ? true : false;
	}

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
			'success'   => __( 'Success! A new user was created.', 'temporary-admin-user' ),
			'nonce'     => __( 'Nonce failed.', 'temporary-admin-user' ),
			'email'     => __( 'Please enter a valid email address.', 'temporary-admin-user' ),
			'nouser'    => __( 'No user account was created.', 'temporary-admin-user' ),
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
	 * our user creation function to go on non-ajax
	 *
	 * @return null
	 */
	public function create_user_nojs() {

		// make sure we have our actions before moving forward
		if( empty( $_POST['tempadmin-data'] ) || empty( $_POST['tempadmin-manual-nonce'] ) ) {
			return;
		}

		// do the nonce verification
		if ( ! wp_verify_nonce( $_POST['tempadmin-manual-nonce'], 'tempadmin_make_nojs' ) ) {
			// get our redirect link
			$fail   = self::get_admin_page_link( 'error', array( 'errcode' => 'NONCE_FAILED' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// get our user data as a stand-alone variable
		$data   = $_POST['tempadmin-data'];

		// check for missing email
		if ( empty( $data['email'] ) ) {
			// get our redirect link
			$fail   = self::get_admin_page_link( 'error', array( 'errcode' => 'NO_EMAIL' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// if our time isn't passed for some reason, make it one day
		$time	= ! empty( $data['time'] ) ? $data['time'] : 'day';

		// create our user
		$user   = self::create_new_user( $data['email'], $time );

		// check for error on user creation
		if ( ! empty( $user['error'] ) ) {
			// get our redirect link
			$fail   = self::get_admin_page_link( 'error', array( 'errcode' => 'NO_USER' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// get our redirect link
		$good   = self::get_admin_page_link( 'success', array( 'id' => absint( $user ) ) );

		// do the redirect
		wp_redirect( $good, 302 );
		exit();
	}

	/**
	 * our user creation function to go on ajax
	 *
	 * @return null
	 */
	public function create_user_js() {

		// set up return array for ajax responses
		$ret = array();

		// die fast without a nonce
		if( empty( $_POST['nonce'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_NONCE';
			$ret['message'] = self::get_admin_messages( 'nonce' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure we got an email
		if( empty( $_POST['email'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_EMAIL';
			$ret['message'] = self::get_admin_messages( 'email' );
			echo json_encode( $ret );
			die();
		}

		// check to see if our nonce failed
		if( false === check_ajax_referer( 'tempadmin_make_js', 'nonce', false ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = self::get_admin_messages( 'nonce' );
			echo json_encode($ret);
			die();
		}

		// if our time isn't passed for some reason, make it one day
		$time	= ! empty( $_POST['time'] ) ? $_POST['time'] : 'day';

		// create our user
		$user   = self::create_new_user( $_POST['email'], $time );

		// return error if no user was created
		if( empty( $user ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_USER';
			$ret['message'] = self::get_admin_messages( 'nouser' );
			echo json_encode( $ret );
			die();
		}

		// return success if user was created
		if( ! empty( $user ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['message'] = self::get_admin_messages( 'success' );
			echo json_encode( $ret );
			die();
		}

		// unknown error
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN_ERROR';
		$ret['message'] = self::get_admin_messages( 'default' );
		echo json_encode($ret);
		die();
	}

/// end class
}


// Instantiate our class
$TempAdminUser_Core = TempAdminUser_Core::getInstance();
