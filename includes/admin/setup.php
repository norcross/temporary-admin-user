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
add_filter( 'removable_query_args', __NAMESPACE__ . '\add_removable_args' );

/**
 * Load our admin side CSS.
 *
 * @param  string $admin_hook  The page hook we're on.
 *
 * @return void
 */
function load_admin_core_assets( $admin_hook ) {

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

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function add_removable_args( $args ) {

	// Set the array of new args.
	$setup_custom_args  = [
		'tmp-admin-users-success',
		'tmp-admin-users-action-complete',
		'tmp-admin-users-action-result',
		'tmp-admin-users-error-code',
	];

	// Include my new args and return.
	return wp_parse_args( $setup_custom_args, $args );
}
