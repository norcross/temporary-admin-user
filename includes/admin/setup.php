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
add_filter( 'manage_users_columns', __NAMESPACE__ . '\add_temp_user_badge' );
add_action( 'manage_users_custom_column', __NAMESPACE__ . '\show_temp_user_badge', 20, 3 );

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

/**
 * Add a column to indicate a temporary user.
 *
 * @param  array $columns  The existing array of columns.
 *
 * @return array $columns  The modified array of columns.
 */
function add_temp_user_badge( $columns ) {

	// Make our column with no label.
	$columns['tmp-admin-badge'] = '';

	// Return my entire array.
	return $columns;
}

/**
 * Show the small badge if they are a temp user.
 *
 * @param  string  $output       Custom column output. Default empty.
 * @param  string  $column_name  The actual column name.
 * @param  integer $user_id      The currently-listed user.
 *
 * @return mixed
 */
function show_temp_user_badge( $output, $column_name, $user_id ) {

	// Return whatever we have if it isn't our column.
	if ( 'tmp-admin-badge' !== $column_name ) {
		return $output;
	}

	// Check for the flag.
	$check_flag = get_user_meta( absint( $user_id ), Core\META_PREFIX . 'flag', true );

	// Return an empty string if no flag exists.
	if ( empty( $check_flag ) ) {
		return '';
	}

	// Get the created time.
	$timestamp  = get_user_meta( absint( $user_id ), Core\META_PREFIX . 'created', true );

	// Format the text.
	$setup_text = sprintf( __( 'Generated from the Temporary Admin User plugin on %s', 'temporary-admin-user' ), gmdate( get_option( 'date_format' ), $timestamp ) );

	// Return the icon.
	return '<span title="' . esc_attr( $setup_text ) . '" class="dashicons dashicons-admin-network tmp-admin-badge-icon"></span>';
}
