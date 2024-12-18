<?php
/**
 * Handle the processing involves.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Process;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Queries as Queries;

/**
 * Create the new temporary user.
 *
 * @param  string  $user_email  The supplied email.
 * @param  string  $duration    The supplied expiration
 *
 * @return integer $user_id     The newly created user ID.
 */
function create_new_user( $user_email = '', $duration = '' ) {

	// Set a stamp for now.
	$now_stamp  = current_datetime()->format('U');

	// Get the exipration.
	$set_expire = Helpers\create_expire_time( $duration, 'create', $now_stamp );

	// Set my user args.
	$setup_user = [
		'user_login'   => Helpers\create_username( $user_email ),
		'user_pass'    => apply_filters( Core\HOOK_PREFIX . 'random_password', wp_generate_password( 16, true, false ) ),
		'user_email'   => sanitize_email( $user_email, true ),
		'user_url'     => home_url(),
		'role'         => 'administrator',
		'description'  => sprintf( __( 'Generated from the Temporary Admin User plugin on %s', 'temporary-admin-user' ), gmdate( get_option( 'date_format' ), $now_stamp ) ),
		'first_name'   => __( 'Temporary', 'temporary-admin-user' ),
		'last_name'    => __( 'Admin', 'temporary-admin-user' ),
		'display_name' => __( 'Temporary Admin', 'temporary-admin-user' ),
		'meta_input'   => [
			Core\META_PREFIX . 'flag'     => true,
			Core\META_PREFIX . 'admin_id' => get_current_user_id(),
			Core\META_PREFIX . 'created'  => $now_stamp,
			Core\META_PREFIX . 'expires'  => $set_expire,
			Core\META_PREFIX . 'status'   => 'active',
			'show_welcome_panel'          => 0,
 			'dismissed_wp_pointers'       => 'wp330_toolbar,wp330_saving_widgets,wp340_choose_image_from_library,wp340_customize_current_theme_link,wp350_media,wp360_revisions,wp360_locks',
 		],
	];

	// Create the user.
	$create_id  = wp_insert_user( $setup_user ) ;

	// Bail if this didn't work.
	if ( empty( $create_id ) || is_wp_error( $create_id ) ) {
		return false;
	}

	// Send the new user email.
	wp_send_new_user_notifications( $create_id, 'user' );

	// Allow others to help.
	do_action( Core\HOOK_PREFIX . 'after_user_created', $create_id, $setup_user );

	// Return the user ID.
	return $create_id;
}

/**
 * Add a pre-determined amount of time to the existing user.
 *
 * @param  integer $user_id   The user ID we want to restrict.
 * @param  string  $duration  The requested duration length.
 *
 * @return void
 */
function extend_existing_user( $user_id = 0, $duration = 'day' ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'before_user_extend', $user_id );

	// Get my data for the particular period provided.
	$range_data = Helpers\get_user_durations( $duration );
	$bonus_time = ! empty( $range_data['value'] ) ? $range_data['value'] : DAY_IN_SECONDS;

	// Set a stamp for now.
	$now_stamp  = current_datetime()->format('U');

	// Get the exipration.
	$get_expire = get_user_meta( $user_id, Core\META_PREFIX . 'expires', true );
	$set_expire = ! empty( $get_expire ) ? $get_expire : $now_stamp;

	// Now bump the time up.
	$new_expore = absint( $set_expire ) + absint( $bonus_time );

	// Handle the updated times.
	update_user_meta( $user_id, Core\META_PREFIX . 'updated', $now_stamp );
	update_user_meta( $user_id, Core\META_PREFIX . 'expires', $new_expore );

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'after_user_extend', $user_id );

	// And return true, so we know to report back.
	return true;
}

/**
 * Add a pre-determined amount of time to the existing user.
 *
 * @param  integer $user_id   The user ID we want to restrict.
 * @param  string  $duration  The requested duration length.
 *
 * @return void
 */
function promote_existing_user( $user_id = 0, $duration = 'day' ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'before_user_promote', $user_id );

	// Fetch the WP_User object of our user.
	$get_user_obj   = new \WP_User( absint( $user_id ) );

	// Replace the current role with 'administrator' role.
	$get_user_obj->set_role( 'administrator' );

	// Set a stamp for now.
	$now_stamp  = current_datetime()->format('U');

	// Get the exipration.
	$set_expire = Helpers\create_expire_time( $duration, 'update', $now_stamp );

	// Handle the updated times.
	update_user_meta( $user_id, Core\META_PREFIX . 'updated', $now_stamp );
	update_user_meta( $user_id, Core\META_PREFIX . 'expires', $set_expire );
	update_user_meta( $user_id, Core\META_PREFIX . 'status', 'active' );

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'after_user_promote', $user_id );

	// And return true, so we know to report back.
	return true;
}

/**
 * Take the existing user and restrict them.
 *
 * @param  integer $user_id  The user ID we want to restrict.
 *
 * @return boolean
 */
function restrict_existing_user( $user_id = 0 ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'before_user_restrict', $user_id );

	// Fetch the WP_User object of our user.
	$get_user_obj   = new \WP_User( absint( $user_id ) );

	// Replace the current role with 'subscriber' role.
	$get_user_obj->set_role( 'subscriber' );

	// Define a few timestamps.
	$now_stamp  = current_datetime()->format('U');
	$exp_stamp  = absint( $now_stamp ) - MINUTE_IN_SECONDS;

	// Handle the updated times.
	update_user_meta( $user_id, Core\META_PREFIX . 'updated', $now_stamp );
	update_user_meta( $user_id, Core\META_PREFIX . 'expires', $exp_stamp );
	update_user_meta( $user_id, Core\META_PREFIX . 'status', 'inactive' );

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'after_user_restrict', $user_id );

	// And return true, so we know to report back.
	return true;
}

/**
 * Take all existing users and restrict them.
 *
 * @return boolean
 */
function restrict_all_users() {

	// Get all the users we have.
	$get_all_users  = Queries\query_all_temporary_users();

	// Loop the user IDs.
	foreach ( $get_all_users as $user_id ) {

		// Attempt to restrict it.
		$maybe_restrict = restrict_existing_user( $user_id );

		// True means it was OK.
		if ( false !== $maybe_restrict ) {
			continue;
		}

		// Bail if something went wrong.
		return false;
	}

	// And return true, so we know to report back.
	return true;
}

/**
 * Take the existing user and delete them.
 *
 * @param  integer $user_id  The user ID we want to delete.
 *
 * @return boolean
 */
function delete_existing_user( $user_id = 0 ) {

	// Bail without a user ID.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'before_user_delete', $user_id );

	// Attempt to delete the user.
	$maybe_delete_user  = wp_delete_user( $user_id, get_current_user_id() );

	// Bail if we failed the update.
	if ( empty( $maybe_delete_user ) || is_wp_error( $maybe_delete_user ) ) {
		return false; // @@todo needs some error checking
	}

	// Allow other things to hook into this process.
	do_action( Core\HOOK_PREFIX . 'after_user_delete', $user_id );

	// And return true, so we know to report back.
	return true;
}

/**
 * Take all existing users and delete them.
 *
 * @return boolean
 */
function delete_all_users() {

	// Get all the users we have.
	$get_all_users  = Queries\query_all_temporary_users();

	// Loop the user IDs.
	foreach ( $get_all_users as $user_id ) {

		// Attempt to delete it.
		$maybe_delete   = delete_existing_user( $user_id );

		// True means it was OK.
		if ( false !== $maybe_delete ) {
			continue;
		}

		// Bail if something went wrong.
		return false;
	}

	// And return true, so we know to report back.
	return true;
}
