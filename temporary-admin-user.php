<?php
/**
 * Plugin Name: Temporary Admin User
 * Plugin URI:  https://github.com/norcross/temporary-admin-user
 * Description: Create admin users for support that expire.
 * Version:     1.0.0
 * Author:      Andrew Norcross
 * Author URI:  https://andrewnorcross.com
 * Text Domain: temporary-admin-user
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '1.0.0' );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Set our assets URL constant.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );

// Set our includes and template path constants.
define( __NAMESPACE__ . '\INCLUDES_PATH', __DIR__ . '/includes' );

// Set the various prefixes for our actions and filters.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'temporary_admin_user_' );
define( __NAMESPACE__ . '\META_PREFIX', '_tmp_admin_user_' );
define( __NAMESPACE__ . '\NONCE_PREFIX', 'tempaa_nonce_' );
define( __NAMESPACE__ . '\TRANSIENT_PREFIX', 'tempaa_tr_' );

// Set our menu root.
define( __NAMESPACE__ . '\MENU_ROOT', 'temporary-admin-user' );

// Set our cron function name constants.
define( __NAMESPACE__ . '\EXPIRED_CRON', 'tmp_admin_user_expire_check' );

// Load the multi-use files first.
require_once __DIR__ . '/includes/helpers.php';

// Handle our processing files.
require_once __DIR__ . '/includes/process/cron.php';
require_once __DIR__ . '/includes/process/queries.php';
require_once __DIR__ . '/includes/process/user-changes.php';

// Handle our admin items.
require_once __DIR__ . '/includes/admin/admin-bar.php';
require_once __DIR__ . '/includes/admin/display.php';
require_once __DIR__ . '/includes/admin/setup.php';
require_once __DIR__ . '/includes/admin/markup.php';
require_once __DIR__ . '/includes/admin/menu-items.php';
require_once __DIR__ . '/includes/admin/notices.php';
require_once __DIR__ . '/includes/admin/triggers.php';
require_once __DIR__ . '/includes/admin/user-table.php';

// Check that we have the CLI constant available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	// Load our commands file.
	require_once dirname( __FILE__ ) . '/includes/cli-commands.php';

	// And add our command.
	WP_CLI::add_command( 'tmp-admin-user', TempAdminUserCommands::class );
}

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';
