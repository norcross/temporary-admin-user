<?php
/**
 * Handle the individual queries we need to do.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Process\Queries;

// Set our aliases.
use Norcross\TempAdminUser as Core;

/**
 * Handle the query for the admin side user table.
 *
 * @param  array $query_args  The args to pass into the query.
 *
 * @return array              Our users, or an empty array.
 */
function query_user_table_data( $query_args = [] ) {

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
 * @return array  Our users, or an empty array.
 */
function query_all_temporary_users() {

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'all_users';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

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

		// Set our transient with our data for a minute.
		set_transient( $ky, $get_users->results, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $get_users->results;
	}

	// And return the resulting.
	return $cached_dataset;
}

/**
 * Query all of the currently active users we've created.
 *
 * @return array  Our users, or an empty array.
 */
function query_all_active_temporary_users() {

	// Set the key to use in our transient.
	$ky = Core\TRANSIENT_PREFIX . 'active_users';

	// If we don't want the cache'd version, delete the transient first.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $ky );
	}

	// Attempt to get the data from the cache.
	$cached_dataset = get_transient( $ky );

	// If we have none, do the things.
	if ( false === $cached_dataset ) {

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

		// Set our transient with our data for a minute.
		set_transient( $ky, $get_users->results, MINUTE_IN_SECONDS );

		// And change the variable to do the things.
		$cached_dataset = $get_users->results;
	}

	// And return the resulting.
	return $cached_dataset;
}
