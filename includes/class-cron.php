<?php
/**
 * Our various WP-Cron stuff setup.
 *
 * @package TempAdminUser
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Call our class.
 */
class TempAdminUser_Cron {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'tmp_admin_user_check_expired',         array( $this, 'check_expired_users'         )           );
	}

	/**
	 * Run the check for expired users.
	 *
	 * @return void
	 */
	public function check_expired_users() {

		// Bail if we have no users.
		if ( false === $users = TempAdminUser_Users::get_temp_users() ) {
			return;
		}

		// Loop the users and do the status check.
		foreach ( $users as $user ) {

			// Check our status, skip the active ones.
			if ( 'active' === $status = TempAdminUser_Users::check_user_status( $user->ID ) ) {
				continue;
			}

			// Run the restrict.
			$restrict  = TempAdminUser_Users::restrict_existing_user( $user );

			// @@todo add some error checking or flag if it fails?
		}
	}

	// End our class.
}

// Call our class.
$TempAdminUser_Cron = new TempAdminUser_Cron();
$TempAdminUser_Cron->init();
