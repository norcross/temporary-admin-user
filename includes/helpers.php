<?php
/**
 * Contains various utility or helper functions.
 *
 * @package TempAdminUser
 */

// Call our namepsace.
namespace Norcross\TempAdminUser\Helpers;

// Set our alias items.
use Norcross\TempAdminUser as Core;

/**
 * Fetch the admin menu link on the users menu.
 *
 * @return string
 */
function get_admin_menu_link() {

	// Bail if we aren't on the admin side.
	if ( ! is_admin() ) {
		return false;
	}

	// Set the root menu page and the admin base.
	$set_menu_root  = trim( Core\MENU_ROOT );

	// If we're doing Ajax, build it manually.
	if ( wp_doing_ajax() ) {
		return add_query_arg( [ 'page' => $set_menu_root ], admin_url( 'users.php' ) );
	}

	// Use the `menu_page_url` function if we have it.
	if ( function_exists( 'menu_page_url' ) ) {

		// Return using the function.
		return menu_page_url( $set_menu_root, false );
	}

	// Build out the link if we don't have our function.
	return add_query_arg( [ 'page' => $set_menu_root ], admin_url( 'users.php' ) );
}
