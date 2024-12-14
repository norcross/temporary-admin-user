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

/**
 * Delete various options when deactivating the plugin.
 *
 * @return void
 */
function deactivate() {

	// Pull in our scheduled cron and unschedule it.
	Cron\modify_refresh_cron( true, false );
}
register_deactivation_hook( Core\FILE, __NAMESPACE__ . '\deactivate' );
