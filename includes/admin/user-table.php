<?php
/**
 * Our table setup for the handling all the content.
 *
 * @package TempAdminUser
 */

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Queries as Queries;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table.
 */
class Temporary_Admin_Users_List extends WP_List_Table {

	/**
	 * Temporary_Admin_Users_List constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct( array(
			'singular' => __( 'Temporary Admin Users', 'temporary-admin-user' ),
			'plural'   => __( 'Temporary Admin Users', 'temporary-admin-user' ),
			'ajax'     => false,
			'screen'   => Core\MENU_ROOT,
		) );
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Roll out each part.
		$columns    = $this->get_columns();
		$sortable   = $this->get_sortable_columns();

		// Load up the pagination settings.
		$paginate   = $this->get_items_per_page( 'tmp_table_per_page', 20 );



		$item_count = $this->table_count();
		$current    = $this->get_pagenum();

		// Now grab the dataset.
		$dataset    = $this->table_data( $paginate );

		// Set my pagination args.
		$this->set_pagination_args( [
			'total_items' => $item_count,
			'per_page'    => $paginate,
			'total_pages' => ceil( $item_count / $paginate ),
		] );

		// Do the column headers.
		$this->_column_headers = [ $columns, [], $sortable ];

		// And the result.
		$this->items = $dataset;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array
	 */
	public function get_columns() {

		// Build and return our array of column setups.
		$setup_data = [
			'cb'      => '<input type="checkbox" />',
			'id'      => __( 'ID', 'temporary-admin-user' ),
			'email'   => __( 'Email Address', 'temporary-admin-user' ),
			'status'  => __( 'Status', 'temporary-admin-user' ),
			'created' => __( 'Date Created', 'temporary-admin-user' ),
			'expires' => __( 'Expiration', 'temporary-admin-user' ),
			'actions' => __( 'Actions', 'temporary-admin-user' ),
		];

		// Return the array.
		return apply_filters( Core\HOOK_PREFIX . 'table_columns', $setup_data );
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		// Build our array of sortable columns.
		$setup_data = [
			'email'     => [ 'email', false ],
			'status'    => [ 'status', false ],
			'created'   => [ 'created', true ],
			'expires'   => [ 'expires', true ],
		];

		// Return the array.
		return apply_filters( Core\HOOK_PREFIX . 'sortable_columns', $setup_data );
	}

	/**
	 * Display all the things.
	 *
	 * @return void
	 */
	public function display() {

		// Wrap the basic div on it.
		echo '<div class="wrap tmp-admin-user-settings-wrap">';

			// Include our page display.
			$this->table_page_title_display();

			/*
			// Throw a wrap around the table.
			echo '<div class="ac-table-admin-section-wrap ac-table-admin-table-wrap">';

				// Wrap the display in a form.
				echo '<form action="" class="ac-admin-form" id="ac-admin-table-form" method="get">';

					// And the parent display (which is most of it).
					parent::display();

				// Close up the form.
				echo '</form>';

			// And close the table div.
			echo '</div>';
			*/

		// And close the final div.
		echo '</div>';
	}

	/**
	 * Generates the table navigation above or below the table.
	 *
	 * @param  string $which  Which nav it is.
	 *
	 * @return HTML
	 */
	protected function display_tablenav( $which ) {

		// Open the table nav.
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';

			// Include the blank div where the bulk action dropdown would be.
			echo '<div class="alignleft actions bulkactions"></div>';

			// Now show the other parts.
			$this->extra_tablenav( $which );
			$this->pagination( $which );

			// Clear everything out.
			echo '<br class="clear" />';

		// Close it up.
		echo '</div>';
	}

	/**
	 * Set up our custom table classes.
	 *
	 * @return array
	 */
	protected function get_table_classes() {

		// Set our array.
		$setup_data = [
			'widefat',
			'fixed',
			'striped',
			'table-view-list',
			'posts',
			'temporary-admin-user',
		];

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'table_classes', $setup_data );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		// Set our array.
		$setup_data = [
			'bulk-promote'  => __( 'Promote Users', 'temporary-admin-user' ),
			'bulk-restrict' => __( 'Restrict Users', 'temporary-admin-user' ),
			'bulk-delete'   => __( 'Delete Users', 'temporary-admin-user' ),
		];

