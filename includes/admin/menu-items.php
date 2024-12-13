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
	$setup_page = add_users_page(
		__( 'Temporary Admin Users', 'temporary-admin-user' ),
		__( 'Temporary Admins', 'temporary-admin-user' ),
		'promote_users',
		Core\MENU_ROOT,
		__NAMESPACE__ . '\settings_page_view',
		7
	);

	// Now handle some screen options.
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_screen_options' );

	// Nothing left inside this.
}

/**
 * Add our per_page option for the table.
 *
 * @return void
 */
function add_screen_options() {

	// Define the args we want.
	$setup_args = [
		'label'   => __( 'Per Page', 'temporary-admin-user' ),
		'default' => 20,
		'option'  => 'tmp_table_per_page'
	];

	// And add it to the setup.
	add_screen_option( 'per_page', $setup_args );
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

	// Call our table class.
	$table  = new \Temporary_Admin_Users_List();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}
