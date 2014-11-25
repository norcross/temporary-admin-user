<?php
/**
 * Temporary Admin User - Process Module
 *
 * Contains all the processing functions
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
class TempAdminUser_Process {

	/**
	 * this is our constructor.
	 * there are many like it, but this one is mine
	 */
	public function __construct() {
		add_action( 'init',                             array( $this, 'check_user_statuses'     ),  9999    );
		add_action( 'admin_init',                       array( $this, 'create_user_nojs'        )           );
		add_action( 'admin_init',                       array( $this, 'update_users_nojs'       )           );
		add_action( 'wp_ajax_create_user_js',           array( $this, 'create_user_js'          )           );
		add_action( 'wp_ajax_update_users_js',          array( $this, 'update_users_js'         )           );
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
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NONCE_FAILED' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// get our user data as a stand-alone variable
		$data   = $_POST['tempadmin-data'];

		// check for missing email
		if ( empty( $data['email'] ) ) {
			// get our redirect link
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NO_EMAIL' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// check for existing email
		if ( ! empty( $data['email'] ) && email_exists( $data['email'] ) ) {
			// get our redirect link
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'USED_EMAIL' ) );
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
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NO_CREATE' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// get our redirect link
		$good   = TempAdminUser_Utilities::get_admin_page_link( 'success', array( 'id' => absint( $user ), 'type' => 'created' ) );

		// do the redirect
		wp_redirect( $good, 302 );
		exit();
	}

	/**
	 * our user demote or delete function to go on non-ajax
	 *
	 * @return null
	 */
	public function update_users_nojs() {

		// make sure we have an allowed action type before moving forward
		if( empty( $_POST['tempadmin-user-action'] ) || ! in_array( $_POST['tempadmin-user-action'], array( 'demote', 'delete' ) ) ) {
			return;
		}

		// set our type as a variable
		$type   = $_POST['tempadmin-user-action'];

		// do the nonce verification
		if ( ! wp_verify_nonce( $_POST['tempadmin-manual-' . $type . '-nonce'], 'tempadmin_' . $type . '_nojs' ) ) {
			// get our redirect link
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NONCE_FAILED' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// check for empty users
		if ( empty( $_POST['users'] ) ) {
			// get our redirect link
			$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NO_USERS' ) );
			// do the redirect
			wp_redirect( $fail, 302 );
			exit();
		}

		// make sure my users are an array
		$users  = ! is_array( $_POST['users'] ) ? (array) $_POST['users'] : $_POST['users'];

		// demote users if requested
		if ( $type == 'demote' ) {

			// do the demotion
			$action = self::demote_users( $users );

			// handle our redirect for failure
			if ( $action === false ) {

				// get our redirect link
				$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NO_DEMOTE' ) );
				// do the redirect
				wp_redirect( $fail, 302 );
				exit();
			}

			// handle our redirect for success
			if ( $action === true ) {

				// get our redirect link
				$good   = TempAdminUser_Utilities::get_admin_page_link( 'success', array( 'type' => 'demoted' ) );
				// do the redirect
				wp_redirect( $good, 302 );
				exit();
			}

		}

		// delete users if requested
		if ( $type == 'delete' ) {

			// do the deletion
			$action = self::delete_users( $users );

			// handle our redirect for failure
			if ( $action === false ) {

				// get our redirect link
				$fail   = TempAdminUser_Utilities::get_admin_page_link( 'error', array( 'errcode' => 'NO_DELETE' ) );
				// do the redirect
				wp_redirect( $fail, 302 );
				exit();
			}

			// handle our redirect for success
			if ( $action === true ) {

				// get our redirect link
				$good   = TempAdminUser_Utilities::get_admin_page_link( 'success', array( 'type' => 'deleted' ) );
				// do the redirect
				wp_redirect( $good, 302 );
				exit();
			}

		}

		// and just be done
		return;
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
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nonce' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure we got an email
		if( empty( $_POST['email'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_EMAIL';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'noemail' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure our email isn't already used
		if( ! empty( $_POST['email'] ) && email_exists( $_POST['email'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'USED_EMAIL';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'usedemail' );
			echo json_encode( $ret );
			die();
		}

		// check to see if our nonce failed
		if( false === check_ajax_referer( 'tempadmin_make_js', 'nonce', false ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nonce' );
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
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nocreate' );
			echo json_encode( $ret );
			die();
		}

		// return success if user was created
		if( ! empty( $user ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['newrow']  = TempAdminUser_Layout::single_user_row( $user );
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'created' );
			echo json_encode( $ret );
			die();
		}

		// unknown error
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN_ERROR';
		$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'default' );
		echo json_encode($ret);
		die();
	}

	/**
	 * [update_users_js description]
	 * @return [type] [description]
	 */
	public function update_users_js() {

		// set up return array for ajax responses
		$ret = array();

		// die fast without a nonce
		if( empty( $_POST['nonce'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_NONCE';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nonce' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure we got a type
		if( empty( $_POST['type'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_TYPE';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'notype' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure we got a type that is allowed
		if( ! in_array( $_POST['type'], array( 'demote', 'delete' ) ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'BAD_TYPE';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'badtype' );
			echo json_encode( $ret );
			die();
		}

		// check to make sure we got users
		if( empty( $_POST['users'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_USERS';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nousers' );
			echo json_encode( $ret );
			die();
		}

		// set our type as a variable
		$type   = $_POST['type'];

		// check to see if our nonce failed
		if( false === check_ajax_referer( 'tempadmin_' . $type . '_js', 'nonce', false ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nonce' );
			echo json_encode($ret);
			die();
		}

		// make sure my users are an array
		$users  = ! is_array( $_POST['users'] ) ? (array) $_POST['users'] : $_POST['users'];

		// demote users if requested
		if ( $type == 'demote' ) {

			// do the action
			$action = self::demote_users( $users );

			// return error if demotion failed
			if( $action === false ) {
				$ret['success'] = false;
				$ret['errcode'] = 'NO_DEMOTE';
				$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nodemote' );
				echo json_encode( $ret );
				die();
			}

			// return success if users were demoted
			if( $action === true ) {
				$ret['success'] = true;
				$ret['errcode'] = null;
				$ret['remrows'] = $users;
				$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'demoted' );
				echo json_encode( $ret );
				die();
			}

		}

		// delete users if requested
		if ( $type == 'delete' ) {

			// do the action itself
			$action = self::delete_users( $users );

			// return error if deletion failed
			if( $action === false ) {
				$ret['success'] = false;
				$ret['errcode'] = 'NO_DELETE';
				$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'nodelete' );
				echo json_encode( $ret );
				die();
			}

			// return success if users were deleted
			if( $action === true ) {
				$ret['success'] = true;
				$ret['errcode'] = null;
				$ret['remrows'] = $users;
				$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'deleted' );
				echo json_encode( $ret );
				die();
			}
		}

		// unknown error
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN_ERROR';
		$ret['message'] = TempAdminUser_Utilities::get_admin_messages( 'default' );
		echo json_encode($ret);
		die();
	}

	/**
	 * [check_user_statuses description]
	 * @return [type] [description]
	 */
	public function check_user_statuses() {

		// check for transient, run every hour (for now)
		if( false === get_transient( 'tempadmin_status_check' )  ) {

			// get my users
			$users  = TempAdminUser_Utilities::get_temp_users();

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
	 * create the new temporary user
	 *
	 * @param  string  $email     the user-submitted email
	 * @param  string  $time      the user-submitted expiration
	 *
	 * @return integer $user      the newly created user ID
	 */
	protected static function create_new_user( $email = '', $time = '' ) {

		// first get our password, since we use it in multiple places
		$password   = TempAdminUser_Utilities::generate_password();

		// set my user args
		$user_args  = array(
			'user_login'  => TempAdminUser_Utilities::create_username( $email ),
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
		update_user_meta( $user_id, '_tempadmin_expire', TempAdminUser_Utilities::get_user_expire_time( $time ) );

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
	 * update the user to a specified role
	 *
	 * @param  integer $user_id   the user ID being modified
	 * @param  string  $role      the status being changed to
	 *
	 * @return bool               the result of the update
	 */
	protected static function reset_user_status( $user_id = 0, $role = 'subscriber' ) {

		// set a quick setup string
		$update = wp_update_user( array( 'ID' => absint( $user_id ), 'role' => $role ) );

		// return false if we got an error
		if ( is_wp_error( $update ) ) {
			return false;
		}

		// update the timestamp if we are bumping down to subscriber
		if ( $role == 'subscriber' ) {
			update_user_meta( $user_id, '_tempadmin_expire', time() );
		}

		// send back true
		return true;
	}


	/**
	 * [demote_users description]
	 * @param  array  $users [description]
	 * @return [type]        [description]
	 */
	protected static function demote_users( $users = array() ) {

		// set a flag first
		$result = true;

		// loop them
		foreach( $users as $user_id ) {

			// run the reset
			$action = self::reset_user_status( $user_id );

			// check for failure
			if ( $action === false ) {

				// set my result flag false
				$result = false;

				// and break
				break;
			}
		}

		// send back the result
		return $result;
	}

	/**
	 * [delete_users description]
	 * @param  array  $users [description]
	 * @return [type]        [description]
	 */
	protected static function delete_users( $users = array() ) {

		// set a flag first
		$result = true;

		// loop them
		foreach( $users as $user_id ) {

			// run the reset
			$action = self::remove_user( $user_id );

			// check for failure
			if ( $action === false ) {

				// set my result flag false
				$result = false;

				// and break
				break;
			}
		}

		// send back the result
		return $result;
	}

	/**
	 * delete the user
	 *
	 * @param  integer $user_id   the user ID being deleted
	 *
	 * @return bool               the result of the update
	 */
	protected static function remove_user( $user_id = 0 ) {

		// set a quick setup string
		$delete = wp_delete_user( absint( $user_id ), get_current_user_id() );

		// return true / false bool if we got an error
		return is_wp_error( $delete ) ? false : true;
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
		$sitedata   = TempAdminUser_Utilities::get_site_data();

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
		$content    = TempAdminUser_Layout::generate_email_content( $user, $password, $sitedata );

		// and send the email
		wp_mail( sanitize_email( $user->user_email ), $subject, $content, $headers );

		// reset the email content type (if originally set )
		if ( $email_type == 'html' ) {
			remove_filter( 'wp_mail_content_type', array( __class__, 'set_html_content_type' ) );
		}

		// and return
		return;
	}

/// end class
}


// Instantiate our class
new TempAdminUser_Process();
