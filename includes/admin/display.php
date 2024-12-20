<?php
/**
 * Handle specific display related items.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Admin\Display;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;

/**
 * Start our engines.
 */
add_filter( 'manage_users_columns', __NAMESPACE__ . '\add_temporary_user_badge_column' );
add_action( 'manage_users_custom_column', __NAMESPACE__ . '\show_temporary_user_badge', 20, 3 );
add_filter( 'removable_query_args', __NAMESPACE__ . '\remove_temporary_admin_args' );

/**
 * Add a column to indicate a temporary user.
 *
 * @param  array $columns  The existing array of columns.
 *
 * @return array $columns  The modified array of columns.
 */
function add_temporary_user_badge_column( $columns ) {

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
function show_temporary_user_badge( $output, $column_name, $user_id ) {

	// Return whatever we have if it isn't our column.
	if ( 'tmp-admin-badge' !== $column_name ) {
		return $output;
	}

	// Check for the flag.
	$check_flag = Helpers\confirm_user_via_plugin( $user_id );

	// Return an empty string if no flag exists.
	if ( empty( $check_flag ) ) {
		return '';
	}

	// Format the text.
	$setup_text = Helpers\generate_user_creation_text( $user_id );

	// Return the icon.
	return '<span title="' . esc_attr( $setup_text ) . '" class="dashicons dashicons-hourglass tmp-admin-badge-icon"></span>';
}

/**
 * Add our custom strings to the args that get hidden.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function remove_temporary_admin_args( $args ) {

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