		// Return it, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'bulk_actions', $setup_data );
	}

	/**
	 * Handle our admin page title setup.
	 *
	 * @return HTML
	 */
	public function table_page_title_display() {

		// Wrap a div on it.
		echo '<div class="tmp-admin-user-section-wrap tmp-admin-user-title-wrap">';

			// Handle the title.
			echo '<h1 class="wp-heading-inline tmp-admin-user-settings-title">' . esc_html( get_admin_page_title() ) . '</h1>';

			// Cut off the header.
			echo '<hr class="wp-header-end">';

		// Close the div.
		echo '</div>';
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param  string $which  Which markup area after (bottom) or before (top) the list.
	 *
	 * @return HTML
	 */
	protected function extra_tablenav( $which ) {
		return '';
	}

	/**
	 * Checkbox column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {

		// Return my checkbox.
		return '<input type="checkbox" name="tmp_admin_users[]" id="cb-' . absint( $item['id'] ) . '" value="' . absint( $item['id'] ) . '" /><label for="cb-' . $id . '" class="screen-reader-text">' . __( 'Select user', 'temporary-admin-user' ) . '</label>';
	}

	/**
	 * The current status column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_status( $item ) {
		return '';
	}

	/**
	 * The "date created" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_created( $item ) {
		return '';
	}

	/**
	 * The "date expires" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_expires( $item ) {
		return '';
	}

	/**
	 * Our actions column column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_actions( $item ) {
		return '';
	}

	/**
	 * Get the table data.
	 *
	 * @param  integer $per_page  How may we want per-page.
	 *
	 * @return Array
	 */
	private function table_data( $per_page = 20 ) {

		// Set the possible args for the query.
		$setup_args = [
			'paged'    => filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ),
			'order'    => filter_input( INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS ),
			'orderby'  => filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS ),
			'per_page' => $per_page,
		];

		// Just handle the default return.
		return Queries\query_current_temporary_users( $setup_args );
	}

	/**
	 * Get our table data in a format we can use.
	 *
	 * @param  array  $table_data  The data we got from our query.
	 *
	 * @return array
	 */
	private function format_table_data( $table_data = [] ) {

		// Bail without data to look at.
		if ( empty( $table_data ) ) {
			return false;
		}

		// Set my empty.
		$list_data  = [];

		// Now loop each customer info.
		foreach ( $table_data as $index => $item ) {

			// Make sure this is an array.
			$set_object = is_array( $item ) ? get_post( $item['ID'] ) : $item;
			$set_data   = is_array( $item ) ? $item : (array) $item;

			// Confirm a title.
			$set_title  = ! empty( $set_data['post_title'] ) ? $set_data['post_title'] : '(' . __( 'no title', 'temporary-admin-user' ) . ')';

			// Set up the basic return array.
			$list_data[] = [
				'id'             => $set_data['ID'],
				'title'          => $set_title,
				'categories'     => AuthorQueries\query_single_item_term_data( $set_data['ID'], 'category' ),
				'post_tags'      => AuthorQueries\query_single_item_term_data( $set_data['ID'], 'post_tag' ),
				'post_type'      => $set_data['post_type'],
				'date'           => $set_data['post_date'],
				'stamp'          => strtotime( $set_data['post_date'] ),
				'status'         => $set_data['post_status'],
				'post_object'    => $set_object,
			];
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'table_data', $list_data, $table_data );
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @param  array  $dataset      Our entire dataset.
	 * @param  string $column_name  Current column name.
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
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';

			default :
				return apply_filters( Core\HOOK_PREFIX . 'table_column_default', '', $dataset, $column_name );
		}
	}

	/**
	 * This is a legacy piece from the WP_List_Table that only renders a hidden button.
	 *
	 * @param  object|array $item         The item being acted upon.
	 * @param  string       $column_name  Current column name.
	 * @param  string       $primary      Primary column name.
	 *
	 * @return string                     An empty string.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return '';
	}

	/**
	 * Handle the display text for when no items exist.
	 *
	 * @return string
	 */
	public function no_items() {
		esc_html_e( 'No users available.', 'temporary-admin-user' );
	}

	// Add any additional functions here.
}
