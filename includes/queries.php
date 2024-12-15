<?php
/**
 * Handle the individual queries we need to do.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Queries;

// Set our aliases.
use Norcross\TempAdminUser as Core;

/**
 * Query all of the temporary users we've created.
 *
 * @param  array $query_args  The args to pass into the query.
 *
 * @return array              Our users, or an empty array.
 */
function query_current_temporary_users( $query_args = [] ) {

	// Set a default offset and per-page.
	$per_page   = ! empty( $query_args['per_page'] ) ? $query_args['per_page'] : 20;
	$set_offset = 0;

	// Calculate the offset.
	if ( ! empty( $query_args['paged'] ) && absint( $query_args['paged'] ) > 1 ) {
		$set_offset = ( absint( $query_args['paged'] ) - 1 ) * absint( $per_page );
	}

	// Define the order and orderby, with defaults.
	$do_orderby = ! empty( $query_args['orderby'] ) ? $query_args['orderby'] : 'expires';
	$do_order   = ! empty( $query_args['order'] ) ? strtoupper( $query_args['order'] ) : 'ASC';

	// Set my args.
	$setup_args = [
		'fields'     => 'all',
		'number'     => absint( $per_page ),
		'offset'     => absint( $set_offset ),
		'order'      => $do_order,
		'orderby'    => 'registered',
		'meta_query' => [
			[
				'key'   => Core\META_PREFIX . 'flag',
				'value' => true,
			]
		]
	];

	/**
	 * Set the rest of the args based on the orderby.
	 */

	// Handle email sorting, which is just one key change.
	if ( 'email' === $do_orderby ) {

		// Update the orderby.
		$setup_args['orderby'] = 'user_email';
	}

	// Handle the other 3 sorting methods.
	if ( in_array( $do_orderby, ['status', 'created', 'expires', 'updated'], true ) ) {

		// Confirm the orderby meta key.
		$order_meta = 'status' === $do_orderby ? 'meta_value' : 'meta_value_num';

		// Update the orderby.
		$setup_args['orderby'] = $order_meta;

		// And add the meta key parts.
		$setup_args['meta_key'] = Core\META_PREFIX . $do_orderby;
	}

	// Run the user query.
	$get_users  = new \WP_User_Query( $setup_args );

	// Bail if we errored out or don't have any users.
	if ( is_wp_error( $get_users ) || empty( $get_users->results ) ) {
		return [
			'total' => 0,
			'users' => [],
		];
	}

	// Return the query results.
	return [
		'total' => $get_users->total_users,
		'users' => $get_users->results,
	];
}

/**
 * Query all of the users we've created.
 *
 * @param  array $query_args  The args to pass into the query.
 *
 * @return array              Our users, or an empty array.
 */
function query_all_temporary_users( $query_args = [] ) {

	// Set my args.
	$setup_args = [
		'fields'     => 'ID',
		'number'     => 99999,
		'meta_query' => [
			[
				'key'   => Core\META_PREFIX . 'flag',
				'value' => true,
			],
		]
	];

	// Run the user query.
	$get_users  = new \WP_User_Query( $setup_args );

	// Bail if we errored out or don't have any users.
	if ( is_wp_error( $get_users ) || empty( $get_users->results ) ) {
		return [];
	}

	// Return the resulting user IDs if we have them.
	return $get_users->results;
}

/**
 * Query all of the currently active users we've created.
 *
 * @param  array $query_args  The args to pass into the query.
 *
 * @return array              Our users, or an empty array.
 */
function query_all_active_temporary_users( $query_args = [] ) {

	// Set my args.
	$setup_args = [
		'fields'     => 'ID',
		'number'     => 99999,
		'meta_query' => [
			[
				'key'   => Core\META_PREFIX . 'flag',
				'value' => true,
			],
			[
				'key'   => Core\META_PREFIX . 'status',
				'value' => 'active',
			],
		]
	];

	// Run the user query.
	$get_users  = new \WP_User_Query( $setup_args );

	// Bail if we errored out or don't have any users.
	if ( is_wp_error( $get_users ) || empty( $get_users->results ) ) {
		return [];
	}

	// Return the resulting user IDs if we have them.
	return $get_users->results;
}
