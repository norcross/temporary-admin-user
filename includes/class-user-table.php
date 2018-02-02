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
	 * TemporaryAdminUsers_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Temporary Admin User', 'temporary-admin-user' ),
			'plural'   => __( 'Temporary Admin Users', 'temporary-admin-user' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {

		// Roll out each part.
		$columns    = $this->get_columns();
		$hidden     = array();
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
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Make sure we have the bulk action running.
		$this->process_bulk_action();

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
			'id'        => __( 'ID', 'temporary-admin-user' ),
			'email'     => __( 'Email Address', 'temporary-admin-user' ),
			'status'    => __( 'Status', 'temporary-admin-user' ),
			'created'   => __( 'Date Created', 'temporary-admin-user' ),
			'expires'   => __( 'Expiration', 'temporary-admin-user' ),
			'actions'   => __( 'Actions', 'temporary-admin-user' ),
		);
	}

	/**
	 * Return null for our table, since no row actions exist.
	 *
	 * @param  object $item         The item being acted upon.
	 * @param  string $column_name  Current column name.
	 * @param  string $primary      Primary column name.
	 *
	 * @return null
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return '';
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
			'status'    => array( 'status', false ),
			'created'   => array( 'created', true ),
			'expires'   => array( 'expires', true ),
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
			'bulk_promote'  => __( 'Promote Users', 'temporary-admin-user' ),
			'bulk_restrict' => __( 'Restrict Users', 'temporary-admin-user' ),
			'bulk_delete'   => __( 'Delete Users', 'temporary-admin-user' ),
		);
	}

	/**
	 * Handle bulk actions.
	 *
	 * @see $this->prepare_items()
	 */
	protected function process_bulk_action() {

		// Bail if we aren't on the page.
		if ( empty( $this->current_action() ) || false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// Bail if a nonce was never passed.
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		// Grab an array of our items.
		$items  = array( 'bulk_promote', 'bulk_restrict', 'bulk_delete' );

		// Now check each variable against the array.
		if ( ! in_array( $this->current_action(), $items ) ) {
			return;
		}

		// Fail on a bad nonce.
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-temporaryadminusers' ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nononce' ) );
		}

		// Check for the array of users being passed.
		if ( empty( $_POST['tmp_admin_users'] ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nousers' ) );
		}

		// Check our user IDs.
		$user_ids   = array_filter( $_POST['tmp_admin_users'], 'absint' );

		// Check for the array of users being passed.
		if ( empty( $user_ids ) ) {
			tmp_admin_user()->admin_page_redirect( array( 'success' => 0, 'errcode' => 'nousers' ) );
		}

		// Set my action.
		$action = sanitize_text_field( $this->current_action() );

		// Loop my users and run the action on each one.
		foreach ( $user_ids as $user_id ) {

			// Grab my userdata.
			$user   = get_userdata( $user_id );

			// Handle my different action types.
			switch ( $action ) {

				case 'bulk_promote' :
					TempAdminUser_Users::promote_existing_user( $user );
					break;

				case 'bulk_restrict' :
					TempAdminUser_Users::restrict_existing_user( $user );
					break;

				case 'bulk_delete' :
					TempAdminUser_Users::delete_existing_user( $user );
					break;

				// End all case breaks.
			}
		}

		// And our success.
		tmp_admin_user()->admin_page_redirect( array( 'success' => 1, 'action' => $action ) );
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

		// Do the check and output accordingly.
		$status = ! empty( $item['restrict'] ) ? __( 'Restricted', 'temporary-admin-user' ) : __( 'Active', 'temporary-admin-user' );

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

		// Output the "expires" text.
		if ( ! empty( $item['restrict'] ) ) {
			return '<em>' . __( 'This account has expired.', 'temporary-admin-user' ) . '</em>';
		}

		// Set my stamp.
		$stamp  = absint( $item['expires'] );

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
				if ( empty( $item['restrict'] ) && 'promote' === esc_attr( $action ) || ! empty( $item['restrict'] ) && 'restrict' === esc_attr( $action ) ) {

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
			$restrict   = get_user_meta( $user->ID, '_tmp_admin_user_is_restricted', true );

			// Set the array of the data we want.
			$data[] = array(
				'id'        => $user->ID,
				'email'     => $user->user_email,
				'created'   => $created,
				'expires'   => $expires,
				'restrict'  => $restrict,
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

			case 'id' :
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
		$order  = ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc';

		// Set my result up.
		$result = strcmp( $a[ $ordby ], $b[ $ordby ] );

		// Return it one way or the other.
		return 'asc' === $order ? $result : -$result;
	}
}
