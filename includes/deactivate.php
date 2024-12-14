<?php
/**
 * Our deactivation setup.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Deactivate;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Cron as Cron;
use Norcross\TempAdminUser\Process as Process;

/**
 * Manage the cron and restricting users when deactivating the plugin.
 *
 * @return void
 */
function deactivate() {

	// Pull in our scheduled cron and unschedule it.
	Cron\modify_refresh_cron( true, false );

	// Restrict all the users.
	Process\restrict_all_users();
}
register_deactivation_hook( Core\FILE, __NAMESPACE__ . '\deactivate' );
