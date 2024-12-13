<?php
/**
 * Plugin Name: Temporary Admin User
 * Plugin URI:  https://github.com/norcross/temporary-admin-user
 * Description: Create admin users for support that expire.
 * Version:     0.0.2
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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '0.0.2' );

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
define( __NAMESPACE__ . '\OPTION_PREFIX', 'tempaa_setting_' );
define( __NAMESPACE__ . '\TRANSIENT_PREFIX', 'tempaa_tr_' );

// Set our menu root.
define( __NAMESPACE__ . '\MENU_ROOT', 'temporary-admin-user' );

// Now we handle all the various file loading.
temporary_admin_user_file_load();

/**
 * Actually load our files.
 *
 * @return void
 */
function temporary_admin_user_file_load() {

	// Load the multi-use files first.
	require_once __DIR__ . '/includes/helpers.php';
	require_once __DIR__ . '/includes/queries.php';
	require_once __DIR__ . '/includes/process.php';

	// Handle our admin items.
	require_once __DIR__ . '/includes/admin/admin-bar.php';
	require_once __DIR__ . '/includes/admin/setup.php';
	require_once __DIR__ . '/includes/admin/markup.php';
	require_once __DIR__ . '/includes/admin/menu-items.php';
	require_once __DIR__ . '/includes/admin/user-table.php';

	/*



	require_once __DIR__ . '/includes/admin/notices.php';
	require_once __DIR__ . '/includes/admin/process.php';

	// Load the triggered file loads.
	require_once __DIR__ . '/includes/deactivate.php';
	require_once __DIR__ . '/includes/uninstall.php';
	*/
}
