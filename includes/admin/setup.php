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
use Norcross\TempAdminUser\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_admin_core_assets', 10 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_user_table_css' );
add_filter( 'user_has_cap', __NAMESPACE__ . '\modify_temporary_user_permissions', 20, 4 );

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
		.column-tmp-admin-badge {
			width: 32px;
			text-align: center;
		}
		span.tmp-admin-badge-icon {
			cursor: help;
		}
		';

	// And add the CSS.
	wp_add_inline_style( 'common', $admin_bar_css );
}

/**
 * Filter the capabilities for the temporary users.
 *
 * @param  array   $allcaps  All the capabilities of the user.
 * @param  array   $cap      [0] Required capability.
 * @param  array   $args     [0] Requested capability.
 *                           [1] User ID.
 *                           [2] Associated object ID.
 *
 * @param  WP_User $user     The user object being loaded.
 *
 * @return array             The potentially modified array of permissions.
 */
function modify_temporary_user_permissions( $allcaps, $cap, $args, $user ) {

	// Allow users to edit other users via this filter.
	if ( false !== apply_filters( Core\HOOK_PREFIX . 'enable_user_management', false ) ) {
		return $allcaps;
	}

	// Check for the flag.
	$check_flag = Helpers\confirm_user_via_plugin( $user->ID );

	// Return the current array if this isn't one of our users.
	if ( empty( $check_flag ) ) {
		return $allcaps;
	}

	// Remove these specific permissions.
	$allcaps['edit_users']    = 0;
	$allcaps['remove_users']  = 0;
	$allcaps['promote_users'] = 0;
	$allcaps['delete_users']  = 0;
	$allcaps['create_users']  = 0;

	// Return the modified array.
	return apply_filters( Core\HOOK_PREFIX . 'allowed_user_permissions', $allcaps );
}
