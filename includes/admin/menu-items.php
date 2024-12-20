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
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Admin\Markup as AdminMarkup;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\load_temporary_users_table_menu', 10 );

/**
 * Add a submenu item to the primary users menu.
 *
 * @return void
 */
function load_temporary_users_table_menu() {

	// Handle loading the initial menu.
	$setup_page = add_users_page(
		__( 'Temporary Admin Users', 'temporary-admin-user' ),
		__( 'Temporary Admins', 'temporary-admin-user' ),
		'create_users',
		Core\MENU_ROOT,
		__NAMESPACE__ . '\settings_page_view',
		7
	);

	// Now handle some screen options and help tab.
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_screen_options' );
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_help_tab_options' );
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
 * Add our help tab options for the table.
 *
 * @return void
 */
function add_help_tab_options() {

	// Grab the current screen object
	$screen = get_current_screen();

	// Add the overview tab explaining the icons.
	$screen->add_help_tab( [
		'id'      => 'tmp-htb-overview',
		'title'   => __( 'Overview', 'temporary-admin-user' ),
		'content' => AdminMarkup\render_overview_help_tab( false ),
	] );

	// Add the list of available CLI commands.
	$screen->add_help_tab( [
		'id'      => 'tmp-htb-wp-cli',
		'title'   => __( 'WP-CLI Usage', 'temporary-admin-user' ),
		'content' => AdminMarkup\render_cli_help_tab( false ),
	] );
}

/**
 * Handle loading our custom settings page, which includes the table.
 *
 * @return HTML
 */
function settings_page_view() {

	// Bail if we shouldn't be here.
	if ( ! current_user_can( 'create_users' ) ) {
		wp_die( esc_html__( 'You are not permitted to view this page.', 'temporary-admin-user' ) );
	}

	// Call our table class.
	$table  = new \Temporary_Admin_Users_List();

	// And output the table.
	$table->prepare_items();

	// The actual table itself.
	$table->display();
}
