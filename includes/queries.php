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
		$set_offset = ( absint( $query_args['paged'] ) - 1 ) * absint( $ppage );
	}

	// Define the order and orderby, with defaults.
	$do_orderby = ! empty( $query_args['orderby'] ) ? $query_args['orderby'] : 'expires';
	$do_order   = ! empty( $query_args['order'] ) ? strtoupper( $query_args['order'] ) : 'DESC';

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
	if ( in_array( $do_orderby, ['status', 'created', 'expires'], true ) ) {

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
 * Get the available expiration time ranges that a user can be set to.
 *
 * @param  string  $single  Optional to fetch one item from the array.
 * @param  boolean $keys    Whether to return just the keys.
 *
 * @return array            An array of the time data.
 */
function get_user_durations( $single = '', $keys = false ) {

	// Set my ranges. All values are in seconds.
	$ranges = [
		'fifteen' => [
			'value' => 900,
			'label' => __( 'Fifteen Minutes', 'temporary-admin-user' )
		],
		'halfhour' => [
			'value' => 1800,
			'label' => __( 'Thirty Minutes', 'temporary-admin-user' )
		],
		'hour' => [
			'value' => HOUR_IN_SECONDS,
			'label' => __( 'One Hour', 'temporary-admin-user' )
		],
		'day' => [
			'value' => DAY_IN_SECONDS,
			'label' => __( 'One Day', 'temporary-admin-user' )
		],
		'week' => [
			'value' => WEEK_IN_SECONDS,
			'label' => __( 'One Week', 'temporary-admin-user' )
		],
		'month' => [
			'value' => MONTH_IN_SECONDS,
			'label' => __( 'One Month', 'temporary-admin-user' )
		],
		'year' => [
			'value' => YEAR_IN_SECONDS,
			'label' => __( 'One Month', 'temporary-admin-user' )
		],
	];

	// Allow it filtered.
	$ranges = apply_filters( Core\HOOK_PREFIX . 'expire_ranges', $ranges );

	// Bail if no data exists.
	if ( empty( $ranges ) ) {
		return false;
	}

	// Return just the array keys.
	if ( ! empty( $keys ) ) {
		return array_keys( $ranges );
	}

	// Return the entire array if no key requested.
	if ( empty( $single ) ) {
		return $ranges;
	}

	// Return the single key item.
	return ! empty( $single ) && isset( $ranges[ $single ] ) ? $ranges[ $single ] : false;
}
