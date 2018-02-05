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
		add_filter( 'manage_users_columns',                 array( $this, 'add_temp_user_badge'         )           );
		add_action( 'manage_users_custom_column',           array( $this, 'show_temp_user_badge'        ),  10, 3   );
		add_action( 'admin_notices',                        array( $this, 'display_temp_user_banner'    )           );
		add_action( 'admin_init',                           array( $this, 'generate_new_user'           )           );
		add_action( 'admin_init',                           array( $this, 'modify_existing_user'        )           );
	}

	/**
	 * Add a column to indicate a temporary user.
	 *
	 * @param  array $columns  The existing array of columns.
	 *
	 * @return array $columns  The modified array of columns.
	 */
	public function add_temp_user_badge( $columns ) {

		// Make our column with no label.
		$columns['tmp-admin'] = '';

		// Return my entire array.
		return $columns;
	}

	/**
	 * Show the small badge if they are a temp user.
	 *
	 * @param  string  $output       Custom column output. Default empty.
	 * @param  string  $column_name  The actual column name.
	 * @param  integer $user_id      The currently-listed user.
	 *
	 * @return mixed
	 */
	public function show_temp_user_badge( $output, $column_name, $user_id ) {

		// Return whatever we have if it isn't our column.
		if ( 'tmp-admin' !== $column_name ) {
			return $output;
		}

		// Check for the flag.
		$check  = get_user_meta( absint( $user_id ), '_tmp_admin_user_flag', true );

		// Return the icon, or nothing.
		return ! empty( $check ) ? '<span title="' . __( 'This user was created with the Temporary Admin User plugin', 'temporary-admin-user' ) . '" class="dashicons dashicons-admin-network"></span>' : $output;
	}

	/**
	 * Display the banner showing the user was made in the plugin.
	 *
	 * @return HTML
	 */
	public function display_temp_user_banner() {

		// Call the global $pagenow variable.
		global $pagenow;

		// Bail if this isn't a user page at all.
		if ( empty( $pagenow ) || 'user-edit.php' !== esc_attr( $pagenow ) || empty( $_GET['user_id'] ) ) {
			return;
		}

		// Check for the flag.
		$check  = get_user_meta( absint( $_GET['user_id'] ), '_tmp_admin_user_flag', true );

		// Do the notice banner if we have the flag.
		if ( ! empty( $check ) ) {

			// Get my created flag.
			$create = get_user_meta( absint( $_GET['user_id'] ), '_tmp_admin_user_created', true );

			// Set my message text.
			$msgtxt = sprintf( __( 'This user was created with the Temporary Admin User plugin on %s', 'temporary-admin-user' ), date( 'F jS Y @ g:i a', $create ) );

			// And handle the notice.
			echo '<div class="notice notice-info is-dismissible tmp-admin-user-message">';
				echo '<p>' . wp_kses_post( $msgtxt ) . '</p>';
			echo '</div>';
		}

		// And be done.
		return;
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

		if ( false !== $user_id = self::create_new_user( $user_email, $duration ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 1, 'action' => 'create', 'newuser' => 1 ) );
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

		// Check the user ID itself.
		if ( false === $check = TempAdminUser_Helper::user_id_exists( $id ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'baduser' ) );
		}

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

		// Bail if no userdata exists.
		if ( false === $user = get_userdata( $id ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nouser' ) );
		}

		// Handle my different action types.
		switch ( sanitize_text_field( $_GET['tmp-action'] ) ) {

			case 'promote' :
				self::promote_existing_user( $user );
				break;

			case 'restrict' :
				self::restrict_existing_user( $user );
				break;

			case 'delete' :
				self::delete_existing_user( $user );
				break;

			// End all case breaks.
		}

		// And our success.
		tmp_admin_user()->admin_page_redirect( array( 'success' => 1, 'action' => sanitize_text_field( $_GET['tmp-action'] ) ) );
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
		update_user_meta( $user_id, '_tmp_admin_user_admin_id', get_current_user_id() );
		update_user_meta( $user_id, '_tmp_admin_user_created', current_time( 'timestamp' ) );
		update_user_meta( $user_id, '_tmp_admin_user_expires', TempAdminUser_Helper::get_user_expire_time( $duration, $user_id, 'create' ) );

		// And update some basic WP related user meta.
		update_user_meta( $user_id, 'show_welcome_panel', 0 );
		update_user_meta( $user_id, 'dismissed_wp_pointers', 'wp330_toolbar,wp330_saving_widgets,wp340_choose_image_from_library,wp340_customize_current_theme_link,wp350_media,wp360_revisions,wp360_locks' );

		// Return the user ID.
		return $user_id;
	}

	/**
	 * Add a pre-determined amount of time to the existing user.
	 *
	 * @param  object  $user      The WP_User object we are updating.
	 * @param  boolean $redirect  Whether to do the actual redirect or not.
	 *
	 * @return void
	 */
	public static function promote_existing_user( $user, $redirect = true ) {

		// Quick check to make sure we got the whole object and not just an ID.
		$user   = ! is_object( $user ) ? get_userdata( $user ) : $user;

		// Double check the user was passed.
		if ( empty( $user ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nouser' ), $redirect );
		}

		// Check the user ID itself.
		if ( false === $check = TempAdminUser_Helper::user_id_exists( $user->ID ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'baduser' ), $redirect );
		}

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_promote', $user, $redirect );

		// If we are already an admin, don't bother updating the user.
		if ( ! in_array( 'administrator', $user->roles ) ) {

			// Grab the user args.
			if ( false === $setup = self::get_user_update_args( $user, 'administrator', 'promote' ) ) {
				return false; // @@todo needs some error checking
			}

			// Get the new ID.
			$update = wp_insert_user( $setup );

			// Bail if we failed the update.
			if ( empty( $update ) || is_wp_error( $update ) ) {
				return false; // @@todo needs some error checking
			}
		}

		// Handle the expires time.
		update_user_meta( $user->ID, '_tmp_admin_user_updated', current_time( 'timestamp' ) );
		update_user_meta( $user->ID, '_tmp_admin_user_expires', TempAdminUser_Helper::get_user_expire_time( 'day', 'promote', $user->ID ) );

		// Delete our restricted flag if it happens to exist.
		delete_user_meta( $user->ID, '_tmp_admin_user_is_restricted' );

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_promote', $user, $redirect );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Take the user and set them to the restricted status.
	 *
	 * @param  object  $user      The WP_User object we are updating.
	 * @param  boolean $redirect  Whether to do the actual redirect or not.
	 *
	 * @return void
	 */
	public static function restrict_existing_user( $user, $redirect = true ) {

		// Quick check to make sure we got the whole object and not just an ID.
		$user   = ! is_object( $user ) ? get_userdata( $user ) : $user;

		// Double check the user was passed.
		if ( empty( $user ) || empty( $user->ID ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nouser' ), $redirect );
		}

		// Check the user ID itself.
		if ( false === $check = TempAdminUser_Helper::user_id_exists( $user->ID ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'baduser' ), $redirect );
		}

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_restrict', $user, $redirect );

		// If we are already a subscriber, don't bother updating the user.
		if ( ! in_array( 'subscriber', $user->roles ) ) {

			// Grab the user args.
			if ( false === $setup = self::get_user_update_args( $user, 'subscriber', 'restrict' ) ) {
				return false; // @@todo needs some error checking
			}

			// Get the new ID.
			$update = wp_insert_user( $setup );

			// Bail if we failed the update.
			if ( empty( $update ) || is_wp_error( $update ) ) {
				return false; // @@todo needs some error checking
			}
		}

		// Set my expire time one hour back.
		$expire = current_time( 'timestamp' ) - HOUR_IN_SECONDS;

		// Handle the expires time.
		update_user_meta( $user->ID, '_tmp_admin_user_updated', current_time( 'timestamp' ) );
		update_user_meta( $user->ID, '_tmp_admin_user_expires', absint( $expire ) );
		update_user_meta( $user->ID, '_tmp_admin_user_is_restricted', true );

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_restrict', $user, $redirect );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Take te existing user and delete them.
	 *
	 * @param  object  $user      The WP_User object we are updating.
	 * @param  boolean $redirect  Whether to do the actual redirect or not.
	 *
	 * @return void
	 */
	public static function delete_existing_user( $user, $redirect = true ) {

		// Quick check to make sure we got the whole object and not just an ID.
		$user   = ! is_object( $user ) ? get_userdata( $user ) : $user;

		// Double check the user was passed.
		if ( empty( $user ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nouser' ), $redirect );
		}

		// Check the user ID itself.
		if ( false === $check = TempAdminUser_Helper::user_id_exists( $user->ID ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'baduser' ), $redirect );
		}

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_before_user_delete', $user, $redirect );

		// Get the new ID.
		$delete = wp_delete_user( $user->ID, get_current_user_id() );

		// Bail if we failed the update.
		if ( is_wp_error( $delete ) ) {
			return false; // @@todo needs some error checking
		}

		// Allow other things to hook into this process.
		do_action( 'tmp_admin_user_after_user_delete', $user, $redirect );

		// And return true, so we know to report back.
		return true;
	}

	/**
	 * Run one of the function on every temporary user we've created.
	 *
	 * @return void
	 */
	public static function update_all_users( $action = '' ) {

		// Do the action type valid check.
		if ( empty( $action ) || ! in_array( sanitize_text_field( $action ), array( 'promote', 'restrict', 'delete' ) ) ) {
			return false; // @@todo needs some error checking
		}

		// Fetch our users and bail if we have none.
		if ( false === $users = self::get_temp_users() ) {
			return false; // @@todo needs some error checking
		}

		// Loop my user objects and set them all.
		foreach ( $users as $user ) {

			// Check the user ID itself.
			if ( false === $check = TempAdminUser_Helper::user_id_exists( $user->ID ) ) {
				continue; // @@todo needs some error checking
			}

			// Handle my different action types.
			switch ( sanitize_text_field( $action ) ) {

				case 'promote' :
					self::promote_existing_user( $user );
					break;

				case 'restrict' :
					self::restrict_existing_user( $user );
					break;

				case 'delete' :
					self::delete_existing_user( $user );
					break;

				// End all case breaks.
			}
		}

		// And be done.
		return;
	}

	/**
	 * Set the args for the user modification.
	 *
	 * @param  object $user    The whole WP_User object.
	 * @param  string $role    What the role we are updating is.
	 * @param  string $action  What action is being taken on the user.
	 *
	 * @return array
	 */
	public static function get_user_update_args( $user, $role = '', $action = '' ) {

		// Create the user args for updating.
		$setup  = array(
			'ID'             => absint( $user->ID ),
			'role'           => $role,
			'user_login'     => $user->user_login,
			'user_email'     => $user->user_email,
			'user_nicename'  => $user->user_nicename,
			'user_url'       => $user->user_url,
			'first_name'     => $user->first_name,
			'last_name'      => $user->last_name,
			'display_name'   => $user->display_name,
			'nickname'       => $user->nickname,

		);

		// Run one more filter on it.
		return apply_filters( 'tmp_admin_user_modify_args', $setup, $user, $action );
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
	 * Check the user ID to check their status.
	 *
	 * @param  integer $user_id  The user ID we wanna check.
	 *
	 * @return string           True if the user is expired.
	 */
	public static function check_user_status( $user_id = 0 ) {

		// Bail without an ID.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Fetch the meta keys.
		$restricted = get_user_meta( $user_id, '_tmp_admin_user_is_restricted', true );
		$timestamp  = get_user_meta( $user_id, '_tmp_admin_user_expires', true );

		// If we have the restricted flag, return that.
		if ( ! empty( $restricted ) ) {
			return 'restricted';
		}

		// Now do the timestamp comparison.
		return current_time( 'timestamp' ) < absint( $timestamp ) ? 'active' : 'restricted';
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
	public static function create_user_password() {

		// Set my variables.
		$charcount  = apply_filters( 'tmp_admin_user_pass_charcount', 16 );
		$speclchar  = apply_filters( 'tmp_admin_user_pass_specchar', true );
		$xspeclchar = apply_filters( 'tmp_admin_user_pass_xspeclchar', false );

		// Return the password generated.
		return wp_generate_password( absint( $charcount ), $speclchar, $xspeclchar );
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
