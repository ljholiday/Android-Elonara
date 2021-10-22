<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Integration
{
    const PF_API_CONNECT_STATUS = 'printful_api_connect_status';
    const PF_CONNECT_ERROR = 'printful_connect_error';

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
     * @return Printful_Client
     * @throws PrintfulException
     */
	public function get_client() {

		require_once 'class-printful-client.php';
		$client = new Printful_Client( $this->get_option( 'printful_key' ), $this->get_option( 'disable_ssl' ) == 'yes' );

		return $client;
	}

    /**
     * Check if the connection to printful is working
     * @param bool $force
     * @return bool
     * @throws PrintfulException
     */
	public function is_connected( $force = false ) {

		$api_key = $this->get_option( 'printful_key' );

		//dont need to show error - the plugin is simply not setup
		if ( empty( $api_key ) ) {
			return false;
		}

		//validate length, show error
		if ( strlen( $api_key ) != 36 ) {
			$message      = 'Invalid API key - the key must be 36 characters long. Please ensure that your API key in <a href="%s">Settings</a> matches the one in your <a href="%s">Printful dashboard</a>.';
			$settings_url = admin_url( 'admin.php?page=printful-dashboard&tab=settings' );
			$printful_url = Printful_Base::get_printful_host() . 'dashboard/';
			$this->set_connect_error(sprintf( $message, $settings_url, $printful_url ) );

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

		//attempt to connect to printful to verify the API key
		try {
			$storeData = $client->get( 'store' );
			if ( ! empty( $storeData ) && $storeData['type'] == 'woocommerce') {
				$response = true;
				$this->clear_connect_error();
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 1 ) );  //no expiry
			} elseif ( $storeData['type'] != 'woocommerce' ) {
				$message      = 'Invalid API key. This API key belongs to a ' . ucfirst( $storeData['type'] ) . ' store. Please copy the correct key from <a href="%s">Printful store settings</a> and enter it in the <a href="%s">Printful plugin settings</a>';
				$settings_url = admin_url( 'admin.php?page=printful-dashboard&tab=settings' );
				$printful_url = Printful_Base::get_printful_host() . 'dashboard/store';
				$this->set_connect_error( sprintf( $message, $settings_url, $printful_url ) );
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 0 ), MINUTE_IN_SECONDS );  //try again in 1 minute
			}
		} catch ( Exception $e ) {

			if ( $e->getCode() == 401 ) {
				$message      = 'Invalid API key. Please ensure that your API key in <a href="%s">Printful plugin settings</a> matches the one in your <a href="%s">Printful store settings</a>.';
				$settings_url = admin_url( 'admin.php?page=printful-dashboard&tab=settings' );
				$printful_url = Printful_Base::get_printful_host() . 'dashboard/store';
				$this->set_connect_error( sprintf( $message, $settings_url, $printful_url ) );
				set_transient( self::PF_API_CONNECT_STATUS, array( 'status' => 0 ), MINUTE_IN_SECONDS );  //try again in 1 minute
			} else {
				$this->set_connect_error( 'Could not connect to Printful API. Please try again later. (Error ' . $e->getCode() . ': ' . $e->getMessage() . ')' );
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
     * @throws PrintfulException
     */
	public static function ajax_force_check_connect_status() {
		if ( Printful_Integration::instance()->is_connected( true ) ) {
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
		$options  = get_option( 'woocommerce_printful_settings', $default );
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
		update_option( 'woocommerce_printful_settings', $settings );
	}
}