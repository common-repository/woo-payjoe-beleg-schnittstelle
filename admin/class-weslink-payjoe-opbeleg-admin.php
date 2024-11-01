<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://payjoe.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/admin
 * @author     PayJoe <info@payjoe.de>
 */
require_once plugin_dir_path( __FILE__ ) . '/class-weslink-payjoe-opbeleg-orders.php';
require_once plugin_dir_path( __FILE__ ) . '/partials/constants.php';
require_once plugin_dir_path( __FILE__ ) . '/partials/logging.php';

class Weslink_Payjoe_Opbeleg_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @since    1.0.0
	 */

	// set args to null as default so that in case wanna invoking public static function of this class,
	// we don't need to defiine these args
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'admin_notices', array( $this, 'sample_admin_notice__success' ) );
	}

	public function sample_admin_notice__success() {
		$zugangs_id = get_option( 'payjoe_zugangsid' );
		$username   = get_option( 'payjoe_username' );
		$apikey     = get_option( 'payjoe_apikey' );

		$show_warning = false;
		if ( empty( $apikey ) || empty( $zugangs_id ) || empty( $username ) ) {
			$show_warning = true;
		}

		if ( $show_warning ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					esc_html_e( 'One of the following PayJoe Settings is missing: ', 'woo-payjoe-beleg-schnittstelle' );
					echo '<ul style="list-style: disc; margin-left: 20px;">';
					if ( empty( $apikey ) ) {
						echo '<li>' . esc_html__( 'API Key', 'woo-payjoe-beleg-schnittstelle' ) . '</li>';
					}
					if ( empty( $username ) ) {
						echo '<li>' . esc_html__( 'Username', 'woo-payjoe-beleg-schnittstelle' ) . '</li>';
					}
					if ( empty( $zugangs_id ) ) {
						echo '<li>' . esc_html__( 'Account ID', 'woo-payjoe-beleg-schnittstelle' ) . '</li>';
					}
					echo '</ul>';

					?>
					</p>
			</div>
			<?php
		}
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Weslink_Payjoe_Opbeleg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Weslink_Payjoe_Opbeleg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/weslink-payjoe-opbeleg-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Weslink_Payjoe_Opbeleg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Weslink_Payjoe_Opbeleg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/weslink-payjoe-opbeleg-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @return        void
	 * @since        1.0.0
	 */
	public function add_menu() {

		add_submenu_page(
			'woocommerce',
			__( 'PayJoe Settings', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'PayJoe', 'woo-payjoe-beleg-schnittstelle' ),
			'manage_woocommerce',
			$this->plugin_name,
			array( $this, 'get_settings_page' )
		);

		/*
				add_menu_page(   'PayJoe Einstellungen', // Titel der Seite
									'PayJoe', // Menu Titel
									'manage_options', //Menü Bereich
									$this->plugin_name, //id
									array($this,'payjoe_settings_page_callback'),
									'dashicons-welcome-widgets-menus', //plugins_url( '/images/logo.png', __FILE__ ), //https://developer.wordpress.org/reference/functions/add_menu_page/
									3
				);
		*/
		$this->create_txt_field(
			'payjoe_username',
			'payjoe_section_basis',
			__( 'Username', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Your E-Mail which your regular login at PayJoe', 'woo-payjoe-beleg-schnittstelle' )
		);
		$this->create_txt_field(
			'payjoe_apikey',
			'payjoe_section_basis',
			__( 'API Key', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Secret key for API authentication', 'woo-payjoe-beleg-schnittstelle' )
		);
		$this->create_txt_field(
			'payjoe_zugangsid',
			'payjoe_section_basis',
			__( 'Account ID', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'The PayJoe account ID', 'woo-payjoe-beleg-schnittstelle' )
		);

		// key/value pair ~ value/label
		$interval_options = array(
			'0'   => __( 'Disable', 'woo-payjoe-beleg-schnittstelle' ),
			'0.5' => '0,5',
			'1'   => '1',
			'2'   => '2',
			'3'   => '3',
			'6'   => '6',
			'6'   => '6',
			'8'   => '8',
			'10'  => '10',
			'12'  => '12',
		);
		$default_interval = '0';

		$this->create_select_field(
			'payjoe_interval',
			'payjoe_section_basis',
			__( 'Interval', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'How often should the documents be transferred (hours)', 'woo-payjoe-beleg-schnittstelle' ),
			$interval_options,
			$default_interval
		);
		/*
		$this->create_txt_field('payjoe_startrenr', 'payjoe_section_basis'
			, __('Start Invoice Number', 'woo-payjoe-beleg-schnittstelle')
			, __('Which invoice number is the first you want to upload?', 'woo-payjoe-beleg-schnittstelle')
		);
		*/
		$this->create_date_field(
			'payjoe_start_order_date',
			'payjoe_section_basis',
			__( 'Start Order Date', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Orders before this date will not be uploaded', 'woo-payjoe-beleg-schnittstelle' )
		);

		$this->create_txt_field(
			'payjoe_transfer_count',
			'payjoe_section_basis',
			__( 'Upload size', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Number of documents to transfer in a single API upload. (max: 500, default: 100)', 'woo-payjoe-beleg-schnittstelle' ),
			100
		);

		$logging_options        = array(
			'0' => __( 'Disable', 'woo-payjoe-beleg-schnittstelle' ),
			'1' => __( 'Enable', 'woo-payjoe-beleg-schnittstelle' ),
		);
		$default_logging_option = '0';

		$loggin_description_more = '';
		if ( get_option( 'payjoe_log' ) ) {
			$payjoe_log_file_url     = sprintf( 'admin.php?page=%s&payjoe_download_log_file=1', $this->plugin_name );
			$loggin_description_more = sprintf( '<a href="%s" target="_blank">%s</a>', $payjoe_log_file_url, __( 'Download Logging File', 'woo-payjoe-beleg-schnittstelle' ) );
		}

		$this->create_select_field(
			'payjoe_log',
			'payjoe_section_basis',
			__( 'Logging', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Enable/Disable logging. (Logfiles are stored at uploads/payjoe/)', 'woo-payjoe-beleg-schnittstelle' ), // . $loggin_description_more
			$logging_options,
			$default_logging_option
		);

		$invoice_options        = array(
			PAYJOE_PLUGIN_TYPE_WCPDF         => 'WooCommerce PDF Invoices & Packing Slips',
			PAYJOE_PLUGIN_TYPE_GERMANIZED    => 'WooCommerce Germanized',
			PAYJOE_PLUGIN_TYPE_GERMAN_MARKET => 'German Market',
		);
		$default_invoice_option = PAYJOE_PLUGIN_TYPE_WCPDF;

		$this->create_select_field(
			'payjoe_invoice_options',
			'payjoe_section_basis',
			__( 'Invoice System', 'woo-payjoe-beleg-schnittstelle' ),
			__( 'Select the System used to generate the invoices', 'woo-payjoe-beleg-schnittstelle' ), // . $loggin_description_more
			$invoice_options,
			$default_invoice_option
		);

		add_settings_section( 'payjoe_section_basis', __( 'General Settings', 'woo-payjoe-beleg-schnittstelle' ), null, $this->plugin_name );

	} // add_menu()

	public function create_txt_field( $id, $section, $title, $info = '', $default = '' ) {
		$args = array(
			'info'    => $info,
			'id'      => $id,
			'default' => $default,
		);
		add_settings_field( $id, $title, array( $this, 'display_txt_field' ), $this->plugin_name, $section, $args );
		register_setting( $section, $id );
	}

	public function create_date_field( $id, $section, $title, $info = '', $default = '' ) {
		$args = array(
			'info'    => $info,
			'id'      => $id,
			'default' => $default,
		);
		add_settings_field( $id, $title, array( $this, 'display_date_field' ), $this->plugin_name, $section, $args );
		register_setting( $section, $id );
	}

	public function create_select_field( $id, $section, $title, $info = '', $options = array(), $default_option = null ) {
		$args = array(
			'info'           => $info,
			'id'             => $id,
			'options'        => $options,
			'default_option' => $default_option,
		);
		add_settings_field( $id, $title, array( $this, 'display_select_field' ), $this->plugin_name, $section, $args );
		register_setting( $section, $id );
	}

	public function display_txt_field( $args ) {
		$value = ! empty( get_option( $args['id'] ) ) ? get_option( $args['id'] ) : $args['default'];
		?>

		<input type="text" name="<?php echo esc_attr( $args['id'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>"
			   value="<?php echo esc_attr( $value ); ?>" size="65"/>
		<br><span class="description"> <?php echo esc_html( $args['info'] ); ?> </span>

		<?php
	}

	public function display_date_field( $args ) {
		$value = ! empty( get_option( $args['id'] ) ) ? get_option( $args['id'] ) : $args['default'];
		?>

		<input type="date" name="<?php echo esc_attr( $args['id'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>"
			   value="<?php echo esc_attr( $value ); ?>" size="65"/>
		<br><span class="description"> <?php echo esc_html( $args['info'] ); ?> </span>

		<?php
	}

	public function display_select_field( $args ) {
		?>
		<select name="<?php echo esc_attr( $args['id'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php
			foreach ( $args['options'] as $key => $val ) {
				$key          = strval( $key );
				$compared_val = ! empty( get_option( $args['id'] ) ) ? get_option( $args['id'] ) : $args['default_option'];
				$selected     = $compared_val === $key ? 'selected' : '';
				?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?> >
					<?php echo esc_html( $val ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<br><span class="description"> <?php echo esc_html( $args['info'] ); ?> </span>

		<?php
	}

	/*
	 * ********************************************* START TEMPLATE ************************************************
	*/


	/**
	 * Creates the settings page
	 *
	 * @return        void
	 * @since        1.0.0
	 */
	public function get_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-payjoe-beleg-schnittstelle' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PayJoe order upload', 'woo-payjoe-beleg-schnittstelle' ); ?></h1>
			<span>
				<?php echo wp_kses( __( 'PayJoe_info-text', 'woo-payjoe-beleg-schnittstelle' ), 'post' ); ?>
			</span>
			<form method="post" action="options.php" id="<?php echo esc_attr( $this->plugin_name ); ?>">
				<?php
				settings_fields( 'payjoe_section_basis' );
				do_settings_sections( $this->plugin_name );
				submit_button();
				?>
			</form>

			<hr>
			<h1><?php esc_html_e( 'Manually invoice upload', 'woo-payjoe-beleg-schnittstelle' ); ?></h1>
			<form method="post" action="admin.php?page=<?php echo esc_attr( $this->plugin_name ); ?>" id="payjoe_testapi_form">
				<input type="hidden" id="payjoe_testapi" name="payjoe_testapi">
				<?php
				submit_button( __( 'Upload new invoices now', 'woo-payjoe-beleg-schnittstelle' ) );
				?>
			</form>

			<hr>
			<h1><?php esc_html_e( 'Resend all invoices', 'woo-payjoe-beleg-schnittstelle' ); ?></h1>
			<form method="post" action="admin.php?page=<?php echo esc_attr( $this->plugin_name ); ?>" id="payjoe_testapi_form">
				<input type="hidden" id="payjoe_resend_invoices" name="payjoe_resend_invoices">
				<?php
				submit_button( __( 'Resend all invoices now', 'woo-payjoe-beleg-schnittstelle' ) );
				?>
			</form>


		</div>
		<?php
	} // options_page()

	public function init() {
		$this->register_cronjob( 'old', 'new' );
	}

	public function create_custom_schedule( $schedules ) {
		$payjoe_interval = get_option( 'payjoe_interval' );

		if ( $payjoe_interval ) {
			$schedules['weslink-payjoe-opbeleg-custom-schedule'] = array(
				'interval' => $payjoe_interval * 60 * 60,
				'display'  => __( 'Weslink-PayJoe-Opbeleg Custom Scheduled CronJob', 'woo-payjoe-beleg-schnittstelle' ),
			);
		}

		return $schedules;
	}

	public function register_cronjob( $old_value, $value, $option = '' ) {
		if ( $old_value !== $value ) {
			// remove existing cronjob
			wp_clear_scheduled_hook( 'weslink-payjoe-opbeleg-create-cronjob' );
		}

		$is_settings_set = ! ! get_option( 'payjoe_zugangsid' ) && ! ! get_option( 'payjoe_apikey' ) && ! ! get_option( 'payjoe_username' );

		if ( $value && $is_settings_set ) {
			$timestamp = wp_next_scheduled( 'weslink-payjoe-opbeleg-create-cronjob' );

			if ( $timestamp === false ) {
				// create new one
				wp_schedule_event( time(), 'weslink-payjoe-opbeleg-custom-schedule', 'weslink-payjoe-opbeleg-create-cronjob' );
			}
		}
	}

	// this one will be invoked by hook 'weslink-payjoe-opbeleg-create-cronjob'
	public function submit_order_to_api() {
		$enable_log = get_option( 'payjoe_log' );

		if ( $enable_log ) {
			payjoe_cleanup_logs();
			ob_start();
		}

		try {
			$payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();
			$posts         = $payjoe_orders->getOrderPosts();
			$invoices      = $payjoe_orders->itemsToInvoices( $posts );
			$payjoe_orders->uploadInvoices( $invoices, $enable_log );
		} catch ( Throwable $e ) {
			$message = __( 'Failed to submit orders to API.', 'woo-payjoe-beleg-schnittstelle' );
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload-error', $message . '<pre>' . (string) $e . '</pre>' );
			echo $message . "\n" .  esc_html( (string) $e ), "\n";
		}

		if ( $enable_log ) {
			// Append a new info to the file
			$date     = gmdate( 'Y-m-d H:i:s' );
			$content  = wp_specialchars_decode( ob_get_contents() );
			$log_info = "Order Upload: $date\n" . str_repeat( '-', 15 ) . "\n" . $content . "\n\n";

			$file_log = get_payjoe_log_path();
			if ( ! file_exists( $file_log ) || is_writable( $file_log ) ) {
				// WP_Filesystem does not support appending to a file
				$file_log = fopen( $file_log, 'a' );
				if ( $file_log !== false ) {
					fwrite( $file_log, $log_info );
					fclose( $file_log );
				}
			}

			ob_end_flush();
		}
	}

	/**
	 * Add a custom action to order actions select box on edit order page.
	 *
	 * @param array $actions order actions array to display
	 * @return array - updated actions
	 */
	public function add_order_actions( $actions ) {
		/** @var WC_Order $theorder */
		global $theorder;

		$invoice_number = get_post_meta( $theorder->get_id(), '_payjoe_invoice_number', true );
		$pj_status      = get_post_meta( $theorder->get_id(), '_payjoe_status', true );

		switch ( $pj_status ) {
			default:
			case PAYJOE_STATUS_PENDING:
				$actions['pj_upload_orders_action'] = __( 'PayJoe: Send order now', 'woo-payjoe-beleg-schnittstelle' );
				if ( $invoice_number ) {
					$actions['pj_reset_orders_action'] = __( 'PayJoe: Reset transfer state', 'woo-payjoe-beleg-schnittstelle' );
				}
				break;
			case PAYJOE_STATUS_RESEND:
			case PAYJOE_STATUS_OK:
			case PAYJOE_STATUS_ERROR:
				$actions['pj_upload_orders_action'] = __( 'PayJoe: Re-send order now', 'woo-payjoe-beleg-schnittstelle' );
				$actions['pj_reset_orders_action']  = __( 'PayJoe: Reset transfer state', 'woo-payjoe-beleg-schnittstelle' );
				break;
		}

		return $actions;
	}

	/**
	 * Add bulk actions for orders.
	 */
	public function add_order_bulk_actions( $bulk_actions ) {
		$bulk_actions['pj_upload_orders_action'] = __( 'PayJoe: Send order now', 'woo-payjoe-beleg-schnittstelle' );
		$bulk_actions['pj_reset_orders_action']  = __( 'PayJoe: Reset transfer state', 'woo-payjoe-beleg-schnittstelle' );
		return $bulk_actions;
	}

	/**
	 * Handle bulk action.
	 */
	public function handle_post_bulk_action( $redirect_to, $doaction, $post_ids ) {
		$args = array(
			'limit'    => -1, // No limit
			'post__in' => $post_ids,
		);
		switch ( $doaction ) {
			case 'pj_upload_orders_action':
				$orders = wc_get_orders( $args );
				$this->upload_orders( $orders );

				break;

			case 'pj_reset_orders_action':
				$orders = wc_get_orders( $args );
				$this->reset_orders( $orders );

				break;
		}

		return $redirect_to;
	}

	/**
	 * Clears the custom notices after they have been shown.
	 */
	public function clear_admin_notices() {
		WC_Admin_Notices::remove_notice( 'payjoe-upload' );
		WC_Admin_Notices::remove_notice( 'payjoe-upload-error' );
	}

	/**
	 * Handle upload action on order page.
	 */
	public function upload_orders_action( WC_Abstract_Order $order ) {
		$this->upload_orders( array( $order ) );
	}

	/**
	 * Handle reset action on order page.
	 */
	public function reset_orders_action( WC_Abstract_Order $order ) {
		$this->reset_orders( array( $order ) );
	}

	/**
	 * @param WC_Abstract_Order[] $orders
	 */
	public function upload_orders( array $orders ) {
		$log_json_data = false;

		ob_start();
		try {
			$order_ids = array_map(
				function ( WC_Abstract_Order $x ) {
					return $x->get_id();
				},
				$orders
			);

			foreach ( $orders as $o ) {
				if ( $o instanceof WC_Order ) {
					$refunds = $o->get_refunds();
					foreach ( $refunds as $r ) {
						$order_ids[] = $r->get_id();
						$orders[]    = $r;
					}
				}
			}

			$payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();
			$payjoe_orders->mapInvoiceNumbers( $order_ids );
			$invoices = $payjoe_orders->itemsToInvoices( $orders );
			$payjoe_orders->uploadInvoices( $invoices, $log_json_data );

			$output = ob_get_contents();
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload', '<pre>' . $output . '<pre>' );
		} catch ( Throwable $e ) {
			$output = ob_get_contents();
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload-error', '<pre>' . $e->getMessage() . "\n\n" . $output . '</pre>' );
		} finally {
			ob_end_clean();
		}
	}

	 /**
	  * @param WC_Abstract_Order[] $orders
	  */
	public function reset_orders( array $orders ) {
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				foreach ( $order->get_refunds() as $refund ) {
					PayJoe_Post::reset( $refund );
				}
			}
			PayJoe_Post::reset( $order );
		}
	}

	// update option about latest processed invoice number
	public function update_latest_processed_invoice_number( $result_msg, $post_id, $invoice_number ) {
		update_option( 'payjoe_startrenr', $invoice_number );
	}

	// update payjoe status
	public function update_payjoe_status( $result_msg, $post_id ) {
		$transfer_ok = ( $result_msg['error'] ? false : true );

		// update payjoe status
		if ( $transfer_ok === true ) {
			update_post_meta( $post_id, '_payjoe_status', PAYJOE_STATUS_OK );
			update_post_meta( $post_id, '_payjoe_error', '' );
		} elseif ( $result_msg['error'] === 'Duplicate' ) {
			update_post_meta( $post_id, '_payjoe_status', PAYJOE_STATUS_OK );
			update_post_meta( $post_id, '_payjoe_error', '' );
		} else {
			update_post_meta( $post_id, '_payjoe_status', PAYJOE_STATUS_ERROR );
			update_post_meta( $post_id, '_payjoe_error', $result_msg['error'] );
		}
	}

	/**
	 * Create additional PayJoe Status column for API SUBMISSION STATUS
	 *
	 * @param array $columns shop order columns
	 * @return array
	 */
	public function add_payjoe_status_column( $columns ) {
		// put the column after the Status column
		$new_columns = array_slice( $columns, 0, 2, true ) +
			array( 'payjoe_status' => __( 'PayJoe Status', 'woo-payjoe-beleg-schnittstelle' ) ) +
			array_slice( $columns, 2, count( $columns ) - 1, true );
		return $new_columns;
	}

	/**
	 * Display PayJoe Status in Shop Order column
	 *
	 * @param string $column column slug
	 */
	public function payjoe_status_column_data( $column ) {
		global $post;

		if ( $column === 'payjoe_status' ) {
			$pj_status = intval( get_post_meta( $post->ID, '_payjoe_status', true ) );
			$pj_error  = get_post_meta( $post->ID, '_payjoe_error', true );

			$this->echo_payjoe_order_column_html( $pj_status, $pj_error );
		}
	}

	private function echo_payjoe_order_column_html( int $status, string $error = null ) {
		switch ( $status ) {
			default:
			case PAYJOE_STATUS_PENDING:
				echo '<span class="order-status status-pending"><span class="dashicons-before dashicons-clock" title="' . esc_attr__( 'Waiting for invoice data', 'woo-payjoe-beleg-schnittstelle' ) . '" /></span>';
				break;
			case PAYJOE_STATUS_OK:
				echo '<span class="order-status status-processing"><span class="dashicons-before dashicons-yes" title="' . esc_attr__( 'Invoices transferred', 'woo-payjoe-beleg-schnittstelle' ) . '" /></span>';
				break;
			case PAYJOE_STATUS_ERROR:
				/* translators: %s: The error message */
				echo '<span class="order-status status-failed"><span class="dashicons-before dashicons-no" title="' . sprintf( esc_attr__( 'Transfer error: %s', 'woo-payjoe-beleg-schnittstelle' ), esc_attr( $error ) ) . '" /></span>';
				break;
			case PAYJOE_STATUS_RESEND:
				echo '<span class="order-status status-on-hold"><span class="dashicons-before dashicons-image-rotate" title="' . esc_attr__( 'Resend queued', 'woo-payjoe-beleg-schnittstelle' ) . '" /></span>';
				break;
		}
	}

	public function my_api_test_msg() {
		?>
		<div class="updated">
			<h3><?php esc_html_e( 'Result of order upload', 'woo-payjoe-beleg-schnittstelle' ); ?></h3>
			<pre style="overflow: scroll; height: 565px; word-wrap: break-word;"><?php $this->submit_order_to_api(); ?></pre>
		</div>
		<?php
	}

	public function my_resend_invoices_msg() {
		$payjoe_orders = new Weslink_Payjoe_Opbeleg_Orders();

		?>
		<div class="updated">
			<h3><?php esc_html_e( 'Result of order upload', 'woo-payjoe-beleg-schnittstelle' ); ?></h3>
			<pre style="overflow: scroll; height: 565px; word-wrap: break-word;">
			<?php
			$payjoe_orders->setResendStatus();
			?>
				</pre>
		</div>
		<?php
	}
}

// download log file
if ( isset( $_GET['payjoe_download_log_file'] ) ) {
	// get file url path
	$full_path = get_payjoe_log_path();

	// flush file
	if ( is_readable( $full_path ) ) {
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $full_path ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $full_path ) );
		readfile( $full_path );
		exit();
	}
}
