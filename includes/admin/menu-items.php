<?php
/**
 * Load our menu items.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\MenuItems;

// Set our aliases.
use Norcross\TempAdminUser as Core;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\load_author_table_menu', 10 );

/**
 * Add a top-level item for getting to the user table.
 *
 * @return void
 */
function load_author_table_menu() {

	// Handle loading the initial menu.
	add_users_page(
		__( 'Temporary Users', 'temporary-admin-user' ),
		__( 'Temporary Users', 'temporary-admin-user' ),
		'promote_users',
		Core\MENU_ROOT,
		__NAMESPACE__ . '\settings_page_view',
		7
	);

	// Nothing left inside this.
}

/**
 * Handle loading our custom settings page.
 *
 * @return HTML
 */
function settings_page_view() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'promote_users' ) ) {
		wp_die( esc_html__( 'You are not permitted to view this page.', 'temporary-admin-user' ) );
	}

	echo 'Gonna have a table here soon.';
}
