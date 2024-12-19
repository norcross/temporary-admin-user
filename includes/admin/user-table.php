<?php
/**
 * Our table setup for the handling all the content.
 *
 * @package TempAdminUser
 */

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Admin\Markup as AdminMarkup;
use Norcross\TempAdminUser\Process\Queries as Queries;
use Norcross\TempAdminUser\Process\UserChanges as UserChanges;


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

		// Check for any bulk actions running.
		$this->process_bulk_actions();

		// Roll out each part.
		$columns    = $this->get_columns();
		$sortable   = $this->get_sortable_columns();

		// Load up the pagination settings.
		$paginate   = $this->get_items_per_page( 'tmp_table_per_page', 20 );

		// Now grab the dataset.
		$dataset    = $this->table_data( $paginate );

		// Get the total count from the query.
		$item_count = $dataset['total'];

		// Set my pagination args.
		$this->set_pagination_args( [
			'total_items' => $item_count,
			'per_page'    => $paginate,
			'total_pages' => ceil( $item_count / $paginate ),
		] );

		// Do the column headers.
		$this->_column_headers = [ $columns, [], $sortable ];

		// And the result.
		$this->items = $this->format_table_data( $dataset['users'] );
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
			'updated' => __( 'Last Updated', 'temporary-admin-user' ),
			'expires' => __( 'Expiration', 'temporary-admin-user' ),
			'actions' => __( 'Actions', 'temporary-admin-user' ),
		];

		// Include the bulk action dropdown.
		if ( ! $this->has_items() ) {
			unset( $setup_data['cb'] );
		}

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
			'updated'   => [ 'updated', true ],
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

			// Handle the new user form.
			$this->table_new_user_form_display();

			// Throw a wrap around the table.
			echo '<div class="tmp-admin-user-section-wrap tmp-admin-user-table-data-wrap">';

				// Wrap the display in a form.
				echo '<form action="" class="tmp-admin-user-table-form" id="tmp-admin-user-list-table-form" method="get">';

					// And the parent display (which is most of it).
					parent::display();

				// Close up the form.
				echo '</form>';

			// And close the table div.
			echo '</div>';

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

			// Include the bulk action dropdown.
			if ( $this->has_items() ) {
				echo '<div class="alignleft actions bulkactions">';
				$this->bulk_actions( $which );
				echo '</div>';
			}

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
			'tmp-admin-user-existing-table',
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
		return apply_filters( Core\HOOK_PREFIX . 'bulk_actions', [] );
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
			echo '<h1 class="wp-heading-inline">' . esc_html( get_admin_page_title() ) . '</h1>';

			// Cut off the header.
			echo '<hr class="wp-header-end">';

		// Close the div.
		echo '</div>';
	}

	/**
	 * Handle our new user form which goes before the table.
	 *
	 * @return HTML
	 */
	public function table_new_user_form_display() {

		// Wrap a div on it.
		echo '<div class="tmp-admin-user-section-wrap tmp-admin-user-new-user-form-wrap">';

			// Handle the title.
			echo '<h3 class="tmp-admin-user-new-user-form-title">' . esc_html__( 'Create New Temporary User', 'temporary-admin-user' ) . '</h3>';

			// And my actual form.
			AdminMarkup\render_new_user_form();

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
		return '<input type="checkbox" name="tmp_admin_users[]" id="cb-' . absint( $item['id'] ) . '" value="' . absint( $item['id'] ) . '" /><label for="cb-' . absint( $item['id'] ) . '" class="screen-reader-text">' . __( 'Select user', 'temporary-admin-user' ) . '</label>';
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
		$stamp  = absint( $item['stamps']['created'] );
		$local  = gmdate( 'Y/m/d g:i:s a', $stamp );

		// Set my date with the formatting.
		$show   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'temporary-admin-user' ), human_time_diff( $stamp, $item['stamps']['current'] ) );

		// Wrap it in an accessible tag.
		$build  = '<abbr title="' . esc_attr( $local ) . '">' . esc_html( $show ) . '</abbr>';

		// Return my formatted date.
		return apply_filters( Core\HOOK_PREFIX . 'created_date_display', $build, $item );
	}

	/**
	 * The "last updated" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_updated( $item ) {

		// If there is no last updated, then say so.
		if ( empty( $item['stamps']['updated'] ) ) {

			// Set an accessible.
			$setup_text = '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . __( 'No update time', 'temporary-admin-user' ) . '</span>';

			// Return my formatted text.
			return apply_filters( Core\HOOK_PREFIX . 'updated_date_display', $setup_text, $item, true );
		}

		// Set my stamp.
		$stamp  = absint( $item['stamps']['updated'] );
		$local  = gmdate( 'Y/m/d g:i:s a', $stamp );

		// Set my date with the formatting.
		$show   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'temporary-admin-user' ), human_time_diff( $stamp, $item['stamps']['current'] ) );

		// Wrap it in an accessible tag.
		$build  = '<abbr title="' . esc_attr( $local ) . '">' . esc_html( $show ) . '</abbr>';

		// Return my formatted date.
		return apply_filters( Core\HOOK_PREFIX . 'updated_date_display', $build, $item );
	}

	/**
	 * The "date expires" column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_expires( $item ) {

		// Handle determining if the timestamp expired.
		if ( absint( $item['stamps']['current'] ) >= absint( $item['stamps']['expires'] ) ) {

			// If this hasn't been handled by the cron job, restrict the account now.
			if ( ! empty( $item['status'] ) && 'active' === $item['status'] ) {
				UserChanges\restrict_existing_user( $item['id'] );
			}

			// Return my formatted text.
			return apply_filters( Core\HOOK_PREFIX . 'expires_date_display', '<em>' . __( 'This account has expired.', 'temporary-admin-user' ) . '</em>', $item, true );
		}

		// Set my stamps, both local and GMT.
		$stamp  = absint( $item['stamps']['expires'] );
		$local  = gmdate( 'Y/m/d g:i:s a', $stamp );

		// Do my date logicals.
		$now    = new DateTime( 'now' );
		$future = new DateTime( $local );
		$intrvl = $future->diff( $now );
		$format = $intrvl->format( '%a days, %h hours, %i minutes' );

		// Remove the possible "0 days" because it's ugly.
		$show   = str_replace( '0 days,', '', $format );

		// Wrap it in an accessible tag.
		$build  = '<abbr title="' . esc_attr( $local ) . '">' . esc_html( $show ) . '</abbr>';

		// Return my formatted date.
		return apply_filters( Core\HOOK_PREFIX . 'expires_date_display', $build, $item, false );
	}

	/**
	 * Our actions column column.
	 *
	 * @param  array  $item  The item from the data array.
	 *
	 * @return string
	 */
	protected function column_actions( $item ) {

		// First get my user actions.
		$setup_actions  = Helpers\create_user_action_args( $item['id'], $item['email'] );

		// Pass it over to the larger HTML builder.
		return AdminMarkup\render_user_actions_list( $item, $setup_actions, false );
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

		// Return the results of the query with the args.
		return Queries\query_user_table_data( $setup_args );
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

		// Include the "now" time so we can calc it later.
		$right_now  = current_datetime()->format('U');

		// Set my empty.
		$list_data  = [];

		// Now loop each bit of user info.
		foreach ( $table_data as $user_obj ) {

			// Adding to the array of arrays.
			$list_data[] = [
				'id'     => $user_obj->ID,
				'email'  => $user_obj->user_email,
				'status' => get_user_meta( $user_obj->ID, Core\META_PREFIX . 'status', true ),
				'stamps' => [
					'created' => get_user_meta( $user_obj->ID, Core\META_PREFIX . 'created', true ),
					'expires' => get_user_meta( $user_obj->ID, Core\META_PREFIX . 'expires', true ),
					'updated' => get_user_meta( $user_obj->ID, Core\META_PREFIX . 'updated', true ),
					'current' => $right_now,
				]
			];
		}

		// Return our data.
		return apply_filters( Core\HOOK_PREFIX . 'table_display_data', $list_data, $table_data );
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
				return ! empty( $dataset[ $column_name ] ) ? $dataset[ $column_name ] : '';

			case 'created' :
			case 'updated' :
			case 'expires' :
				return ! empty( $dataset['stamps'][ $column_name ] ) ? $dataset['stamps'][ $column_name ] : '';

			case 'status' :
				return ! empty( $dataset[ $column_name ] ) ? ucfirst( $dataset[ $column_name ] ) : '';

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

	/**
	 * Handle bulk actions.
	 *
	 * @see $this->prepare_items()
	 */
	protected function process_bulk_actions() {

		// Allow this hooked since it needs to run at a specific time in the table generation.
		do_action( Core\HOOK_PREFIX . 'bulk_action_process' );
	}

	// Add any additional functions here.
}
