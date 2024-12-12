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
// add_action( 'admin_init', __NAMESPACE__ . '\run_criteria_lookup' );

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
		'user_login'  => Helpers\create_username( $user_email ),
		'user_pass'   => Helpers\create_user_password(),
		'user_email'  => sanitize_email( $user_email, true ),
		'role'        => 'administrator'
		'meta_input'  => [
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
