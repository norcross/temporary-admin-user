<?php
/**
 * Do some random display needs on the admin side.
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
add_filter( 'manage_users_columns', __NAMESPACE__ . '\add_temp_user_badge' );
add_action( 'manage_users_custom_column', __NAMESPACE__ . '\show_temp_user_badge', 20, 3 );

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
	$check_flag = Helpers\confirm_user_via_plugin( $user_id );

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
