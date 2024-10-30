<?php
session_start();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
//use below file to remove Fatal error: Call to undefined function convert_to_screen()
require_once( ABSPATH . 'wp-admin/includes/template.php' );
require_once 'Repo.php';


class PictureListTable extends WP_List_Table {

	public $repo;


	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Picture', NABTXD ), //singular name of the listed records
			'plural'   => __( 'Pictures', NABTXD ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
			'screen'   => 'interval-list'        //hook suffix
		] );
		$this->repo = new Repo();

	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No pictures avaliable.', NABTXD );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'pid':
			case 'image_slug':
			case 'filename':
			case 'alttext':
			case 'description':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-select[]" value="%s" />', $item['pid']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_pid( $item ) {

		$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

		$title = '<strong>' . $item['pid'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['pid'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'          => '<input type="checkbox" />',
			'pid'         => __( 'ID', NABTXD ),
			'filename'    => __( 'Filename', NABTXD ),
			'alttext'     => __( 'Alt', NABTXD ),
			'description' => __( 'Description', NABTXD )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'filename' => array( 'filename', true ),
			'pid'      => array( 'pid', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete'       => __('Delete',NABTXD),
			'bulk-change-alt'   => __('Change Alt',NABTXD),
			'random-change-alt' => __('Random Change Alt',NABTXD)

		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$result = $this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 15 );
		$current_page = $this->get_pagenum();
		$gid = $_REQUEST['gid'];
		$total_items  = $this->repo->record_count('ngg_pictures',$gid);

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = $this->repo->get_pictures( $per_page, $current_page,$_REQUEST['gid'] );

		return $result;
	}

	public function process_bulk_action() {

//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
				die( 'Go get a life script kiddies' );
			} else {
				$this->repo->delete_picture( absint( $_GET['customer'] ) );

				wp_redirect( esc_url( add_query_arg() ) );
				exit;
			}

		}

// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-select'] );

// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				$this->repo->delete_picture( $id );

			}

			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-change-alt' ) {
			return $this->repo->updateMultipleItemsBySingleAlt( 'bulk-select', 'checkbox_alt' );
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'random-change-alt' ) {

			return $this->repo->updateImages( 'bulk-select', 'checkbox_alt' );
		}

	}
}



