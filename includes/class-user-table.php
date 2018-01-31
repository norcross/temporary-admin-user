<?php
/**
 * Our user table setup.
 *
 * @package TempAdminUser
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class TemporaryAdminUsers_Table extends WP_List_Table {

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {

		// Roll out each part.
		$columns    = $this->get_columns();
		$sortable   = $this->get_sortable_columns();
		$dataset    = $this->table_data();

		// Handle our sorting.
		usort( $dataset, array( $this, 'sort_data' ) );

		$paginate   = 10;
		$current    = $this->get_pagenum();

		// Set my pagination args.
		$this->set_pagination_args( array(
			'total_items' => count( $dataset ),
			'per_page'    => $paginate
		));

		// Slice up our dataset.
		$dataset    = array_slice( $dataset, ( ( $current - 1 ) * $paginate ), $paginate );

		// Do the column headers
		$this->_column_headers = array( $columns, array(), $sortable );

		// And the result.
		$this->items = $dataset;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return Array
	 */
	public function get_columns() {

		// Return our array of column setups.
		return array(
			'cb'        => '<input type="checkbox" />',
			'email'     => __( 'Email Address', 'temporary-admin-user' ),
			'status'    => __( 'Status', 'temporary-admin-user' ),
			'created'   => __( 'Date Created', 'temporary-admin-user' ),
			'expires'   => __( 'Expiration', 'temporary-admin-user' ),
			'actions'   => __( 'Actions', 'temporary-admin-user' ),
		);
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {

		// Return our setup.
		return array(
			'email'     => array( 'email', false ),
			'status'    => array( 'status', true ),
			'created'   => array( 'created', true ),
			'expires'   => array( 'expires', false ),
		);
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Return the setup.
		return array(
			'promote_users'  => __( 'Promote Users', 'temporary-admin-user' ),
			'restrict_users' => __( 'Restrict Users', 'temporary-admin-user' ),
			'delete_users'   => __( 'Delete Users', 'temporary-admin-user' ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {

		// Set my ID.
		$id = absint( $item['id'] );

		// Return my checkbox.
		return '<input type="checkbox" name="tmp_admin_users[]" id="cb-' . $id . '" value="' . $id . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select user', 'temporary-admin-user' ) . '</label>';
	}

	/**
	 * The current status column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_status( $item ) {

		// Set my stamp.
		$stamp  = absint( $item['expires'] );

		// Do the check and output accordingly.
		$status = current_time( 'timestamp' ) < absint( $stamp ) ? __( 'Active', 'temporary-admin-user' ) : __( 'Restricted', 'temporary-admin-user' );

		// Return my formatted date.
		return apply_filters( 'tmp_admin_user_status_display', $status, $item );
	}

	/**
	 * The "date created" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_created( $item ) {

		// Set my stamp.
		$stamp  = absint( $item['created'] );

		// Set my date with the formatting.
		$show   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'temporary-admin-user' ), human_time_diff( $stamp, current_time( 'timestamp' ) ) );

		// Wrap it in an accessible tag.
		$date   = '<abbr title="' . date( 'Y/m/d g:i:s a', $stamp ) . '">' . $show . '</abbr>';

		// Return my formatted date.
		return apply_filters( 'tmp_admin_user_created_date_display', $date, $item );
	}

	/**
	 * The "date expires" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_expires( $item ) {

		// Set my stamp.
		$stamp  = absint( $item['expires'] );

		// Output the "expires" text.
		if ( current_time( 'timestamp' ) > absint( $stamp ) ) {
			return '<em>' . __( 'This account has expired.', 'temporary-admin-user' ) . '</em>';
		}

		// Do my date logicals.
		$now    = new DateTime();
		$future = new DateTime( date( 'Y/m/d g:i:s a', $stamp ) );
		$intrvl = $future->diff( $now );

		// Wrap it in an accessible tag.
		$date   = '<abbr title="' . date( 'Y/m/d g:i:s a', $stamp ) . '">' . $intrvl->format( '%a days, %h hours, %i minutes' ) . '</abbr>';

		// Return my formatted date.
		return apply_filters( 'tmp_admin_user_expires_date_display', $date, $item );
	}

	/**
	 * Our actions column column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_actions( $item ) {

		// Set my ID.
		$id = absint( $item['id'] );

		// Create an array of actions.
		$setup  = array(
			'profile'   => array(
				'label' => __( 'View / Edit Profile', 'temporary-admin-user' ),
				'icon'  => 'id-alt',
				'link'  => get_edit_user_link( $id ),
				'blank' => true,
			),
			'email'     => array(
				'label' => __( 'Email User', 'temporary-admin-user' ),
				'icon'  => 'email',
				'link'  => 'mailto:' . antispambot( $item['email'] ),
			),
			'promote'   => array(
				'label' => __( 'Promote User', 'temporary-admin-user' ),
				'icon'  => 'star-filled',
			),
			'restrict'  => array(
				'label' => __( 'Restrict User', 'temporary-admin-user' ),
				'icon'  => 'lock',
			),
			'delete'    => array(
				'label' => __( 'Delete User', 'temporary-admin-user' ),
				'icon'  => 'trash',
			),
		);

		// Grab my status.
		$status = TempAdminUser_Users::check_user_status( $id );

		// Set my empty.
		$build  = '';

		// Now loop my setup.
		foreach ( $setup as $action => $items ) {

			// If we passed a link, use that.
			if ( ! empty( $items['link'] ) ) {

				// Check for blanks.
				$blank  = ! empty( $items['blank'] ) ? 'target="_blank"' : '';

				// And output the link itself.
				$build .= '<a class="tmp-admin-user-link tmp-admin-user-view tmp-admin-user-view-' . esc_attr( $action ) . '" href="' . esc_url( $items['link'] ) . '" title="' . esc_attr( $items['label'] ) . '" ' . esc_attr( $blank ) . '><i class="dashicons dashicons-' . esc_attr( $items['icon'] ) . '"></i></a>';

			} else {

				// Create my class.
				$class  = 'tmp-admin-user-link tmp-admin-user-action tmp-admin-user-action-' . esc_attr( $action );

				// Hide links based on status.
				if ( 'promote' === esc_attr( $action ) && 'active' === esc_attr( $status ) ) {

					// Create my class.
					$class .= ' tmp-admin-user-disabled';

					// And output the markup.
					$build .= '<span class="' . esc_attr( $class ) . '"><i class="dashicons dashicons-' . esc_attr( $items['icon'] ) . '"></i></span>';

				} elseif ( 'restrict' === esc_attr( $action ) && 'restricted' === esc_attr( $status ) ) {

					// Create my class.
					$class .= ' tmp-admin-user-disabled';

					// And output the markup.
					$build .= '<span class="' . esc_attr( $class ) . '"><i class="dashicons dashicons-' . esc_attr( $items['icon'] ) . '"></i></span>';

				} else {

					// Create the link args.
					$args   = array(
						'tmp-single'  => 1,
						'tmp-action'  => esc_attr( $action ),
						'user-id'     => $id,
						'nonce'       => wp_create_nonce( 'tmp_single_user_' . $id ),
					);

					// Set up the link.
					$link   = add_query_arg( $args, TempAdminUser_Helper::get_menu_link() );

					// And output the markup.
					$build .= '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $link ) . '" title="' . esc_attr( $items['label'] ) . '"><i class="dashicons dashicons-' . esc_attr( $items['icon'] ) . '"></i></a>';
				}
			}
		}

		// Return my links.
		return $build;
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {

		// First grab my users.
		if ( false === $users = TempAdminUser_Users::get_temp_users() ) {
			return array();
		}

		// Set my empty.
		$data   = array();

		// Loop my userdata.
		foreach ( $users as $user ) {

			// Get our created and expired times.
			$created    = get_user_meta( $user->ID, '_tmp_admin_user_created', true );
			$expires    = get_user_meta( $user->ID, '_tmp_admin_user_expires', true );

			// Set the array of the data we want.
			$data[] = array(
				'id'        => $user->ID,
				'email'     => $user->user_email,
				'created'   => $created,
				'expires'   => $expires,
			);
		}

		// Return our data.
		return $data;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array  $dataset      Our entire dataset.
	 * @param  string $column_name  Current column name
	 *
	 * @return mixed
	 */
	public function column_default( $dataset, $column_name ) {

		// Run our column switch.
		switch ( $column_name ) {

			case 'email' :
			case 'created' :
			case 'expires' :
				return $dataset[ $column_name ];

			default :
				return;
		}
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {

		// Set defaults and check for query strings.
		$ordby  = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'expires';
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'asc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'asc' === $order ? $result : -$result;
	}
}
