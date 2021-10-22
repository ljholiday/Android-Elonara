<?php
/**
Plugin Name: ThisNew Integration for WooCommerce
Plugin URI: https://wordpress.org/plugins/thisnew-shipping-for-woocommerce/
Description: Calculate correct shipping and tax rates for your ThisNew-Woocommerce integration.
Version: 1.0.0
Author: ThisNew
Author URI: http://www.thisnew.com
License: GPL2 http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: thisnew
WC requires at least: 3.0.0
WC tested up to: 3.9
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'PF_PLUGIN_FILE' ) ) {
    define( 'PF_PLUGIN_FILE', __FILE__ );
}

class ThisNew_Base {

    const VERSION = '1.0.0';
//	const PF_HOST = 'https://test.thisnew.com/';
//	const PF_API_HOST = 'https://test.thisnew.com/';
// const PF_HOST = 'https://ut.thisnew.com/';
// const PF_API_HOST = 'https://ut.thisnew.com/';
     const PF_HOST = 'https://www.thisnew.com/';
     const PF_API_HOST = 'https://www.thisnew.com/';

    /**
     * Construct the plugin.
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'thisnew_load_plugin_textdomain') );

        // WP REST API.
        $this->rest_api_init();
    }

    /**
     * Initialize the plugin.313
     */
    public function init() {

        if (!class_exists('WC_Integration')) {
            return;
        }

        //load required classes
	    require_once 'includes/class-thisnew-integration.php';
	    require_once 'includes/class-thisnew-carriers.php';
	    require_once 'includes/class-thisnew-taxes.php';
	    require_once 'includes/class-thisnew-shipping.php';
	    require_once 'includes/class-thisnew-request-log.php';
	    require_once 'includes/class-thisnew-admin.php';
	    require_once 'includes/class-thisnew-admin-dashboard.php';
	    require_once 'includes/class-thisnew-admin-settings.php';
        require_once 'includes/class-thisnew-admin-status.php';
	    require_once 'includes/class-thisnew-admin-support.php';
	    require_once 'includes/class-thisnew-size-chart-tab.php';
	    require_once 'includes/class-thisnew-size-chart-tab.php';
        require_once 'includes/class-thisnew-template.php';
        require_once 'includes/class-thisnew-customizer.php';


	    //launch init
	    ThisNew_Taxes::init();
        ThisNew_Shipping::init();
        ThisNew_Request_log::init();
        ThisNew_Admin::init();
        ThisNew_Size_Chart_Tab::init();
        ThisNew_Template::init();
        ThisNew_Customizer::init();

	    //hook ajax callbacks
	    add_action( 'wp_ajax_save_thisnew_settings', array( 'ThisNew_Admin_Settings', 'save_thisnew_settings' ) );
//	    add_action( 'wp_ajax_ajax_force_check_connect_status', array( 'ThisNew_Integration', 'ajax_force_check_connect_status' ) );
	    add_action( 'wp_ajax_get_thisnew_stats', array( 'ThisNew_Admin_Dashboard', 'render_stats_ajax' ) );
	    add_action( 'wp_ajax_get_thisnew_orders', array( 'ThisNew_Admin_Dashboard', 'render_orders_ajax' ) );
	    add_action( 'wp_ajax_get_thisnew_status_checklist', array( 'ThisNew_Admin_Status', 'render_status_table_ajax' ) );
	    add_action( 'wp_ajax_get_thisnew_status_report', array( 'ThisNew_Admin_Support', 'render_status_report_ajax' ) );
	    add_action( 'wp_ajax_get_thisnew_carriers', array( 'ThisNew_Admin_Settings', 'render_carriers_ajax' ) );
//        add_action( 'wp_ajax_get_send_url', array( 'ThisNew_Admin_Dashboard', 'ajax_send_url' ) );
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     */
    public function thisnew_load_plugin_textdomain() {
        load_plugin_textdomain( 'thisnew', false, plugin_basename( dirname( PF_PLUGIN_FILE ) ) . '/i18n/languages' );
    }

	/**
	 * @return string
	 */
    public static function get_asset_url() {
		return trailingslashit(plugin_dir_url(__FILE__)) . 'assets/';
    }

    /**
	 * @return string
	 */
	public static function get_thisnew_host() {
		if ( defined( 'PF_DEV_HOST' ) ) {
			return PF_DEV_HOST;
		}

		return self::PF_HOST;
	}

	/**
	 * @return string
	 */
	public static function get_thisnew_api_host() {
		if ( defined( 'PF_DEV_API_HOST' ) ) {
			return PF_DEV_API_HOST;
		}

		return self::PF_API_HOST;
	}

    private function rest_api_init()
    {
        // REST API was included starting WordPress 4.4.
        if ( ! class_exists( 'WP_REST_Server' ) ) {
            return;
        }

        // Init REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 20);
    }

    public function register_rest_routes()
    {
        require_once 'includes/class-thisnew-rest-api-controller.php';

        $thisnewRestAPIController = new ThisNew_REST_API_Controller();
        $thisnewRestAPIController->register_routes();
    }
}

new ThisNew_Base();    //let's go