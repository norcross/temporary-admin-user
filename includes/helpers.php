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
use Norcross\TempAdminUser\Process\Queries as Queries;

/**
 * Get the available expiration time ranges that a user can be set to.
 *
 * @param  string  $single_range  Optional to fetch one item from the array.
 * @param  boolean $keys_only     Whether to return just the keys.
 *
 * @return array                  An array of the time data.
 */
function get_user_durations( $single_range = '', $keys_only = false ) {

	// Set my ranges. All values are in seconds.
	$range_value_args   = [
		'fifteen' => [
			'value' => ( MINUTE_IN_SECONDS * 15 ),
			'label' => __( 'Fifteen Minutes', 'temporary-admin-user' ),
		],
		'halfhour' => [
			'value' => ( MINUTE_IN_SECONDS * 30 ),
			'label' => __( 'Thirty Minutes', 'temporary-admin-user' ),
		],
		'hour' => [
			'value' => HOUR_IN_SECONDS,
			'label' => __( 'One Hour', 'temporary-admin-user' ),
		],
		'day' => [
			'value' => DAY_IN_SECONDS,
			'label' => __( 'One Day', 'temporary-admin-user' ),
		],
		'week' => [
			'value' => WEEK_IN_SECONDS,
			'label' => __( 'One Week', 'temporary-admin-user' ),
		],
		'month' => [
			'value' => MONTH_IN_SECONDS,
			'label' => __( 'One Month', 'temporary-admin-user' ),
		],
		'year' => [
			'value' => YEAR_IN_SECONDS,
			'label' => __( 'One Year', 'temporary-admin-user' ),
		],
	];

	// Allow it filtered.
	$range_value_args   = apply_filters( Core\HOOK_PREFIX . 'user_duration_args', $range_value_args );

	// Bail if no data exists.
	if ( empty( $range_value_args ) ) {
		return false;
	}

	// Return just the array keys.
	if ( false !== $keys_only ) {
		return array_keys( $range_value_args );
	}

	// Return the entire array if no key requested.
	if ( empty( $single_range ) ) {
		return $range_value_args;
	}

	// Return the single key item, or false.
	return isset( $range_value_args[ $single_range ] ) ? $range_value_args[ $single_range ] : false;
}

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

	// If we're doing Ajax or don't have our function, build it manually.
	if ( wp_doing_ajax() || ! function_exists( 'menu_page_url' ) ) {
		return add_query_arg( [ 'page' => $set_menu_root ], admin_url( 'users.php' ) );
	}

	// Use the `menu_page_url` function and return it.
	return menu_page_url( $set_menu_root, false );
}

/**
 * Redirect based on an edit action result.
 *
 * @param  string  $error    Optional error code.
 * @param  string  $result   What the result of the action was.
 * @param  boolean $success  Whether it was successful.
 *
 * @return void
 */
