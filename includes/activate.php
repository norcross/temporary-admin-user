<?php
/**
 * Our activation setup.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Activate;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Cron as Cron;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Schedule our cron job assuming it isn't there already.
	if ( ! wp_next_scheduled( Core\EXPIRED_CRON ) ) {
		Cron\modify_refresh_cron( false, 'hourly' );
	}
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );
