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
add_action( 'admin_menu', __NAMESPACE__ . '\load_author_table_menu', 10 );
add_action( 'admin_menu', __NAMESPACE__ . '\modify_temp_user_menu', 999 );

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

	// Now handle some screen options and help tab.
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_screen_options' );
	add_action( "load-$setup_page", __NAMESPACE__ . '\add_help_tab_options' );

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
 * Add our help tab option for the table.
 *
 * @return void
 */
function add_help_tab_options() {

	// Grab the current screen object
	$screen = get_current_screen();

	// Add the initial help tab.
	$screen->add_help_tab( [
		'id'      => 'tmp-htb-overview',
		'title'   => __( 'Overview', 'temporary-admin-user' ),
		'content' => AdminMarkup\render_overview_help_tab( false ),
	] );

	// Add the advanced help tab.
	$screen->add_help_tab( [
		'id'      => 'tmp-htb-wp-cli',
		'title'   => __( 'WP-CLI Usage', 'temporary-admin-user' ),
		'content' => AdminMarkup\render_cli_help_tab( false ),
	] );
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

/**
 * Remove some of the admin menu options for temp users.
 *
 * @return void
 */
function modify_temp_user_menu() {

	// See if this is a temp user.
	$maybe_temp = Helpers\confirm_user_via_plugin( get_current_user_id() );

	// If this is false, no changes needed.
	if ( false === $maybe_temp ) {
		return;
	}

	// Call the global we need.
	global $submenu;

	// Bail without the users.
	if ( ! isset( $submenu['users.php'] ) ) {
		return;
	}

	// Now loop through and start removing some menu items.
	foreach ( $submenu['users.php'] as $index => $menu_item ) {

		// Remove anything tied to the "promote users" permission.
		if ( ! empty( $menu_item[1] ) && 'promote_users' === sanitize_text_field( $menu_item[1] ) ) {
			unset( $submenu['users.php'][ $index ] );
		}

		// Remove the "Add New" option.
		if ( ! empty( $menu_item[2] ) && 'user-new.php' === sanitize_text_field( $menu_item[2] ) ) {
			unset( $submenu['users.php'][ $index ] );
		}

		// We aren't looking for anything else yet.
	}

	// And be done.
	return;
}