function redirect_admin_action_result( $error = '', $result = 'failed', $success = false ) {

	// Set up my redirect args.
	$redirect_args  = [
		'tmp-admin-users-success'         => $success,
		'tmp-admin-users-action-complete' => 'yes',
		'tmp-admin-users-action-result'   => esc_attr( $result ),
	];

	// Add the error code if we have one.
	if ( ! empty( $error ) ) {
		$redirect_args['tmp-admin-users-error-code'] = esc_attr( $error );
	}

	// Now set my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, get_admin_menu_link() );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
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
function create_expire_time( $length = 'day', $action = 'create', $current_time = 0 ) {

	// Allow my time length to be filtered based on action.
	$length = apply_filters( Core\HOOK_PREFIX . 'active_duration', $length, $action );

	// Get my data for the particular period provided.
	$data   = get_user_durations( $length );

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
	$uname  = ! empty( $uname ) ? $uname : 'random-' . mt_rand( 1000000000, 9999999999 );

	// Return it sanitized.
	return apply_filters( Core\HOOK_PREFIX . 'create_username', sanitize_user( $uname, true ), $user_email );
}

/**
 * Set up the various actions for a user on the table.
 *
 * @param  integer $user_id     The user ID.
 * @param  string  $user_email  The email of the user.
 *
 * @return array
 */
function create_user_action_args( $user_id = 0, $user_email = '' ) {

	// Create an array of actions.
	$setup_args = [
		'profile' => [
			'label' => __( 'View / Edit Profile', 'temporary-admin-user' ),
			'icon'  => 'id-alt',
			'link'  => get_edit_user_link( $user_id ),
			'blank' => true,
			'help'  => __( 'View the complete user profile.', 'temporary-admin-user' ),
		],
		'email' => [
			'label' => __( 'Email User', 'temporary-admin-user' ),
			'icon'  => 'email',
			'link'  => 'mailto:' . antispambot( $user_email ),
			'blank' => false,
			'help'  => __( 'Send an email to this user via an email client', 'temporary-admin-user' ),
		],
		'extend' => [
			'label' => __( 'Extend User', 'temporary-admin-user' ),
			'icon'  => 'clock',
			'link'  => '',
			'blank' => false,
			'help'  => __( 'Extend the time until account expiration', 'temporary-admin-user' ),
		],
		'promote' => [
			'label' => __( 'Promote User', 'temporary-admin-user' ),
			'icon'  => 'star-filled',
			'link'  => '',
			'blank' => false,
			'help'  => __( 'Restores the expired user back to admin again', 'temporary-admin-user' ),
		],
		'restrict' => [
			'label' => __( 'Restrict User', 'temporary-admin-user' ),
			'icon'  => 'lock',
			'link'  => '',
			'blank' => false,
			'help'  => __( 'Demotes the user account to the subscriber level', 'temporary-admin-user' ),
		],
		'delete' => [
			'label' => __( 'Delete User', 'temporary-admin-user' ),
			'icon'  => 'trash',
			'link'  => '',
			'blank' => false,
			'help'  => __( 'Delete the user entirely', 'temporary-admin-user' ),
		],
	];

	// Return them filtered.
	return apply_filters( Core\HOOK_PREFIX . 'user_action_args', $setup_args, $user_id, $user_email );
}

/**
 * Generate the text used on the admin notice and user table.
 *
 * @param  integer $user_id  The user ID being checked.
 *
 * @return string
 */
function generate_user_creation_text( $user_id = 0 ) {

	// Get the time it was created.
	$set_timestamp  = get_user_meta( absint( $user_id ), Core\META_PREFIX . 'created', true );

	// Set my message text.
	return sprintf( __( 'This user was created with the Temporary Admin User plugin on %s', 'temporary-admin-user' ), gmdate( get_option( 'date_format' ), $set_timestamp ) );
}

/**
 * Make sure the user is one of ours.
 *
 * @param  integer $user_id  The ID of the user.
 *
 * @return mixed
 */
function confirm_user_via_plugin( $user_id = 0 ) {

	// Check for the flag.
	$check_flag = get_user_meta( absint( $user_id ), Core\META_PREFIX . 'flag', true );

	// Go false if there is no flag.
	if ( empty( $check_flag ) ) {
		return false;
	}

	// Now get the existing status.
	$get_status = get_user_meta( absint( $user_id ), Core\META_PREFIX . 'status', true );

	// Return the status.
	return ! empty( $get_status ) ? $get_status : 'unknown';
}

/**
 * Try to grab a legit user ID based on passing the items.
 *
 * @param  integer $user_id     The potential ID of the user.
 * @param  string  $user_email  The potential email of the user.
 *
 * @return integer
 */
function confirm_user_id_via_cli( $user_id = 0, $user_email = '' ) {

	// Bail if neither.
	if ( empty( $user_id ) && empty( $user_email ) ) {
		return 0;
	}

	// Check by user ID first.
	if ( ! empty( $user_id ) ) {

		// Attempt to get the user.
		$maybe_has_user = get_user_by( 'id', absint( $user_id ) );

		// If it worked, we have a valid ID. So set it.
		if ( ! empty( $maybe_has_user ) ) {
			return $maybe_has_user->ID;
		}
	}

	// Check by email now first.
	if ( ! empty( $user_email ) ) {

		// Attempt to get the user.
		$maybe_has_user = get_user_by( 'email', sanitize_email( $user_email ) );

		// If it worked, we have a valid ID. So set it.
		if ( ! empty( $maybe_has_user ) ) {
			return $maybe_has_user->ID;
		}
	}

	// Return empty.
	return 0;
}

/**
 * Send the new user email if we want to.
 *
 * @param  integer $user_id  The user ID we just make.
 *
 * @return void
 */
function maybe_send_new_user_email( $user_id = 0 ) {

	// Pass our filter with a default.
	$maybe_send = apply_filters( Core\HOOK_PREFIX . 'disable_new_user_email', false );

	// Bail if the filter is passed.
	if ( false !== $maybe_send ) {
		return;
	}

	// Send the new user email.
	wp_send_new_user_notifications( $user_id, 'user' );
}

/**
 * Purge all the stored transients as needed.
 *
 * @return void
 */
function purge_stored_transients() {

	// Delete both transients to be safe.
	delete_transient( Core\TRANSIENT_PREFIX . 'active_users' );
	delete_transient( Core\TRANSIENT_PREFIX . 'all_users' );
}
