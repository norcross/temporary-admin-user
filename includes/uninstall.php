<?php
/**
 * Our uninstall call.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Uninstall;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Cron as Cron;
use Norcross\TempAdminUser\Process as Process;

/**
 * Manage the cron and deleting users when deactivating the plugin.
 *
 * @return void
 */
function uninstall() {

	// Pull in our scheduled cron and unschedule it.
	Cron\modify_refresh_cron( true, false );

	// Delete all the users.
	Process\delete_all_users();
}
register_uninstall_hook( Core\FILE, __NAMESPACE__ . '\uninstall' );
