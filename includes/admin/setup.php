<?php
/**
 * Handle any admin-related setup.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Admin\Setup;

// Set our aliases.
use Norcross\TempAdminUser as Core;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_admin_core_assets', 10 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_user_table_css' );

/**
 * Load our admin side CSS.
 *
 * @return void
 */
function load_admin_core_assets( $hook ) {

	// Only run this on our page.
	if ( empty( $admin_hook ) || 'users_page_' . Core\MENU_ROOT !== $admin_hook ) {
		return;
	}

	// Set my handle.
	$handle = 'temporary-admin-user';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our primary CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );
}

/**
 * Load some basic CSS for the user table.
 *
 * @return void
 */
function load_user_table_css() {

	// Set my CSS up.
	$admin_bar_css  = '
		.column-tmp-admin {
			width: 32px;
			text-align: center;
		}';

	// And add the CSS.
	wp_add_inline_style( 'common', $admin_bar_css );
}
