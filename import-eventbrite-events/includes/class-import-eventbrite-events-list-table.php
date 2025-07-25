<?php
/**
 *  List table for scheduled import.
 *
 * @link       http://xylusthemes.com/
 * @since      1.0.0
 *
 * @package    Import_Eventbrite_Events
 * @subpackage Import_Eventbrite_Events/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class respoinsible for generate list table for scheduled import.
 */
class Import_Eventbrite_Events_List_Table extends WP_List_Table {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $status, $page;
			// Set parent defaults.
			parent::__construct(
				array(
					'singular' => 'iee_scheduled_import',     // singular name of the listed records.
					'plural'   => 'iee_scheduled_import',    // plural name of the listed records.
					'ajax'     => false,        // does this table support ajax?
				)
			);
	}

	/**
	 * Setup output for default column.
	 *
	 * @since    1.0.0
	 * @param array  $item Items.
	 * @param string $column_name  Column name.
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Setup output for title column.
	 *
	 * @since    1.0.0
	 * @param array $item Items.
	 * @return array
	 */
	function column_title( $item ) {

		$iee_url_delete_args = array(
			'page'       => isset( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : 'eventbrite_event', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'iee_action' => 'iee_simport_delete',
			'import_id'  => absint( $item['ID'] ),
		);

		$page              = isset( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : 'eventbrite_event'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab               = 'scheduled';
		$wp_redirect       = admin_url( 'admin.php?page=' . $page );
		$iee_url_edit_args = array(
			'tab'  => wp_unslash( $tab ),
			'edit' => absint( $item['ID'] ),
		);

		// Build row actions.
		$actions = array(
			'edit'   => sprintf( '<a href="%1$s">%2$s</a>', esc_url( add_query_arg( $iee_url_edit_args, $wp_redirect ) ), esc_html__( 'Edit', 'import-eventbrite-events' ) ),
			'delete' => sprintf( '<a href="%1$s" onclick="return confirm(\'Warning!! Are you sure to Delete this scheduled import? Scheduled import will be permanatly deleted.\')">%2$s</a>', esc_url( wp_nonce_url( add_query_arg( $iee_url_delete_args ), 'iee_delete_import_nonce' ) ), esc_html__( 'Delete', 'import-eventbrite-events' ) ),
		);

		$organizer_id    = $item["eventbrite_id"];
		$schedule_title = $item['title'];
		if ( strpos( $schedule_title, '(' ) !== false ) {
			$parts = explode( '(', $schedule_title, 2 );
			$schedule_title = trim( $parts[0] ) . '<br>(' . trim( $parts[1] );
		}
		if ( is_numeric( $organizer_id ) ) {
			$base_url = ( strpos( $schedule_title, 'by Collection ID' ) !== false ) ? 'https://www.eventbrite.com/cc/' : 'https://www.eventbrite.com/o/';
			$organizer_id = '<a href="' . esc_url( $base_url . $item["eventbrite_id"] ) . '" target="_blank">' . esc_html( $item["eventbrite_id"] ) . '</a>';
		}
		return sprintf( '<strong>%1$s</strong>
			<span>%2$s</span></br>
			<span>%3$s</span></br>
			<span>%4$s</span></br>
			<span>%5$s</span></br>
			<span style="color:silver">(id:%6$s)</span>%7$s',
			$schedule_title,
			__('Origin', 'import-eventbrite-events') . ': <b>' . ucfirst( $item["import_origin"] ) . '</b>',
			__('Import By', 'import-eventbrite-events') . ': <b>' . $item["import_by"] . '</b>',
			__('Eventbrite ID', 'import-eventbrite-events') . ': <b>' . $organizer_id . '</b>',
			__('Import Into', 'import-eventbrite-events') . ': <b>' . $item["import_into"] . '</b>',
			$item['ID'],
			$this->row_actions( $actions )
		);
	}

	/**
	 * Setup output for Action column.
	 *
	 * @since    1.0.0
	 * @param array $item Items.
	 * @return array
	 */
	function column_active_pause( $item ) {
		$post_id = $item['ID'];
		$status  = get_post_meta( $post_id, '_iee_schedule_status', true );

		if ( $status === 'paused' ) {
			$status_text  = 'Paused';
			$color        = '#d63638';
			$action_text  = 'Activate';
			$new_status   = 'active';
			$btn_class    = 'button button-secondary';
		} else {
			$status_text  = 'Active';
			$color        = '#008000';
			$action_text  = 'Pause';
			$new_status   = 'paused';
			$btn_class    = 'button button-primary';
		}

		$url = wp_nonce_url( add_query_arg([ 'action' => 'iee_toggle_status', 'schedule_id' => $post_id, 'new_status'  => $new_status, ]), 'iee_toggle_schedule_' . $post_id );

		return sprintf(
			'<div class="iee-status-wrap" style="display:flex; flex-direction:column; gap:6px; font-size:13px;">
				<div><a href="%s" class="%s">%s</a></div>
				<div><strong>Schedule Status:</strong> <span style="color:%s; font-weight:600;">%s</span></div>
			</div>',
			esc_url( $url ),
			esc_attr( $btn_class ),
			esc_html( $action_text ),
			esc_attr( $color ),
			esc_html( $status_text )
		);
	}




	/**
	 * Setup output for Action column.
	 *
	 * @since    1.0.0
	 * @param array $item Items.
	 * @return array
	 */
	function column_action( $item ) {

		$xtmi_run_import_args = array(
			'page'       => isset( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : 'eventbrite_event', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'iee_action' => 'iee_run_import',
			'import_id'  => $item['ID'],
		);

		$current_import = '';
		if(isset($item['current_import'])){
			$cimport = '<strong>'.esc_html__( 'Import is running in Background', 'import-eventbrite-events' ).'</strong>';
			if(!empty($item['current_import'])){
				$stats = array();
				if( $item['current_import']['created'] > 0 ){
					// translators: %d: Number of events created.
					$stats[] = sprintf( __( '%d Created', 'import-eventbrite-events' ), $item['current_import']['created']);
				}
				if( $item['current_import']['updated'] > 0 ){
					// translators: %d: Number of events Updated.
					$stats[] = sprintf( __( '%d Updated', 'import-eventbrite-events' ), $item['current_import']['updated'] );
				}
				if( $item['current_import']['skipped'] > 0 ){
					// translators: %d: Number of events Skipped.
					$stats[] = sprintf( __( '%d Skipped', 'import-eventbrite-events' ), $item['current_import']['skipped'] );
				}
				if( $item['current_import']['skip_trash'] > 0 ){
					// translators: %d: Number of events Skipped.
					$stats[] = sprintf( __( '%d Skipped in Trash', 'import-eventbrite-events' ), $item['current_import']['skip_trash'] );
				}
				if( !empty( $stats ) ){
					$stats = esc_html__( 'Stats: ', 'import-eventbrite-events' ).'<span style="color: silver">'.implode(', ', $stats).'</span>';
					$cimport .= '<br/>'.$stats;
				}
			}
			$current_import = '<div class="inprogress_import">'.$cimport.'</div>';
		}

		// Return the title contents.
		return sprintf(
			'<a class="button-primary" href="%1$s">%2$s</a><br/>%3$s<br/>%4$s<br/><br/>%5$s',
			esc_url( wp_nonce_url( add_query_arg( $xtmi_run_import_args ), 'iee_run_import_nonce' ) ),
			esc_html__( 'Import Now', 'import-eventbrite-events' ),
			$item['last_import'],
			$item['stats'],
			$current_import
		);
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("video")
			/*$2%s*/ $item['ID']             // The value of the checkbox should be the record's id
		);
	}

	/**
	 * Get column title.
	 *
	 * @since    1.0.0
	 */
	function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'title'            => __( 'Scheduled import', 'import-eventbrite-events' ),
			'import_status'    => __( 'Import Event Status', 'import-eventbrite-events' ),
			'import_category'  => __( 'Import Category', 'import-eventbrite-events' ),
			'import_frequency' => __( 'Import Frequency', 'import-eventbrite-events' ),
			'next_run'         => __( 'Next Run', 'import-eventbrite-events' ),
			'action'           => __( 'Action', 'import-eventbrite-events' ),
			'active_pause'     => __( 'Active/Pause', 'import-eventbrite-events' ),
		);
		return $columns;
	}

	public function get_bulk_actions() {

		return array(
			'delete' => __( 'Delete', 'import-eventbrite-events' ),
		);

	}

	/**
	 * Prepare Meetup url data.
	 *
	 * @since    1.0.0
	 */
	function prepare_items( $origin = '' ) {
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array( 'ID' );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		if ( $origin != '' ) {
			$data = $this->get_scheduled_import_data( $origin );
		} else {
			$data = $this->get_scheduled_import_data();
		}

		if ( ! empty( $data ) ) {
			$total_items = ( $data['total_records'] ) ? (int) $data['total_records'] : 0;
			// Set data to items.
			$this->items = ( $data['import_data'] ) ? $data['import_data'] : array();

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,  // WE have to calculate the total number of items.
					'per_page'    => $per_page, // WE have to determine how many items to show on a page.
					'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
				)
			);
		}
	}

	/**
	 * Get Meetup url data.
	 *
	 * @since    1.0.0
	 */
	function get_scheduled_import_data( $origin = '' ) {
		global $iee_events;

		// Check Running Imports.
		$current_imports = array();
		$batches = iee_get_inprogress_import();
		if(!empty($batches)){
			foreach ($batches as $batch) {
				if ( is_multisite() ) {
					$batch = isset( $batch->meta_value ) ? maybe_unserialize( $batch->meta_value ) : array();
				}else{
				    $batch = isset( $batch->option_value ) ? maybe_unserialize( $batch->option_value ) : array();
				}
				if( !empty( $batch ) && is_array( $batch ) ){
					$batch = current( $batch );
					$import_data = isset( $batch['imported_events'] ) ? $batch['imported_events'] : array(); 
					$import_status = array(
						'created' => 0,
						'updated' => 0,
						'skipped' => 0,
						'skip_trash' => 0
					);
					foreach ( $import_data as $key => $value ) {
						if ( $value['status'] == 'created' ) {
							$import_status['created'] += 1;
						} elseif ( $value['status'] == 'updated' ) {
							$import_status['updated'] += 1;
						} elseif ( $value['status'] == 'skipped' ) {
							$import_status['skipped'] += 1;
						} elseif ( $value['status'] == 'skip_trash' ) {
							$import_status['skip_trash'] += 1;
						}
					}	
					$current_imports[$batch['import_id']] = $import_status;
				}
			}
		}

		$scheduled_import_data = array(
			'total_records' => 0,
			'import_data'   => array(),
		);
		$per_page       = 10;
		$current_page   = $this->get_pagenum();
		$import_plugins = $iee_events->common->get_active_supported_event_plugins();

		$query_args = array(
			'post_type'      => 'iee_scheduled_import',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		if( isset( $_REQUEST['s'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['s'] = sanitize_text_field($_REQUEST['s']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		}

		if ( $origin != '' ) {
			$query_args['meta_key']   = 'import_origin';     //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_value'] = esc_attr( $origin ); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}
		$importdata_query                       = new WP_Query( $query_args );
		$scheduled_import_data['total_records'] = ( $importdata_query->found_posts ) ? (int) $importdata_query->found_posts : 0;
		$next_run_times = $this->get_iee_next_run_times();
		// The Loop.
		if ( $importdata_query->have_posts() ) {
			while ( $importdata_query->have_posts() ) {
				$importdata_query->the_post();

				$import_id     = get_the_ID();
				$import_title  = get_the_title();
				$import_data   = get_post_meta( $import_id, 'import_eventdata', true );
				$import_origin = get_post_meta( $import_id, 'import_origin', true );
				$import_plugin = isset( $import_data['import_into'] ) ? $import_data['import_into'] : '';
				$import_status = isset( $import_data['event_status'] ) ? $import_data['event_status'] : '';
				$eventbrite_id = $import_data['import_by'] === 'organizer_id' ? $import_data['organizer_id'] : ( $import_data['import_by'] === 'collection_id' ? $import_data['collection_id'] : __( 'Your Events', 'import-eventbrite-events' ) );
				$import_into = isset( $import_plugins[$import_plugin]) ? $import_plugins[$import_plugin] : $import_plugin;

				$term_names   = array();
				$import_terms = isset( $import_data['event_cats'] ) ? $import_data['event_cats'] : array();

				if ( $import_terms && ! empty( $import_terms ) ) {
					foreach ( $import_terms as $term ) {
						$get_term = '';
						if ( $import_plugin != '' && ! empty( $iee_events->$import_plugin ) ) {
							$get_term = get_term( $term, $iee_events->$import_plugin->get_taxonomy() );
						}

						if ( ! is_wp_error( $get_term ) && ! empty( $get_term ) ) {
							$term_names[] = $get_term->name;
						}
					}
				}

				$stats = $last_import_history_date = '';
				$history_args             = array(
					'post_type'      => 'iee_import_history',
					'post_status'    => 'publish',
					'numberposts'    => 1,
					'meta_key'       => 'schedule_import_id', //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $import_id,           //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'fields'         => 'ids'
				);

				$history = get_posts( $history_args );

				if( !empty( $history ) ){
					// translators: %d: Number of Last import.
					$last_import_history_date = sprintf( __( 'Last Import: %s ago', 'import-eventbrite-events' ), human_time_diff( get_the_date( 'U', $history[0] ), current_time( 'timestamp' ) ) );
					$created = get_post_meta( $history[0], 'created', true );
					$updated = get_post_meta( $history[0], 'updated', true );
					$skipped = get_post_meta( $history[0], 'skipped', true );
					$skip_trash = get_post_meta( $history[0], 'skip_trash', true );
					$stats = array();
					if( $created > 0 ){
						// translators: %d: Number of events created.
						$stats[] = sprintf( __( '%d Created', 'import-eventbrite-events' ), $created );
					}
					if( $updated > 0 ){
						// translators: %d: Number of events Updated.
						$stats[] = sprintf( __( '%d Updated', 'import-eventbrite-events' ), $updated );
					}
					if( $skipped > 0 ){
						// translators: %d: Number of events Skipped.
						$stats[] = sprintf( __( '%d Skipped', 'import-eventbrite-events' ), $skipped );
					}
					if( $skip_trash > 0 ){
						// translators: %d: Number of events Skipped in Trash.
						$stats[] = sprintf( __( '%d Skipped in Trash', 'import-eventbrite-events' ), $skip_trash );
					}
					if( !empty( $stats ) ){
						$stats = esc_html__( 'Last Import Stats: ', 'import-eventbrite-events' ).'<span style="color: silver">'.implode(', ', $stats).'</span>';
					}else{
						$error_reason      = get_post_meta( $history[0], 'error_reason', true );
						$nothing_to_import = get_post_meta( $history[0], 'nothing_to_import', true );
						if( !empty( $error_reason ) ){
							$stats = '<span style="color: red"><strong>'.esc_attr( 'The Private token you provided was invalid.', 'import-eventbrite-events' ).'</strong></span><br>';	
						}else{
							if( $nothing_to_import ){
								$stats = '<span style="color: silver">'.__( 'No events are imported.', 'import-eventbrite-events' ).'</span>';	
							}else{
								$stats = '';
							}
						}
					}
				}

				$next_run = '-';
				if(isset($next_run_times[$import_id]) && !empty($next_run_times[$import_id])){
					$next_time = $next_run_times[$import_id];
					$next_run = sprintf( '%s<br>(%s)',
						esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_time ), 'Y-m-d H:i:s' ) ),
						esc_html( human_time_diff( current_time( 'timestamp', true ), $next_time ) )
					);
				}

				if( $next_run == '-' ){
					$iee_events->common->iee_recreate_missing_schedule_import( $import_id );
				}
				$sstatus  = get_post_meta( $import_id, '_iee_schedule_status', true );
				if( $sstatus === 'paused' ){
					$next_run = '-';
				}

				$scheduled_import = array(
					'ID'               => $import_id,
					'title'            => $import_title,
					'import_status'    => ucfirst( $import_status ),
					'import_category'  => implode( ', ', $term_names ),
					'import_frequency' => isset( $import_data['import_frequency'] ) ? ucfirst( $import_data['import_frequency'] ) : '',
					'next_run'         => $next_run,
					'import_origin'    => $import_origin,
					'import_into'	   => $import_into,
					'eventbrite_id'	   => $eventbrite_id,
					'import_by'		   => $import_data['import_by'] === 'organizer_id' ? $import_data['organizer_id'] : ( $import_data['import_by'] === 'collection_id' ? $import_data['collection_id'] : __( 'Your Events', 'import-eventbrite-events' ) ),
					'last_import'      => $last_import_history_date,
					'stats'			  => $stats
				);
				if( isset( $current_imports[$import_id] ) ){
					$scheduled_import['current_import'] = $current_imports[$import_id];
				}
				$scheduled_import_data['import_data'][] = $scheduled_import;
			}
		}

		// Restore original Post Data.
		wp_reset_postdata();
		return $scheduled_import_data;
	}

	/**
	 * Get IEE crons.
	 *
	 * @return Array
	 */
	function get_iee_crons(){
		$crons = array();
		if(function_exists('_get_cron_array') ){
			$crons = _get_cron_array();
		}
		$wpea_scheduled = array_filter($crons, function($cron) {
			$cron_name = array_keys($cron) ? array_keys($cron)[0] : '';
			if (strpos($cron_name, 'iee_run_scheduled_import') !== false) {
				return true;
			}
			return false;
		});
		return $wpea_scheduled;
	}


	/**
	 * Get Next run time array for schdeuled import.
	 *
	 * @return Array
	 */
	function get_iee_next_run_times(){
		$next_runs = array();
		$crons  = $this->get_iee_crons();
		foreach($crons as $time => $cron){
			foreach($cron as $cron_name){
				foreach($cron_name as $cron_post_id){
					$schedule_id = isset( $cron_post_id['args']['post_id'] )  ? $cron_post_id['args']['post_id'] : 0;
					if( isset($cron_post_id['args']) && $schedule_id > 0 ){
						$next_runs[$schedule_id] = $time;
					}
				}
			}
		}
		return $next_runs;
	}
}

/**
 * Class respoinsible for generate list table for scheduled import.
 */
class Import_Eventbrite_Events_History_List_Table extends WP_List_Table {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $status, $page;
			// Set parent defaults.
			parent::__construct(
				array(
					'singular' => 'import_history',     // singular name of the listed records.
					'plural'   => 'iee_import_histories',   // plural name of the listed records.
					'ajax'     => false,        // does this table support ajax?
				)
			);
	}

	/**
	 * Setup output for default column.
	 *
	 * @since    1.0.0
	 * @param array  $item Items.
	 * @param string $column_name  Column name.
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Setup output for title column.
	 *
	 * @since    1.0.0
	 * @param array $item Items.
	 * @return array
	 */
	function column_title( $item ) {

		$iee_url_delete_args = array(
			'page'       => isset( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : 'eventbrite_event', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'tab'        => isset( $_REQUEST['tab'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) ) : 'history', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'iee_action' => 'iee_history_delete',
			'history_id' => absint( $item['ID'] ),
		);
		// Build row actions.
		$actions = array(
			'delete' => sprintf( '<a href="%1$s" onclick="return confirm(\'Warning!! Are you sure to Delete this import history? Import history will be permanatly deleted.\')">%2$s</a>', esc_url( wp_nonce_url( add_query_arg( $iee_url_delete_args ), 'iee_delete_history_nonce' ) ), esc_html__( 'Delete', 'import-eventbrite-events' ) ),
		);

		// Return the title contents.
		return sprintf(
			'<strong>%1$s</strong><span>%3$s</span> %2$s',
			$item['title'],
			$this->row_actions( $actions ),
			__( 'Origin', 'import-eventbrite-events' ) . ': <b>' . ucfirst( get_post_meta( $item['ID'], 'import_origin', true ) ) . '</b>'
		);
	}

	/**
	 * Setup output for Stats column.
	 *
	 * @since    1.0.0
	 * @param array $item Items.
	 * @return array
	 */
	function column_stats( $item ) {

		$created = get_post_meta( $item['ID'], 'created', true );
		$updated = get_post_meta( $item['ID'], 'updated', true );
		$skipped = get_post_meta( $item['ID'], 'skipped', true );
		$skip_trash = get_post_meta( $item['ID'], 'skip_trash', true );
		$error_reason = get_post_meta( $item['ID'], 'error_reason', true );
		$nothing_to_import = get_post_meta( $item['ID'], 'nothing_to_import', true );

		$success_message = '<span style="color: silver"><strong>';
		if ( $created > 0 ) {
			// translators: %d: Number of events Created.
			$success_message .= sprintf( __( '%d Created', 'import-eventbrite-events' ), $created ) . '<br>';
		}
		if ( $updated > 0 ) {
			// translators: %d: Number of events Updated.
			$success_message .= sprintf( __( '%d Updated', 'import-eventbrite-events' ), $updated ) . '<br>';
		}
		if ( $skipped > 0 ) {
			// translators: %d: Number of events Skipped.
			$success_message .= sprintf( __( '%d Skipped', 'import-eventbrite-events' ), $skipped ) . '<br>';
		}
		if ( $skip_trash > 0 ) {
			// translators: %d: Number of events Skipped in Trash.
			$success_message .= sprintf( __( '%d Skipped in Trash', 'import-eventbrite-events' ), $skip_trash ) . '<br>';
		}
		if( !empty( $error_reason ) ){
			$success_message .= __( 'The Private token you provided was invalid.', 'import-eventbrite-events' ) . '<br>';	
		}else{
			if( $nothing_to_import ){
				$success_message .= __( 'No events are imported.', 'import-eventbrite-events' ) . '<br>';	
			}
		}
		$success_message .= '</strong></span>';

		// Return the title contents.
		return $success_message;
	}

	/**
	 * Setup output for Action column.
	 *
	 * @param array $item Items.
	 * @return array
	 */
	function column_action( $item ) {
		$url = add_query_arg( array(
			'action'    => 'iee_view_import_history',
			'history'   => $item['ID'],
			'TB_iframe' => 'true',
			'width'     => '800',
			'height'    => '500'
		), admin_url( 'admin.php' ) );

		$imported_data = get_post_meta($item['ID'], 'imported_data', true);
		if(!empty($imported_data)){
			return sprintf(
				'<a href="%1$s" title="%2$s" class="open-history-details-modal button button-primary thickbox">%3$s</a>',
				$url,
				$item['title'],
				__( 'View Imported Events', 'import-eventbrite-events' )
			);
		}else{
			return '-';
		}
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("video")
			/*$2%s*/ $item['ID']                // The value of the checkbox should be the record's id
		);
	}

	/**
	 * Get column title.
	 *
	 * @since    1.0.0
	 */
	function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Import', 'import-eventbrite-events' ),
			'import_category' => __( 'Import Category', 'import-eventbrite-events' ),
			'import_date'     => __( 'Import Date', 'import-eventbrite-events' ),
			'stats'           => __( 'Import Stats', 'import-eventbrite-events' ),
			'action'          => __( 'Action', 'import-eventbrite-events' ),
		);
		return $columns;
	}

	public function extra_tablenav( $which ) {
		
		if ( 'top' !== $which ) {
			return;
		}	
		$iee_url_all_delete_args = array(
			'page'       => isset( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : 'eventbrite_event', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'tab'        => isset( $_REQUEST['tab'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) ) : 'history', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'iee_action' => 'iee_all_history_delete',
		);
		
		$delete_ids  = get_posts( array( 'numberposts' => 1,'fields' => 'ids', 'post_type' => 'iee_import_history' ) );
		$actions = '';
		if( !empty( $delete_ids ) ){
			$wp_delete_noonce_url = esc_url( wp_nonce_url( add_query_arg( $iee_url_all_delete_args, admin_url( 'admin.php' ) ), 'iee_delete_all_history_nonce' ) );
			$confirmation_message = esc_html__( "Warning!! Are you sure to delete all these import history? Import history will be permanatly deleted.", "import-eventbrite-events" );
			?>
			<a class="button apply" href="<?php echo esc_url( $wp_delete_noonce_url ); ?>" onclick="return confirm('<?php echo esc_attr( $confirmation_message ); ?>')">
				<?php esc_html_e( 'Clear Import History', 'import-eventbrite-events' ); ?>
			</a>
			<?php
		}
	}

	public function get_bulk_actions() {

		return array(
			'delete' => __( 'Delete', 'import-eventbrite-events' ),
		);

	}

	/**
	 * Prepare Meetup url data.
	 *
	 * @since    1.0.0
	 */
	function prepare_items( $origin = '' ) {
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array( 'ID' );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		if ( $origin != '' ) {
			$data = $this->get_import_history_data( $origin );
		} else {
			$data = $this->get_import_history_data();
		}

		if ( ! empty( $data ) ) {
			$total_items = ( $data['total_records'] ) ? (int) $data['total_records'] : 0;
			// Set data to items.
			$this->items = ( $data['import_data'] ) ? $data['import_data'] : array();

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,  // WE have to calculate the total number of items.
					'per_page'    => $per_page, // WE have to determine how many items to show on a page.
					'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
				)
			);
		}
	}

	/**
	 * Get Meetup url data.
	 *
	 * @since    1.0.0
	 */
	function get_import_history_data( $origin = '' ) {
		global $iee_events;

		$scheduled_import_data = array(
			'total_records' => 0,
			'import_data'   => array(),
		);
		$per_page              = 10;
		$current_page          = $this->get_pagenum();

		$query_args = array(
			'post_type'      => 'iee_import_history',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		if ( $origin != '' ) {
			$query_args['meta_key']   = 'import_origin';     //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['meta_value'] = esc_attr( $origin ); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$importdata_query                       = new WP_Query( $query_args );
		$scheduled_import_data['total_records'] = ( $importdata_query->found_posts ) ? (int) $importdata_query->found_posts : 0;
		// The Loop.
		if ( $importdata_query->have_posts() ) {
			while ( $importdata_query->have_posts() ) {
				$importdata_query->the_post();

				$import_id     = get_the_ID();
				$import_data   = get_post_meta( $import_id, 'import_data', true );
				$import_origin = get_post_meta( $import_id, 'import_origin', true );
				$import_plugin = isset( $import_data['import_into'] ) ? $import_data['import_into'] : '';

				$term_names   = array();
				$import_terms = isset( $import_data['event_cats'] ) ? $import_data['event_cats'] : array();

				if ( $import_terms && ! empty( $import_terms ) ) {
					foreach ( $import_terms as $term ) {
						$get_term = '';
						if ( $import_plugin != '' && ! empty( $iee_events->$import_plugin ) ) {
							$get_term = get_term( $term, $iee_events->$import_plugin->get_taxonomy() );
						}

						if ( ! is_wp_error( $get_term ) && ! empty( $get_term ) ) {
							$term_names[] = $get_term->name;
						}
					}
				}

				$scheduled_import_data['import_data'][] = array(
					'ID'              => $import_id,
					'title'           => get_the_title(),
					'import_category' => implode( ', ', $term_names ),
					'import_date'     => get_the_date( 'F j Y, h:i A' ),
				);
			}
		}
		// Restore original Post Data.
		wp_reset_postdata();
		return $scheduled_import_data;
	}
}

class Shortcode_List_Table extends WP_List_Table {

	public function prepare_items() {

		$columns 	= $this->get_columns();
		$hidden 	= $this->get_hidden_columns();
		$sortable 	= $this->get_sortable_columns();
		$data 		= $this->table_data();

		$perPage 		= 20;
		$currentPage 	= $this->get_pagenum();
		$totalItems 	= count( $data );

		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page'    => $perPage
		) );

		$data = array_slice( $data, ( ( $currentPage-1 ) * $perPage ), $perPage );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'id'            => __( 'ID', 'import-eventbrite-events' ),
			'how_to_use'    => __( 'Title', 'import-eventbrite-events' ),
			'shortcode'     => __( 'Shortcode', 'import-eventbrite-events' ),
			'action'    	=> __( 'Action', 'import-eventbrite-events' ),
		);

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {
		$data = array();

		$data[] = array(
					'id'            => 1,
					'how_to_use'    => 'Display All Events',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events]</p>',
					'action'     	=> '<button class="iee-btn-copy-shortcode button-primary"  data-value="[eventbrite_events]">Copy</button>',
					);
		$data[] = array(
					'id'            => 2,
					'how_to_use'    => 'New Grid Layouts <span style="color:green;font-weight: 900;">( PRO )</span>',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events layout="style2"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary'  data-value='[eventbrite_events layout=\"style2\"]'>Copy</button>",
					);
		$data[] = array(
					'id'            => 3,
					'how_to_use'    => 'New Grid Layouts <span style="color:green;font-weight: 900;">( PRO )</span>',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events layout="style3"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary'  data-value='[eventbrite_events layout=\"style3\"]'>Copy</button>",
					);
		$data[] = array(
					'id'            => 4,
					'how_to_use'    => 'New Grid Layouts <span style="color:green;font-weight: 900;">( PRO )</span>',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events layout="style4"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary'  data-value='[eventbrite_events layout=\"style4\"]'>Copy</button>",
					);
		$data[] = array(
					'id'            => 5,
					'how_to_use'    => 'New Grid Layouts <span style="color:green;font-weight: 900;">( PRO )</span>',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events layout="style5"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary'  data-value='[eventbrite_events layout=\"style5\"]'>Copy</button>",
					);
		$data[] = array(
					'id'            => 6,
					'how_to_use'    => 'New Grid Layouts <span style="color:green;font-weight: 900;">( PRO )</span>',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events layout="style6"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary'  data-value='[eventbrite_events layout=\"style6\"]'>Copy</button>",
					);
		$data[] = array(
					'id'            => 6,
					'how_to_use'    => 'Display with column',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events col="2"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events col=\"2\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 7,
					'how_to_use'    => 'Limit for display events',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events posts_per_page="12"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events posts_per_page=\"12\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 8,
					'how_to_use'    => 'Display Events based on order',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events order="asc"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events order=\"asc\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 9,
					'how_to_use'    => 'Display events based on category',
					'shortcode'     => '<p class="iee_short_code" >[eventbrite_events category="cat1"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events category=\"cat1\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 10,
					'how_to_use'    => 'Display Past events',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events past_events="yes"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events past_events=\"yes\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 11,
					'how_to_use'    => 'Display Events based on orderby',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events order="asc" orderby="post_title"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events order=\"asc\" orderby=\"post_title\"]' >Copy</button>",
					);
		$data[] = array(
					'id'            => 12,
					'how_to_use'    => 'Full Short-code',
					'shortcode'     => '<p class="iee_short_code">[eventbrite_events  col="2" posts_per_page="12" category="cat1" past_events="yes" order="desc" orderby="post_title" start_date="YYYY-MM-DD" end_date="YYYY-MM-DD"]</p>',
					'action'     	=> "<button class='iee-btn-copy-shortcode button-primary' data-value='[eventbrite_events col=\"2\" posts_per_page=\"12\" category=\"cat1\" past_events=\"yes\" order=\"desc\" orderby=\"post_title\" start_date=\"YYYY-MM-DD\" end_date=\"YYYY-MM-DD\"]' >Copy</button>",
					);
		return $data;
	}
	
	/**
	 * Define what data to show on each column of the table
	 *
	 * @param Array  $item Data
	 * @param String $column_name - Current column name
	 */
	public function column_default( $item, $column_name ){
		switch( $column_name ){
			case 'id':
			case 'how_to_use':
			case 'shortcode':
			case 'action':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}
}