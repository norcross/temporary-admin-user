<?php
/**
 * Our users setup.
 *
 * @package TempAdminUser
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Call our class.
 */
class TempAdminUser_Users {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init',                           array( $this, 'generate_new_user'           )           );
		add_action( 'admin_init',                           array( $this, 'modify_existing_user'        )           );
	}

	/**
	 * Generate a new user when passed the info.
	 *
	 * @return void
	 */
	public function generate_new_user() {

		// Bail if we don't have a request.
		if ( empty( $_POST['tmp-admin-new-user-request'] ) ) {
			return;
		}

		// Bail if we aren't on the page.
		if ( false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// Check nonce and bail if missing or not valid.
		if ( empty( $_POST['tmp-admin-new-user-nonce'] ) || ! wp_verify_nonce( $_POST['tmp-admin-new-user-nonce'], 'tmp-admin-new-user-nonce' ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nonce' ) );
		}

		// Do the email check.
		if ( empty( $_POST['tmp-admin-new-user-email'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'noemail' ) );
		}

		// Check if the email address exists.
		if ( email_exists( $_POST['tmp-admin-new-user-email'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'usedemail' ) );
		}

		// Do the duration exists check.
		if ( empty( $_POST['tmp-admin-new-user-duration'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'noduration' ) );
		}

		// Do the duration valid check.
		if ( ! in_array( sanitize_text_field( $_POST['tmp-admin-new-user-duration'] ), TempAdminUser_Helper::get_user_durations( 0, true ) ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'badduration' ) );
		}

		// Set our variables.
		$user_email = sanitize_text_field( $_POST['tmp-admin-new-user-email'] );
		$duration   = sanitize_text_field( $_POST['tmp-admin-new-user-duration'] );

		// preprint( $_POST, true );
		if ( false !== $user_id = self::create_new_user( $user_email, $duration ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 1, 'newuser' => 1 ) );
		}

		// And unknown error.
		tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'unknown' ) );
	}

	/**
	 * Generate a new user when passed the info.
	 *
	 * @return void
	 */
	public function modify_existing_user() {

		// Bail if we aren't on the page.
		if ( false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// Bail if we don't have a request.
		if ( empty( $_GET['tmp-single'] ) ) {
			return;
		}

		// Do the user ID check.
		if ( empty( $_GET['user-id'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'noid' ) );
		}

		// Set my ID.
		$id = absint( $_GET['user-id'] );

		// Check nonce and bail if missing or not valid.
		if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'tmp_single_user_' . $id ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nonce' ) );
		}

		// Do the action type check.
		if ( empty( $_GET['tmp-action'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'notype' ) );
		}

		// Do the action type valid check.
		if ( ! in_array( sanitize_text_field( $_GET['tmp-action'] ), array( 'promote', 'restrict', 'delete' ) ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'badtype' ) );
		}

		// Handle my different action types.
		switch ( sanitize_text_field( $_GET['tmp-action'] ) ) {

			case 'promote' :
				self::promote_existing_user( $user_id );
				break;

			case 'restrict' :
				self::restrict_existing_user( $user_id );
				break;

			case 'delete' :
				self::delete_existing_user( $user_id );
				break;

			// End all case breaks.
		}
	}

	/**
	 * Add a pre-determined amount of time to the existing user.
	 *
	 * @param  integer $user_id  The user ID we are updating.
	 *
	 * @return void
	 */
	public static function promote_existing_user( $user_id = 0 ) {

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_promote', $user_id );

		// And my setup.
		$setup  = array(
			'ID'    => absint( $user_id ),
			'role'  => 'administrator',
		);

		// Run one more filter on it.
		$setup  = apply_filters( 'tmp_admin_user_promote_args', $setup, $user_id );

		// Bail if we nix'd it in the filter.
		if ( empty( $setup ) ) {
			return false;
		}

		// Get the new ID.
		$update = wp_insert_user( $setup );

		// Bail if we failed the update.
		if ( is_wp_error( $update ) ) {
			return false;
		}

		// Handle the expires time.
		update_user_meta( $user_id, '_tmp_admin_user_updated', current_time( 'timestamp' ) );
		update_user_meta( $user_id, '_tmp_admin_user_expires', TempAdminUser_Helper::get_user_expire_time( 'day' ) );

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_promote', $user_id );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Add a pre-determined amount of time to the existing user.
	 *
	 * @param  integer $user_id  The user ID we are restricting.
	 *
	 * @return void
	 */
	public static function restrict_existing_user( $user_id = 0 ) {

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_restrict', $user_id );

		// And my setup.
		$setup  = array(
			'ID'    => absint( $user_id ),
			'role'  => 'subscriber',
		);

		// Run one more filter on it.
		$setup  = apply_filters( 'tmp_admin_user_restrict_args', $setup, $user_id );

		// Bail if we nix'd it in the filter.
		if ( empty( $setup ) ) {
			return false;
		}

		// Get the new ID.
		$update = wp_insert_user( $setup );

		// Bail if we failed the update.
		if ( is_wp_error( $update ) ) {
			return false;
		}

		// Handle the expires time.
		update_user_meta( $user_id, '_tmp_admin_user_updated', current_time( 'timestamp' ) );
		update_user_meta( $user_id, '_tmp_admin_user_expires', current_time( 'timestamp' ) );

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_restrict', $user_id );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Take te existing user and delete them.
	 *
	 * @param  integer $user_id  The user ID we are deleting.
	 *
	 * @return void
	 */
	public static function delete_existing_user( $user_id = 0 ) {

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_delete', $user_id );

		// Get the new ID.
		$delete = wp_delete_user( $user_id, get_current_user_id() );

		// Bail if we failed the update.
		if ( is_wp_error( $delete ) ) {
			return false;
		}

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_delete', $user_id );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Check the user ID against the allowed permissions to prevent temp admins from adding / deleting users and other actions on site.
	 *
	 * @param  integer $user_id  The user ID we wanna check.
	 *
	 * @return boolean           False if the use is a temp (or none at all), true otherwise.
	 */
	public static function check_user_perm( $user_id = 0 ) {

		// If user ID is missing, fetch the current logged in.
		$user_id    = ! empty( $user_id ) ? $user_id : get_current_user_id();

		// Bail without an ID.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Fetch the meta key.
		$check  = get_user_meta( $user_id, '_tmp_admin_user_flag', true );

		// Return our bool.
		return ! empty( $check ) ? false : true;
	}

	/**
	 * Create a username from the provided email address. If the username already
	 * exists, it adds some random numbers to the end to make it unique.
	 *
	 * @param  string $user_email  The user-provided email.
	 *
	 * @return string              The stripped and sanitized username.
	 */
	public static function create_username( $user_email = '' ) {

		// Break it up.
		$split  = explode( '@', $user_email );

		// Get the name portion, stripping out periods.
		$uname  = preg_replace( '/[^a-zA-Z0-9\s]/', '', $split[0] );

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
	public static function create_user_password() {

		// set my variables
		$charcount  = apply_filters( 'tmp_admin_user_pass_charcount', 16 );
		$speclchar  = apply_filters( 'tmp_admin_user_pass_specchar', true );
		$xspeclchar = apply_filters( 'tmp_admin_user_pass_xspeclchar', false );

		// return the password generated
		return wp_generate_password( absint( $charcount ), $speclchar, $xspeclchar );
	}

	/**
	 * Create the new temporary user.
	 *
	 * @param  string  $user_email  The supplied email.
	 * @param  string  $duration    The supplied expiration
	 *
	 * @return integer $user_id     The newly created user ID.
	 */
	protected static function create_new_user( $user_email = '', $duration = '' ) {

		// Make sure the user calling the action has permission to do so.
		if ( false === $access = TempAdminUser_Users::check_user_perm() ) {
			return false;
		}

		// Set my user args.
		$user_args  = array(
			'user_login'  => self::create_username( $user_email ),
			'user_pass'   => self::create_user_password(),
			'user_email'  => sanitize_email( $user_email, true ),
			'role'        => 'administrator'
		);

		// Filter the args.
		$user_args  = apply_filters( 'tmp_admin_user_new_user_args', $user_args );

		// Bail if we have no user args.
		if ( empty( $user_args ) ) {
			return false;
		}

		// Create the user.
		$user_id    = wp_insert_user( $user_args ) ;

		// Return an error message if the user was not created.
		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nocreate' ) );
		}

		// Now add our custom meta keys.
		update_user_meta( $user_id, '_tmp_admin_user_flag', true );
		update_user_meta( $user_id, '_tmp_admin_user_created', current_time( 'timestamp' ) );
		update_user_meta( $user_id, '_tmp_admin_user_expires', TempAdminUser_Helper::get_user_expire_time( $duration ) );

		// And update some basic WP related user meta.
		update_user_meta( $user_id, 'show_welcome_panel', 0 );
		update_user_meta( $user_id, 'dismissed_wp_pointers', 'wp330_toolbar,wp330_saving_widgets,wp340_choose_image_from_library,wp340_customize_current_theme_link,wp350_media,wp360_revisions,wp360_locks' );

		// Return the user ID.
		return $user_id;
	}

	/**
	 * Set up the WP_User_Query args.
	 *
	 * @return array  An array of data for use.
	 */
	public static function get_temp_users() {

		// Set my args.
		$args   = array(
			'fields'       => 'all',
			'role'         => 'administrator',
			'meta_key'     => '_tmp_admin_user_expires',
			'meta_query'   => array(
				array(
					'key'     => '_tmp_admin_user_flag',
					'value'   => true
				)
			)
		);

		// Run the user query.
		$users  = new WP_User_Query( $args );

		// Bail if we errored out or don't have any users.
		if ( is_wp_error( $users ) || empty( $users->results ) ) {
			return false;
		}

		// Return the query results.
		return $users->results;
	}

	// End our class.
}

// Call our class.
$TempAdminUser_Users = new TempAdminUser_Users();
$TempAdminUser_Users->init();
