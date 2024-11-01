<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://payjoe.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 * @author     PayJoe <info@payjoe.de>
 */
class Weslink_Payjoe_Opbeleg {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Weslink_Payjoe_Opbeleg_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = PAYJOE_PLUGIN_BASENAME;
		$this->version     = PAYJOE_PLUGIN_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Weslink_Payjoe_Opbeleg_Loader. Orchestrates the hooks of the plugin.
	 * - Weslink_Payjoe_Opbeleg_I18n. Defines internationalization functionality.
	 * - Weslink_Payjoe_Opbeleg_Admin. Defines all hooks for the admin area.
	 * - Weslink_Payjoe_Opbeleg_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-weslink-payjoe-opbeleg-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-weslink-payjoe-opbeleg-i18n.php';

		/**
		 * Common classes
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-payjoe-invoice.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-payjoe-post.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-weslink-payjoe-opbeleg-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-weslink-payjoe-opbeleg-public.php';

		$this->loader = new Weslink_Payjoe_Opbeleg_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Weslink_Payjoe_Opbeleg_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Weslink_Payjoe_Opbeleg_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Weslink_Payjoe_Opbeleg_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_admin, 'init', 10, 3 );

		// add/update custom schedule
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'create_custom_schedule', 10 );

		// add new column "PayJoe Status" to Order List
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'add_payjoe_status_column', 999 );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu' );

		// hook to update option 'payjoe_startrenr' with value of latest invoice number
		$this->loader->add_action( 'weslink-payjoe-opbeleg-update-last-processed', $plugin_admin, 'update_latest_processed_invoice_number', 10, 3 );

		// hook to update option 'payjoe_status' and 'payjoe_error' if any
		$this->loader->add_action( 'weslink-payjoe-opbeleg-post-upload', $plugin_admin, 'update_payjoe_status', 10, 2 );

		// schedule cron-job
		$this->loader->add_action( 'add_option_payjoe_interval', $plugin_admin, 'register_cronjob', 10, 3 );
		$this->loader->add_action( 'update_option_payjoe_interval', $plugin_admin, 'register_cronjob', 10, 3 );

		// hook used to call get new invoices
		$this->loader->add_action( 'weslink-payjoe-opbeleg-create-cronjob', $plugin_admin, 'submit_order_to_api', 10 );

		// hook to get payjoe data
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'payjoe_status_column_data', 10 );

		// WooCommerce order action
		$this->loader->add_filter( 'woocommerce_order_actions', $plugin_admin, 'add_order_actions', 20, 1 );
		$this->loader->add_action( 'woocommerce_order_action_pj_upload_orders_action', $plugin_admin, 'upload_orders_action', 20, 1 );
		$this->loader->add_action( 'woocommerce_order_action_pj_reset_orders_action', $plugin_admin, 'reset_orders_action', 20, 1 );

		$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'add_order_bulk_actions', 20, 1 );
		$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'handle_post_bulk_action', 10, 3 );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'clear_admin_notices', 30 );

		// quick submitting invoices to API
		if ( isset( $_POST['payjoe_testapi'] ) ) {
			$this->loader->add_action( 'admin_notices', $plugin_admin, 'my_api_test_msg' );
		} elseif ( isset( $_POST['payjoe_resend_invoices'] ) ) {
			$this->loader->add_action( 'admin_notices', $plugin_admin, 'my_resend_invoices_msg' );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Weslink_Payjoe_Opbeleg_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Weslink_Payjoe_Opbeleg_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}
}
