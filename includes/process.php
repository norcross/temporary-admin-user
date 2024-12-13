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

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\add_new_user_via_form' );

/**
 * Add a new user when requested on the form.
 *
 * @return void
 */
function add_new_user_via_form() {
	// preprint( $_POST, true );
}

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
	$get_expire = Helpers\create_expire_time( $duration, 'create', $now_stamp );

	// Set my user args.
	$setup_user = [
		'user_login'   => Helpers\create_username( $user_email ),
		'user_pass'    => apply_filters( Core\HOOK_PREFIX . 'random_password', wp_generate_password( 16, true, false ) ),
		'user_email'   => sanitize_email( $user_email, true ),
		'user_url'     => home_url(),
		'role'         => 'administrator',
		'description'  => sprintf( __( 'Generated from the Temporary Admin User plugin on %s and will expire %s', 'temporary-admin-user' ), gmdate( 'F jS, Y', $now_stamp ), gmdate( 'F jS, Y', $get_expire ) ),
		'first_name'   => __( 'Temporary', 'temporary-admin-user' ),
		'last_name'    => __( 'Admin', 'temporary-admin-user' ),
		'display_name' => __( 'Temporary Admin', 'temporary-admin-user' ),
		'meta_input'   => [
			Core\META_PREFIX . 'flag'     => true,
			Core\META_PREFIX . 'admin_id' => get_current_user_id(),
			Core\META_PREFIX . 'created'  => $now_stamp,
			Core\META_PREFIX . 'expires'  => $get_expire,
			Core\META_PREFIX . 'status'   => 'active',
			'show_welcome_panel'          => 0,
 			'dismissed_wp_pointers'       => 'wp330_toolbar,wp330_saving_widgets,wp340_choose_image_from_library,wp340_customize_current_theme_link,wp350_media,wp360_revisions,wp360_locks',
 		],
	];

	// Filter the args.
	$setup_user = apply_filters( Core\HOOK_PREFIX . 'new_user_args', $setup_user );

	// Bail if we have no user args.
	if ( empty( $setup_user ) ) {
		return false;
	}

	// Create the user.
	$create_id  = wp_insert_user( $setup_user ) ;

	// Bail if this didn't work.
	if ( empty( $create_id ) || is_wp_error( $create_id ) ) {
		return false;
	}

	// Allow others to help.
	do_action( Core\HOOK_PREFIX . 'after_user_created', $create_id, $setup_user );

	// Return the user ID.
	return $create_id;
}
