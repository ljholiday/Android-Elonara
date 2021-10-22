<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ThisNew_Admin_Dashboard {

	const API_KEY_SEARCH_STRING = 'ThisNew';

	public static $_instance;

	/**
	 * @return thisnew_Admin_Dashboard
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * thisnew_Admin_Dashboard constructor.
	 */
	function __construct() {

	}

    /**
     * Show the view
     * @throws thisnewException
     */
	public static function view() {

		$dashboard = self::instance();
		$api_key = ThisNew_Integration::instance()->get_option( 'thisnew_key' );
		$connect_status = ThisNew_Integration::instance()->is_connected();

		if ( $connect_status ) {
			$dashboard->render_dashboard();
		} else if(!$connect_status && strlen($api_key) > 0) {
			$dashboard->render_connect_error();
		} else {
			$dashboard->render_connect();
		}
	}

	/**
	 * Display the thisnew connect page
	 */
	public function render_connect() {

		$status = ThisNew_Admin_Status::instance();
		$issues = array();

		$permalinks_set = $status->run_single_test( 'check_permalinks' );

		if ( $permalinks_set == ThisNew_Admin_Status::PF_STATUS_FAIL ) {
			$message      = 'WooCommerce API will not work unless your permalinks are set up correctly. Go to <a href="%s">Permalinks settings</a> and make sure that they are NOT set to "plain".';
			$settings_url = admin_url( 'options-permalink.php' );
			$issues[]     = sprintf( $message, $settings_url );
		}

		if ( strpos( get_site_url(), 'localhost' ) ) {
			$issues[] = 'You can\'t connect to ThisNew from localhost. ThisNew needs to be able reach your site to establish a connection.';
		}

		ThisNew_Admin::load_template( 'header', array( 'tabs' => ThisNew_Admin::get_tabs() ) );

		ThisNew_Admin::load_template( 'connect', array(
				'consumer_key'       => $this->_get_consumer_key(),
				'waiting_sync'       => isset( $_GET['sync-in-progress'] ),
				'consumer_key_error' => isset( $_GET['consumer-key-error'] ),
				'issues'             => $issues,
			)
		);

		if ( isset( $_GET['sync-in-progress'] ) ) {
			$emit_auth_response = 'ThisNew_Connect.send_return_message();';
			ThisNew_Admin::load_template( 'inline-script', array( 'script' => $emit_auth_response ) );
		}

		ThisNew_Admin::load_template('footer');
	}

	/**
	 * Display the thisnew connect error page
	 */
	public function render_connect_error() {

		ThisNew_Admin::load_template( 'header', array( 'tabs' => ThisNew_Admin::get_tabs() ) );

		$connect_error = ThisNew_Integration::instance()->get_connect_error();
		if ( $connect_error ) {
			ThisNew_Admin::load_template('error', array('error' => $connect_error));
		}

		ThisNew_Admin::load_template('footer');
	}

	/**
	 * Display the dashboard
	 */
	public function render_dashboard() {

		ThisNew_Admin::load_template( 'header', array( 'tabs' => ThisNew_Admin::get_tabs() ) );

		$stats = $this->_get_stats(true);
		$orders = $this->_get_orders(true);
		$error = false;

		if ( is_wp_error( $stats ) ) {
			$error = $stats;
		}
		if ( is_wp_error( $orders ) ) {
			$error = $orders;
		}

		if ( !$error || true ) {
			if(false){
				ThisNew_Admin::load_template('error-msg');
			}
			if ( $stats ) {
				ThisNew_Admin::load_template( 'stats', array( 'stats' => $stats ) );
			} else {
				ThisNew_Admin::load_template( 'ajax-loader', array( 'action' => 'get_thisnew_stats', 'message' => __( 'Loading your stats...', 'thisnew' ) ) );
			}

			if ($orders) {
				ThisNew_Admin::load_template( 'order-table', array( 'orders' => $orders ) );
			} else {
				ThisNew_Admin::load_template( 'ajax-loader', array( 'action' => 'get_thisnew_orders', 'message' => __( 'Loading your orders...', 'thisnew' ) ) );
			}

		} else {
			ThisNew_Admin::load_template( 'error', array( 'error' => $error->get_error_message('thisnew') ) );
		}
		// ThisNew_Admin::load_template( 'order-table', array( 'orders' => $orders ) );
		ThisNew_Admin::load_template( 'quick-links' );

		if ( isset( $_GET['sync-in-progress'] ) ) {
			$emit_auth_response = 'ThisNew_Connect.send_return_message();';
			ThisNew_Admin::load_template( 'inline-script', array( 'script' => $emit_auth_response ) );
		}

		ThisNew_Admin::load_template( 'footer' );
	}

	/**
	 * Ajax response for stats block
	 */
	public static function render_stats_ajax() {

		$stats = self::instance()->_get_stats();

		if ( ! empty( $stats ) && ! is_wp_error( $stats ) ) {
			ThisNew_Admin::load_template( 'stats', array( 'stats' => $stats ) );
		} else {
			ThisNew_Admin::load_template( 'error', array( 'error' => $stats->get_error_message( 'thisnew' ) ) );
		}

		exit;
	}

	/**
	 * Ajax response for stats block
	 */
	public static function render_orders_ajax() {

		$orders = self::instance()->_get_orders();

		if ( ! empty( $orders ) && is_wp_error( $orders ) ) {
			ThisNew_Admin::load_template( 'error', array( 'error' => $orders->get_error_message('thisnew') ) );
		} else {
			ThisNew_Admin::load_template( 'order-table', array( 'orders' => $orders ) );
		}

		exit;
	}

	/**
	 * Get store statistics from API
	 * @param bool $only_cached_results
	 * @return mixed
	 */
	private function _get_stats($only_cached_results = false) {
        $store_id = $this->get_option( 'thisnew_store_id' );
		$stats = get_transient( 'thisnew_stats' );
		if ( $only_cached_results || $stats ) {
			return $stats;
		}

		try {
			$stats = ThisNew_Integration::instance()->get_client()->get( 'diy-api/v1/woocommerce/store/statistics', array( 'thisnew_store_id' => $store_id ) );
			if ( ! empty( $stats['store_statistics'] ) ) {
				$stats = $stats['store_statistics'];
			}
			set_transient( 'thisnew_stats', $stats, MINUTE_IN_SECONDS * 5 ); //cache for 5 minute
		} catch (ThisNewApiException $e) {
			return new WP_Error('thisnew', 'Could not connect to ThisNew API. Please try again later!');
		} catch (ThisNewException $e) {
			return new WP_Error('thisnew', 'Could not connect to ThisNew API. Please try again later!');
		}

		return $stats;
	}

	/**
	 * Get thisnew orders from the API
	 * @param bool $only_cached_results
	 * @return mixed
	 */
	private function _get_orders($only_cached_results = false) {
		// $orders = get_transient( 'thisnew_orders' );
        $store_id = $this->get_option( 'thisnew_store_id' );
		$page=$_GET['pagenum']?$_GET['pagenum'] : 1;
		// if ( $only_cached_results || $orders ) {
		// 	return $orders;
		// }

		try {
			$order_data = ThisNew_Integration::instance()->get_client()->get( 'diy-api/v1/woocommerce/orders', array( 'page' => $page,'thisnew_store_id' => $store_id ));
            $count=0;
            $orders='';
			if ( ! empty( $order_data ) ) {
                $count=$order_data['count'];
				$orders=$order_data['orders'];
                $pageCount=intval(ceil($count/10));
                $num_links=4;
                $start=1;
                $end=1;
                if ($pageCount > 1) {
                    if ($pageCount <= $num_links) {
                        $start = 1;
                        $end = $pageCount;
                    } else {
                        $start = $page - floor($num_links / 2);
                        $end = $page + floor($num_links / 2);

                        if ($start < 1) {
                            $end += abs($start) + 1;
                            $start = 1;
                        }

                        if ($end > $pageCount) {
                            $start -= ($end - $pageCount);
                            $end = $pageCount;
                        }
                    }
                }
			}
			$orders = array( 'count' => $count, 'results' => $orders,'start'=>$start,'end'=>$end,'page'=>$page );
			// set_transient( 'thisnew_orders', $orders, MINUTE_IN_SECONDS * 5 ); //cache for 5 minute
		} catch (ThisNewApiException $e) {
			return new WP_Error('thisnew', 'Could not connect to ThisNew API. Please try again later!');
		} catch (ThisNewException $e) {
			return new WP_Error('thisnew', 'Could not connect to ThisNew API. Please try again later!');
		}

		return $orders;
	}
    public static function  ajax_send_url(){
        $consumer_key=ThisNew_Admin_Dashboard::_get_consumer_key();
        $url = ThisNew_Base::get_thisnew_host() . 'diy-d/v1/woocommerce/exist?website=' . urlencode( trailingslashit( get_home_url() ) ) . '&key=' . urlencode( $consumer_key ) . '&returnUrl=' . urlencode( get_admin_url( null,'admin.php?page=' . ThisNew_Admin::MENU_SLUG_DASHBOARD ) );
        $result = wp_remote_get($url);
        $response = json_decode( $result['body'], true );
        header('Content-Type:application/json');
        $response['callbackname']='diy-d/v1/woocommerce/exist?website=' . urlencode( trailingslashit( get_home_url() ) ) . '&key=' . urlencode( $consumer_key ) . '&returnUrl=' . urlencode( get_admin_url( null,'admin.php?page=' . ThisNew_Admin::MENU_SLUG_DASHBOARD ) );
        die(json_encode($response));
    }
	/**
	 * Get the last used consumer key fragment and use it for validating the address
	 * @return null|string
	 */
	private function _get_consumer_key() {

		global $wpdb;

		// Get the API key
        $thisnewKey = '%' . esc_sql( $wpdb->esc_like( wc_clean( self::API_KEY_SEARCH_STRING ) ) ) . '%';
        $consumer_key = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT truncated_key FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE %s ORDER BY key_id DESC LIMIT 1",
                $thisnewKey
            )
        );

		//if not found by description, it was probably manually created. try the last used key instead
		if ( ! $consumer_key ) {
			$consumer_key = $wpdb->get_var(
			    "SELECT truncated_key FROM {$wpdb->prefix}woocommerce_api_keys ORDER BY key_id DESC LIMIT 1"
            );
		}

		return $consumer_key;
	}
    /**
     * Wrapper method for getting an option
     * @param $name
     * @param array $default
     * @return bool
     */
    public function get_option( $name, $default = array() ) {
        $options  = get_option( 'woocommerce_thisnew_settings', $default );
        if ( ! empty( $options[ $name ] ) ) {
            return $options[ $name ];
        }

        return false;
    }


}