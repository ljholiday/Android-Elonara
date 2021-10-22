<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ThisNew_Integration
{
    const PF_API_CONNECT_STATUS = 'thisnew_api_connect_status';
    const PF_CONNECT_ERROR = 'thisnew_connect_error';

	public static $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		self::$_instance = $this;
	}

    /**
     * @return thisnew_Client
     * @throws thisnewException
     */
	public function get_client() {

		require_once 'class-thisnew-client.php';
		$client = new ThisNew_Client( $this->get_option( 'thisnew_key' ), $this->get_option( 'disable_ssl' ) == 'yes' );

		return $client;
	}

    /**
     * Check if the connection to thisnew is working
     * @param bool $force
     * @return bool
     * @throws thisnewException
     */
	public function is_connected( $force = false ) {

		$api_key = $this->get_option( 'thisnew_key' );
        $store_id = $this->get_option( 'thisnew_store_id' );
		//dont need to show error - the plugin is simply not setup
		if ( empty( $api_key ) ) {
			return false;
		}

		//validate length, show error
		if ( strlen( $api_key ) != 36 ) {
			$message      = 'Invalid API key - the key must be 36 characters long. Please delete the API key in the <a href="'. get_home_url().'/wp-admin/admin.php?page=thisnew-dashboard&tab=settings'.'">Settings</a> and connect again.';
			$settings_url = admin_url( 'admin.php?page=thisnew-dashboard&tab=settings' );
			$thisnew_url = ThisNew_Base::get_thisnew_host();
			$this->set_connect_error(sprintf( $message, $settings_url, $thisnew_url ) );

			return false;
		}

		//show connect status from cache
		if ( ! $force ) {
			$connected = get_transient( self::PF_API_CONNECT_STATUS );
			if ( $connected && $connected['status'] == 1 ) {
				$this->clear_connect_error();

				return true;
			} else if ( $connected && $connected['status'] == 0 ) {    //try again in a minute
				return false;
			}
		}

		$client   = $this->get_client();
		$response = false;

		//attempt to connect to thisnew to verify the API key
		try {

			$storeData = $client->get( 'diy-api/v1/woocommerce/store', array( 'thisnew_store_id' => $store_id ));
			if ( ! empty( $storeData ) && $storeData['type'] == 'woocommerce') {
				$response = true;
				$this->clear_connect_error();
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 1 ) );  //no expiry
			} elseif ( $storeData['type'] != 'woocommerce' ) {
				$message      = 'Synchronization failed, the store has been deleted from ThisNew.';
				$settings_url = admin_url( 'admin.php?page=thisnew-dashboard&tab=settings' );
				$thisnew_url = ThisNew_Base::get_thisnew_host() . 'dashboard/store';
				$this->set_connect_error( sprintf( $message, $settings_url, $thisnew_url ) );
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 0 ), MINUTE_IN_SECONDS );  //try again in 1 minute
			}
		} catch ( Exception $e ) {

			if ( $e->getCode() == 401 ) {
				$message      = 'Invalid API key. Please ensure that your API key in <a href="%s">ThisNew plugin settings</a> matches the one in your <a href="%s">ThisNew store settings</a>.';
				$settings_url = admin_url( 'admin.php?page=thisnew-dashboard&tab=settings' );
				$thisnew_url = ThisNew_Base::get_thisnew_host() . 'dashboard/store';
				$this->set_connect_error( sprintf( $message, $settings_url, $thisnew_url ) );
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 0 ), MINUTE_IN_SECONDS );  //try again in 1 minute
			} else {
				$this->set_connect_error( 'Could not connect to ThisNew API. Please try again later. (Error ' . $e->getCode() . ': ' . $e->getMessage() . ')' );
			}

			//do nothing
			set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 0 ), MINUTE_IN_SECONDS );  //try again in 1 minute
		}

		return $response;
	}

	/**
	 * Update connect error message
	 * @param string $error
	 */
	public function set_connect_error($error = '') {
		update_option( self::PF_CONNECT_ERROR, $error );
	}

	/**
	 * Get current connect error message
	 */
	public function get_connect_error() {
		return get_option( self::PF_CONNECT_ERROR, false );
	}

	/**
	 * Remove option used for storing current connect error
	 */
	public function clear_connect_error() {
		delete_option( self::PF_CONNECT_ERROR );
	}

    /**
     * AJAX call endpoint for connect status check
     * @throws thisnewException
     */
	public static function ajax_force_check_connect_status() {
		if ( ThisNew_Integration::instance()->is_connected( true ) ) {
			die( 'OK' );
		}

		die( 'FAIL' );
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

	/**
	 * Save the setting
	 * @param $settings
	 */
	public function update_settings( $settings ) {
		delete_transient( self::PF_API_CONNECT_STATUS );    //remove the successful API status since API key could have changed
		update_option( 'woocommerce_thisnew_settings', $settings );
	}
}